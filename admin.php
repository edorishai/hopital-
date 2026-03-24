<?php
session_start();

$cfg = __DIR__ . '/config-db.php';
if (!file_exists($cfg)) { $cfg = __DIR__ . '/../config-db.php'; }
if (!file_exists($cfg)) { die('config-db.php introuvable — vérifiez le chemin: ' . __DIR__); }
require_once $cfg;


if (!isset($_SESSION['user_id']) || ($_SESSION['nom_role'] ?? '') !== 'Administratif') {
    header('Location: page_connexion.php'); 
    exit;
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];

// --- Ajout Personnel ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_personnel'])) {
$nom = substr(trim($_POST['nom'] ?? ''), 0, 50);
$prenom = substr(trim($_POST['prenom'] ?? ''), 0, 50);
$tel = preg_replace('/\D+/', '', $_POST['tel'] ?? '');
$tel = substr($tel, 0, 10);
$email = substr(trim($_POST['email'] ?? ''), 0, 150);
$mot_de_passe_raw = $_POST['mot_de_passe'] ?? '';
$mot_de_passe = password_hash(substr($mot_de_passe_raw, 0, 255), PASSWORD_DEFAULT);

$role_app = $_POST['role_app'] ?? null;
$id_role = $_POST['id_role'] ?? null;
$id_service = $_POST['id_service'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO Personnel (nom, prenom, tel, email, mot_de_passe, role_app, id_role, id_service) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nom,$prenom,$tel,$email,$mot_de_passe,$role_app,$id_role,$id_service])) {
            $messages[] = ['type'=>'success','text'=>"Personnel ajouté avec succès !"];
        } else {
            $messages[] = ['type'=>'danger','text'=>"Erreur lors de l'ajout du personnel."];
        }
    } catch (Exception $e) {
        $messages[] = ['type'=>'danger','text'=>"Exception: ".$e->getMessage()];
    }
}

// --- Ajout Service ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_service'])) {
    $nom_service = $_POST['nom_service'] ?? null;
    try {
        $stmt = $pdo->prepare("INSERT INTO Service (nom_service) VALUES (?)");
        if ($stmt->execute([$nom_service])) {
            $messages[] = ['type'=>'success','text'=>"Service ajouté avec succès !"];
        } else {
            $messages[] = ['type'=>'danger','text'=>"Erreur lors de l'ajout du service."];
        }
    } catch (Exception $e) {
        $messages[] = ['type'=>'danger','text'=>"Exception: ".$e->getMessage()];
    }
}


// --- Suppression ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $messages[] = ['type'=>'danger','text'=>'Jeton CSRF invalide.'];
    } else {
        $table = $_POST['table'] ?? '';
        $id = $_POST['id'] ?? '';
        $allowed = ['Personnel'=>'Personnel','personnel'=>'Personnel',
            'service'=>'service','Service'=>'service',
            'Medecin'=>'Medecin','medecin'=>'Medecin'];
        if (!array_key_exists($table,$allowed)) $messages[] = ['type'=>'danger','text'=>'Table non autorisée.'];
        else {
            $tableSql = $allowed[$table];
            try {
                $colStmt = $pdo->query("SHOW COLUMNS FROM `$tableSql`");
                $col = $colStmt->fetch(PDO::FETCH_ASSOC);
                if (!$col) $messages[] = ['type'=>'warning','text'=>"Impossible de lire la structure de $tableSql."];
                else {
                    $pk = $col['Field'];
                    $del = $pdo->prepare("DELETE FROM `$tableSql` WHERE `$pk` = :id");
                    if ($del->execute([':id'=>$id])) {
                        $messages[] = $del->rowCount()>0 ?
                            ['type'=>'success','text'=>"Ligne supprimée de $tableSql."] :
                            ['type'=>'warning','text'=>"Aucune ligne supprimée (ID introuvable)."];
                    } else $messages[] = ['type'=>'danger','text'=>"Erreur lors de la suppression."];
                }
            } catch(Exception $e) {
                $messages[] = ['type'=>'danger','text'=>"Exception suppression: ".$e->getMessage()];
            }
        }
    }
}

// --- Fonction pour afficher tableau ---
function render_table(PDO $pdo,string $tableName,string $editPage,string $displayName,array $options=[]) {
    echo "<section class='tabl e-section'>";
    echo "<h3 class='table-title'>".htmlspecialchars($displayName)."</h3>";
    try {
        $sql = "SELECT * FROM `$tableName` LIMIT 1000";    
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($rows)===0){ echo "<p class='muted'>Aucune donnée dans $displayName.</p></section>"; return; }
        
        // 1. On sauvegarde TOUTES les colonnes pour trouver la Clé Primaire (ex: id_personne)
        $all_fields = array_keys($rows[0]);
        $pkName = $all_fields[0]; 

        // 2. On filtre les colonnes qu'on ne veut pas afficher
        $fields = $all_fields;
        if (!empty($options['exclude'])) {
            $fields = array_diff($fields, $options['exclude']);
        }

        $wrapperClass = !empty($options['small']) ? 'table-wrapper small' : (!empty($options['service'])?'table-wrapper service-table':'table-wrapper full');
        echo "<div class='$wrapperClass'>";
        echo "<table class='admin-table'><thead><tr>";
        foreach($fields as $f) echo "<th>".htmlspecialchars($f)."</th>";
        echo "<th>Actions</th></tr></thead><tbody>";

        foreach($rows as $row){
            echo "<tr>";
            foreach($fields as $f){
                $val = $row[$f];
                $display = is_null($val) ? "<em>null</em>" : htmlspecialchars((string)$val);
                $plain = strip_tags($display);
                if(mb_strlen($plain)>50) $display = htmlspecialchars(mb_substr($plain,0,50))."…";
                echo "<td title='".htmlspecialchars($plain)."'>$display</td>";
            }
            
            $pkValue = $row[$pkName];
            echo "<td class='actions-cell'>";
            echo "<a class='btn btn-edit' href='".htmlspecialchars($editPage)."?table=".urlencode($tableName)."&id=".urlencode($pkValue)."'>Modifier</a>";
            echo "<form method='post' class='inline-delete' onsubmit='return confirm(\"Confirmer la suppression ?\");'>";
            echo "<input type='hidden' name='csrf_token' value='".$_SESSION['csrf_token']."'>";
            echo "<input type='hidden' name='action' value='delete'>";
            echo "<input type='hidden' name='table' value='".htmlspecialchars($tableName)."'>";
            echo "<input type='hidden' name='id' value='".htmlspecialchars($pkValue)."'>";
            echo "<button type='submit' class='btn btn-delete'>Supprimer</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table></div></section>";
    } catch(Exception $e){
        echo "<div class='error'>Erreur lecture ".htmlspecialchars($displayName).": ".htmlspecialchars($e->getMessage())."</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tableau de bord - Admin</title>
<link rel="stylesheet" href="css/admin.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="css/table_admin.css?v=<?php echo time(); ?>">
</head>
<body>
<header class="admin-header">
    <div class="header-container">
        <h1>Panneau d'administration</h1>
        <nav class="admin-nav">
            <button onclick="location.href='../pre-admission/admission.php'">Gérer les hospitalisations</button>
            
            <button class="logout" onclick="location.href='page_connexion.php'">Déconnexion</button>
        </nav>
    </div>
</header>

<main class="admin-main">
    <div class="messages-area">
        <?php foreach($messages as $m): ?>
            <div class="alert <?= htmlspecialchars($m['type'])==='success'?'alert-success':(htmlspecialchars($m['type'])==='danger'?'alert-danger':'alert-warning') ?>">
                <?= htmlspecialchars($m['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="container">
        <div class="form-box">
            <h2>Ajouter un personnel</h2>
            <form method="post" class="form-add">
                <label>Nom</label><input type="text" name="nom" required>
                <label>Prénom</label><input type="text" name="prenom" required>
                <label>Téléphone</label><input type="tel" name="tel" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" title="10 chiffres" required>
                <label>Email</label><input type="email" name="email" required>
                <label>Mot de passe</label><input type="password" name="mot_de_passe" required>
                <label>Rôle applicatif</label>
                <select name="role_app">
                    <option value="user">Utilisateur</option>
                    <option value="admin">Admin</option>
                </select>
                <label>Rôle</label>
                <select name="id_role">
                    <?php foreach($pdo->query("SELECT id_role, nom_role FROM Role") as $role): ?>
                        <option value="<?= $role['id_role'] ?>"><?= htmlspecialchars($role['nom_role']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Service</label>
                <select name="id_service">
                    <?php foreach($pdo->query("SELECT id_service, nom_service FROM Service") as $service): ?>
                        <option value="<?= $service['id_service'] ?>"><?= htmlspecialchars($service['nom_service']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="ajouter_personnel" class="btn btn-primary">Ajouter</button>
            </form>
        </div>




        <div class="form-box">
            <h2>Ajouter un service</h2>
            <form method="post" class="form-add">
                <label>Nom du service</label>
                <input type="text" name="nom_service" required>
                <button type="submit" name="ajouter_service" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>

    <section class="tables-area">
        <?php
        render_table($pdo,'Personnel','modification.php','Personnel',[
            'small' => true, 
            'exclude' => ['mot_de_passe', 'id_role', 'id_service']
        ]);        
        render_table($pdo,'Service','modification.php','Services',['Service'=>true]);
        ?>
    </section>
</main>
</body>
</html>