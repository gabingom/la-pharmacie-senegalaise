<!-- ============================================================
     ANIMATION D'ACCUEIL — reservee au role Etat
     S'affiche une seule fois par session, apres la connexion.
     ============================================================ -->
<style>
#wlc{position:fixed;inset:0;z-index:99999;background:radial-gradient(circle at 50% 45%,#2e9450 0%,#1c6d3a 45%,#0d3f22 100%);display:flex;align-items:center;justify-content:center;flex-direction:column;overflow:hidden;animation:wlcOut 0.9s ease 4.1s forwards;}
@keyframes wlcOut{to{opacity:0;visibility:hidden;}}

/* Rayons tournants */
#wlc .rays{position:absolute;width:1400px;height:1400px;background:repeating-conic-gradient(rgba(255,255,255,0.05) 0deg 8deg, transparent 8deg 16deg);animation:spin 26s linear infinite;opacity:0;animation:spin 26s linear infinite, rayFade 1s ease 0.3s forwards;}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes rayFade{to{opacity:1}}

/* Anneaux qui se propagent */
#wlc .ring{position:absolute;border:2px solid rgba(255,255,255,0.4);border-radius:50%;width:150px;height:150px;opacity:0;animation:ripple 2.6s ease-out infinite;}
#wlc .ring:nth-child(2){animation-delay:0.5s;}
#wlc .ring:nth-child(3){animation-delay:1s;}
@keyframes ripple{0%{transform:scale(1);opacity:0.55}100%{transform:scale(6);opacity:0}}

/* Sceau central */
#wlc .seal{position:relative;z-index:3;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,0.13);border:3px solid rgba(255,255,255,0.55);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);transform:scale(0) rotate(-90deg);animation:sealIn 1s cubic-bezier(.2,1.3,.4,1) 0.25s forwards;box-shadow:0 0 70px rgba(255,255,255,0.28);}
@keyframes sealIn{to{transform:scale(1) rotate(0)}}
#wlc .seal svg{width:74px;height:88px;}

/* Etoiles / eclats */
#wlc .spark{position:absolute;width:6px;height:6px;background:#fff;border-radius:50%;opacity:0;animation:sparkle 1.6s ease-out forwards;}
@keyframes sparkle{0%{opacity:0;transform:translate(0,0) scale(0)}40%{opacity:1;transform:translate(var(--dx),var(--dy)) scale(1.3)}100%{opacity:0;transform:translate(calc(var(--dx)*1.6),calc(var(--dy)*1.6)) scale(0)}}

/* Textes */
#wlc .txt{position:relative;z-index:3;text-align:center;margin-top:34px;}
#wlc .t1{font-size:0.82rem;letter-spacing:5px;text-transform:uppercase;color:rgba(255,255,255,0.66);font-weight:700;opacity:0;animation:txtIn 0.8s ease 1.1s forwards;}
#wlc .t2{font-size:2.5rem;font-weight:800;color:#fff;margin-top:12px;letter-spacing:1px;opacity:0;animation:txtIn 0.8s ease 1.4s forwards;text-shadow:0 4px 26px rgba(0,0,0,0.28);}
#wlc .t3{font-size:1rem;color:rgba(255,255,255,0.78);margin-top:14px;font-weight:500;opacity:0;animation:txtIn 0.8s ease 1.75s forwards;}
@keyframes txtIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}

/* Barre de decorum */
#wlc .bar{position:relative;z-index:3;margin-top:26px;width:0;height:3px;background:linear-gradient(90deg,transparent,#fff,transparent);border-radius:3px;animation:barGrow 1.2s ease 2.05s forwards;}
@keyframes barGrow{to{width:230px}}

/* Mention basse */
#wlc .foot{position:absolute;bottom:42px;z-index:3;font-size:0.8rem;color:rgba(255,255,255,0.45);letter-spacing:2px;text-transform:uppercase;opacity:0;animation:txtIn 0.9s ease 2.4s forwards;}
</style>

<div id="wlc">
  <div class="rays"></div>
  <div class="ring"></div><div class="ring"></div><div class="ring"></div>

  <div class="seal">
    <svg viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="50" cy="52" rx="38" ry="12" fill="#fff"/>
      <rect x="45" y="52" width="10" height="56" rx="4" fill="#fff"/>
      <ellipse cx="50" cy="112" rx="24" ry="6" fill="#fff"/>
      <path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#fff"/>
      <circle cx="50" cy="18" r="7" fill="#fff"/>
    </svg>
  </div>

  <div class="txt">
    <div class="t1">République du Sénégal</div>
    <div class="t2">Ministère de la Santé</div>
    <div class="t3">Autorité nationale de régulation pharmaceutique</div>
  </div>
  <div class="bar"></div>

  <div class="foot">Accès supervision — niveau souverain</div>
</div>

<script>
(function(){
  const w = document.getElementById('wlc');
  if(!w) return;

  // Eclats lumineux autour du sceau
  const seal = w.querySelector('.seal');
  for(let i = 0; i < 16; i++){
    const a = (Math.PI * 2 / 16) * i;
    const d = 110 + Math.random() * 70;
    const s = document.createElement('div');
    s.className = 'spark';
    s.style.setProperty('--dx', (Math.cos(a) * d).toFixed(0) + 'px');
    s.style.setProperty('--dy', (Math.sin(a) * d).toFixed(0) + 'px');
    s.style.animationDelay = (0.75 + Math.random() * 0.5).toFixed(2) + 's';
    w.appendChild(s);
    // centrer le point de depart sur le sceau
    s.style.left = '50%'; s.style.top = '46%';
  }

  // Retirer l'overlay du DOM apres l'animation
  setTimeout(function(){ if(w && w.parentNode) w.parentNode.removeChild(w); }, 5200);

  // Permettre de passer l'animation au clic
  w.addEventListener('click', function(){
    w.style.transition = 'opacity .4s'; w.style.opacity = '0';
    setTimeout(function(){ if(w && w.parentNode) w.parentNode.removeChild(w); }, 420);
  });
})();
</script>
