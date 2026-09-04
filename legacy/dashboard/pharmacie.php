<?php
require_once '../config/session.php';
require_once '../config/db.php';
require_once '../config/helpers.php';
exigerRole('pharmacie');
// Animation d'accueil : une seule fois par session
$montrerAccueil = empty($_SESSION['accueil_phcie_vu']);
if ($montrerAccueil) { $_SESSION['accueil_phcie_vu'] = true; }
$sid = structureId();

// Fiche de ma structure (region, zone, contacts)
$maStructure = $pdo->prepare("SELECT nom, region, zone, telephone, email, adresse, latitude, longitude FROM structures WHERE id=?");
$maStructure->execute([$sid]);
$maStructure = $maStructure->fetch() ?: [];
$ficheIncomplete = empty($maStructure['region']) || $maStructure['region'] === 'A definir'
                   || empty($maStructure['zone']) || empty($maStructure['telephone']);

// PRA parent (avec coordonnees pour contact)
$pra = $pdo->prepare("SELECT p.id, p.nom, p.telephone, p.region, p.email FROM structures s LEFT JOIN structures p ON s.pra_parent = p.id WHERE s.id=?");
$pra->execute([$sid]);
$praInfo = $pra->fetch();
$praId = $praInfo['id'] ?? null;

// Catalogue : UNIQUEMENT les medicaments que le PRA parent a reellement en stock
// (plus de liste figee : on lit le stock en ligne du PRA)
$catalogue = $pdo->prepare("
    SELECT m.id, m.nom, m.forme, m.dosage, m.categorie, m.seuil_alerte,
           SUM(s.quantite) AS stock_pra
    FROM stocks s
    JOIN medicaments m ON s.medicament_id = m.id
    WHERE s.structure_id = ?
    GROUP BY m.id
    HAVING stock_pra > 0
    ORDER BY m.nom
");
$catalogue->execute([$praId]); $catalogue=$catalogue->fetchAll();

// Mes commandes
$commandes = $pdo->prepare("
    SELECT c.*, GROUP_CONCAT(CONCAT(m.nom,' ',m.dosage) SEPARATOR ', ') AS meds, SUM(l.quantite_demandee) AS qte
    FROM commandes c LEFT JOIN lignes_commande l ON l.commande_id=c.id
    LEFT JOIN medicaments m ON l.medicament_id=m.id
    WHERE c.demandeur_id=? GROUP BY c.id ORDER BY c.date_commande DESC
");
$commandes->execute([idUtilisateur()]); $commandes=$commandes->fetchAll();
$nbAttente=count(array_filter($commandes,fn($c)=>$c['statut']==='en_attente'));
$nbLivree=count(array_filter($commandes,fn($c)=>$c['statut']==='livree'));

// Mon stock
$stocks = $pdo->prepare("SELECT s.*, m.nom, m.dosage, m.forme FROM stocks s JOIN medicaments m ON s.medicament_id=m.id WHERE s.structure_id=?");
$stocks->execute([$sid]); $stocks=$stocks->fetchAll();

// Peremptions
$perem = $pdo->prepare("
    SELECT s.*, m.nom, m.dosage, DATEDIFF(s.date_peremption,CURDATE()) AS jr
    FROM stocks s JOIN medicaments m ON s.medicament_id=m.id
    WHERE s.structure_id=? AND s.date_peremption IS NOT NULL AND DATEDIFF(s.date_peremption,CURDATE())<=90 ORDER BY jr ASC
");
$perem->execute([$sid]); $perem=$perem->fetchAll();

// Liste commandable = exactement ce que le PRA a en stock (le catalogue ci-dessus)
$meds = $catalogue;
$retour = '../pharmacie.php';

// ---- Autorisations de commander aupres d'un AUTRE PRA ----
$mesAutorisations = [];   // toutes mes demandes (avec statut)
$catalogueExterne = [];   // medicaments autorises ET reellement en stock chez le PRA cible
$autresPra = [];
$tousMeds  = [];
try {
    $a = $pdo->prepare("
        SELECT a.*, pc.nom AS pra_cible_nom, CONCAT(m.nom,' ',m.dosage) AS med_nom
        FROM autorisations_pra a
        JOIN structures pc ON a.pra_cible_id = pc.id
        JOIN medicaments m ON a.medicament_id = m.id
        WHERE a.pharmacie_id = ?
        ORDER BY FIELD(a.statut,'en_attente','accordee','refusee','revoquee'), a.created_at DESC
    ");
    $a->execute([$sid]);
    $mesAutorisations = $a->fetchAll();

    // Pour chaque autorisation accordee : le PRA cible a-t-il le produit en stock ?
    $q = $pdo->prepare("SELECT COALESCE(SUM(quantite),0) FROM stocks WHERE structure_id=? AND medicament_id=?");
    foreach ($mesAutorisations as $x) {
        if ($x['statut'] !== 'accordee') continue;
        $q->execute([$x['pra_cible_id'], $x['medicament_id']]);
        $x['stock_dispo'] = (int)$q->fetchColumn();
        $catalogueExterne[] = $x;
    }

    // Autres PRA (hors mon PRA de rattachement) + tous les medicaments referencés
    $ap = $pdo->prepare("SELECT id, nom, region FROM structures WHERE type='pra' AND id<>? AND statut='active' ORDER BY nom");
    $ap->execute([$praId ?: 0]);
    $autresPra = $ap->fetchAll();
    $tousMeds  = $pdo->query("SELECT id, nom, dosage FROM medicaments ORDER BY nom")->fetchAll();
} catch (Exception $e) {}
$nbAutorAcc = count($catalogueExterne);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>La Pharmacie Sénégalaise — Pharmacie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<?php if($montrerAccueil) { $accueilTitre = 'Bienvenue, ' . nomUtilisateur();
$accueilSousTitre = structureNom() . ' — Espace pharmacie'; include __DIR__.'/welcome_role.php'; } ?>
<div class="app">
  <aside class="sb">
    <div class="sb-logo"><div class="sb-logo-ring"><svg viewBox="0 0 100 120" fill="none"><ellipse cx="50" cy="52" rx="38" ry="12" fill="#1faa4e"/><rect x="45" y="52" width="10" height="56" rx="4" fill="#1faa4e"/><ellipse cx="50" cy="112" rx="24" ry="6" fill="#1faa4e"/><path d="M50 6 Q70 20 68 38 Q60 30 50 28 Q40 30 32 38 Q30 20 50 6Z" fill="#1faa4e"/><circle cx="50" cy="18" r="7" fill="#1faa4e"/></svg></div><div class="sb-name">La Pharmacie<br>Sénégalaise</div></div>
    <div class="sb-user"><div class="sb-av"><?= htmlspecialchars(initiales()) ?></div><div><div class="sb-un"><?= htmlspecialchars(structureNom()) ?></div><div class="sb-ur">Pharmacie agréée</div></div></div>
    <nav class="sb-nav">
      <div class="sb-sec">Principal</div>
      <a class="sb-item active" onclick="nav('dashboard',this)" href="#"><i class="ti ti-dashboard"></i>Tableau de bord</a>
      <div class="sb-sec">Catalogue</div>
      <a class="sb-item" onclick="nav('catalogue',this)" href="#"><i class="ti ti-pill"></i>Médicaments dispo.</a>
      <a class="sb-item" onclick="nav('commander',this)" href="#"><i class="ti ti-shopping-cart"></i>Commander</a>
      <div class="sb-sec">Suivi</div>
      <a class="sb-item" onclick="nav('commandes',this)" href="#"><i class="ti ti-package"></i>Mes commandes<?php if($nbAttente):?><span class="sb-badge-w"><?= $nbAttente ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('stock',this)" href="#"><i class="ti ti-building-warehouse"></i>Mon stock</a>
      <a class="sb-item" onclick="nav('externe',this)" href="#"><i class="ti ti-shield-check"></i>Commander ailleurs<?php if($nbAutorAcc):?><span class="sb-badge-w"><?= $nbAutorAcc ?></span><?php endif;?></a>
      <a class="sb-item" onclick="nav('carte',this);initLpsMapWhenVisible();" href="#"><i class="ti ti-map-2"></i>Carte</a>
      <a class="sb-item" onclick="nav('vente',this)" href="#"><i class="ti ti-cash-register"></i>Enregistrer une vente</a>
      <a class="sb-item" onclick="nav('peremptions',this)" href="#"><i class="ti ti-clock"></i>Péremptions<?php if($perem):?><span class="sb-badge-w"><?= count($perem) ?></span><?php endif;?></a>
      <div class="sb-sec">Intelligence</div>
      <a class="sb-item" onclick="nav('stats',this)" href="#"><i class="ti ti-chart-line"></i>Tendances</a>
      <div class="sb-sec">Système</div>
      <a class="sb-item" onclick="nav('parametres',this)" href="#"><i class="ti ti-settings"></i>Paramètres</a>
    </nav>
    <div class="sb-bot"><a class="sb-logout" href="../auth/logout.php"><i class="ti ti-logout"></i>Déconnexion</a></div>
  </aside>
  <div class="main">
    <div class="top"><div class="top-title" id="topTitle">Tableau de bord</div><div class="top-r"><div class="top-date"><i class="ti ti-calendar-event"></i><?= function_exists('strftime_fr') ? strftime_fr() : date('d/m/Y') ?></div><div class="bell"><i class="ti ti-bell"></i></div></div></div>
    <div class="content">
      <?php if(isset($_GET['ok'])): ?>
        <div style="background:#eafaf0;color:#1a7a40;border:1px solid #b8ddc4;border-radius:12px;padding:13px 18px;margin-bottom:18px;font-weight:600;">✓ Votre commande a été transmise à votre PRA. Vous pouvez en suivre l'état dans « Mes commandes ».</div>
      <?php elseif(isset($_GET['err'])): ?>
        <div style="background:#fdeded;color:#a32d2d;border:1px solid #e8a09d;border-radius:12px;padding:13px 18px;margin-bottom:18px;font-weight:600;">⚠️ <?= htmlspecialchars($_GET['err']) ?></div>
      <?php endif; ?>


      <div id="dashboard" class="section active">
        <div class="stats">
          <div class="stat stat-hero">
            <div class="stat-top"><span class="stat-lbl">Médicaments disponibles</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= nb(count($catalogue)) ?></div>
            <div class="stat-pill stat-pill-hero"><i class="ti ti-pill"></i>Chez votre PRA</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Commandes en cours</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--warn);"><?= $nbAttente ?></div>
            <div class="stat-pill" style="background:var(--warn-bg);color:#9a6a0a;"><i class="ti ti-package"></i>En traitement</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Commandes livrées</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num"><?= $nbLivree ?></div>
            <div class="stat-pill" style="background:var(--green-pale);color:var(--green-d);"><i class="ti ti-check"></i>Réceptionnées</div>
          </div>
          <div class="stat">
            <div class="stat-top"><span class="stat-lbl">Péremptions proches</span><span class="stat-arrow"><i class="ti ti-arrow-up-right"></i></span></div>
            <div class="stat-num" style="color:var(--danger);"><?= count($perem) ?></div>
            <div class="stat-pill" style="background:var(--danger-bg);color:#a32d2d;"><i class="ti ti-clock"></i>À écouler</div>
          </div>
        </div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-package"></i>Mes dernières commandes</div><button class="btn btn-pr" onclick="nav('commander',document.querySelectorAll('.sb-item')[2])">+ Commander</button></div>
          <table><thead><tr><th>Réf.</th><th>Médicament</th><th>Qté</th><th>Date</th><th>Statut</th></tr></thead><tbody>
          <?php if(!$commandes):?><tr><td colspan="5"><div class="empty">Aucune commande</div></td></tr><?php endif;?>
          <?php foreach(array_slice($commandes,0,6) as $c): $sp=$c['statut']==='livree'?'p-ok':($c['statut']==='en_attente'?'p-warn':($c['statut']==='rejetee'?'p-bad':'p-info')); ?>
            <tr><td><strong><?= htmlspecialchars($c['reference']) ?></strong></td><td><?= htmlspecialchars($c['meds']??'—') ?></td>
            <td><?= nb($c['qte']) ?></td><td><?= date('d/m/Y',strtotime($c['date_commande'])) ?></td>
            <td><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td></tr>
          <?php endforeach;?></tbody></table>
        </div>
      </div>

      <div id="catalogue" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-pill"></i>Médicaments disponibles via le PRA</div></div>
        <table><thead><tr><th>Médicament</th><th>Forme</th><th>Dosage</th><th>Catégorie</th><th>Stock PRA</th><th>Disponibilité</th><th></th></tr></thead><tbody>
        <?php if(!$catalogue):?><tr><td colspan="7"><div class="empty">Votre PRA de rattachement n'a encore mis aucun médicament en ligne.</div></td></tr><?php endif;?>
        <?php foreach($catalogue as $m): $dispo=$m['stock_pra']; ?>
          <tr><td><strong><?= htmlspecialchars($m['nom']) ?></strong></td><td><?= ucfirst($m['forme']) ?></td><td><?= htmlspecialchars($m['dosage']) ?></td><td><?= htmlspecialchars($m['categorie']) ?></td>
          <td><?= nb($dispo) ?></td>
          <td><?php if($dispo==0):?><span class="pill p-bad">Rupture</span><?php elseif($dispo<$m['seuil_alerte']):?><span class="pill p-warn">Stock faible</span><?php else:?><span class="pill p-ok">Disponible</span><?php endif;?></td>
          <td><?php if($dispo>0):?><button class="btn btn-pr" onclick="nav('commander',document.querySelectorAll('.sb-item')[2])">Commander</button><?php else:?><button class="btn" disabled>Indisponible</button><?php endif;?></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="commander" class="section"><div class="card" style="max-width:560px;">
        <div class="ch"><div class="ct"><i class="ti ti-shopping-cart"></i>Nouvelle commande</div></div>
        <div class="cp">
        <?php if($praId): ?>
          <div style="display:flex;align-items:center;gap:12px;background:var(--green-pale);border:1px solid var(--green-border);border-radius:11px;padding:12px 16px;margin-bottom:16px;">
            <i class="ti ti-phone" style="font-size:1.4rem;color:var(--green-d);"></i>
            <div style="font-size:0.88rem;">
              <div>Votre PRA de rattachement : <strong><?= htmlspecialchars($praInfo['nom']) ?></strong><?= $praInfo['region']?' ('.htmlspecialchars($praInfo['region']).')':'' ?></div>
              <div style="color:var(--mid);">Pour tout renseignement : <strong><?= htmlspecialchars($praInfo['telephone'] ?: 'numéro non communiqué') ?></strong></div>
            </div>
            <div style="margin-left:auto;"><?= boutonsContact($praInfo['telephone']??'', $praInfo['email']??'') ?></div>
          </div>
        <?php endif; ?>
        <?php if(!$praId): ?>
          <div class="empty">Votre pharmacie n'est rattachée à aucun PRA. Veuillez contacter le Ministère de la Santé pour le rattachement.</div>
        <?php elseif(!$meds): ?>
          <div class="empty">Votre PRA de rattachement n'a actuellement aucun médicament disponible en ligne. Vous pouvez le contacter directement, ou réessayer plus tard.</div>
        <?php else: ?>
          <form action="actions/signaler.php" method="POST">
          <input type="hidden" name="type" value="commande"><input type="hidden" name="retour" value="<?= $retour ?>">
          <div class="fg"><label class="lbl">Médicament disponible chez votre PRA</label>
            <select class="sel" name="medicament_id" id="cmdMed" required>
              <option value="">— Sélectionner —</option>
              <?php foreach($meds as $m):?><option value="<?= $m['id'] ?>" data-dispo="<?= (int)$m['stock_pra'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['dosage']) ?> — <?= nb($m['stock_pra']) ?> en stock</option><?php endforeach;?>
            </select>
            <div class="hint">Seuls les médicaments réellement mis en ligne par votre PRA apparaissent ici.</div>
          </div>
          <div class="fg"><label class="lbl">Quantité demandée</label><input class="inp" type="number" name="quantite" id="cmdQte" value="50" min="1" required>
            <div class="hint" id="cmdDispoHint"></div>
          </div>
          <div class="fg"><label class="lbl">Niveau d'urgence</label><select class="sel" name="urgence"><option value="normale">Normale</option><option value="alerte">Alerte</option><option value="critique">Critique</option></select></div>
          <div class="fg"><label class="lbl">Justification du besoin</label><textarea class="inp" name="notes" rows="3" placeholder="Renouvellement stock, anticipation pic saisonnier..."></textarea></div>
          <button class="btn btn-pr" type="submit" style="width:100%;padding:12px;"><i class="ti ti-send"></i> Soumettre la commande</button>
          </form>
        <?php endif; ?>
        </div>

      </div></div>

      <div id="commandes" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-package"></i>Historique de mes commandes</div></div>
        <table><thead><tr><th>Réf.</th><th>Médicament</th><th>Qté</th><th>Date</th><th>Urgence</th><th>Statut</th></tr></thead><tbody>
        <?php if(!$commandes):?><tr><td colspan="6"><div class="empty">Aucune commande</div></td></tr><?php endif;?>
        <?php foreach($commandes as $c): $sp=$c['statut']==='livree'?'p-ok':($c['statut']==='en_attente'?'p-warn':($c['statut']==='rejetee'?'p-bad':'p-info')); $up=$c['urgence']==='critique'?'p-bad':($c['urgence']==='alerte'?'p-warn':'p-info'); ?>
          <tr><td><strong><?= htmlspecialchars($c['reference']) ?></strong></td><td><?= htmlspecialchars($c['meds']??'—') ?></td>
          <td><?= nb($c['qte']) ?></td><td><?= date('d/m/Y',strtotime($c['date_commande'])) ?></td>
          <td><span class="pill <?= $up ?>"><?= ucfirst($c['urgence']) ?></span></td>
          <td><span class="pill <?= $sp ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="stock" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-building-warehouse"></i>Mon stock actuel</div></div>
        <table><thead><tr><th>Médicament</th><th>Forme</th><th>Stock</th><th>Lot</th><th>Péremption</th></tr></thead><tbody>
        <?php if(!$stocks):?><tr><td colspan="5"><div class="empty">Stock vide</div></td></tr><?php endif;?>
        <?php foreach($stocks as $s):?>
          <tr><td><strong><?= htmlspecialchars($s['nom'].' '.$s['dosage']) ?></strong></td><td><?= ucfirst($s['forme']) ?></td>
          <td><?= nb($s['quantite']) ?></td><td><?= htmlspecialchars($s['numero_lot']??'—') ?></td>
          <td><?= $s['date_peremption']?date('m/Y',strtotime($s['date_peremption'])):'—' ?></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="externe" class="section">
        <div class="ia-box"><div class="ia-ic"><i class="ti ti-shield-check"></i></div><div><div class="ia-t">Commander auprès d'un autre PRA</div><div class="ia-d">Lorsque votre PRA de rattachement ne dispose pas d'un médicament, vous pouvez lui demander l'autorisation de le commander auprès d'un autre PRA. L'autorisation porte sur un médicament précis et <strong>reste valable jusqu'à révocation</strong>. Le PRA sollicité reste libre d'accepter ou de refuser votre commande.</div></div></div>

        <?php if($catalogueExterne): ?>
        <div class="card">
          <div class="ch"><div class="ct"><i class="ti ti-basket"></i>Médicaments autorisés</div><span class="pill p-ok"><?= count($catalogueExterne) ?> autorisation(s)</span></div>
          <table><thead><tr><th>Médicament</th><th>PRA sollicité</th><th>Stock dispo.</th><th>Quantité</th><th>Action</th></tr></thead><tbody>
          <?php foreach($catalogueExterne as $i=>$e): ?>
            <tr>
            <td><strong><?= htmlspecialchars($e['med_nom']) ?></strong></td>
            <td><?= htmlspecialchars($e['pra_cible_nom']) ?></td>
            <td><?php if($e['stock_dispo']>0): ?><span class="pill p-ok"><?= nb($e['stock_dispo']) ?></span><?php else: ?><span class="pill p-bad">Rupture</span><?php endif; ?></td>
            <td>
              <?php if($e['stock_dispo']>0): ?>
              <form action="actions/signaler.php" method="POST" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="type" value="commande">
                <input type="hidden" name="retour" value="<?= $retour ?>">
                <input type="hidden" name="medicament_id" value="<?= $e['medicament_id'] ?>">
                <input type="hidden" name="pra_cible_id" value="<?= $e['pra_cible_id'] ?>">
                <input class="inp" type="number" name="quantite" value="10" min="1" max="<?= $e['stock_dispo'] ?>" style="width:90px;padding:7px 10px;">
                <select class="sel" name="urgence" style="width:120px;padding:7px 10px;">
                  <option value="normale">Normale</option><option value="alerte">Alerte</option><option value="critique">Critique</option>
                </select>
            <?php else: ?>
              <span style="font-size:.82rem;color:var(--muted);">—</span>
            <?php endif; ?>
            </td>
            <td class="ac">
              <?php if($e['stock_dispo']>0): ?>
                <button class="btn btn-ok" type="submit"><i class="ti ti-send"></i> Commander</button>
              </form>
              <?php else: ?><span style="font-size:.82rem;color:var(--muted);">Indisponible</span><?php endif; ?>
            </td></tr>
          <?php endforeach;?></tbody></table>
        </div>
        <?php endif; ?>

        <div class="two">
          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-mail-forward"></i>Demander une autorisation</div></div>
            <div class="cp">
              <?php if(!$praId): ?>
                <div class="empty">Votre pharmacie n'est rattachée à aucun PRA.</div>
              <?php else: ?>
              <div class="fg"><label class="lbl">Médicament recherché</label>
                <select class="sel" id="exMed">
                  <option value="">— Sélectionner —</option>
                  <?php foreach($tousMeds as $m):?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nom'].' '.$m['dosage']) ?></option><?php endforeach;?>
                </select>
              </div>
              <div class="fg"><label class="lbl">PRA à solliciter</label>
                <select class="sel" id="exPra">
                  <option value="">— Sélectionner —</option>
                  <?php foreach($autresPra as $p):?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom'].($p['region']?' ('.$p['region'].')':'')) ?></option><?php endforeach;?>
                </select>
                <div class="hint">Consultez la <strong>Carte</strong> pour repérer les PRA proches et leurs coordonnées.</div>
              </div>
              <div class="fg"><label class="lbl">Justification</label><textarea class="inp" id="exMotif" rows="3" placeholder="Ex. : produit indisponible chez mon PRA depuis 2 semaines, forte demande des patients."></textarea></div>
              <button class="btn btn-pr" style="width:100%;padding:12px;" onclick="demanderAutorisation()"><i class="ti ti-send"></i> Envoyer la demande à mon PRA</button>
              <div id="exMsg" style="margin-top:14px;"></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card">
            <div class="ch"><div class="ct"><i class="ti ti-history"></i>Mes demandes</div></div>
            <table><thead><tr><th>Médicament</th><th>PRA</th><th>Statut</th></tr></thead><tbody>
            <?php if(!$mesAutorisations):?><tr><td colspan="3"><div class="empty">Aucune demande</div></td></tr><?php endif;?>
            <?php foreach($mesAutorisations as $a):
              $p = ['en_attente'=>'p-warn','accordee'=>'p-ok','refusee'=>'p-bad','revoquee'=>'p-gray'][$a['statut']] ?? 'p-info';
              $lbl = ['en_attente'=>'En attente','accordee'=>'Accordée','refusee'=>'Refusée','revoquee'=>'Révoquée'][$a['statut']] ?? $a['statut']; ?>
              <tr><td><?= htmlspecialchars($a['med_nom']) ?></td>
              <td><?= htmlspecialchars($a['pra_cible_nom']) ?></td>
              <td><span class="pill <?= $p ?>"><?= $lbl ?></span>
                  <?php if($a['reponse']): ?><div style="font-size:.78rem;color:var(--muted);margin-top:3px;"><?= htmlspecialchars($a['reponse']) ?></div><?php endif; ?>
              </td></tr>
            <?php endforeach;?></tbody></table>
          </div>
        </div>
      </div>

      <div id="vente" class="section">
        <div class="ia-box" style="background:linear-gradient(135deg,#eafaf0,#f7fdf9);border-color:#b8ddc4;">
          <div class="ia-ic" style="background:var(--green);"><i class="ti ti-cash-register"></i></div>
          <div><div class="ia-t" style="color:var(--green-deep);">Enregistrer une vente</div><div class="ia-d" style="color:var(--green-d);">Chaque vente décrémente automatiquement votre stock et alimente les statistiques de consommation. Ces données permettent à l'État et aux PRA de mieux anticiper les besoins de votre zone.</div></div>
        </div>
        <div class="card" style="max-width:520px;">
          <div class="ch"><div class="ct"><i class="ti ti-shopping-bag"></i>Nouvelle vente</div></div>
          <div class="cp">
            <div class="fg"><label class="lbl">Médicament vendu</label>
              <select class="sel" id="venteMymed">
                <option value="">— Sélectionner —</option>
                <?php foreach($stocks as $s): ?>
                  <option value="<?= $s['medicament_id'] ?>" data-stock="<?= $s['quantite'] ?>"><?= htmlspecialchars($s['nom'].' '.$s['dosage']) ?> (stock : <?= $s['quantite'] ?>)</option>
                <?php endforeach; ?>
              </select>
              <?php if(!$stocks): ?><div class="hint">Votre stock est vide. Passez d'abord une commande.</div><?php endif; ?>
            </div>
            <div class="fg"><label class="lbl">Quantité vendue</label><input class="inp" type="number" id="venteQte" value="1" min="1"></div>
            <button class="btn btn-pr" style="width:100%;padding:12px;" onclick="enregistrerVente()"><i class="ti ti-check"></i> Valider la vente</button>
            <div id="venteMsg" style="margin-top:14px;"></div>
          </div>
        </div>
      </div>

      <div id="peremptions" class="section"><div class="card">
        <div class="ch"><div class="ct"><i class="ti ti-clock"></i>Médicaments proches de la péremption</div></div>
        <table><thead><tr><th>Médicament</th><th>Lot</th><th>Quantité</th><th>Péremption</th><th>Jours restants</th></tr></thead><tbody>
        <?php if(!$perem):?><tr><td colspan="5"><div class="empty">Aucune péremption proche</div></td></tr><?php endif;?>
        <?php foreach($perem as $p):?>
          <tr><td><strong><?= htmlspecialchars($p['nom'].' '.$p['dosage']) ?></strong></td><td><?= htmlspecialchars($p['numero_lot']??'—') ?></td>
          <td><?= nb($p['quantite']) ?></td><td><?= date('d/m/Y',strtotime($p['date_peremption'])) ?></td>
          <td><span class="pill <?= $p['jr']<=30?'p-bad':'p-warn' ?>"><?= $p['jr'] ?> jours</span></td></tr>
        <?php endforeach;?></tbody></table>
      </div></div>

      <div id="stats" class="section">
        <div class="card"><div class="cp"><div class="ct"><i class="ti ti-chart-bar"></i>Mes commandes par médicament</div><div class="chart-h"><canvas id="barChart"></canvas></div></div></div>
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

        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-adjustments"></i>Seuils & rappels</div></div><div class="cp"><div class="grid2">
          <div class="fg"><label class="lbl">Seuil de réapprovisionnement</label><div class="inp-row"><input class="inp" type="number" value="100"><span class="suffix">unités</span></div></div>
          <div class="fg"><label class="lbl">Alerte de péremption</label><div class="inp-row"><input class="inp" type="number" value="60"><span class="suffix">jours</span></div></div>
        </div></div></div>
        <div class="card"><div class="ch"><div class="ct"><i class="ti ti-bell"></i>Notifications</div></div><div class="cp">
          <div class="toggle-row"><div><div class="tr-txt">Rappel automatique de réapprovisionnement</div></div><div class="toggle on" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
          <div class="toggle-row"><div><div class="tr-txt">Alerte de péremption proche</div></div><div class="toggle on" onclick="this.classList.toggle('on')"><div class="toggle-dot"></div></div></div>
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

window.SECTION_TITLES={dashboard:'Tableau de bord',catalogue:'Médicaments disponibles',commander:'Nouvelle commande',commandes:'Mes commandes',stock:'Mon stock',externe:'Commander auprès d\'un autre PRA',vente:'Enregistrer une vente',carte:'Carte des structures',peremptions:'Péremptions',stats:'Tendances',parametres:'Paramètres'};

// Demander a mon PRA l'autorisation de commander ailleurs
async function demanderAutorisation(){
  const msg = document.getElementById('exMsg');
  const med = document.getElementById('exMed').value;
  const pra = document.getElementById('exPra').value;
  const motif = document.getElementById('exMotif').value.trim();
  if(!med || !pra){ msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ Sélectionnez un médicament et un PRA.</div>'; return; }
  if(!motif){ msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ La justification est obligatoire.</div>'; return; }
  const fd = new FormData();
  fd.append('action','demander');
  fd.append('medicament_id', med);
  fd.append('pra_cible_id', pra);
  fd.append('motif', motif);
  try{
    const res = await fetch('actions/autorisation.php', {method:'POST', body:fd});
    const j = await res.json();
    if(j.success){
      msg.innerHTML = '<div style="background:#eafaf0;color:#1a7a40;padding:10px 14px;border-radius:10px;font-size:.85rem;">✓ '+j.message+'</div>';
      setTimeout(()=>location.reload(), 1400);
    } else {
      msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ '+j.message+'</div>';
    }
  }catch(e){ msg.innerHTML = '<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">Erreur serveur.</div>'; }
}
async function enregistrerVente(){
  const sel=document.getElementById('venteMymed');
  const qte=document.getElementById('venteQte');
  const msg=document.getElementById('venteMsg');
  const medId=sel.value;
  if(!medId){ msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ Sélectionnez un médicament.</div>'; return; }
  const fd=new FormData(); fd.append('medicament_id',medId); fd.append('quantite',qte.value);
  try{
    const res=await fetch('actions/vente.php',{method:'POST',body:fd});
    const j=await res.json();
    if(j.success){
      msg.innerHTML='<div style="background:#eafaf0;color:#1a7a40;padding:10px 14px;border-radius:10px;font-size:.85rem;">✅ Vente enregistrée. Nouveau stock : <b>'+j.nouveau_stock+'</b>. Rechargez la page pour mettre à jour les tableaux.</div>';
      // Mettre a jour l'option dans le select
      const opt=sel.querySelector('option[value="'+medId+'"]');
      if(opt){ opt.dataset.stock=j.nouveau_stock; opt.textContent=opt.textContent.replace(/\(stock : \d+\)/,'(stock : '+j.nouveau_stock+')'); }
      qte.value=1;
    } else {
      msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">⚠️ '+j.message+'</div>';
    }
  }catch(e){ msg.innerHTML='<div style="background:#fdeded;color:#a32d2d;padding:10px 14px;border-radius:10px;font-size:.85rem;">Erreur serveur.</div>'; }
}
function initCharts(){
  const green='#1faa4e',gridC='#eef7f1',textC='#5a8a6a';
  Chart.defaults.font.family="'Inter',sans-serif";Chart.defaults.color=textC;
  const m={};<?php foreach($commandes as $c){ if($c['meds']){ echo "(function(){var k=".json_encode(explode(' ',$c['meds'])[0]).";m[k]=(m[k]||0)+".intval($c['qte']).";})();"; } }?>
  new Chart(document.getElementById('barChart'),{type:'bar',data:{labels:Object.keys(m),datasets:[{label:'Quantité commandée',data:Object.values(m),backgroundColor:green,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{grid:{color:gridC}},x:{grid:{display:false}}}}});
}
</script>
<script src="../assets/js/dashboard.js"></script>
<?php include __DIR__."/assistant_widget.php"; ?>
</body>
</html>
