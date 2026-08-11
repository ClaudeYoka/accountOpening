<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
require_once __DIR__ . '/../includes/config.php';

function normalize_chequier_status($status) {
    $s = trim(mb_strtolower($status, 'UTF-8'));
    $s = str_replace([' ', '-'], '', $s);

    if (in_array($s, ['recu', 'reçu', 'reçue'])) {
        return 'reçu';
    }
    if (in_array($s, ['livre', 'livré'])) {
        return 'livré';
    }
    if (in_array($s, ['prestataire'])) {
        return 'prestataire';
    }
    if (in_array($s, ['encours', 'en cours'])) {
        return 'encours';
    }
    return 'encours';
}

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

$whereParts = ["tc.type_compte IS NOT NULL", "tc.type_compte != ''"];
$bindTypes = '';
$bindValues = [];

if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $whereParts[] = "(tc.account_number LIKE ? OR tc.firstname LIKE ? OR tc.nip LIKE ?)";
    $bindTypes .= 'sss';
    $bindValues[] = $searchTerm;
    $bindValues[] = $searchTerm;
    $bindValues[] = $searchTerm;
}

if ($status_filter !== '') {
    $status_filter_normalized = normalize_chequier_status($status_filter);
    if ($status_filter_normalized !== '') {
        $whereParts[] = "LOWER(REPLACE(COALESCE(cs.status, tc.access, 'encours'), ' ', '')) COLLATE utf8mb4_general_ci = ?";
        $bindTypes .= 's';
        $bindValues[] = str_replace([' ', '-'], '', mb_strtolower($status_filter_normalized, 'UTF-8'));
    }
}

if ($date_from !== '') {
    $dateFrom = DateTime::createFromFormat('Y-m-d', $date_from);
    if ($dateFrom) {
        $whereParts[] = "tc.date_enregistrement >= ?";
        $bindTypes .= 's';
        $bindValues[] = $dateFrom->format('Y-m-d') . ' 00:00:00';
    }
}

if ($date_to !== '') {
    $dateTo = DateTime::createFromFormat('Y-m-d', $date_to);
    if ($dateTo) {
        $whereParts[] = "tc.date_enregistrement <= ?";
        $bindTypes .= 's';
        $bindValues[] = $dateTo->format('Y-m-d') . ' 23:59:59';
    }
}

$where = implode(' AND ', $whereParts);

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

$stmt = mysqli_prepare($conn, $query);
$result = false;
if (!$stmt) {
    $chequier_error_message = 'Échec préparation requête : ' . mysqli_error($conn);
} else {
    if ($bindTypes !== '') {
        $bindParams = array_merge([$bindTypes], $bindValues);
        $tmp = [];
        foreach ($bindParams as $key => $value) {
            $tmp[$key] = &$bindParams[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $chequier_error_message = 'Échec exécution requête : ' . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
}
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
                        <div><label>Statut</label>
                            <select name="status" class="form-control"><option value="">Tous</option>
                                <option value="encours" <?php if ($status_filter=='encours') echo 'selected'; ?>>En cours</option>
                                <option value="reçu" <?php if ($status_filter=='reçu') echo 'selected'; ?>>Reçu</option>
                                <option value="livré" <?php if ($status_filter=='livré') echo 'selected'; ?>>Livré</option>
                                <option value="prestataire" <?php if ($status_filter=='prestataire') echo 'selected'; ?>>Prestataire</option>
                            </select>
                        </div>
                        <div><label>Date du</label><input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control"></div>
                        <div><label>Au</label><input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control"></div>
                        <div><button type="submit" class="btn btn-primary">Appliquer</button> <a href="historique_demande_chequier.php" class="btn btn-secondary">Réinitialiser</a></div>
                    </form>

                    <div class="table-responsive">
                        <table class="data-table table hover nowrap">
                            <thead>
                                <tr>
                                    <th>#</th><th>Compte</th><th>Client</th><th>Agence</th><th>Type</th><th>Quantité</th><th>Statut</th><th>Date Demande</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historic_requests as $idx => $req): ?>
                                    <tr onclick="window.location.href='chequier_request_detail.php?request_id=<?php echo $req['id']; ?>'" style="cursor: pointer;" data-request-id="<?php echo $req['id']; ?>">
                                        <td><?php echo $idx + 1; ?></td>
                                        <td><?php echo htmlspecialchars($req['account_number']); ?></td>
                                        <td><?php echo htmlspecialchars($req['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['agency_name']); ?></td>
                                        <td><?php echo htmlspecialchars($req['type_compte']); ?></td>
                                        <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                                        <td><span class="badge" style="background:#ffc107;color:#000;<?php if($req['current_status'] =='reçu'){ echo 'background:#28a745;color:#fff;'; } elseif($req['current_status']=='livré'){ echo 'background:#6f42c1;color:#fff;'; } elseif($req['current_status']=='prestataire'){ echo 'background:#17a2b8;color:#fff;'; } ?>">
                                            <?php echo status_label_php($req['current_status']); ?></span></td>
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
