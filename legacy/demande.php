<?php
session_start();
$success = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    $nom=trim($_POST['nom']??''); $prenom=trim($_POST['prenom']??'');
    $structure=trim($_POST['structure_nom']??''); $role=$_POST['role_demande']??'';
    $email=trim($_POST['email']??''); $tel=trim($_POST['telephone']??'');
    if($nom&&$prenom&&$structure&&$role&&$email){
        if($role==='etat'){ $error="Le rôle État ne peut pas être demandé via ce formulaire."; }
        else{
            $stmt=$pdo->prepare("INSERT INTO demandes_acces (nom,prenom,structure_nom,role_demande,email,telephone) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$nom,$prenom,$structure,$role,$email,$tel]);
            $success=true;
        }
    } else { $error="Veuillez remplir tous les champs obligatoires."; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Pharmacie Sénégalaise — Demande d'accès</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:linear-gradient(170deg,#3dd44a 0%,#fff 48%,#4ecb5a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.card{background:rgba(255,255,255,0.92);border-radius:20px;box-shadow:0 18px 50px rgba(15,90,48,0.18);padding:36px;width:100%;max-width:460px;}
.title{font-size:1.4rem;font-weight:700;color:#0a3d20;text-align:center;margin-bottom:8px;}
.warn{background:#fef6e7;border:1px solid #f0c040;border-radius:12px;padding:13px 16px;font-size:0.88rem;color:#5c3d00;line-height:1.6;margin-bottom:20px;}
.lbl{font-size:0.82rem;font-weight:600;color:#2d6b45;margin-bottom:5px;display:block;}
.inp{width:100%;padding:11px 14px;border:1.5px solid #d4ebdb;border-radius:11px;font-size:0.95rem;color:#1a3a25;margin-bottom:14px;outline:none;font-family:inherit;}
.inp:focus{border-color:#1faa4e;}
.btn{width:100%;padding:13px;background:#1faa4e;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:600;cursor:pointer;}
.btn:hover{background:#178a3e;}
.success{text-align:center;}
.success .ic{font-size:3rem;margin-bottom:12px;}
.back{display:block;text-align:center;margin-top:16px;color:#1faa4e;font-weight:600;text-decoration:none;font-size:0.9rem;}
.err{background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:0.88rem;margin-bottom:14px;}
.note{background:#eafaf0;border-radius:11px;padding:12px 15px;font-size:0.85rem;color:#1a5c30;margin-bottom:16px;line-height:1.55;}
</style>
</head>
<body>
<div class="card">
<?php if($success): ?>
  <div class="success">
    <div class="ic">✅</div>
    <div class="title">Demande envoyée !</div>
    <p style="color:#2d6b45;font-size:0.9rem;line-height:1.6;margin-top:8px;">Le Ministère de la Santé examinera votre dossier et vous contactera sous 72h.</p>
    <a class="back" href="index.php">← Retour à la connexion</a>
  </div>
<?php else: ?>
  <div class="title">Demande d'accès</div>
  <p style="text-align:center;color:#5a8a6a;font-size:0.88rem;margin-bottom:18px;">Plateforme officielle — Ministère de la Santé</p>
  <div class="warn">🔒 <strong>Accès contrôlé par l'État.</strong> Aucune inscription libre. Tout compte est créé et validé par le Ministère après vérification.</div>
  <?php if($error): ?><div class="err">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label class="lbl">Prénom *</label><input class="inp" type="text" name="prenom" required>
    <label class="lbl">Nom *</label><input class="inp" type="text" name="nom" required>
    <label class="lbl">Structure / Organisation *</label><input class="inp" type="text" name="structure_nom" placeholder="ex. Pharmacie Centrale de Thiès" required>
    <label class="lbl">Rôle demandé *</label>
    <select class="inp" name="role_demande" required>
      <option value="">— Sélectionner —</option>
      <option value="pra">PRA (Pharmacie de Répartition)</option>
      <option value="pharmacie">Pharmacie agréée</option>
      <option value="fournisseur">Fournisseur</option>
    </select>
    <label class="lbl">Email officiel *</label><input class="inp" type="email" name="email" required>
    <label class="lbl">Téléphone</label><input class="inp" type="tel" name="telephone">
    <div class="note">ℹ️ Le rôle <strong>État</strong> n'est jamais accessible via ce formulaire.</div>
    <button class="btn" type="submit">📤 Envoyer la demande</button>
  </form>
  <a class="back" href="index.php">← Retour à la connexion</a>
<?php endif; ?>
</div>
</body>
</html>
