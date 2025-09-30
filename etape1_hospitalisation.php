<?php
// --- etape1_hospitalisation.php ---
require_once 'config.php'; 

$current_step = 1;
$steps = [ 1 => "HOSPITALISATION", 2 => "PATIENT", 3 => "COUVERTURE SOCIALE", 4 => "DOCUMENTS" ];

// Récupérer la liste des médecins
$medecins = $pdo->query("SELECT med_id, nom FROM MEDECIN ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation minimale
    if (!empty($_POST['type_admission']) && !empty($_POST['date_hospitalisation']) && !empty($_POST['medecin_id'])) {
        $_SESSION['etape1_data'] = $_POST;
        header("Location: etape2_patient.php");
        exit;
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Pré-remplir les champs si l'utilisateur revient en arrière
$data = $_SESSION['etape1_data'] ?? [];
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

        <h1>1. HOSPITALISATION</h1>
        <?php if (isset($error)): ?><p style="color: red;"><?= $error ?></p><?php endif; ?>
        
        <form action="etape1_hospitalisation.php" method="post">
            
            <div class="form-group">
                <label for="type_admission">Pré-admission pour : *</label>
                <select name="type_admission" required>
                    <option value="">Choix</option>
                    <option value="Ambulatoire chirurgie" <?= ($data['type_admission'] ?? '') == 'Ambulatoire chirurgie' ? 'selected' : '' ?>>Ambulatoire chirurgie</option>
                    <option value="Hospitalisation (au moins une nuit)" <?= ($data['type_admission'] ?? '') == 'Hospitalisation (au moins une nuit)' ? 'selected' : '' ?>>Hospitalisation (au moins une nuit)</option>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="date_hospitalisation">Date d'hospitalisation *</label>
                    <input type="date" name="date_hospitalisation" value="<?= $data['date_hospitalisation'] ?? '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="heure_intervention">Heure de l'intervention</label>
                    <input type="time" name="heure_intervention" value="<?= $data['heure_intervention'] ?? '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="medecin_id">Nom du médecin *</label>
                <select name="medecin_id" required>
                    <option value="">Choix</option>
                    <?php foreach ($medecins as $medecin): ?>
                        <option value="<?= $medecin['med_id'] ?>" <?= ($data['medecin_id'] ?? '') == $medecin['med_id'] ? 'selected' : '' ?>><?= htmlspecialchars($medecin['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">SUIVANT ></button>
        </form>
    </div>
</body>
</html>