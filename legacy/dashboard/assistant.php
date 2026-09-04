<?php
// ============================================================
//  ASSISTANT CONVERSATIONNEL A REGLES — La Pharmacie Senegalaise
//  Recoit une question, detecte l'intention par mots-cles,
//  interroge le moteur d'analyse + la base, et repond.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/moteur_ia.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$question = trim($_POST['question'] ?? '');
$q = mb_strtolower($question);
$role = roleUtilisateur();
$sid  = structureId();

// Fonction utilitaire : la question contient-elle un de ces mots ?
function contient($q, $mots) {
    foreach ($mots as $m) if (mb_strpos($q, $m) !== false) return true;
    return false;
}

$reponse = '';

// ---------- SALUTATIONS ----------
if (contient($q, ['bonjour','salut','bonsoir','coucou','cc','hello'])) {
    $reponse = "Bonjour 👋 Je suis l'assistant de La Pharmacie Sénégalaise. Je peux vous renseigner sur les stocks, les ruptures, les rééquilibrages, les subventions et le fonctionnement de la plateforme. Posez-moi une question !";
}
// ---------- AIDE / CAPACITES ----------
elseif (contient($q, ['aide','help','que peux','quoi faire','comment ça marche','fonctionne','capacités'])) {
    $reponse = "Voici ce que je peux faire :<br>"
        . "• <b>Ruptures</b> : « quels médicaments risquent la rupture ? »<br>"
        . "• <b>Stocks</b> : « quels sont les stocks critiques ? »<br>"
        . "• <b>Rééquilibrage</b> : « quels transferts proposes-tu ? »<br>"
        . "• <b>Déséquilibres</b> : « les villages sont-ils défavorisés ? »<br>"
        . "• <b>Subventions / commandes</b> : « comment demander une subvention ? »<br>"
        . "Posez votre question simplement.";
}
// ---------- PREDICTION DE RUPTURE ----------
elseif (contient($q, ['rupture','va manquer','bientôt vide','plus de stock','tomber en panne','jours restants'])) {
    $structure = in_array($role, ['pra','pharmacie']) ? $sid : null;
    $ruptures = predireRuptures($pdo, $structure);
    if (!$ruptures) {
        $reponse = "✅ Bonne nouvelle : aucune rupture imminente détectée pour le moment.";
    } else {
        $reponse = "⚠️ Voici les médicaments à surveiller (du plus urgent au moins urgent) :<br>";
        foreach (array_slice($ruptures, 0, 6) as $r) {
            $jr = $r['jours_restants'] !== null ? $r['jours_restants'] . " jour(s) restant(s)" : "rythme de vente inconnu";
            $ic = $r['niveau'] === 'critique' ? '🔴' : ($r['niveau'] === 'alerte' ? '🟠' : '🟡');
            $reponse .= "$ic <b>" . htmlspecialchars($r['medicament']) . "</b> à " . htmlspecialchars($r['structure'])
                     . " — stock " . $r['quantite'] . ", $jr<br>";
        }
    }
}
// ---------- STOCKS CRITIQUES ----------
elseif (contient($q, ['stock critique','stocks critiques','sous le seuil','niveau bas','stock bas','en alerte'])) {
    $where = in_array($role,['pra','pharmacie']) ? "AND s.structure_id = " . intval($sid) : "";
    $rows = $pdo->query("
        SELECT m.nom, m.dosage, s.quantite, m.seuil_alerte, st.nom AS structure_nom
        FROM stocks s JOIN medicaments m ON s.medicament_id=m.id
        JOIN structures st ON s.structure_id=st.id
        WHERE s.quantite < m.seuil_alerte $where
        ORDER BY (s.quantite/m.seuil_alerte) ASC LIMIT 8
    ")->fetchAll();
    if (!$rows) {
        $reponse = "✅ Aucun stock sous le seuil critique actuellement.";
    } else {
        $reponse = "🔴 Stocks sous le seuil d'alerte :<br>";
        foreach ($rows as $r) {
            $reponse .= "• <b>" . htmlspecialchars($r['nom'].' '.$r['dosage']) . "</b> — "
                     . $r['quantite'] . "/" . $r['seuil_alerte'] . " (" . htmlspecialchars($r['structure_nom']) . ")<br>";
        }
    }
}
// ---------- REEQUILIBRAGE ----------
elseif (contient($q, ['rééquilibrage','reequilibrage','transfert','transferer','déplacer stock','equilibrer'])) {
    $sugg = suggererReequilibrages($pdo);
    if (!$sugg) {
        $reponse = "Aucun transfert nécessaire pour l'instant : les stocks sont relativement équilibrés entre les régions.";
    } else {
        $reponse = "🔄 Transferts suggérés pour rééquilibrer :<br>";
        foreach (array_slice($sugg, 0, 6) as $s) {
            $ic = $s['priorite'] === 'critique' ? '🔴' : '🟠';
            $reponse .= "$ic <b>" . htmlspecialchars($s['medicament']) . "</b> : "
                     . htmlspecialchars($s['source']) . " (" . $s['source_pct'] . "%) → "
                     . htmlspecialchars($s['destination']) . " (" . $s['dest_pct'] . "%), "
                     . $s['quantite'] . " unités<br>";
        }
    }
}
// ---------- DESEQUILIBRES VILLE/VILLAGE ----------
elseif (contient($q, ['village','rural','ville','défavorisé','defavorise','équité','equite','desequilibre','déséquilibre','campagne'])) {
    $d = detecterDesequilibres($pdo);
    if ($d['desequilibre']) {
        $de = $d['desequilibre'];
        $reponse = "⚠️ <b>" . $de['constat'] . ".</b><br>"
                 . "Niveau moyen en ville : <b>" . $de['niveau_ville'] . "%</b><br>"
                 . "Niveau moyen en zone rurale/village : <b>" . $de['niveau_rural'] . "%</b><br>"
                 . "Écart : " . $de['ecart'] . " points (" . $de['gravite'] . "). "
                 . "Un rééquilibrage vers les zones rurales est recommandé.";
    } else {
        $reponse = "✅ L'équilibre entre les zones urbaines et rurales est correct actuellement. Aucune zone n'est nettement défavorisée.";
    }
}
// ---------- SUBVENTIONS ----------
elseif (contient($q, ['subvention','aide financière','aide financiere','financer','pas les moyens','budget'])) {
    if ($role === 'pra') {
        $reponse = "💰 En tant que PRA, vous pouvez signaler une pharmacie en difficulté pour qu'elle soit subventionnée par l'État : allez dans la section <b>Subventions</b> du menu, puis remplissez le formulaire (pharmacie, médicaments, montant estimé, motif). L'État examinera la demande.";
    } elseif ($role === 'etat') {
        $n = (int)$pdo->query("SELECT COUNT(*) FROM subventions WHERE statut='en_attente'")->fetchColumn();
        $reponse = "💰 Vous avez actuellement <b>$n demande(s) de subvention</b> en attente. Rendez-vous dans la section <b>Subventions</b> pour les examiner et les approuver.";
    } else {
        $reponse = "💰 Les subventions sont gérées par votre PRA et l'État. Si votre pharmacie est en difficulté financière, contactez votre PRA pour qu'il signale votre situation.";
    }
}
// ---------- COMMANDES ----------
elseif (contient($q, ['commande','commander','passer commande','réapprovision','reapprovision','approvision'])) {
    if ($role === 'pharmacie') {
        $reponse = "📦 Pour passer une commande : menu <b>Commander</b>, choisissez le médicament, la quantité et l'urgence, puis soumettez. Votre PRA recevra la demande et la validera.";
    } elseif ($role === 'pra') {
        $n = (int)$pdo->prepare("SELECT COUNT(*) FROM commandes c JOIN utilisateurs u ON c.demandeur_id=u.id JOIN structures st ON u.structure_id=st.id WHERE st.pra_parent=? AND c.statut='en_attente'")->execute([$sid]);
        $reponse = "📦 Les demandes de vos pharmacies apparaissent dans <b>Demandes pharmacies</b>. Vous pouvez les valider ou les refuser.";
    } else {
        $reponse = "📦 Les commandes circulent des pharmacies vers les PRA, puis sont validées par l'État dans la section <b>Validation</b>.";
    }
}
// ---------- OBLIGATION DE REAPPROVISIONNEMENT (regle de gestion) ----------
elseif (contient($q, ['obliger','obligation','forcer','imposer','contraindre'])) {
    $reponse = "⚖️ <b>Règle de gestion :</b> l'État peut obliger une pharmacie à se réapprovisionner si <b>deux conditions</b> sont réunies :<br>"
             . "1️⃣ Son stock est à un <b>seuil critique</b><br>"
             . "2️⃣ La demande dans sa zone est jugée <b>haute</b> (médicament très vendu) <b>ou nécessaire</b> (pic anormal = risque d'épidémie, ou pharmacies voisines aussi en difficulté).<br>"
             . "L'analyse de la zone se fait automatiquement à partir des ventes réelles.";
}
// ---------- REMERCIEMENTS ----------
elseif (contient($q, ['merci','thanks','nickel','parfait','super','génial'])) {
    $reponse = "Avec plaisir 😊 N'hésitez pas si vous avez d'autres questions sur la plateforme.";
}
// ---------- PAR DEFAUT ----------
else {
    $reponse = "Je n'ai pas bien compris votre question 🤔. Je peux vous aider sur : les <b>ruptures</b>, les <b>stocks critiques</b>, les <b>rééquilibrages</b>, les <b>déséquilibres ville/village</b>, les <b>subventions</b> et les <b>commandes</b>. Essayez par exemple : « quels médicaments risquent la rupture ? »";
}

echo json_encode(['reponse' => $reponse]);
