<?php
// Éviter toute sortie HTML qui pourrait casser le JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Ne pas afficher les erreurs directement

try {
    @include_once(__DIR__ . '/../includes/config.php');
    @include_once(__DIR__ . '/../includes/flexcube_helpers.php');

function json_error($message) {
    ob_end_clean(); // Vider le buffer de sortie
    echo json_encode(['status'=>'error','message'=>$message]);
    exit;
}

$account = isset($_GET['account']) ? trim($_GET['account']) : '';
$rib_key = isset($_GET['rib_key']) ? trim($_GET['rib_key']) : '';

error_log("rib_lookup.php called with account: '$account' (length: " . strlen($account) . ")");

if(!$account){
    json_error('Numéro de compte manquant');
}

// Rechercher uniquement via Flexcube SQL
$row = null;
try {
    set_time_limit(40);
    $flexcube_data = fetchAccountFromFlexcube($account);
    if ($flexcube_data) {
        $row = [
            'account_number' => $flexcube_data['account_number'] ?? null,
            'customer_name' => $flexcube_data['account_name'] ?? null,
            'customer_address' => $flexcube_data['customer_address'] ?? null,
            'manager_name' => $flexcube_data['manager_name'] ?? null,
            'internal_account' => $flexcube_data['account_number'] ?? null,
            'currency' => 'XAF',
            'branch_code' => $flexcube_data['branch_code'] ?? 'T31',
            'account_type' => $flexcube_data['account_type'] ?? ($flexcube_data['description'] ?? null),
            'opening_date' => $flexcube_data['opening_date'] ?? null,
            'source' => 'flexcube_oracle',
            'rib_key' => null,
            'json_snapshot' => json_encode($flexcube_data)
        ];
    }
} catch (Exception $e) {
    error_log('Flexcube Oracle Error: ' . $e->getMessage());
} catch (Throwable $t) {
    error_log('Flexcube Oracle Throwable: ' . $t->getMessage());
}

if (!$row) {
    echo json_encode(['status'=>'not_found','message'=>'Compte introuvable']);
    exit;
}

$data = [];
if($row && !empty($row['json_snapshot'])){
    $data = json_decode($row['json_snapshot'], true) ?: [];
}

// Helper to pick first non-empty from row, snapshot keys, flexcube keys
function pick_sn($row, $data, $keys){
    foreach($keys as $k){
        if(isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') return $row[$k];
        if(isset($data[$k]) && $data[$k] !== null && $data[$k] !== '') return $data[$k];
    }
    return null;
}

function extractRibKey($value){
    if (!$value) return null;
    $digits = preg_replace('/\D/', '', (string)$value);
    if ($digits === '') return null;
    return strlen($digits) >= 2 ? substr($digits, -2) : $digits;
}

// Mapping des codes guichets Ecobank vers les codes numériques RIB
function mapBranchCodeToRIB($branchCode){
    $mapping = [
        'T31' => '00001',
        'T34' => '00004',
        'T41' => '00011',
        'T33' => '00003',
        'T35' => '00005',
        'T32' => '00002',
        'T40' => '00010',
        'T38' => '00008',
        'T39' => '00009'
    ];
    return isset($mapping[$branchCode]) ? $mapping[$branchCode] : $branchCode;
}

// Fixed bank identifiers (invariables)
$DEFAULT_COUNTRY_CODE = 'CG39';
$DEFAULT_BANK_CODE = '30014';
$DEFAULT_BRANCH_CODE = '00001';

// Get account info - prioritize row data (from local DB)
$account_number_val = $row ? ($row['account_number'] ?? ($row['bank_account_number'] ?? ($data['account_number'] ?? null))) : ($data['account_number'] ?? null);
$rib_value = pick_sn($row ?: [], $data, ['rib', 'rib_key', 'clearing_ac_no', 'RIB']);
$rkey_val = $rib_key ?: extractRibKey($rib_value);

// Determine account opening date: prefer DB created_at (submission date), fallback to snapshot fields
$date_open_val = null;
if ($row && !empty($row['opening_date'])) {
    $ts = strtotime($row['opening_date']);
    if ($ts !== false) $date_open_val = date('d-m-Y', $ts);
}
if ($date_open_val === null) {
    $tmp = pick_sn($row ?: [], $data, ['opening_date','date_open','date-of-opening','date_ouverture']);
    if ($tmp) {
        $ts2 = strtotime($tmp);
        if ($ts2 !== false) $date_open_val = date('d-m-Y', $ts2);
        else $date_open_val = $tmp;
    }
}

$account_obj = [
    'account_number' => $account_number_val,
    'rib_key' => $rkey_val,
    'rib_full' => $rib_value,
    'account_title' => pick_sn($row ?: [], $data, ['account_title','account_name','customer_name','full_name']),
    // use fixed codes unless overridden by snapshot
    // 'country_code' => pick_sn($row ?: [], $data, ['country_code','country-code','residence_country','pays','country']) ?: $DEFAULT_COUNTRY_CODE,
    'country_code' => $DEFAULT_COUNTRY_CODE,
    'bank_code' => pick_sn($row ?: [], $data, ['bank_code','bank-code']) ?: $DEFAULT_BANK_CODE,
    'branch_code' => pick_sn($row ?: [], $data, ['branch_code','branch-code']) ?: $DEFAULT_BRANCH_CODE,
    'account_type' => pick_sn($row ?: [], $data, ['account_type','type','compte_type']),
    'date_open' => $date_open_val,
    'currency' => pick_sn($row ?: [], $data, ['currency','devise','account_currency']) ?: null,
    'iban' => null,
    'internal_account' => $row ? ($row['account_number'] ?? ($row['bank_account_number'] ?? null)) : null,
    'manager_name' => pick_sn($row ?: [], $data, ['manager_name','account_manager','manager','charge_compte','chargé_compte']) ?: null,
    'swift' => pick_sn($row ?: [], $data, ['swift','swift_code','swift-code']) ?: 'ECOCCGCG'
];

// Compose the IBAN using the fixed codes and the provided account + rib key (only account and rib_key vary)
if($account_obj['country_code'] && $account_obj['bank_code'] && $account_obj['branch_code'] && $account_obj['account_number']){
    // Map branch code to RIB equivalent (e.g., T31 -> 00001)
    $rib_branch_code = mapBranchCodeToRIB($account_obj['branch_code']);
    $iban_key = $account_obj['rib_key'] ?: '';
    $account_obj['iban'] = $account_obj['country_code'] . ' - ' . $account_obj['bank_code'] . ' - ' . $rib_branch_code . ' - ' . $account_obj['account_number'] . ($iban_key ? ' - ' . $iban_key : '');
} else {
    $account_obj['iban'] = trim(($data['iban'] ?? '') ?: '');
}

$bank_obj = [
    'name' => 'ECOBANK CONGO',
    'address' => "Croisement des avenues Gouverneur Félix ÉBOUÉ et Amilcar CABRAL, quartier la Plaine, Centre-Ville."
];

$customer = [
    'customer_name' => pick_sn($row, $data, ['customer_name','account_name','full_name','name']) ?: null,
    'first_name' => pick_sn($row, $data, ['first_name','prenom','given_name']) ?: null,
    'middle_name' => pick_sn($row, $data, ['middle_name','prenom2','middle_name_2','second_name']) ?: null,
    'last_name' => pick_sn($row, $data, ['last_name','nom','family_name']) ?: null,
    'email' => pick_sn($row, $data, ['email','courriel','email_address']) ?: null,
    'mobile' => pick_sn($row, $data, ['mobile','telephone','telephone1','mobile1']) ?: null
];

// Si seul le nom complet est disponible, tenter de le splitter en prénom / nom
if (empty($customer['first_name']) && empty($customer['last_name']) && !empty($customer['customer_name'])) {
    $parts = preg_split('/\s+/', trim($customer['customer_name']));
    if (count($parts) === 1) {
        $customer['first_name'] = $parts[0];
    } else {
        $customer['last_name'] = implode(' ', $parts);
        $customer['first_name'] = array_shift($parts);
        
    }
}

$customer_full_name = '';
if (!empty($customer['customer_name'])) {
    $customer_full_name = trim($customer['customer_name']);
} else {
    $customer_full_name = trim(implode(' ', array_filter([
        $customer['last_name'],
        $customer['first_name']
    ], function($part){
        return trim((string)$part) !== '';
    })));
}
if ($customer_full_name) {
    $customer['customer_name'] = $customer_full_name;
    if (empty($account_obj['account_title'])) {
        $account_obj['account_title'] = $customer_full_name;
    }
}

$correspondents = [
    ["bank"=>"BANQUE NATIONAL DU CANADA","account"=>"10332322800100101","swift"=>"BNDCCAMM","currency"=>"CAD"],
    ["bank"=>"BHF Bank Francfort","account"=>"651042","swift"=>"BHFBDEFF","currency"=>"EUR"],
    ["bank"=>"CITI Bank London","account"=>"12083566","swift"=>"CITIGB2L","currency"=>"GBP"],
    ["bank"=>"CITI Bank London","account"=>"12083558","swift"=>"CITIGB2L","currency"=>"EUR"],
    ["bank"=>"CITI Bank NY","account"=>"36903113","swift"=>"CITIUS33","currency"=>"USD"],
    ["bank"=>"CITI Bank NY Master Card","account"=>"36327478","swift"=>"CITIUS33","currency"=>"USD"],
    ["bank"=>"COMMERZ Bank","account"=>"400877408500","swift"=>"COBADEFF","currency"=>"EUR"],
    ["bank"=>"Ecobank Benin","account"=>"0019211111952201","swift"=>"ECOCBJBJ","currency"=>"XOF"],
    ["bank"=>"Ecobank Ghana","account"=>"9989194421210701","swift"=>"ECOCGHAC","currency"=>"USD"],
    ["bank"=>"Ecobank Nigeria","account"=>"9983038748","swift"=>"ECOCNGLA","currency"=>"USD"],
    ["bank"=>"Ecobank Paris","account"=>"EFR9278100004401","swift"=>"ECOCFRPP","currency"=>"EUR"],
    ["bank"=>"Ecobank Paris","account"=>"EFR9278100004402","swift"=>"ECOCFRPP","currency"=>"USD"],
    ["bank"=>"NATIXIS Paris","account"=>"FR7630007999990638451400016EUR","swift"=>"NATXFRPP","currency"=>"EUR"],
    ["bank"=>"NEDBank","account"=>"1986253066","swift"=>"NEDSZAJJ","currency"=>"ZAR"],
    ["bank"=>"Nostro Côte d'Ivoire","account"=>"0019211204379201","swift"=>"ECOCCIAB","currency"=>"XOF"],
];

echo json_encode([
    'status'=>'ok',
    'account'=>$account_obj,
    'bank'=>$bank_obj,
    'customer'=>$customer,
    'correspondents'=>$correspondents
]);

ob_end_flush();
exit;

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['status'=>'error','message'=>'Erreur interne: ' . $e->getMessage()]);
    exit;
}

?>