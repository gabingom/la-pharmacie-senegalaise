<?php
// ============================================================
//  Fonctions utilitaires partagees par les dashboards
// ============================================================

// Lire un parametre depuis la table parametres
function param($pdo, $cle, $defaut = null) {
    $st = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = ?");
    $st->execute([$cle]);
    $v = $st->fetchColumn();
    return $v !== false ? $v : $defaut;
}

// Calcul du pourcentage de stock par rapport au seuil
function pctStock($quantite, $seuil) {
    if ($seuil <= 0) return 100;
    return round($quantite / $seuil * 100);
}

// Classe CSS selon le pourcentage
function classeNiveau($pct) {
    if ($pct < 20) return 'pf-b';
    if ($pct < 50) return 'pf-w';
    return 'pf-ok';
}
function pillNiveau($pct) {
    if ($pct < 20) return ['p-bad','Critique'];
    if ($pct < 100) return ['p-warn','Alerte'];
    return ['p-ok','OK'];
}

// Formater un nombre
function nb($n) { return number_format($n, 0, ',', ' '); }

// Boutons de contact (appeler / email) en un clic
// $tel et $email peuvent etre vides : le bouton correspondant n'apparait pas.
function boutonsContact($tel, $email) {
    $html = '<div class="contact-wrap">';
    if (!empty($tel)) {
        $telClean = preg_replace('/[^0-9+]/', '', $tel);
        $html .= '<a class="btn-call" href="tel:'.htmlspecialchars($telClean).'" title="Appeler '.htmlspecialchars($tel).'"><i class="ti ti-phone"></i>Appeler</a>';
    } else {
        $html .= '<span class="btn-off" title="Numero non communique"><i class="ti ti-phone-off"></i>Appeler</span>';
    }
    if (!empty($email)) {
        $html .= '<a class="btn-mail" href="mailto:'.htmlspecialchars($email).'" title="Envoyer un email a '.htmlspecialchars($email).'"><i class="ti ti-mail"></i>Email</a>';
    } else {
        $html .= '<span class="btn-off" title="Adresse non communiquee"><i class="ti ti-mail-off"></i>Email</span>';
    }
    $html .= '</div>';
    return $html;
}

// Date du jour en francais, pour l'entete des tableaux de bord
function strftime_fr() {
    $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois  = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    return $jours[(int)date('w')] . ' ' . (int)date('j') . ' ' . $mois[(int)date('n')] . ' ' . date('Y');
}
