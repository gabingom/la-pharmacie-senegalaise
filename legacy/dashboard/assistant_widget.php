<!-- ============================================================
     ASSISTANT CONVERSATIONNEL — widget flottant
     A inclure dans les dashboards (avant </body>)
     ============================================================ -->
<style>
.chat-fab{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:var(--green);box-shadow:0 8px 24px rgba(31,170,78,0.4);display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:999;transition:transform 0.2s,background 0.2s;border:none;}
.chat-fab:hover{transform:scale(1.08);background:var(--green-d);}
.chat-fab i{font-size:1.7rem;color:#fff;}
.chat-fab .badge-ia{position:absolute;top:-2px;right:-2px;background:#5b4cc4;color:#fff;font-size:0.6rem;font-weight:700;padding:2px 6px;border-radius:10px;border:2px solid #fff;}
.chat-win{position:fixed;bottom:96px;right:24px;width:380px;max-width:calc(100vw - 48px);height:520px;max-height:calc(100vh - 130px);background:#fff;border-radius:18px;box-shadow:0 16px 48px rgba(15,90,48,0.25);display:none;flex-direction:column;overflow:hidden;z-index:999;border:1px solid var(--green-border);}
.chat-win.open{display:flex;animation:chatIn 0.25s ease;}
@keyframes chatIn{from{opacity:0;transform:translateY(16px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.chat-head{background:var(--green);padding:15px 18px;display:flex;align-items:center;gap:11px;flex-shrink:0;}
.chat-head-av{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.chat-head-av i{font-size:1.3rem;color:#fff;}
.chat-head-txt{flex:1;color:#fff;}
.chat-head-title{font-size:0.98rem;font-weight:700;}
.chat-head-sub{font-size:0.74rem;opacity:0.85;display:flex;align-items:center;gap:5px;}
.chat-head-sub::before{content:'';width:7px;height:7px;background:#7dffa6;border-radius:50%;display:inline-block;}
.chat-close{background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;opacity:0.85;}
.chat-close:hover{opacity:1;}
.chat-body{flex:1;overflow-y:auto;padding:18px;background:#f4faf6;display:flex;flex-direction:column;gap:12px;}
.chat-msg{max-width:82%;padding:11px 14px;border-radius:14px;font-size:0.88rem;line-height:1.5;}
.chat-msg.bot{background:#fff;color:var(--body);border:1px solid var(--green-border);align-self:flex-start;border-bottom-left-radius:4px;}
.chat-msg.user{background:var(--green);color:#fff;align-self:flex-end;border-bottom-right-radius:4px;}
.chat-msg.bot b{color:var(--green-deep);}
.chat-typing{align-self:flex-start;background:#fff;border:1px solid var(--green-border);padding:13px 16px;border-radius:14px;border-bottom-left-radius:4px;display:none;}
.chat-typing.show{display:block;}
.chat-typing span{display:inline-block;width:7px;height:7px;background:var(--muted);border-radius:50%;margin:0 1px;animation:typing 1.2s infinite;}
.chat-typing span:nth-child(2){animation-delay:0.2s;}
.chat-typing span:nth-child(3){animation-delay:0.4s;}
@keyframes typing{0%,60%,100%{opacity:0.3;transform:translateY(0)}30%{opacity:1;transform:translateY(-4px)}}
.chat-suggest{display:flex;flex-wrap:wrap;gap:7px;padding:0 18px 12px;background:#f4faf6;}
.chat-chip{background:#fff;border:1px solid var(--green-border);color:var(--green-d);font-size:0.78rem;padding:6px 12px;border-radius:16px;cursor:pointer;transition:all 0.15s;}
.chat-chip:hover{background:var(--green-pale);border-color:var(--green);}
.chat-foot{padding:12px 14px;background:#fff;border-top:1px solid var(--green-border);display:flex;gap:9px;flex-shrink:0;}
.chat-input{flex:1;border:1.5px solid var(--green-border);border-radius:22px;padding:10px 16px;font-size:0.88rem;outline:none;font-family:inherit;color:var(--body);}
.chat-input:focus{border-color:var(--green);}
.chat-send{width:42px;height:42px;border-radius:50%;background:var(--green);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.15s;}
.chat-send:hover{background:var(--green-d);}
.chat-send i{font-size:1.2rem;}
</style>

<button class="chat-fab" id="chatFab" onclick="toggleChat()" title="Assistant">
  <i class="ti ti-message-chatbot"></i>
  <span class="badge-ia">IA</span>
</button>

<div class="chat-win" id="chatWin">
  <div class="chat-head">
    <div class="chat-head-av"><i class="ti ti-robot"></i></div>
    <div class="chat-head-txt">
      <div class="chat-head-title">Assistant Pharmacie</div>
      <div class="chat-head-sub">En ligne</div>
    </div>
    <button class="chat-close" onclick="toggleChat()"><i class="ti ti-x"></i></button>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="chat-msg bot">Bonjour. Je suis l'assistant de <b>La Pharmacie Sénégalaise</b>. Je peux vous renseigner sur le fonctionnement de la plateforme, les données en temps réel et la logistique sanitaire. Comment puis-je vous aider ?</div>
    <div class="chat-typing" id="chatTyping"><span></span><span></span><span></span></div>
  </div>
  <div class="chat-suggest" id="chatSuggest">
    <div class="chat-chip" onclick="askChat('Quels sont les stocks critiques ?')">Stocks critiques</div>
    <div class="chat-chip" onclick="askChat('Quels médicaments risquent la rupture ?')">Risques de rupture</div>
    <div class="chat-chip" onclick="askChat('Quels transferts proposez-vous ?')">Rééquilibrage</div>
    <div class="chat-chip" onclick="askChat('Comment demander une subvention ?')">Subventions</div>
  </div>
  <div class="chat-foot">
    <input type="text" class="chat-input" id="chatInput" placeholder="Écrivez votre question..." onkeydown="if(event.key==='Enter')sendChat()">
    <button class="chat-send" onclick="sendChat()"><i class="ti ti-send"></i></button>
  </div>
</div>

<script>
function toggleChat(){
  document.getElementById('chatWin').classList.toggle('open');
}
function askChat(txt){
  document.getElementById('chatInput').value = txt;
  sendChat();
}
async function sendChat(){
  const input = document.getElementById('chatInput');
  const body = document.getElementById('chatBody');
  const typing = document.getElementById('chatTyping');
  const suggest = document.getElementById('chatSuggest');
  const q = input.value.trim();
  if(!q) return;

  // Message utilisateur
  const um = document.createElement('div');
  um.className = 'chat-msg user';
  um.textContent = q;
  body.insertBefore(um, typing);
  input.value = '';
  suggest.style.display = 'none';

  // Indicateur de frappe
  typing.classList.add('show');
  body.scrollTop = body.scrollHeight;

  try{
    const fd = new FormData();
    fd.append('question', q);
    const res = await fetch('actions/assistant.php', {method:'POST', body:fd});
    const j = await res.json();
    // Petite pause pour l'effet naturel
    setTimeout(()=>{
      typing.classList.remove('show');
      const bm = document.createElement('div');
      bm.className = 'chat-msg bot';
      bm.innerHTML = j.reponse;
      body.insertBefore(bm, typing);
      body.scrollTop = body.scrollHeight;
    }, 500);
  }catch(e){
    typing.classList.remove('show');
    const bm = document.createElement('div');
    bm.className = 'chat-msg bot';
    bm.textContent = "Désolé, une erreur est survenue. Réessayez.";
    body.insertBefore(bm, typing);
  }
}
</script>
