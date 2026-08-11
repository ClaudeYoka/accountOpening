<?php
/**
 * API AJAX Endpoint - Fetch Account via Flexcube
 *
 * Called from ecobank_account_form.php JavaScript when user enters account number
 * Returns account data from Flexcube API for form auto-fill
 */

// Prevent any HTML output that could break JSON
ob_start();

// Only include what's needed for Oracle connection
include('../includes/flexcube_helpers.php');

header('Content-Type: application/json; charset=utf-8');

// Get account number from request
$account_number = isset($_POST['account']) ? trim($_POST['account']) : '';
$account_number = isset($_GET['account']) ? trim($_GET['account']) : $account_number;

if (empty($account_number)) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Numéro de compte manquant'
    ]);
    exit;
}

// Validate format (basic check)
if (!preg_match('/^[0-9]{10,20}$/', $account_number)) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Format de numéro de compte invalide'
    ]);
    exit;
}

try {
    error_log("[fetch_account_flexcube] Début de la requête pour le compte: $account_number");

    // Try to fetch from Flexcube first (via OCI8)
    $flexcube_data = fetchAccountFromFlexcube($account_number);

    error_log("[fetch_account_flexcube] Résultat: " . ($flexcube_data ? 'Données trouvées' : 'Aucune donnée'));

    if ($flexcube_data) {
        error_log("[fetch_account_flexcube] Données brutes: " . json_encode($flexcube_data));

        // Map Flexcube data to form field names
        $form_data = mapFlexcubeDataToFormFields($flexcube_data);

        error_log("[fetch_account_flexcube] Données mappées: " . json_encode($form_data));

        ob_end_flush();
        echo json_encode([
            'success' => true,
            'source' => 'flexcube',
            'data' => $form_data,
            'raw' => $flexcube_data
        ]);
        exit;
    }

    // If Flexcube fails, return not found error
    // Do NOT attempt MySQL fallback here as it causes PDO errors
    error_log("[fetch_account_flexcube] Compte introuvable dans Flexcube");
    http_response_code(404);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Compte introuvable dans Flexcube'
    ]);
    exit;

} catch (Exception $e) {
    error_log('[fetch_account_flexcube] Exception: ' . $e->getMessage());
    error_log('[fetch_account_flexcube] Stack:' . $e->getTraceAsString());
    http_response_code(500);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Map Flexcube API response to form field names
 * Clean version - retourne juste les champs nécessaires
 *
 * @param array $flexcube_data Data from Flexcube API
 * @return array Mapped form data
 */
function mapFlexcubeDataToFormFields($flexcube_data) {
    $form_data = [];

    // Account number
    $form_data['account_number'] = $flexcube_data['account_number'] ?? '';

    // Customer ID
    $form_data['customer_id'] = $flexcube_data['customer_id'] ?? '';

// Email (support de plusieurs variantes pour l'auto-remplissage du formulaire)
        $raw_email = $flexcube_data['email']
            ?? $flexcube_data['email_address']
            ?? $flexcube_data['adresse_email']
            ?? $flexcube_data['mail']
            ?? $flexcube_data['e_mail']
            ?? $flexcube_data['EMAIL']
            ?? $flexcube_data['EMAIL_ID']
            ?? $flexcube_data['EMAILID']
            ?? '';

        $form_data['email'] = $raw_email;
        $form_data['email_address'] = $raw_email;
        $form_data['adresse_email'] = $raw_email;
        $form_data['mail'] = $raw_email;
        $form_data['e_mail'] = $raw_email;

    // Telephone
    $form_data['telephone'] = $flexcube_data['telephone'] ?? '';
    $form_data['phone_number'] = $flexcube_data['telephone'] ?? '';

    // RIB / clé RIB
    $form_data['rib'] = $flexcube_data['rib'] ?? $flexcube_data['clearing_ac_no'] ?? $flexcube_data['rib_key'] ?? '';
    $form_data['rib_key'] = $flexcube_data['rib_key'] ?? $flexcube_data['rib'] ?? $flexcube_data['clearing_ac_no'] ?? '';
    $form_data['clearing_ac_no'] = $flexcube_data['clearing_ac_no'] ?? $form_data['rib'];

    // Customer address
    $form_data['customer_address'] = $flexcube_data['customer_address'] ?? '';

    // Branch code
    $form_data['branch_code'] = $flexcube_data['branch_code'] ?? '';

    // Account title direct from Flexcube (ac_desc)
    $form_data['account_title'] = $flexcube_data['account_name'] ?? $flexcube_data['account_title'] ?? '';
    $form_data['customer_name'] = $form_data['account_title'];
    $form_data['account_name'] = $form_data['account_title'];

    // Names - use explicit fields when available, otherwise preserve explicit fields if possible
    if (!empty($flexcube_data['first_name']) || !empty($flexcube_data['last_name'])) {
        $form_data['first_name'] = $flexcube_data['first_name'] ?? '';
        $form_data['last_name'] = $flexcube_data['last_name'] ?? '';
    } else {
        $form_data['first_name'] = '';
        $form_data['last_name'] = '';
    }

    // Middle name if available
    $form_data['middle_name'] = $flexcube_data['middle_name'] ?? '';

    // Date of birth
    $form_data['date_of_birth'] = $flexcube_data['date_of_birth'] ?? '';

    return $form_data;
}

/**
 * Map database record to form field names
 * 
 * @param array $db_row Database row
 * @return array Mapped form data
 */
function mapDatabaseDataToFormFields($db_row) {
    $form_data = [];
    
    // Map common database fields to form fields
    $field_mapping = [
        'account_number' => ['account_number', 'account-number-field'],
        'noms' => ['first-name', 'noms', 'prenom'],
        'nom' => ['last-name', 'nom'],
        'prenom2' => ['middle-name', 'prenom2'],
        'email' => ['email'],
        'mobile1' => ['telephone', 'phone', 'tel'],
        'mobile2' => ['telephone2', 'phone2', 'tel2'],
        'nationalite' => ['nationality'],
        'lieu_naiss' => ['pob', 'place-of-birth'],
        'pays' => ['residence-country', 'country'],
        'id_num' => ['document-number', 'id-number'],
        'employeur' => ['employer-name', 'employer'],
        'services' => ['other-services', 'services'],
        'customer_id' => ['customer-id', 'customer_id'],
        'father_name' => ['father-name'],
        'mother_name' => ['mother-name']
    ];
    
    foreach ($field_mapping as $db_field => $form_fields) {
        if (isset($db_row[$db_field]) && !empty($db_row[$db_field])) {
            $value = $db_row[$db_field];
            
            // For name fields, try to split them
            if ($db_field === 'noms' && is_string($value)) {
                $parts = explode(' ', trim($value));
                $form_data['first-name'] = $parts[0] ?? null;
                $form_data['noms'] = $parts[0] ?? null;
                if (count($parts) > 1) {
                    $form_data['last-name'] = $parts[count($parts) - 1];
                }
                if (count($parts) > 2) {
                    $form_data['middle-name'] = implode(' ', array_slice($parts, 1, -1));
                }
            } else {
                // Set all mapped form field names
                foreach ($form_fields as $form_field) {
                    $form_data[$form_field] = $value;
                }
            }
        }
    }
    
    // Generic fallback: copy all fields
    foreach ($db_row as $key => $value) {
        if (!empty($value) && !isset($form_data[$key])) {
            $form_data[$key] = $value;
        }
    }
    
    return array_filter($form_data, function($v) { 
        return $v !== null && $v !== ''; 
    });
}
?>
