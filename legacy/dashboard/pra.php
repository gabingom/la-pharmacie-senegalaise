<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/helpers.php';
exigerRole('pra');
// Animation d'accueil : une seule fois par session
$montrerAccueil = empty($_SESSION['accueil_pra_vu']);
if ($montrerAccueil) { $_SESSION['accueil_pra_vu'] = true; }
$sid = structureId();

// Fiche de ma structure (region, zone, contacts)
$maStructure = $pdo->prepare("SELECT nom, region, zone, telephone, email, adresse, latitude, longitude FROM structures WHERE id=?");
$maStructure->execute([$sid]);
$maStructure = $maStructure->fetch() ?: [];
$ficheIncomplete = empty($maStructure['region']) || $maStructure['region'] === 'A definir'
                   || empty($maStructure['zone']) || empty($maStructure['telephone']);

// Stocks du PRA
$stocks = $pdo->prepare("
    SELECT s.*, m.nom, m.dosage, m.forme, m.seuil_alerte
    FROM stocks s JOIN medicaments m ON s.medicament_id=m.id
    WHERE s.structure_id=? ORDER BY (s.quantite/m.seuil_alerte) ASC
");
$stocks->execute([$sid]); $stocks=$stocks->fetchAll();

$nbRefs=count($stocks);
$nbCrit=0; foreach($stocks as $s){ if(pctStock($s['quantite'],$s['seuil_alerte'])<100) $nbCrit++; }

// Demandes des pharmacies rattachées à ce PRA
$demandes = $pdo->prepare("
    SELECT c.*, st.nom AS struct_nom, st.pra_parent,
           GROUP_CONCAT(CONCAT(m.nom,' ',m.dosage) SEPARATOR ', ') AS meds,
           SUM(l.quantite_demandee) AS qte
    FROM commandes c JOIN utilisateurs u ON c.demandeur_id=u.id
    JOIN structures st ON u.structure_id=st.id
    LEFT JOIN lignes_commande l ON l.commande_id=c.id
    LEFT JOIN medicaments m ON l.medicament_id=m.id
    WHERE st.type='pharmacie' AND c.statut='en_attente'
      AND ( c.pra_cible_id = ? OR (c.pra_cible_id IS NULL AND st.pra_parent = ?) )
    GROUP BY c.id ORDER BY c.date_commande DESC
");
$demandes->execute([$sid, $sid]); $demandes=$demandes->fetchAll();

// ---- Autorisations de commande externe (PRA de rattachement) ----
$autorisations = []; $autresPra = []; $nbAutor = 0;
try {
    $a = $pdo->prepare("
        SELECT a.*, ph.nom AS pharma_nom, pc.nom AS pra_cible_nom,
               CONCAT(m.nom,' ',m.dosage) AS med_nom
        FROM autorisations_pra a
        JOIN structures ph  ON a.pharmacie_id = ph.id
        JOIN structures pc  ON a.pra_cible_id = pc.id
        JOIN medicaments m  ON a.medicament_id = m.id
        WHERE a.pra_origine_id = ? AND a.statut IN ('en_attente','accordee')
        ORDER BY FIELD(a.statut,'en_attente','accordee'), a.created_at DESC
    ");
    $a->execute([$sid]);
    $autorisations = $a->fetchAll();
    foreach ($autorisations as $x) if ($x['statut']==='en_attente') $nbAutor++;

    // Autres PRA (pour proposer une orientation)
    $ap = $pdo->prepare("SELECT id, nom, region FROM structures WHERE type='pra' AND id<>? AND statut='active' ORDER BY nom");
    $ap->execute([$sid]);
    $autresPra = $ap->fetchAll();
} catch (Exception $e) {}

// Péremptions
$perem = $pdo->prepare("
    SELECT s.*, m.nom, m.dosage, DATEDIFF(s.date_peremption,CURDATE()) AS jr
    FROM stocks s JOIN medicaments m ON s.medicament_id=m.id
    WHERE s.structure_id=? AND s.date_peremption IS NOT NULL AND DATEDIFF(s.date_peremption,CURDATE())<=90
    ORDER BY jr ASC
");
$perem->execute([$sid]); $perem=$perem->fetchAll();

// Pharmacies de la zone (pour signalements)
$pharmacies = $pdo->prepare("SELECT s.id, s.nom, s.telephone, s.zone, u.email FROM structures s LEFT JOIN utilisateurs u ON u.structure_id=s.id WHERE s.type='pharmacie' AND s.pra_parent=? GROUP BY s.id");
$pharmacies->execute([$sid]); $pharmacies=$pharmacies->fetchAll();

// Médicaments (pour signalements)
$meds = $pdo->query("SELECT id,nom,dosage FROM medicaments ORDER BY nom")->fetchAll();

// Mes signalements
$mesReeq = $pdo->prepare("SELECT r.*, m.nom AS med_nom FROM reequilibrages r JOIN medicaments m ON r.medicament_id=m.id WHERE r.signale_par=? ORDER BY r.created_at DESC");
$mesReeq->execute([idUtilisateur()]); $mesReeq=$mesReeq->fetchAll();
$mesSub = $pdo->prepare("SELECT sub.*, ph.nom AS pharma_nom FROM subventions sub JOIN structures ph ON sub.pharmacie_id=ph.id WHERE sub.signale_par=? ORDER BY sub.created_at DESC");
$mesSub->execute([idUtilisateur()]); $mesSub=$mesSub->fetchAll();

$P=[]; foreach($pdo->query("SELECT cle,valeur FROM parametres") as $r) $P[$r['cle']]=$r['valeur'];
$retour = '../pra.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Pharmacie Sénégalaise — PRA</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<?php if($montrerAccueil) { $accueilTitre = 'Bienvenue, ' . nomUtilisateur();
$accueilSousTitre = structureNom() . ' — Pharmacie Régionale d\'Approvisionnement'; include __DIR__.'/welcome_role.php'; } ?>
<div class="app">
  <aside class="sb">
    <div class="sb-logo"><div class="sb-logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#1faa4e"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#1faa4e"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#1faa4e"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#1faa4e"/><circle cx="50" cy="18" r="7" fill="#1faa4e"/></svg></div><div class="sb-name">La Pharmacie<br>Sénégalaise</div></div>
    <div class="sb-user"><div class="sb-av"><?= htmlspecialchars(initiales()) ?></div><div><div class="sb-un"><?= htmlspecialchars(structureNom()) ?></div><div class="sb-ur">Gestionnaire de stock</div></div></div>
    <nav class="sb-nav">
      <div class="sb-sec">Principal</div>
      <a class="sb-item active" onclick="nav('dashboard',this)" href="#"><i class="ti ti-dashboard"></i>Tableau de bord</a>
      <div class="sb-sec">Stock</div>
      <a class="sb-item" onclick="nav('inventaire',this)" href="#"><i class="ti ti-building-warehouse"></i>Inventaire</a>
      <a class="sb-item" onclick="nav('peremptions',this)" href="#"><i class="ti ti-clock"></i>Péremptions<?php if($perem):?><span class="sb-badge-w"><?= count($perem) ?></span><?php endif;?></a>
      <div class="sb-sec">Distribution</div>
      <a class="sb-item" onclick="nav('demandes',this)" href="#"><i class="ti ti-list-check"></i>Demandes pharmacies<?php if($demandes):?><span class="sb-badge-w"><?= count($demandes) ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('autorisations',this)" href="#"><i class="ti ti-shield-check"></i>Autorisations<?php if($nbAutor):?><span class="sb-badge-w"><?= $nbAutor ?></span><?php endif;?></a>
      <div class="sb-sec">Signalements</div>
      <a class="sb-item" onclick="nav('reequilibrage',this)" href="#"><i class="ti ti-arrows-exchange"></i>Rééquilibrage</a>
      <a class="sb-item" onclick="nav('subventions',this)" href="#"><i class="ti ti-cash"></i>Subventions</a>
      <div class="sb-sec">Intelligence</div>
      <a class="sb-item" onclick="nav('stats',this)" href="#"><i class="ti ti-chart-line"></i>Tendances</a>
      <a class="sb-item" onclick="nav('carte',this);initLpsMapWhenVisible();" href="#"><i class="ti ti-map-2"></i>Carte</a>
      <div class="sb-sec">Système</div>
      <a class="sb-item" onclick="nav('parametres',this)" href="#"><i class="ti ti-settings"></i>Paramètres</a>
    </nav>
    <div class="sb-bot"><a class="sb-logout" href="../auth/logout.php"><i class="ti ti-logout"></i>Déconnexion</a></div>
  </aside>
  <div class="main">
    <div class="top"><div class="top-title" id="topTitle">Tableau de bord — <?= htmlspecialchars(structureNom()) ?></div>
    <div class="top-r"><div class="top-date"><i class="ti ti-calendar-event"></i><?= function_exists('strftime_fr') ? strftime_fr() : date('d/m/Y') ?></div><div class="bell"><i class="ti ti-bell"></i><?php if($nbCrit):?><div class="bell-dot"></div><?php endif;?></div></div></div>
    <div class="content">

      <div id="dashboard" class="section active">
        <div class="stats">
          <div class="stat stat-hero">
            <div class="stat-top"><span class="stat-lbl">Références en stock</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= nb($nbRefs) ?></div>
            <div class="stat-pill stat-pill-hero"><i class="ti ti-building-warehouse"></i>Inventaire régional</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Stocks à surveiller</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--danger);"><?= $nbCrit ?></div>
            <div class="stat-pill" style="background:var(--danger-bg);color:#a32d2d;"><i class="ti ti-alert-triangle"></i><?= $nbCrit ? 'Sous le seuil' : 'Aucun risque' ?></div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Demandes en attente</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--warn);"><?= count($demandes) ?></div>
            <div class="stat-pill" style="background:var(--warn-bg);color:#9a6a0a;"><i class="ti ti-list-check"></i>À traiter</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Péremptions proches</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= count($perem) ?></div>
            <div class="stat-pill" style="background:var(--info-bg);color:#1d5c9e;"><i class="ti ti-clock"></i>À écouler</div>
          </div>
        </div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-alert-triangle"></i>Stocks en alerte</div></div>
          <table><thead><tr><th>Médicament</th><th>Stock</th><th>Seuil</th><th>Niveau</th></tr></thead><tbody>
          <?php $alerteStocks=array_filter($stocks,fn($s)=>pctStock($s['quantite'],$s['seuil_alerte'])<100);
          if(!$alerteStocks):?><tr><td colspan="4"><div class="empty">Tous les stocks sont au niveau ✓</div></td></tr><?php endif;
          foreach($alerteStocks as $s): $pct=pctStock($s['quantite'],$s['seuil_alerte']); ?>
            <tr><td><strong><?= htmlspecialchars($s['nom'].' '.$s['dosage']) ?></strong></td>
            <td style="font-weight:700;color:<?= $pct<20?'var(--danger)':'var(--warn)' ?>"><?= nb($s['quantite']) ?></td>
            <td><?= nb($s['seuil_alerte']) ?></td>
            <td><div class="prog"><div class="pbar"><div class="pfill <?= classeNiveau($pct) ?>" style="width:<?= min($pct,100) ?>%"></div></div><span class="plbl"><?= $pct ?>%</span></div></td></tr>
          <?php endforeach;?></tbody></table>
        </div>
      </div>

      <div id="inventaire" class="section">
        <div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-building-warehouse"></i>Inventaire complet</div><button class="btn btn-pr" onclick="document.getElementById('formAjoutStock').style.display='block';this.style.display='none';"><i class="ti ti-plus"></i> Ajouter un médicament</button></div>
        <div id="formAjoutStock" style="display:none;padding:18px 20px;border-bottom:1px solid #eef7f1;background:#fafdfb;">
          <div class="grid2">
            <div class="fg"><label class="lbl">Nom du médicament</label><input class="inp" id="asNom" type="text" placeholder="ex. Amoxicilline"></div>
            <div class="fg"><label class="lbl">Dosage</label><input class="inp" id="asDosage" type="text" placeholder="ex. 500mg"></div>
            <div class="fg"><label class="lbl">Forme</label><select class="sel" id="asForme"><option value="comprime">Comprimé</option><option value="gelule">Gélule</option><option value="sirop">Sirop</option><option value="injection">Injection</option><option value="sachet">Sachet</option><option value="pommade">Pommade</option><option value="autre">Autre</option></select></div>
            <div class="fg"><label class="lbl">Catégorie</label><input class="inp" id="asCat" type="text" placeholder="ex. Antibiotique"></div>
            <div class="fg"><label class="lbl">Quantité en stock</label><input class="inp" id="asQte" type="number" value="1000" min="1"></div>
            <div class="fg"><label class="lbl">Seuil d'alerte</label><input class="inp" id="asSeuil" type="number" value="500" min="0"></div>
            <div class="fg"><label class="lbl">Numéro de lot</label><input class="inp" id="asLot" type="text" placeholder="ex. DK2025-30"></div>
            <div class="fg"><label class="lbl">Date de péremption</label><input class="inp" id="asPerem" type="date"></div>
          </div>
          <div id="asDoublonZone" style="display:none;background:#fef6e7;border:1px solid #f0c040;border-radius:10px;padding:13px 16px;margin:10px 0;font-size:0.88rem;color:#5c3d00;"></div>
          <div id="asMsg" style="margin:10px 0;"></div>
          <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="btn" onclick="document.getElementById('formAjoutStock').style.display='none';document.querySelector('#inventaire .btn-pr').style.display='inline-flex';">Annuler</button>
            <button class="btn btn-pr" id="asBtn" onclick="ajouterStock(false)"><i class="ti ti-device-floppy"></i> Enregistrer</button>
          </div>
        </div>
        <table><thead><tr><th>Médicament</th><th>Forme</th><th>Stock</th><th>Lot</th><th>Péremption</th><th>Seuil</th><th>Statut</th></tr></thead><tbody>
        <?php if(!$stocks):?><tr><td colspan="7"><div class="empty">Votre stock est vide. Cliquez sur « Ajouter un médicament » pour le constituer.</div></td></tr><?php endif;?>
        <?php foreach($stocks as $s): $pct=pctStock($s['quantite'],$s['seuil_alerte']); [$pc,$pl]=pillNiveau($pct); ?>
          <tr><td><strong><?= htmlspecialchars($s['nom'].' '.$s['dosage']) ?></strong></td><td><?= ucfirst($s['forme']) ?></td>
          <td><?= nb($s['quantite']) ?></td><td><?= htmlspecialchars($s['numero_lot']??'—') ?></td>
          <td><?= $s['date_peremption']?date('m/Y',strtotime($s['date_peremption'])):'—' ?></td><td><?= nb($s['seuil_alerte']) ?></td>
          <td><span class="pill <?= $pc ?>"><?= $pl ?></span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="peremptions" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-clock"></i>Suivi des péremptions</div></div>
        <table><thead><tr><th>Médicament</th><th>Lot</th><th>Quantité</th><th>Péremption</th><th>Jours restants</th></tr></thead><tbody>
        <?php if(!$perem):?><tr><td colspan="5"><div class="empty">Aucune péremption proche</div></td></tr><?php endif;?>
        <?php foreach($perem as $p):?>
          <tr><td><strong><?= htmlspecialchars($p['nom'].' '.$p['dosage']) ?></strong></td><td><?= htmlspecialchars($p['numero_lot']??'—') ?></td>
          <td><?= nb($p['quantite']) ?></td><td><?= date('d/m/Y',strtotime($p['date_peremption'])) ?></td>
          <td><span class="pill <?= $p['jr']<=30?'p-bad':($p['jr']<=60?'p-warn':'p-ok') ?>"><?= $p['jr'] ?> jours</span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="demandes" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-list-check"></i>Demandes des pharmacies</div><span class="pill p-warn"><?= count($demandes) ?> en attente</span></div>
        <table><thead><tr><th>Réf.</th><th>Pharmacie</th><th>Médicament</th><th>Qté</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
        <?php if(!$demandes):?><tr><td colspan="6"><div class="empty">Aucune demande en attente</div></td></tr><?php endif;?>
        <?php foreach($demandes as $d):?>
          <tr><td><strong><?= htmlspecialchars($d['reference']) ?></strong></td>
          <td><?= htmlspecialchars($d['struct_nom']) ?>
              <?php if($d['pra_parent'] != $sid): ?><span class="pill p-info" style="margin-left:5px;">Externe</span><?php endif; ?></td>
          <td><?= htmlspecialchars($d['meds']??'—') ?></td><td><?= nb($d['qte']) ?></td>
          <td class="st-cell"><span class="pill p-warn">En attente</span></td>
          <td class="ac"><div style="display:flex;gap:6px;"><button class="btn btn-ok" onclick="action('actions/commande.php',<?= $d['id'] ?>,'valider',this)">✓ Valider</button><button class="btn btn-bad" onclick="action('actions/commande.php',<?= $d['id'] ?>,'rejeter',this)">✗ Refuser</button></div></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="autorisations" class="section">
        <div class="ia-box"><div class="ia-ic"><i class="ti ti-shield-check"></i></div><div><div class="ia-t">Autorisations de commande externe</div><div class="ia-d">Lorsque vous ne disposez pas d'un médicament, vous pouvez <strong>autoriser une de vos pharmacies à le commander auprès d'un autre PRA</strong>. L'autorisation porte sur un médicament précis et reste valable jusqu'à révocation. Le PRA sollicité reste libre d'accepter ou de refuser la commande.</div></div></div>

        <div class="card">
          <div class="ch"><div class="ct"><i class="ti ti-inbox"></i>Demandes et autorisations en cours</div><span class="pill p-warn"><?= $nbAutor ?> en attente</span></div>
          <table><thead><tr><th>Pharmacie</th><th>Médicament</th><th>PRA sollicité</th><th>Motif</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
          <?php if(!$autorisations):?><tr><td colspan="6"><div class="empty">Aucune demande ni autorisation en cours</div></td></tr><?php endif;?>
          <?php foreach($autorisations as $a): $enAttente = $a['statut']==='en_attente'; ?>
            <tr>
            <td><strong><?= htmlspecialchars($a['pharma_nom']) ?></strong></td>
            <td><?= htmlspecialchars($a['med_nom']) ?></td>
            <td><?= htmlspecialchars($a['pra_cible_nom']) ?></td>
            <td style="max-width:200px;font-size:0.84rem;color:var(--mid);"><?= $a['motif'] ? htmlspecialchars($a['motif']) : '—' ?></td>
            <td class="st-cell"><span class="pill <?= $enAttente?'p-warn':'p-ok' ?>"><?= $enAttente?'En attente':'Accordée' ?></span></td>
            <td class="ac"><div style="display:flex;gap:6px;">
              <?php if($enAttente): ?>
                <button class="btn btn-ok"  onclick="autoriser(<?= $a['id'] ?>,'accorder',this)">✓ Accorder</button>
                <button class="btn btn-bad" onclick="autoriser(<?= $a['id'] ?>,'refuser',this)">✗ Refuser</button>
              <?php else: ?>
                <button class="btn btn-bad" onclick="autoriser(<?= $a['id'] ?>,'revoquer',this)"><i class="ti ti-ban"></i> Révoquer</button>
              <?php endif; ?>
            </div></td></tr>
          <?php endforeach;?></tbody></table>
        </div>

        <div class="card" style="max-width:560px;">
          <div class="ch"><div class="ct"><i class="ti ti-send"></i>Orienter une pharmacie vers un autre PRA</div></div>
          <div class="cp">
            <div class="fg"><label class="lbl">Pharmacie concernée</label>
              <select class="sel" id="auPharma">
                <option value="">— Sélectionner —</option>
                <?php foreach($pharmacies as $ph):?><option value="<?= $ph['id'] ?>"><?= htmlspecialchars($ph['nom']) ?></option><?php endforeach;?>
              </select>
            </div>
            <div class="fg"><label class="lbl">Médicament</label>
              <select class="sel" id="auMed">
                <option value="">— Sélectionner —</option>
                <?php foreach($meds as $m):?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['dosage']) ?></option><?php endforeach;?>
              </select>
            </div>
            <div class="fg"><label class="lbl">PRA à solliciter</label>
              <select class="sel" id="auPra">
                <option value="">— Sélectionner —</option>
                <?php foreach($autresPra as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom'].($p['region']?' ('.$p['region'].')':'')) ?></option><?php endforeach;?>
              </select>
            </div>
            <div class="fg"><label class="lbl">Motif (facultatif)</label><input class="inp" id="auMotif" placeholder="Ex. : rupture prolongée sur ce produit"></div>
            <button class="btn btn-pr" style="width:100%;padding:12px;" onclick="proposerAutorisation()"><i class="ti ti-check"></i> Accorder l'autorisation</button>
            <div id="auMsg" style="margin-top:14px;"></div>
          </div>
        </div>
      </div>

      <div id="reequilibrage" class="section">
        <div class="ia-box"><div class="ia-ic"><i class="ti ti-arrows-exchange"></i></div><div><div class="ia-t">Signaler un besoin de rééquilibrage</div><div class="ia-d">Si une pharmacie manque d'un médicament disponible ailleurs, signalez-le. L'État organisera un <strong>transfert depuis une région en surstock</strong>.</div></div></div>
        <div class="two">
          <div class="card"><div class="ch"><div class="ct"><i class="ti ti-flag"></i>Nouveau signalement</div></div>
            <div class="cp"><form action="actions/signaler.php" method="POST">
              <input type="hidden" name="type" value="reequilibrage"><input type="hidden" name="retour" value="<?= $retour ?>">
              <div class="fg"><label class="lbl">Médicament concerné</label><select class="sel" name="medicament_id" required><?php foreach($meds as $m):?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['dosage']) ?></option><?php endforeach;?></select></div>
              <div class="fg"><label class="lbl">Quantité nécessaire</label><div class="inp-row"><input class="inp" type="number" name="quantite" value="1000" required><span class="suffix">unités</span></div></div>
              <div class="fg"><label class="lbl">Priorité</label><select class="sel" name="priorite"><option value="critique">Critique</option><option value="moderee">Modérée</option><option value="faible">Faible</option></select></div>
              <div class="fg"><label class="lbl">Justification</label><textarea class="inp" name="justification" rows="2" placeholder="Pharmacie en rupture, forte demande locale..."></textarea></div>
              <button class="btn btn-pr" type="submit" style="width:100%;padding:11px;"><i class="ti ti-send"></i> Envoyer le signalement</button>
            </form></div>
          </div>
          <div class="card"><div class="ch"><div class="ct"><i class="ti ti-history"></i>Mes signalements</div></div>
            <table><thead><tr><th>Médicament</th><th>Qté</th><th>Statut</th></tr></thead><tbody>
            <?php if(!$mesReeq):?><tr><td colspan="3"><div class="empty">Aucun signalement</div></td></tr><?php endif;?>
            <?php foreach($mesReeq as $r): $sp=$r['statut']==='en_attente'?'p-warn':($r['statut']==='validee'?'p-ok':($r['statut']==='recu'?'p-ok':'p-bad')); ?>
              <tr><td><strong><?= htmlspecialchars($r['med_nom']) ?></strong></td><td><?= nb($r['quantite']) ?></td>
              <td><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$r['statut'])) ?></span></td></tr>
            <?php endforeach;?></tbody></table>
          </div>
        </div>
      </div>

      <div id="subventions" class="section">
        <div class="ia-box" style="background:linear-gradient(135deg,#eafaf0,#f7fdf9);border-color:#b8ddc4;"><div class="ia-ic" style="background:var(--green);"><i class="ti ti-cash"></i></div><div><div class="ia-t" style="color:var(--green-deep);">Signaler une pharmacie en difficulté</div><div class="ia-d" style="color:var(--green-d);">Si une pharmacie ne peut pas s'approvisionner, signalez-la. L'État pourra <strong>subventionner ses médicaments</strong>.</div></div></div>
        <div class="two">
          <div class="card"><div class="ch"><div class="ct"><i class="ti ti-flag"></i>Nouveau signalement</div></div>
            <div class="cp"><form action="actions/signaler.php" method="POST">
              <input type="hidden" name="type" value="subvention"><input type="hidden" name="retour" value="<?= $retour ?>">
              <div class="fg"><label class="lbl">Pharmacie concernée</label><select class="sel" name="pharmacie_id" required><?php if(!$pharmacies):?><option value="">Aucune pharmacie rattachée</option><?php endif; foreach($pharmacies as $ph):?><option value="<?= $ph['id'] ?>"><?= htmlspecialchars($ph['nom']) ?></option><?php endforeach;?></select></div>
              <div class="fg"><label class="lbl">Médicaments nécessaires</label><input class="inp" type="text" name="medicaments" placeholder="ex. Antipaludiques, SRO" required></div>
              <div class="fg"><label class="lbl">Montant estimé</label><div class="inp-row"><input class="inp" type="number" name="montant" value="200000" required><span class="suffix">FCFA</span></div></div>
              <div class="fg"><label class="lbl">Motif</label><textarea class="inp" name="motif" rows="2" placeholder="Zone rurale, faible budget..."></textarea></div>
              <button class="btn btn-pr" type="submit" style="width:100%;padding:11px;"><i class="ti ti-send"></i> Envoyer à l'État</button>
            </form></div>
          </div>
          <div class="card"><div class="ch"><div class="ct"><i class="ti ti-history"></i>Mes demandes</div></div>
            <table><thead><tr><th>Pharmacie</th><th>Montant</th><th>Statut</th></tr></thead><tbody>
            <?php if(!$mesSub):?><tr><td colspan="3"><div class="empty">Aucune demande</div></td></tr><?php endif;?>
            <?php foreach($mesSub as $s): $sp=$s['statut']==='en_attente'?'p-warn':($s['statut']==='approuvee'?'p-ok':'p-bad'); ?>
              <tr><td><strong><?= htmlspecialchars($s['pharma_nom']) ?></strong></td><td><?= nb($s['montant_estime']) ?></td>
              <td><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$s['statut'])) ?></span></td></tr>
            <?php endforeach;?></tbody></table>
          </div>
        </div>
      </div>

      <div id="stats" class="section">
        <div class="card"><div class="cp"><div class="ct"><i class="ti ti-chart-bar"></i>Niveau de stock par médicament</div><div class="chart-h"><canvas id="barChart"></canvas></div></div></div>
      </div>

      <div id="carte" class="section">
        <div class="card" style="padding:0;overflow:hidden;">
          <div class="ch"><div class="ct"><i class="ti ti-map-2"></i>Carte des structures</div></div>
          <?php include __DIR__.'/carte_widget.php'; ?>
        </div>
      </div>

      <div id="parametres" class="section">

        <?php if($ficheIncomplete): ?>
        <div class="ia-box" style="background:linear-gradient(135deg,#fdf3e3,#fffaf2);border-color:#f0c98a;">
          <div class="ia-ic" style="background:var(--warn);"><i class="ti ti-alert-triangle"></i></div>
          <div><div class="ia-t" style="color:#8a5d00;">Fiche de structure incomplète</div>
          <div class="ia-d" style="color:#9a6a0a;">Renseignez ci-dessous votre région, votre zone et vos coordonnées. Sans ces informations, les autres structures ne peuvent pas vous contacter, et votre région n'apparaît pas dans les statistiques nationales.</div></div>
        </div>
        <?php endif; ?>

        <div class="card">
          <div class="ch"><div class="ct"><i class="ti ti-id-badge"></i>Fiche de ma structure</div>
            <span class="pill <?= $ficheIncomplete ? 'p-warn' : 'p-ok' ?>"><?= $ficheIncomplete ? 'À compléter' : 'Complète' ?></span>
          </div>
          <div class="cp">
            <div class="fg"><label class="lbl">Nom de la structure</label>
              <input class="inp" value="<?= htmlspecialchars($maStructure['nom'] ?? '') ?>" disabled>
              <div class="hint">Le nom est défini par le Ministère et ne peut pas être modifié ici.</div>
            </div>
            <div class="grid2">
              <div class="fg"><label class="lbl">Région</label>
                <select class="sel" id="fsRegion">
                  <option value="">— Sélectionner —</option>
                  <option value="Dakar"<?= ($maStructure['region']??'')==='Dakar'?' selected':'' ?>>Dakar</option>
                  <option value="Thiès"<?= ($maStructure['region']??'')==='Thiès'?' selected':'' ?>>Thiès</option>
                  <option value="Diourbel"<?= ($maStructure['region']??'')==='Diourbel'?' selected':'' ?>>Diourbel</option>
                  <option value="Fatick"<?= ($maStructure['region']??'')==='Fatick'?' selected':'' ?>>Fatick</option>
                  <option value="Kaffrine"<?= ($maStructure['region']??'')==='Kaffrine'?' selected':'' ?>>Kaffrine</option>
                  <option value="Kaolack"<?= ($maStructure['region']??'')==='Kaolack'?' selected':'' ?>>Kaolack</option>
                  <option value="Kédougou"<?= ($maStructure['region']??'')==='Kédougou'?' selected':'' ?>>Kédougou</option>
                  <option value="Kolda"<?= ($maStructure['region']??'')==='Kolda'?' selected':'' ?>>Kolda</option>
                  <option value="Louga"<?= ($maStructure['region']??'')==='Louga'?' selected':'' ?>>Louga</option>
                  <option value="Matam"<?= ($maStructure['region']??'')==='Matam'?' selected':'' ?>>Matam</option>
                  <option value="Saint-Louis"<?= ($maStructure['region']??'')==='Saint-Louis'?' selected':'' ?>>Saint-Louis</option>
                  <option value="Sédhiou"<?= ($maStructure['region']??'')==='Sédhiou'?' selected':'' ?>>Sédhiou</option>
                  <option value="Tambacounda"<?= ($maStructure['region']??'')==='Tambacounda'?' selected':'' ?>>Tambacounda</option>
                  <option value="Ziguinchor"<?= ($maStructure['region']??'')==='Ziguinchor'?' selected':'' ?>>Ziguinchor</option>
                  <option value="Sine Saloum"<?= ($maStructure['region']??'')==='Sine Saloum'?' selected':'' ?>>Sine Saloum</option>
                </select>
              </div>
              <div class="fg"><label class="lbl">Zone</label>
                <select class="sel" id="fsZone">
                  <option value="">— Sélectionner —</option>
                  <option value="ville"<?= ($maStructure['zone']??'')==='ville'?' selected':'' ?>>Ville</option>
                  <option value="village"<?= ($maStructure['zone']??'')==='village'?' selected':'' ?>>Village</option>
                  <option value="rural"<?= ($maStructure['zone']??'')==='rural'?' selected':'' ?>>Rural</option>
                </select>
                <div class="hint">Sert à mesurer l'équité de la distribution entre zones urbaines et rurales.</div>
              </div>
            </div>
            <div class="grid2">
              <div class="fg"><label class="lbl">Téléphone</label>
                <input class="inp" id="fsTel" placeholder="77 123 45 67" value="<?= htmlspecialchars($maStructure['telephone'] ?? '') ?>">
                <div class="hint">Affiché aux structures partenaires pour vous joindre.</div>
              </div>
              <div class="fg"><label class="lbl">Email de la structure</label>
                <input class="inp" id="fsEmail" type="email" placeholder="contact@structure.sn" value="<?= htmlspecialchars($maStructure['email'] ?? '') ?>">
              </div>
            </div>
            <div class="fg"><label class="lbl">Adresse</label>
              <input class="inp" id="fsAdresse" placeholder="Quartier, ville" value="<?= htmlspecialchars($maStructure['adresse'] ?? '') ?>">
            </div>
            <div class="fg">
              <label class="lbl">Localisation sur la carte</label>
              <?php if(!empty($maStructure['latitude'])): ?>
                <span class="pill p-ok"><i class="ti ti-map-pin"></i> Position enregistrée</span>
              <?php else: ?>
                <span class="pill p-warn"><i class="ti ti-map-pin-off"></i> Non localisée — rendez-vous dans l'onglet Carte</span>
              <?php endif; ?>
            </div>
            <button class="btn btn-pr" style="width:100%;padding:12px;" onclick="enregistrerFiche()"><i class="ti ti-device-floppy"></i> Enregistrer ma fiche</button>
            <div id="fsMsg" style="margin-top:14px;"></div>
          </div>
        </div>

        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-adjustments"></i>Seuils & automatisations</div></div><div class="cp">
          <div class="grid2">
            <div class="fg"><label class="lbl">Délai de traitement des demandes</label><div class="inp-row"><input class="inp" type="number" value="48"><span class="suffix">heures</span></div></div>
            <div class="fg"><label class="lbl">Seuil de validation automatique</label><div class="inp-row"><input class="inp" type="number" value="100"><span class="suffix">unités</span></div></div>
          </div>
          <div class="toggle-row"><div><div class="tr-txt">Alerte automatique à l'État en cas de rupture</div></div><div class="toggle on" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
          <div class="toggle-row"><div><div class="tr-txt">Validation automatique des petites demandes</div></div><div class="toggle" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
        </div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-category"></i>Pharmacies rattachées</div></div>
          <table><thead><tr><th>Pharmacie</th><th>Zone</th><th>Téléphone</th><th>Contact</th></tr></thead><tbody>
          <?php if(!$pharmacies):?><tr><td colspan="4"><div class="empty">Aucune pharmacie rattachée</div></td></tr><?php endif;?>
          <?php foreach($pharmacies as $ph):?>
            <tr><td><strong><?= htmlspecialchars($ph['nom']) ?></strong></td>
            <td><?= htmlspecialchars(ucfirst($ph['zone']??'—')) ?></td>
            <td><?= htmlspecialchars($ph['telephone'] ?: '—') ?></td>
            <td><?= boutonsContact($ph['telephone']??'', $ph['email']??'') ?></td></tr>
          <?php endforeach;?>
          </tbody></table>
        </div></div>
        <div class="save-bar"><button class="btn btn-pr" onclick="this.innerHTML='✓ Enregistré'"><i class="ti ti-device-floppy"></i> Enregistrer</button></div>
      </div>

    </div>
  </div>
</div>
<script>

// Enregistrer la fiche de ma structure
async function enregistrerFiche(){
  const msg = document.getElementById('fsMsg');
  const fd = new FormData();
  fd.append('region',    document.getElementById('fsRegion').value);
  fd.append('zone',      document.getElementById('fsZone').value);
  fd.append('telephone', document.getElementById('fsTel').value);
  fd.append('email',     document.getElementById('fsEmail').value);
  fd.append('adresse',   document.getElementById('fsAdresse').value);
  try{
    const res = await fetch('actions/fiche_structure.php', {method:'POST', body:fd});
    const j = await res.json();
    if(j.success){
      msg.innerHTML = '<div style="background:#e9f7ee;color:#247a41;padding:10px 14px;border-radius:10px;font-size:.88rem;">\u2713 ' + j.message + '</div>';
      setTimeout(()=>location.reload(), 1200);
    } else {
      msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.88rem;">\u26a0 ' + j.message + '</div>';
    }
  }catch(e){
    msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.88rem;">Erreur serveur.</div>';
  }
}

window.SECTION_TITLES={dashboard:'Tableau de bord — <?= htmlspecialchars(structureNom()) ?>',inventaire:'Inventaire',peremptions:'Suivi des péremptions',demandes:'Demandes des pharmacies',autorisations:'Autorisations de commande externe',reequilibrage:'Signaler un rééquilibrage',subventions:'Signaler une subvention',stats:'Tendances de consommation',carte:'Carte des structures',parametres:'Paramètres du PRA'};

// Repondre a une demande d'autorisation / revoquer
async function autoriser(id, act, btn){
  let reponse = '';
  if(act === 'refuser'){
    reponse = prompt('Motif du refus (obligatoire) :');
    if(reponse === null) return;
    if(reponse.trim() === ''){ alert('Le motif est obligatoire.'); return; }
  } else if(act === 'revoquer'){
    if(!confirm("Révoquer cette autorisation ? La pharmacie ne pourra plus commander ce médicament auprès de ce PRA.")) return;
  } else {
    if(!confirm("Accorder cette autorisation ?")) return;
  }
  const fd = new FormData();
  fd.append('action', act); fd.append('id', id); fd.append('reponse', reponse);
  try{
    const res = await fetch('actions/autorisation.php', {method:'POST', body:fd});
    const j = await res.json();
    if(j.success){ location.reload(); }
    else alert('Erreur : ' + (j.message || 'action impossible'));
  }catch(e){ alert('Erreur serveur.'); }
}

// Le PRA oriente lui-meme une pharmacie vers un autre PRA
async function proposerAutorisation(){
  const msg = document.getElementById('auMsg');
  const fd = new FormData();
  fd.append('action','proposer');
  fd.append('pharmacie_id',  document.getElementById('auPharma').value);
  fd.append('medicament_id', document.getElementById('auMed').value);
  fd.append('pra_cible_id',  document.getElementById('auPra').value);
  fd.append('motif',         document.getElementById('auMotif').value);
  try{
    const res = await fetch('actions/autorisation.php', {method:'POST', body:fd});
    const j = await res.json();
    if(j.success){
      msg.innerHTML = '<div style="background:#eafaf0;color:#1a7a40;padding:10px 14px;border-radius:10px;font-size:.85rem;">✓ '+j.message+'</div>';
      setTimeout(()=>location.reload(), 1200);
    } else {
      msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ '+j.message+'</div>';
    }
  }catch(e){ msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">Erreur serveur.</div>'; }
}

async function ajouterStock(forcer){
  const msg=document.getElementById('asMsg');
  const dz=document.getElementById('asDoublonZone');
  const nom=document.getElementById('asNom').value.trim();
  const qte=document.getElementById('asQte').value;
  if(!forcer){ dz.style.display='none'; dz.innerHTML=''; }
  if(!nom){ msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ Le nom du médicament est requis.</div>'; return; }
  if(!qte||qte<1){ msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ Indiquez une quantité valide.</div>'; return; }

  const fd=new FormData();
  fd.append('nom',nom);
  fd.append('dosage',document.getElementById('asDosage').value.trim());
  fd.append('forme',document.getElementById('asForme').value);
  fd.append('categorie',document.getElementById('asCat').value.trim());
  fd.append('quantite',qte);
  fd.append('seuil_alerte',document.getElementById('asSeuil').value);
  fd.append('numero_lot',document.getElementById('asLot').value.trim());
  fd.append('date_peremption',document.getElementById('asPerem').value);
  if(forcer) fd.append('forcer','1');

  try{
    const res=await fetch('actions/ajout_stock.php',{method:'POST',body:fd});
    const j=await res.json();
    if(j.success){
      msg.innerHTML='<div style="background:#eafaf0;color:#1a7a40;padding:10px 14px;border-radius:10px;font-size:.85rem;">✓ '+j.message+' Rechargez la page pour voir la mise à jour.</div>';
      dz.style.display='none';
      setTimeout(()=>location.reload(),1200);
    } else if(j.doublon){
      // Detection de doublon : on propose de reutiliser un medicament existant ou de forcer
      let html='⚠️ <b>'+j.message+'</b><br><br>Médicament(s) similaire(s) déjà enregistré(s) :<br>';
      j.proches.forEach(p=>{
        html+='<div style="margin:6px 0;"><button class="btn btn-ok" onclick="reutiliserMed('+p.id+')">C\'est : '+p.label+'</button></div>';
      });
      html+='<br>Sinon, s\'il s\'agit réellement d\'un <b>nouveau</b> médicament différent :<br>';
      html+='<div style="margin-top:6px;"><button class="btn btn-bad" onclick="ajouterStock(true)">Non, créer quand même « '+nom+' »</button></div>';
      dz.innerHTML=html; dz.style.display='block'; msg.innerHTML='';
    } else {
      msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ '+j.message+'</div>';
    }
  }catch(e){ msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">Erreur serveur.</div>'; }
}

// Reutiliser un medicament existant (on envoie son id, pas un nouveau nom)
async function reutiliserMed(medId){
  const fd=new FormData();
  fd.append('medicament_id',medId);
  fd.append('quantite',document.getElementById('asQte').value);
  fd.append('numero_lot',document.getElementById('asLot').value.trim());
  fd.append('date_peremption',document.getElementById('asPerem').value);
  try{
    const res=await fetch('actions/ajout_stock.php',{method:'POST',body:fd});
    const j=await res.json();
    const msg=document.getElementById('asMsg');
    if(j.success){
      document.getElementById('asDoublonZone').style.display='none';
      msg.innerHTML='<div style="background:#eafaf0;color:#1a7a40;padding:10px 14px;border-radius:10px;font-size:.85rem;">✓ Stock ajouté au médicament existant. Rechargement...</div>';
      setTimeout(()=>location.reload(),1200);
    } else {
      msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ '+j.message+'</div>';
    }
  }catch(e){ document.getElementById('asMsg').innerHTML='Erreur serveur.'; }
}

function initCharts(){
  const green='#1faa4e',gridC='#eef7f1',textC='#5a8a6a';
  Chart.defaults.font.family="'Inter',sans-serif";Chart.defaults.color=textC;
  new Chart(document.getElementById('barChart'),{type:'bar',data:{labels:<?= json_encode(array_map(fn($s)=>$s['nom'],$stocks)) ?>,datasets:[{label:'Stock',data:<?= json_encode(array_map(fn($s)=>intval($s['quantite']),$stocks)) ?>,backgroundColor:green,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{grid:{color:gridC}},y:{grid:{display:false}}}}});
}
</script>
<script src="../assets/js/dashboard.js"></script>
<?php include __DIR__."/assistant_widget.php"; ?>
</body>
</html>
