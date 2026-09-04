<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerConnexion();
header('Content-Type: application/json');

try {
    $params = $_POST['params'] ?? [];
    $st = $pdo->prepare("UPDATE parametres SET valeur=? WHERE cle=?");
    foreach ($params as $cle => $valeur) {
        $st->execute([$valeur, $cle]);
    }
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
