<?php
/**
 * CSRF Protection Helper
 * Utiliser csrf_token() dans les formulaires
 */

class CSRFProtection {
    const TOKEN_LENGTH = 32;
    const TOKEN_LIFETIME = 3600; // 1 hour
    
    public static function generateToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Check if token exists and is still valid
        if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
            if (time() - $_SESSION['csrf_token_time'] < self::TOKEN_LIFETIME) {
                return $_SESSION['csrf_token'];
            }
        }
        
        // Generate new token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        Logger::getInstance()->debug('CSRF token generated', ['token_prefix' => substr($token, 0, 8)]);
        
        return $token;
    }
    
    public static function validateToken($token_from_request) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            Logger::getInstance()->warning('CSRF validation failed: no token in session', 
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }
        
        if (empty($token_from_request)) {
            Logger::getInstance()->warning('CSRF validation failed: empty token from request', 
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        if (!hash_equals($_SESSION['csrf_token'], $token_from_request)) {
            Logger::getInstance()->warning('CSRF validation failed: token mismatch', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user' => $_SESSION['alogin'] ?? 'anonymous'
            ]);
            return false;
        }
        
        // Token is valid
        Logger::getInstance()->debug('CSRF token validated successfully', 
            ['user' => $_SESSION['alogin'] ?? 'anonymous']);
        return true;
    }
    
    public static function getToken() {
        return self::generateToken();
    }
}

// Global helpers
function csrf_token() {
    return CSRFProtection::generateToken();
}

function get_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf_token($token) {
    if (!CSRFProtection::validateToken($token)) {
        http_response_code(419);
        die(json_encode(['error' => 'CSRF token validation failed'], JSON_UNESCAPED_UNICODE));
    }
}

// Modified CSRF verification that can be used at start of POST handlers
function check_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if (!$token) {
        Logger::getInstance()->warning('CSRF check failed: no token provided', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => 'Token CSRF manquant ou invalide'], JSON_UNESCAPED_UNICODE));
    }
    
    if (!CSRFProtection::validateToken($token)) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => 'Token CSRF invalide'], JSON_UNESCAPED_UNICODE));
    }
}

?>
