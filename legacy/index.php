<?php
// Si deja connecte, rediriger vers le bon dashboard
session_start();
if (!empty($_SESSION['user_id'])) {
    $pages = [
        'etat'        => 'dashboard/etat.php',
        'pra'         => 'dashboard/pra.php',
        'pharmacie'   => 'dashboard/pharmacie.php',
        'fournisseur' => 'dashboard/pharmacie.php',
    ];
    header('Location: ' . ($pages[$_SESSION['user_role']] ?? 'index.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>La Pharmacie Sénégalaise — Connexion</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{
  --green:#2e9450;--green-l:#3aa85f;--green-d:#247a41;--green-deep:#14512c;
  --cream:#e9e5da;--surface:#f4f2eb;
}
body{font-family:'Inter',sans-serif;background:var(--cream);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}

/* Cadre general */
.shell{width:100%;max-width:1120px;min-height:640px;background:#fdfdfb;border-radius:28px;box-shadow:0 24px 70px rgba(20,81,44,0.16);display:flex;overflow:hidden;}

/* ---------- Colonne gauche : formulaire ---------- */
.side-form{flex:1;padding:54px 56px;display:flex;flex-direction:column;justify-content:center;position:relative;}
.side-form::before{content:'';position:absolute;top:-60px;left:-60px;width:170px;height:170px;background:#eff8f2;border-radius:50%;pointer-events:none;}
.brand{display:flex;align-items:center;gap:13px;margin-bottom:38px;position:relative;z-index:2;}
.brand-ring{width:52px;height:52px;background:var(--green);border-radius:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(46,148,80,0.3);flex-shrink:0;}
.brand-ring svg{width:27px;height:33px;}
.brand-txt{line-height:1.25;}
.brand-name{font-size:1.08rem;font-weight:800;color:var(--green-deep);}
.brand-sub{font-size:0.8rem;color:#8a9a90;font-weight:500;}

.h-title{font-size:2rem;font-weight:800;color:#17301f;margin-bottom:8px;position:relative;z-index:2;}
.h-sub{font-size:0.98rem;color:#7e8f84;margin-bottom:34px;position:relative;z-index:2;}

.fld{position:relative;margin-bottom:18px;z-index:2;}
.fld-lbl{font-size:0.86rem;font-weight:600;color:#4a6555;margin-bottom:8px;display:block;}
.fld-box{position:relative;display:flex;align-items:center;}
.fld-box i{position:absolute;left:16px;font-size:20px;color:#a8bdb0;transition:color 0.18s;pointer-events:none;}
.fld-box input{width:100%;padding:15px 16px 15px 48px;border:1.6px solid #e3e6df;border-radius:14px;background:#fff;color:#213328;font-size:1rem;outline:none;transition:all 0.18s;font-family:inherit;}
.fld-box input:focus{border-color:var(--green);box-shadow:0 0 0 4px rgba(46,148,80,0.11);}
.fld-box input:focus + i,.fld-box:focus-within i{color:var(--green);}
.fld-box input::placeholder{color:#b3c2b8;}
.eye{position:absolute;right:14px;left:auto;font-size:19px;color:#a8bdb0;cursor:pointer;pointer-events:auto !important;}
.eye:hover{color:var(--green);}

.row{display:flex;align-items:center;justify-content:space-between;margin:22px 0 26px;font-size:0.92rem;position:relative;z-index:2;}
.remember{display:flex;align-items:center;gap:9px;color:#5f7a68;cursor:pointer;user-select:none;}
.remember input{width:18px;height:18px;accent-color:var(--green);cursor:pointer;}
.forgot{color:var(--green);font-weight:600;text-decoration:none;}
.forgot:hover{text-decoration:underline;}

.btn{width:100%;padding:16px;background:var(--green);color:#fff;border:none;border-radius:14px;font-size:1.05rem;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:10px;transition:all 0.18s;box-shadow:0 8px 20px rgba(46,148,80,0.28);position:relative;z-index:2;}
.btn:hover{background:var(--green-d);transform:translateY(-2px);box-shadow:0 12px 26px rgba(46,148,80,0.34);}
.btn:active{transform:translateY(0);}
.btn:disabled{opacity:0.75;cursor:wait;transform:none;}
.btn i{font-size:1.15rem;}
.btn-spin{display:none;width:19px;height:19px;border:2.5px solid rgba(255,255,255,0.35);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}

.msg-err,.msg-ok{font-size:0.92rem;padding:12px 16px;border-radius:12px;margin-top:16px;display:none;align-items:center;gap:9px;position:relative;z-index:2;}
.msg-err{background:#fdeded;color:#a32d2d;}
.msg-ok{background:#e9f7ee;color:#247a41;}
.req{text-align:center;margin-top:28px;font-size:0.93rem;color:#7e8f84;position:relative;z-index:2;}
.req a{color:var(--green);font-weight:600;text-decoration:none;}
.req a:hover{text-decoration:underline;}

/* ---------- Colonne droite : panneau vert ---------- */
.side-visual{width:46%;background:linear-gradient(160deg,var(--green-l),var(--green) 55%,var(--green-d));position:relative;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:56px 48px;}
.blob{position:absolute;border-radius:50%;pointer-events:none;}
.b1{width:340px;height:340px;background:rgba(255,255,255,0.09);top:-110px;right:-90px;animation:f1 10s ease-in-out infinite;}
.b2{width:250px;height:250px;background:rgba(255,255,255,0.07);bottom:-90px;left:-70px;animation:f2 12s ease-in-out infinite;}
.b3{width:150px;height:150px;background:rgba(255,255,255,0.06);top:120px;left:30px;animation:f1 14s ease-in-out infinite;}
@keyframes f1{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,18px)}}
@keyframes f2{0%,100%{transform:translate(0,0)}50%{transform:translate(16px,-16px)}}

.vis-in{position:relative;z-index:2;animation:up 0.8s ease both;}
@keyframes up{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.vis-emblem{width:112px;height:112px;margin:0 auto 30px;background:rgba(255,255,255,0.16);border:2px solid rgba(255,255,255,0.3);border-radius:32px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.vis-emblem svg{width:54px;height:64px;}
.vis-title{font-size:2rem;font-weight:800;color:#fff;letter-spacing:0.4px;margin-bottom:14px;}
.vis-quote{font-size:1.06rem;color:rgba(255,255,255,0.92);line-height:1.75;max-width:330px;margin:0 auto;}
.vis-author{font-size:0.9rem;color:rgba(255,255,255,0.68);margin-top:18px;font-weight:500;}

/* Trois piliers illustres */
.pillars{display:flex;gap:16px;margin-top:44px;position:relative;z-index:2;}
.pil{flex:1;background:rgba(255,255,255,0.13);border:1px solid rgba(255,255,255,0.18);border-radius:16px;padding:16px 10px;backdrop-filter:blur(4px);transition:transform 0.2s;}
.pil:hover{transform:translateY(-3px);}
.pil i{font-size:1.5rem;color:#fff;display:block;margin-bottom:7px;}
.pil span{font-size:0.76rem;color:rgba(255,255,255,0.88);font-weight:600;letter-spacing:0.2px;}

@media(max-width:900px){
  .shell{flex-direction:column;}
  .side-visual{width:100%;padding:40px 30px;}
  .side-form{padding:40px 32px;}
  .pillars{margin-top:28px;}
}
</style>
</head>
<body>
<div class="shell">

  <!-- Formulaire -->
  <div class="side-form">
    <div class="brand">
      <div class="brand-ring">
        <svg viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/><circle cx="50" cy="18" r="7" fill="#fff"/></svg>
      </div>
      <div class="brand-txt">
        <div class="brand-name">La Pharmacie Sénégalaise</div>
        <div class="brand-sub">Ministère de la Santé</div>
      </div>
    </div>

    <div class="h-title">Connexion</div>
    <div class="h-sub">Accédez à votre espace de gestion pharmaceutique.</div>

    <form id="loginForm" autocomplete="off">
      <div class="fld">
        <label class="fld-lbl">Adresse email</label>
        <div class="fld-box">
          <input type="email" id="email" name="email" placeholder="votre@email.sn" required />
          <i class="ti ti-mail"></i>
        </div>
      </div>
      <div class="fld">
        <label class="fld-lbl">Mot de passe</label>
        <div class="fld-box">
          <input type="password" id="password" name="password" placeholder="••••••••" required />
          <i class="ti ti-lock"></i>
          <i class="ti ti-eye eye" id="eyeBtn" onclick="toggleMdp()"></i>
        </div>
      </div>

      <div class="row">
        <label class="remember"><input type="checkbox" id="remember" /> Se souvenir de mon email</label>
        <a class="forgot" href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
      </div>

      <button type="submit" class="btn" id="loginBtn">
        <i class="ti ti-login-2" id="btnIcon"></i>
        <span id="btnText">Se connecter</span>
        <div class="btn-spin" id="btnSpin"></div>
      </button>

      <div class="msg-err" id="msgErr"></div>
      <div class="msg-ok" id="msgOk"></div>
    </form>

    <div class="req">Pas encore de compte ? <a href="demande.php">Faire une demande d'accès</a></div>
  </div>

  <!-- Panneau visuel -->
  <div class="side-visual">
    <div class="blob b1"></div><div class="blob b2"></div><div class="blob b3"></div>
    <div class="vis-in">
      <div class="vis-emblem">
        <svg viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/><circle cx="50" cy="18" r="7" fill="#fff"/></svg>
      </div>
      <div class="vis-title">Bienvenue</div>
      <div class="vis-quote">« La santé est un trésor qu'il faut préserver, et un droit qu'il faut garantir à tous. »</div>
      <div class="vis-author">— La Pharmacie Sénégalaise</div>

      <div class="pillars">
        <div class="pil"><i class="ti ti-building-warehouse"></i><span>Stocks</span></div>
        <div class="pil"><i class="ti ti-map-2"></i><span>Territoire</span></div>
        <div class="pil"><i class="ti ti-shield-check"></i><span>Équité</span></div>
      </div>
    </div>
  </div>

</div>

<script>
function toggleMdp(){
  const p = document.getElementById('password');
  const e = document.getElementById('eyeBtn');
  if(p.type === 'password'){ p.type = 'text'; e.className = 'ti ti-eye-off eye'; }
  else { p.type = 'password'; e.className = 'ti ti-eye eye'; }
}

// Se souvenir de l'email : pre-remplissage au chargement
(function(){
  const memo = localStorage.getItem('lps_email');
  if(memo){
    document.getElementById('email').value = memo;
    document.getElementById('remember').checked = true;
    document.getElementById('password').focus();
  }
})();

document.getElementById('loginForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const err=document.getElementById('msgErr'), ok=document.getElementById('msgOk');
  const spin=document.getElementById('btnSpin'), txt=document.getElementById('btnText'), ico=document.getElementById('btnIcon');
  const btn=document.getElementById('loginBtn');
  err.style.display='none'; ok.style.display='none';

  txt.style.display='none'; ico.style.display='none'; spin.style.display='block'; btn.disabled=true;

  const data=new FormData(this);
  try{
    const res=await fetch('auth/login.php',{method:'POST',body:data});
    const j=await res.json();
    if(j.success){
      // Memoriser (ou oublier) l'email selon la case cochee
      if(document.getElementById('remember').checked){
        localStorage.setItem('lps_email', document.getElementById('email').value.trim());
      } else {
        localStorage.removeItem('lps_email');
      }
      ok.innerHTML='<i class="ti ti-circle-check"></i> Connexion réussie — redirection…';
      ok.style.display='flex';
      setTimeout(()=>{window.location.href=j.redirect;},700);
    }else{
      err.innerHTML='<i class="ti ti-alert-circle"></i> '+j.message;
      err.style.display='flex';
      spin.style.display='none'; txt.style.display='inline'; ico.style.display='inline'; btn.disabled=false;
      document.getElementById('password').value='';
    }
  }catch(ex){
    err.innerHTML='<i class="ti ti-alert-circle"></i> Erreur de connexion au serveur.';
    err.style.display='flex';
    spin.style.display='none'; txt.style.display='inline'; ico.style.display='inline'; btn.disabled=false;
  }
});
</script>
</body>
</html>
