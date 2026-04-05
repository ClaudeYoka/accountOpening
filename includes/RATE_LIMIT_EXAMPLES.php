<?php
/**
 * Integration File - Rate Limiting
 * Intégration du RateLimiter dans AuthenticationJQuery
 *
 * À inclure dans: loginController.php, authentication flows
 * Usage: Voir les exemples ci-dessous
 */

// Enable in your files:
// 1. Inclure après config.php:
//    include('RateLimiter.php');
//    
// 2. Ajouter au POST du formulaire login:
//    middleware_rate_limit($dbh, 'login', 5, 300);

// ========================================
// EXEMPLE 1: PROTECTION LOGIN (5 attempts/5min)
// ========================================

/*
// Dans loginController.php - AJOUTER CES LIGNES:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    
    // NOUVEAU: Rate limit check
    include('RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'login', 5, 300);
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // ... reste du code existant ...
    
    if ($passwordValid) {
        // LOGIN SUCCESS - NOUVEAU: Clear rate limit logs
        $limiter = new RateLimiter($dbh);
        $limiter->clearLogs('login', $limiter->getClientIdentifier());
        
        // ... reste du code login ...
    } else {
        // LOGIN FAILED - Logs automatiquement enregistré
        audit_log_login($conn, $username, false);
        $login_result['error'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
}
*/

// ========================================
// EXEMPLE 2: PROTECTION FORM SUBMIT (5 attempts/60s)
// ========================================

/*
// Dans cso/save_ecobank_form.php:

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // NOUVEAU: Rate limit check
    include('../includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'form_submit_ecobank', 5, 60);
    
    // Validation et traitement du formulaire...
    // Puis au succès:
    
    if ($save_success) {
        // Nettoyer les logs après succès
        $limiter = new RateLimiter($dbh);
        $limiter->clearLogs('form_submit_ecobank', $limiter->getClientIdentifier());
        
        echo json_encode(['success' => true, ...]);
    }
}
*/

// ========================================
// EXEMPLE 3: PROTECTION PASSWORD RESET (3 attempts/10min)
// ========================================

/*
// Dans forgot-password.html / change_password.php:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    
    include('includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'password_reset', 3, 600);
    
    // Traitement réinitialisation...
}
*/

// ========================================
// EXEMPLE 4: PROTECTION API FLEXCUBE (100 attempts/hour)
// ========================================

/*
// Dans cso/fetch_account_flexcube.php:

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['account_number'])) {
    
    include('../includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'api_flexcube', 100, 3600);
    
    // Appel API...
}
*/

// ========================================
// EXEMPLE 5: PROTECTION FILE UPLOAD (10 attempts/10min)
// ========================================

/*
// Dans includes/FileUploadHandler.php:

public function handleUpload($file, $action = 'file_upload') {
    include('RateLimiter.php');
    $rate_check = middleware_rate_limit($GLOBALS['dbh'], $action, 10, 600);
    
    // Traitement upload...
}
*/

// ========================================
// EXEMPLE 6: PROTECTION MESSAGES (10 attempts/1min)
// ========================================

/*
// Dans chatlog.php:

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    include('includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'message_send', 10, 60);
    
    // Enregistrer message...
}
*/

// ========================================
// EXEMPLE 7: PROTECTION NOTIFICATIONS (20 attempts/hour)
// ========================================

/*
// Dans admin/get_notifications.php:

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    include('../includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'get_notifications', 20, 3600);
    
    // Récupérer notifications...
}
*/

// ========================================
// EXEMPLE 8: PROTECTION EXPORTS (5 attempts/hour)
// ========================================

/*
// Dans cso/export_chequier_excel.php:

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    include('../includes/RateLimiter.php');
    $rate_check = middleware_rate_limit($dbh, 'export_data', 5, 3600);
    
    // Export Excel...
}
*/

?>
