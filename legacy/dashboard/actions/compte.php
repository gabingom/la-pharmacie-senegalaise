<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerRole('etat');
header('Content-Type: application/json');

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['suspendre','reactiver','supprimer'])) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit;
}

// Securite : un administrateur Etat ne peut pas se supprimer/suspendre lui-meme
if ($id == idUtilisateur()) {
    echo json_encode(['success'=>false,'message'=>'Vous ne pouvez pas modifier votre propre compte.']); exit;
}

try {
    // Recuperer l'utilisateur cible
    $u = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=?");
    $u->execute([$id]);
    $user = $u->fetch();
    if (!$user) { echo json_encode(['success'=>false,'message'=>'Utilisateur introuvable.']); exit; }

    // Securite : empecher de toucher a un autre compte Etat
    if ($user['role'] === 'etat') {
        echo json_encode(['success'=>false,'message'=>'Les comptes État ne peuvent pas être modifiés ici.']); exit;
    }

    if ($action === 'suspendre') {
        // Verifier qu'un avertissement de suspension existe et que le delai est ecoule
        $av = $pdo->prepare("SELECT * FROM avertissements WHERE utilisateur_id=? AND type='suspension' AND annule=0 ORDER BY created_at DESC LIMIT 1");
        $av->execute([$id]);
        $avert = $av->fetch();
        if (!$avert) {
            echo json_encode(['success'=>false,'message'=>'Aucun avertissement de suspension n\'a été émis. Vous devez d\'abord envoyer un avertissement avec motif.']); exit;
        }
        if (strtotime($avert['applicable_le']) > time()) {
            $j = ceil((strtotime($avert['applicable_le']) - time())/86400);
            echo json_encode(['success'=>false,'message'=>'Le délai n\'est pas écoulé. La suspension sera possible dans '.$j.' jour(s) (le '.date('d/m/Y', strtotime($avert['applicable_le'])).').']); exit;
        }
        $pdo->prepare("UPDATE utilisateurs SET statut='suspendu' WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true,'nouveau_statut'=>'suspendu']);
    }
    elseif ($action === 'reactiver') {
        // La reactivation annule aussi les avertissements en cours
        $pdo->prepare("UPDATE utilisateurs SET statut='actif' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE avertissements SET annule=1 WHERE utilisateur_id=? AND annule=0")->execute([$id]);
        echo json_encode(['success'=>true,'nouveau_statut'=>'actif']);
    }
    elseif ($action === 'supprimer') {
        // Verifier qu'un avertissement de suppression existe et que le delai est ecoule
        $av = $pdo->prepare("SELECT * FROM avertissements WHERE utilisateur_id=? AND type='suppression' AND annule=0 ORDER BY created_at DESC LIMIT 1");
        $av->execute([$id]);
        $avert = $av->fetch();
        if (!$avert) {
            echo json_encode(['success'=>false,'message'=>'Aucun avertissement de suppression n\'a été émis. Vous devez d\'abord envoyer un avertissement avec motif.']); exit;
        }
        if (strtotime($avert['applicable_le']) > time()) {
            $j = ceil((strtotime($avert['applicable_le']) - time())/86400);
            echo json_encode(['success'=>false,'message'=>'Le délai n\'est pas écoulé. La suppression sera possible dans '.$j.' jour(s) (le '.date('d/m/Y', strtotime($avert['applicable_le'])).').']); exit;
        }

        // Verifier si l'utilisateur a des commandes liees
        $check = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE demandeur_id=?");
        $check->execute([$id]);
        $nbCommandes = $check->fetchColumn();

        if ($nbCommandes > 0) {
            $pdo->prepare("UPDATE utilisateurs SET statut='suspendu' WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true,'nouveau_statut'=>'suspendu',
                'message'=>'Cet utilisateur a un historique de commandes. Il a été suspendu (et non supprimé) pour préserver les données.']);
        } else {
            $pdo->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true,'supprime'=>true]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
