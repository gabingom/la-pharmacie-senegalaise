<?php
// ============================================================
//  POINTS DE LA CARTE — renvoie les structures geolocalisees
//  Le contenu depend du role :
//   - Etat       : tous les PRA + toutes les pharmacies
//   - PRA        : lui-meme + ses pharmacies (juridiction) + autres PRA
//   - Pharmacie  : elle-meme + son PRA + autres PRA + autres pharmacies
//  Categories renvoyees (pour la couleur du marqueur) :
//   moi, mon_pra, ma_pharmacie, autre_pra, autre_pharmacie
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$role = roleUtilisateur();
$sid  = structureId();

// Recupere toutes les structures geolocalisees
$rows = $pdo->query("
    SELECT id, nom, type, region, telephone, zone, pra_parent, latitude, longitude
    FROM structures
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND statut='active'
")->fetchAll();

// Determiner le pra_parent de la structure courante (si pharmacie)
$monPraParent = null;
if ($role === 'pharmacie' && $sid) {
    foreach ($rows as $r) { if ($r['id'] == $sid) { $monPraParent = $r['pra_parent']; break; } }
}

$points = [];
foreach ($rows as $r) {
    $cat = null;

    if ($r['id'] == $sid) {
        $cat = 'moi';
    }
    elseif ($role === 'etat') {
        // L'Etat voit tout, distingue juste PRA et pharmacies
        $cat = ($r['type'] === 'pra') ? 'autre_pra' : (($r['type']==='pharmacie') ? 'autre_pharmacie' : null);
    }
    elseif ($role === 'pra') {
        if ($r['type'] === 'pharmacie' && $r['pra_parent'] == $sid) {
            $cat = 'ma_pharmacie';          // pharmacie sous ma juridiction
        } elseif ($r['type'] === 'pharmacie') {
            $cat = 'autre_pharmacie';       // pharmacie d'un autre PRA
        } elseif ($r['type'] === 'pra') {
            $cat = 'autre_pra';
        }
    }
    elseif ($role === 'pharmacie') {
        if ($r['type'] === 'pra' && $r['id'] == $monPraParent) {
            $cat = 'mon_pra';               // mon PRA de rattachement
        } elseif ($r['type'] === 'pra') {
            $cat = 'autre_pra';
        } elseif ($r['type'] === 'pharmacie') {
            $cat = 'autre_pharmacie';
        }
    }

    if ($cat === null) continue; // on n'affiche pas les fournisseurs etc.

    $points[] = [
        'id'        => (int)$r['id'],
        'nom'       => $r['nom'],
        'type'      => $r['type'],
        'region'    => $r['region'],
        'zone'      => $r['zone'],
        'telephone' => $r['telephone'] ?: 'Non communiqué',
        'lat'       => (float)$r['latitude'],
        'lng'       => (float)$r['longitude'],
        'categorie' => $cat,
    ];
}

echo json_encode(['points' => $points]);
