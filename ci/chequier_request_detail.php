<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php
require_once __DIR__ . '/../includes/config.php';

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
if ($request_id <= 0) {
    header('Location: historique_demande_chequier.php');
    exit;
}

$query = "SELECT
            tc.id,
            tc.firstname AS customer_name,
            tc.branch_code,
            tc.account_number AS account_number,
            tc.mobile1 AS phone_number,
            tc.email,
            tc.type_compte,
            tc.adr_rue AS address,
            tc.nip AS rib_key,
            tc.etabliss AS quantity,
            tc.access AS status,
            tc.date_enregistrement AS created_at,
            COALESCE(tb.DepartmentName, tc.branch_code) AS agency_name
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        WHERE tc.id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    $error_message = 'Erreur de préparation de requête : ' . mysqli_error($conn);
} else {
    mysqli_stmt_bind_param($stmt, 'i', $request_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $request = mysqli_fetch_assoc($result);
    } else {
        $request = null;
        $error_message = 'Demande de chéquier introuvable.';
    }
}

function normalize_chequier_status($status) {
    $s = trim(mb_strtolower($status, 'UTF-8'));
    $s = str_replace([' ', '-'], '', $s);
    if (in_array($s, ['recu', 'reçu', 'rece', 'recue'])) {
        return 'reçu';
    }
    if (in_array($s, ['livre', 'livré'])) {
        return 'livré';
    }
    if (in_array($s, ['prestataire'])) {
        return 'prestataire';
    }
    return 'encours';
}

function status_label_php($status) {
    $map = [
        'encours' => 'En cours',
        'prestataire' => 'Prestataire',
        'reçu' => 'Reçu',
        'livré' => 'Livré'
    ];
    $key = normalize_chequier_status($status);
    return isset($map[$key]) ? $map[$key] : ucfirst($status);
}

function status_badge_style($status) {
    $status = normalize_chequier_status($status);
    switch ($status) {
        case 'reçu': return 'background:#28a745;color:#fff;';
        case 'livré': return 'background:#6f42c1;color:#fff;';
        case 'prestataire': return 'background:#17a2b8;color:#fff;';
        default: return 'background:#ffc107;color:#000;';
    }
}
?>
<body>
    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="title"><h2 class="h3 mb-0">Détail de la demande de chèque</h2></div>
                        <nav aria-label="breadcrumb"><ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="historique_demande_chequier.php">Historique Chéquiers</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Détail demande</li>
                        </ol></nav>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php else: ?>
                        <div class="row mb-30">
                            <div class="col-md-12">
                                <h4 class="mb-20">Demande # <?php echo htmlspecialchars($request['id']); ?></h4>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>Client : </strong> <?php echo htmlspecialchars($request['customer_name']); ?>
                            </div>
                            <div class="col-md-6 mb-20">
                                <strong>Compte : </strong> <?php echo htmlspecialchars($request['account_number']); ?>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>Agence : </strong> <?php echo htmlspecialchars($request['agency_name'] ?: $request['branch_code']); ?>
                            </div>
                            <div class="col-md-6 mb-20">
                                <strong>Téléphone : </strong>  <?php echo htmlspecialchars($request['phone_number']); ?>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>RIB : </strong> <?php echo htmlspecialchars($request['rib_key']); ?>
                            </div>
                            <div class="col-md-6 mb-20">
                                <strong>Email : </strong> <?php echo htmlspecialchars($request['email']); ?>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>Type de chèque : </strong> <?php echo htmlspecialchars($request['type_compte']); ?>
                            </div>
                            <div class="col-md-6 mb-20">
                                <strong>Quantité :  </strong> <?php echo htmlspecialchars($request['quantity']); ?>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>Adresse :</strong> <?php echo nl2br(htmlspecialchars($request['address'])); ?>
                            </div>
                            <div class="col-md-6 mb-20">
                                <strong>Statut :</strong>  <span class="badge" style="<?php echo status_badge_style($request['status']); ?>"><?php echo status_label_php($request['status']); ?></span>
                                <p></p>
                            </div>

                            <div class="col-md-6 mb-20">
                                <strong>Date de demande :</strong> <?php echo !empty($request['created_at']) ? date('d/m/Y H:i', strtotime($request['created_at'])) : '-'; ?>
                            </div>
                        </div>

                        <a href="historique_demande_chequier.php" class="btn btn-secondary"><i class="icon-copy dw dw-left-arrow-1"></i> Retour à l'historique</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <?php include('includes/scriptJs.php')?>
</body>
</html>
