<?php
session_start();

// Chargement robuste de config-db.php
$cfg = __DIR__ . '/config-db.php';
if (!file_exists($cfg)) { $cfg = __DIR__ . '/../config-db.php'; }
if (!file_exists($cfg)) { die('config-db.php introuvable — vérifiez le chemin: ' . __DIR__); }
require_once $cfg;

// Vérifie que l'utilisateur est admin (par `role_app` ou `nom_role`)
if (!isset($_SESSION['user_id']) || (($_SESSION['role_app'] ?? '') !== 'admin' && ($_SESSION['nom_role'] ?? '') !== 'Administratif')) {
    header('Location: page_connexion.php');
    exit;
}

// Récupération des paramètres GET
$table = $_GET['table'] ?? null;
$id = $_GET['id'] ?? null;
if (!$table || !$id) die("Table ou ID manquant.");

// Tables autorisées
$allowed = [
    'Personnel' => 'Personnel',
    'Service'   => 'Service',
    'Medecin'   => 'Medecin'
];
if (!array_key_exists($table, $allowed)) die("Table non autorisée.");
$tableSql = $allowed[$table];

// Clé primaire dynamique
$colStmt = $pdo->query("SHOW COLUMNS FROM `$tableSql`");
$cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
if (!$cols) die("Impossible de lire la structure de la table.");
$pkName = $cols[0]['Field'];

// Récupération de l'enregistrement
$stmt = $pdo->prepare("SELECT * FROM `$tableSql` WHERE `$pkName`=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) die("Enregistrement introuvable.");

// Récupération des rôles et services si Personnel
$roles = $services = [];
if ($tableSql === 'Personnel') {
    $roles = $pdo->query("SELECT id_role, nom_role FROM Role")->fetchAll(PDO::FETCH_ASSOC);
    $services = $pdo->query("SELECT id_service, nom_service FROM Service")->fetchAll(PDO::FETCH_ASSOC);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = [];

    if ($tableSql === 'Personnel') {
        $nom = substr($_POST['nom'], 0, 50);
        $prenom = substr($_POST['prenom'], 0, 50);
        $tel = substr($_POST['tel'], 0, 10);
        $email = substr($_POST['email'], 0, 150);
        $role_app = $_POST['role_app'];
        $id_role = $_POST['id_role'];
        $id_service = $_POST['id_service'];
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';

        if ($mot_de_passe !== '') {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $sql = "UPDATE Personnel 
                    SET nom=?, prenom=?, tel=?, email=?, mot_de_passe=?, role_app=?, id_role=?, id_service=? 
                    WHERE `$pkName`=?";
            $params = [$nom, $prenom, $tel, $email, $hash, $role_app, $id_role, $id_service, $id];
        } else {
            $sql = "UPDATE Personnel 
                    SET nom=?, prenom=?, tel=?, email=?, role_app=?, id_role=?, id_service=? 
                    WHERE `$pkName`=?";
            $params = [$nom, $prenom, $tel, $email, $role_app, $id_role, $id_service, $id];
        }

    } elseif ($tableSql === 'Service') {
        $nom_service = substr($_POST['nom_service'], 0, 50);
        $sql = "UPDATE Service SET nom_service=? WHERE `$pkName`=?";
        $params = [$nom_service, $id];

    } elseif ($tableSql === 'Medecin') {
        $nom = substr($_POST['nom'], 0, 50);
        $prenom = substr($_POST['prenom'], 0, 50);
        $specialite = substr($_POST['specialite'], 0, 50);
        $sql = "UPDATE Medecin SET nom=?, prenom=?, specialite=? WHERE `$pkName`=?";
        $params = [$nom, $prenom, $specialite, $id];
    }

    // Exécution
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        header('Location: admin.php'); // Redirection vers admin
        exit;
    } else {
        $message = "Erreur lors de la modification.";
    }

    // Recharge en cas d'erreur
    $stmt = $pdo->prepare("SELECT * FROM `$tableSql` WHERE `$pkName`=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier <?= htmlspecialchars($tableSql) ?></title>
<link rel="stylesheet" href="css/admin.css">
</head>
<body>
<header class="admin-header">
    <h1>Modifier <?= htmlspecialchars($tableSql) ?></h1>
    <nav><a href="../admin.php">← Retour</a></nav>
</header>
<main class="admin-main">
    <?php if (!empty($message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <form method="post">
        <?php if ($tableSql === 'Personnel'): ?>
            <label>Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($row['nom']) ?>" maxlength="50" required>
            <label>Prénom</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($row['prenom']) ?>" maxlength="50" required>
            <label>Téléphone</label>
            <input type="text" name="tel" value="<?= htmlspecialchars($row['tel']) ?>" maxlength="10">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" maxlength="150" required>
            <label>Mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="mot_de_passe">
            <label>Rôle applicatif</label>
            <select name="role_app">
                <option value="user" <?= $row['role_app']==='user'?'selected':'' ?>>Utilisateur</option>
                <option value="admin" <?= $row['role_app']==='admin'?'selected':'' ?>>Admin</option>
            </select>
            <label>Rôle</label>
            <select name="id_role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id_role'] ?>" <?= $role['id_role']==$row['id_role']?'selected':'' ?>><?= htmlspecialchars($role['nom_role']) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Service</label>
            <select name="id_service">
                <?php foreach ($services as $service): ?>
                    <option value="<?= $service['id_service'] ?>" <?= $service['id_service']==$row['id_service']?'selected':'' ?>><?= htmlspecialchars($service['nom_service']) ?></option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($tableSql === 'Medecin'): ?>
            <label>Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($row['nom']) ?>" maxlength="50" required>
            <label>Prénom</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($row['prenom']) ?>" maxlength="50" required>
            <label>Spécialité</label>
            <input type="text" name="specialite" value="<?= htmlspecialchars($row['specialite']) ?>" maxlength="50" required>

        <?php elseif ($tableSql === 'Service'): ?>
            <label>Nom du service</label>
            <input type="text" name="nom_service" value="<?= htmlspecialchars($row['nom_service']) ?>" maxlength="50" required>
        <?php endif; ?>
            <button type="submit" class="btn btn-primary">Modifier</button>
        </form>
    </div>
</main>
</body>
</html>
