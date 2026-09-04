<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerConnexion();

$type = $_POST['type'] ?? '';

try {
    if ($type === 'subvention') {
        exigerRole('pra');
        $pdo->prepare("INSERT INTO subventions (pharmacie_id,signale_par,medicaments,montant_estime,motif) VALUES (?,?,?,?,?)")
            ->execute([
                intval($_POST['pharmacie_id'] ?? 0),
                idUtilisateur(),
                trim($_POST['medicaments'] ?? ''),
                floatval($_POST['montant'] ?? 0),
                trim($_POST['motif'] ?? '')
            ]);
    }
    elseif ($type === 'reequilibrage') {
        exigerRole('pra');
        $pdo->prepare("INSERT INTO reequilibrages (medicament_id,destination_id,quantite,origine,signale_par,priorite,justification) VALUES (?,?,?,'pra',?,?,?)")
            ->execute([
                intval($_POST['medicament_id'] ?? 0),
                structureId(),
                intval($_POST['quantite'] ?? 0),
                idUtilisateur(),
                $_POST['priorite'] ?? 'moderee',
                trim($_POST['justification'] ?? '')
            ]);
    }
    elseif ($type === 'commande') {
        exigerRole('pharmacie');
        $medId    = intval($_POST['medicament_id'] ?? 0);
        $qte      = intval($_POST['quantite'] ?? 0);
        $praCible = intval($_POST['pra_cible_id'] ?? 0);   // 0 = PRA de rattachement
        $sid      = structureId();

        if ($medId <= 0 || $qte <= 0) {
            throw new Exception("Veuillez choisir un médicament et une quantité valide.");
        }

        // Retrouver le PRA parent de la pharmacie
        $pr = $pdo->prepare("SELECT pra_parent FROM structures WHERE id=?");
        $pr->execute([$sid]);
        $praParent = (int)$pr->fetchColumn();
        if (!$praParent) {
            throw new Exception("Votre pharmacie n'est rattachée à aucun PRA.");
        }

        // PRA destinataire de la commande
        $praId = $praCible ?: $praParent;

        // Si commande hors du PRA de rattachement : une autorisation active est exigee
        if ($praId !== $praParent) {
            $au = $pdo->prepare("SELECT COUNT(*) FROM autorisations_pra
                                 WHERE pharmacie_id=? AND medicament_id=? AND pra_cible_id=?
                                   AND statut='accordee'");
            $au->execute([$sid, $medId, $praId]);
            if (!$au->fetchColumn()) {
                throw new Exception("Vous n'avez pas d'autorisation pour commander ce médicament auprès de ce PRA. Sollicitez d'abord votre PRA de rattachement.");
            }
        }

        // Verifier que le PRA destinataire a bien ce medicament en stock
        $st = $pdo->prepare("SELECT COALESCE(SUM(quantite),0) FROM stocks WHERE structure_id=? AND medicament_id=?");
        $st->execute([$praId, $medId]);
        $dispo = (int)$st->fetchColumn();
        if ($dispo <= 0) {
            throw new Exception("Ce médicament n'est plus disponible chez ce PRA.");
        }

        // Creer la commande
        $ref = 'CMD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
        $pdo->prepare("INSERT INTO commandes (reference,demandeur_id,pra_cible_id,urgence,notes) VALUES (?,?,?,?,?)")
            ->execute([$ref, idUtilisateur(), $praId, $_POST['urgence'] ?? 'normale', trim($_POST['notes'] ?? '')]);
        $cid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO lignes_commande (commande_id,medicament_id,quantite_demandee) VALUES (?,?,?)")
            ->execute([$cid, $medId, $qte]);
    }
    else {
        throw new Exception('Type inconnu');
    }
    // Redirection retour
    header('Location: ' . ($_POST['retour'] ?? '../index.php') . '?ok=1');
    exit;
} catch (Exception $e) {
    header('Location: ' . ($_POST['retour'] ?? '../index.php') . '?err=' . urlencode($e->getMessage()));
    exit;
}
