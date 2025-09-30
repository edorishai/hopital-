<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=hopital;charset=utf8', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Vérifie l'utilisateur
    $stmt = $pdo->prepare("SELECT id_utilisateur, mot_de_passe, role_app FROM Utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // Stocke l'id de l'utilisateur dans la session
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['role_app'] = $user['role_app'];

        // Redirection selon le rôle
        if ($user['role_app'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: ajout_patient.php');
        }
        exit;
    } else {
        $message = "L'adresse email ou le mot de passe est incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="css/page_connexion.css">
</head>
<body>
    <div class="container">
        <h1>Connexion</h1>
        <?php if ($message): ?>
            <p style="color:red"><?= $message ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Mot de passe" required><br><br>
            <button class="btn btn-primary" type="submit">Connexion</button>
        </form>
    </div>
</body>
</html>
