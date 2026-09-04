<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerRole('pharmacie');
header('Content-Type: application/json');

$medId = intval($_POST['medicament_id'] ?? 0);
$qte   = intval($_POST['quantite'] ?? 0);
$sid   = structureId();

if (!$medId || $qte <= 0) {
    echo json_encode(['success'=>false,'message'=>'Médicament et quantité requis.']); exit;
}

try {
    $pdo->beginTransaction();

    // Verifier le stock disponible
    $st = $pdo->prepare("SELECT id, quantite FROM stocks WHERE structure_id=? AND medicament_id=? LIMIT 1");
    $st->execute([$sid, $medId]);
    $stock = $st->fetch();

    if (!$stock) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Ce médicament n\'est pas dans votre stock.']); exit;
    }
    if ($stock['quantite'] < $qte) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Stock insuffisant : il reste '.$stock['quantite'].' unité(s).']); exit;
    }

    // Decrementer le stock
    $pdo->prepare("UPDATE stocks SET quantite = quantite - ? WHERE id=?")
        ->execute([$qte, $stock['id']]);

    // Enregistrer la vente
    $pdo->prepare("INSERT INTO ventes (structure_id, medicament_id, quantite) VALUES (?,?,?)")
        ->execute([$sid, $medId, $qte]);

    $nouveauStock = $stock['quantite'] - $qte;
    $pdo->commit();

    echo json_encode(['success'=>true, 'nouveau_stock'=>$nouveauStock]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
