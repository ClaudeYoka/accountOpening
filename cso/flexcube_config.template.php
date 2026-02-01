<?php
/**
 * Configuration Template pour Flexcube API
 * 
 * À ajouter dans votre fichier config.php ou .env
 * 
 * IMPORTANT: Cette configuration est optionnelle.
 * Les valeurs par défaut fonctionnent pour l'environnement de test Ecobank.
 */

// ============================================
// Configuration Flexcube
// ============================================

/**
 * URL de l'API Flexcube
 * 
 * DEV/TEST: https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo
 * PROD: À adapter selon l'environnement de production Ecobank
 */
define('FLEXCUBE_API_URL', 'https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo');

/**
 * Code Source pour la requête
 * (fourni par Ecobank)
 */
define('FLEXCUBE_SOURCE_CODE', 'ECOBANKWEB');

/**
 * Code Affilié pour la requête
 * (fourni par Ecobank)
 */
define('FLEXCUBE_AFFILIATE_CODE', 'ECG');

/**
 * Canal Source pour la requête
 */
define('FLEXCUBE_SOURCE_CHANNEL_ID', 'WEB');

/**
 * Vérification SSL
 * 
 * false = Développement (pour ignorer les certificats auto-signés)
 * true = Production (recommandé)
 */
define('FLEXCUBE_VERIFY_SSL', false); // À mettre à true en production

/**
 * Timeout en secondes pour la requête HTTP
 */
define('FLEXCUBE_TIMEOUT', 30);

/**
 * Durée du cache en secondes
 * (0 = pas de cache)
 */
define('FLEXCUBE_CACHE_DURATION', 3600); // 1 heure

/**
 * Activer le log des appels API
 */
define('FLEXCUBE_LOG_ENABLED', true);

/**
 * Chemin du fichier log
 */
define('FLEXCUBE_LOG_FILE', __DIR__ . '/../logs/flexcube_api.log');

/**
 * Mode fallback
 * 
 * true = Utiliser la BD locale si Flexcube échoue
 * false = Retourner l'erreur sans fallback
 */
define('FLEXCUBE_FALLBACK_TO_LOCAL_DB', true);

// ============================================
// Configuration Optionnelle: Authentification
// ============================================

/**
 * Si Flexcube nécessite une authentification basique
 * (rare pour cette API, mais possible)
 */
// define('FLEXCUBE_AUTH_USERNAME', 'your_username');
// define('FLEXCUBE_AUTH_PASSWORD', 'your_password');

// ============================================
// Configuration Optionnelle: Proxy
// ============================================

/**
 * Si vous êtes derrière un proxy
 */
// define('FLEXCUBE_PROXY_URL', 'http://proxy.example.com:8080');
// define('FLEXCUBE_PROXY_AUTH', 'username:password');

// ============================================
// Initialisation Automatique (Optionnel)
// ============================================

/**
 * Configuration du service Flexcube au démarrage
 */
if (!function_exists('init_flexcube')) {
    function init_flexcube() {
        // Inclure le service
        $flexcube_path = __DIR__ . '/includes/flexcube_helpers.php';
        if (file_exists($flexcube_path)) {
            include_once($flexcube_path);
            
            // Configurer l'instance
            $api = getFlexcubeAPI();
            
            // Appliquer les paramètres si définis
            if (defined('FLEXCUBE_API_URL')) {
                $api->setApiUrl(FLEXCUBE_API_URL);
            }
            
            if (defined('FLEXCUBE_VERIFY_SSL')) {
                $api->setSSLVerification(FLEXCUBE_VERIFY_SSL);
            }
            
            if (defined('FLEXCUBE_SOURCE_CODE') && defined('FLEXCUBE_AFFILIATE_CODE')) {
                $api->setAuthConfig(FLEXCUBE_SOURCE_CODE, FLEXCUBE_AFFILIATE_CODE);
            }
            
            return true;
        }
        return false;
    }
}

// ============================================
// Fonction de Test
// ============================================

/**
 * Tester la configuration Flexcube
 */

// if (!function_exists('test_flexcube_config')) {
//     function test_flexcube_config() {
//         echo "=== Test Configuration Flexcube ===\n";
        
//         echo "URL API: " . (defined('FLEXCUBE_API_URL') ? FLEXCUBE_API_URL : 'N/A') . "\n";
//         echo "Source Code: " . (defined('FLEXCUBE_SOURCE_CODE') ? FLEXCUBE_SOURCE_CODE : 'N/A') . "\n";
//         echo "Affiliate Code: " . (defined('FLEXCUBE_AFFILIATE_CODE') ? FLEXCUBE_AFFILIATE_CODE : 'N/A') . "\n";
//         echo "SSL Verify: " . (defined('FLEXCUBE_VERIFY_SSL') ? (FLEXCUBE_VERIFY_SSL ? 'OUI' : 'NON') : 'N/A') . "\n";
//         echo "Timeout: " . (defined('FLEXCUBE_TIMEOUT') ? FLEXCUBE_TIMEOUT . 's' : 'N/A') . "\n";
//         echo "Cache Duration: " . (defined('FLEXCUBE_CACHE_DURATION') ? FLEXCUBE_CACHE_DURATION . 's' : 'N/A') . "\n";
//         echo "\n";
        
//         // Essayer une connexion
//         if (function_exists('testFlexcubeConnection')) {
//             $test = testFlexcubeConnection();
//             echo "Status: " . $test['status'] . "\n";
//             if ($test['status'] === 'OK') {
//                 echo "✓ Connexion établie avec succès\n";
//             } else {
//                 echo "✗ Erreur de connexion\n";
//                 echo "Détails: " . $test['message'] . "\n";
//             }
//         }
//     }
// }

?>
