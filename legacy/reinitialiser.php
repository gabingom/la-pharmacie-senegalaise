<?php
require_once 'config/db.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$erreur = ''; $ok = false; $tokenValide = false; $user = null;

// Verifier le token
if ($token) {
    $u = $pdo->prepare("SELECT * FROM utilisateurs WHERE token_reset=? AND token_expire > NOW() LIMIT 1");
    $u->execute([$token]);
    $user = $u->fetch();
    if ($user) $tokenValide = true;
}

// Traitement du nouveau mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValide) {
    $nouveau = $_POST['nouveau'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($nouveau) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($nouveau !== $confirm) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        $hash = password_hash($nouveau, PASSWORD_BCRYPT);
        // Mise a jour + effacer le token + enregistrer la date + consommer l'autorisation eventuelle
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=?, token_reset=NULL, token_expire=NULL, doit_changer_mdp=0, derniere_reset=NOW(), reset_autorise=0 WHERE id=?")
            ->execute([$hash, $user['id']]);
        $ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialiser le mot de passe — La Pharmacie Sénégalaise</title>
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
.center{text-align:center;}
.center .ic{font-size:3rem;margin-bottom:12px;}
.go{display:inline-block;margin-top:14px;background:#1faa4e;color:#fff;text-decoration:none;padding:12px 28px;border-radius:12px;font-weight:600;font-size:0.95rem;}
.back{display:block;text-align:center;margin-top:18px;color:#1faa4e;font-weight:600;text-decoration:none;font-size:0.9rem;}
</style>
</head>
<body>
<div class="card">
<?php if($ok): ?>
  <div class="center">
    <div class="ic">✅</div>
    <div class="title">Mot de passe réinitialisé !</div>
    <div class="sub">Votre nouveau mot de passe est enregistré. Vous pouvez maintenant vous connecter.</div>
    <a class="go" href="index.php">Se connecter →</a>
  </div>
<?php elseif(!$tokenValide): ?>
  <div class="center">
    <div class="ic">⚠️</div>
    <div class="title">Lien invalide ou expiré</div>
    <div class="sub">Ce lien de réinitialisation n'est plus valide (il expire au bout d'1 heure). Veuillez refaire une demande.</div>
    <a class="go" href="mot_de_passe_oublie.php">Refaire une demande →</a>
  </div>
<?php else: ?>
  <div class="logo">
    <div class="logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/><circle cx="50" cy="18" r="7" fill="#fff"/></svg></div>
    <div class="brand">La Pharmacie Sénégalaise</div>
  </div>
  <div class="title">Nouveau mot de passe</div>
  <div class="sub">Bonjour <?= htmlspecialchars($user['prenom']) ?>, choisissez votre nouveau mot de passe.</div>

  <?php if($erreur): ?><div class="err">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

  <form method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <label class="lbl">Nouveau mot de passe</label>
    <input class="inp" type="password" name="nouveau" placeholder="Au moins 6 caractères" required autofocus>
    <label class="lbl">Confirmer le mot de passe</label>
    <input class="inp" type="password" name="confirm" placeholder="Retapez le mot de passe" required>
    <button class="btn" type="submit">Réinitialiser mon mot de passe</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
