<?php
// ============================================================
//  CONFIGURATION EMAIL (Gmail SMTP)
//  >>> REMPLIR AVEC VOS PROPRES IDENTIFIANTS GMAIL <<<
// ============================================================
//
//  IMPORTANT : Gmail exige un "mot de passe d'application"
//  (PAS votre mot de passe Gmail habituel).
//
//  Comment l'obtenir :
//    1. Activez la validation en 2 etapes sur votre compte Google
//    2. Allez sur : https://myaccount.google.com/apppasswords
//    3. Generez un mot de passe d'application (16 caracteres)
//    4. Collez-le ci-dessous dans MAIL_PASSWORD (sans espaces)
//
// ============================================================

// Activer ou desactiver l'envoi reel d'emails
// true  = envoie de vrais emails via Gmail
// false = ecrit l'email dans un fichier (lib/emails_envoyes.txt) pour tester sans Gmail
define('MAIL_ACTIF', filter_var(getenv('MAIL_ACTIF') ?: 'false', FILTER_VALIDATE_BOOLEAN));

// Vos identifiants Gmail
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: '');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');

// Expediteur affiche
define('MAIL_FROM_NAME', 'La Pharmacie Senegalaise');
define('MAIL_FROM_ADDR', getenv('MAIL_FROM_ADDRESS') ?: MAIL_USERNAME);
define('MAIL_SSL_NO_VERIFY', filter_var(getenv('MAIL_SSL_NO_VERIFY') ?: 'false', FILTER_VALIDATE_BOOLEAN));