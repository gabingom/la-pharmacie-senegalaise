<?php
// ============================================================
//  ENREGISTRER LA LOCALISATION DE SA PROPRE STRUCTURE
//  Chaque structure (PRA, pharmacie) positionne son point.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$sid = structureId();
if (!$sid) {
    echo json_encode(['success'=>false,'message'=>"Aucune structure associée à votre compte."]); exit;
}

$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;

// Validation basique des coordonnees
if (!is_numeric($lat) || !is_numeric($lng) ||
    $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['success'=>false,'message'=>"Coordonnées invalides."]); exit;
}

try {
    $pdo->prepare("UPDATE structures SET latitude=?, longitude=? WHERE id=?")
        ->execute([$lat, $lng, $sid]);
    echo json_encode(['success'=>true, 'message'=>"Localisation enregistrée avec succès."]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
