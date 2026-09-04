<?php
// ============================================================
//  Fonction d'envoi d'email via PHPMailer (Gmail SMTP)
// ============================================================
require_once __DIR__ . '/mail.php';

// ------------------------------------------------------------
//  Chargement de PHPMailer
//  Certains hebergeurs n'autorisent pas la creation de sous-dossiers
//  par FTP : les fichiers peuvent donc se trouver soit dans
//  lib/PHPMailer/, soit directement dans lib/. On gere les deux cas.
// ------------------------------------------------------------
$dossiersPHPMailer = [
    __DIR__ . '/../lib/PHPMailer',   // emplacement normal
    __DIR__ . '/../lib',             // repli : fichiers a plat dans lib/
];
$basePHPMailer = null;
foreach ($dossiersPHPMailer as $d) {
    if (file_exists($d . '/PHPMailer.php')) { $basePHPMailer = $d; break; }
}
if ($basePHPMailer === null) {
    die("PHPMailer est introuvable. Verifiez que PHPMailer.php, SMTP.php et "
      . "Exception.php sont bien presents dans le dossier lib/.");
}
require_once $basePHPMailer . '/PHPMailer.php';
require_once $basePHPMailer . '/SMTP.php';
require_once $basePHPMailer . '/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Envoie un email. Retourne true si succes, false sinon.
 * Si MAIL_ACTIF est false, ecrit l'email dans un fichier au lieu de l'envoyer.
 */
function envoyerEmail($destinataire, $sujet, $corpsHtml) {
    // Mode simulation : on ecrit dans un fichier
    if (!MAIL_ACTIF) {
        $log = __DIR__ . '/../lib/emails_envoyes.txt';
        $contenu = "=== EMAIL ===\n"
                 . "Date : " . date('d/m/Y H:i:s') . "\n"
                 . "A : $destinataire\n"
                 . "Sujet : $sujet\n"
                 . "Message :\n" . strip_tags($corpsHtml) . "\n\n";
        file_put_contents($log, $contenu, FILE_APPEND);
        return true;
    }

    // Envoi reel via Gmail
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // --- Certificat SSL ---
        // En local (WAMP), aucun certificat racine n'est configure :
        // on desactive la verification, uniquement dans ce cas.
        // En production, la verification reste ACTIVE (securite).
        if (defined('MAIL_SSL_NO_VERIFY') && MAIL_SSL_NO_VERIFY) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        $mail->setFrom(MAIL_FROM_ADDR, MAIL_FROM_NAME);
        $mail->addAddress($destinataire);
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corpsHtml;
        $mail->AltBody = strip_tags($corpsHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // En cas d'erreur, on logue mais on ne bloque pas l'application
        error_log("Erreur envoi email : " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Construit le corps HTML de l'email d'autorisation de compte.
 */
function emailAutorisation($prenom, $nom, $email, $motDePasse, $role) {
    $roles = ['pra'=>'PRA (Pharmacie de Repartition)','pharmacie'=>'Pharmacie agreee','fournisseur'=>'Fournisseur'];
    $roleLib = $roles[$role] ?? $role;
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:#1faa4e;padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
        <div style="color:rgba(255,255,255,0.85);font-size:13px;margin-top:4px;">Ministere de la Sante et de l\'Action Sociale</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:#0a3d20;font-size:18px;">Votre acces a ete approuve</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($prenom . ' ' . $nom) . '</strong>,</p>
        <p style="font-size:14px;line-height:1.6;">Le Ministere de la Sante a approuve votre demande d\'acces a la plateforme en tant que <strong>' . htmlspecialchars($roleLib) . '</strong>.</p>
        <p style="font-size:14px;line-height:1.6;">Voici vos identifiants de connexion :</p>
        <div style="background:#eafaf0;border-radius:10px;padding:16px 20px;margin:16px 0;font-size:14px;">
          <div style="margin-bottom:8px;"><strong>Identifiant :</strong> ' . htmlspecialchars($email) . '</div>
          <div><strong>Mot de passe temporaire :</strong> <span style="font-family:monospace;background:#fff;padding:2px 8px;border-radius:5px;border:1px solid #b8ddc4;">' . htmlspecialchars($motDePasse) . '</span></div>
        </div>
        <p style="font-size:13px;color:#5a8a6a;line-height:1.6;">Pour des raisons de securite, nous vous recommandons de changer ce mot de passe lors de votre premiere connexion.</p>
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
      <div style="background:#f4faf6;padding:14px;text-align:center;font-size:11px;color:#7a9a86;">
        La sante est un tresor qu\'il faut preserver.
      </div>
    </div>';
}

/**
 * Email de rejet de demande.
 */
function emailRejet($prenom, $nom) {
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:#1faa4e;padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:#0a3d20;font-size:18px;">Concernant votre demande d\'acces</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($prenom . ' ' . $nom) . '</strong>,</p>
        <p style="font-size:14px;line-height:1.6;">Apres examen, votre demande d\'acces a la plateforme n\'a pas pu etre approuvee pour le moment.</p>
        <p style="font-size:14px;line-height:1.6;">Pour plus d\'informations, veuillez contacter le Ministere de la Sante.</p>
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
    </div>';
}

/**
 * Email de reinitialisation de mot de passe (lien avec token).
 */
function emailReset($prenom, $lien) {
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:#1faa4e;padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:#0a3d20;font-size:18px;">Reinitialisation de votre mot de passe</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($prenom) . '</strong>,</p>
        <p style="font-size:14px;line-height:1.6;">Vous avez demande la reinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour en choisir un nouveau :</p>
        <p style="text-align:center;margin:26px 0;">
          <a href="' . htmlspecialchars($lien) . '" style="background:#1faa4e;color:#fff;text-decoration:none;padding:14px 32px;border-radius:10px;font-weight:bold;font-size:15px;display:inline-block;">Reinitialiser mon mot de passe</a>
        </p>
        <p style="font-size:13px;line-height:1.6;color:#5a8a6a;">Ce lien est valable <strong>1 heure</strong>. Si vous n\'etes pas a l\'origine de cette demande, ignorez cet email : votre mot de passe restera inchange.</p>
        <p style="font-size:12px;line-height:1.6;color:#90b8a0;margin-top:18px;word-break:break-all;">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>' . htmlspecialchars($lien) . '</p>
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
    </div>';
}

/**
 * Email d'avertissement avant suspension ou suppression.
 */
function emailAvertissement($prenom, $nom, $structure, $type, $motif, $delai, $applicable) {
    $titre = $type === 'suspension' ? 'Avertissement avant suspension' : 'Avertissement avant suppression';
    $action = $type === 'suspension' ? 'la suspension' : 'la suppression définitive';
    $dateAppl = date('d/m/Y', strtotime($applicable));
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:#c0392b;padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
        <div style="color:#fff;font-size:13px;margin-top:4px;opacity:0.9;">Ministere de la Sante</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:#a32d2d;font-size:18px;">' . $titre . '</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour <strong>' . htmlspecialchars($prenom . ' ' . $nom) . '</strong>'
        . ($structure ? ' (' . htmlspecialchars($structure) . ')' : '') . ',</p>
        <p style="font-size:14px;line-height:1.6;">Le Ministere de la Sante vous notifie un avertissement officiel pouvant conduire a ' . $action . ' de votre acces a la plateforme.</p>
        <div style="background:#fdeded;border-left:4px solid #c0392b;padding:14px 16px;margin:18px 0;border-radius:4px;">
          <div style="font-size:13px;color:#7a1f1a;font-weight:bold;margin-bottom:4px;">Motif :</div>
          <div style="font-size:14px;color:#1a3a25;line-height:1.6;">' . nl2br(htmlspecialchars($motif)) . '</div>
        </div>
        <p style="font-size:14px;line-height:1.6;">Conformement a la procedure, vous disposez d\'un delai de <strong>' . (int)$delai . ' jours</strong> pour regulariser votre situation. A defaut, ' . $action . ' pourra etre prononcee a compter du <strong>' . $dateAppl . '</strong>.</p>
        <p style="font-size:14px;line-height:1.6;">Pour toute regularisation ou information, veuillez contacter sans delai le Ministere de la Sante.</p>
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
    </div>';
}

/**
 * Email a la pharmacie sur l'evolution de sa commande.
 * $etat : 'livree', 'validee_attente', 'rejetee'
 */
function emailCommande($prenom, $pharmacie, $reference, $etat, $extra='') {
    $map = [
        'livree'          => ['Commande livree', '#1faa4e', 'Votre commande a ete validee et livree. Les produits ont ete ajoutes a votre stock.'],
        'validee_attente' => ['Commande en cours de traitement', '#d68910', 'Votre commande a ete validee par votre PRA. Les produits ne sont pas encore tous disponibles : un reapprovisionnement est en cours (reequilibrage entre regions ou commande au fournisseur). Vous serez informe de la livraison.'],
        'rejetee'         => ['Commande non retenue', '#c0392b', 'Apres examen, votre commande n\'a pas pu etre validee par votre PRA. Pour plus d\'informations, veuillez contacter votre PRA de rattachement.'],
    ];
    $info = $map[$etat] ?? ['Mise a jour de votre commande', '#1a5fa5', 'Le statut de votre commande a ete mis a jour.'];
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:'.$info[1].';padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:'.$info[1].';font-size:18px;">'.$info[0].'</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour <strong>'.htmlspecialchars($prenom).'</strong>'.($pharmacie?' ('.htmlspecialchars($pharmacie).')':'').',</p>
        <p style="font-size:14px;line-height:1.6;">Commande de reference <strong>'.htmlspecialchars($reference).'</strong> :</p>
        <p style="font-size:14px;line-height:1.6;">'.$info[2].'</p>
        '.($extra ? '<p style="font-size:14px;line-height:1.6;">'.htmlspecialchars($extra).'</p>' : '').'
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
    </div>';
}

/**
 * Email lie a l'autorisation de commander aupres d'un autre PRA.
 * $etat : 'demande' | 'accordee' | 'refusee' | 'revoquee'
 */
function emailAutorisationPra($etat, $info, $texte = '') {
    $map = [
        'demande'  => ['Demande d\'autorisation de commande externe', '#d68910',
            'La pharmacie <strong>'.htmlspecialchars($info['pharmacie']).'</strong> sollicite votre autorisation pour commander le medicament <strong>'.htmlspecialchars($info['medicament']).'</strong> aupres du <strong>'.htmlspecialchars($info['pra_cible']).'</strong>. Vous pouvez accorder ou refuser cette demande depuis votre espace, rubrique « Autorisations ».'],
        'accordee' => ['Autorisation accordee', '#1faa4e',
            'Votre PRA de rattachement (<strong>'.htmlspecialchars($info['pra_origine']).'</strong>) vous autorise a commander le medicament <strong>'.htmlspecialchars($info['medicament']).'</strong> aupres du <strong>'.htmlspecialchars($info['pra_cible']).'</strong>. Cette autorisation reste valable jusqu\'a revocation.'],
        'refusee'  => ['Autorisation refusee', '#c0392b',
            'Votre demande de commander le medicament <strong>'.htmlspecialchars($info['medicament']).'</strong> aupres du <strong>'.htmlspecialchars($info['pra_cible']).'</strong> n\'a pas ete retenue par votre PRA de rattachement.'],
        'revoquee' => ['Autorisation revoquee', '#c0392b',
            'L\'autorisation de commander le medicament <strong>'.htmlspecialchars($info['medicament']).'</strong> aupres du <strong>'.htmlspecialchars($info['pra_cible']).'</strong> a ete revoquee par votre PRA de rattachement.'],
        'info_cible' => ['Une pharmacie est autorisee a vous solliciter', '#1a5fa5',
            'Le <strong>'.htmlspecialchars($info['pra_origine']).'</strong> a autorise la pharmacie <strong>'.htmlspecialchars($info['pharmacie']).'</strong>, qui releve de sa juridiction, a vous commander le medicament <strong>'.htmlspecialchars($info['medicament']).'</strong>, ce produit n\'etant pas disponible chez lui.<br><br>Vous pourrez recevoir une commande de cette pharmacie dans votre espace, rubrique « Demandes pharmacies » (signalee par la mention « Externe »). Vous restez libre de l\'accepter ou de la refuser selon votre stock.'],
    ];
    $i = $map[$etat] ?? ['Mise a jour', '#1a5fa5', 'Le statut de votre autorisation a ete mis a jour.'];
    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #d4ebdb;border-radius:12px;overflow:hidden;">
      <div style="background:'.$i[1].';padding:24px;text-align:center;">
        <div style="color:#fff;font-size:20px;font-weight:bold;">La Pharmacie Senegalaise</div>
      </div>
      <div style="padding:28px 32px;color:#1a3a25;">
        <h2 style="color:'.$i[1].';font-size:18px;">'.$i[0].'</h2>
        <p style="font-size:14px;line-height:1.6;">Bonjour,</p>
        <p style="font-size:14px;line-height:1.6;">'.$i[2].'</p>
        '.($texte ? '<div style="background:#f7fdf9;border-left:4px solid '.$i[1].';padding:12px 15px;margin:16px 0;border-radius:4px;font-size:14px;line-height:1.6;">'.nl2br(htmlspecialchars($texte)).'</div>' : '').'
        <p style="font-size:14px;line-height:1.6;margin-top:20px;">Cordialement,<br>L\'equipe de La Pharmacie Senegalaise</p>
      </div>
    </div>';
}
