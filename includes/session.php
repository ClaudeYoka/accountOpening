<?php
// Headers de sécurité
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");

// Configuration de session sécurisée
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 7200); // 2 heures

require_once __DIR__ . '/security_config.php';
include_once __DIR__ . '/config.php';

/** @var mysqli $conn */
if (empty($conn) || !($conn instanceof mysqli)) {
    error_log('Session error: database connection not available.');
    echo "<div style='padding:20px;color:#900'>Connexion à la base de données introuvable.</div>";
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('ACCOUNT_OPENING_SESSION');
    session_start();
}

// Timeout de session pour inactivité
$timeout = 7200; // 2 heures
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Générer token CSRF si pas présent
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fonctions helper pour CSRF (compatibilité)
if (!function_exists('get_csrf_token')) {
    function get_csrf_token() {
        return CSRFProtection::generateToken();
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return CSRFProtection::validateToken($token);
    }
}

//Check whether the session variable SESS_MEMBER_ID is present or not
$session_id = '';
$session_role = '';
$session_depart = '';

if (isset($_SESSION['alogin']) && trim((string) $_SESSION['alogin']) !== '') {
    $session_id = trim((string) $_SESSION['alogin']);
} elseif (isset($_SESSION['emp_id']) && trim((string) $_SESSION['emp_id']) !== '') {
    $session_id = trim((string) $_SESSION['emp_id']);
}

if ($session_id === '') {
    header('Location: ../index.php');
    exit;
}

$session_role = $_SESSION['arole'] ?? '';
$session_depart = $_SESSION['adepart'] ?? '';

// Stocker emp_id dans la session
if (!isset($_SESSION['emp_id'])) {
    $_SESSION['emp_id'] = $session_id;
}

// Récupérer le nom complet de l'utilisateur depuis la base de données
// config.php est déjà inclus avant session.php dans tous les fichiers
if (isset($conn) && $conn instanceof mysqli) {
    $query = "SELECT FirstName, LastName FROM tblemployees WHERE emp_id = '$session_id'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $user_info = mysqli_fetch_assoc($result);
        $_SESSION['user_fullname'] = $user_info['FirstName'] . ' ' . $user_info['LastName'];
    }
}
?>