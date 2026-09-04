<?php
// ============================================================
//  AUTORISATION DE COMMANDER AUPRES D'UN AUTRE PRA
//  Actions :
//   - demander  (pharmacie) : sollicite son PRA de rattachement
//   - proposer  (PRA)       : le PRA accorde d'office a une pharmacie
//   - accorder  (PRA)       : accepte une demande en attente
//   - refuser   (PRA)       : refuse une demande en attente
//   - revoquer  (PRA)       : retire une autorisation deja accordee
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
require_once '../../config/mail.php';
require_once '../../config/mailer.php';
exigerConnexion();
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$sid    = structureId();
$role   = roleUtilisateur();

try {

// ---------- 1. LA PHARMACIE DEMANDE UNE AUTORISATION ----------
if ($action === 'demander') {
    exigerRole('pharmacie');
    $medId   = intval($_POST['medicament_id'] ?? 0);
    $praCible= intval($_POST['pra_cible_id'] ?? 0);
    $motif   = trim($_POST['motif'] ?? '');

    if (!$medId || !$praCible) throw new Exception("Médicament et PRA cible obligatoires.");
    if ($motif === '')          throw new Exception("Veuillez justifier votre demande.");

    // PRA de rattachement
    $p = $pdo->prepare("SELECT pra_parent FROM structures WHERE id=?");
    $p->execute([$sid]);
    $praOrigine = (int)$p->fetchColumn();
    if (!$praOrigine)              throw new Exception("Votre pharmacie n'est rattachée à aucun PRA.");
    if ($praCible === $praOrigine) throw new Exception("Ce PRA est déjà votre PRA de rattachement : aucune autorisation n'est nécessaire.");

    // Deja une demande en attente ou une autorisation active pour ce medicament ?
    $ex = $pdo->prepare("SELECT statut FROM autorisations_pra
                         WHERE pharmacie_id=? AND medicament_id=? AND pra_cible_id=?
                           AND statut IN ('en_attente','accordee') LIMIT 1");
    $ex->execute([$sid, $medId, $praCible]);
    if ($st = $ex->fetchColumn()) {
        throw new Exception($st === 'accordee'
            ? "Vous disposez déjà d'une autorisation active pour ce médicament auprès de ce PRA."
            : "Une demande est déjà en cours d'examen pour ce médicament.");
    }

    $pdo->prepare("INSERT INTO autorisations_pra
        (pharmacie_id, medicament_id, pra_origine_id, pra_cible_id, initiateur, motif)
        VALUES (?,?,?,?,'pharmacie',?)")
        ->execute([$sid, $medId, $praOrigine, $praCible, $motif]);

    // Prevenir le PRA de rattachement
    $info = infosAutorisation($pdo, $sid, $medId, $praOrigine, $praCible);
    if ($info['email_pra_origine']) {
        envoyerEmail($info['email_pra_origine'],
            'Demande d\'autorisation de commande externe - La Pharmacie Senegalaise',
            emailAutorisationPra('demande', $info, $motif));
    }
    echo json_encode(['success'=>true, 'message'=>"Demande transmise à votre PRA de rattachement."]);
}

// ---------- 2. LE PRA PROPOSE / ACCORDE D'OFFICE ----------
elseif ($action === 'proposer') {
    exigerRole('pra');
    $pharmaId = intval($_POST['pharmacie_id'] ?? 0);
    $medId    = intval($_POST['medicament_id'] ?? 0);
    $praCible = intval($_POST['pra_cible_id'] ?? 0);
    $motif    = trim($_POST['motif'] ?? '');

    if (!$pharmaId || !$medId || !$praCible) throw new Exception("Pharmacie, médicament et PRA cible obligatoires.");
    if ($praCible === $sid) throw new Exception("Vous ne pouvez pas orienter une pharmacie vers vous-même.");

    // La pharmacie doit bien etre sous ma juridiction
    $c = $pdo->prepare("SELECT COUNT(*) FROM structures WHERE id=? AND pra_parent=?");
    $c->execute([$pharmaId, $sid]);
    if (!$c->fetchColumn()) throw new Exception("Cette pharmacie n'est pas sous votre juridiction.");

    // Annuler une eventuelle demande en attente sur le meme couple
    $pdo->prepare("UPDATE autorisations_pra SET statut='revoquee', traite_at=NOW()
                   WHERE pharmacie_id=? AND medicament_id=? AND statut IN ('en_attente','accordee')")
        ->execute([$pharmaId, $medId]);

    $pdo->prepare("INSERT INTO autorisations_pra
        (pharmacie_id, medicament_id, pra_origine_id, pra_cible_id, initiateur, motif, statut, traite_at)
        VALUES (?,?,?,?,'pra',?, 'accordee', NOW())")
        ->execute([$pharmaId, $medId, $sid, $praCible, $motif]);

    $info = infosAutorisation($pdo, $pharmaId, $medId, $sid, $praCible);
    if ($info['email_pharmacie']) {
        envoyerEmail($info['email_pharmacie'],
            'Autorisation de commande externe accordee - La Pharmacie Senegalaise',
            emailAutorisationPra('accordee', $info, $motif));
    }
    // Prevenir le PRA sollicite qu'il peut etre contacte
    if ($info['email_pra_cible']) {
        envoyerEmail($info['email_pra_cible'],
            'Une pharmacie est autorisee a vous solliciter - La Pharmacie Senegalaise',
            emailAutorisationPra('info_cible', $info, $motif));
    }
    echo json_encode(['success'=>true, 'message'=>"Autorisation accordée. La pharmacie et le PRA sollicité ont été informés."]);
}

// ---------- 3. LE PRA REPOND A UNE DEMANDE / REVOQUE ----------
elseif (in_array($action, ['accorder','refuser','revoquer'])) {
    exigerRole('pra');
    $id      = intval($_POST['id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    if (!$id) throw new Exception("Autorisation introuvable.");

    $a = $pdo->prepare("SELECT * FROM autorisations_pra WHERE id=? AND pra_origine_id=?");
    $a->execute([$id, $sid]);
    $aut = $a->fetch();
    if (!$aut) throw new Exception("Cette autorisation ne relève pas de votre juridiction.");

    if ($action === 'refuser' && $reponse === '') {
        throw new Exception("Veuillez indiquer le motif du refus.");
    }

    $map = ['accorder'=>'accordee', 'refuser'=>'refusee', 'revoquer'=>'revoquee'];
    $pdo->prepare("UPDATE autorisations_pra SET statut=?, reponse=?, traite_at=NOW() WHERE id=?")
        ->execute([$map[$action], $reponse, $id]);

    $info = infosAutorisation($pdo, $aut['pharmacie_id'], $aut['medicament_id'], $sid, $aut['pra_cible_id']);
    if ($info['email_pharmacie']) {
        envoyerEmail($info['email_pharmacie'],
            'Autorisation de commande externe - La Pharmacie Senegalaise',
            emailAutorisationPra($map[$action], $info, $reponse));
    }
    // Si l'autorisation est accordee, prevenir aussi le PRA sollicite
    if ($map[$action] === 'accordee' && $info['email_pra_cible']) {
        envoyerEmail($info['email_pra_cible'],
            'Une pharmacie est autorisee a vous solliciter - La Pharmacie Senegalaise',
            emailAutorisationPra('info_cible', $info, $aut['motif']));
    }
    echo json_encode(['success'=>true, 'statut'=>$map[$action]]);
}

else {
    throw new Exception("Action inconnue.");
}

} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}

// ------------------------------------------------------------
// Recupere les libelles + emails utiles pour les notifications
// ------------------------------------------------------------
function infosAutorisation($pdo, $pharmaId, $medId, $praOrigineId, $praCibleId) {
    $q = $pdo->prepare("SELECT nom FROM structures WHERE id=?");
    $q->execute([$pharmaId]);  $pharma = $q->fetchColumn();
    $q->execute([$praOrigineId]); $praO = $q->fetchColumn();
    $q->execute([$praCibleId]);   $praC = $q->fetchColumn();

    $m = $pdo->prepare("SELECT CONCAT(nom,' ',dosage) FROM medicaments WHERE id=?");
    $m->execute([$medId]); $med = $m->fetchColumn();

    $e = $pdo->prepare("SELECT email, prenom FROM utilisateurs WHERE structure_id=? AND statut='actif' LIMIT 1");
    $e->execute([$pharmaId]); $up = $e->fetch();
    $e->execute([$praOrigineId]); $uo = $e->fetch();
    $e->execute([$praCibleId]);   $uc = $e->fetch();

    return [
        'pharmacie'         => $pharma,
        'pra_origine'       => $praO,
        'pra_cible'         => $praC,
        'medicament'        => $med,
        'email_pharmacie'   => $up['email']  ?? null,
        'prenom_pharmacie'  => $up['prenom'] ?? '',
        'email_pra_origine' => $uo['email']  ?? null,
        'prenom_pra'        => $uo['prenom'] ?? '',
        'email_pra_cible'   => $uc['email']  ?? null,
        'prenom_pra_cible'  => $uc['prenom'] ?? '',
    ];
}
