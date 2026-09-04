<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/mail.php';
require_once '../../config/mailer.php';
exigerRole('etat');
header('Content-Type: application/json');

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$id || !in_array($action, ['autoriser','refuser'])) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit;
}

try {
    // Recuperer la demande
    $d = $pdo->prepare("SELECT * FROM demandes_reset WHERE id=?");
    $d->execute([$id]);
    $dem = $d->fetch();
    if (!$dem) { echo json_encode(['success'=>false,'message'=>'Demande introuvable.']); exit; }

    if ($action === 'autoriser') {
        // Marquer la demande comme autorisee
        $pdo->prepare("UPDATE demandes_reset SET statut='autorisee', traite_par=?, traite_at=NOW() WHERE id=?")
            ->execute([idUtilisateur(), $id]);
        // Autoriser l'utilisateur a refaire une reinitialisation
        $pdo->prepare("UPDATE utilisateurs SET reset_autorise=1 WHERE id=?")->execute([$dem['utilisateur_id']]);

        // Prevenir l'utilisateur par email qu'il peut reinitialiser
        $u = $pdo->prepare("SELECT prenom FROM utilisateurs WHERE id=?");
        $u->execute([$dem['utilisateur_id']]);
        $prenom = $u->fetchColumn();
        $corps = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
          <div style="background:#1faa4e;padding:24px;text-align:center;"><div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div></div>
          <div style="padding:28px 32px;color:#1a3a25;">
            <h2 style="color:#0a3d20;font-size:18px;">Reinitialisation autorisee</h2>
            <p style="font-size:14px;line-height:1.6;">Bonjour <strong>'.htmlspecialchars($prenom).'</strong>,</p>
            <p style="font-size:14px;line-height:1.6;">Le Ministere de la Sante a autorise votre demande de reinitialisation exceptionnelle. Vous pouvez maintenant reinitialiser votre mot de passe depuis la page de connexion.</p>
            <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
          </div></div>';
        envoyerEmail($dem['email'], 'Reinitialisation autorisee - La Pharmacie Senegalaise', $corps);

        echo json_encode(['success'=>true]);
    } else {
        $pdo->prepare("UPDATE demandes_reset SET statut='refusee', traite_par=?, traite_at=NOW() WHERE id=?")
            ->execute([idUtilisateur(), $id]);
        echo json_encode(['success'=>true]);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
