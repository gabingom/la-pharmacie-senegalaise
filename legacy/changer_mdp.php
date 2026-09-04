<?php
require_once 'config/session.php';
require_once 'config/db.php';
exigerConnexionSimple();

$erreur = ''; $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actuel  = $_POST['actuel'] ?? '';
    $nouveau = $_POST['nouveau'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Recuperer l'utilisateur
    $u = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=?");
    $u->execute([idUtilisateur()]);
    $user = $u->fetch();

    if (!password_verify($actuel, $user['mot_de_passe'])) {
        $erreur = "Le mot de passe actuel est incorrect.";
    } elseif (strlen($nouveau) < 6) {
        $erreur = "Le nouveau mot de passe doit faire au moins 6 caractères.";
    } elseif ($nouveau !== $confirm) {
        $erreur = "Les deux nouveaux mots de passe ne correspondent pas.";
    } elseif ($nouveau === $actuel) {
        $erreur = "Le nouveau mot de passe doit être différent de l'ancien.";
    } else {
        // Mise a jour
        $hash = password_hash($nouveau, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=?, doit_changer_mdp=0 WHERE id=?")
            ->execute([$hash, idUtilisateur()]);
        unset($_SESSION['doit_changer_mdp']);
        $ok = true;
    }
}

// Page de destination apres changement
$pages = ['etat'=>'dashboard/etat.php','pra'=>'dashboard/pra.php','pharmacie'=>'dashboard/pharmacie.php','fournisseur'=>'dashboard/pharmacie.php'];
$dest = $pages[roleUtilisateur()] ?? 'index.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Changer mon mot de passe — La Pharmacie Sénégalaise</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/dist/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:linear-gradient(170deg,#3dd44a 0%,#fff 48%,#4ecb5a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.card{background:rgba(255,255,255,0.95);border-radius:22px;box-shadow:0 24px 64px rgba(15,90,48,0.2);padding:44px;width:100%;max-width:440px;}
.logo{display:flex;align-items:center;gap:11px;justify-content:center;margin-bottom:8px;}
.logo-ring{width:46px;height:46px;background:#1faa4e;border-radius:50%;display:flex;align-items:center;justify-content:center;}
.logo-ring svg{width:26px;height:30px;}
.brand{font-size:1rem;font-weight:700;color:#1a7a40;}
.title{font-size:1.5rem;font-weight:700;color:#0a3d20;text-align:center;margin-bottom:6px;}
.sub{font-size:0.9rem;color:#5a8a6a;text-align:center;margin-bottom:26px;line-height:1.5;}
.lbl{font-size:0.85rem;font-weight:600;color:#2d6b45;margin-bottom:6px;display:block;}
.inp{width:100%;padding:13px 15px;border:1.5px solid #d4ebdb;border-radius:12px;font-size:0.98rem;color:#1a3a25;margin-bottom:16px;outline:none;font-family:inherit;}
.inp:focus{border-color:#1faa4e;box-shadow:0 0 0 3px rgba(31,170,78,0.12);}
.btn{width:100%;padding:14px;background:#1faa4e;color:#fff;border:none;border-radius:12px;font-size:1.02rem;font-weight:600;cursor:pointer;}
.btn:hover{background:#178a3e;}
.err{background:#fdeded;color:#a32d2d;padding:11px 15px;border-radius:11px;font-size:0.88rem;margin-bottom:16px;}
.success{text-align:center;}
.success .ic{font-size:3rem;margin-bottom:12px;}
.info{background:#fef6e7;border:1px solid #f0c040;border-radius:11px;padding:12px 15px;font-size:0.85rem;color:#5c3d00;margin-bottom:20px;line-height:1.5;}
.go{display:inline-block;margin-top:14px;background:#1faa4e;color:#fff;text-decoration:none;padding:12px 28px;border-radius:12px;font-weight:600;font-size:0.95rem;}
</style>
</head>
<body>
<div class="card">
<?php if($ok): ?>
  <div class="success">
    <div class="ic">✅</div>
    <div class="title">Mot de passe modifié !</div>
    <div class="sub">Votre nouveau mot de passe est enregistré. Vous pouvez maintenant accéder à votre espace.</div>
    <a class="go" href="<?= $dest ?>">Accéder à mon tableau de bord →</a>
  </div>
<?php else: ?>
  <div class="logo">
    <div class="logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/><circle cx="50" cy="18" r="7" fill="#fff"/></svg></div>
    <div class="brand">La Pharmacie Sénégalaise</div>
  </div>
  <div class="title">Changez votre mot de passe</div>
  <div class="sub">Pour votre sécurité, vous devez définir un nouveau mot de passe personnel avant d'accéder à la plateforme.</div>

  <?php if(isset($_SESSION['doit_changer_mdp'])): ?>
  <div class="info">🔐 Vous utilisez un mot de passe temporaire fourni par le Ministère. Choisissez-en un nouveau, connu de vous seul.</div>
  <?php endif; ?>

  <?php if($erreur): ?><div class="err">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>

  <form method="POST">
    <label class="lbl">Mot de passe actuel (temporaire)</label>
    <input class="inp" type="password" name="actuel" placeholder="Le mot de passe reçu par email" required>
    <label class="lbl">Nouveau mot de passe</label>
    <input class="inp" type="password" name="nouveau" placeholder="Au moins 6 caractères" required>
    <label class="lbl">Confirmer le nouveau mot de passe</label>
    <input class="inp" type="password" name="confirm" placeholder="Retapez le nouveau mot de passe" required>
    <button class="btn" type="submit">Valider mon nouveau mot de passe</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
