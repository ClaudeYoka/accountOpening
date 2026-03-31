<?php
/**
 * Business Metrics Exporter for Account Opening Application
 * Collects application-specific metrics from database
 */

include('includes/config.php');
header('Content-Type: text/plain; charset=utf-8');

// Database connection check
$db_status = 1;
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $db_status = 0;
}

echo "# HELP db_connection_status Database connection status (1=up, 0=down)\n";
echo "# TYPE db_connection_status gauge\n";
echo "db_connection_status $db_status\n";

if ($db_status == 1) {
    try {
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tblemployees");
        $users = $stmt->fetch()['total'];
        echo "# HELP app_total_users Total number of registered users\n";
        echo "# TYPE app_total_users gauge\n";
        echo "app_total_users $users\n";

        // Active sessions (rough estimate based on recent logins)
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM tbl_logins WHERE login_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $active_sessions = $stmt->fetch()['active'];
        echo "# HELP app_active_sessions Number of active user sessions in last hour\n";
        echo "# TYPE app_active_sessions gauge\n";
        echo "app_active_sessions $active_sessions\n";

        // Total accounts
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tblcompte");
        $accounts = $stmt->fetch()['total'];
        echo "# HELP app_total_accounts Total number of customer accounts\n";
        echo "# TYPE app_total_accounts gauge\n";
        echo "app_total_accounts $accounts\n";

        // Pending chequier requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM tblcompte WHERE access = 'encours'");
        $pending_chequiers = $stmt->fetch()['pending'];
        echo "# HELP app_pending_chequier_requests Number of pending chequier requests\n";
        echo "# TYPE app_pending_chequier_requests gauge\n";
        echo "app_pending_chequier_requests $pending_chequiers\n";

        // Completed chequier requests
        $stmt = $pdo->query("SELECT COUNT(*) as completed FROM tblcompte WHERE access = 'livré'");
        $completed_chequiers = $stmt->fetch()['completed'];
        echo "# HELP app_completed_chequier_requests Number of completed chequier requests\n";
        echo "# TYPE app_completed_chequier_requests gauge\n";
        echo "app_completed_chequier_requests $completed_chequiers\n";

        // Form submissions today
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM ecobank_form_submissions WHERE DATE(created_at) = CURDATE()");
        $submissions_today = $stmt->fetch()['today'];
        echo "# HELP app_form_submissions_today Number of form submissions today\n";
        echo "# TYPE app_form_submissions_today gauge\n";
        echo "app_form_submissions_today $submissions_today\n";

        // Failed login attempts (last 24h)
        $stmt = $pdo->query("SELECT COUNT(*) as failed FROM audit_logs WHERE action = 'LOGIN_FAILED' AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $failed_logins = $stmt->fetch()['failed'];
        echo "# HELP app_failed_logins_24h Number of failed login attempts in last 24 hours\n";
        echo "# TYPE app_failed_logins_24h gauge\n";
        echo "app_failed_logins_24h $failed_logins\n";

        // Successful logins (last 24h)
        $stmt = $pdo->query("SELECT COUNT(*) as success FROM audit_logs WHERE action = 'LOGIN_SUCCESS' AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $successful_logins = $stmt->fetch()['success'];
        echo "# HELP app_successful_logins_24h Number of successful logins in last 24 hours\n";
        echo "# TYPE app_successful_logins_24h gauge\n";
        echo "app_successful_logins_24h $successful_logins\n";

        // Notifications pending
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM tblnotification WHERE is_read = 0");
        $pending_notifications = $stmt->fetch()['pending'];
        echo "# HELP app_pending_notifications Number of unread notifications\n";
        echo "# TYPE app_pending_notifications gauge\n";
        echo "app_pending_notifications $pending_notifications\n";

        // Database size
        $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
        $db_size = $stmt->fetch()['size_mb'];
        echo "# HELP app_database_size_mb Database size in megabytes\n";
        echo "# TYPE app_database_size_mb gauge\n";
        echo "app_database_size_mb $db_size\n";

        // Recent errors (last 1h)
        $stmt = $pdo->query("SELECT COUNT(*) as errors FROM audit_logs WHERE action = 'ERROR' AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $recent_errors = $stmt->fetch()['errors'];
        echo "# HELP app_recent_errors Number of errors in last hour\n";
        echo "# TYPE app_recent_errors gauge\n";
        echo "app_recent_errors $recent_errors\n";

    } catch (Exception $e) {
        echo "# HELP app_metrics_error Business metrics collection error\n";
        echo "# TYPE app_metrics_error gauge\n";
        echo "app_metrics_error 1\n";
    }
}

// Application uptime (simulated - in production you'd track this properly)
$uptime_file = __DIR__ . '/app_uptime.txt';
if (!file_exists($uptime_file)) {
    file_put_contents($uptime_file, time());
}
$start_time = (int)file_get_contents($uptime_file);
$uptime_seconds = time() - $start_time;

echo "# HELP app_uptime_seconds Application uptime in seconds\n";
echo "# TYPE app_uptime_seconds counter\n";
echo "app_uptime_seconds $uptime_seconds\n";
?>