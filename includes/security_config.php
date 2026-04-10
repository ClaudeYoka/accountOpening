<?php
/**
 * Security Configuration - À charger dans index.php AVANT tout output
 */

// === LOAD LOGGER FIRST ===
require_once __DIR__ . '/Logger.php';

// === CSRF PROTECTION ===
require_once __DIR__ . '/CSRF.php';

if (!function_exists('get_env')) {
    function get_env($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value ?: $default;
    }
}

// === LOAD ENV ===
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = explode('=', $line, 2) + [null, null];
        if ($key) putenv("$key=$value");
    }
}

// === SECURITY HEADERS ===
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// === PHP SETUP ===
error_reporting(E_ALL);
ini_set('display_errors', get_env('APP_DEBUG') === 'true' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// === SESSION SETUP ===
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', get_env('SESSION_SECURE_COOKIE') === 'true' ? '1' : '0');
ini_set('session.cookie_samesite', get_env('SESSION_SAMESITE', 'Strict'));
ini_set('session.gc_maxlifetime', get_env('SESSION_TIMEOUT', 1800));
ini_set('session.sid_length', '48');
ini_set('session.sid_bits_per_character', '6');

// === CSRF TOKEN GENERATION ===
function csrf_generate_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// === CSRF TOKEN VERIFICATION ===
function csrf_verify_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

// === LOGGING SETUP (via Logger class) ===
function log_action($level, $message, $context = [], $category = 'app') {
    Logger::getInstance()->log($level, $message, $context, $category);
}

?>

