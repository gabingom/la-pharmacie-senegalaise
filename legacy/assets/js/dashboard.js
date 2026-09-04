// ============================================================
//  La Pharmacie Sénégalaise — JS commun des dashboards
// ============================================================
const titles = window.SECTION_TITLES || {};

function nav(id, el) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
  const sec = document.getElementById(id);
  if (sec) sec.classList.add('active');
  if (el) el.classList.add('active');
  const t = document.getElementById('topTitle');
  if (t && titles[id]) t.textContent = titles[id];
  if (id === 'stats' && typeof initCharts === 'function' && !window._charts) {
    window._charts = true;
    setTimeout(initCharts, 100);
  }
}

// Action générique sur une ligne (valider / rejeter)
async function action(url, id, act, btn) {
  const labels = {valider:'Valider cette commande ?', rejeter:'Rejeter cette commande ?',
    approuver:'Approuver ?', subventionner:'Approuver cette subvention ?'};
  if (!confirm(labels[act] || 'Confirmer cette action ?')) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('action', act === 'subventionner' ? 'approuver' : act);
  try {
    const res = await fetch(url, {method:'POST', body:fd});
    const j = await res.json();
    if (j.success) {
      const row = btn.closest('tr');
      const st = row ? row.querySelector('.st-cell') : null;
      const positif = ['valider','approuver','subventionner'].includes(act);
      // Statut affiche selon le retour serveur
      let label = positif ? '✓ Validé' : 'Rejeté';
      let cls = positif ? 'p-ok' : 'p-bad';
      if (j.livree) { label = '✓ Livrée'; cls = 'p-ok'; }
      else if (j.reappro) { label = 'À réapprovisionner'; cls = 'p-warn'; }
      if (st) st.innerHTML = '<span class="pill '+cls+'">'+label+'</span>';
      const ac = btn.closest('.ac');
      if (ac) ac.innerHTML = '<span style="font-size:.82rem;color:'+(positif?'#1a7a40':'#a32d2d')+';font-weight:600;">Traité ✓</span>';
      if (!positif && row) row.style.opacity = '0.5';
      // Message d'information eventuel (ex : reapprovisionnement)
      if (j.message) alert(j.message);
    } else {
      alert('Erreur : ' + (j.message || 'action impossible'));
    }
  } catch(e) { alert('Erreur serveur.'); }
}

// Enregistrer les paramètres
async function saveParams(btn) {
  const params = {};
  document.querySelectorAll('[data-param]').forEach(el => {
    if (el.classList.contains('toggle')) params[el.dataset.param] = el.classList.contains('on') ? '1' : '0';
    else params[el.dataset.param] = el.value;
  });
  const fd = new FormData();
  Object.entries(params).forEach(([k,v]) => fd.append('params['+k+']', v));
  try {
    const res = await fetch('actions/parametres.php', {method:'POST', body:fd});
    const j = await res.json();
    btn.innerHTML = j.success ? '✓ Paramètres enregistrés' : '✗ Erreur';
  } catch(e) { btn.innerHTML = '✗ Erreur serveur'; }
}

// Gestion des comptes (suspendre / reactiver / supprimer)
async function compte(id, act, btn) {
  const messages = {
    suspendre: 'Suspendre ce compte ? L\'utilisateur ne pourra plus se connecter.',
    reactiver: 'Réactiver ce compte ?',
    supprimer: 'Supprimer ce compte définitivement ? (S\'il a un historique, il sera suspendu à la place.)'
  };
  if (!confirm(messages[act])) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('action', act);
  try {
    const res = await fetch('actions/compte.php', {method:'POST', body:fd});
    const j = await res.json();
    if (j.success) {
      const row = btn.closest('tr');
      if (j.supprime) {
        // Ligne supprimee : on la retire du tableau
        row.style.transition = 'opacity 0.3s';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 300);
      } else {
        // Statut change : mettre a jour l'affichage
        const st = row.querySelector('.st-cell');
        const actif = j.nouveau_statut === 'actif';
        if (st) st.innerHTML = '<span class="pill '+(actif?'p-ok':'p-bad')+'">'+(actif?'Actif':'Suspendu')+'</span>';
        const ac = row.querySelector('.ac');
        if (ac) {
          ac.innerHTML = '<div style="display:flex;gap:6px;">' +
            (actif
              ? '<button class="btn btn-bad" onclick="compte('+id+',\'suspendre\',this)" title="Suspendre"><i class="ti ti-ban"></i></button>'
              : '<button class="btn btn-ok" onclick="compte('+id+',\'reactiver\',this)" title="Réactiver"><i class="ti ti-check"></i></button>') +
            '<button class="btn btn-bad" onclick="compte('+id+',\'supprimer\',this)" title="Supprimer"><i class="ti ti-trash"></i></button></div>';
        }
        if (j.message) alert(j.message);
      }
    } else {
      alert('Erreur : ' + (j.message || 'action impossible'));
    }
  } catch(e) { alert('Erreur serveur.'); }
}

// Assigner un PRA de rattachement a une pharmacie
async function assignerPra(btn) {
  const row = btn.closest('tr');
  const sel = row.querySelector('.sel-pra');
  const structureId = sel.dataset.sid;
  const praId = sel.value;
  if (!praId) { alert('Choisissez un PRA avant d\'enregistrer.'); return; }

  const fd = new FormData();
  fd.append('structure_id', structureId);
  fd.append('pra_id', praId);

  btn.disabled = true;
  try {
    const res = await fetch('actions/assigner_pra.php', {method:'POST', body:fd});
    const j = await res.json();
    if (j.success) {
      const badge = row.querySelector('.p-warn');
      if (badge) badge.remove();
      btn.innerHTML = '<i class="ti ti-check" style="color:var(--green);"></i>';
      setTimeout(() => { btn.innerHTML = '<i class="ti ti-device-floppy"></i>'; btn.disabled = false; }, 1200);
    } else {
      alert('Erreur : ' + (j.message || 'assignation impossible'));
      btn.disabled = false;
    }
  } catch(e) { alert('Erreur serveur.'); btn.disabled = false; }
}

// Emettre un avertissement avant suspension/suppression
async function avertir(id, type, nom, btn){
  const libelle = type === 'suspension' ? 'suspension' : 'suppression';
  const motif = prompt('Avertissement avant '+libelle+' de « '+nom+' ».\n\nVeuillez saisir le motif (obligatoire). Un email officiel sera envoyé et le délai légal démarrera :');
  if(motif === null) return;           // annule
  if(motif.trim() === ''){ alert('Le motif est obligatoire.'); return; }
  const fd = new FormData();
  fd.append('id', id); fd.append('type', type); fd.append('motif', motif.trim());
  try{
    const res = await fetch('actions/avertir.php', {method:'POST', body:fd});
    const j = await res.json();
    if(j.success){
      alert('Avertissement envoyé.\n'+(j.email_envoye?'Un email a été adressé à l\'utilisateur.':'(Email non envoyé : vérifiez la configuration.)')+'\nLa '+libelle+' sera possible à partir du '+j.applicable_le+'.');
      location.reload();
    } else {
      alert('Erreur : '+(j.message||'action impossible'));
    }
  }catch(e){ alert('Erreur serveur.'); }
}
