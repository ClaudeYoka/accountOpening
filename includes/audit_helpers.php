<?php
/**
 * Audit Logging Helper Functions
 * Common audit logging patterns for the application
 */

// load audit logger functions if absent
if (!function_exists('audit_log_form_submission')) {
    $auditLoggerPath = __DIR__ . '/audit_logger.php';
    if (file_exists($auditLoggerPath)) {
        require_once $auditLoggerPath;
    }
}

/**
 * Get human-readable action description
 */
function get_action_description($action) {
    $descriptions = [
        // User management
        'created' => 'Création d\'utilisateur',
        'deleted' => 'Suppression d\'utilisateur',
        'updated' => 'Modification d\'utilisateur',
        'login' => 'Connexion',
        'logout' => 'Déconnexion',

        // Department actions
        'department_created' => 'Création de département',
        'department_updated' => 'Modification de département',
        'department_deleted' => 'Suppression de département',

        // Agency actions
        'agency_created' => 'Création d\'agence',
        'agency_updated' => 'Modification d\'agence',
        'agency_deleted' => 'Suppression d\'agence',

        // Form submissions
        'account_opening_form' => 'Soumission formulaire ouverture compte',
        'chequier_request' => 'Demande de chéquier',

        // Security events
        'failed_login' => 'Tentative de connexion échouée',
        'suspicious_activity' => 'Activité suspecte détectée',

        // Admin actions
        'page_access' => 'Accès à la page',
        'session_active' => 'Session active',
        'config_change' => 'Modification de configuration',
        'file_upload' => 'Téléchargement de fichier',
        'data_export' => 'Export de données',

        // Database operations
        'db_insert' => 'Insertion en base de données',
        'db_update' => 'Mise à jour en base de données',
        'db_delete' => 'Suppression en base de données',

        // Notifications
        'notification_created' => 'Création de notification',
        'notification_read' => 'Notification lue',
        'notification_deleted' => 'Suppression de notification'
    ];

    return $descriptions[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

// Log admin actions
function log_admin_access($conn, $page) {
    audit_log_admin_action($conn, 'page_access', ['page' => $page]);
}

function log_admin_user_management($conn, $action, $target_user_id, $details = []) {
    // Get target user info for better logging
    $target_info = [];
    if ($target_user_id) {
        $user_query = mysqli_prepare($conn, "SELECT FirstName, LastName, Department FROM tblemployees WHERE emp_id = ?");
        if ($user_query) {
            mysqli_stmt_bind_param($user_query, "s", $target_user_id);
            mysqli_stmt_execute($user_query);
            $user_result = mysqli_stmt_get_result($user_query);
            if ($user_result && $user = mysqli_fetch_assoc($user_result)) {
                $target_info = [
                    'target_user_name' => $user['FirstName'] . ' ' . $user['LastName'],
                    'target_user_department' => $user['Department']
                ];
            }
            mysqli_stmt_close($user_query);
        }
    }

    audit_log_user_action($conn, $action, $_SESSION['alogin'] ?? null, array_merge([
        'target_user_id' => $target_user_id
    ], $target_info, $details));
}

function log_admin_department_action($conn, $action, $department_id, $details = []) {
    // Get department info for better logging
    $dept_info = [];
    if ($department_id) {
        $dept_query = mysqli_prepare($conn, "SELECT DepartmentName FROM tbldepartments WHERE id = ?");
        if ($dept_query) {
            mysqli_stmt_bind_param($dept_query, "i", $department_id);
            mysqli_stmt_execute($dept_query);
            $dept_result = mysqli_stmt_get_result($dept_query);
            if ($dept_result && $dept = mysqli_fetch_assoc($dept_result)) {
                $dept_info = ['department_name' => $dept['DepartmentName']];
            }
            mysqli_stmt_close($dept_query);
        }
    }

    audit_log_admin_action($conn, 'department_' . $action, array_merge([
        'department_id' => $department_id
    ], $dept_info, $details));
}

function log_admin_agency_action($conn, $action, $agency_id, $details = []) {
    // Get agency info for better logging
    $agency_info = [];
    if ($agency_id) {
        $agency_query = mysqli_prepare($conn, "SELECT agence_name FROM tblagence WHERE id = ?");
        if ($agency_query) {
            mysqli_stmt_bind_param($agency_query, "i", $agency_id);
            mysqli_stmt_execute($agency_query);
            $agency_result = mysqli_stmt_get_result($agency_query);
            if ($agency_result && $agency = mysqli_fetch_assoc($agency_result)) {
                $agency_info = ['agency_name' => $agency['agence_name']];
            }
            mysqli_stmt_close($agency_query);
        }
    }

    audit_log_admin_action($conn, 'agency_' . $action, array_merge([
        'agency_id' => $agency_id
    ], $agency_info, $details));
}

// Log form submissions
function log_form_submission_success($conn, $form_type, $submission_id, $customer_info = []) {
    // Enhance customer info for better logging
    $enhanced_info = $customer_info;
    if (!empty($customer_info['customer_name'])) {
        $enhanced_info['customer_display'] = $customer_info['customer_name'];
    }
    if (!empty($customer_info['account_number'])) {
        $enhanced_info['account_number'] = $customer_info['account_number'];
    }

    audit_log_form_submission($conn, $form_type, $submission_id, $enhanced_info);
}

// Log security events
function log_failed_login_attempt($conn, $username, $ip_address) {
    // Get additional context for failed login
    $context = [
        'attempted_username' => $username,
        'ip_address' => $ip_address,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];

    audit_log_security_event($conn, 'failed_login', $context);
}

function log_suspicious_activity($conn, $activity_type, $details = []) {
    audit_log_security_event($conn, $activity_type, $details);
}

// Log data exports
function log_data_export($conn, $export_type, $filters = [], $record_count = 0) {
    audit_log_export($conn, $export_type, $filters, $record_count);
}

// Log notifications
function log_notification_action($conn, $action, $notification_id, $details = []) {
    audit_log_admin_action($conn, 'notification_' . $action, array_merge([
        'notification_id' => $notification_id
    ], $details));
}

// Log file uploads (if any)
function log_file_upload($conn, $file_type, $file_name, $file_size) {
    audit_log_admin_action($conn, 'file_upload', [
        'file_type' => $file_type,
        'file_name' => $file_name,
        'file_size' => $file_size
    ]);
}

// Log configuration changes
function log_config_change($conn, $config_type, $old_value, $new_value) {
    audit_log_admin_action($conn, 'config_change', [
        'config_type' => $config_type,
        'old_value' => $old_value,
        'new_value' => $new_value
    ]);
}

// Log database operations
function log_db_operation($conn, $operation, $table, $record_id = null, $details = []) {
    audit_log_admin_action($conn, 'db_' . $operation, array_merge([
        'table' => $table,
        'record_id' => $record_id
    ], $details));
}

// Initialize audit logging for admin pages
function init_admin_audit_logging($conn, $page_name) {
    // Log page access
    log_admin_access($conn, $page_name);

    // Log session information for security
    if (isset($_SESSION['alogin'])) {
        audit_log_admin_action($conn, 'session_active', [
            'user_id' => $_SESSION['alogin'],
            'role' => $_SESSION['arole'] ?? 'unknown',
            'department' => $_SESSION['adepart'] ?? 'unknown'
        ]);
    }
}

// Wrapper for log_admin_action without explicit $conn (uses global)
function log_admin_action($action, $record_id = 0, $details = []) {
    global $conn;
    if ($conn) {
        audit_log_admin_action($conn, $action, array_merge([
            'record_id' => $record_id
        ], $details));
    }
}
?>