<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerRole('etat');
header('Content-Type: application/json');

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$id || !in_array($action, ['approuver','rejeter'])) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit;
}
$statut = $action === 'approuver' ? 'validee' : 'rejetee';
try {
    $pdo->prepare("UPDATE reequilibrages SET statut=?, valide_par=?, traite_at=NOW() WHERE id=?")
        ->execute([$statut, idUtilisateur(), $id]);
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
