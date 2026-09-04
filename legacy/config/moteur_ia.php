<?php
// ============================================================
//  MOTEUR D'ANALYSE INTELLIGENTE — La Pharmacie Senegalaise
//  Calculs bases sur les vraies donnees de la base.
//  Sert au tableau de bord ET a l'assistant conversationnel.
// ============================================================

/**
 * 1) PREDICTION DE RUPTURE
 * Pour chaque stock sous le seuil, estime le nombre de jours restants
 * avant rupture, base sur le rythme de ventes reel des 30 derniers jours.
 * Retourne un tableau trie du plus urgent au moins urgent.
 */
function predireRuptures($pdo, $structure_id = null) {
    $where = $structure_id ? "AND s.structure_id = " . intval($structure_id) : "";
    $stocks = $pdo->query("
        SELECT s.id, s.structure_id, s.quantite, s.medicament_id,
               m.nom, m.dosage, m.seuil_alerte,
               st.nom AS structure_nom, st.region, st.zone
        FROM stocks s
        JOIN medicaments m ON s.medicament_id = m.id
        JOIN structures st ON s.structure_id = st.id
        WHERE 1=1 $where
    ")->fetchAll();

    $resultats = [];
    foreach ($stocks as $s) {
        // Ventes des 30 derniers jours pour ce medicament dans cette structure
        $v = $pdo->prepare("
            SELECT COALESCE(SUM(quantite),0) AS total
            FROM ventes
            WHERE structure_id = ? AND medicament_id = ?
              AND date_vente >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $v->execute([$s['structure_id'], $s['medicament_id']]);
        $ventes30j = (int)$v->fetchColumn();

        // Rythme moyen par jour
        $parJour = $ventes30j / 30;

        // Estimation jours restants
        if ($parJour > 0) {
            $joursRestants = floor($s['quantite'] / $parJour);
        } else {
            $joursRestants = null; // pas de ventes => pas de projection
        }

        // On ne garde que les cas pertinents : stock sous le seuil OU rupture proche
        $sousSeuil = $s['quantite'] < $s['seuil_alerte'];
        $ruptureProche = ($joursRestants !== null && $joursRestants <= 14);

        if ($sousSeuil || $ruptureProche) {
            $resultats[] = [
                'medicament'    => $s['nom'] . ' ' . $s['dosage'],
                'structure'     => $s['structure_nom'],
                'region'        => $s['region'],
                'zone'          => $s['zone'],
                'quantite'      => (int)$s['quantite'],
                'seuil'         => (int)$s['seuil_alerte'],
                'ventes_30j'    => $ventes30j,
                'par_jour'      => round($parJour, 1),
                'jours_restants'=> $joursRestants,
                'niveau'        => $joursRestants !== null && $joursRestants <= 3 ? 'critique'
                                  : ($sousSeuil ? 'alerte' : 'surveiller'),
            ];
        }
    }

    // Tri : les plus urgents d'abord (jours restants croissant, null a la fin)
    usort($resultats, function($a, $b) {
        if ($a['jours_restants'] === null) return 1;
        if ($b['jours_restants'] === null) return -1;
        return $a['jours_restants'] <=> $b['jours_restants'];
    });
    return $resultats;
}

/**
 * 2) SUGGESTIONS DE REEQUILIBRAGE
 * Detecte les structures en surstock pour un medicament et les structures
 * en deficit pour le meme medicament, et propose des transferts.
 */
function suggererReequilibrages($pdo) {
    // Seuil de surstock (parametre configurable, defaut 80%)
    $seuilSurstock = 80;
    try {
        $p = $pdo->query("SELECT valeur FROM parametres WHERE cle='seuil_surstock'")->fetchColumn();
        if ($p) $seuilSurstock = (int)$p;
    } catch (Exception $e) {}

    // Etat des stocks : on croise TOUS les PRA avec TOUS les medicaments
    // presents quelque part dans le reseau des PRA.
    // Un PRA sans ligne de stock pour un medicament est considere a 0
    // (sinon un PRA qui n'a jamais saisi le produit resterait invisible).
    $rows = $pdo->query("
        SELECT st.id AS structure_id,
               m.id  AS medicament_id,
               COALESCE(s.quantite, 0) AS quantite,
               m.nom, m.dosage, m.seuil_alerte,
               st.nom AS structure_nom, st.region, st.zone, st.type
        FROM structures st
        CROSS JOIN medicaments m
        LEFT JOIN (
            SELECT structure_id, medicament_id, SUM(quantite) AS quantite
            FROM stocks GROUP BY structure_id, medicament_id
        ) s ON s.structure_id = st.id AND s.medicament_id = m.id
        WHERE st.type = 'pra' AND st.statut = 'active'
          AND m.id IN (
              SELECT DISTINCT s2.medicament_id
              FROM stocks s2
              JOIN structures st2 ON s2.structure_id = st2.id
              WHERE st2.type = 'pra' AND s2.quantite > 0
          )
    ")->fetchAll();

    // Regrouper par medicament
    $parMed = [];
    foreach ($rows as $r) {
        $parMed[$r['medicament_id']][] = $r;
    }

    $suggestions = [];
    foreach ($parMed as $medId => $structures) {
        $surstock = [];
        $deficit  = [];
        foreach ($structures as $st) {
            $pct = $st['seuil_alerte'] > 0 ? ($st['quantite'] / $st['seuil_alerte'] * 100) : 100;
            if ($pct >= $seuilSurstock + 20) {       // bien au-dessus = peut ceder
                $surstock[] = $st + ['pct' => round($pct)];
            } elseif ($pct < 50) {                   // en dessous = a besoin
                $deficit[] = $st + ['pct' => round($pct)];
            }
        }
        // Associer surstock et deficit
        foreach ($deficit as $d) {
            foreach ($surstock as $s) {
                if ($s['structure_id'] != $d['structure_id']) {
                    $qte = min(
                        $s['quantite'] - $s['seuil_alerte'],   // ce que la source peut ceder
                        $d['seuil_alerte'] - $d['quantite']    // ce dont la destination a besoin
                    );
                    if ($qte > 0) {
                        $suggestions[] = [
                            'medicament'  => $d['nom'] . ' ' . $d['dosage'],
                            'source'      => $s['structure_nom'],
                            'source_pct'  => $s['pct'],
                            'destination' => $d['structure_nom'],
                            'dest_pct'    => $d['pct'],
                            'dest_zone'   => $d['zone'],
                            'quantite'    => (int)$qte,
                            'priorite'    => $d['pct'] < 20 ? 'critique' : 'moderee',
                        ];
                    }
                    break; // une source par deficit suffit
                }
            }
        }
    }
    return $suggestions;
}

/**
 * 3) DETECTION DES DESEQUILIBRES VILLE / VILLAGE
 * Compare le niveau de stock moyen des zones 'ville' vs 'village'/'rural'
 * pour reperer si les zones rurales sont defavorisees.
 */
function detecterDesequilibres($pdo) {
    $rows = $pdo->query("
        SELECT st.zone,
               AVG(CASE WHEN m.seuil_alerte > 0 THEN s.quantite / m.seuil_alerte * 100 ELSE 100 END) AS niveau_moyen,
               COUNT(DISTINCT st.id) AS nb_structures
        FROM stocks s
        JOIN medicaments m ON s.medicament_id = m.id
        JOIN structures st ON s.structure_id = st.id
        WHERE st.type IN ('pharmacie','pra')
        GROUP BY st.zone
    ")->fetchAll();

    $zones = [];
    foreach ($rows as $r) {
        $zones[$r['zone']] = [
            'niveau' => round($r['niveau_moyen']),
            'nb'     => (int)$r['nb_structures'],
        ];
    }

    $ville   = $zones['ville']['niveau']   ?? null;
    $village = $zones['village']['niveau'] ?? null;
    $rural   = $zones['rural']['niveau']   ?? null;

    // Comparer ville vs (village + rural)
    $nivVille = $ville;
    $nivRural = null;
    if ($village !== null && $rural !== null) $nivRural = round(($village + $rural) / 2);
    elseif ($village !== null) $nivRural = $village;
    elseif ($rural !== null)   $nivRural = $rural;

    $desequilibre = null;
    if ($nivVille !== null && $nivRural !== null) {
        $ecart = $nivVille - $nivRural;
        if ($ecart > 25) {
            $desequilibre = [
                'constat' => "Les zones rurales/villages sont defavorisees",
                'niveau_ville'  => $nivVille,
                'niveau_rural'  => $nivRural,
                'ecart'         => $ecart,
                'gravite'       => $ecart > 50 ? 'fort' : 'modere',
            ];
        }
    }

    return [
        'zones'        => $zones,
        'desequilibre' => $desequilibre,
    ];
}

/**
 * 4) JUGER LA DEMANDE D'UN MEDICAMENT DANS UNE ZONE
 * "Haute" = beaucoup vendu dans la zone.
 * "Necessaire" = pic anormal (risque epidemie) OU pharmacies voisines aussi en difficulte.
 */
function jugerDemandeZone($pdo, $medicament_id, $region) {
    // Ventes du medicament dans la region sur 30 jours
    $v = $pdo->prepare("
        SELECT COALESCE(SUM(v.quantite),0) AS total
        FROM ventes v
        JOIN structures st ON v.structure_id = st.id
        WHERE v.medicament_id = ? AND st.region = ?
          AND v.date_vente >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $v->execute([$medicament_id, $region]);
    $ventes30j = (int)$v->fetchColumn();

    // Moyenne nationale du medicament sur 30 jours (pour comparer)
    $m = $pdo->prepare("
        SELECT COALESCE(SUM(quantite),0)
        FROM ventes
        WHERE medicament_id = ? AND date_vente >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $m->execute([$medicament_id]);
    $totalNational = (int)$m->fetchColumn();

    // Nombre de regions actives
    $nbRegions = (int)$pdo->query("SELECT COUNT(DISTINCT region) FROM structures WHERE type='pra'")->fetchColumn();
    $moyenneParRegion = $nbRegions > 0 ? $totalNational / $nbRegions : 0;

    // Demande haute si ventes de la region > 1.5x la moyenne nationale par region
    $demandeHaute = $moyenneParRegion > 0 && $ventes30j > $moyenneParRegion * 1.5;

    // Pic anormal (potentiel risque epidemie) : ventes 7 derniers jours > moitie des ventes du mois
    $v7 = $pdo->prepare("
        SELECT COALESCE(SUM(v.quantite),0)
        FROM ventes v JOIN structures st ON v.structure_id = st.id
        WHERE v.medicament_id = ? AND st.region = ?
          AND v.date_vente >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $v7->execute([$medicament_id, $region]);
    $ventes7j = (int)$v7->fetchColumn();
    $picAnormal = $ventes30j > 0 && $ventes7j > $ventes30j * 0.5;

    // Pharmacies de la region en difficulte (stock sous seuil)
    $diff = $pdo->prepare("
        SELECT COUNT(*) FROM stocks s
        JOIN medicaments m ON s.medicament_id = m.id
        JOIN structures st ON s.structure_id = st.id
        WHERE st.region = ? AND st.type='pharmacie' AND s.quantite < m.seuil_alerte
    ");
    $diff->execute([$region]);
    $nbPharmaciesDiff = (int)$diff->fetchColumn();

    $necessaire = $picAnormal || $nbPharmaciesDiff >= 2;

    return [
        'ventes_30j'        => $ventes30j,
        'ventes_7j'         => $ventes7j,
        'moyenne_region'    => round($moyenneParRegion),
        'demande_haute'     => $demandeHaute,
        'pic_anormal'       => $picAnormal,
        'pharmacies_diff'   => $nbPharmaciesDiff,
        'necessaire'        => $necessaire,
        // Conclusion : l'Etat peut-il obliger un reapprovisionnement ?
        'obligation_possible' => $demandeHaute && $necessaire,
    ];
}
