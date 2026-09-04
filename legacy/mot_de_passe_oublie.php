<?php
require_once 'config/db.php';
require_once 'config/mail.php';
require_once 'config/mailer.php';

$ok = false; $erreur = ''; $limite = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $erreur = "Veuillez saisir votre adresse email.";
    } else {
        // Chercher l'utilisateur
        $u = $pdo->prepare("SELECT * FROM utilisateurs WHERE email=? AND statut='actif' LIMIT 1");
        $u->execute([$email]);
        $user = $u->fetch();

        if ($user) {
            // Verifier la limite : 1 reinitialisation par mois (sauf si l'Etat a autorise)
            $limiteAtteinte = false;
            if (empty($user['reset_autorise']) && !empty($user['derniere_reset'])) {
                $joursDepuis = (time() - strtotime($user['derniere_reset'])) / 86400;
                if ($joursDepuis < 30) {
                    $limiteAtteinte = true;
                }
            }

            if ($limiteAtteinte) {
                // Au-dela de la limite : creer une demande d'autorisation a l'Etat
                // (si pas deja une demande en attente)
                $dej = $pdo->prepare("SELECT COUNT(*) FROM demandes_reset WHERE utilisateur_id=? AND statut='en_attente'");
                $dej->execute([$user['id']]);
                if ($dej->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO demandes_reset (utilisateur_id, email) VALUES (?,?)")
                        ->execute([$user['id'], $email]);
                }
                $limite = true;  // affichage special
            } else {
                // Generer un token unique valable 1 heure
                $token = bin2hex(random_bytes(32));
                $expire = date('Y-m-d H:i:s', time() + 3600);
                $pdo->prepare("UPDATE utilisateurs SET token_reset=?, token_expire=? WHERE id=?")
                    ->execute([$token, $expire, $user['id']]);

                // Lien de reinitialisation
                $base = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
                $lien = $base . '/reinitialiser.php?token=' . $token;

                // Email
                $corps = emailReset($user['prenom'], $lien);
                envoyerEmail($email, 'Reinitialisation de votre mot de passe - La Pharmacie Senegalaise', $corps);
                $ok = true;
            }
        } else {
            // Email inconnu : on affiche quand meme le message de succes (securite)
            $ok = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mot de passe oublié — La Pharmacie Sénégalaise</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:linear-gradient(170deg,#3dd44a 0%,#fff 48%,#4ecb5a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.card{background:rgba(255,255,255,0.95);border-radius:22px;box-shadow:0 24px 64px rgba(15,90,48,0.2);padding:44px;width:100%;max-width:430px;}
.logo{display:flex;align-items:center;gap:11px;justify-content:center;margin-bottom:8px;}
.logo-ring{width:46px;height:46px;background:#1faa4e;border-radius:50%;display:flex;align-items:center;justify-content:center;}
.logo-ring svg{width:26px;height:30px;}
.brand{font-size:1rem;font-weight:700;color:#1a7a40;}
.title{font-size:1.45rem;font-weight:700;color:#0a3d20;text-align:center;margin-bottom:6px;}
.sub{font-size:0.9rem;color:#5a8a6a;text-align:center;margin-bottom:26px;line-height:1.5;}
.lbl{font-size:0.85rem;font-weight:600;color:#2d6b45;margin-bottom:6px;display:block;}
.inp{width:100%;padding:13px 15px;border:1.5px solid #d4ebdb;border-radius:12px;font-size:0.98rem;color:#1a3a25;margin-bottom:16px;outline:none;font-family:inherit;}
.inp:focus{border-color:#1faa4e;box-shadow:0 0 0 3px rgba(31,170,78,0.12);}
.btn{width:100%;padding:14px;background:#1faa4e;color:#fff;border:none;border-radius:12px;font-size:1.02rem;font-weight:600;cursor:pointer;}
.btn:hover{background:#178a3e;}
.err{background:#fdeded;color:#a32d2d;padding:11px 15px;border-radius:11px;font-size:0.88rem;margin-bottom:16px;}
.success{text-align:center;}
.success .ic{font-size:3rem;margin-bottom:12px;}
.back{display:block;text-align:center;margin-top:18px;color:#1faa4e;font-weight:600;text-decoration:none;font-size:0.9rem;}
.back:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="card">
<?php if($limite): ?>
  <div class="success">
    <div class="ic">🔒</div>
    <div class="title">Limite atteinte</div>
    <div class="sub">Vous avez déjà réinitialisé votre mot de passe ce mois-ci. Pour des raisons de sécurité, une nouvelle réinitialisation nécessite l'autorisation du Ministère de la Santé.<br><br>Votre demande a été transmise. Vous serez recontacté une fois l'autorisation accordée.</div>
    <a class="back" href="index.php">← Retour à la connexion</a>
  </div>
<?php elseif($ok): ?>
  <div class="success">
    <div class="ic">📬</div>
    <div class="title">Email envoyé</div>
    <div class="sub">Si cette adresse correspond à un compte, vous recevrez un lien de réinitialisation dans quelques instants. Pensez à vérifier vos spams.</div>
    <a class="back" href="index.php">← Retour à la connexion</a>
  </div>
<?php else: ?>
  <div class="logo">
    <div class="logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/><circle cx="50" cy="18" r="7" fill="#fff"/></svg></div>
    <div class="brand">La Pharmacie Sénégalaise</div>
  </div>
  <div class="title">Mot de passe oublié ?</div>
  <div class="sub">Saisissez votre adresse email. Nous vous enverrons un lien pour réinitialiser votre mot de passe.</div>

  <?php if($erreur): ?><div class="err">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

  <form method="POST">
    <label class="lbl">Adresse email</label>
    <input class="inp" type="email" name="email" placeholder="votre@email.sn" required autofocus>
    <button class="btn" type="submit">Envoyer le lien de réinitialisation</button>
  </form>
  <a class="back" href="index.php">← Retour à la connexion</a>
<?php endif; ?>
</div>
</body>
</html>
