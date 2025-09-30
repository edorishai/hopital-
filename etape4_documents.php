<?php
// --- etape4_documents.php ---
require_once 'config.php';

// Sécurité : si les étapes précédentes ne sont pas remplies
if (!isset($_SESSION['etape1_data']) || !isset($_SESSION['etape2_data']) || !isset($_SESSION['etape3_data'])) { 
    header("Location: etape1_hospitalisation.php"); exit; 
}

$current_step = 4;
$steps = [ 1 => "HOSPITALISATION", 2 => "PATIENT", 3 => "COUVERTURE SOCIALE", 4 => "DOCUMENTS" ];

// Récupérer la liste des documents requis
$documents_requis = $pdo->query("SELECT doc_id, nom_document FROM DOCUMENT")->fetchAll(PDO::FETCH_ASSOC);

$upload_dir = 'uploads/'; // Dossier où stocker les fichiers
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo->beginTransaction();
    try {
        // ------------------------------------
        // 1. ENREGISTREMENT DE LA PERSONNE DE CONFIANCE ET À PRÉVENIR
        // ------------------------------------
        
        $pers_confiance_id = null;
        if (!empty($_SESSION['etape2_data']['pc_nom'])) {
            $stmt = $pdo->prepare("INSERT INTO PERSONNE (type_pers, nom, prenom, adresse, telephone) VALUES ('CONFIANCE', ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['etape2_data']['pc_nom'],
                $_SESSION['etape2_data']['pc_prenom'],
                $_SESSION['etape2_data']['pc_adresse'],
                $_SESSION['etape2_data']['pc_telephone']
            ]);
            $pers_confiance_id = $pdo->lastInsertId();
        }

        $pers_prevenir_id = null;
        if (!empty($_SESSION['etape2_data']['pa_nom'])) {
            $stmt = $pdo->prepare("INSERT INTO PERSONNE (type_pers, nom, prenom, adresse, telephone) VALUES ('PREVENIR', ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['etape2_data']['pa_nom'],
                $_SESSION['etape2_data']['pa_prenom'],
                $_SESSION['etape2_data']['pa_adresse'],
                $_SESSION['etape2_data']['pa_telephone']
            ]);
            $pers_prevenir_id = $pdo->lastInsertId();
        }

        // ------------------------------------
        // 2. ENREGISTREMENT DU PATIENT
        // ------------------------------------
        $pat_data = $_SESSION['etape2_data'];
        $stmt = $pdo->prepare("INSERT INTO PATIENT (civ, nom_naissance, nom_epouse, prenom, date_naissance, adresse, cp, ville, email, telephone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $pat_data['civ'], $pat_data['nom_naissance'], $pat_data['nom_epouse'], $pat_data['prenom'], 
            $pat_data['date_naissance'], $pat_data['adresse'], $pat_data['cp'], $pat_data['ville'], 
            $pat_data['email'], $pat_data['telephone']
        ]);
        $pat_id = $pdo->lastInsertId();

        // ------------------------------------
        // 3. ENREGISTREMENT DE LA PRÉ-ADMISSION
        // ------------------------------------
        $etape1 = $_SESSION['etape1_data'];
        $etape3 = $_SESSION['etape3_data'];
        
        $stmt = $pdo->prepare("INSERT INTO PRE_ADMISSION (pat_id, type_admission, date_hospitalisation, heure_intervention, med_id, num_secu_sociale, est_assure, est_ald, nom_mutuelle, num_adherent_mutuelle, chambre_particuliere, pers_confiance_id, pers_prevenir_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $pat_id, $etape1['type_admission'], $etape1['date_hospitalisation'], $etape1['heure_intervention'], 
            $etape1['medecin_id'], $etape3['num_secu_sociale'], $etape3['est_assure'], $etape3['est_ald'], 
            $etape3['nom_mutuelle'], $etape3['num_adherent_mutuelle'], $etape3['chambre_particuliere'],
            $pers_confiance_id, $pers_prevenir_id
        ]);
        $pre_adm_id = $pdo->lastInsertId();

        // ------------------------------------
        // 4. GESTION DES FICHIERS TÉLÉCHARGÉS
        // ------------------------------------
        foreach ($_FILES['documents']['name'] as $doc_id => $filename) {
            if ($_FILES['documents']['error'][$doc_id] === UPLOAD_ERR_OK) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $new_filename = $pre_adm_id . '_' . $doc_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['documents']['tmp_name'][$doc_id], $target_path)) {
                    $stmt = $pdo->prepare("INSERT INTO DOCUMENT_JOINT (pre_adm_id, doc_id, chemin_fichier) VALUES (?, ?, ?)");
                    $stmt->execute([$pre_adm_id, $doc_id, $target_path]);
                }
            }
        }

        $pdo->commit();
        session_destroy();
        header("Location: confirmation.php?id=" . $pre_adm_id);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la sauvegarde finale : " . $e->getMessage();
        // Optionnel: loguer l'erreur
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Pré-admission - Étape <?= $current_step ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        
        <div class="steps-container">
            <?php foreach ($steps as $number => $title): ?>
                <div class="step <?= $number === $current_step ? 'active' : '' ?>">
                    <div class="step-number"><?= $number ?></div>
                    <div class="step-title"><?= $title ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <h1>4. DOCUMENTS À JOINDRE</h1>
        <?php if (isset($error)): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>

        <form action="etape4_documents.php" method="post" enctype="multipart/form-data">
            
            <p>Veuillez joindre les documents numérisés nécessaires à votre pré-admission.</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <?php foreach ($documents_requis as $doc): ?>
                    <div class="form-group">
                        <label for="doc_<?= $doc['doc_id'] ?>"><?= htmlspecialchars($doc['nom_document']) ?></label>
                        <input type="file" name="documents[<?= $doc['doc_id'] ?>]" id="doc_<?= $doc['doc_id'] ?>" style="border: none;">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <hr style="margin: 20px 0;">
            
            <div class="nav-buttons">
                <button type="button" onclick="window.location.href='etape3_couverture_sociale.php'">PRÉCÉDENT</button>
                <button type="submit" name="valider">VALIDER LA PRÉ-ADMISSION</button>
            </div>
        </form>
    </div>
</body>
</html>