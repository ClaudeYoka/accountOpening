<?php
/**
 * Centralized Logging System
 * Remplace tous les error_log() et file_put_contents() éparpillés
 */

class Logger {
    private static $instance = null;
    private $logDir;
    private $maxFiles = 10;
    private $maxFileSize = 10485760; // 10MB
    
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    private function __construct() {
        $this->logDir = __DIR__ . '/../logs';
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Main logging method
     */
    public function log($level, $message, $context = [], $category = 'app') {
        $logFile = $this->logDir . "/{$category}.log";
        
        // Format structured log entry
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'level' => $level,
            'message' => $message,
            'user' => $_SESSION['alogin'] ?? $_SESSION['emp_id'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        // Write to file
        $this->writeLog($logFile, $logLine);
        
        // Also trigger PHP error_log for system errors
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            error_log("[{$level}] {$message} | User: " . $logEntry['user'] . " | IP: " . $logEntry['ip']);
        }
    }
    
    /**
     * Convenience methods
     */
    public function debug($message, $context = [], $category = 'app') {
        $this->log(self::LEVEL_DEBUG, $message, $context, $category);
    }
    
    public function info($message, $context = [], $category = 'app') {
        $this->log(self::LEVEL_INFO, $message, $context, $category);
    }
    
    public function warning($message, $context = [], $category = 'app') {
        $this->log(self::LEVEL_WARNING, $message, $context, $category);
    }
    
    public function error($message, $context = [], $category = 'app') {
        $this->log(self::LEVEL_ERROR, $message, $context, $category);
    }
    
    public function critical($message, $context = [], $category = 'app') {
        $this->log(self::LEVEL_CRITICAL, $message, $context, $category);
    }
    
    /**
     * Special method for form saves (replaces save_ecobank_form_debug.log)
     */
    public function logFormSubmission($form_type, $data, $status = 'submitted', $errors = []) {
        $this->log(
            self::LEVEL_INFO,
            "Form submission: $form_type",
            [
                'form_type' => $form_type,
                'status' => $status,
                'data_keys' => array_keys((array)$data),
                'errors' => $errors,
                'data_size' => strlen(json_encode($data))
            ],
            'forms'
        );
    }
    
    /**
     * SQL query logging
     */
    public function logSql($query, $params = [], $error = null) {
        $this->log(
            $error ? self::LEVEL_ERROR : self::LEVEL_DEBUG,
            $error ? "SQL Error: $error" : "SQL Query",
            [
                'query' => substr($query, 0, 500),
                'params_count' => count($params),
                'error' => $error
            ],
            'sql'
        );
    }
    
    /**
     * API call logging
     */
    public function logApiCall($endpoint, $method, $status_code, $response_time_ms, $error = null) {
        $this->log(
            ($status_code >= 200 && $status_code < 300) ? self::LEVEL_INFO : self::LEVEL_WARNING,
            "API Call: $method $endpoint",
            [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $status_code,
                'response_time_ms' => $response_time_ms,
                'error' => $error
            ],
            'api'
        );
    }
    
    /**
     * Authentication logging
     */
    public function logAuth($event, $username, $success, $reason = null) {
        $this->log(
            $success ? self::LEVEL_INFO : self::LEVEL_WARNING,
            "Auth Event: $event",
            [
                'event' => $event,
                'username' => $username,
                'success' => $success,
                'reason' => $reason
            ],
            'auth'
        );
    }
    
    /**
     * Write to logfile with rotation
     */
    private function writeLog($logFile, $logLine) {
        // Check if file needs rotation
        if (file_exists($logFile) && filesize($logFile) > $this->maxFileSize) {
            $this->rotateLog($logFile);
        }
        
        // Write line
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        @chmod($logFile, 0664);
    }
    
    /**
     * Rotate log files
     */
    private function rotateLog($logFile) {
        $basename = basename($logFile, '.log');
        
        // Remove oldest file if we exceed max files
        for ($i = $this->maxFiles; $i > 1; $i--) {
            $oldFile = dirname($logFile) . "/{$basename}.{$i}.log";
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $src = dirname($logFile) . "/{$basename}.{$i}.log";
            $dst = dirname($logFile) . "/{$basename}." . ($i + 1) . ".log";
            if (file_exists($src)) {
                rename($src, $dst);
            }
        }
        
        // Rename current to .1
        $newFile = dirname($logFile) . "/{$basename}.1.log";
        rename($logFile, $newFile);
    }
    
    /**
     * Get recent logs
     */
    public function getLogs($category = 'app', $limit = 100) {
        $logFile = $this->logDir . "/{$category}.log";
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        foreach (array_slice($lines, -$limit) as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $logs[] = $decoded;
            }
        }
        
        return $logs;
    }
    
    /**
     * Clear old logs (maintenance)
     */
    public function clearOldLogs($category = null, $daysOld = 30) {
        $cutoffTime = time() - ($daysOld * 86400);
        
        $pattern = $category ? "{$this->logDir}/{$category}*.log" : "{$this->logDir}/*.log";
        foreach (glob($pattern) as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}

// Global convenience function
function log_message($level, $message, $context = [], $category = 'app') {
    Logger::getInstance()->log($level, $message, $context, $category);
}

?>
