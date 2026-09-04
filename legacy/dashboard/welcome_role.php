<?php
// ============================================================
//  ANIMATION D'ACCUEIL — PRA et Pharmacie
//  Volontairement plus sobre que celle de l'Etat :
//  une carte qui apparait, un logo, un mot de bienvenue.
//  Duree ~2,4 s. Une seule fois par session.
//  Variables attendues : $accueilTitre, $accueilSousTitre
// ============================================================
$accueilTitre     = $accueilTitre     ?? 'Bienvenue';
$accueilSousTitre = $accueilSousTitre ?? '';
?>
<style>
#wlc2{position:fixed;inset:0;z-index:99999;background:rgba(20,81,44,0.55);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;animation:w2Out 0.6s ease 2.2s forwards;}
@keyframes w2Out{to{opacity:0;visibility:hidden;}}

#wlc2 .box{background:#fff;border-radius:22px;padding:38px 46px;text-align:center;box-shadow:0 22px 60px rgba(0,0,0,0.22);transform:scale(0.9);opacity:0;animation:w2In 0.55s cubic-bezier(.2,1.2,.4,1) 0.1s forwards;}
@keyframes w2In{to{transform:scale(1);opacity:1}}

#wlc2 .ring{width:78px;height:78px;margin:0 auto 18px;border-radius:50%;background:var(--green,#2e9450);display:flex;align-items:center;justify-content:center;position:relative;}
#wlc2 .ring svg{width:38px;height:46px;}
#wlc2 .ring::after{content:'';position:absolute;inset:-8px;border-radius:50%;border:2px solid var(--green,#2e9450);opacity:0.35;animation:w2Pulse 1.6s ease-out infinite;}
@keyframes w2Pulse{0%{transform:scale(1);opacity:.4}100%{transform:scale(1.35);opacity:0}}

#wlc2 .t{font-size:1.5rem;font-weight:700;color:#173a26;opacity:0;animation:w2Up .5s ease .45s forwards;}
#wlc2 .s{font-size:0.95rem;color:#6b8a76;margin-top:6px;opacity:0;animation:w2Up .5s ease .62s forwards;}
@keyframes w2Up{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

#wlc2 .bar{margin:20px auto 0;width:0;height:3px;background:var(--green,#2e9450);border-radius:3px;opacity:0.75;animation:w2Bar 1.3s ease .8s forwards;}
@keyframes w2Bar{to{width:130px}}
</style>

<div id="wlc2">
  <div class="box">
    <div class="ring">
      <svg viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/>
        <rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/>
        <ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/>
        <path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/>
        <circle cx="50" cy="18" r="7" fill="#fff"/>
      </svg>
    </div>
    <div class="t"><?= htmlspecialchars($accueilTitre) ?></div>
    <?php if($accueilSousTitre): ?><div class="s"><?= htmlspecialchars($accueilSousTitre) ?></div><?php endif; ?>
    <div class="bar"></div>
  </div>
</div>

<script>
(function(){
  var w = document.getElementById('wlc2');
  if(!w) return;
  setTimeout(function(){ if(w.parentNode) w.parentNode.removeChild(w); }, 3000);
  w.addEventListener('click', function(){
    w.style.transition = 'opacity .3s'; w.style.opacity = '0';
    setTimeout(function(){ if(w.parentNode) w.parentNode.removeChild(w); }, 320);
  });
})();
</script>
