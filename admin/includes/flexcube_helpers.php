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
    $password = 'Piratemoi@2026';
    
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
        
        // Requête SQL pour récupérer les données du compte depuis Oracle
        $sql = "SELECT
            a.branch_code,
            a.cust_no AS ID,
            a.cust_ac_no AS account_number,
            a.ac_desc AS account_name,
            a.account_class AS Account_Class,
            a.ccy AS Devise,
            b.description,
            cp.date_of_birth AS Date_naissance,
            cp.FIRST_NAME AS first_name,
            cp.LAST_NAME AS last_name,
            cp.MIDDLE_NAME AS middle_name,
            cp.SEX AS sex,
            cp.e_mail AS Email,
            a.cust_no AS customer_id,
            cp.date_of_birth,
            cp.place_of_birth,
            cp.P_NATIONAL_ID AS national_id,
            cp.PASSPORT_NO AS passport_no,
            cp.PPT_ISS_DATE AS passport_issue_date,
            cp.PPT_EXP_DATE AS passport_expiry_date,
            a.clearing_ac_no AS rib,
            cp.telephone,
            cp.e_mail AS email,
            a.ac_open_date AS opening_date,
            mis.code_desc AS manager_name,
            TRIM(NVL(a.address1, '') || ' ' || NVL(a.address2, '') || ' ' || NVL(a.address4, '')) AS account_address,
            TRIM(NVL(cu.address_line1, '') || ' ' || NVL(cu.address_line2, '') || ' ' || NVL(cu.address_line3, '') || ' ' || NVL(cu.address_line4, '')) AS customer_address
        FROM
            fcubscs2.sttm_cust_account a
        LEFT JOIN
            fcubscs2.sttm_account_class b ON a.account_class = b.account_class
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
