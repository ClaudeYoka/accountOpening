<?php
/**
 * Audit Logging System for Chequier Management
 * Logs all critical actions for compliance and debugging
 */

class AuditLogger {
    private static $logFile = __DIR__ . '/../logs/audit.log';
    private static $dbTable = 'audit_logs';

    /**
     * Log an action to file and database
     */
    public static function log($action, $details = [], $conn = null) {
        $timestamp = date('Y-m-d H:i:s');
        $userId = isset($_SESSION['emp_id']) ? $_SESSION['emp_id'] : 'system';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Enrich details with context information
        $enrichedDetails = self::enrichDetails($action, $details);

        $logEntry = [
            'timestamp' => $timestamp,
            'user_id' => $userId,
            'action' => $action,
            'details' => json_encode($enrichedDetails, JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
            'user_agent' => substr($userAgent, 0, 255)
        ];

        // Log to file
        self::logToFile($logEntry);

        // Log to database if connection available
        if ($conn) {
            self::logToDatabase($logEntry, $conn);
        }
    }

    /**
     * Enrich log details with contextual information
     */
    private static function enrichDetails($action, $details) {
        $enriched = $details;

        // Add session context
        if (isset($_SESSION['alogin'])) {
            $enriched['session_user'] = $_SESSION['alogin'];
        }

        // Add page context
        if (isset($_SERVER['REQUEST_URI'])) {
            $enriched['page_url'] = $_SERVER['REQUEST_URI'];
        }

        // Add timestamp for better tracking
        $enriched['log_timestamp'] = date('Y-m-d H:i:s');

        // Add action category
        $enriched['action_category'] = self::categorizeAction($action);

        return $enriched;
    }

    /**
     * Categorize action for better organization
     */
    private static function categorizeAction($action) {
        $categories = [
            'login' => 'authentication',
            'logout' => 'authentication',
            'failed_login' => 'security',
            'suspicious_activity' => 'security',
            'page_access' => 'navigation',
            'session_active' => 'session',
            'created' => 'user_management',
            'updated' => 'user_management',
            'deleted' => 'user_management',
            'department_' => 'department_management',
            'agency_' => 'agency_management',
            'account_opening_form' => 'business',
            'chequier_request' => 'business',
            'config_change' => 'system',
            'file_upload' => 'system',
            'data_export' => 'system',
            'db_' => 'database',
            'notification_' => 'communication'
        ];

        foreach ($categories as $prefix => $category) {
            if (strpos($action, $prefix) === 0) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * Log to file system
     */
    private static function logToFile($logEntry) {
        // Ensure log directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logLine = sprintf(
            "[%s] USER:%s ACTION:%s DETAILS:%s IP:%s\n",
            $logEntry['timestamp'],
            $logEntry['user_id'],
            $logEntry['action'],
            $logEntry['details'],
            $logEntry['ip_address']
        );

        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log to database
     */
    private static function logToDatabase($logEntry, $conn) {
        // Ensure audit table exists
        self::ensureAuditTable($conn);

        $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs
            (timestamp, user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssss',
                $logEntry['timestamp'],
                $logEntry['user_id'],
                $logEntry['action'],
                $logEntry['details'],
                $logEntry['ip_address'],
                $logEntry['user_agent']
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /**
     * Ensure audit_logs table exists
     */
    private static function ensureAuditTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            user_id VARCHAR(50) DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            INDEX idx_timestamp (timestamp),
            INDEX idx_user_id (user_id),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($conn, $sql);
    }

    /**
     * Get audit logs with filtering
     */
    public static function getLogs($conn, $filters = [], $limit = 100, $offset = 0) {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $types .= 's';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'action = ?';
            $types .= 's';
            $params[] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'timestamp >= ?';
            $types .= 's';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'timestamp <= ?';
            $types .= 's';
            $params[] = $filters['date_to'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT al.*, CONCAT(e.FirstName, ' ', e.LastName) as user_fullname, e.Department as user_department, e.role as user_role
                  FROM audit_logs al
                  LEFT JOIN tblemployees e ON al.user_id = e.emp_id
                  $whereSql
                  ORDER BY al.timestamp DESC LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            $types .= 'ii';
            $params[] = $limit;
            $params[] = $offset;

            $bindParams = array_merge([$types], $params);
            $tmp = [];
            foreach ($bindParams as $key => $value) {
                $tmp[$key] = &$bindParams[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $tmp);

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            $logs = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $logs[] = $row;
                }
            }

            return $logs;
        }

        return [];
    }

    /**
     * Clean old logs (keep last N days)
     */
    public static function cleanOldLogs($conn, $daysToKeep = 90) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        $stmt = mysqli_prepare($conn, "DELETE FROM audit_logs WHERE timestamp < ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $cutoffDate);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            self::log('AUDIT_CLEANUP', ['deleted_records' => $affected, 'cutoff_date' => $cutoffDate], $conn);
            return $affected;
        }

        return 0;
    }
}

// Convenience functions for common audit actions
if (!function_exists('audit_log_chequier_status_change')) {
function audit_log_chequier_status_change($conn, $requestId, $oldStatus, $newStatus) {
    AuditLogger::log('CHEQUIER_STATUS_CHANGE', [
        'request_id' => $requestId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ], $conn);
}
}

function audit_log_export($conn, $type, $filters = [], $recordCount = 0) {
    AuditLogger::log('EXPORT_' . strtoupper($type), [
        'filters' => $filters,
        'record_count' => $recordCount
    ], $conn);
}

function audit_log_login($conn, $userId, $success = true) {
    AuditLogger::log($success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED', [
        'user_id' => $userId
    ], $conn);
}

function audit_log_logout($conn, $userId) {
    AuditLogger::log('LOGOUT', [
        'user_id' => $userId
    ], $conn);
}

function audit_log_user_action($conn, $action, $userId, $details = []) {
    AuditLogger::log('USER_' . strtoupper($action), array_merge([
        'user_id' => $userId
    ], $details), $conn);
}

function audit_log_form_submission($conn, $formType, $submissionId, $details = []) {
    AuditLogger::log('FORM_SUBMIT_' . strtoupper($formType), array_merge([
        'submission_id' => $submissionId
    ], $details), $conn);
}

function audit_log_admin_action($conn, $action, $details = []) {
    AuditLogger::log('ADMIN_' . strtoupper($action), $details, $conn);
}

function audit_log_error($conn, $errorType, $message, $context = []) {
    AuditLogger::log('ERROR_' . strtoupper($errorType), [
        'message' => $message,
        'context' => $context
    ], $conn);
}

function audit_log_security_event($conn, $eventType, $details = []) {
    AuditLogger::log('SECURITY_' . strtoupper($eventType), $details, $conn);
}
?>