<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chequier_status (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, request_id INT NOT NULL, status VARCHAR(100) NOT NULL, changed_by INT DEFAULT NULL, changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(request_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$query = "SELECT tc.id, tc.account_number, tc.firstname AS customer_name, tc.branch_code, tc.type_compte, tc.etabliss AS quantity, tc.date_enregistrement AS created_at, COALESCE(tb.DepartmentName, tc.branch_code) AS agency_name, COALESCE(cs.status, tc.access, 'encours') AS current_status
          FROM tblcompte tc
          LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
          LEFT JOIN (SELECT request_id, status FROM chequier_status s1 WHERE s1.changed_at = (SELECT MAX(s2.changed_at) FROM chequier_status s2 WHERE s2.request_id = s1.request_id)) cs ON tc.id = cs.request_id
          WHERE LOWER(REPLACE(COALESCE(cs.status, tc.access, 'encours'), ' ', '')) COLLATE utf8mb4_general_ci = 'reçu'
          AND tc.type_compte IS NOT NULL AND tc.type_compte <> ''
          ORDER BY tc.date_enregistrement DESC";
$result = mysqli_query($conn, $query);
$requests = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $requests[] = $row;
?>
<body>
    <?php include('includes/navbar.php'); include('includes/right_sidebar.php'); include('includes/left_sidebar.php'); ?>
    <div class="mobile-menu-overlay"></div>
    <div class="main-container"><div class="pd-ltr-20">
        <div class="page-header"><div class="title">
            <h2 class="h3 mb-0">Demandes reçues</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="dashboard_chequier">Dashboard chéquier</a>
                    </li>
                    <li class="breadcrumb-item active">Demandes reçues</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="card-box mb-30"><div class="pd-20">
    <p><strong><?php echo count($requests); ?></strong> demande(s) reçue(s).</p>
    <div class="table-responsive">
        <table class="data-table table hover nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Compte</th>
                    <th>Client</th>
                    <th>Agence</th>
                    <th>Type</th>
                    <th>Quantité</th>
                    <th>Statut</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($requests as $index => $request): ?>
    <tr>
        <td><?php echo $index + 1; ?></td>
        <td><?php echo htmlspecialchars($request['account_number']); ?></td>
        <td><?php echo htmlspecialchars($request['customer_name']); ?></td>
        <td><?php echo htmlspecialchars($request['agency_name']); ?></td>
        <td><?php echo htmlspecialchars($request['type_compte']); ?></td>
        <td><?php echo htmlspecialchars($request['quantity']); ?></td>
        <td><span class="badge" style="background:#17a2b8;color:#fff;">Reçu</span></td>
        <td><?php echo !empty($request['created_at']) ? date('d/m/Y', strtotime($request['created_at'])) : '-'; ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    </div></div>
    <?php include('includes/footer.php'); ?>
</div>
</div>
<?php include('includes/scriptJs.php'); ?>
</body>
</html>
