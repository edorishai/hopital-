<?php
session_start();

$cfg = __DIR__ . '/config-db.php';
if (!file_exists($cfg)) { $cfg = __DIR__ . '/../config-db.php'; }
if (!file_exists($cfg)) { die('config-db.php introuvable — vérifiez le chemin: ' . __DIR__); }
require_once $cfg;

$message = '';
$recaptchaSecret = '6LevTOErAAAAAKdgSvvc5uNVf-V_FqMi3U7M4Dth';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Vérification du captcha
    $recaptchaVerify = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse
    );
    $recaptchaData = json_decode($recaptchaVerify);

    if (!$recaptchaData->success) {
        $message = "Veuillez valider le captcha.";
    } else {
        // Vérifie l'utilisateur dans la table Personnel
        $stmt = $pdo->prepare("
            SELECT p.*, r.nom_role 
            FROM Personnel p
            JOIN Role r ON p.id_role = r.id_role
            WHERE p.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Stocke les infos dans la session
            $_SESSION['user_id'] = $user['id_personne'];
            $_SESSION['nom_role'] = $user['nom_role'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];


            // Redirection selon le rôle
            if ($user['nom_role'] === 'Administratif') {
                header('Location: admin.php');
                exit;
            } elseif ($user['nom_role'] === 'secretaire') {
                header('Location: ../pre-admission/admission.php');
                exit;
            } elseif ($user['nom_role'] === 'Médecin') {
                header('Location: rendez-vous_medecin.php');
                exit;
            } else {
                header('Location: user_page.php');
                exit;
            }

        } else {
            // Mauvais identifiants
            $message = "L'adresse email ou le mot de passe est incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="css/page_connexion.css?v=<?php echo time(); ?>">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <img src="img/logo-lfps.png" alt="Logo de l'hôpital" style="height:130px; margin-left:20px;">
        <h1>Connexion</h1>
        <?php if ($message): ?>
            <p style="color:red"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Mot de passe" required><br><br>
            
            <div class="g-recaptcha" data-sitekey="6LevTOErAAAAAE2JUu1xHFK1qgDlYWzeWYLiKAxJ"></div><br>
            
            <button class="btn btn-primary" type="submit">Connexion</button>

            <div class="motpasse">
            <a href="passeoublie.php">Mot de passe oublié ?</a>
            </div><br>
            
        </form>
    </div>
</body>
</html>