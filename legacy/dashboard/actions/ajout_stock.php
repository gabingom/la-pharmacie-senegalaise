<?php
// ============================================================
//  AJOUT D'UN MEDICAMENT AU STOCK (PRA)
//  Avec detection de doublon / erreur de frappe (point 4)
//  - Si un nom tres proche existe deja, on alerte au lieu de creer
//  - L'utilisateur peut forcer la creation (parametre 'forcer')
// ============================================================
require_once '../../config/session.php';
require_once '../../config/db.php';
exigerRole('pra');
header('Content-Type: application/json; charset=utf-8');

$sid     = structureId();
$nom     = trim($_POST['nom'] ?? '');
$forme   = $_POST['forme'] ?? 'autre';
$dosage  = trim($_POST['dosage'] ?? '');
$cat     = trim($_POST['categorie'] ?? '');
$seuil   = intval($_POST['seuil_alerte'] ?? 100);
$qte     = intval($_POST['quantite'] ?? 0);
$lot     = trim($_POST['numero_lot'] ?? '');
$perem   = $_POST['date_peremption'] ?? null;
$medId   = intval($_POST['medicament_id'] ?? 0); // si on reutilise un medicament existant
$forcer  = ($_POST['forcer'] ?? '') === '1';

// --- Normalisation pour comparer les noms (enleve accents, minuscules) ---
function normNom($t) {
    $t = mb_strtolower(trim($t),'UTF-8');
    $rep = ['à'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i',
            'ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'];
    $t = strtr($t, $rep);
    $t = preg_replace('/[^a-z0-9]/','',$t); // ne garde que lettres/chiffres
    return $t;
}

// Distance de Levenshtein "tolerante" : proche si <=2 differences
function sontProches($a, $b) {
    $na = normNom($a); $nb = normNom($b);
    if ($na === '' || $nb === '') return false;
    if ($na === $nb) return true;
    $d = levenshtein($na, $nb);
    $maxLen = max(strlen($na), strlen($nb));
    // proche si 1-2 caracteres de difference, ou difference < 15% de la longueur
    return $d <= 2 || ($maxLen > 6 && $d <= round($maxLen * 0.15));
}

try {
    if ($qte <= 0) throw new Exception("La quantité doit être supérieure à zéro.");

    // CAS 1 : on reutilise un medicament existant choisi explicitement
    if ($medId > 0) {
        $chk = $pdo->prepare("SELECT id FROM medicaments WHERE id=?");
        $chk->execute([$medId]);
        if (!$chk->fetchColumn()) throw new Exception("Médicament introuvable.");
        $idMed = $medId;
    }
    else {
        if ($nom === '') throw new Exception("Le nom du médicament est requis.");

        // --- DETECTION DE DOUBLON (sauf si l'utilisateur force) ---
        if (!$forcer) {
            $tous = $pdo->query("SELECT id, nom, dosage, forme FROM medicaments")->fetchAll();
            $proches = [];
            foreach ($tous as $m) {
                // On compare le nom ; meme dosage => encore plus probable que ce soit un doublon
                if (sontProches($nom, $m['nom'])) {
                    $proches[] = $m;
                }
            }
            if ($proches) {
                // On renvoie la liste des medicaments proches pour demander confirmation
                $opts = [];
                foreach ($proches as $p) {
                    $opts[] = ['id'=>$p['id'], 'label'=>trim($p['nom'].' '.$p['dosage'].' ('.$p['forme'].')')];
                }
                echo json_encode([
                    'success'   => false,
                    'doublon'   => true,
                    'message'   => "Un ou plusieurs médicaments très proches existent déjà. S'agit-il du même produit ?",
                    'proches'   => $opts
                ]);
                exit;
            }
        }

        // Creer le nouveau medicament dans le referentiel
        $pdo->prepare("INSERT INTO medicaments (nom,forme,dosage,categorie,seuil_alerte) VALUES (?,?,?,?,?)")
            ->execute([$nom, $forme, $dosage, $cat, $seuil]);
        $idMed = $pdo->lastInsertId();
    }

    // --- Ajouter / cumuler au stock du PRA ---
    // Si ce medicament est deja en stock pour ce PRA (meme lot), on cumule
    $ex = $pdo->prepare("SELECT id, quantite FROM stocks WHERE structure_id=? AND medicament_id=? AND (numero_lot <=> ?) LIMIT 1");
    $ex->execute([$sid, $idMed, $lot !== '' ? $lot : null]);
    $row = $ex->fetch();

    if ($row) {
        $pdo->prepare("UPDATE stocks SET quantite = quantite + ?, date_peremption = COALESCE(?, date_peremption) WHERE id=?")
            ->execute([$qte, $perem ?: null, $row['id']]);
    } else {
        $pdo->prepare("INSERT INTO stocks (medicament_id,structure_id,quantite,numero_lot,date_peremption) VALUES (?,?,?,?,?)")
            ->execute([$idMed, $sid, $qte, $lot !== '' ? $lot : null, $perem ?: null]);
    }

    echo json_encode(['success'=>true, 'message'=>"Médicament ajouté à votre stock avec succès."]);

} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
