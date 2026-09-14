<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

$request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
$request = null;
$error_message = '';

if ($request_id <= 0) {
    $error_message = 'Identifiant de demande invalide.';
} else {
    $stmt = mysqli_prepare($conn, "SELECT tc.*, COALESCE(tb.DepartmentName, tc.branch_code) AS agency_name
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        WHERE tc.id = ? AND tc.emp_id = ?
        LIMIT 1");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'is', $request_id, $session_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $request = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
    }

    if (!$request) {
        $error_message = 'Demande introuvable ou non accessible.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<body>
    <?php include('includes/preloader.php') ?>
    <?php include('includes/navbar.php') ?>
    <?php include('includes/right_sidebar.php') ?>
    <?php include('includes/left_sidebar.php') ?>

    <div class="mobile-menu-overlay"></div>
    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8 col-sm-12">
                        <div class="title">
                            <h2 class="h3 mb-0">Détail de la demande de chéquier</h2>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 text-right">
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="dw dw-left-arrow-1"></i> Retour au dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php else: ?>
                        <h4 class="text-blue mb-20">Demande #<?php echo (int) $request['id']; ?></h4>
                        <div class="row">
                            <?php
                            $details = [
                                'Client' => $request['firstname'] ?? '',
                                'Numéro de compte' => $request['account_number'] ?? '',
                                'Agence' => $request['agency_name'] ?? ($request['branch_code'] ?? ''),
                                'Téléphone' => $request['mobile1'] ?? '',
                                'Email' => $request['email'] ?? '',
                                'Adresse' => $request['adr_rue'] ?? '',
                                'Type de chéquier' => $request['type_compte'] ?? '',
                                'Quantité' => $request['etabliss'] ?? '',
                                'RIB' => $request['nip'] ?? '',
                                'Carte' => $request['titre'] ?? '',
                                'Frais prélevés' => $request['objectif'] ?? '',
                                'Client enrollé' => $request['devise_pref'] ?? '',
                                'Numéro de série' => $request['ident_etud'] ?? '',
                                'Statut' => $request['access'] ?? '',
                                'Date de demande' => !empty($request['date_enregistrement']) ? date('d/m/Y H:i', strtotime($request['date_enregistrement'])) : ''
                            ];
                            foreach ($details as $label => $value):
                            ?>
                                <div class="col-md-6 mb-20">
                                    <strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> :</strong>
                                    <span><?php echo nl2br(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="dw dw-left-arrow-1"></i> Retour au dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/scriptJs.php') ?>
</body>
</html>
