<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/helpers.php';
exigerRole('etat');
// Securite : si helpers.php n'est pas a jour, on definit la fonction ici
if (!function_exists('strftime_fr')) {
    function strftime_fr() {
        $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $mois  = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        return $jours[(int)date('w')] . ' ' . (int)date('j') . ' ' . $mois[(int)date('n')] . ' ' . date('Y');
    }
}
// Animation d'accueil : une seule fois par session
$montrerAccueil = empty($_SESSION['accueil_etat_vu']);
if ($montrerAccueil) { $_SESSION['accueil_etat_vu'] = true; }

$nbMeds = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$nbPRA  = $pdo->query("SELECT COUNT(*) FROM structures WHERE type='pra' AND statut='active'")->fetchColumn();
$nbAlertesCrit = $pdo->query("SELECT COUNT(*) FROM alertes WHERE priorite='critique' AND lue=0")->fetchColumn();
$nbReeq = $pdo->query("SELECT COUNT(*) FROM reequilibrages WHERE statut='en_attente'")->fetchColumn();
$nbCmd  = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut='en_attente'")->fetchColumn();
$nbSub  = $pdo->query("SELECT COUNT(*) FROM subventions WHERE statut='en_attente'")->fetchColumn();
$nbDem  = $pdo->query("SELECT COUNT(*) FROM demandes_acces WHERE statut='en_attente'")->fetchColumn();
// Demandes de reinitialisation exceptionnelle en attente
$nbReset = 0;
try { $nbReset = $pdo->query("SELECT COUNT(*) FROM demandes_reset WHERE statut='en_attente'")->fetchColumn(); } catch (Exception $e) {}

$regions = $pdo->query("
    SELECT st.id, st.nom, st.region,
           COALESCE(SUM(s.quantite),0) AS stock, COALESCE(SUM(m.seuil_alerte),1) AS seuil
    FROM structures st
    LEFT JOIN stocks s ON s.structure_id=st.id
    LEFT JOIN medicaments m ON s.medicament_id=m.id
    WHERE st.type='pra' GROUP BY st.id
    ORDER BY (COALESCE(SUM(s.quantite),0)/COALESCE(SUM(m.seuil_alerte),1)) ASC
")->fetchAll();

// ---- Moteur d'analyse (IA) : donnees reelles pour le tableau de bord ----
require_once '../config/moteur_ia.php';
$iaRuptures       = predireRuptures($pdo);
$iaReequilibrages = suggererReequilibrages($pdo);
$iaDesequilibre   = detecterDesequilibres($pdo);
synchroniserReequilibragesIA($pdo, $iaReequilibrages);
$nbReeq = $pdo->query("SELECT COUNT(*) FROM reequilibrages WHERE statut='en_attente'")->fetchColumn();

// Couverture nationale = part des regions dont le stock est >= seuil (100%)
$nbRegionsOk = 0;
foreach ($regions as $r) { if ($r['seuil'] > 0 && ($r['stock']/$r['seuil']) >= 1) $nbRegionsOk++; }
$couverture = count($regions) ? round($nbRegionsOk / count($regions) * 100) : 0;

// Donnees du graphique "niveau de stock par region" (plafonne a 100 pour l'affichage des barres)
$graphRegions = [];
foreach ($regions as $r) {
    $pct = $r['seuil'] > 0 ? round($r['stock']/$r['seuil']*100) : 0;
    $graphRegions[] = ['nom'=>$r['region'] ?: $r['nom'], 'pct'=>$pct, 'pct_barre'=>min($pct,100)];
}

$commandes = $pdo->query("
    SELECT c.*, st.nom AS struct_nom,
           GROUP_CONCAT(CONCAT(m.nom,' ',m.dosage) SEPARATOR ', ') AS meds,
           SUM(l.quantite_demandee) AS qte
    FROM commandes c JOIN utilisateurs u ON c.demandeur_id=u.id
    JOIN structures st ON u.structure_id=st.id
    LEFT JOIN lignes_commande l ON l.commande_id=c.id
    LEFT JOIN medicaments m ON l.medicament_id=m.id
    GROUP BY c.id ORDER BY c.date_commande DESC LIMIT 50
")->fetchAll();

$subventions = $pdo->query("
    SELECT sub.*, ph.nom AS pharma_nom, prast.nom AS pra_nom
    FROM subventions sub JOIN structures ph ON sub.pharmacie_id=ph.id
    JOIN utilisateurs u ON sub.signale_par=u.id
    LEFT JOIN structures prast ON u.structure_id=prast.id
    ORDER BY FIELD(sub.statut,'en_attente','approuvee','rejetee'), sub.created_at DESC
")->fetchAll();

$reequilibrages = $pdo->query("
    SELECT r.*, m.nom AS med_nom, m.dosage, src.nom AS source_nom, dst.nom AS dest_nom
    FROM reequilibrages r JOIN medicaments m ON r.medicament_id=m.id
    LEFT JOIN structures src ON r.source_id=src.id JOIN structures dst ON r.destination_id=dst.id
    ORDER BY FIELD(r.statut,'en_attente','validee','rejetee'), r.created_at DESC
")->fetchAll();

$demandes = $pdo->query("SELECT * FROM demandes_acces WHERE statut='en_attente' ORDER BY created_at DESC")->fetchAll();
// Demandes de reinitialisation exceptionnelle
$demandesReset = [];
try {
    $demandesReset = $pdo->query("
        SELECT dr.*, u.prenom, u.nom, u.role, s.nom AS struct_nom
        FROM demandes_reset dr
        JOIN utilisateurs u ON dr.utilisateur_id=u.id
        LEFT JOIN structures s ON u.structure_id=s.id
        WHERE dr.statut='en_attente' ORDER BY dr.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}
$comptes = $pdo->query("SELECT u.*, s.id AS struct_id, s.type AS struct_type, s.nom AS struct_nom, s.telephone AS struct_tel, s.pra_parent AS struct_pra_parent FROM utilisateurs u LEFT JOIN structures s ON u.structure_id=s.id ORDER BY u.role,u.nom")->fetchAll();
$listePRA = $pdo->query("SELECT id, nom FROM structures WHERE type='pra' AND statut='active' ORDER BY nom")->fetchAll();
// Avertissements actifs (non annules), indexes par utilisateur et type
$avertParUser = [];
try {
    foreach ($pdo->query("SELECT * FROM avertissements WHERE annule=0 ORDER BY created_at DESC") as $a) {
        $avertParUser[$a['utilisateur_id']][$a['type']] = $a;
    }
} catch (Exception $e) {}
$fournisseurs = $pdo->query("SELECT * FROM fournisseurs ORDER BY nom")->fetchAll();
$alertes = $pdo->query("
    SELECT a.*, m.nom AS med_nom, st.nom AS struct_nom FROM alertes a
    LEFT JOIN stocks s ON a.stock_id=s.id LEFT JOIN medicaments m ON s.medicament_id=m.id
    LEFT JOIN structures st ON s.structure_id=st.id
    WHERE a.lue=0 ORDER BY FIELD(a.priorite,'critique','alerte','info'), a.created_at DESC
")->fetchAll();

$P = [];
foreach ($pdo->query("SELECT cle,valeur FROM parametres") as $r) $P[$r['cle']]=$r['valeur'];

$conso = $pdo->query("
    SELECT m.categorie, COALESCE(SUM(l.quantite_demandee),0) AS total FROM medicaments m
    LEFT JOIN lignes_commande l ON l.medicament_id=m.id GROUP BY m.categorie
")->fetchAll();
$catLabels=json_encode(array_column($conso,'categorie'));
$catData=json_encode(array_map('intval',array_column($conso,'total')));

$totalReg=count($regions); $okReg=0;
foreach($regions as $r){ if(pctStock($r['stock'],$r['seuil'])>=50) $okReg++; }
$equite=$totalReg?round($okReg/$totalReg*100):0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Pharmacie Sénégalaise — État</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<?php if($montrerAccueil) include __DIR__.'/welcome_etat.php'; ?>
<div class="app">
  <aside class="sb">
    <div class="sb-logo"><div class="sb-logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#1faa4e"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#1faa4e"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#1faa4e"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#1faa4e"/><circle cx="50" cy="18" r="7" fill="#1faa4e"/></svg></div><div class="sb-name">La Pharmacie<br>Sénégalaise</div></div>
    <div class="sb-user"><div class="sb-av"><?= htmlspecialchars(initiales()) ?></div><div><div class="sb-un"><?= htmlspecialchars(nomUtilisateur()) ?></div><div class="sb-ur">Super administrateur</div></div></div>
    <nav class="sb-nav">
      <div class="sb-sec">Principal</div>
      <a class="sb-item active" onclick="nav('dashboard',this)" href="#"><i class="ti ti-dashboard"></i>Tableau de bord</a>
      <a class="sb-item" onclick="nav('alertes',this)" href="#"><i class="ti ti-bell-ringing"></i>Alertes<?php if($nbAlertesCrit):?><span class="sb-badge"><?= $nbAlertesCrit ?></span><?php endif;?></a>
      <div class="sb-sec">Distribution</div>
      <a class="sb-item" onclick="nav('reequilibrage',this)" href="#"><i class="ti ti-arrows-exchange"></i>Rééquilibrage<?php if($nbReeq):?><span class="sb-badge-w"><?= $nbReeq ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('subventions',this)" href="#"><i class="ti ti-cash"></i>Subventions<?php if($nbSub):?><span class="sb-badge-w"><?= $nbSub ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('validation',this)" href="#"><i class="ti ti-checklist"></i>Suivi commandes</a>
      <a class="sb-item" onclick="nav('regions',this)" href="#"><i class="ti ti-map-pin"></i>Régions</a>
      <div class="sb-sec">Gestion</div>
      <a class="sb-item" onclick="nav('comptes',this)" href="#"><i class="ti ti-users"></i>Comptes<?php if($nbDem):?><span class="sb-badge"><?= $nbDem ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('resets',this)" href="#"><i class="ti ti-key"></i>Réinitialisations<?php if($nbReset):?><span class="sb-badge-w"><?= $nbReset ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('fournisseurs',this)" href="#"><i class="ti ti-truck-delivery"></i>Fournisseurs</a>
      <div class="sb-sec">Intelligence</div>
      <a class="sb-item" onclick="nav('stats',this)" href="#"><i class="ti ti-chart-line"></i>Statistiques IA</a>
      <a class="sb-item" onclick="nav('carte',this);initLpsMapWhenVisible();" href="#"><i class="ti ti-map-2"></i>Carte nationale</a>
      <div class="sb-sec">Système</div>
      <a class="sb-item" onclick="nav('parametres',this)" href="#"><i class="ti ti-settings"></i>Paramètres</a>
    </nav>
    <div class="sb-bot"><a class="sb-logout" href="../auth/logout.php"><i class="ti ti-logout"></i>Déconnexion</a></div>
  </aside>
  <div class="main">
    <div class="top"><div class="top-title" id="topTitle">Tableau de bord national</div>
    <div class="top-r"><div class="top-date"><i class="ti ti-calendar-event"></i><?= function_exists('strftime_fr') ? strftime_fr() : date('d/m/Y') ?></div><div class="bell"><i class="ti ti-bell"></i><?php if($nbAlertesCrit):?><div class="bell-dot"></div><?php endif;?></div></div></div>
    <div class="content">

      <div id="dashboard" class="section active">
        <div class="stats">
          <div class="stat stat-hero">
            <div class="stat-top"><span class="stat-lbl">Médicaments suivis</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= nb($nbMeds) ?></div>
            <div class="stat-pill stat-pill-hero"><i class="ti ti-trending-up"></i>Suivi en continu</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Alertes critiques</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--danger);"><?= count($iaRuptures) ?></div>
            <div class="stat-pill" style="background:var(--danger-bg);color:#a32d2d;"><i class="ti ti-alert-triangle"></i><?= count($iaRuptures) ? 'Action requise' : 'Aucun risque' ?></div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Rééquilibrages</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= count($iaReequilibrages) ?></div>
            <div class="stat-pill" style="background:var(--purple-bg);color:#4d3fa8;"><i class="ti ti-sparkles"></i>Suggérés par l'IA</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Équité inter-régions</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--warn);"><?= $equite ?>%</div>
            <div class="stat-pill" style="background:var(--warn-bg);color:#9a6a0a;"><i class="ti ti-scale"></i>Écart ville / rural</div>
          </div>
        </div>

        <div class="two">
          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-chart-bar"></i>Niveau de stock par région</div></div>
            <div class="cp">
              <?php if(!$graphRegions): ?><div class="empty">Aucune région enregistrée</div><?php else: ?>
              <div class="bar-chart">
                <?php foreach($graphRegions as $g): $coul = $g['pct']>=80?'var(--green)':($g['pct']>=40?'var(--warn)':'var(--danger)'); ?>
                  <div class="bar-col">
                    <div class="bar-val"><?= $g['pct'] ?>%</div>
                    <div class="bar-track"><div class="bar-fill" style="height:<?= max($g['pct_barre'],4) ?>%;background:<?= $coul ?>;"></div></div>
                    <div class="bar-lbl"><?= htmlspecialchars($g['nom']) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-disc"></i>Couverture nationale</div></div>
            <div class="cp" style="display:flex;flex-direction:column;align-items:center;">
              <svg viewBox="0 0 120 120" style="width:150px;height:150px;">
                <circle cx="60" cy="60" r="48" fill="none" stroke="var(--green-border)" stroke-width="15" stroke-linecap="round"
                        stroke-dasharray="<?= round(3.02*(100-$couverture)) ?> 302" transform="rotate(135 60 60)"/>
                <circle cx="60" cy="60" r="48" fill="none" stroke="var(--green)" stroke-width="15" stroke-linecap="round"
                        stroke-dasharray="<?= round(3.02*$couverture) ?> 302" transform="rotate(135 60 60)"/>
                <text x="60" y="58" text-anchor="middle" font-size="24" font-weight="700" fill="var(--head)"><?= $couverture ?>%</text>
                <text x="60" y="75" text-anchor="middle" font-size="9" fill="var(--muted)">régions couvertes</text>
              </svg>
              <div style="display:flex;gap:16px;margin-top:8px;">
                <span class="legend-dot" style="--c:var(--green);">Suffisant</span>
                <span class="legend-dot" style="--c:var(--green-border);">Déficit</span>
              </div>
            </div>
          </div>
        </div>

        <div class="two">
          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-arrows-exchange"></i>Rééquilibrages suggérés par l'IA</div><span class="pill p-ia"><?= count($iaReequilibrages) ?></span></div>
            <?php if(!$iaReequilibrages):?><div class="empty">Aucun déséquilibre détecté actuellement</div><?php endif;?>
            <?php foreach(array_slice($iaReequilibrages,0,5) as $r): $ic = $r['priorite']==='critique'?'aic-r':'aic-p'; ?>
              <div class="alert"><div class="aic <?= $ic ?>"><i class="ti ti-arrows-exchange"></i></div>
              <div style="flex:1;"><div class="at"><?= htmlspecialchars($r['medicament']) ?></div>
              <div class="ad"><?= htmlspecialchars($r['source']) ?> (<?= $r['source_pct'] ?>%) → <?= htmlspecialchars($r['destination']) ?> (<?= $r['dest_pct'] ?>%)</div></div>
              <div class="atm"><?= nb($r['quantite']) ?> u.</div></div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-alert-triangle"></i>Risques de rupture</div><span class="pill p-bad"><?= count($iaRuptures) ?></span></div>
            <?php if(!$iaRuptures):?><div class="empty">Aucune rupture imminente</div><?php endif;?>
            <?php foreach(array_slice($iaRuptures,0,5) as $r): $ic = $r['niveau']==='critique'?'aic-r':($r['niveau']==='alerte'?'aic-a':'aic-g'); ?>
              <div class="alert"><div class="aic <?= $ic ?>"><i class="ti ti-pill"></i></div>
              <div style="flex:1;"><div class="at"><?= htmlspecialchars($r['medicament']) ?></div>
              <div class="ad"><?= htmlspecialchars($r['structure']) ?> · stock <?= $r['quantite'] ?><?= $r['jours_restants']!==null ? ' · '.$r['jours_restants'].' j restants' : '' ?></div></div>
              <div class="atm"><?= ucfirst($r['niveau']) ?></div></div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if($iaDesequilibre['desequilibre']): $de=$iaDesequilibre['desequilibre']; ?>
        <div class="ia-box">
          <div class="ia-ic"><i class="ti ti-map-2"></i></div>
          <div><div class="ia-t">Déséquilibre territorial détecté</div>
          <div class="ia-d"><?= htmlspecialchars($de['constat']) ?>. Niveau moyen en ville : <strong><?= $de['niveau_ville'] ?>%</strong>, en zone rurale : <strong><?= $de['niveau_rural'] ?>%</strong> (écart <?= $de['ecart'] ?> points, <?= htmlspecialchars($de['gravite']) ?>). Un rééquilibrage vers les zones rurales est recommandé.</div></div>
        </div>
        <?php endif; ?>
      </div>

      <div id="alertes" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-bell-ringing"></i>Toutes les alertes</div><span class="pill p-bad"><?= count($alertes) ?> actives</span></div>
        <?php if(!$alertes):?><div class="empty">Aucune alerte active</div><?php endif;?>
        <?php foreach($alertes as $a): $ic=$a['priorite']==='critique'?'aic-r':($a['priorite']==='alerte'?'aic-a':'aic-g'); ?>
          <div class="alert"><div class="aic <?= $ic ?>"><i class="ti ti-alert-triangle"></i></div>
          <div style="flex:1;"><div class="at"><?= htmlspecialchars($a['message']) ?></div><div class="ad"><?= htmlspecialchars(trim(($a['med_nom']??'').' '.($a['struct_nom']?'· '.$a['struct_nom']:''))) ?></div></div>
          <div class="atm"><?= date('d/m H:i',strtotime($a['created_at'])) ?></div></div>
        <?php endforeach;?>
      </div></div>

      <div id="reequilibrage" class="section">
        <div class="ia-box"><div class="ia-ic"><i class="ti ti-brain"></i></div><div><div class="ia-t">Rééquilibrage intelligent</div><div class="ia-d">Transferts entre régions pour qu'<strong>aucune pharmacie ne soit défavorisée</strong>.</div></div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-arrows-exchange"></i>Transferts proposés</div></div>
          <table><thead><tr><th>Médicament</th><th>Source</th><th>Destination</th><th>Qté</th><th>Origine</th><th>Priorité</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
          <?php if(!$reequilibrages):?><tr><td colspan="8"><div class="empty">Aucun rééquilibrage</div></td></tr><?php endif;?>
          <?php foreach($reequilibrages as $r): $pp=$r['priorite']==='critique'?'p-bad':($r['priorite']==='moderee'?'p-warn':'p-info'); $sp=$r['statut']==='en_attente'?'p-warn':($r['statut']==='validee'?'p-ok':'p-bad'); ?>
            <tr><td><strong><?= htmlspecialchars($r['med_nom'].' '.$r['dosage']) ?></strong></td>
            <td><?= htmlspecialchars($r['source_nom']??'—') ?></td><td><?= htmlspecialchars($r['dest_nom']) ?></td><td><?= nb($r['quantite']) ?></td>
            <td><span class="pill <?= $r['origine']==='ia'?'p-ia':'p-gray' ?>"><?= strtoupper($r['origine']) ?></span></td>
            <td><span class="pill <?= $pp ?>"><?= ucfirst($r['priorite']) ?></span></td>
            <td class="st-cell"><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$r['statut'])) ?></span></td>
            <td class="ac"><?php if($r['statut']==='en_attente'):?><div style="display:flex;gap:6px;"><button class="btn btn-ok" onclick="action('actions/reequilibrage.php',<?= $r['id'] ?>,'approuver',this)">✓</button><button class="btn btn-bad" onclick="action('actions/reequilibrage.php',<?= $r['id'] ?>,'rejeter',this)">✗</button></div><?php else:?><span style="font-size:.82rem;color:var(--muted);">Traité</span><?php endif;?></td></tr>
          <?php endforeach;?></tbody></table>
        </div>
      </div>

      <div id="subventions" class="section">
        <div class="ia-box" style="background:linear-gradient(135deg,#eafaf0,#f7fdf9);border-color:#b8ddc4;"><div class="ia-ic" style="background:var(--green);"><i class="ti ti-cash"></i></div><div><div class="ia-t" style="color:var(--green-deep);">Subventions de l'État</div><div class="ia-d" style="color:var(--green-d);">Un PRA signale une pharmacie en difficulté, l'État <strong>subventionne</strong>.</div></div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-flag"></i>Demandes de subvention</div></div>
          <table><thead><tr><th>Pharmacie</th><th>Signalé par</th><th>Médicaments</th><th>Montant</th><th>Motif</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
          <?php if(!$subventions):?><tr><td colspan="7"><div class="empty">Aucune demande</div></td></tr><?php endif;?>
          <?php foreach($subventions as $s): $sp=$s['statut']==='en_attente'?'p-warn':($s['statut']==='approuvee'?'p-ok':'p-bad'); ?>
            <tr><td><strong><?= htmlspecialchars($s['pharma_nom']) ?></strong></td><td><?= htmlspecialchars($s['pra_nom']??'PRA') ?></td>
            <td><?= htmlspecialchars($s['medicaments']) ?></td><td><?= nb($s['montant_estime']) ?> FCFA</td>
            <td style="max-width:220px;font-size:0.84rem;color:var(--mid);"><?= $s['motif'] ? htmlspecialchars($s['motif']) : '<span style="color:var(--muted);">—</span>' ?></td>
            <td class="st-cell"><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$s['statut'])) ?></span></td>
            <td class="ac"><?php if($s['statut']==='en_attente'):?><div style="display:flex;gap:6px;"><button class="btn btn-ok" onclick="action('actions/subvention.php',<?= $s['id'] ?>,'subventionner',this)">✓ Subventionner</button><button class="btn btn-bad" onclick="action('actions/subvention.php',<?= $s['id'] ?>,'rejeter',this)">✗</button></div><?php else:?><span style="font-size:.82rem;color:var(--muted);">Traité</span><?php endif;?></td></tr>
          <?php endforeach;?></tbody></table>
        </div>
      </div>

      <div id="validation" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-checklist"></i>Suivi des commandes</div><span class="pill p-info">Lecture seule — traçabilité</span></div>
        <div class="cp" style="padding-bottom:0;"><p style="font-size:0.85rem;color:var(--muted);margin-bottom:4px;">L'État assure le suivi des commandes à des fins de traçabilité. La validation relève du PRA et du fournisseur.</p></div>
        <table><thead><tr><th>Réf.</th><th>Demandeur</th><th>Médicament</th><th>Qté</th><th>Urgence</th><th>Date</th><th>Statut</th></tr></thead><tbody>
        <?php if(!$commandes):?><tr><td colspan="7"><div class="empty">Aucune commande enregistrée</div></td></tr><?php endif;?>
        <?php foreach($commandes as $c):
          $up=$c['urgence']==='critique'?'p-bad':($c['urgence']==='alerte'?'p-warn':'p-info');
          $sp=$c['statut']==='livree'?'p-ok':($c['statut']==='en_attente'?'p-warn':($c['statut']==='rejetee'?'p-bad':'p-info')); ?>
          <tr><td><strong><?= htmlspecialchars($c['reference']) ?></strong></td><td><?= htmlspecialchars($c['struct_nom']) ?></td>
          <td><?= htmlspecialchars($c['meds']??'—') ?></td><td><?= nb($c['qte']) ?></td>
          <td><span class="pill <?= $up ?>"><?= ucfirst($c['urgence']) ?></span></td>
          <td><?= date('d/m/Y',strtotime($c['date_commande'])) ?></td>
          <td><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="regions" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-map-pin"></i>État des stocks par région</div></div>
        <table><thead><tr><th>PRA</th><th>Région</th><th>Niveau de stock</th><th>Statut</th></tr></thead><tbody>
        <?php foreach($regions as $r): $pct=pctStock($r['stock'],$r['seuil']); [$pc,$pl]=pillNiveau($pct); ?>
          <tr><td><strong><?= htmlspecialchars($r['nom']) ?></strong></td><td><?= htmlspecialchars($r['region']) ?></td>
          <td><div class="prog"><div class="pbar"><div class="pfill <?= classeNiveau($pct) ?>" style="width:<?= min($pct,100) ?>%"></div></div><span class="plbl"><?= $pct ?>%</span></div></td>
          <td><span class="pill <?= $pc ?>"><?= $pl ?></span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="comptes" class="section">
        <div class="card" style="margin-bottom:16px;"><div class="ch"><div class="ct"><i class="ti ti-user-plus"></i>Demandes d'accès</div><span class="pill p-warn"><?= count($demandes) ?> en attente</span></div>
          <table><thead><tr><th>Nom</th><th>Structure</th><th>Rôle</th><th>Actions</th></tr></thead><tbody>
          <?php if(!$demandes):?><tr><td colspan="4"><div class="empty">Aucune demande</div></td></tr><?php endif;?>
          <?php foreach($demandes as $d):?>
            <tr><td><strong><?= htmlspecialchars($d['prenom'].' '.$d['nom']) ?></strong></td><td><?= htmlspecialchars($d['structure_nom']) ?></td>
            <td><span class="pill p-info"><?= ucfirst($d['role_demande']) ?></span></td>
            <td class="ac"><div style="display:flex;gap:6px;"><button class="btn btn-ok" onclick="action('actions/demande.php',<?= $d['id'] ?>,'approuver',this)">✓ Valider</button><button class="btn btn-bad" onclick="action('actions/demande.php',<?= $d['id'] ?>,'rejeter',this)">✗</button></div></td></tr>
          <?php endforeach;?></tbody></table>
        </div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-users"></i>Comptes actifs</div></div>
          <table><thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Structure</th><th>Rattachement PRA</th><th>Contact</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
          <?php foreach($comptes as $u):?>
            <tr><td><strong><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></strong></td><td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="pill p-gray"><?= ucfirst($u['role']) ?></span></td><td><?= htmlspecialchars($u['struct_nom']??'—') ?></td>
            <td>
            <?php if($u['struct_type']==='pharmacie'):
              $praActuel = null;
              foreach($listePRA as $pr){ if($pr['id']==$u['struct_pra_parent']){ $praActuel=$pr['nom']; break; } }
            ?>
              <div style="display:flex;align-items:center;gap:6px;">
                <select class="sel-pra" data-sid="<?= $u['struct_id'] ?>" style="font-size:.8rem;padding:4px 6px;border-radius:8px;border:1px solid var(--green-border);">
                  <option value="">— Non assigné —</option>
                  <?php foreach($listePRA as $pr): ?>
                    <option value="<?= $pr['id'] ?>"<?= $pr['id']==$u['struct_pra_parent']?' selected':'' ?>><?= htmlspecialchars($pr['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn" style="padding:6px 8px;" title="Enregistrer le rattachement" onclick="assignerPra(this)"><i class="ti ti-device-floppy"></i></button>
                <?php if(!$praActuel): ?><span class="pill p-warn" style="margin-left:2px;">À assigner</span><?php endif; ?>
              </div>
            <?php else: ?>
              <span style="font-size:.8rem;color:var(--muted);">—</span>
            <?php endif; ?>
            </td>
            <td><?= boutonsContact($u['struct_tel']??'', $u['email']) ?></td>
            <td class="st-cell"><span class="pill <?= $u['statut']==='actif'?'p-ok':'p-bad' ?>"><?= ucfirst($u['statut']) ?></span></td>
            <td class="ac">
            <?php if($u['role']==='etat'): ?>
              <span style="font-size:.8rem;color:var(--muted);">—</span>
            <?php else:
              $avSusp = $avertParUser[$u['id']]['suspension'] ?? null;
              $avSupp = $avertParUser[$u['id']]['suppression'] ?? null;
              $suspPrete = $avSusp && strtotime($avSusp['applicable_le']) <= time();
              $suppPrete = $avSupp && strtotime($avSupp['applicable_le']) <= time();
            ?>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if($u['statut']==='actif'): ?>
                  <?php if($suspPrete): ?>
                    <button class="btn btn-bad" onclick="compte(<?= $u['id'] ?>,'suspendre',this)" title="Délai écoulé — suspendre"><i class="ti ti-ban"></i> Suspendre</button>
                  <?php elseif($avSusp): ?>
                    <button class="btn" disabled title="En attente du délai"><i class="ti ti-clock"></i> Susp. le <?= date('d/m',strtotime($avSusp['applicable_le'])) ?></button>
                  <?php else: ?>
                    <button class="btn" onclick="avertir(<?= $u['id'] ?>,'suspension','<?= htmlspecialchars(addslashes($u['prenom'].' '.$u['nom']),ENT_QUOTES) ?>',this)" title="Avertir avant suspension"><i class="ti ti-alert-triangle"></i> Avertir (susp.)</button>
                  <?php endif; ?>
                <?php else: ?>
                  <button class="btn btn-ok" onclick="compte(<?= $u['id'] ?>,'reactiver',this)" title="Réactiver"><i class="ti ti-check"></i></button>
                <?php endif; ?>

                <?php if($suppPrete): ?>
                  <button class="btn btn-bad" onclick="compte(<?= $u['id'] ?>,'supprimer',this)" title="Délai écoulé — supprimer"><i class="ti ti-trash"></i> Supprimer</button>
                <?php elseif($avSupp): ?>
                  <button class="btn" disabled title="En attente du délai"><i class="ti ti-clock"></i> Suppr. le <?= date('d/m',strtotime($avSupp['applicable_le'])) ?></button>
                <?php else: ?>
                  <button class="btn" onclick="avertir(<?= $u['id'] ?>,'suppression','<?= htmlspecialchars(addslashes($u['prenom'].' '.$u['nom']),ENT_QUOTES) ?>',this)" title="Avertir avant suppression"><i class="ti ti-alert-octagon"></i> Avertir (suppr.)</button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            </td></tr>
          <?php endforeach;?></tbody></table>
        </div>
      </div>

      <div id="fournisseurs" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-truck-delivery"></i>Fournisseurs référencés</div></div>
        <table><thead><tr><th>Fournisseur</th><th>Spécialité</th><th>Email</th><th>Ponctualité</th><th>Statut</th></tr></thead><tbody>
        <?php foreach($fournisseurs as $f):?>
          <tr><td><strong><?= htmlspecialchars($f['nom']) ?></strong></td><td><?= htmlspecialchars($f['specialite']) ?></td>
          <td><?= htmlspecialchars($f['email']) ?></td>
          <td><span class="pill <?= $f['taux_ponctualite']>=90?'p-ok':'p-warn' ?>"><?= $f['taux_ponctualite'] ?>%</span></td>
          <td><span class="pill <?= $f['statut']==='actif'?'p-ok':'p-warn' ?>"><?= ucfirst(str_replace('_',' ',$f['statut'])) ?></span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="resets" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-key"></i>Demandes de réinitialisation exceptionnelle</div><span class="pill p-warn"><?= count($demandesReset) ?> en attente</span></div>
        <div class="cp" style="padding-bottom:0;"><p style="font-size:0.85rem;color:var(--muted);margin-bottom:4px;">Ces utilisateurs ont déjà réinitialisé leur mot de passe ce mois-ci. Ils demandent une réinitialisation supplémentaire qui nécessite votre autorisation.</p></div>
        <table><thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Structure</th><th>Demandé le</th><th>Actions</th></tr></thead><tbody>
        <?php if(!$demandesReset):?><tr><td colspan="6"><div class="empty">Aucune demande de réinitialisation</div></td></tr><?php endif;?>
        <?php foreach($demandesReset as $dr):?>
          <tr><td><strong><?= htmlspecialchars($dr['prenom'].' '.$dr['nom']) ?></strong></td>
          <td><?= htmlspecialchars($dr['email']) ?></td>
          <td><span class="pill p-gray"><?= ucfirst($dr['role']) ?></span></td>
          <td><?= htmlspecialchars($dr['struct_nom']??'—') ?></td>
          <td><?= date('d/m/Y H:i',strtotime($dr['created_at'])) ?></td>
          <td class="ac"><div style="display:flex;gap:6px;">
            <button class="btn btn-ok" onclick="resetDemande(<?= $dr['id'] ?>,'autoriser',this)">✓ Autoriser</button>
            <button class="btn btn-bad" onclick="resetDemande(<?= $dr['id'] ?>,'refuser',this)">✗</button>
          </div></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="stats" class="section">
        <div class="ia-box"><div class="ia-ic"><i class="ti ti-brain"></i></div><div><div class="ia-t">Analyse IA</div><div class="ia-d">Tendances de consommation et niveau des régions.</div></div></div>
        <div class="card"><div class="cp"><div class="ct"><i class="ti ti-chart-pie"></i>Consommation par catégorie</div><div class="chart-h"><canvas id="pieChart"></canvas></div></div></div>
        <div class="card"><div class="cp"><div class="ct"><i class="ti ti-map-pin"></i>Niveau de stock par région</div><div class="chart-h"><canvas id="regionChart"></canvas></div></div></div>
      </div>

      <div id="carte" class="section">
        <div class="card" style="padding:0;overflow:hidden;">
          <div class="ch"><div class="ct"><i class="ti ti-map-2"></i>Carte nationale des structures</div></div>
          <?php include __DIR__.'/carte_widget.php'; ?>
        </div>
      </div>

      <div id="parametres" class="section">
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-adjustments"></i>Seuils d'alerte</div></div><div class="cp"><div class="grid2">
          <div class="fg"><label class="lbl">Seuil de stock critique</label><div class="inp-row"><input class="inp" type="number" data-param="seuil_critique" value="<?= htmlspecialchars($P['seuil_critique']??20) ?>"><span class="suffix">%</span></div></div>
          <div class="fg"><label class="lbl">Seuil de stock bas</label><div class="inp-row"><input class="inp" type="number" data-param="seuil_bas" value="<?= htmlspecialchars($P['seuil_bas']??50) ?>"><span class="suffix">%</span></div></div>
          <div class="fg"><label class="lbl">Alerte péremption</label><div class="inp-row"><input class="inp" type="number" data-param="alerte_peremption" value="<?= htmlspecialchars($P['alerte_peremption']??30) ?>"><span class="suffix">jours</span></div></div>
          <div class="fg"><label class="lbl">Seuil de surstock</label><div class="inp-row"><input class="inp" type="number" data-param="seuil_surstock" value="<?= htmlspecialchars($P['seuil_surstock']??80) ?>"><span class="suffix">%</span></div></div>
        </div></div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-cash"></i>Subventions</div></div><div class="cp">
          <div class="toggle-row"><div><div class="tr-txt">Activer les subventions</div></div><div class="toggle <?= ($P['subventions_actives']??1)?'on':'' ?>" data-param="subventions_actives" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
          <div class="grid2" style="margin-top:14px;">
            <div class="fg"><label class="lbl">Plafond par pharmacie</label><div class="inp-row"><input class="inp" type="number" data-param="plafond_subvention" value="<?= htmlspecialchars($P['plafond_subvention']??500000) ?>"><span class="suffix">FCFA</span></div></div>
            <div class="fg"><label class="lbl">Taux de prise en charge</label><div class="inp-row"><input class="inp" type="number" data-param="taux_prise_en_charge" value="<?= htmlspecialchars($P['taux_prise_en_charge']??100) ?>"><span class="suffix">%</span></div></div>
          </div>
        </div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-arrows-exchange"></i>Rééquilibrage</div></div><div class="cp">
          <div class="toggle-row"><div><div class="tr-txt">Rééquilibrage automatique par l'IA</div></div><div class="toggle <?= ($P['reequilibrage_ia']??1)?'on':'' ?>" data-param="reequilibrage_ia" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
          <div class="toggle-row"><div><div class="tr-txt">Signalement manuel par les PRA</div></div><div class="toggle <?= ($P['reequilibrage_pra']??1)?'on':'' ?>" data-param="reequilibrage_pra" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
        </div></div>
        <div class="save-bar"><button class="btn btn-pr" onclick="saveParams(this)"><i class="ti ti-device-floppy"></i> Enregistrer les paramètres</button></div>
      </div>

    </div>
  </div>
</div>
<script>
window.SECTION_TITLES={dashboard:'Tableau de bord national',alertes:'Alertes & IA',reequilibrage:'Rééquilibrage des régions',subventions:"Subventions de l'État",validation:'Suivi des commandes',regions:'État des stocks par région',comptes:'Comptes & accès',resets:'Demandes de réinitialisation',fournisseurs:'Fournisseurs référencés',stats:'Statistiques & prévisions IA',carte:'Carte nationale des structures',parametres:'Paramètres de la plateforme'};
async function resetDemande(id, act, btn){
  if(!confirm(act==='autoriser'?'Autoriser cet utilisateur à réinitialiser son mot de passe ?':'Refuser cette demande ?')) return;
  const fd=new FormData(); fd.append('id',id); fd.append('action',act);
  try{
    const res=await fetch('actions/reset_demande.php',{method:'POST',body:fd});
    const j=await res.json();
    if(j.success){
      const row=btn.closest('tr'); const ac=btn.closest('.ac');
      if(ac) ac.innerHTML='<span style="font-size:.82rem;color:'+(act==='autoriser'?'#1a7a40':'#a32d2d')+';font-weight:600;">'+(act==='autoriser'?'Autorisé ✓':'Refusé')+'</span>';
      if(act==='refuser'&&row) row.style.opacity='0.5';
    } else { alert('Erreur : '+(j.message||'action impossible')); }
  }catch(e){ alert('Erreur serveur.'); }
}
function initCharts(){
  const green='#1faa4e',amber='#f0a020',purple='#5b4cc4',blue='#378ADD',gridC='#eef7f1',textC='#5a8a6a';
  Chart.defaults.font.family="'Inter',sans-serif";Chart.defaults.color=textC;
  new Chart(document.getElementById('pieChart'),{type:'doughnut',data:{labels:<?= $catLabels ?>,datasets:[{data:<?= $catData ?>,backgroundColor:[green,blue,amber,purple,'#97c459','#e24b4a'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}}}});
  new Chart(document.getElementById('regionChart'),{type:'bar',data:{labels:<?= json_encode(array_column($regions,'region')) ?>,datasets:[{label:'Niveau (%)',data:<?= json_encode(array_map(fn($r)=>pctStock($r['stock'],$r['seuil']),$regions)) ?>,backgroundColor:green,borderRadius:6,barThickness:26}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:gridC},ticks:{callback:v=>v+'%'}},x:{grid:{display:false}}}}});
}
</script>
<script src="../assets/js/dashboard.js"></script>
<?php include __DIR__."/assistant_widget.php"; ?>
</body>
</html>
