<?php
// ============================================================
//  Traitement de la connexion
//  Verifie l'utilisateur dans la BDD et redirige selon le role
// ============================================================
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Methode non autorisee.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$pwd   = $_POST['password'] ?? '';

if ($email === '' || $pwd === '') {
    echo json_encode(['success' => false, 'message' => 'Veuillez renseigner votre identifiant et votre mot de passe.']);
    exit;
}

// Recherche de l'utilisateur (actif uniquement)
$stmt = $pdo->prepare("
    SELECT u.*, s.nom AS structure_nom, s.region
    FROM utilisateurs u
    LEFT JOIN structures s ON u.structure_id = s.id
    WHERE u.email = ? AND u.statut = 'actif'
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pwd, $user['mot_de_passe'])) {
    echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.']);
    exit;
}

// Mise a jour de la derniere connexion
$pdo->prepare("UPDATE utilisateurs SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

// Creation de la session
$_SESSION['user_id']       = $user['id'];
$_SESSION['user_nom']      = $user['prenom'] . ' ' . $user['nom'];
$_SESSION['user_role']     = $user['role'];
$_SESSION['user_email']    = $user['email'];
$_SESSION['structure_id']  = $user['structure_id'];
$_SESSION['structure_nom'] = $user['structure_nom'];

// Si l'utilisateur doit changer son mot de passe (1ere connexion / mot de passe temporaire)
if (!empty($user['doit_changer_mdp'])) {
    $_SESSION['doit_changer_mdp'] = true;
    echo json_encode(['success' => true, 'redirect' => 'changer_mdp.php', 'role' => $user['role']]);
    exit;
}

// Redirection selon le role (detecte automatiquement depuis la BDD)
$pages = [
    'etat'        => 'dashboard/etat.php',
    'pra'         => 'dashboard/pra.php',
    'pharmacie'   => 'dashboard/pharmacie.php',
    'fournisseur' => 'dashboard/pharmacie.php', // pas de dashboard fournisseur dedie
];
$redirect = $pages[$user['role']] ?? 'index.php';

echo json_encode([
    'success'  => true,
    'redirect' => $redirect,
    'role'     => $user['role'],
    'nom'      => $_SESSION['user_nom']
]);
