<?php
// ============================================================
//  Gestion des sessions et des roles
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estConnecte() {
    return !empty($_SESSION['user_id']);
}

function exigerConnexion() {
    if (!estConnecte()) {
        header('Location: ../index.php');
        exit;
    }
    // Si l'utilisateur doit changer son mot de passe, le forcer a le faire
    if (!empty($_SESSION['doit_changer_mdp'])) {
        header('Location: ../changer_mdp.php');
        exit;
    }
}

// Comme exigerConnexion mais sans rediriger vers changer_mdp
// (utilisee par la page changer_mdp.php elle-meme pour eviter une boucle)
function exigerConnexionSimple() {
    if (!estConnecte()) {
        header('Location: index.php');
        exit;
    }
}

function exigerRole($role) {
    exigerConnexion();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: ../index.php');
        exit;
    }
}

function roleUtilisateur()    { return $_SESSION['user_role'] ?? null; }
function nomUtilisateur()     { return $_SESSION['user_nom'] ?? 'Utilisateur'; }
function idUtilisateur()      { return $_SESSION['user_id'] ?? null; }
function structureId()        { return $_SESSION['structure_id'] ?? null; }
function structureNom()       { return $_SESSION['structure_nom'] ?? ''; }

// Initiales pour l'avatar
function initiales() {
    $nom = nomUtilisateur();
    $parts = explode(' ', $nom);
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0],0,1) . substr($parts[1],0,1));
    }
    return strtoupper(substr($nom,0,2));
}
