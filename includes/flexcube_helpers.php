<?php
/**
 * Helper Functions pour Flexcube Oracle
 * Fonctions pour accès direct à la base de données Oracle Flexcube
 */

/**
 * Instance singleton de la connexion Oracle Flexcube
 */
$_flexcube_oracle_connection = null;

/**
 * Établit une connexion directe à la base de données Oracle Flexcube avec OCI8
 * avec système de fallback (ADC-cemacfc-SCAN puis ldc-cemacfc-scan)
 * 
 * @return resource|null Ressource de connexion OCI8 à Oracle ou null en cas d'erreur
 */
function connectToFlexcubeDatabase() {
    global $_flexcube_oracle_connection;
    
    // Retourner la connexion existante si elle est déjà établie
    if ($_flexcube_oracle_connection !== null) {
        return $_flexcube_oracle_connection;
    }
    
    // Configuration Oracle
    $hosts = ['ADC-CEMACFC-SCAN', 'LDC-CEMACFC-SCAN']; // Fallback hosts
    $port = '1521';
    $service = 'SRVFCUBSCS2';
    $username = 'cyoka';
    $password = 'Welcometo@2026';
    
    $last_error = null;
    
    // Essayer chaque hôte
    foreach ($hosts as $hote) {
        try {
            // Format de connexion OCI8: //HOST:PORT/SERVICE_NAME
            $connection_string = "//$hote:$port/$service";
            
            $_flexcube_oracle_connection = @oci_connect($username, $password, $connection_string);
            
            if ($_flexcube_oracle_connection) {
                error_log("[Flexcube] Connexion OCI8 réussie à Oracle via $hote");
                return $_flexcube_oracle_connection;
            } else {
                $e = oci_error();
                $last_error = $e['message'];
                error_log("[Flexcube] Erreur de connexion OCI8 à $hote: " . $last_error);
                continue;
            }
            
        } catch (Exception $erreur) {
            $last_error = $erreur->getMessage();
            error_log("[Flexcube] Exception lors de la connexion à $hote: " . $last_error);
            continue;
        }
    }
    
    // Si aucune connexion n'a réussi
    error_log("[Flexcube] ERREUR CRITIQUE: Impossible de connecter à la base de données Oracle. Erreur: " . $last_error);
    return null;
}

/**
 * Récupère les données d'un compte directement depuis la base de données Oracle Flexcube avec OCI8
 * 
 * @param string $account_number Numéro de compte
 * @return array|null Données du compte formatées ou null en cas d'erreur
 */
function fetchAccountFromOracleDatabase($account_number) {
    try {
        $conn = connectToFlexcubeDatabase();
        
        if ($conn === null) {
            error_log("[Flexcube] Impossible de se connecter à la base de données Oracle");
            return null;
        }
        
        // Validation du numéro de compte
        if (empty($account_number)) {
            error_log("[Flexcube] Numéro de compte vide");
            return null;
        }
        
        error_log("[Flexcube] Récupération du compte: $account_number");
        
        // Requête SQL pour récupérer les données du compte depuis Oracle
        $sql = "SELECT
            a.branch_code,
            a.cust_ac_no AS account_number,
            a.ac_desc AS account_name,
            cp.FIRST_NAME AS first_name,
            cp.LAST_NAME AS last_name,
            cp.MIDDLE_NAME AS middle_name,
            cp.SEX AS sex,
            a.cust_no AS customer_id,
            cp.date_of_birth,
            cp.place_of_birth,
            cp.P_NATIONAL_ID AS national_id,
            cp.PASSPORT_NO AS passport_no,
            cp.PPT_ISS_DATE AS passport_issue_date,
            cp.PPT_EXP_DATE AS passport_expiry_date,
            a.clearing_ac_no AS rib,
            cp.telephone,
            a.ac_open_date AS opening_date,
            mis.code_desc AS manager_name,
            TRIM(NVL(a.address1, '') || ' ' || NVL(a.address2, '') || ' ' || NVL(a.address4, '')) AS account_address,
            TRIM(NVL(cu.address_line1, '') || ' ' || NVL(cu.address_line2, '') || ' ' || NVL(cu.address_line3, '') || ' ' || NVL(cu.address_line4, '')) AS customer_address
        FROM
            fcubscs2.sttm_cust_account a
        LEFT JOIN
            fcubscs2.sttm_account_balance c ON a.cust_ac_no = c.cust_ac_no
        LEFT JOIN
            fcubscs2.sttm_cust_personal cp ON a.cust_no = cp.customer_no
        LEFT JOIN
            fcubscs2.sttm_customer cu ON a.cust_no = cu.customer_no
        LEFT JOIN (SELECT DISTINCT customer, comp_mis_1, cust_mis_2 FROM fcubscs2.mitm_customer_default) d ON a.cust_no = d.customer
        LEFT JOIN (SELECT DISTINCT mis_code, code_desc FROM fcubscs2.gltm_mis_code WHERE mis_code LIKE 'CG%') mis ON mis.mis_code = d.comp_mis_1
        WHERE
            a.location = 'CG'
            AND a.cust_ac_no = :account_no
            AND ROWNUM = 1";
        
        // Parser la requête
        $stid = oci_parse($conn, $sql);
        
        if (!$stid) {
            $e = oci_error($conn);
            error_log("[Flexcube] Erreur de préparation de requête OCI8: " . $e['message']);
            return null;
        }
        
        // Bind le paramètre
        oci_bind_by_name($stid, ':account_no', $account_number);
        
        error_log("[Flexcube] Paramètre bindé: account_no = $account_number");
        
        // Exécuter la requête
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            error_log("[Flexcube] Erreur d'exécution OCI8 pour le compte $account_number: " . $e['message']);
            oci_free_statement($stid);
            return null;
        }
        
        error_log("[Flexcube] Requête exécutée avec succès");
        
        // Récupérer le résultat
        $result = oci_fetch_assoc($stid);
        oci_free_statement($stid);
        
        if ($result) {
            // Convertir les clés en minuscules pour cohérence
            $result = array_change_key_case($result, CASE_LOWER);
            error_log("[Flexcube] Données récupérées avec succès pour le compte: $account_number");
            error_log("[Flexcube] Données récupérées: " . json_encode($result));
            return $result;
        } else {
            error_log("[Flexcube] Aucune donnée trouvée pour le compte: $account_number");
            return null;
        }
        
    } catch (Exception $e) {
        error_log("[Flexcube] Erreur générale lors de la récupération du compte $account_number: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les infos d'un compte depuis Flexcube (Oracle directement)
 * 
 * @param string $account_number Numéro de compte
 * @return array|null Données du compte ou null en cas d'erreur
 */
function fetchAccountFromFlexcube($account_number) {
    return fetchAccountFromOracleDatabase($account_number);
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
