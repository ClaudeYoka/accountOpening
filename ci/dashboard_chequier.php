<?php

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chequier_status (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, request_id INT NOT NULL, status VARCHAR(100) NOT NULL, changed_by INT DEFAULT NULL, changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(request_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$latest_status = "LEFT JOIN (SELECT request_id, status FROM chequier_status s1 WHERE s1.changed_at = (SELECT MAX(s2.changed_at) FROM chequier_status s2 WHERE s2.request_id = s1.request_id)) cs ON tc.id = cs.request_id";
$base_where = "tc.type_compte IS NOT NULL AND tc.type_compte <> ''";
$stats = [];
foreach (
    [   'donné' => ['label' => 'Chéquiers donnés', 'icon' => 'fa-check', 'color' => '#ef3f4f', 'url' => 'demandes_donnees'],
        'prestataire' => ['label' => 'Chez le prestataire', 'icon' => 'fa-truck', 'color' => '#ff7817', 'url' => 'demandes_prestataire'],
        'reçu' => ['label' => 'Demandes reçues', 'icon' => 'fa-inbox', 'color' => '#ec3f93', 'url' => 'demandes_recues'],
        'livré' => ['label' => 'Livrées en agence', 'icon' => 'fa-home', 'color' => '#09b981', 'url' => 'demandes_livrees']
    ] as $status => $definition) 
    {
    $label = $definition['label'];
    $escaped_status = mysqli_real_escape_string($conn, $status);
    $query = "SELECT COUNT(*) AS request_count, COALESCE(SUM(COALESCE(tc.etabliss, 1)), 0) AS chequier_count FROM tblcompte tc $latest_status WHERE $base_where AND LOWER(REPLACE(COALESCE(cs.status, tc.access, 'encours'), ' ', '')) COLLATE utf8mb4_general_ci = '$escaped_status'";
    $result = mysqli_query($conn, $query);
    $row = $result ? mysqli_fetch_assoc($result) : ['request_count' => 0, 'chequier_count' => 0];
    $stats[$status] = ['label' => $label, 'icon' => $definition['icon'], 'color' => $definition['color'],
                        'url' => $definition['url'], 'requests' => (int)$row['request_count'], 'chequiers' => (int)$row['chequier_count']];
    }
$links = [
        ['url' => 'demande_chequier', 'title' => 'Demandes en cours', 'icon' => 'fa fa-clock-o', 'color' => '#f0ad4e'],
        ['url' => 'historique_demande_chequier', 'title' => 'Historique des demandes', 'icon' => 'fa fa-history', 'color' => '#337ab7'],
        ['url' => 'demandes_donnees', 'title' => 'Chéquiers donnés', 'icon' => 'fa fa-check', 'color' => '#28a745'],
        ['url' => 'demandes_prestataire', 'title' => 'Chez le prestataire', 'icon' => 'fa fa-truck', 'color' => '#17a2b8'],
        ['url' => 'demandes_recues', 'title' => 'Demandes reçues', 'icon' => 'fa fa-inbox', 'color' => '#ec3f93'],
        ['url' => 'demandes_livrees', 'title' => 'Demandes livrées', 'icon' => 'fa fa-home', 'color' => '#6f42c1']
    ];
?>

<body>

<?php include('includes/navbar.php'); include('includes/right_sidebar.php'); include('includes/left_sidebar.php'); ?>

<div class="mobile-menu-overlay">

</div><div class="main-container">
<div class="pd-ltr-20">
<div class="page-header">
    <div class="title">
        <h2 class="h3 mb-0">Dashboard Chéquier</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                <li class="breadcrumb-item active">Dashboard Chéquier</li>
            </ol>
        </nav>
    </div>
</div>

<style>
    .chequier-kpi {
        position: relative;
        min-height: 174px;
        padding: 25px 24px;
        border: 0;
        border-radius: 6px;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .chequier-kpi:hover { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0,0,0,.12); }
    .chequier-kpi h5 { color: #91a0b8; text-transform: uppercase; font-weight: 700; font-size: 16px; margin: 0 0 8px; }
    .chequier-kpi .kpi-number { color: #263c5a; font-size: 31px; font-weight: 500; line-height: 1.2; }
    .chequier-kpi .kpi-detail { color: #91a0b8; margin-top: 22px; font-size: 15px; }
    .chequier-kpi .kpi-icon { position: absolute; top: 24px; right: 24px; width: 72px; height: 72px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 8px 16px rgba(0,0,0,.14); }
    .chequier-kpi .kpi-link { color: inherit; text-decoration: none; }
</style>
<div class="row">
    <?php foreach ($stats as $status => $stat): ?>
<div class="col-lg-6 col-md-6 mb-30">
    <a class="kpi-link" href="<?php echo htmlspecialchars($stat['url']); ?>">
        <div class="card-box chequier-kpi">
            <h5><?php echo htmlspecialchars($stat['label']); ?></h5>
            <div class="kpi-number"><?php echo $stat['requests']; ?></div>
            <div class="kpi-detail"><?php echo $stat['chequiers']; ?> chéquier(s) concernés</div>
            <div class="kpi-icon" style="background:<?php echo htmlspecialchars($stat['color']); ?>;">
                <i class="fa <?php echo htmlspecialchars($stat['icon']); ?>"></i>
            </div>
        </div>
    </a>
</div>
<?php endforeach; ?>
</div>
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="mb-20">Gestion des demandes de chéquier</h4>
        <div class="row">
<?php foreach ($links as $link): ?>
    <div class="col-md-4 col-sm-6 mb-20">
        <a href="<?php echo htmlspecialchars($link['url']); ?>" class="btn btn-block" style="border:1px solid 
            <?php echo $link['color']; ?>;color:<?php echo $link['color']; ?>;padding:14px;text-align:left;">
            <i class="<?php echo $link['icon']; ?>" style="width:24px;"></i>
            <?php echo htmlspecialchars($link['title']); ?>
        </a>
    </div>
<?php endforeach; ?>
</div>
</div>
</div>
    <?php include('includes/footer.php'); ?></div>
</div>
    <?php include('includes/scriptJs.php'); ?>
</body>
</html>
