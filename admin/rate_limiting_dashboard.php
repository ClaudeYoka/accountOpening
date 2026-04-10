<?php 
/**
 * Admin Rate Limiting Dashboard
 * Monitore les violations et permet la gestion
 */

include('./includes/header.php');
include('../includes/session.php');
include('../includes/config.php'); // Assurer que $dbh est défini
include('../includes/audit_logger.php');
include('../includes/audit_helpers.php'); // Ajouté pour log_admin_action
include('../includes/RateLimiter.php');

// DEBUG: Afficher info session pour diagnostic
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "SESSION arole: " . ($_SESSION['arole'] ?? 'NOT SET') . "\n";
    echo "SESSION alogin: " . ($_SESSION['alogin'] ?? 'NOT SET') . "\n";
    echo "SESSION adepart: " . ($_SESSION['adepart'] ?? 'NOT SET') . "\n";
    echo "SERVER REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "PHP VERSION: " . PHP_VERSION . "\n";
    echo "CURRENT TIME: " . date('Y-m-d H:i:s') . "\n";
    echo "</pre>";
    
    // Test DB connection
    try {
        $test_stmt = $conn->prepare("SELECT 1");
        $test_stmt->execute();
        echo "DB Connection (mysqli): OK\n";
    } catch (Exception $e) {
        echo "DB Connection (mysqli) ERROR: " . $e->getMessage() . "\n";
    }
    
    // Test PDO connection
    if (isset($dbh)) {
        try {
            $test_pdo = $dbh->prepare("SELECT 1");
            $test_pdo->execute();
            echo "DB Connection (PDO): OK\n";
        } catch (Exception $e) {
            echo "DB Connection (PDO) ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "DB Connection (PDO): \$dbh NOT SET\n";
    }
    
    exit;
}

// Vérifier permissions admin
if (!isset($_SESSION['arole']) || $_SESSION['arole'] !== 'Admin') {
    // DEBUG: Log pourquoi on redirige
    error_log("Rate limiting dashboard access denied. Session arole: " . ($_SESSION['arole'] ?? 'NOT SET') . ", URI: " . $_SERVER['REQUEST_URI']);
    header('Location: ../index.php');
    exit;
}

try {
    $limiter = new RateLimiter($dbh);
} catch (Exception $e) {
    error_log("RateLimiter instantiation error: " . $e->getMessage());
    die("Erreur RateLimiter: " . $e->getMessage());
}

// Traiter les actions
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['action'] ?? '';
    
    if ($post_action === 'unblock') {
        $identifier = $_POST['identifier'] ?? '';
        $violation_action = $_POST['violation_action'] ?? 'login';
        
        $limiter->unblock($violation_action, $identifier);
        $action_message = 'Identifiant débloqué avec succès.';
        
        // Log audit
        log_admin_action('RATE_LIMIT_UNBLOCK', 0, [
            'action' => $violation_action,
            'identifier' => $identifier,
            'admin_id' => $_SESSION['alogin'] ?? 'unknown'
        ]);
    }
    
    if ($post_action === 'whitelist') {
        $identifier = $_POST['identifier'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        $limiter->addWhitelist($identifier, $reason);
        $action_message = 'Identifiant ajouté à la whitelist.';
        
        log_admin_action('RATE_LIMIT_WHITELIST', 0, [
            'identifier' => $identifier,
            'reason' => $reason,
            'admin_id' => $_SESSION['alogin'] ?? 'unknown'
        ]);
    }
}

// Récupérer les stats
try {
    $blocked_logins = $limiter->getBlockedIdentifiers('login', 5, 300);
    $blocked_forms = $limiter->getBlockedIdentifiers('form_submit_ecobank', 5, 60);
    $blocked_apis = $limiter->getBlockedIdentifiers('api_flexcube', 100, 3600);
    $all_violations = count($blocked_logins) + count($blocked_forms) + count($blocked_apis);
} catch (Exception $e) {
    error_log("RateLimiter stats error: " . $e->getMessage());
    $blocked_logins = [];
    $blocked_forms = [];
    $blocked_apis = [];
    $all_violations = 0;
}
?>

<body>
    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6 col-sm-12">
                        <h4 class="font-20 weight-500 mb-10 text-capitalize">
                            🔒 Rate Limiting Dashboard
                        </h4>
                        <small class="text-muted">
                            <a href="?debug=1" class="text-info" target="_blank">🔍 Debug Session</a>
                        </small>
                    </div>
                       
                    <div class="col-md-6 col-sm-12 text-right">
                        <a href="." class="btn btn-sm btn-primary">
                            ← Retour
                        </a>
                        <a href="monitoring_security.php" class="btn btn-sm btn-primary">
                            ← Retour Sécurité
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($action_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($action_message); ?>
                </div>
            <?php endif; ?>

            <!-- Overview Cards -->
            <div class="row">
                <div class="col-md-3 mb-20">
                    <div class="card box-shadow">
                        <div class="card-body">
                            <h5 class="card-title">Logins Bloqués</h5>
                            <h3 class="text-danger"><?php echo count($blocked_logins); ?></h3>
                            <p class="text-muted">5 tentatives / 5 min</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-20">
                    <div class="card box-shadow">
                        <div class="card-body">
                            <h5 class="card-title">Formulaires Bloqués</h5>
                            <h3 class="text-danger"><?php echo count($blocked_forms); ?></h3>
                            <p class="text-muted">5 soumissions / 60 sec</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-20">
                    <div class="card box-shadow">
                        <div class="card-body">
                            <h5 class="card-title">APIs Bloquées</h5>
                            <h3 class="text-danger"><?php echo count($blocked_apis); ?></h3>
                            <p class="text-muted">100 appels / 60 min</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-20">
                    <div class="card box-shadow">
                        <div class="card-body">
                            <h5 class="card-title">Total Violations</h5>
                            <h3 class="text-warning"><?php echo $all_violations; ?></h3>
                            <p class="text-muted">24 dernières heures</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tentatives de Login Bloquées -->
            <div class="row mt-20">
                <div class="col-md-12 mb-20">
                    <div class="card box-shadow">
                        <div class="card-header">
                            <h5 class="card-title">🚨 Tentatives de Login Bloquées (Dernier 5 min)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($blocked_logins) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>IP Address</th>
                                                <th>Attempts</th>
                                                <th>Retry After (sec)</th>
                                                <th>Last Attempt</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($blocked_logins as $block): ?>
                                                <tr class="<?php echo $block['seconds_remaining'] > 0 ? 'table-danger' : 'table-warning'; ?>">
                                                    <td>
                                                        <code><?php echo htmlspecialchars(substr($block['identifier'], 0, 16)) . '...'; ?></code>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-danger"><?php echo $block['attempts']; ?> / 5</span>
                                                    </td>
                                                    <td>
                                                        <?php echo max(0, $block['seconds_remaining']); ?> sec
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-secondary">
                                                            <?php echo htmlspecialchars($block['last_attempt']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="unblock">
                                                            <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($block['identifier']); ?>">
                                                            <input type="hidden" name="violation_action" value="login">
                                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Débloquer?')">
                                                                🔓 Débloquer
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">✅ Aucune tentative de login bloquée.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Soumissions de Formulaires Bloquées -->
            <div class="row mt-20">
                <div class="col-md-12 mb-20">
                    <div class="card box-shadow">
                        <div class="card-header">
                            <h5 class="card-title">📝 Soumissions de Formulaires Bloquées (Dernier 1 min)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($blocked_forms) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Identifiant</th>
                                                <th>Tentatives</th>
                                                <th>Retry After</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($blocked_forms as $block): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars(substr($block['identifier'], 0, 16)) . '...'; ?></code></td>
                                                    <td><span class="badge badge-danger"><?php echo $block['attempts']; ?> / 5</span></td>
                                                    <td><?php echo max(0, $block['seconds_remaining']); ?> sec</td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="action" value="unblock">
                                                            <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($block['identifier']); ?>">
                                                            <input type="hidden" name="violation_action" value="form_submit_ecobank">
                                                            <button type="submit" class="btn btn-sm btn-success">🔓 Débloquer</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">✅ Aucune soumission bloquée.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Limit Stats -->
            <div class="row mt-20">
                <div class="col-md-12 mb-20">
                    <div class="card box-shadow">
                        <div class="card-header">
                            <h5 class="card-title">📊 Statistiques Rate Limiting (Dernier 1 heure)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Top Login Attempts by IP</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>IP</th>
                                                    <th>Attempts</th>
                                                    <th>Last Try</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                try {
                                                    $login_stats = $limiter->getStats('login', 10);
                                                } catch (Exception $e) {
                                                    error_log("RateLimiter getStats error: " . $e->getMessage());
                                                    $login_stats = [];
                                                    echo "<tr><td colspan='3' class='text-danger'>Erreur chargement stats: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                                }
                                                foreach ($login_stats as $stat):
                                                ?>
                                                    <tr>
                                                        <td><code><?php echo htmlspecialchars($stat['ip_address']); ?></code></td>
                                                        <td><span class="badge badge-info"><?php echo $stat['attempts']; ?></span></td>
                                                        <td><?php echo htmlspecialchars($stat['last_attempt']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Configuration Recommandée</h6>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>Limit / Window</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Login</strong></td>
                                                <td>5 / 5 min</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Password Reset</strong></td>
                                                <td>3 / 10 min</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Form Submit</strong></td>
                                                <td>5 / 1 min</td>
                                            </tr>
                                            <tr>
                                                <td><strong>API Call</strong></td>
                                                <td>100 / 1 hour</td>
                                            </tr>
                                            <tr>
                                                <td><strong>File Upload</strong></td>
                                                <td>10 / 10 min</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Whitelist Management -->
            <div class="row mt-20">
                <div class="col-md-12 mb-20">
                    <div class="card box-shadow">
                        <div class="card-header">
                            <h5 class="card-title">✅ Whitelist Management</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-20">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Identifiant (SHA256)</label>
                                            <input type="text" name="identifier" class="form-control" 
                                                   placeholder="Hash identifiant" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Raison</label>
                                            <input type="text" name="reason" class="form-control" 
                                                   placeholder="ex: Admin workstation, API server" required>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="action" value="whitelist">
                                <button type="submit" class="btn btn-primary">➕ Ajouter à Whitelist</button>
                            </form>

                            <div class="alert alert-info">
                                <strong>ℹ️ Comment obtenir l'identifiant:</strong>
                                <br>L'identifiant est affiché dans les listes de violations ci-dessus (SHA256 hash IP + User Agent).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendors/scripts/jquery.min.js"></script>
    <script src="../../vendors/scripts/bootstrap.bundle.min.js"></script>

</body>
</html>
