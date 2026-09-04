<!-- ============================================================
     WIDGET CARTE — geolocalisation des structures (Leaflet)
     A inclure dans une section des dashboards.
     Necessite : role courant accessible cote JS via window.LPS_ROLE
     ============================================================ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.map-wrap{display:flex;flex-direction:column;gap:0;}
#lpsMap{height:460px;width:100%;border-radius:0 0 15px 15px;z-index:1;}
.map-legend{display:flex;flex-wrap:wrap;gap:14px;padding:14px 20px;border-bottom:1px solid #eef7f1;background:#fafdfb;}
.lg-item{display:flex;align-items:center;gap:7px;font-size:0.82rem;color:var(--body);font-weight:500;}
.lg-dot{width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,0.15);}
.lg-moi{background:#1faa4e;}
.lg-mon_pra{background:#1a5fa5;}
.lg-ma_pharmacie{background:#d68910;}
.lg-autre_pra{background:#5b4cc4;}
.lg-autre_pharmacie{background:#9aa0a6;}
.map-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:13px 20px;border-bottom:1px solid #eef7f1;}
.map-toolbar .hint{margin:0;}
.leaflet-popup-content{font-family:'Inter',sans-serif;font-size:0.85rem;line-height:1.5;}
.popup-tel{color:#1a5fa5;font-weight:600;}
.popup-type{display:inline-block;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;padding:2px 8px;border-radius:10px;background:#eafaf0;color:#178a3e;margin-bottom:4px;}
</style>

<div class="map-wrap">
  <div class="map-toolbar">
    <button class="btn btn-pr" id="btnMeLocaliser" onclick="meLocaliser()"><i class="ti ti-current-location"></i> Me localiser automatiquement</button>
    <button class="btn" id="btnDefinirPos" onclick="activerDefinitionPosition()"><i class="ti ti-map-pin"></i> Placer manuellement</button>
    <span class="hint" id="mapPosHint">Cliquez sur « Me localiser » pour enregistrer automatiquement l'emplacement de votre structure.</span>
  </div>
  <div class="map-legend" id="mapLegend"></div>
  <div id="lpsMap"></div>
</div>

<script>
(function(){
  let map, markers = [], modeDefinition = false, monMarqueur = null;

  // Couleurs par categorie
  const COULEURS = {
    moi:            '#1faa4e',
    mon_pra:        '#1a5fa5',
    ma_pharmacie:   '#d68910',
    autre_pra:      '#5b4cc4',
    autre_pharmacie:'#9aa0a6'
  };
  const LABELS = {
    moi:'Ma structure', mon_pra:'Mon PRA', ma_pharmacie:'Mes pharmacies',
    autre_pra:'Autres PRA', autre_pharmacie:'Autres pharmacies'
  };

  function iconePour(cat){
    const c = COULEURS[cat] || '#9aa0a6';
    return L.divIcon({
      className:'',
      html:'<div style="width:22px;height:22px;border-radius:50%;background:'+c+';border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.4);"></div>',
      iconSize:[22,22], iconAnchor:[11,11], popupAnchor:[0,-12]
    });
  }

  function construireLegende(cats){
    const lg = document.getElementById('mapLegend');
    let html = '';
    Object.keys(LABELS).forEach(function(cat){
      if(cats.has(cat)){
        html += '<div class="lg-item"><span class="lg-dot lg-'+cat+'"></span>'+LABELS[cat]+'</div>';
      }
    });
    lg.innerHTML = html || '<span class="hint" style="margin:0;">Aucune structure géolocalisée pour le moment.</span>';
  }

  function initMap(){
    // Centre par defaut : Senegal
    map = L.map('lpsMap').setView([14.5, -15.5], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'© OpenStreetMap', maxZoom:18
    }).addTo(map);

    // Clic sur la carte = definir sa position (si mode actif)
    map.on('click', function(e){
      if(!modeDefinition) return;
      enregistrerPosition(e.latlng.lat, e.latlng.lng);
    });

    chargerPoints();
  }

  function chargerPoints(){
    fetch('actions/carte_points.php')
      .then(r=>r.json())
      .then(function(d){
        markers.forEach(m=>map.removeLayer(m)); markers=[];
        const cats = new Set();
        const bounds = [];
        (d.points||[]).forEach(function(p){
          cats.add(p.categorie);
          const m = L.marker([p.lat,p.lng],{icon:iconePour(p.categorie)}).addTo(map);
          const typeLabel = p.type==='pra'?'PRA':(p.type==='pharmacie'?'Pharmacie':'Structure');
          m.bindPopup(
            '<span class="popup-type">'+typeLabel+'</span><br>'+
            '<strong>'+p.nom+'</strong><br>'+
            (p.region?('Région : '+p.region+'<br>'):'')+
            'Tél : <span class="popup-tel">'+p.telephone+'</span>'
          );
          markers.push(m);
          bounds.push([p.lat,p.lng]);
          if(p.categorie==='moi') monMarqueur = m;
        });
        construireLegende(cats);
        if(bounds.length) map.fitBounds(bounds,{padding:[40,40],maxZoom:12});
      })
      .catch(function(){ document.getElementById('mapLegend').innerHTML='<span class="hint" style="margin:0;">Impossible de charger les points.</span>'; });
  }

  window.activerDefinitionPosition = function(){
    modeDefinition = true;
    document.getElementById('mapPosHint').innerHTML = '<strong style="color:#1faa4e;">Cliquez maintenant sur la carte à l\'emplacement de votre structure.</strong>';
    document.getElementById('lpsMap').style.cursor = 'crosshair';
  };

  // Geolocalisation automatique via le navigateur
  window.meLocaliser = function(){
    const hint = document.getElementById('mapPosHint');
    const btn = document.getElementById('btnMeLocaliser');
    if(!navigator.geolocation){
      hint.innerHTML = '<strong style="color:#c0392b;">Votre navigateur ne supporte pas la géolocalisation. Utilisez « Placer manuellement ».</strong>';
      return;
    }
    btn.disabled = true;
    hint.innerHTML = 'Recherche de votre position en cours…';
    navigator.geolocation.getCurrentPosition(
      function(pos){
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        if(map){ map.setView([lat,lng], 14); }
        enregistrerPosition(lat, lng);
        btn.disabled = false;
      },
      function(err){
        btn.disabled = false;
        let msg = "Impossible de vous localiser.";
        if(err.code === 1) msg = "Vous avez refusé la géolocalisation. Autorisez-la ou utilisez « Placer manuellement ».";
        else if(err.code === 2) msg = "Position indisponible. Vérifiez votre connexion ou utilisez « Placer manuellement ».";
        else if(err.code === 3) msg = "La localisation a pris trop de temps. Réessayez ou placez manuellement.";
        hint.innerHTML = '<strong style="color:#c0392b;">'+msg+'</strong>';
      },
      { enableHighAccuracy:true, timeout:10000, maximumAge:0 }
    );
  };

  function enregistrerPosition(lat, lng){
    const fd = new FormData();
    fd.append('latitude', lat); fd.append('longitude', lng);
    fetch('actions/localisation.php',{method:'POST',body:fd})
      .then(r=>r.json())
      .then(function(j){
        const hint = document.getElementById('mapPosHint');
        if(j.success){
          hint.innerHTML = '<strong style="color:#1faa4e;">✓ Position enregistrée.</strong>';
          modeDefinition = false;
          document.getElementById('lpsMap').style.cursor = '';
          chargerPoints();
        } else {
          hint.innerHTML = '<strong style="color:#c0392b;">'+(j.message||'Erreur')+'</strong>';
        }
      })
      .catch(function(){ document.getElementById('mapPosHint').innerHTML='<strong style="color:#c0392b;">Erreur serveur.</strong>'; });
  }

  // Initialiser quand la section devient visible (la carte a besoin d'etre visible pour s'afficher)
  window.initLpsMapWhenVisible = function(){
    if(map){ setTimeout(()=>map.invalidateSize(), 100); return; }
    initMap();
  };
})();
</script>
