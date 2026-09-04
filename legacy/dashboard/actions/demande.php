<?php
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/mailer.php';
exigerRole('etat');
header('Content-Type: application/json');

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$id || !in_array($action, ['approuver','rejeter'])) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit;
}
$statut = $action === 'approuver' ? 'approuvee' : 'rejetee';

try {
    // Recuperer la demande
    $d = $pdo->prepare("SELECT * FROM demandes_acces WHERE id=?");
    $d->execute([$id]);
    $dem = $d->fetch();
    if (!$dem) { echo json_encode(['success'=>false,'message'=>'Demande introuvable.']); exit; }

    $pdo->prepare("UPDATE demandes_acces SET statut=?, traite_par=?, traite_at=NOW() WHERE id=?")
        ->execute([$statut, idUtilisateur(), $id]);

    $emailEnvoye = false;

    if ($action === 'approuver') {
        // Verifier si l'email existe deja
        $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email=?");
        $check->execute([$dem['email']]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success'=>false,'message'=>'Un compte existe déjà avec l\'email '.$dem['email'].'. La demande ne peut pas être approuvée.']);
            exit;
        }

        // Creer la structure + le compte utilisateur
        $pdo->prepare("INSERT INTO structures (nom,type,region) VALUES (?,?,'A definir')")
            ->execute([$dem['structure_nom'], $dem['role_demande']]);
        $sid = $pdo->lastInsertId();

        // Mot de passe temporaire aleatoire
        $motDePasse = 'LPS' . random_int(1000, 9999) . '!';
        $hash = password_hash($motDePasse, PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,doit_changer_mdp,role,structure_id,statut) VALUES (?,?,?,?,1,?,?,'actif')")
            ->execute([$dem['nom'],$dem['prenom'],$dem['email'],$hash,$dem['role_demande'],$sid]);

        // Envoyer l'email d'autorisation avec les identifiants
        $corps = emailAutorisation($dem['prenom'], $dem['nom'], $dem['email'], $motDePasse, $dem['role_demande']);
        $emailEnvoye = envoyerEmail($dem['email'], 'Votre acces a La Pharmacie Senegalaise a ete approuve', $corps);
    } else {
        // Email de rejet
        $corps = emailRejet($dem['prenom'], $dem['nom']);
        $emailEnvoye = envoyerEmail($dem['email'], 'Concernant votre demande d\'acces', $corps);
    }

    echo json_encode([
        'success'      => true,
        'email_envoye' => $emailEnvoye,
        'message'      => $emailEnvoye ? 'Email envoyé à ' . $dem['email'] : 'Traité (email non envoyé, vérifiez la config)'
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
