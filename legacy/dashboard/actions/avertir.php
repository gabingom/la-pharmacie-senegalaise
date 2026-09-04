<?php
// ============================================================
//  EMETTRE UN AVERTISSEMENT (avant suspension ou suppression)
//  L'Etat ecrit un motif, un email est envoye, un delai demarre.
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/mail.php';
require_once '../../config/mailer.php';
exigerRole('etat');
header('Content-Type: application/json; charset=utf-8');

$id    = intval($_POST['id'] ?? 0);
$type  = $_POST['type'] ?? '';        // 'suspension' ou 'suppression'
$motif = trim($_POST['motif'] ?? '');

if (!$id || !in_array($type, ['suspension','suppression'])) {
    echo json_encode(['success'=>false,'message'=>'Paramètres invalides.']); exit;
}
if ($motif === '') {
    echo json_encode(['success'=>false,'message'=>'Le motif de l\'avertissement est obligatoire.']); exit;
}

try {
    // Recuperer l'utilisateur cible + sa structure
    $u = $pdo->prepare("SELECT u.*, s.nom AS struct_nom FROM utilisateurs u LEFT JOIN structures s ON u.structure_id=s.id WHERE u.id=?");
    $u->execute([$id]);
    $user = $u->fetch();
    if (!$user) { echo json_encode(['success'=>false,'message'=>'Utilisateur introuvable.']); exit; }
    if ($user['role'] === 'etat') {
        echo json_encode(['success'=>false,'message'=>'Les comptes État ne peuvent pas faire l\'objet d\'un avertissement.']); exit;
    }

    // Delai applicable selon le type
    $cle = $type === 'suspension' ? 'delai_suspension' : 'delai_suppression';
    $d = $pdo->prepare("SELECT valeur FROM parametres WHERE cle=?");
    $d->execute([$cle]);
    $delai = (int)($d->fetchColumn() ?: ($type==='suspension'?10:15));

    $applicable = date('Y-m-d H:i:s', time() + $delai*86400);

    // Annuler les avertissements precedents du meme type encore actifs
    $pdo->prepare("UPDATE avertissements SET annule=1 WHERE utilisateur_id=? AND type=? AND annule=0")
        ->execute([$id, $type]);

    // Enregistrer le nouvel avertissement
    $pdo->prepare("INSERT INTO avertissements (utilisateur_id, type, motif, emis_par, applicable_le) VALUES (?,?,?,?,?)")
        ->execute([$id, $type, $motif, idUtilisateur(), $applicable]);

    // Envoyer l'email d'avertissement
    $corps = emailAvertissement($user['prenom'], $user['nom'], $user['struct_nom'], $type, $motif, $delai, $applicable);
    $sujet = $type === 'suspension'
        ? 'Avertissement avant suspension - La Pharmacie Senegalaise'
        : 'Avertissement avant suppression - La Pharmacie Senegalaise';
    $envoye = envoyerEmail($user['email'], $sujet, $corps);

    echo json_encode([
        'success'=>true,
        'applicable_le'=>date('d/m/Y', strtotime($applicable)),
        'delai'=>$delai,
        'email_envoye'=>$envoye
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
