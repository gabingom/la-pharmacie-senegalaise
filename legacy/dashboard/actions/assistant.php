<?php
// ============================================================
//  ASSISTANT CONVERSATIONNEL — Moteur de recherche par score
//  Charge les bases de connaissances (fonctionnement, sante,
//  donnees) et trouve la meilleure correspondance par scoring.
//  Ton institutionnel. Les reponses 'donnees' sont dynamiques.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/moteur_ia.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$question = trim($_POST['question'] ?? '');
$role = roleUtilisateur();
$sid  = structureId();

// ---------- Normalisation du texte (enleve accents, ponctuation) ----------
function normaliser($txt) {
    $txt = mb_strtolower($txt, 'UTF-8');
    $remplace = ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
                 'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u',
                 'ç'=>'c','’'=>"'",'œ'=>'oe'];
    $txt = strtr($txt, $remplace);
    $txt = preg_replace('/[^a-z0-9\' ]/', ' ', $txt);
    $txt = preg_replace('/\s+/', ' ', $txt);
    return trim($txt);
}

// ---------- Mots vides (ignores dans le scoring) ----------
$STOPWORDS = ['le','la','les','un','une','des','de','du','d','je','tu','il','on','nous','vous',
    'mon','ma','mes','ton','ta','tes','son','sa','ses','ce','cet','cette','est','sont','a','as',
    'au','aux','et','ou','que','qui','quoi','comment','pour','par','sur','dans','en','avec','quel',
    'quels','quelle','quelles','combien','y','t','l','se','si','ne','pas','plus','c','ca','sa','the'];

// ---------- Chargement des bases de connaissances ----------
$kb = [];
foreach (['fonctionnement','sante','donnees'] as $theme) {
    $f = __DIR__ . "/../../config/kb/$theme.php";
    if (file_exists($f)) {
        foreach (require $f as $entree) {
            $entree['theme'] = $theme;
            $kb[] = $entree;
        }
    }
}

// ---------- Moteur de scoring ----------
$qn = normaliser($question);
$motsQ = array_filter(explode(' ', $qn), function($m) use ($STOPWORDS) {
    return strlen($m) > 1 && !in_array($m, $STOPWORDS);
});

$meilleur = null;
$meilleurScore = 0;

foreach ($kb as $entree) {
    $score = 0;
    foreach ($entree['mots'] as $variante) {
        $vn = normaliser($variante);
        // Correspondance exacte de la variante entiere dans la question = gros bonus
        if ($vn !== '' && mb_strpos($qn, $vn) !== false) {
            $score += 10 + mb_strlen($vn) / 5;
        }
        // Correspondance mot a mot
        $motsV = array_filter(explode(' ', $vn), function($m) use ($STOPWORDS) {
            return strlen($m) > 1 && !in_array($m, $STOPWORDS);
        });
        foreach ($motsQ as $mq) {
            foreach ($motsV as $mv) {
                if ($mq === $mv) {
                    $score += 3;
                } elseif (strlen($mq) > 3 && (mb_strpos($mv, $mq) !== false || mb_strpos($mq, $mv) !== false)) {
                    $score += 1.5; // correspondance partielle (racine commune)
                }
            }
        }
    }
    if ($score > $meilleurScore) {
        $meilleurScore = $score;
        $meilleur = $entree;
    }
}

// ---------- Seuil minimal de confiance ----------
$SEUIL = 4;

// ---------- Construction de la reponse ----------
if ($meilleur && $meilleurScore >= $SEUIL) {
    if (isset($meilleur['fn'])) {
        // Reponse dynamique : on appelle la fonction de donnees
        $reponse = reponseDynamique($meilleur['fn'], $pdo, $role, $sid);
    } else {
        $reponse = $meilleur['rep'];
    }
} else {
    // Aucune correspondance fiable : reponse de repli avec suggestions
    $reponse = repli($qn);
}

echo json_encode(['reponse' => $reponse]);


// ============================================================
//  FONCTIONS DE REPONSE DYNAMIQUE (lisent la base en direct)
// ============================================================
function reponseDynamique($fn, $pdo, $role, $sid) {
    switch ($fn) {

        case 'stocks_critiques': {
            $where = in_array($role,['pra','pharmacie']) ? "AND s.structure_id = ".intval($sid) : "";
            $rows = $pdo->query("
                SELECT m.nom, m.dosage, s.quantite, m.seuil_alerte, st.nom AS structure_nom
                FROM stocks s JOIN medicaments m ON s.medicament_id=m.id
                JOIN structures st ON s.structure_id=st.id
                WHERE s.quantite < m.seuil_alerte $where
                ORDER BY (s.quantite/m.seuil_alerte) ASC LIMIT 8
            ")->fetchAll();
            if (!$rows) return "Aucun stock ne se trouve actuellement sous le seuil d'alerte. La situation est satisfaisante.";
            $r = "Les médicaments suivants se trouvent sous le seuil d'alerte :<br>";
            foreach ($rows as $x) {
                $r .= "— <b>".htmlspecialchars($x['nom'].' '.$x['dosage'])."</b> : "
                    .$x['quantite']."/".$x['seuil_alerte']." unités (".htmlspecialchars($x['structure_nom']).")<br>";
            }
            $r .= "Il est recommandé d'engager les démarches de réapprovisionnement correspondantes.";
            return $r;
        }

        case 'predire_ruptures': {
            $structure = in_array($role,['pra','pharmacie']) ? $sid : null;
            $rup = predireRuptures($pdo, $structure);
            if (!$rup) return "Aucune rupture imminente n'est détectée au regard des rythmes de consommation actuels.";
            $r = "Estimation des risques de rupture, du plus urgent au moins urgent :<br>";
            foreach (array_slice($rup,0,6) as $x) {
                $jr = $x['jours_restants']!==null ? $x['jours_restants']." jour(s) estimé(s) avant rupture" : "rythme de consommation non déterminé";
                $r .= "— <b>".htmlspecialchars($x['medicament'])."</b> à ".htmlspecialchars($x['structure'])
                    ." : stock de ".$x['quantite']." unités, $jr.<br>";
            }
            return $r;
        }

        case 'suggerer_reequilibrages': {
            $s = suggererReequilibrages($pdo);
            if (!$s) return "Aucun transfert de rééquilibrage n'est requis actuellement : les stocks sont relativement équilibrés entre les régions.";
            $r = "Transferts de rééquilibrage suggérés :<br>";
            foreach (array_slice($s,0,6) as $x) {
                $r .= "— <b>".htmlspecialchars($x['medicament'])."</b> : de ".htmlspecialchars($x['source'])
                    ." (".$x['source_pct']."%) vers ".htmlspecialchars($x['destination'])
                    ." (".$x['dest_pct']."%), pour ".$x['quantite']." unités. Priorité : ".$x['priorite'].".<br>";
            }
            $r .= "Ces propositions sont soumises à la validation de l'État.";
            return $r;
        }

        case 'detecter_desequilibres': {
            $d = detecterDesequilibres($pdo);
            if ($d['desequilibre']) {
                $de = $d['desequilibre'];
                return "Un déséquilibre a été constaté. ".$de['constat'].".<br>"
                    ."Niveau moyen en zone urbaine : <b>".$de['niveau_ville']."%</b>. "
                    ."Niveau moyen en zone rurale ou village : <b>".$de['niveau_rural']."%</b>. "
                    ."L'écart est de ".$de['ecart']." points (gravité ".$de['gravite']."). "
                    ."Un rééquilibrage en faveur des zones rurales est recommandé.";
            }
            return "L'équilibre entre zones urbaines et rurales est satisfaisant. Aucune zone n'apparaît nettement défavorisée.";
        }

        case 'nb_medicaments': {
            $n = (int)$pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
            return "La plateforme assure actuellement le suivi de <b>$n médicament(s)</b> référencé(s).";
        }

        case 'nb_pra': {
            $n = (int)$pdo->query("SELECT COUNT(*) FROM structures WHERE type='pra' AND statut='active'")->fetchColumn();
            return "On dénombre actuellement <b>$n PRA</b> actif(s) sur le territoire.";
        }

        case 'nb_pharmacies': {
            $n = (int)$pdo->query("SELECT COUNT(*) FROM structures WHERE type='pharmacie' AND statut='active'")->fetchColumn();
            return "On dénombre actuellement <b>$n pharmacie(s)</b> active(s) sur la plateforme.";
        }

        case 'nb_alertes': {
            $n = (int)$pdo->query("SELECT COUNT(*) FROM alertes WHERE lue=0")->fetchColumn();
            $c = (int)$pdo->query("SELECT COUNT(*) FROM alertes WHERE lue=0 AND priorite='critique'")->fetchColumn();
            return "Il y a actuellement <b>$n alerte(s)</b> active(s), dont <b>$c critique(s)</b>.";
        }

        case 'commandes_attente': {
            if ($role === 'pra') {
                $st = $pdo->prepare("SELECT COUNT(*) FROM commandes c JOIN utilisateurs u ON c.demandeur_id=u.id JOIN structures s ON u.structure_id=s.id WHERE s.pra_parent=? AND c.statut='en_attente'");
                $st->execute([$sid]); $n=(int)$st->fetchColumn();
                return "Vous avez <b>$n commande(s)</b> de pharmacies en attente de traitement dans la section « Demandes pharmacies ».";
            }
            $n = (int)$pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();
            return "Il y a actuellement <b>$n commande(s)</b> en attente de validation.";
        }

        case 'subventions_attente': {
            $n = (int)$pdo->query("SELECT COUNT(*) FROM subventions WHERE statut='en_attente'")->fetchColumn();
            return "Il y a actuellement <b>$n demande(s) de subvention</b> en attente d'examen.";
        }

        case 'mon_stock': {
            if (!in_array($role,['pra','pharmacie'])) return "Cette information concerne les comptes PRA et Pharmacie. En tant qu'État, consultez la section « Régions » pour une vue nationale.";
            $st = $pdo->prepare("SELECT COUNT(*) refs, COALESCE(SUM(quantite),0) total FROM stocks WHERE structure_id=?");
            $st->execute([$sid]); $x=$st->fetch();
            return "Votre stock comporte <b>".$x['refs']." référence(s)</b> pour un total de <b>".number_format($x['total'],0,',',' ')." unités</b>.";
        }

        case 'mes_ventes': {
            if ($role !== 'pharmacie') return "Le suivi des ventes concerne les comptes Pharmacie.";
            $st = $pdo->prepare("SELECT COALESCE(SUM(quantite),0) FROM ventes WHERE structure_id=? AND date_vente >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $st->execute([$sid]); $n=(int)$st->fetchColumn();
            return "Vous avez enregistré <b>$n unité(s)</b> vendue(s) au cours des 30 derniers jours.";
        }

        case 'top_ventes': {
            $where = $role==='pharmacie' ? "WHERE v.structure_id=".intval($sid) : "";
            $row = $pdo->query("
                SELECT m.nom, m.dosage, SUM(v.quantite) total
                FROM ventes v JOIN medicaments m ON v.medicament_id=m.id
                $where GROUP BY v.medicament_id ORDER BY total DESC LIMIT 3
            ")->fetchAll();
            if (!$row) return "Aucune vente n'a encore été enregistrée pour établir un classement.";
            $r = "Médicaments les plus consommés :<br>";
            $i=1; foreach($row as $x){ $r.="$i. <b>".htmlspecialchars($x['nom'].' '.$x['dosage'])."</b> — ".number_format($x['total'],0,',',' ')." unités<br>"; $i++; }
            return $r;
        }

        case 'peremptions_proches': {
            $where = in_array($role,['pra','pharmacie']) ? "AND s.structure_id=".intval($sid) : "";
            $n = (int)$pdo->query("SELECT COUNT(*) FROM stocks s WHERE s.date_peremption IS NOT NULL AND DATEDIFF(s.date_peremption,CURDATE()) BETWEEN 0 AND 60 $where")->fetchColumn();
            if (!$n) return "Aucun lot n'arrive à péremption dans les 60 prochains jours.";
            return "<b>$n lot(s)</b> arrive(nt) à péremption dans les 60 prochains jours. Consultez la section « Péremptions » pour le détail.";
        }

        case 'equite_nationale': {
            $regions = $pdo->query("
                SELECT COALESCE(SUM(s.quantite),0) stock, COALESCE(SUM(m.seuil_alerte),1) seuil
                FROM structures st LEFT JOIN stocks s ON s.structure_id=st.id
                LEFT JOIN medicaments m ON s.medicament_id=m.id
                WHERE st.type='pra' GROUP BY st.id
            ")->fetchAll();
            if (!$regions) return "Données insuffisantes pour évaluer l'équité nationale.";
            $ok=0; foreach($regions as $r){ if($r['seuil']>0 && $r['stock']/$r['seuil']*100>=50) $ok++; }
            $pct = round($ok/count($regions)*100);
            return "Le niveau d'équité inter-régions est actuellement de <b>$pct%</b> (proportion de régions disposant d'un stock satisfaisant).";
        }

        default:
            return "Cette information n'est pas disponible pour le moment.";
    }
}

// ============================================================
//  REPONSE DE REPLI (aucune correspondance fiable)
// ============================================================
function repli($qn) {
    // Salutations
    if (preg_match('/\b(bonjour|bonsoir|salut|coucou|hello)\b/', $qn)) {
        return "Bonjour. Je suis l'assistant de La Pharmacie Sénégalaise. Je peux vous renseigner sur le fonctionnement de la plateforme, les données de stock et de consommation, ainsi que sur les aspects de logistique sanitaire. Quelle est votre question ?";
    }
    if (preg_match('/\b(merci|remercie)\b/', $qn)) {
        return "Je vous en prie. Je reste à votre disposition pour toute autre question.";
    }
    if (preg_match('/\b(aide|help|aider|capacite)\b/', $qn)) {
        return "Je peux vous assister sur trois domaines : le <b>fonctionnement de la plateforme</b> (comptes, commandes, subventions, rééquilibrage), les <b>données</b> en temps réel (stocks critiques, ruptures, ventes), et la <b>logistique sanitaire</b> (chaîne du froid, épidémies, médicaments). Formulez votre question simplement.";
    }
    return "Je n'ai pas identifié de réponse précise à votre demande. Vous pouvez m'interroger sur :<br>"
        ."— le <b>fonctionnement</b> de la plateforme (commandes, subventions, comptes, rééquilibrage) ;<br>"
        ."— les <b>données</b> en temps réel (stocks critiques, risques de rupture, ventes) ;<br>"
        ."— la <b>logistique sanitaire</b> (chaîne du froid, épidémies, médicaments essentiels).<br>"
        ."Veuillez reformuler votre question en précisant le sujet souhaité.";
}
