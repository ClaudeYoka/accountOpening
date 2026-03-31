<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
require_once __DIR__ . '/../includes/config.php';

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$where = "tc.type_compte IS NOT NULL AND tc.type_compte != ''";

if ($search !== '') {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $where .= " AND (tc.account_number LIKE '%$search_escaped%' OR tc.firstname LIKE '%$search_escaped%' OR tc.nip LIKE '%$search_escaped%')";
}

if ($status_filter !== '') {
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND (COALESCE(cs.status, tc.access, 'encours') = '$status_safe')";
}

if ($date_from !== '') {
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $where .= " AND tc.date_enregistrement >= '$date_from_safe 00:00:00'";
}

if ($date_to !== '') {
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $where .= " AND tc.date_enregistrement <= '$date_to_safe 23:59:59'";
}

$query = "SELECT
            tc.id,
            tc.firstname as customer_name,
            tc.branch_code,
            tc.account_number as account_number,
            tc.mobile1 as phone_number,
            tc.email,
            tc.type_compte,
            tc.adr_rue as address,
            tc.nip as rib_key,
            tc.etabliss as quantity,
            tc.access as status,
            tc.date_enregistrement as created_at,
            COALESCE(tb.DepartmentName, tc.branch_code) as agency_name,
            COALESCE(cs.status, tc.access, 'encours') as current_status
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        LEFT JOIN (
            SELECT request_id, status
            FROM chequier_status cs1
            WHERE cs1.changed_at = (
                SELECT MAX(cs2.changed_at)
                FROM chequier_status cs2
                WHERE cs2.request_id = cs1.request_id
            )
        ) cs ON tc.id = cs.request_id
        WHERE $where
        ORDER BY tc.date_enregistrement DESC";

$result = mysqli_query($conn, $query);
$historic_requests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $historic_requests[] = $row;
    }
}

function status_label_php($status) {
    $map = [
        'encours' => 'En cours',
        'prestataire' => 'Prestataire',
        'reçu' => 'Reçu',
        'livré' => 'Livré'
    ];
    return isset($map[$status]) ? $map[$status] : ucfirst($status);
}
?>

<body>
    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="mobile-menu-overlay"></div>
    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="title"><h2 class="h3 mb-0">Historique Demandes de Chéquiers</h2></div>
                        <nav aria-label="breadcrumb"><ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Historique Chéquiers</li>
                        </ol></nav>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <form method="GET" class="mb-20" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                        <div><label>Recherche</label><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Compte / Nom / RIB"></div>
                        <div><label>Statut</label><select name="status" class="form-control"><option value="">Tous</option><option value="encours" <?php if ($status_filter=='encours') echo 'selected'; ?>>En cours</option><option value="reçu" <?php if ($status_filter=='reçu') echo 'selected'; ?>>Reçu</option><option value="livré" <?php if ($status_filter=='livré') echo 'selected'; ?>>Livré</option><option value="prestataire" <?php if ($status_filter=='prestataire') echo 'selected'; ?>>Prestataire</option></select></div>
                        <div><label>Date du</label><input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control"></div>
                        <div><label>Au</label><input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control"></div>
                        <div><button type="submit" class="btn btn-primary">Appliquer</button> <a href="historique_demande_chequier.php" class="btn btn-secondary">Réinitialiser</a></div>
                    </form>

                    <div class="table-responsive">
                        <table class="data-table table hover nowrap">
                            <thead><tr><th>#</th><th>Compte</th><th>Client</th><th>Agence</th><th>Type</th><th>Quantité</th><th>Statut</th><th>Date Demande</th></tr></thead>
                            <tbody>
                    <?php foreach ($historic_requests as $idx => $req): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($req['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($req['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['agency_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['type_compte']); ?></td>
                            <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                            <td><span class="badge" style="background:#ffc107;color:#000;<?php if($req['current_status'] =='reçu'){ echo 'background:#28a745;color:#fff;'; } elseif($req['current_status']=='livré'){ echo 'background:#6f42c1;color:#fff;'; } elseif($req['current_status']=='prestataire'){ echo 'background:#17a2b8;color:#fff;'; } ?>"><?php echo status_label_php($req['current_status']); ?></span></td>
                            <td><?php echo !empty($req['created_at']) ? date('d/m/Y', strtotime($req['created_at'])) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>
</body>
</html>
