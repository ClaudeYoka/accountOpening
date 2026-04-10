<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php include('../includes/audit_logger.php')?>
<?php include('../includes/audit_helpers.php')?>

<?php
// Check if user is admin
if (!isset($_SESSION['arole']) || strtolower($_SESSION['arole']) !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle filters
$filters = [];
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if (!empty($date_from)) $filters['date_from'] = $date_from;
if (!empty($date_to)) $filters['date_to'] = $date_to;
if (!empty($user_id)) $filters['user_id'] = $user_id;
if (!empty($action)) $filters['action'] = $action;

// Get audit logs
try {
    $logs = AuditLogger::getLogs($conn, $filters, $limit, $offset);
    echo "<!-- Debug: " . count($logs) . " logs retrieved -->";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des logs: " . htmlspecialchars($e->getMessage()) . "</div>";
    $logs = [];
}

// Get unique users and actions for filter dropdowns
$users_query = mysqli_query($conn, "SELECT DISTINCT user_id FROM audit_logs WHERE user_id IS NOT NULL ORDER BY user_id");
$actions_query = mysqli_query($conn, "SELECT DISTINCT action FROM audit_logs ORDER BY action");

$unique_users = [];
$unique_actions = [];

if ($users_query) {
    while ($row = mysqli_fetch_assoc($users_query)) {
        $unique_users[] = $row['user_id'];
    }
}

if ($actions_query) {
    while ($row = mysqli_fetch_assoc($actions_query)) {
        $unique_actions[] = $row['action'];
    }
}

// Handle cleanup action
if (isset($_POST['cleanup_logs'])) {
    check_csrf();
    $days = intval($_POST['cleanup_days'] ?? 90);
    $deleted = AuditLogger::cleanOldLogs($conn, $days);
    $cleanup_message = "Nettoyage effectué : $deleted anciens logs supprimés.";
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
                            <h2 class="h3 mb-0">LOGS D'AUDIT</h2>
                        </div>
                        <nav aria-label="breadcrumb" role="navigation">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Logs d'Audit</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#cleanupModal">
                            <i class="fa fa-trash"></i> Nettoyer les anciens logs
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <?php if (isset($cleanup_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Succès :</strong> <?php echo htmlspecialchars($cleanup_message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="mb-20" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                        <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Utilisateur :</label>
                                <select name="user_id" class="form-control" style="width: 120px;">
                                    <option value="">Tous</option>
                                    <?php foreach ($unique_users as $uid): ?>
                                        <option value="<?php echo htmlspecialchars($uid); ?>" <?php echo ($user_id == $uid) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($uid); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Action :</label>
                                <select name="action" class="form-control" style="width: 150px;">
                                    <option value="">Toutes</option>
                                    <?php foreach ($unique_actions as $act): ?>
                                        <option value="<?php echo htmlspecialchars($act); ?>" <?php echo ($action == $act) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($act); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Date début :</label>
                                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control" style="width: 140px;">
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Date fin :</label>
                                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control" style="width: 140px;">
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Limite :</label>
                                <select name="limit" class="form-control" style="width: 80px;">
                                    <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100</option>
                                    <option value="200" <?php echo ($limit == 200) ? 'selected' : ''; ?>>200</option>
                                    <option value="500" <?php echo ($limit == 500) ? 'selected' : ''; ?>>500</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>

                            <?php if (!empty($date_from) || !empty($date_to) || !empty($user_id) || !empty($action)): ?>
                                <a href="audit_logs.php" class="btn btn-sm btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($logs)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fa fa-file-text-o" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p style="color: #999; font-size: 16px;">Aucun log d'audit trouvé</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-20">Total : <strong><?php echo count($logs); ?></strong> log(s)</h4>

                        <div class="table-responsive">
                            <table class="data-table table hover multiple-select-row nowrap">
                                <thead>
                                    <tr>
                                        <th>Date/Heure</th>
                                        <th>Utilisateur</th>
                                        <th>Action</th>
                                        <th>Détails</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $log_count = 0;
                                    foreach ($logs as $log): 
                                        $log_count++;
                                        if ($log_count <= 3) { // Debug only first 3 logs
                                            echo "<!-- Debug log " . $log_count . ": " . json_encode(array_keys($log)) . " -->";
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['timestamp'] ?? $log['created_at'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                $user_display = $log['user_fullname'] ?? $log['user_id'] ?? 'Système';
                                                if ($user_display === 'Système') {
                                                    echo '<span class="text-muted">Système</span>';
                                                } else {
                                                    echo '<strong>' . htmlspecialchars($user_display) . '</strong>';
                                
                                                     if (!empty($log['user_role'])) {
                                                        echo '<br><small class="text-muted">' . htmlspecialchars($log['user_role']) . '</small>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: #007db8; color: white; padding: 4px 8px; border-radius: 3px;">
                                                    <?php
                                                    $action_desc = get_action_description($log['action']);
                                                    echo htmlspecialchars($action_desc);
                                                    ?>
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['action']); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $details = $log['details'] ?? '';
                                                if (!empty($details)) {
                                                    $decoded = json_decode($details, true);
                                                    if ($decoded !== null) {
                                                        echo '<div style="font-size: 12px;">';
                                                        foreach ($decoded as $key => $value) {
                                                            $display_key = ucfirst(str_replace('_', ' ', $key));
                                                            $display_value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
                                                            echo '<strong>' . htmlspecialchars($display_key) . ':</strong> ' . htmlspecialchars($display_value) . '<br>';
                                                        }
                                                        echo '</div>';
                                                    } else {
                                                        echo '<span style="font-size: 12px; color: #666;">' . htmlspecialchars($details) . '</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if (count($logs) >= $limit): ?>
                            <div class="mt-20" style="text-align: center;">
                                <?php
                                $current_page = floor($offset / $limit) + 1;
                                $prev_offset = max(0, $offset - $limit);
                                $next_offset = $offset + $limit;

                                $query_params = $_GET;
                                unset($query_params['offset']);
                                $base_url = 'audit_logs.php?' . http_build_query($query_params);
                                ?>

                                <?php if ($offset > 0): ?>
                                    <a href="<?php echo $base_url; ?>&offset=<?php echo $prev_offset; ?>" class="btn btn-sm btn-secondary">Précédent</a>
                                <?php endif; ?>

                                <span class="mx-10">Page <?php echo $current_page; ?></span>

                                <a href="<?php echo $base_url; ?>&offset=<?php echo $next_offset; ?>" class="btn btn-sm btn-primary">Suivant</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cleanup Modal -->
            <div class="modal fade" id="cleanupModal" tabindex="-1" role="dialog" aria-labelledby="cleanupModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cleanupModalLabel">Nettoyer les anciens logs</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form method="POST">
                            <?php echo get_csrf_field(); ?>
                            <div class="modal-body">
                                <p>Cette action supprimera définitivement tous les logs plus anciens que le nombre de jours spécifié.</p>
                                <div class="form-group">
                                    <label for="cleanup_days">Nombre de jours à conserver :</label>
                                    <input type="number" class="form-control" id="cleanup_days" name="cleanup_days" value="90" min="1" max="365" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                <button type="submit" name="cleanup_logs" class="btn btn-warning">Nettoyer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>
</body>
</html>