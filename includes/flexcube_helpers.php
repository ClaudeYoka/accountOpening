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
 * Récupère un compte - essaye Flexcube, puis les nouvelles tables normalisées
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

    // Fallback vers la table ecobank_form_submissions
    $account = mysqli_real_escape_string($conn, $account_number);

    $sql = "SELECT id, customer_id, account_number, customer_name, first_name, last_name,
                   email, mobile, account_type, created_at
            FROM ecobank_form_submissions
            WHERE account_number = ? OR bank_account_number = ?
            ORDER BY created_at DESC LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $account, $account);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        return [
            'source' => 'database',
            'data' => $row
        ];
    }

    mysqli_stmt_close($stmt);

    return [
        'source' => 'not_found',
        'data' => null,
        'error' => 'Account not found in database'
    ];

    // Fallback vers l'ancienne table (pour compatibilité pendant migration)
    $sql_legacy = "SELECT * FROM ecobank_form_submissions
            WHERE account_number = ? OR bank_account_number = ?
            LIMIT 1";
    $stmt_legacy = mysqli_prepare($conn, $sql_legacy);
    mysqli_stmt_bind_param($stmt_legacy, 'ss', $account, $account);
    mysqli_stmt_execute($stmt_legacy);
    $res_legacy = mysqli_stmt_get_result($stmt_legacy);

    if ($res_legacy && mysqli_num_rows($res_legacy) > 0) {
        $row_legacy = mysqli_fetch_assoc($res_legacy);
        mysqli_stmt_close($stmt_legacy);

        return [
            'source' => 'legacy_db',
            'data' => $row_legacy
        ];
    }

    mysqli_stmt_close($stmt_legacy);

    // Dernier fallback: tblCompte
    $sql2 = "SELECT * FROM tblCompte WHERE account_number = ? LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, 's', $account);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);

    if ($res2 && mysqli_num_rows($res2) > 0) {
        $row2 = mysqli_fetch_assoc($res2);
        mysqli_stmt_close($stmt2);

        return [
            'source' => 'tblcompte',
            'data' => $row2
        ];
    }

    mysqli_stmt_close($stmt2);

    return [
        'source' => 'not_found',
        'data' => null,
        'error' => 'Account not found in any source'
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
 * Récupère les données client normalisées depuis la nouvelle structure
 *
 * @param int $customer_id ID du client dans la table customers
 * @param mysqli $conn Connexion BD
 * @return array|null Données complètes du client ou null
 */
function getCustomerData($customer_id, $conn) {
    $sql = "SELECT * FROM ecobank_form_submissions
            WHERE customer_id = ?
            ORDER BY created_at DESC LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return [
            'customer' => $data,
            'account' => [
                'number' => $data['account_number'],
                'type' => $data['account_type']
            ]
        ];
    }

    mysqli_stmt_close($stmt);
    return null;
}

/**
 * Recherche des comptes par critères (version normalisée)
 *
 * @param array $criteria Critères de recherche
 * @param mysqli $conn Connexion BD
 * @param int $limit Limite des résultats
 * @return array Liste des comptes trouvés
 */
function searchAccounts($criteria, $conn, $limit = 100) {
    $where = [];
    $params = [];
    $types = '';

    if (!empty($criteria['account_number'])) {
        $where[] = "(a.account_number LIKE ? OR a.bank_account_number LIKE ?)";
        $params[] = '%' . $criteria['account_number'] . '%';
        $params[] = '%' . $criteria['account_number'] . '%';
        $types .= 'ss';
    }

    if (!empty($criteria['customer_name'])) {
        $where[] = "(c.customer_name LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
        $params[] = '%' . $criteria['customer_name'] . '%';
        $params[] = '%' . $criteria['customer_name'] . '%';
        $types .= 'ss';
    }

    if (!empty($criteria['status'])) {
        $where[] = "a.status = ?";
        $params[] = $criteria['status'];
        $types .= 's';
    }

    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT id, customer_id, account_number, customer_name, first_name, last_name,
                   email, mobile, account_type, created_at
            FROM ecobank_form_submissions
            $where_clause
            ORDER BY created_at DESC
            LIMIT ?";

    $params[] = $limit;
    $types .= 'i';

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $accounts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $accounts[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $accounts;
}

/**
 * Enrichit une ligne de données avec des informations Flexcube
 * 
 * @param array $row Ligne de données de base de données
 * @return array Ligne enrichie
 */
function enrichRowWithFlexcube($row) {
    if (empty($row['account_number'])) {
        return $row;
    }
    
    $flexcube_data = fetchAccountFromFlexcube($row['account_number']);
    
    if ($flexcube_data) {
        // Fusionner les données Flexcube avec les données locales
        $enriched = array_merge($row, [
            'flexcube_balance' => $flexcube_data['balance'] ?? null,
            'flexcube_status' => $flexcube_data['status'] ?? null,
            'flexcube_currency' => $flexcube_data['currency'] ?? null,
            'flexcube_opening_date' => $flexcube_data['opening_date'] ?? null,
            'source' => 'flexcube_enriched'
        ]);
        
        // Utiliser les données Flexcube pour certains champs si manquants localement
        if (empty($enriched['customer_name']) && !empty($flexcube_data['account_name'])) {
            $enriched['customer_name'] = $flexcube_data['account_name'];
        }
        if (empty($enriched['account_type']) && !empty($flexcube_data['account_type'])) {
            $enriched['account_type'] = $flexcube_data['account_type'];
        }
        
        return $enriched;
    }
    
    return $row;
}
?>
