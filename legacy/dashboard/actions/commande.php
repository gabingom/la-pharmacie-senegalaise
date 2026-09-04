<?php
// ============================================================
//  VALIDATION D'UNE COMMANDE PAR LE PRA
//  Logique : si le PRA a le stock -> il livre (decremente + email)
//            sinon -> commande validee mais en attente de
//            reapprovisionnement (email a la pharmacie)
//  Rejet -> email a la pharmacie
//  L'Etat ne valide plus : suivi seul.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/mail.php';
require_once '../../config/mailer.php';
exigerConnexion();
header('Content-Type: application/json');

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$id || !in_array($action, ['valider','rejeter'])) {
    echo json_encode(['success'=>false,'message'=>'Parametres invalides.']); exit;
}

try {
    $c = $pdo->prepare("
        SELECT c.*, u.email AS email_phcie, u.prenom, u.nom,
               s.id AS pharma_id, s.nom AS pharma_nom, s.pra_parent
        FROM commandes c
        JOIN utilisateurs u ON c.demandeur_id = u.id
        JOIN structures s   ON u.structure_id = s.id
        WHERE c.id = ?
    ");
    $c->execute([$id]);
    $cmd = $c->fetch();
    if (!$cmd) { echo json_encode(['success'=>false,'message'=>'Commande introuvable.']); exit; }

    $praId = structureId();

    // Securite : seul le PRA destinataire peut traiter la commande
    $destinataire = $cmd['pra_cible_id'] ?: $cmd['pra_parent'];
    if ((int)$destinataire !== (int)$praId) {
        echo json_encode(['success'=>false,'message'=>"Cette commande ne vous est pas adressée."]); exit;
    }

    // ---------- REJET ----------
    if ($action === 'rejeter') {
        $pdo->prepare("UPDATE commandes SET statut='rejetee', validateur_id=?, date_validation=NOW() WHERE id=?")
            ->execute([idUtilisateur(), $id]);
        $corps = emailCommande($cmd['prenom'], $cmd['pharma_nom'], $cmd['reference'], 'rejetee', '');
        envoyerEmail($cmd['email_phcie'], 'Votre commande '.$cmd['reference'].' - La Pharmacie Senegalaise', $corps);
        echo json_encode(['success'=>true]);
        exit;
    }

    // ---------- VALIDATION ----------
    $lignes = $pdo->prepare("SELECT l.*, m.nom, m.dosage FROM lignes_commande l JOIN medicaments m ON l.medicament_id=m.id WHERE l.commande_id=?");
    $lignes->execute([$id]);
    $lignes = $lignes->fetchAll();

    $stockSuffisant = true;
    foreach ($lignes as $lg) {
        $s = $pdo->prepare("SELECT COALESCE(SUM(quantite),0) FROM stocks WHERE structure_id=? AND medicament_id=?");
        $s->execute([$praId, $lg['medicament_id']]);
        if ($s->fetchColumn() < $lg['quantite_demandee']) { $stockSuffisant = false; break; }
    }

    if ($stockSuffisant) {
        $pdo->beginTransaction();
        foreach ($lignes as $lg) {
            $reste = $lg['quantite_demandee'];
            $lots = $pdo->prepare("SELECT id, quantite FROM stocks WHERE structure_id=? AND medicament_id=? AND quantite>0 ORDER BY date_peremption ASC");
            $lots->execute([$praId, $lg['medicament_id']]);
            foreach ($lots->fetchAll() as $lot) {
                if ($reste <= 0) break;
                $pris = min($reste, $lot['quantite']);
                $pdo->prepare("UPDATE stocks SET quantite=quantite-? WHERE id=?")->execute([$pris, $lot['id']]);
                $reste -= $pris;
            }
            $ex = $pdo->prepare("SELECT id FROM stocks WHERE structure_id=? AND medicament_id=? LIMIT 1");
            $ex->execute([$cmd['pharma_id'], $lg['medicament_id']]);
            $exId = $ex->fetchColumn();
            if ($exId) {
                $pdo->prepare("UPDATE stocks SET quantite=quantite+? WHERE id=?")->execute([$lg['quantite_demandee'], $exId]);
            } else {
                $pdo->prepare("INSERT INTO stocks (medicament_id,structure_id,quantite) VALUES (?,?,?)")
                    ->execute([$lg['medicament_id'], $cmd['pharma_id'], $lg['quantite_demandee']]);
            }
            $pdo->prepare("UPDATE lignes_commande SET quantite_livree=? WHERE id=?")->execute([$lg['quantite_demandee'], $lg['id']]);
        }
        $pdo->prepare("UPDATE commandes SET statut='livree', validateur_id=?, date_validation=NOW() WHERE id=?")
            ->execute([idUtilisateur(), $id]);
        $pdo->commit();

        $corps = emailCommande($cmd['prenom'], $cmd['pharma_nom'], $cmd['reference'], 'livree', '');
        envoyerEmail($cmd['email_phcie'], 'Votre commande '.$cmd['reference'].' a ete livree', $corps);
        echo json_encode(['success'=>true, 'livree'=>true]);
    } else {
        $pdo->prepare("UPDATE commandes SET statut='validee', validateur_id=?, date_validation=NOW() WHERE id=?")
            ->execute([idUtilisateur(), $id]);
        $corps = emailCommande($cmd['prenom'], $cmd['pharma_nom'], $cmd['reference'], 'validee_attente', '');
        envoyerEmail($cmd['email_phcie'], 'Votre commande '.$cmd['reference'].' est en cours de traitement', $corps);
        echo json_encode(['success'=>true, 'reappro'=>true,
            'message'=>'Commande validee. Votre stock est insuffisant : reapprovisionnez-vous (reequilibrage ou fournisseur) puis livrez. La pharmacie a ete informee.']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
