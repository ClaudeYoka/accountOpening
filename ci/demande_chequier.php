<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
// Récupérer les données JSON de ecobank_form_submissions pour les demandes de chéquier
// Les chéquiers sont stockés dans le JSON data du formulaire

function get_chequier_requests($conn, $account_search = '', $filter_month = '', $filter_year = '') {
    $whereConditions = ["tc.type_compte IS NOT NULL", "tc.date_enregistrement >= DATE_SUB(NOW(), INTERVAL 30 DAY)"];
    $types = '';
    $params = [];

    if (!empty($account_search)) {
        $whereConditions[] = 'tc.account_number = ?';
        $types .= 's';
        $params[] = $account_search;
    }

    // Reset global error state
    $GLOBALS['chequier_error_message'] = '';

    if (!empty($filter_month) && is_numeric($filter_month)) {
        $whereConditions[] = 'MONTH(tc.date_enregistrement) = ?';
        $types .= 'i';
        $params[] = intval($filter_month);
    }

    if (!empty($filter_year) && is_numeric($filter_year)) {
        $whereConditions[] = 'YEAR(tc.date_enregistrement) = ?';
        $types .= 'i';
        $params[] = intval($filter_year);
    }

    $whereSql = implode(' AND ', $whereConditions);

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
                tc.emp_id,
                tc.titre as has_card,
                tc.objectif as fees,
                tc.devise_pref as enrolled,
                tc.ident_etud as serial_number,
                tb.DepartmentName as agency_name,
                te.FirstName,
                te.LastName,
                te.EmailId as cso_email,
                COALESCE(cs.status, tc.access, 'encours') as current_status
            FROM tblcompte tc
            LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
            LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
            LEFT JOIN (
                SELECT request_id, status
                FROM chequier_status cs1
                WHERE cs1.changed_at = (
                    SELECT MAX(cs2.changed_at)
                    FROM chequier_status cs2
                    WHERE cs2.request_id = cs1.request_id
                )
            ) cs ON tc.id = cs.request_id
            WHERE " . $whereSql . "
            AND COALESCE(cs.status, tc.access, 'encours') COLLATE utf8mb4_general_ci != 'livré'
            ORDER BY tc.date_enregistrement DESC";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        $GLOBALS['chequier_error_message'] = 'Échec préparation requête : ' . mysqli_error($conn);
        return [];
    }

    if (!empty($types)) {
        $bindParams = array_merge([$types], $params);
        $tmp = [];
        foreach ($bindParams as $key => $value) {
            $tmp[$key] = &$bindParams[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $GLOBALS['chequier_error_message'] = 'Échec exécution requête : ' . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($result === false) {
        $GLOBALS['chequier_error_message'] = 'Échec récupération résultats : ' . mysqli_error($conn);
        return [];
    }

    return $result;
}

// Ensure a table exists to track chequier request statuses and history
function ensureChequierStatusTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS chequier_status (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        status VARCHAR(100) NOT NULL,
        changed_by INT DEFAULT NULL,
        changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
}

// Traiter les données JSON et extraire les infos de chéquier
function extract_chequier_info($input) {
    $chequier_info = [
        'requested' => false,
        'types' => [],
        'quantity' => 0
    ];

    // type_compte est un ENTIER maintenant
    if (isset($input['type_compte']) && is_numeric($input['type_compte'])) {

        // ici on récupère un INT (25, 50, 100)
        $value = (int)$input['type_compte'];

        if (in_array($value, [25, 50, 100])) {
            $chequier_info['requested'] = true;
            $chequier_info['types'][] = $value . ' Feuilles';
            // Utiliser la quantité depuis la base de données (colonne etabliss)
            $chequier_info['quantity'] = isset($input['quantity']) ? (int)$input['quantity'] : 1;
        }

        return $chequier_info;
    }

    return null;
}

// Fonction pour créer une notification
function create_notification($conn, $emp_id_recipient, $message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $stmt = mysqli_prepare($conn, "INSERT INTO tblnotification (emp_id, message, type, created_at, is_read) VALUES (?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, "ssss", $emp_id_recipient, $message, $type, $timestamp);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}



// Traiter les demandes de chéquier et envoyer les notifications
$account_search = '';
$filter_month = '';
$filter_year = '';
$chequier_error_message = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $account_search = trim($_GET['search']);
}

if (isset($_GET['filter_month']) && !empty($_GET['filter_month'])) {
    $filter_month = $_GET['filter_month'];
}

if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $filter_year = $_GET['filter_year'];
}

$result = get_chequier_requests($conn, $account_search, $filter_month, $filter_year);
$chequier_requests = array();

// Ensure status table exists
ensureChequierStatusTable($conn);

if (!empty($GLOBALS['chequier_error_message'])) {
    $chequier_error_message = $GLOBALS['chequier_error_message'];
}

// Récupérer la liste des statuts déjà utilisés pour afficher toutes les options
$all_statuses = array('encours', 'Prestataire', 'Reçu', 'Livré' ); // statuts par défaut
$status_q = mysqli_query($conn, "SELECT DISTINCT status FROM chequier_status");
if ($status_q) {
    while ($sr = mysqli_fetch_assoc($status_q)) {
        $s = trim($sr['status']);
        if ($s !== '' && !in_array($s, $all_statuses)) $all_statuses[] = $s;
    }
}

function normalize_chequier_status($status) {
    $s = strtolower(trim($status));
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
    if (in_array($s, ['encours', 'encours'])) {
        return 'encours';
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

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $chequier_info = extract_chequier_info($row);
        
        if ($chequier_info && $chequier_info['requested']) {
            $row['chequier_info'] = $chequier_info;
            // Fetch current status (latest) for this request
            $stmt = mysqli_prepare($conn, "SELECT status, changed_at, changed_by FROM chequier_status WHERE request_id = ? ORDER BY changed_at DESC LIMIT 1");
            mysqli_stmt_bind_param($stmt, "i", $row['id']);
            mysqli_stmt_execute($stmt);
            $status_q = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            if ($status_q && mysqli_num_rows($status_q) > 0) {
                $srow = mysqli_fetch_assoc($status_q);
                $row['current_status'] = $srow['status'];
                $row['status_changed_at'] = $srow['changed_at'];
                $row['status_changed_by'] = $srow['changed_by'];
            } else {
                $row['current_status'] = 'encours';
                $row['status_changed_at'] = null;
                $row['status_changed_by'] = null;
            }

            // Fetch full history
            $stmt = mysqli_prepare($conn, "SELECT status, changed_at, changed_by FROM chequier_status WHERE request_id = ? ORDER BY changed_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $row['id']);
            mysqli_stmt_execute($stmt);
            $hist_q = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            $history = array();
            if ($hist_q && mysqli_num_rows($hist_q) > 0) {
                while ($hr = mysqli_fetch_assoc($hist_q)) {
                    $history[] = $hr;
                }
            }
            $row['status_history'] = $history;
            $chequier_requests[] = $row;
            
            // Vérifier si une notification a déjà été envoyée pour cette demande
            // $stmt = mysqli_prepare($conn, "SELECT id FROM notifications WHERE message LIKE CONCAT('%', ?, '%') AND emp_id = ?");
            // mysqli_stmt_bind_param($stmt, "ss", $row['id'], $_SESSION['emp_id']);
            // mysqli_stmt_execute($stmt);
            // $check_notif = mysqli_stmt_get_result($stmt);
            // mysqli_stmt_close($stmt);
            
            // if ($check_notif && mysqli_num_rows($check_notif) == 0 && $row['emp_id']) {
            //     // Envoyer notification au CSO (CI)
            //     $msg_cso = "Demande de chéquier effectuée pour le compte " . $row['account_number'] . " - Client: " . $row['customer_name'] . " (Demande #" . $row['id'] . ")";
            //     create_notification($conn, $_SESSION['emp_id'], $msg_cso, 'chequier_created');
            // }
        }
    }
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
                        <div class="title">
                            <h2 class="h3 mb-0">DEMANDES DE CHÉQUIERS</h2>
                        </div>
                        <nav aria-label="breadcrumb" role="navigation">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Demandes de Chéquiers</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <a href="export_chequier_xlsx.php" class="btn btn-sm btn-success" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); border: none; color: white;">
                            <i class="fa fa-file-excel-o"></i> Bon de Commande (EXCEL)
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <!-- Barre de recherche et filtres -->
                    <div class="mb-20" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                        <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Rechercher par N°</label>
                                <input type="text" name="search" placeholder="N° de compte" value="<?php echo htmlspecialchars($account_search); ?>" class="form-control" style="width: 150px;">
                            </div>
                            
                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Mois :</label>
                                <select id="filter_month" name="filter_month" class="form-control" style="width: 100px;">
                                    <option value="">Tous</option>
                                    <?php 
                                        for ($m = 1; $m <= 12; $m++) {
                                            $selected = ($m == $filter_month) ? 'selected' : '';
                                            $month_name = date('F', mktime(0, 0, 0, $m, 1));
                                            echo '<option value="' . str_pad($m, 2, '0', STR_PAD_LEFT) . '" ' . $selected . '>' . $month_name . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Année :</label>
                                <select id="filter_year" name="filter_year" class="form-control" style="width: 100px;">
                                    <option value="">Toutes</option>
                                    <?php
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                            $selected = ($y == $filter_year) ? 'selected' : '';
                                            echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-sm btn-primary">Appliquer</button>
                            
                            <?php if (!empty($account_search) || !empty($filter_month) || !empty($filter_year)): ?>
                                <a href="?" class="btn btn-sm btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    

                    <?php if (!empty($chequier_error_message)): ?>
                        <div class="alert alert-danger" role="alert" style="margin-bottom: 15px;">
                            <strong>Erreur :</strong> <?php echo htmlspecialchars($chequier_error_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <div id="chequier-status-feedback" style="margin-bottom: 16px; display: none;"></div>

                    <?php if (empty($chequier_requests)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fa fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p style="color: #999; font-size: 16px;">Aucune demande de chéquier pour le moment</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-20">Total : <strong><?php echo count($chequier_requests); ?></strong> demande(s)</h4>
                        
                        <div class="table-responsive">
                            <table class="data-table table hover multiple-select-row nowrap">
                                <thead>
                                    <tr>
                                        <th class="table-plus">AGENCE</th>
                                        <th>NOM CLIENT</th>
                                        <th>COMPTE</th>
                                        <th>TYPE CHÉQUIER</th>
                                        <th>QUANTITÉ</th>
                                        <th>STATUT</th>
                                        <th>DATE DEMANDE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chequier_requests as $req): ?>
                                        <tr ondblclick="showDetailsCI(<?php echo $req['id']; ?>)" style="cursor: pointer;" data-request-id="<?php echo $req['id']; ?>">
                                            <td class="table-plus">
                                                <span class="badge" style="background: #007db8; color: white; padding: 6px 12px; border-radius: 4px;">
                                                    <?php echo htmlspecialchars($req['branch_code']); ?>
                                                </span>
                                                <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                                    <?php echo htmlspecialchars($req['agency_name'] ?? ''); ?>
                                                </div>
                                            </td>

                                            <td>
                                                <small><div class="weight-600"><?php echo htmlspecialchars($req['customer_name']); ?></div></small> 
                                            </td>

                                            <td>
                                                <code style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px;">
                                                    <?php echo htmlspecialchars($req['account_number']); ?>
                                                </code>
                                            </td>
                                        
                                            <td>
                                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                    <?php foreach ($req['chequier_info']['types'] as $type): ?>
                                                        <span class="badge" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                                            <?php echo htmlspecialchars($type); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="font-size: 16px; color: #D32F2F;">
                                                    <?php echo htmlspecialchars($req['chequier_info']['quantity'], ENT_QUOTES, 'UTF-8'); ?>
                                                </strong>
                                            </td>
                                            
                                            <td>
                                                <?php
                                                $status_raw = $req['current_status'] ?? $req['status'] ?? 'encours';
                                                $status_norm = normalize_chequier_status($status_raw);
                                                $status_color = '#FFC107';
                                                $status_label = status_label_php($status_norm);

                                                if ($status_norm === 'prestataire') {
                                                    $status_color = '#17A2B8';
                                                } elseif ($status_norm === 'reçu') {
                                                    $status_color = '#28A745';
                                                } elseif ($status_norm === 'livré') {
                                                    $status_color = '#6F42C1';
                                                } else {
                                                    $status_color = '#FFC107';
                                                }
                                                ?>
                                                <span class="badge" style="background: <?php echo $status_color; ?>; color: white; padding: 6px 12px; border-radius: 4px;">
                                                    <?php echo htmlspecialchars($status_label); ?>
                                                </span>
                                                <div class="dropdown" style="display: inline-block; margin-left: 6px;">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Modifier le statut">
                                                        ⋮
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <?php foreach ($all_statuses as $st): ?>
                                                            <a class="dropdown-item" href="#" onclick="updateChequierStatus(<?php echo $req['id']; ?>, '<?php echo addslashes($st); ?>'); return false;"><?php echo htmlspecialchars(status_label_php($st)); ?></a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($req['status_changed_at'] ?? $req['created_at'])); ?></small>
                                            </td>
                                            
                                    
                                        </tr>

                                        <!-- Modal Voir Détails -->
                                        <div class="modal fade" id="viewDetailsModal<?php echo $req['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white;">
                                                        <h5 class="modal-title" id="viewDetailsModalLabel">Détails Demande de Chéquier #<?php echo $req['id']; ?></h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Agence :</label>
                                                            <p><?php echo htmlspecialchars($req['branch_code'] . ' - ' . ($req['agency_name'] ?? 'N/A')); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Nom du Client :</label>
                                                            <p><?php echo htmlspecialchars($req['customer_name']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Numéro de Compte :</label>
                                                            <p><code><?php echo htmlspecialchars($req['account_number']); ?></code></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Email :</label>
                                                            <p><?php echo htmlspecialchars($req['email']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Types de Chéquiers :</label>
                                                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                                <?php foreach ($req['chequier_info']['types'] as $type): ?>
                                                                    <span class="badge" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white; padding: 8px 12px; border-radius: 4px;">
                                                                        <?php echo htmlspecialchars($type); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Quantité Totale :</label>
                                                            <p><strong style="font-size: 18px; color: #D32F2F;"><?php echo htmlspecialchars($req['chequier_info']['quantity'], ENT_QUOTES, 'UTF-8'); ?> chéquier(s)</strong></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Date de la Demande :</label>
                                                            <p><?php echo date('d/m/Y à H:i', strtotime($req['created_at'])); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">CSO (Responsable) :</label>
                                                            <p><?php echo htmlspecialchars(($req['FirstName'] ?? '') . ' ' . ($req['LastName'] ?? '') . ' (' . ($req['cso_email'] ?? '') . ')'); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                                    <!-- Hidden history JSON for JS modal -->
                                                    <script type="application/json" id="history-data-<?php echo $req['id']; ?>" style="display:none;">
                                                        <?php echo json_encode($req['status_history']); ?>
                                                    </script>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>

    <script>
        function generateDeliveryForm(requestId) {
            // Ouvrir le bon de livraison pour l'impression
            window.open('generate_chequier_delivery.php?id=' + requestId, '_blank');
        }

        function markAsProcessed(requestId) {
            if (confirm('Marquer cette demande comme traitée ?')) {
                // Envoyer une requête pour marquer comme traité
                fetch('mark_chequier_processed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayChequierFeedback('success', 'Demande marquée comme traitée');
                        setTimeout(function(){ location.reload(); }, 700);
                    } else {
                        displayChequierFeedback('error', 'Erreur: ' + (data.message || 'Impossible de traiter')); 
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayChequierFeedback('error', 'Une erreur est survenue');
                });
            }
        }
    </script>
    <script>
        function displayChequierFeedback(type, text) {
            var container = document.getElementById('chequier-status-feedback');
            if (!container) return;
            container.style.display = 'block';
            var color = type === 'success' ? '#d4edda' : '#f8d7da';
            var border = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            var textColor = type === 'success' ? '#155724' : '#721c24';
            container.style.background = color;
            container.style.border = '1px solid ' + border;
            container.style.color = textColor;
            container.style.padding = '10px 14px';
            container.style.borderRadius = '4px';
            container.innerHTML = '<strong>' + (type === 'success' ? 'OK' : 'Erreur') + ' :</strong> ' + text;

            setTimeout(function() {
                container.style.display = 'none';
            }, 6000);
        }

        function updateChequierStatus(requestId, newStatus) {
            if (!confirm('Confirmer le changement de statut en : ' + newStatus + ' ?')) return;

            fetch('update_chequier_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, status: newStatus })
            })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    displayChequierFeedback('success', j.message || 'Statut mis à jour.');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    displayChequierFeedback('error', j.message || 'Erreur inconnue');
                }
            })
            .catch(err => {
                console.error(err);
                displayChequierFeedback('error', 'Erreur de communication. Réessayez.');
            });
        }

        function showHistory(requestId) {
            // Chercher l'historique injecté côté PHP dans le DOM (data attribute technique)
            // Nous allons ouvrir un modal dynamique
            var historyData = null;
            // essayer de trouver la variable PHP via un element #history-data-<id>
            var el = document.getElementById('history-data-' + requestId);
            if (el) {
                try { historyData = JSON.parse(el.textContent); } catch(e){ historyData = null; }
            }

            var modalHtml = '<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">'
                + '<div class="modal-header" style="background:#007db8;color:#fff;"><h5 class="modal-title">Historique statut - demande #' + requestId + '</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>'
                + '<div class="modal-body">';

            if (historyData && historyData.length > 0) {
                modalHtml += '<ul class="list-group">';
                historyData.forEach(function(h){
                    modalHtml += '<li class="list-group-item">' + h.status + ' <br><small class="text-muted">Le ' + h.changed_at + (h.changed_by ? ' par ID#'+h.changed_by : '') + '</small></li>';
                });
                modalHtml += '</ul>';
            } else {
                modalHtml += '<p>Aucun historique disponible.</p>';
            }

            modalHtml += '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button></div></div></div></div>';

            // Append and show
            var wrapper = document.createElement('div');
            wrapper.innerHTML = modalHtml;
            document.body.appendChild(wrapper);
            $('#historyModal').modal('show');
            // cleanup on hide
            $('#historyModal').on('hidden.bs.modal', function(){ wrapper.remove(); });
        }

        function showDetailsCI(requestId) {
            fetch('get_chequier_details.php?request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const req = data.request;
                        let html = `
                            <div class="mb-3">
                                <label class="font-weight-600">Agence : ${escapeHtml(req.branch_code)} - ${escapeHtml(req.agency_name || 'N/A')}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Nom du Client : ${escapeHtml(req.customer_name)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Numéro de Compte : ${escapeHtml(req.account_number)}</label>
                                <p><code></code></p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Email : ${escapeHtml(req.email || 'N/A')}</label>
                                <p></p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Type de Chéquier : ${escapeHtml(req.type_compte)} Feuilles</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Adresse : ${escapeHtml(req.address || 'N/A')}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Statut :
                                    <span style="display: inline-block; padding: 6px 12px; background-color: ${getStatusColorCI(req.status).bg}; color: ${getStatusColorCI(req.status).color}; border-radius: 4px; font-weight: 500;">
                                        ${getStatusLabelCI(req.status)}
                                    </span>
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Date de Demande : ${new Date(req.created_at).toLocaleString('fr-FR')}</label>
                            </div> `;
                        
                        let modalHtml = '<div class="modal fade" id="detailsModalCI" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white;"><h5 class="modal-title">Détails de la Demande de Chéquier</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">' + html + '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button></div></div></div></div>';
                        
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = modalHtml;
                        document.body.appendChild(wrapper);
                        $('#detailsModalCI').modal('show');
                        $('#detailsModalCI').on('hidden.bs.modal', function(){ wrapper.remove(); });
                    } else {
                        displayChequierFeedback('error', 'Erreur : ' + (data.error || 'Impossible de charger les détails'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    displayChequierFeedback('error', 'Erreur réseau lors du chargement des détails');
                });
        }

        function getStatusColorCI(status) {
            const colors = {
                'encours': { bg: '#FFC107', color: 'white' },
                'reçu': { bg: '#17A2B8', color: 'white' },
                'livré': { bg: '#28A745', color: 'white' }
            };
            return colors[status] || { bg: '#6C757D', color: 'white' };
        }

        function getStatusLabelCI(status) {
            const labels = {
                'encours': 'En cours',
                'reçu': 'Reçu',
                'livré': 'Livré'
            };
            return labels[status] || status;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
