<?php
/**
 * RATE LIMITING CONFIGURATION
 * 
 * Fichier de configuration centralisé pour le système de rate limiting
 * 
 * USAGE:
 *   include('RATE_LIMITING_CONFIG.php');
 *   $rules = RATE_LIMIT_RULES['login'];
 * 
 * @version 1.0
 * @date 2026-04-05
 */

// ============================================================================
// CONFIGURATION GLOBALE
// ============================================================================

define('RATE_LIMIT_ENABLED', true);           // Activer/désactiver globalement
define('RATE_LIMIT_DEBUG', false);            // Mode debug (logs verbeux)
define('RATE_LIMIT_FAIL_OPEN', true);         // Permettre si DB error
define('RATE_LIMIT_LOG_VIOLATIONS', true);    // Enregistrer les violations
define('RATE_LIMIT_AUTO_CLEANUP', true);      // Auto-cleanup >24h

// ============================================================================
// RÈGLES PAR ENDPOINT
// ============================================================================

const RATE_LIMIT_RULES = [
    
    // ========== AUTHENTIFICATION ==========
    'login' => [
        'name'   => 'Login Attempts',
        'limit'  => 5,              // 5 tentatives
        'window' => 300,            // dans 5 minutes (300s)
        'action' => 'block',        // Bloquer ou 'log'
        'message' => 'Trop de tentatives. Réessayez dans 5 minutes.',
    ],
    
    'forgot_password' => [
        'name'   => 'Forgot Password Requests',
        'limit'  => 3,              // 3 tentatives
        'window' => 600,            // dans 10 minutes
        'action' => 'block',
        'message' => 'Trop de demandes. Réessayez dans 10 minutes.',
    ],
    
    'password_reset' => [
        'name'   => 'Password Reset',
        'limit'  => 3,              // 3 résets
        'window' => 600,            // dans 10 minutes
        'action' => 'block',
        'message' => 'Limite de réinitialisation atteinte.',
    ],
    
    // ========== FORMULAIRES ==========
    'form_submit_ecobank' => [
        'name'   => 'Ecobank Form Submissions',
        'limit'  => 5,              // 5 soumissions
        'window' => 60,             // par minute
        'action' => 'block',
        'message' => 'Trop de soumissions. Attendez avant de réessayer.',
    ],
    
    'demande_chequier' => [
        'name'   => 'Chequier Request',
        'limit'  => 3,              // 3 demandes
        'window' => 300,            // dans 5 minutes
        'action' => 'block',
        'message' => 'Trop de demandes de chéquier. Réessayez plus tard.',
    ],
    
    'file_upload' => [
        'name'   => 'File Upload',
        'limit'  => 10,             // 10 uploads
        'window' => 600,            // dans 10 minutes
        'action' => 'block',
        'message' => 'Limite d\'upload atteinte.',
    ],
    
    // ========== API & INTÉGRATIONS ==========
    'flexcube_api' => [
        'name'   => 'Flexcube API Calls',
        'limit'  => 100,            // 100 appels
        'window' => 3600,           // par heure
        'action' => 'log',          // Just log
        'message' => 'Limite API atteinte.',
    ],
    
    'export_data' => [
        'name'   => 'Data Export',
        'limit'  => 5,              // 5 exports
        'window' => 3600,           // par heure
        'action' => 'block',
        'message' => 'Trop d\'exports. Réessayez plus tard.',
    ],
    
    // ========== COMMUNICATION ==========
    'send_message' => [
        'name'   => 'Send Message',
        'limit'  => 10,             // 10 messages
        'window' => 60,             // par minute
        'action' => 'log',
        'message' => 'Trop de messages trop vite.',
    ],
    
    'get_notifications' => [
        'name'   => 'Get Notifications',
        'limit'  => 20,             // 20 requêtes
        'window' => 3600,           // par heure
        'action' => 'log',
        'message' => 'Trop de notifications.',
    ],
    
];

// ============================================================================
// WHITELIST - IPs ET AGENTS DE CONFIANCE
// ============================================================================

const RATE_LIMIT_WHITELIST = [
    // Systèmes locaux
    '127.0.0.1'              => 'localhost',
    'localhost'              => 'localhost',
    '::1'                    => 'ipv6-localhost',
    
    // Environnement de développement (REMPLACER EN PROD!)
    '192.168.1.0/24'         => 'development-network',
    '192.168.0.0/24'         => 'home-network',
    
    // Serveurs de monitoring (REMPLACER!)
    '10.0.0.5'               => 'prometheus-server',
    '10.0.0.6'               => 'grafana-server',
    
    // IPs clients de confiance (À REMPLIR)
    // '203.0.113.50'        => 'trusted-partner-1',
    // '203.0.113.51'        => 'trusted-partner-2',
];

// ============================================================================
// CONFIGURATIONS AVANCÉES
// ============================================================================

const RATE_LIMIT_ADVANCED = [
    
    // Cleanup automatique (cron job)
    'auto_cleanup_enabled'   => true,
    'auto_cleanup_days'      => 7,          // Garder 7 jours de logs
    'auto_cleanup_interval'  => 3600,       // Exécuter chaque heure
    
    // Détection de violations
    'violation_detection'    => true,
    'violation_threshold'    => 50,         // Violation si >50 tries en 1 heure
    'violation_alert_email'  => 'security@ecobank.com',
    
    // Hashing pour IDs
    'hash_algorithm'         => 'sha256',
    'hash_salt'              => 'ecobank_rate_limit_2026',  // CHANGER EN PROD!
    
    // Timeouts
    'lock_timeout'           => 3600,       // Bloquer 1 heure max
    'cleanup_batch_size'     => 1000,       // Nettoyer par lots de 1000
    
    // Logging
    'log_all_attempts'       => false,      // À true pour debug
    'log_successful'         => false,      // À true pour audit
    'log_file'               => '/var/log/rate_limiting.log',
    
];

// ============================================================================
// MIDDLEWARE HELPER FUNCTIONS
// ============================================================================

/**
 * Middleware dépôt de rate limiting
 * Utiliser dans chaque endpoint à protéger
 * 
 * @param PDO $dbh Database handle
 * @param string $endpoint Endpoint name (ex: 'login', 'form_submit_ecobank')
 * @param int|null $limit Override limit (optional)
 * @param int|null $window Override window (optional)
 * @return array ['blocked' => bool, 'message' => string, 'remaining' => int]
 */
function middleware_rate_limit($dbh, $endpoint, $limit = null, $window = null) {
    if (!RATE_LIMIT_ENABLED) {
        return ['blocked' => false, 'message' => '', 'remaining' => -1];
    }
    
    // Load config for this endpoint
    if (!isset(RATE_LIMIT_RULES[$endpoint])) {
        error_log("Rate limit: Unknown endpoint '$endpoint'");
        return ['blocked' => false, 'message' => 'Unknown endpoint', 'remaining' => -1];
    }
    
    $rule = RATE_LIMIT_RULES[$endpoint];
    $limit = $limit ?? $rule['limit'];
    $window = $window ?? $rule['window'];
    
    try {
        // Include RateLimiter class
        if (!class_exists('RateLimiter')) {
            include(__DIR__ . '/RateLimiter.php');
        }
        
        $limiter = new RateLimiter($dbh);
        $result = $limiter->checkRateLimit($endpoint, $limit, $window);
        
        if ($result['blocked']) {
            if (RATE_LIMIT_DEBUG) {
                error_log("RATE LIMIT BLOCKED: $endpoint - " . $result['remaining']);
            }
            
            if ($rule['action'] === 'block') {
                return [
                    'blocked' => true,
                    'message' => $rule['message'],
                    'remaining' => $result['remaining'],
                    'reset_in' => $result['reset_in'] ?? null,
                ];
            } else {
                // Log only
                error_log("RATE LIMIT LOG: $endpoint - {$result['remaining']} attempts");
                return ['blocked' => false, 'message' => '', 'remaining' => -1];
            }
        }
        
        return ['blocked' => false, 'message' => '', 'remaining' => $result['remaining']];
        
    } catch (Exception $e) {
        if (RATE_LIMIT_FAIL_OPEN) {
            error_log("Rate limit exception (fail-open): " . $e->getMessage());
            return ['blocked' => false, 'message' => '', 'remaining' => -1];
        } else {
            throw $e;
        }
    }
}

/**
 * Nettoyer les logs après succès
 */
function rate_limit_clear_on_success($dbh, $endpoint) {
    try {
        if (!class_exists('RateLimiter')) {
            include(__DIR__ . '/RateLimiter.php');
        }
        
        $limiter = new RateLimiter($dbh);
        $limiter->clearLogs($endpoint, $limiter->getClientIdentifier());
    } catch (Exception $e) {
        error_log("Rate limit clear error: " . $e->getMessage());
    }
}

/**
 * Récupérer stats pour dashboard
 */
function rate_limit_get_stats($dbh) {
    try {
        if (!class_exists('RateLimiter')) {
            include(__DIR__ . '/RateLimiter.php');
        }
        
        $limiter = new RateLimiter($dbh);
        return $limiter->getStats();
    } catch (Exception $e) {
        error_log("Rate limit stats error: " . $e->getMessage());
        return null;
    }
}

/**
 * Ajouter IP à whitelist
 */
function rate_limit_whitelist_add($dbh, $ip, $reason = '') {
    try {
        if (!class_exists('RateLimiter')) {
            include(__DIR__ . '/RateLimiter.php');
        }
        
        $limiter = new RateLimiter($dbh);
        return $limiter->addWhitelist($ip, $reason);
    } catch (Exception $e) {
        error_log("Rate limit whitelist error: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifier si IP est en whitelist
 */
function rate_limit_is_whitelisted($dbh, $ip) {
    try {
        if (!class_exists('RateLimiter')) {
            include(__DIR__ . '/RateLimiter.php');
        }
        
        $limiter = new RateLimiter($dbh);
        return $limiter->isWhitelisted($ip);
    } catch (Exception $e) {
        error_log("Rate limit whitelist check error: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// HELPER - OBTENIR IP CLIENT
// ============================================================================

function get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

// ============================================================================
// CONFIGURATION PRODUCTION
// ============================================================================

/**
 * CHANGEMENTS RECOMMANDÉS AVANT PRODUCTION:
 * 
 * 1. Mettre à jour RATE_LIMIT_WHITELIST avec:
 *    - IPs réelles des serveurs de monitoring
 *    - IPs des clients de confiance
 *    - Réseaux VPN d'administration
 *    - Supprimer les adresses de développement
 * 
 * 2. Changer RATE_LIMIT_ADVANCED['hash_salt'] avec clé unique:
 *    - Générer avec: bin2hex(random_bytes(32))
 *    - Stocker en sécurité
 * 
 * 3. Configurer RATE_LIMIT_ADVANCED:
 *    - violation_alert_email avec adresse réelle
 *    - log_file avec chemin accessible
 * 
 * 4. Configurer cron pour cleanup automatique:
 *    - 0 */1 * * * php /path/cleanup_rate_limit.php
 * 
 * 5. Mettre à jour les règles selon besoins réels:
 *    - Ajuster limits selon usage
 *    - Ajouter endpoints additionnels au besoin
 * 
 * 6. Tester chaque endpoint avant go-live
 * 
 */

?>
