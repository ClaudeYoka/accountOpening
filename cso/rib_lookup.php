<?php
// Éviter les redirects pendant le traitement JSON
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Ne pas afficher les erreurs directement

try {
    @include('../includes/config.php');
    @include('../includes/FlexcubeAPI.php');
    @include('../includes/flexcube_helpers.php');

$account = isset($_GET['account']) ? trim($_GET['account']) : '';
$rib_key = isset($_GET['rib_key']) ? trim($_GET['rib_key']) : '';

error_log("rib_lookup.php called with account: '$account' (length: " . strlen($account) . ")");

if(!$account){
    echo json_encode(['status'=>'error','message'=>'Numéro de compte manquant']);
    exit;
}

// PRIORITY 0: Check LOCAL DATABASE FIRST (much faster than Flexcube)
// If account exists locally with recent data, use it immediately - don't wait for slow API
$row = null;
$use_flexy = true; // Flag pour décider si on essaie Flexcube

// Quick check in local database first
$q_quick = mysqli_prepare($conn, "SELECT * FROM ecobank_form_submissions WHERE account_number COLLATE utf8mb4_0900_ai_ci = ? LIMIT 1");
if($q_quick){
    mysqli_stmt_bind_param($q_quick, 's', $account);
    mysqli_stmt_execute($q_quick);
    $r_quick = mysqli_stmt_get_result($q_quick);
    if($r_quick && mysqli_num_rows($r_quick) > 0){
        $row = mysqli_fetch_assoc($r_quick);
        $use_flexy = false; // Already found locally, skip Flexcube
        error_log("Account found in local DB immediately, skipping Flexcube API");
    }
    mysqli_stmt_close($q_quick);
}

// PRIORITY 1: Try FLEXCUBE API first (using real FlexcubeAPI class) - ONLY IF NOT IN LOCAL DB
if($use_flexy){
try {
    // Timeout pour éviter les blocages (augmenté à 15 secondes pour la requête API)
    set_time_limit(20); // 20 secondes maximum pour cette opération complète (15 API + 5 buffer)
    $flexcube_api = new FlexcubeAPI();
    $flexcube_response = $flexcube_api->getAccountInfo($account);
    
    if($flexcube_response['success'] && $flexcube_response['data']){
        // Flexcube trouvé - transformer les données au format RIB
        $flexcube_data = $flexcube_response['data'];
        $row = [
            'account_number' => $flexcube_data['account_number'] ?? null,
            'customer_name' => $flexcube_data['account_name'] ?? null,
            'currency' => $flexcube_data['currency'] ?? 'XAF',
            'branch_code' => $flexcube_data['branch_code'] ?? 'T31',
            'account_type' => $flexcube_data['account_type'] ?? 'Courant',
            'created_at' => date('Y-m-d'),
            'source' => 'flexcube',
            'json_snapshot' => json_encode($flexcube_data)
        ];
    }
} catch (Exception $e) {
    error_log('Flexcube Error: ' . $e->getMessage());
    // Continuer au fallback
} catch (Throwable $t) {
    // Attraper les erreurs fatales/timeouts (PHP 7+)
    error_log('Flexcube Throwable: ' . $t->getMessage());
    // Continuer au fallback
}
} // Fin du if($use_flexy)

// Si Flexcube n'a pas trouvé le compte, fallback vers la base de données locale
if(!$row){
    // PRIORITY 2: Try exact match in LOCAL DATABASE (ecobank_form_submissions)
$sql = "SELECT * FROM ecobank_form_submissions WHERE account_number COLLATE utf8mb4_0900_ai_ci = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $account);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;

// PRIORITY 3: If not found in ecobank_form_submissions, try LIKE search
if(!$row){
    $esc = '%'.$account.'%';
    $sql2 = "SELECT * FROM ecobank_form_submissions WHERE account_number COLLATE utf8mb4_0900_ai_ci LIKE ? LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, 's', $esc);
    mysqli_stmt_execute($stmt2);
    $res = mysqli_stmt_get_result($stmt2);
    $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
}

    // PRIORITY 4: If still not found, try tblcompte
    if(!$row){
        $q = mysqli_prepare($conn, "SELECT id, account_number, noms, mobile1, email FROM tblcompte WHERE account_number COLLATE utf8mb4_0900_ai_ci = ? LIMIT 1");
        if($q){
            mysqli_stmt_bind_param($q, 's', $account);
            mysqli_stmt_execute($q);
            $r2 = mysqli_stmt_get_result($q);
            if($r2 && mysqli_num_rows($r2) > 0){
                $pc = mysqli_fetch_assoc($r2);
                // Try to find a submission by account_number or matching customer name/email/mobile
                $sql3 = "SELECT * FROM ecobank_form_submissions WHERE account_number COLLATE utf8mb4_0900_ai_ci = ? OR customer_name COLLATE utf8mb4_0900_ai_ci LIKE ? OR mobile COLLATE utf8mb4_0900_ai_ci = ? OR email COLLATE utf8mb4_0900_ai_ci = ? LIMIT 1";
                $stmt3 = mysqli_prepare($conn, $sql3);
                $likeName = '%'.($pc['noms'] ?? '').'%';
                $mobile = $pc['mobile1'] ?? '';
                $email = $pc['email'] ?? '';
                mysqli_stmt_bind_param($stmt3, 'ssss', $pc['account_number'], $likeName, $mobile, $email);
                mysqli_stmt_execute($stmt3);
                $res = mysqli_stmt_get_result($stmt3);
                $row = ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : null;
            }
            mysqli_stmt_close($q);
        }
    }
}

// If still not found in any source, return error
if(!$row){
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
$account_number_val = $row ? ($row['account_number'] ?? ($data['account_number'] ?? null)) : ($data['account_number'] ?? null);
$rkey_val = $rib_key ?: ($data['rib_key'] ?? null);

// Determine account opening date: prefer DB created_at (submission date), fallback to snapshot fields
$date_open_val = null;
if ($row && !empty($row['created_at'])) {
    $ts = strtotime($row['created_at']);
    if ($ts !== false) $date_open_val = date('d-m-Y', $ts);
}
if ($date_open_val === null) {
    $tmp = pick_sn($row ?: [], $data, ['date_open','date-of-opening','date_ouverture']);
    if ($tmp) {
        $ts2 = strtotime($tmp);
        if ($ts2 !== false) $date_open_val = date('d-m-Y', $ts2);
        else $date_open_val = $tmp;
    }
}

$account_obj = [
    'account_number' => $account_number_val,
    'rib_key' => $rkey_val,
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
    'internal_account' => $row ? ($row['account_number'] ?? null) : null,
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
    'address' => "Croisement des avenues Gouverneur Félix ÉBOUÉ et Amilcar CABRAL, quartier la Plaine, Centre-Ville. B.P. 2485, Brazzaville"
];

$customer = [
    'customer_name' => pick_sn($row, $data, ['customer_name','full_name','name']) ?: null,
    'first_name' => pick_sn($row, $data, ['first_name','prenom','given_name']) ?: null,
    'last_name' => pick_sn($row, $data, ['last_name','nom','family_name']) ?: null,
    'email' => pick_sn($row, $data, ['email','courriel','email_address']) ?: null,
    'mobile' => pick_sn($row, $data, ['mobile','telephone','telephone1','mobile1']) ?: null
];

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

exit;

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'Erreur interne: ' . $e->getMessage()]);
    exit;
}

?>