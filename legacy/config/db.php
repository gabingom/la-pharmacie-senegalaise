<?php
// ============================================================
//  Connexion a la base de donnees MySQL (WAMP)
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', '');          // WAMP : mot de passe root vide par defaut
define('DB_NAME', 'lps');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion a la base de donnees : " . $e->getMessage());
}
