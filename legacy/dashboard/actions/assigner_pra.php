<?php
// ============================================================
//  ASSIGNATION DU PRA DE RATTACHEMENT
//  Reservee a l'Etat : associe une pharmacie a son PRA regional.
//  Sans ce rattachement, une pharmacie ne peut adresser aucune
//  commande (son PRA de destination par defaut est introuvable).
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerRole('etat');
header('Content-Type: application/json; charset=utf-8');

$structureId = intval($_POST['structure_id'] ?? 0);
$praId       = intval($_POST['pra_id'] ?? 0);

if (!$structureId || !$praId) {
    echo json_encode(['success'=>false,'message'=>"Paramètres manquants."]); exit;
}

try {
    // La structure ciblee doit bien etre une pharmacie
    $s = $pdo->prepare("SELECT id, nom, type FROM structures WHERE id=?");
    $s->execute([$structureId]);
    $structure = $s->fetch();
    if (!$structure) {
        echo json_encode(['success'=>false,'message'=>"Structure introuvable."]); exit;
    }
    if ($structure['type'] !== 'pharmacie') {
        echo json_encode(['success'=>false,'message'=>"Seule une pharmacie peut recevoir un PRA de rattachement."]); exit;
    }

    // Le PRA cible doit exister, etre bien un PRA, et etre actif
    $p = $pdo->prepare("SELECT id, nom FROM structures WHERE id=? AND type='pra' AND statut='active'");
    $p->execute([$praId]);
    $pra = $p->fetch();
    if (!$pra) {
        echo json_encode(['success'=>false,'message'=>"PRA introuvable ou inactif."]); exit;
    }

    $pdo->prepare("UPDATE structures SET pra_parent=? WHERE id=?")
        ->execute([$praId, $structureId]);

    echo json_encode([
        'success'  => true,
        'pra_nom'  => $pra['nom'],
        'message'  => $structure['nom'] . " est désormais rattachée à " . $pra['nom'] . "."
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
