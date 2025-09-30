<?php
// --- etape2_patient.php ---
require_once 'config.php'; 

// Sécurité : si l'étape 1 n'est pas remplie, on redirige
if (!isset($_SESSION['etape1_data'])) { header("Location: etape1_hospitalisation.php"); exit; }

$current_step = 2;
$steps = [ 1 => "HOSPITALISATION", 2 => "PATIENT", 3 => "COUVERTURE SOCIALE", 4 => "DOCUMENTS" ];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation des champs obligatoires (simplifiée)
    if (!empty($_POST['nom_naissance']) && !empty($_POST['prenom']) && !empty($_POST['adresse']) && !empty($_POST['telephone'])) {
        $_SESSION['etape2_data'] = $_POST;
        header("Location: etape3_couverture_sociale.php");
        exit;
    } else {
        $error = "Veuillez remplir les informations du patient obligatoires.";
    }
}

$data = $_SESSION['etape2_data'] ?? [];
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

        <h1>2. INFORMATIONS CONCERNANT LE PATIENT</h1>
        <?php if (isset($error)): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>
        
        <form action="etape2_patient.php" method="post">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="civ">Civ. *</label>
                    <select name="civ" required>
                         <option value="">Choix</option>
                         <option value="Mme" <?= ($data['civ'] ?? '') == 'Mme' ? 'selected' : '' ?>>Mme</option>
                         <option value="M." <?= ($data['civ'] ?? '') == 'M.' ? 'selected' : '' ?>>M.</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nom_naissance">Nom de naissance *</label>
                    <input type="text" name="nom_naissance" value="<?= $data['nom_naissance'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="nom_epouse">Nom d'épouse</label>
                    <input type="text" name="nom_epouse" value="<?= $data['nom_epouse'] ?? '' ?>">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="prenom">Prénom *</label>
                    <input type="text" name="prenom" value="<?= $data['prenom'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance *</label>
                    <input type="date" name="date_naissance" value="<?= $data['date_naissance'] ?? '' ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="adresse">Adresse *</label>
                <input type="text" name="adresse" value="<?= $data['adresse'] ?? '' ?>" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                 <div class="form-group">
                    <label for="cp">CP *</label>
                    <input type="text" name="cp" value="<?= $data['cp'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="ville">Ville *</label>
                    <input type="text" name="ville" value="<?= $data['ville'] ?? '' ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                 <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" name="email" value="<?= $data['email'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone *</label>
                    <input type="tel" name="telephone" value="<?= $data['telephone'] ?? '' ?>" required>
                </div>
            </div>

            <hr style="margin: 20px 0;">
            <h3>COORDONNÉES PERSONNE DE CONFIANCE</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="pc_nom" placeholder="Nom" value="<?= $data['pc_nom'] ?? '' ?>">
                <input type="text" name="pc_prenom" placeholder="Prénom" value="<?= $data['pc_prenom'] ?? '' ?>">
            </div>
            <input type="text" name="pc_adresse" placeholder="Adresse" value="<?= $data['pc_adresse'] ?? '' ?>">
            <input type="tel" name="pc_telephone" placeholder="Téléphone" value="<?= $data['pc_telephone'] ?? '' ?>">

            <hr style="margin: 20px 0;">
            <h3>COORDONNÉES PERSONNE À PRÉVENIR</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="pa_nom" placeholder="Nom" value="<?= $data['pa_nom'] ?? '' ?>">
                <input type="text" name="pa_prenom" placeholder="Prénom" value="<?= $data['pa_prenom'] ?? '' ?>">
            </div>
            <input type="text" name="pa_adresse" placeholder="Adresse" value="<?= $data['pa_adresse'] ?? '' ?>">
            <input type="tel" name="pa_telephone" placeholder="Téléphone" value="<?= $data['pa_telephone'] ?? '' ?>">

            <div class="nav-buttons">
                <button type="button" onclick="window.location.href='etape1_hospitalisation.php'">PRÉCÉDENT</button>
                <button type="submit">SUIVANT ></button>
            </div>
        </form>
    </div>
</body>
</html>