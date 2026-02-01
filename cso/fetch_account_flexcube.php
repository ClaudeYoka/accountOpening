<?php
/**
 * API AJAX Endpoint - Fetch Account via Flexcube
 *
 * Called from ecobank_account_form.php JavaScript when user enters account number
 * Returns account data from Flexcube API for form auto-fill
 */

include('../includes/config.php');
include('../includes/session.php');
include('includes/flexcube_helpers.php');

header('Content-Type: application/json; charset=utf-8');

// Get account number from request
$account_number = isset($_POST['account']) ? trim($_POST['account']) : '';
$account_number = isset($_GET['account']) ? trim($_GET['account']) : $account_number;

if (empty($account_number)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Numéro de compte manquant'
    ]);
    exit;
}

// Validate format (basic check)
if (!preg_match('/^[0-9]{10,20}$/', $account_number)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Format de numéro de compte invalide'
    ]);
    exit;
}

try {
    // Try to fetch from Flexcube first
    $flexcube_data = fetchAccountFromFlexcube($account_number);
    
    if ($flexcube_data) {
        // Map Flexcube data to form field names
        $form_data = mapFlexcubeDataToFormFields($flexcube_data);
        
        echo json_encode([
            'success' => true,
            'source' => 'flexcube',
            'data' => $form_data,
            'raw' => $flexcube_data
        ]);
        exit;
    }
    
    // Fallback to local database
    $db_result = fetchAccountWithFallback($account_number, $conn);
    
    if ($db_result['data']) {
        $form_data = mapDatabaseDataToFormFields($db_result['data']);
        
        echo json_encode([
            'success' => true,
            'source' => $db_result['source'],
            'data' => $form_data,
            'raw' => $db_result['data']
        ]);
        exit;
    }
    
    // Not found anywhere
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Compte introuvable dans Flexcube et base de données'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}

/**
 * Map Flexcube API response to form field names
 * 
 * @param array $flexcube_data Data from Flexcube API
 * @return array Mapped form data
 */
function mapFlexcubeDataToFormFields($flexcube_data) {
    $form_data = [];
    
    // Account info
    $form_data['account_number'] = $flexcube_data['account_number'] ?? null;
    $form_data['account-number-field'] = $flexcube_data['account_number'] ?? null;
    $form_data['account_name'] = $flexcube_data['account_name'] ?? null;
    $form_data['account_type'] = $flexcube_data['account_type'] ?? null;
    $form_data['currency'] = $flexcube_data['currency'] ?? null;
    $form_data['status'] = $flexcube_data['status'] ?? null;
    $form_data['balance'] = $flexcube_data['balance'] ?? null;
    $form_data['customer_id'] = $flexcube_data['customer_id'] ?? null;
    
    // Use form_fields if available (from UDFDataMapper)
    if (!empty($flexcube_data['form_fields']) && is_array($flexcube_data['form_fields'])) {
        $form_data = array_merge($form_data, $flexcube_data['form_fields']);
    }
    
    // Try to extract name parts from account_name if not already in form_fields
    if (!empty($flexcube_data['account_name']) && empty($form_data['first-name'])) {
        $name_parts = explode(' ', trim($flexcube_data['account_name']));
        
        if (count($name_parts) > 0) {
            $form_data['first-name'] = $name_parts[0];
            $form_data['noms'] = $name_parts[0]; // Alternative field name
            $form_data['prenom'] = $name_parts[0]; // French alternative
        }
        
        if (count($name_parts) > 1) {
            $form_data['last-name'] = $name_parts[count($name_parts) - 1];
            $form_data['nom'] = $name_parts[count($name_parts) - 1];
        }
        
        if (count($name_parts) > 2) {
            $form_data['middle-name'] = implode(' ', array_slice($name_parts, 1, -1));
            $form_data['prenom2'] = implode(' ', array_slice($name_parts, 1, -1));
        }
    }
    
    // Dates and other fields
    if (!empty($flexcube_data['opening_date'])) {
        $form_data['opening_date'] = $flexcube_data['opening_date'];
        $form_data['date_open'] = $flexcube_data['opening_date'];
    }
    
    // Branch info
    if (!empty($flexcube_data['branch_code'])) {
        $form_data['branch_code'] = $flexcube_data['branch_code'];
    }
    
    return array_filter($form_data, function($v) {
        return $v !== null && $v !== '';
    });
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
