<?php
/**
 * Helper Functions pour Flexcube API
 * Fonctions utilitaires pour simplifier l'intégration
 */

require_once(__DIR__ . '/FlexcubeAPI.php');

/**
 * Instance singleton de FlexcubeAPI
 */
$_flexcube_instance = null;

/**
 * Obtient une instance de FlexcubeAPI configurée
 * 
 * @return FlexcubeAPI
 */
function getFlexcubeAPI() {
    global $_flexcube_instance;
    
    if ($_flexcube_instance === null) {
        $_flexcube_instance = new FlexcubeAPI();
        
        // Configurer depuis les constantes d'environnement si disponibles
        if (defined('FLEXCUBE_API_URL')) {
            $_flexcube_instance->setApiUrl(FLEXCUBE_API_URL);
        }
        if (defined('FLEXCUBE_SOURCE_CODE')) {
            $_flexcube_instance->setAuthConfig(FLEXCUBE_SOURCE_CODE, FLEXCUBE_AFFILIATE_CODE ?? 'ECG');
        }
        if (defined('FLEXCUBE_VERIFY_SSL')) {
            $_flexcube_instance->setSSLVerification(FLEXCUBE_VERIFY_SSL);
        }
    }
    
    return $_flexcube_instance;
}

/**
 * Récupère les infos d'un compte via Flexcube
 * 
 * @param string $account_number Numéro de compte
 * @return array|null Données du compte ou null en cas d'erreur
 */
function fetchAccountFromFlexcube($account_number) {
    $api = getFlexcubeAPI();
    $response = $api->getAccountInfo($account_number);
    
    if ($response['success']) {
        return $response['data'];
    }
    
    // Log l'erreur (optionnel)
    error_log("Flexcube Error for account $account_number: " . $response['error']);
    
    return null;
}

/**
 * Récupère un compte - essaye Flexcube, puis la base de données locale
 * 
 * @param string $account_number Numéro de compte
 * @param mysqli $conn Connexion BD
 * @return array Données du compte
 */
function fetchAccountWithFallback($account_number, $conn) {
    
    // Essayer Flexcube d'abord
    $flexcube_data = fetchAccountFromFlexcube($account_number);
    
    if ($flexcube_data) {
        return [
            'source' => 'flexcube',
            'data' => $flexcube_data
        ];
    }
    
    // Fallback vers la BD locale
    $account = mysqli_real_escape_string($conn, $account_number);
    $sql = "SELECT * FROM ecobank_form_submissions 
            WHERE account_number = '$account' OR bank_account_number = '$account' 
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        return [
            'source' => 'local_db',
            'data' => $row
        ];
    }
    
    // Autre fallback: tblCompte
    $sql2 = "SELECT * FROM tblCompte WHERE account_number = '$account' LIMIT 1";
    $res2 = mysqli_query($conn, $sql2);
    
    if ($res2 && mysqli_num_rows($res2) > 0) {
        $row2 = mysqli_fetch_assoc($res2);
        return [
            'source' => 'tblCompte',
            'data' => $row2
        ];
    }
    
    return [
        'source' => 'not_found',
        'data' => null
    ];
}

/**
 * Récupère plusieurs comptes avec Flexcube
 * 
 * @param array $account_numbers Liste de numéros de compte
 * @return array Résultats indexés par numéro de compte
 */
function fetchMultipleAccountsFromFlexcube($account_numbers) {
    $api = getFlexcubeAPI();
    return $api->getMultipleAccounts($account_numbers);
}

/**
 * Teste la connexion à Flexcube
 * 
 * @return array Résultat du test
 */
function testFlexcubeConnection() {
    $api = getFlexcubeAPI();
    return $api->testConnection();
}

/**
 * Mappe les champs Flexcube vers le format local
 * 
 * @param array $flexcube_data Données de Flexcube
 * @return array Données mappées
 */
function mapFlexcubeToLocal($flexcube_data) {
    if (!$flexcube_data) {
        return [];
    }
    
    return [
        'id' => null, // Pas d'ID local
        'customer_id' => $flexcube_data['customer_id'] ?? null,
        'account_number' => $flexcube_data['account_number'] ?? null,
        'bank_account_number' => $flexcube_data['account_number'] ?? null,
        'customer_name' => $flexcube_data['account_name'] ?? null,
        'account_type' => $flexcube_data['account_type'] ?? null,
        'currency' => $flexcube_data['currency'] ?? null,
        'status' => $flexcube_data['status'] ?? null,
        'balance' => $flexcube_data['balance'] ?? null,
        'created_at' => $flexcube_data['opening_date'] ?? date('Y-m-d H:i:s'),
        'source' => 'flexcube'
    ];
}

/**
 * Enrichit une ligne BD avec les données Flexcube
 * 
 * @param array $db_row Données de la BD
 * @param array $flexcube_data Données Flexcube (optionnel)
 * @return array Données enrichies
 */
function enrichRowWithFlexcube($db_row, $flexcube_data = null) {
    if (!$flexcube_data) {
        $account_number = $db_row['account_number'] ?? $db_row['bank_account_number'] ?? null;
        if ($account_number) {
            $flexcube_data = fetchAccountFromFlexcube($account_number);
        }
    }
    
    $enriched = $db_row;
    
    if ($flexcube_data) {
        // Enrichir avec les données Flexcube si disponibles
        if ((!isset($enriched['status']) || !$enriched['status']) && isset($flexcube_data['status'])) {
            $enriched['status'] = $flexcube_data['status'];
        }
        if ((!isset($enriched['balance']) || !$enriched['balance']) && isset($flexcube_data['balance'])) {
            $enriched['balance'] = $flexcube_data['balance'];
        }
        $enriched['flexcube_data'] = $flexcube_data;
    }
    
    return $enriched;
}
?>
