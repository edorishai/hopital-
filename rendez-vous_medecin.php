<?php
session_start();

$cfg = __DIR__ . '/config-db.php';
if (!file_exists($cfg)) { $cfg = __DIR__ . '/../config-db.php'; }
if (!file_exists($cfg)) { die('config-db.php introuvable — vérifiez le chemin: ' . __DIR__); }
require_once $cfg;

// 1. Récupère l'id utilisateur connecté
$uid = $_SESSION['user_id'] ?? null;

// 2. Autorise les administratifs et les médecins, sinon on bloque
if (!$uid || (($_SESSION['nom_role'] ?? '') !== 'Administratif' && ($_SESSION['nom_role'] ?? '') !== 'Médecin' && ($_SESSION['role_app'] ?? '') !== 'medecin')) {
    header('Location: page_connexion.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];

// 3. Variables pour les filtres
$isDoctor = (($_SESSION['nom_role'] ?? '') === 'Médecin' || ($_SESSION['role_app'] ?? '') === 'medecin');
$filterMonth = $_GET['mois'] ?? '';

// 4. Construction intelligente de la requête SQL (Filtres combinés)
$where = [];

if ($isDoctor) {
    // Règle n°1 : Le médecin ne voit QUE ses propres hospitalisations
    $where[] = "h.id_personne = " . intval($uid);
}

if ($filterMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $filterMonth)) {
    // Règle n°2 : On filtre sur le mois choisi (sur la date_admission)
    $where[] = "DATE_FORMAT(h.date_admission, '%Y-%m') = '$filterMonth'";
}

// On assemble tous les morceaux avec des "AND"
$filterSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 5. Requête de base
$baseSql = "
    SELECT
        h.date_admission,
        h.heure_intervention,
        th.type_pre_admission AS type_hospitalisation
    FROM Hospitalisation AS h
    LEFT JOIN Type_hospitalisation AS th
           ON h.id_type = th.id_type
    LEFT JOIN Personnel AS p
           ON h.id_personne = p.id_personne
    LEFT JOIN Service AS s
           ON p.id_service = s.id_service
    $filterSql
    ORDER BY h.date_admission ASC, h.heure_intervention ASC
    LIMIT 1000
";

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
    $showActions = empty($options['hide_actions']);

    echo "<section class='table-section'>";
    echo "<h3 class='table-title'>" . htmlspecialchars($displayName) . "</h3>";
    try {
        if (!empty($options['custom_sql'])) {
            $sql = $options['custom_sql'];
        } else {
            $sql = "SELECT * FROM `$tableName` LIMIT 1000";
        }

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            echo "<p class='muted'>Aucun rendez-vous trouvé.</p></section>";
            return;
        }
        $fields = array_keys($rows[0]);

        $wrapperClass = !empty($options['small']) ? 'table-wrapper small' :
                        (!empty($options['service']) ? 'table-wrapper service-table'
                                                     : 'table-wrapper full');
        echo "<div class='$wrapperClass'>";
        echo "<table class='admin-table'><thead><tr>";
        foreach ($fields as $f) {
            $colName = str_replace('_', ' ', ucfirst($f));
            echo "<th>" . htmlspecialchars($colName) . "</th>";
        }
        if ($showActions) echo "<th>Actions</th>";
        echo "</tr></thead><tbody>";

        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($fields as $f) {
                $val     = $row[$f];
                $display = is_null($val) ? "<em>Non défini</em>"
                                         : htmlspecialchars((string)$val);
                $plain   = strip_tags($display);
                if (mb_strlen($plain) > 50) {
                    $display = htmlspecialchars(mb_substr($plain, 0, 50)) . "…";
                }
                echo "<td title='" . htmlspecialchars($plain) . "'>$display</td>";
            }
            if ($showActions) {
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
<title>Tableau de bord - Médecin</title>
<link rel="stylesheet" href="css/rendez-vous.css?v=<?php echo time(); ?>">
<style>
    /* Petit ajout pour le bouton "Effacer le filtre" */
    .btn-clear {
        background-color: transparent;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    .btn-clear:hover {
        background-color: #f1f5f9;
        color: #334155;
    }
</style>
</head>
<body>
<header class="admin-header">
    <div class="header-container">
        <h1>Mes rendez-vous</h1>
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

    <section class="tables-area">
        <form method="get" class="filter-form">
            <label>
                Filtrer par mois :
                <input type="month" name="mois" value="<?= htmlspecialchars($filterMonth) ?>">
            </label>
            <button type="submit">Rechercher</button>
            
            <?php if ($filterMonth !== ''): ?>
                <button type="button" class="btn-clear" onclick="location.href='rendez-vous_medecin.php'">Effacer</button>
            <?php endif; ?>
        </form>

        <?php
        render_table(
            $pdo,
            'Hospitalisation',
            'Planning',
            [
                'hide_actions' => true,
                'custom_sql'   => $baseSql
            ]
        );
        ?>
    </section>

</main>
</body>
</html>