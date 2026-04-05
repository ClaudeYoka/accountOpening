<?php
/**
 * Rate Limiter Class
 * Protège contre les abus: brute force login, spam formulaires, DDoS
 * 
 * Usage:
 *   $limiter = new RateLimiter($pdo);
 *   $limiter->checkRateLimit('login', 5, 300); // 5 attempts per 5 min
 */

class RateLimiter {
    private $pdo;
    private $table = 'rate_limit_logs';
    private $cleanup_interval = 3600; // Cleanup old records every 1 hour
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->autoCleanup();
    }
    
    /**
     * Vérify if action is rate limited
     * 
     * @param string $action - Action identifier (login, form_submit, api_call, etc)
     * @param int $max_attempts - Max attempts allowed
     * @param int $time_window - Time window in seconds
     * @param string $identifier - Optional custom identifier (default: IP address)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    public function checkRateLimit($action, $max_attempts = 5, $time_window = 300, $identifier = null) {
        if ($identifier === null) {
            $identifier = $this->getClientIdentifier();
        }
        
        try {
            // Get current attempt count
            $count = $this->getAttemptCount($action, $identifier, $time_window);
            
            // Log the attempt
            $this->logAttempt($action, $identifier);
            
            // Calculate response
            $allowed = $count < $max_attempts;
            $remaining = max(0, $max_attempts - $count - 1);
            $retry_after = $allowed ? 0 : $this->getRetryAfter($action, $identifier, $time_window);
            
            return [
                'allowed' => $allowed,
                'remaining' => $remaining,
                'retry_after' => $retry_after,
                'count' => $count + 1,
                'max_attempts' => $max_attempts
            ];
            
        } catch (Exception $e) {
            // On erreur, allow (fail open) mais log l'erreur
            error_log("RateLimiter error: " . $e->getMessage());
            return [
                'allowed' => true,
                'remaining' => 0,
                'retry_after' => 0,
                'error' => true
            ];
        }
    }
    
    /**
     * Déterminer l'identifiant client (IP + User Agent)
     */
    public function getClientIdentifier() {
        $ip = $this->getClientIP();
        $user_agent = substr(md5($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 16);
        return hash('sha256', $ip . $user_agent);
    }
    
    /**
     * Obtenir véritable IP client (derrière proxy)
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Generic proxy
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            return $ips[0];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get nombre de tentatives récentes
     */
    private function getAttemptCount($action, $identifier, $time_window) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE action = :action 
                AND identifier = :identifier 
                AND timestamp > DATE_SUB(NOW(), INTERVAL :window SECOND)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':identifier' => $identifier,
            ':window' => $time_window
        ]);
        
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    /**
     * Enregistrer une tentative
     */
    private function logAttempt($action, $identifier) {
        $sql = "INSERT INTO {$this->table} (action, identifier, ip_address, user_agent, timestamp) 
                VALUES (:action, :identifier, :ip, :ua, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':action' => $action,
            ':identifier' => $identifier,
            ':ip' => $this->getClientIP(),
            ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    }
    
    /**
     * Calculer le délai avant retry
     */
    private function getRetryAfter($action, $identifier, $time_window) {
        $sql = "SELECT UNIX_TIMESTAMP(MAX(timestamp)) as last_attempt 
                FROM {$this->table} 
                WHERE action = :action AND identifier = :identifier";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':identifier' => $identifier
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['last_attempt']) {
            $retry_time = $result['last_attempt'] + $time_window;
            $now = time();
            return max(0, $retry_time - $now);
        }
        
        return $time_window;
    }
    
    /**
     * Nettoyer les anciens logs (> 24h)
     */
    private function autoCleanup() {
        // Cleanup tous les jours max
        $useApcu = function_exists('apcu_fetch') && function_exists('apcu_store');
        $last_cleanup = $useApcu ? apcu_fetch('rate_limit_last_cleanup') : null;
        if ($last_cleanup && (time() - $last_cleanup) < $this->cleanup_interval) {
            return;
        }
        
        try {
            $sql = "DELETE FROM {$this->table} WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $this->pdo->exec($sql);
            if ($useApcu) {
                apcu_store('rate_limit_last_cleanup', time(), $this->cleanup_interval);
            }
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Récupérer les statistiques pour une action
     */
    public function getStats($action, $limit = 100) {
        $sql = "SELECT identifier, ip_address, COUNT(*) as attempts, MAX(timestamp) as last_attempt 
                FROM {$this->table} 
                WHERE action = :action 
                AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY identifier, ip_address
                ORDER BY attempts DESC
                LIMIT " . (int)$limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':action' => $action]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clear les logs pour un identifiant (après success par ex)
     */
    public function clearLogs($action, $identifier) {
        $sql = "DELETE FROM {$this->table} WHERE action = :action AND identifier = :identifier";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':action' => $action,
            ':identifier' => $identifier
        ]);
    }
    
    /**
     * Obtenir les identifiants bloqués actuellement
     */
    public function getBlockedIdentifiers($action, $max_attempts = 5, $time_window = 300) {
        $sql = "SELECT identifier, COUNT(*) as attempts, MAX(timestamp) as last_attempt,
                UNIX_TIMESTAMP(MAX(timestamp)) + :window - UNIX_TIMESTAMP(NOW()) as seconds_remaining
                FROM {$this->table} 
                WHERE action = :action 
                AND timestamp > DATE_SUB(NOW(), INTERVAL :window SECOND)
                GROUP BY identifier
                HAVING attempts >= :max_attempts
                ORDER BY last_attempt DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':window' => $time_window,
            ':max_attempts' => $max_attempts
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Débloquer manuellement un identifiant
     */
    public function unblock($action, $identifier) {
        return $this->clearLogs($action, $identifier);
    }
    
    /**
     * Mettre en whitelist un identifiant (admin, trusted services)
     */
    public function addWhitelist($identifier, $reason = '') {
        $sql = "INSERT INTO rate_limit_whitelist (identifier, reason, created_at) 
                VALUES (:identifier, :reason, NOW())
                ON DUPLICATE KEY UPDATE created_at = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':identifier' => $identifier,
            ':reason' => $reason
        ]);
    }
    
    /**
     * Vérifier si identifiant est whitelist
     */
    public function isWhitelisted($identifier) {
        $sql = "SELECT 1 FROM rate_limit_whitelist WHERE identifier = :identifier";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':identifier' => $identifier]);
        return (bool)$stmt->fetch();
    }
}

/**
 * Middleware Helper - À utiliser dans les formulaires/API
 */
function middleware_rate_limit($pdo, $action, $max_attempts = 5, $time_window = 300) {
    $limiter = new RateLimiter($pdo);
    
    // Vérifier whitelist
    $identifier = $limiter->getClientIdentifier();
    if ($identifier && $limiter->isWhitelisted($identifier)) {
        return ['allowed' => true, 'blocked' => false, 'remaining' => $max_attempts];
    }
    
    $result = $limiter->checkRateLimit($action, $max_attempts, $time_window);
    
    if (!$result['allowed']) {
        return [
            'allowed' => false,
            'blocked' => true,
            'retry_after' => $result['retry_after'],
            'remaining' => $result['remaining'],
            'message' => 'Trop de tentatives. Veuillez réessayer dans ' . $result['retry_after'] . ' secondes.'
        ];
    }
    
    return array_merge($result, ['blocked' => false]);
}

?>
