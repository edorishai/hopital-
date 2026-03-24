<?php

$pre_adm_id = $_GET['id'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Confirmation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="text-align: center;">
        <h1 style="color: green;">✅ Pré-admission Validée !</h1>
        <p>Votre demande de pré-admission a été soumise avec succès à la clinique LPF.</p>
        <p>Votre numéro de dossier est : <strong><?= htmlspecialchars($pre_adm_id) ?></strong></p>
        <hr>
        <p>Merci de votre confiance.</p>
    </div>
</body>
</html>