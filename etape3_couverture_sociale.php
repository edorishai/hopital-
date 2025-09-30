<?php
// --- etape3_couverture_sociale.php ---
require_once 'config.php'; 

if (!isset($_SESSION['etape1_data']) || !isset($_SESSION['etape2_data'])) { header("Location: etape1_hospitalisation.php"); exit; }

$current_step = 3;
$steps = [ 1 => "HOSPITALISATION", 2 => "PATIENT", 3 => "COUVERTURE SOCIALE", 4 => "DOCUMENTS" ];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation des champs obligatoires (simplifiée)
    if (!empty($_POST['num_secu_sociale']) && !empty($_POST['nom_mutuelle'])) {
        $_SESSION['etape3_data'] = $_POST;
        header("Location: etape4_documents.php");
        exit;
    } else {
        $error = "Veuillez remplir les champs obligatoires (Sécu et Mutuelle).";
    }
}

$data = $_SESSION['etape3_data'] ?? [];
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

        <h1>3. COUVERTURE SOCIALE</h1>
        <?php if (isset($error)): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>
        
        <form action="etape3_couverture_sociale.php" method="post">

            <div class="form-group">
                <label for="num_secu_sociale">Numéro de sécurité sociale *</label>
                <input type="text" name="num_secu_sociale" value="<?= $data['num_secu_sociale'] ?? '' ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="est_assure">Le patient est-il l'assuré ? *</label>
                    <select name="est_assure" required>
                        <option value="">Choix</option>
                        <option value="1" <?= ($data['est_assure'] ?? '') == '1' ? 'selected' : '' ?>>Oui</option>
                        <option value="0" <?= ($data['est_assure'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="est_ald">Le patient est-il en ALD ? *</label>
                    <select name="est_ald" required>
                        <option value="">Choix</option>
                        <option value="1" <?= ($data['est_ald'] ?? '') == '1' ? 'selected' : '' ?>>Oui</option>
                        <option value="0" <?= ($data['est_ald'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="nom_mutuelle">Nom de la mutuelle ou de l'assurance *</label>
                <input type="text" name="nom_mutuelle" value="<?= $data['nom_mutuelle'] ?? '' ?>" required>
            </div>

            <div class="form-group">
                <label for="num_adherent_mutuelle">Numéro d'adhérent</label>
                <input type="text" name="num_adherent_mutuelle" value="<?= $data['num_adherent_mutuelle'] ?? '' ?>">
            </div>

            <div class="form-group">
                <label for="chambre_particuliere">Chambre particulière ? *</label>
                <select name="chambre_particuliere" required>
                    <option value="">Choix</option>
                    <option value="1" <?= ($data['chambre_particuliere'] ?? '') == '1' ? 'selected' : '' ?>>Oui</option>
                    <option value="0" <?= ($data['chambre_particuliere'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                </select>
            </div>

            <div class="nav-buttons">
                <button type="button" onclick="window.location.href='etape2_patient.php'">PRÉCÉDENT</button>
                <button type="submit">SUIVANT ></button>
            </div>
        </form>
    </div>
</body>
</html>