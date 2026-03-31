<?php
session_start();

$cfg = __DIR__ . '/config-db.php';
if (!file_exists($cfg)) { $cfg = __DIR__ . '/../config-db.php'; }
if (!file_exists($cfg)) { die('config-db.php introuvable — vérifiez le chemin: ' . __DIR__); }
require_once $cfg;


// Autorise les administratifs et les médecins
if (!isset($_SESSION['user_id']) || (($_SESSION['nom_role'] ?? '') !== 'Administratif' && ($_SESSION['nom_role'] ?? '') !== 'Médecin' && ($_SESSION['role_app'] ?? '') !== 'medecin')) {
    header('Location: page_connexion.php');
    exit;
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];

$services = [];
try {
    $stmt = $pdo->query("SELECT id_service, nom_service FROM Service ORDER BY nom_service");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $messages[] = [
        'type' => 'danger',
        'text' => 'Impossible de charger la liste des services : '.$e->getMessage()
    ];
}

$filterMonth   = $_GET['mois'] ?? '';         // format attendu YYYY‑MM
$filterService = $_GET['id_service'] ?? '';

$where = [];
if ($filterMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
    // C'est ICI qu'on change la colonne : on utilise date_admission
    $where[] = "DATE_FORMAT(h.date_admission, '%Y-%m') = '$filterMonth'"; 
}

if ($filterService !== '' && ctype_digit($filterService)) {
    $where[] = "p.id_service = ".intval($filterService);
}

$filterSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// requête de base, avec les jointures nécessaires
$baseSql = "
    SELECT
        h.date_admission,
        h.heure_intervention,
        th.type_pre_admission       AS type_hospitalisation
    FROM Hospitalisation AS h
    LEFT JOIN Type_hospitalisation AS th
           ON h.id_type = th.id_type
    LEFT JOIN Personnel AS p
           ON h.id_personne = p.id_personne
    LEFT JOIN Service AS s
           ON p.id_service = s.id_service
    $filterSql
    LIMIT 1000
";

// Récupère l id utilisateur connecté
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) {
    header('Location: connexion.php');
    exit;
}

if (isset($_GET['debug']) && $_GET['debug']==='1') {
    ini_set('display_errors',1);
    error_reporting(E_ALL);
    echo "<pre>DEBUG\n";
    echo "Session:\n";
    var_dump($_SESSION);
    echo "\nPDO connection check:\n";
    try {
        $res = $pdo->query("SELECT COUNT(*) AS c FROM `Hospitalisation`")->fetch(PDO::FETCH_ASSOC);
        echo "Hospitalisation rows: " . ($res['c'] ?? 'n/a') . "\n";
    } catch (Exception $e) {
        echo "PDO error: " . $e->getMessage() . "\n";
    }
    echo "</pre>";
}







function render_table(PDO $pdo, string $tableName, string $displayName, array $options = []) {
    // option pour ne pas afficher la colonne "Actions"
    $showActions = empty($options['hide_actions']); // modifié

    echo "<section class='table-section'>";
    echo "<h3 class='table-title'>" . htmlspecialchars($displayName) . "</h3>";
    try {
        if (!empty($options['custom_sql'])) {            // modifié
            $sql = $options['custom_sql'];               // modifié
        } else {
            $sql = "SELECT * FROM `$tableName` LIMIT 1000";
        }

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            echo "<p class='muted'>Aucune donnée dans $displayName.</p></section>";
            return;
        }
        $fields = array_keys($rows[0]);

        $wrapperClass = !empty($options['small']) ? 'table-wrapper small' :
                        (!empty($options['service']) ? 'table-wrapper service-table'
                                                    : 'table-wrapper full');
        echo "<div class='$wrapperClass'>";
        echo "<table class='admin-table'><thead><tr>";
        foreach ($fields as $f) echo "<th>" . htmlspecialchars($f) . "</th>";
        if ($showActions) echo "<th>Actions</th>";     // modifié
        echo "</tr></thead><tbody>";

        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($fields as $f) {
                $val     = $row[$f];
                $display = is_null($val) ? "<em>null</em>"
                                         : htmlspecialchars((string)$val);
                $plain   = strip_tags($display);
                if (mb_strlen($plain) > 50) {
                    $display = htmlspecialchars(mb_substr($plain, 0, 50)) . "…";
                }
                echo "<td title='" . htmlspecialchars($plain) . "'>$display</td>";
            }
            if ($showActions) {                             // modifié
                $pkName  = $fields[0];
                $pkValue = $row[$pkName];
                echo "<td class='actions-cell'>"
                   . "<input type='hidden' name='csrf_token' value='" . $_SESSION['csrf_token'] . "'>"
                   . "<input type='hidden' name='action' value='delete'>"
                   . "<input type='hidden' name='table' value='" . htmlspecialchars($tableName) . "'>"
                   . "<input type='hidden' name='id' value='" . htmlspecialchars($pkValue) . "'>"
                   . "</form></td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table></div></section>";
    } catch (Exception $e) {
        echo "<div class='error'>Erreur lecture " .
             htmlspecialchars($displayName) . ": " .
             htmlspecialchars($e->getMessage()) . "</div>";
    }
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tableau de bord - Admin</title>

<link rel="stylesheet" href="css/rendez-vous.css?v=<?php echo time(); ?>">

</head>
<body>
<header class="admin-header">
    <div class="header-container">
        <h1>liste des rendez-vous</h1>
        <nav class="admin-nav">
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









    <section class="tables-area">
        <form method="get" class="filter-form">
            <label>
                Mois :
                <input type="month" name="mois"
                    value="<?= htmlspecialchars($filterMonth) ?>">
            </label>
            <label>
                Service :
                <select name="id_service">
                    <option value="">Tous</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id_service'] ?>"
                            <?= $filterService === $s['id_service'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nom_service']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Filtrer</button>
        </form>
    <?php
        render_table(
            $pdo,
            'Hospitalisation',
            'Hospitalisation',
            [
                'hide_actions' => true,
                'custom_sql'   => $baseSql        // <<< on reprend la requête définie plus haut
            ]
        );
        ?>
    </section>

    



</main>
</body>
</html>