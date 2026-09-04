<?php
// ============================================================
//  FICHE DE STRUCTURE
//  Chaque PRA / pharmacie complete lui-meme les informations
//  de sa structure : region, zone, telephone, email, adresse.
//  Ces donnees alimentent les boutons de contact, les statistiques
//  par region et la detection des desequilibres ville / rural.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$sid = structureId();
if (!$sid) {
    echo json_encode(['success'=>false,'message'=>"Aucune structure n'est associée à votre compte."]);
    exit;
}

$region    = trim($_POST['region']    ?? '');
$zone      = trim($_POST['zone']      ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email     = trim($_POST['email']     ?? '');
$adresse   = trim($_POST['adresse']   ?? '');

// ---------- Validations ----------
if ($region === '' || $region === 'A definir') {
    echo json_encode(['success'=>false,'message'=>"La région est obligatoire."]); exit;
}
if (!in_array($zone, ['ville','village','rural'], true)) {
    echo json_encode(['success'=>false,'message'=>"La zone doit être : ville, village ou rural."]); exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>"L'adresse email n'est pas valide."]); exit;
}
// Le telephone doit contenir au moins 7 chiffres s'il est renseigne
if ($telephone !== '' && strlen(preg_replace('/[^0-9]/', '', $telephone)) < 7) {
    echo json_encode(['success'=>false,'message'=>"Le numéro de téléphone semble incomplet."]); exit;
}

try {
    $pdo->prepare("UPDATE structures
                   SET region=?, zone=?, telephone=?, email=?, adresse=?
                   WHERE id=?")
        ->execute([$region, $zone, $telephone, $email, $adresse, $sid]);

    echo json_encode(['success'=>true, 'message'=>"Fiche de structure enregistrée."]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
