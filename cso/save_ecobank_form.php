<?php
// save_ecobank_form.php
// Save submitted Ecobank form data into a submissions table as JSON. Returns JSON response.
// include session while capturing any unintended output (prevent HTML redirect from being sent)
ob_start();
include('../includes/session.php');
$__sess_out = ob_get_clean();
// if session include emitted a redirect script, treat as unauthorized
if ($__sess_out && (stripos($__sess_out, 'window.location') !== false || stripos($__sess_out, '<script') !== false)){
    debug_log_local('session include emitted redirect: ' . substr($__sess_out,0,500));
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized - please login']);
    exit;
}

include('../includes/config.php');
// Disable direct display of PHP errors (they break JSON responses) and enable logging
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Register shutdown handler to capture fatal errors
register_shutdown_function(function(){
    $err = error_get_last();
    if($err){
        // Only log fatal errors
        if(in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])){
            debug_log_local('FATAL ERROR: ' . json_encode($err));
        }
    }
});

// If session variable missing, respond with JSON 401
if (!isset($_SESSION) || !isset($_SESSION['alogin']) || trim($_SESSION['alogin']) === '') {
    debug_log_local('Unauthorized access attempt to save endpoint (no session vars)');
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized - please login']);
    exit;
}

// Quick sanity: ensure DB connection exists
if (!isset($conn) || !$conn) {
    error_log('[save_ecobank_form] Missing $conn or DB connection failed');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection error']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Read input (accept JSON or form-encoded)
$input = file_get_contents('php://input');
$data = null;
if ($input) {
    $decoded = json_decode($input, true);
    if ($decoded !== null) $data = $decoded;
}
if ($data === null) {
    // fallback to $_POST
    $data = $_POST;
}

// helper to write local debug log for easier inspection
function debug_log_local($msg){
    $path = __DIR__ . '/save_ecobank_form_debug.log';
    $line = '['.date('c').'] ' . $msg . PHP_EOL;
    // log both to system log and a local debug file
    error_log('[save_ecobank_form] ' . $msg);
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

// Log a short excerpt of the raw input and payload keys (helps debug 500)
if (is_string($input) && $input !== '') {
    debug_log_local('raw_input: ' . substr($input,0,1000));
}
if (is_array($data)) {
    debug_log_local('payload keys: ' . json_encode(array_keys($data)));
} else {
    debug_log_local('payload type: ' . gettype($data));
}

// Normalize to array
if (!is_array($data)) {
    $data = ['raw' => (string)$data];
}

// Pick a simple customer id if provided (customer_id or bank_account_number)
$customer_id = isset($data['customer_id']) ? $data['customer_id'] : (isset($data['bank-account-number']) ? $data['bank-account-number'] : null);

// Ensure table exists (safe CREATE TABLE IF NOT EXISTS)
$createSql = "CREATE TABLE IF NOT EXISTS ecobank_form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(128) DEFAULT NULL,
    account_number VARCHAR(128) DEFAULT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    mobile VARCHAR(64) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    data JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (account_number),
    INDEX (customer_name),
    INDEX (mobile),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$submissions_ok = true; // will become false if we fail to store the JSON snapshot
if (!mysqli_query($conn, $createSql)) {
    debug_log_local('Failed to ensure storage table: '.mysqli_error($conn));
    // do not abort overall flow; mark submissions storage as unavailable
    $submissions_ok = false;
} else {
    // Ensure additional columns exist (backfill schema so older DBs work)
    $needed = [
        'account_number' => 'VARCHAR(128) DEFAULT NULL',
        'bank_account_number' => 'VARCHAR(128) DEFAULT NULL',
        'customer_id' => 'VARCHAR(128) DEFAULT NULL',
        'customer_name' => 'VARCHAR(255) DEFAULT NULL',
        'title' => 'VARCHAR(20) DEFAULT NULL',
        'gender' => 'VARCHAR(20) DEFAULT NULL',
        'first_name' => 'VARCHAR(128) DEFAULT NULL',
        'middle_name' => 'VARCHAR(128) DEFAULT NULL',
        'last_name' => 'VARCHAR(128) DEFAULT NULL',
        'father_name' => 'VARCHAR(128) DEFAULT NULL',
        'mother_name' => 'VARCHAR(128) DEFAULT NULL',
        'date_of_birth' => 'DATE DEFAULT NULL',
        'pob' => 'VARCHAR(128) DEFAULT NULL',
        'id_type' => 'VARCHAR(64) DEFAULT NULL',
        'id_number' => 'VARCHAR(64) DEFAULT NULL',
        'id_issue_date' => 'DATE DEFAULT NULL',
        'id_expiry_date' => 'DATE DEFAULT NULL',
        'nationality' => 'VARCHAR(64) DEFAULT NULL',
        'residence_country' => 'VARCHAR(64) DEFAULT NULL',
        'address' => 'VARCHAR(255) DEFAULT NULL',
        'city' => 'VARCHAR(128) DEFAULT NULL',
        'postal_code' => 'VARCHAR(64) DEFAULT NULL',
        'bp' => 'VARCHAR(64) DEFAULT NULL',
        'telephone' => 'VARCHAR(64) DEFAULT NULL',
        'telephone2' => 'VARCHAR(64) DEFAULT NULL',
        'mobile' => 'VARCHAR(64) DEFAULT NULL',
        'email' => 'VARCHAR(128) DEFAULT NULL',
        'employer_name' => 'VARCHAR(128) DEFAULT NULL',
        'employment_type' => 'VARCHAR(128) DEFAULT NULL',
        'salaried_terms' => 'VARCHAR(128) DEFAULT NULL',
        'salaried_occupation' => 'VARCHAR(128) DEFAULT NULL',
        'salaried_industry' => 'VARCHAR(128) DEFAULT NULL',
        'gross_income_range' => 'VARCHAR(128) DEFAULT NULL',
        'business_name' => 'VARCHAR(255) DEFAULT NULL',
        'business_registration' => 'VARCHAR(255) DEFAULT NULL',
        'business_kra_pin' => 'VARCHAR(128) DEFAULT NULL',
        'business_nature' => 'VARCHAR(128) DEFAULT NULL',
        'turnover_range' => 'VARCHAR(128) DEFAULT NULL',
        'institution_name' => 'VARCHAR(255) DEFAULT NULL',
        'student_id' => 'VARCHAR(128) DEFAULT NULL',
        'client_fingerprint' => 'VARCHAR(255) DEFAULT NULL',
        'magistrat_name' => 'VARCHAR(255) DEFAULT NULL',
        'agent_name' => 'VARCHAR(255) DEFAULT NULL',
        'emergency_contact_name' => 'VARCHAR(255) DEFAULT NULL',
        'account_officer' => 'VARCHAR(128) DEFAULT NULL',
        'account_handler' => 'VARCHAR(128) DEFAULT NULL',
        'branch_code' => 'VARCHAR(64) DEFAULT NULL',
        'approved_by' => 'VARCHAR(128) DEFAULT NULL',
        'approved_signature' => 'TEXT DEFAULT NULL',
        'account_purpose' => 'VARCHAR(128) DEFAULT NULL',
        'currency' => 'VARCHAR(64) DEFAULT NULL',
        'services' => 'TEXT DEFAULT NULL',
        'tax_registration' => 'VARCHAR(64) DEFAULT NULL',
        'emp_id' => 'INT DEFAULT NULL'
    ];
    // fetch existing columns for the table
    $existing = [];
    $schema = defined('DB_NAME') ? DB_NAME : null;
    if ($schema) {
        $esc = mysqli_real_escape_string($conn, $schema);
        $resCols = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$esc."' AND TABLE_NAME='ecobank_form_submissions'");
        if ($resCols) {
            while ($r = mysqli_fetch_assoc($resCols)) {
                $existing[] = $r['COLUMN_NAME'];
            }
            mysqli_free_result($resCols);
        }
    }
    foreach ($needed as $col => $definition) {
        if (!in_array($col, $existing)) {
            $alterSql = "ALTER TABLE ecobank_form_submissions ADD COLUMN `".$col."` " . $definition;
            if (!mysqli_query($conn, $alterSql)) {
                debug_log_local('ALTER TABLE add '.$col.' failed: '.mysqli_error($conn).' | sql: '.$alterSql);
            } else {
                debug_log_local('ALTER TABLE added column '.$col);
                // add to existing array so subsequent logic sees it
                $existing[] = $col;
            }
        } else {
            debug_log_local('Column '.$col.' already exists, skipping');
        }
    }
}

// Extract common indexable values from payload (try several possible keys)
function pick_first_nonempty($arr, $keys){
    foreach($keys as $k){
        if(is_array($arr) && isset($arr[$k]) && $arr[$k] !== '') return $arr[$k];
    }
    return null;
}

// Normalize checkbox/multi-value fields: remove bare "on" values and empty strings,
// flatten arrays and prefer companion "other-..." text inputs when present.
function normalize_checkbox_value($val, $payload = [], $otherKeys = []){
    $vals = [];
    if (is_array($val)) {
        array_walk_recursive($val, function($v) use (&$vals){ $vals[] = $v; });
    } else {
        $vals[] = $val;
    }
    $clean = [];
    foreach($vals as $v){
        if ($v === null) continue;
        $v = trim((string)$v);
        if ($v === '' || strtolower($v) === 'on') continue;
        $clean[] = $v;
    }
    $clean = array_values(array_unique($clean));
    if (!empty($clean)) return implode(' | ', $clean);
    // fallback: check companion "other-" fields in payload
    foreach($otherKeys as $k){
        if (isset($payload[$k]) && $payload[$k] !== ''){
            if (is_array($payload[$k])){
                $tmp = [];
                array_walk_recursive($payload[$k], function($v) use (&$tmp){ $v = trim((string)$v); if ($v !== '' && strtolower($v) !== 'on') $tmp[] = $v; });
                $tmp = array_values(array_unique($tmp));
                if (!empty($tmp)) return implode(' | ', $tmp);
            } else {
                $v = trim((string)$payload[$k]);
                if ($v !== '' && strtolower($v) !== 'on') return $v;
            }
        }
    }
    return null;
}

// Normalize various date formats to YYYY-MM-DD for MySQL DATE columns
function normalize_date_for_mysql($s){
    if ($s === null || $s === '') return null;
    $s = trim($s);
    // already ISO
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
    $formats = ['d/m/Y','d-m-Y','d/m/y','d-m-y','Y/m/d','Y-m-d','m/d/Y','m-d-Y'];
    foreach($formats as $fmt){
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt && $dt->format($fmt) === $s) return $dt->format('Y-m-d');
    }
    // try strtotime fallback
    $ts = strtotime($s);
    if ($ts !== false) return date('Y-m-d', $ts);
    // give up and return null to avoid inserting malformed dates
    return null;
}

$account_number = pick_first_nonempty($data, ['bank-account-number', 'bank_account_number', 'account_number', 'account-number']);
// Names
$first_name = pick_first_nonempty($data, ['first-name','first_name','prenom','prenom1','firstname']);
$middle_name = pick_first_nonempty($data, ['middle-name','middle_name','prenom2']);
$last_name = pick_first_nonempty($data, ['last-name','last_name','noms','name','full_name']);
$customer_name = pick_first_nonempty($data, ['customer_name','full_name','noms','name']);
if (!$customer_name){
    $parts = array_filter([$first_name, $middle_name, $last_name]);
    if(!empty($parts)) $customer_name = implode(' ', $parts);
}
// Dates and documents
$date_of_birth = pick_first_nonempty($data, ['date_of_birth','date-of-birth','dob']);
$id_number = pick_first_nonempty($data, ['id_num','document-number','idnumber']);
$id_issue_date = pick_first_nonempty($data, ['document-issue-date','issue_date','issue-date']);
$id_expiry_date = pick_first_nonempty($data, ['document-expiry-date','expiry_date','expiry-date']);
// Contact/employment
$telephone = pick_first_nonempty($data, ['telephone','telephone1','mobile','mobile1','phone']);
$telephone2 = pick_first_nonempty($data, ['telephone2','mobile2']);
$mobile = pick_first_nonempty($data, ['mobile','mobile1','mobile2','telephone','telephone1','telephone2','phone']);
$email = pick_first_nonempty($data, ['email', 'courriel', 'email_address']);
$nationality = pick_first_nonempty($data, ['nationality','nationalite']);
$residence_country = pick_first_nonempty($data, ['residence-country','residence_country','pays','country']);
$employer_name = pick_first_nonempty($data, ['employer-name','employer','employeur']);
$branch_code = pick_first_nonempty($data, ['branch-code','branch_code']);
$account_officer = pick_first_nonempty($data, ['account-officer','account_officer']);
$account_handler = pick_first_nonempty($data, ['account-handler','account_handler']);
$approved_by = pick_first_nonempty($data, ['approved-by','approved_by']);
$approved_signature = pick_first_nonempty($data, ['approved-signature','approved_signature']);
$account_purpose = pick_first_nonempty($data, ['account-purpose','account_purpose']);
$currency = pick_first_nonempty($data, ['currency','devise_pref','other-currency']);

// Account type: checkbox values (type_compte[]), normalize to readable string
// Account type: prefer 'account_type' payload key (from form) and normalize
// Accept both bracketed names that come from form serialization (e.g. 'type_compte[]')
$account_type = pick_first_nonempty($data, ['account_type','type_compte','type-compte','account_type[]','type_compte[]','type-compte[]']);
$account_type = normalize_checkbox_value($account_type, $data);

// Date d'ouverture (optional) - expect YYYY-MM-DD or other parseable formats
$date_open = pick_first_nonempty($data, ['date_open','date-of-opening','date_ouverture']);
$date_open = normalize_date_for_mysql($date_open);
// services can be multiple - keep as json/text
$services = null;
if (isset($data['services'])) {
    if (is_array($data['services'])) {
        $tmp = [];
        array_walk_recursive($data['services'], function($v) use (&$tmp){ $v = trim((string)$v); if ($v !== '' && strtolower($v) !== 'on') $tmp[] = $v; });
        $tmp = array_values(array_unique($tmp));
        if (empty($tmp)) $services = null;
        elseif (count($tmp) === 1) $services = $tmp[0];
        else $services = json_encode($tmp, JSON_UNESCAPED_UNICODE);
    } else {
        $services = $data['services'];
    }
}

// Normalize checkbox/multi-select style fields so we don't store 'on' or raw arrays
$currency = normalize_checkbox_value($currency, $data, ['other-currency','other_currency']);
// employment/business
$employment_type = pick_first_nonempty($data, ['employment-type','employment_type']);
$salaried_terms = pick_first_nonempty($data, ['salaried-terms','salaried_terms']);
$salaried_occupation = pick_first_nonempty($data, ['salaried-occupation','salaried_occupation']);
$salaried_industry = pick_first_nonempty($data, ['salaried-industry','salaried_industry']);
$gross_income_range = pick_first_nonempty($data, ['gross-income-range','gross_income_range']);
$business_name = pick_first_nonempty($data, ['business-name','business_name']);
$business_registration = pick_first_nonempty($data, ['business-registration','business_registration']);
$business_kra_pin = pick_first_nonempty($data, ['business-kra-pin','business_kra_pin']);
$business_nature = pick_first_nonempty($data, ['business-nature','business_nature']);
$turnover_range = pick_first_nonempty($data, ['turnover-range','turnover_range']);
$institution_name = pick_first_nonempty($data, ['institution-name','institution_name']);

// Normalize multi-checkbox fields to readable strings (prefer companion other-* fields)
$account_purpose = normalize_checkbox_value($account_purpose, $data, ['other-purpose','other_purpose']);
$employment_type = normalize_checkbox_value($employment_type, $data);
$salaried_terms = normalize_checkbox_value($salaried_terms, $data);
$gross_income_range = normalize_checkbox_value($gross_income_range, $data);
$turnover_range = normalize_checkbox_value($turnover_range, $data);

// Clean the original payload so the stored JSON snapshot uses normalized values (no duplicated 'on' entries)
function normalize_payload_checkboxes(&$payload){
    if (!is_array($payload)) return;
    $map = [
        ['keys'=>['currency','devise_pref','other-currency','currency[]'],'other'=>['other-currency','other_currency']],
        ['keys'=>['account-purpose','account_purpose','account-purpose[]'],'other'=>['other-purpose','other_purpose']],
        ['keys'=>['salaried-terms','salaried_terms','salaried-terms[]'],'other'=>[]],
        ['keys'=>['gross-income-range','gross_income_range','gross-income-range[]'],'other'=>[]],
        ['keys'=>['turnover-range','turnover_range','turnover-range[]'],'other'=>[]],
        ['keys'=>['employment-type','employment_type','employment-type[]'],'other'=>[]],
        ['keys'=>['services','services[]'],'other'=>['other-service']],
        ['keys'=>['account_type','type_compte','type-compte','account_type[]','type_compte[]'],'other'=>[]]
    ];
    foreach($map as $m){
        $val = null;
        foreach($m['keys'] as $k) if(isset($payload[$k])) { $val = $payload[$k]; break; }
        if ($val === null) continue;
        $norm = normalize_checkbox_value($val, $payload, $m['other']);
        // write normalized back to primary key if exists, else to first key
        $primary = $m['keys'][0];
        if ($norm === null) { unset($payload[$primary]); }
        else $payload[$primary] = $norm;
        // remove duplicates / alternate keys to reduce clutter
        foreach($m['keys'] as $k) if($k !== $primary && isset($payload[$k])) unset($payload[$k]);
    }
}
normalize_payload_checkboxes($data);
// If normalize_payload_checkboxes added/normalized the account_type into the payload, ensure the server-side
// $account_type variable reflects that change so it will be inserted into the account_type column.
if (empty($account_type)) {
    $account_type = pick_first_nonempty($data, ['account_type','account_type[]','type_compte','type_compte[]','type-compte','type-compte[]']);
    $account_type = normalize_checkbox_value($account_type, $data);
}
$student_id = pick_first_nonempty($data, ['student-id','student_id']);
// personal / other
$father_name = pick_first_nonempty($data, ['father-name','father_name']);
$father_name2 = pick_first_nonempty($data, ['father-name2','father_name2']);
$mother_name = pick_first_nonempty($data, ['mother-name','mother_name']);
$mother_name2 = pick_first_nonempty($data, ['mother-name2','mother_name2']);
$pob = pick_first_nonempty($data, ['pob','place-of-birth','place_of_birth']);
$client_fingerprint = pick_first_nonempty($data, ['client-fingerprint','client_fingerprint']);
$magistrat_name = pick_first_nonempty($data, ['magistrat-name','magistrat_name']);
$agent_name = pick_first_nonempty($data, ['agent-name','agent_name']);
$emergency_contact_name = pick_first_nonempty($data, ['emergency-contact-name','emergency_contact_name']);
// address & misc
$address = pick_first_nonempty($data, ['address','adr_rue','adresse','residential_address']);
$city = pick_first_nonempty($data, ['city','ville']);
$postal_code = pick_first_nonempty($data, ['postal-code','postal_code','code_postal']);
$bp = pick_first_nonempty($data, ['bp']);
// bank account number explicit
$bank_account_number = pick_first_nonempty($data, ['bank-account-number','bank_account_number']);

// Prepare insert
$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
if ($jsonData === false) {
    $encErr = json_last_error_msg();
    error_log('[save_ecobank_form] json_encode failed: ' . $encErr . ' | data snippet: ' . substr(print_r($data, true), 0, 2000));
    // attempt to fallback to stringified representation
    $jsonData = json_encode(['_encoding_error' => $encErr, 'raw' => substr(print_r($data, true), 0, 10000)], JSON_UNESCAPED_UNICODE);
}
debug_log_local('submissions_ok: ' . ($submissions_ok ? 'yes' : 'no'));
$insertId = null;
if ($submissions_ok) {
    debug_log_local('Preparing insert into ecobank_form_submissions');
    try {
        // detect whether helper columns exist in the table (some DBs have older schema)
        $hasAccountColumn = false;
        if (defined('DB_NAME')) {
            $schema = mysqli_real_escape_string($conn, DB_NAME);
            $colCheck = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$schema."' AND TABLE_NAME='ecobank_form_submissions' AND COLUMN_NAME='account_number' LIMIT 1");
            if ($colCheck && mysqli_num_rows($colCheck) > 0) $hasAccountColumn = true;
            if ($colCheck) mysqli_free_result($colCheck);
        }

        if ($hasAccountColumn) {
            // Build associative array of intended columns and values
            $toInsert = [
                'customer_id' => $customer_id,
                'account_number' => $account_number,
                'bank_account_number' => $bank_account_number,
                'customer_name' => $customer_name,
                'title' => $data['title'] ?? null,
                'gender' => $data['gender'] ?? null,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'father_name' => $father_name,
                'mother_name' => $mother_name,
                'date_of_birth' => $date_of_birth,
                'pob' => $pob,
                'id_type' => $data['id_type'] ?? null,
                'id_number' => $id_number,
                'id_issue_date' => $id_issue_date,
                'id_expiry_date' => $id_expiry_date,
                'nationality' => $nationality,
                'residence_country' => $residence_country,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postal_code,
                'bp' => $bp,
                'telephone' => $telephone,
                'telephone2' => $telephone2,
                'mobile' => $mobile,
                'email' => $email,
                'employer_name' => $employer_name,
                'employment_type' => $employment_type,
                'salaried_terms' => $salaried_terms,
                'salaried_occupation' => $salaried_occupation,
                'salaried_industry' => $salaried_industry,
                'gross_income_range' => $gross_income_range,
                'business_name' => $business_name,
                'business_registration' => $business_registration,
                'business_kra_pin' => $business_kra_pin,
                'business_nature' => $business_nature,
                'turnover_range' => $turnover_range,
                'institution_name' => $institution_name,
                'student_id' => $student_id,
                'client_fingerprint' => $client_fingerprint,
                'magistrat_name' => $magistrat_name,
                'agent_name' => $agent_name,
                'emergency_contact_name' => $emergency_contact_name,
                'account_officer' => $account_officer,
                'account_handler' => $account_handler,
                'branch_code' => $branch_code,
                'approved_by' => $approved_by,
                'approved_signature' => $approved_signature,
                'account_purpose' => $account_purpose,
                'currency' => $currency,
                'services' => $services,
                'tax_registration' => $data['tax-registration'] ?? null,
                'account_type' => $account_type,
                'date_open' => $date_open,
                'emp_id' => isset($_SESSION['emp_id']) ? $_SESSION['emp_id'] : null,
                'data' => $jsonData
            ];

            // Only insert columns that actually exist in the table
            $cols = [];
            $placeholders = [];
            $values = [];
            foreach ($toInsert as $col => $val) {
                if (in_array($col, $existing)) {
                    // skip null/empty values to avoid inserting meaningless blanks and to allow default NULLs
                    if ($val === null || $val === '') continue;
                    $cols[] = "`$col`";
                    $placeholders[] = '?';
                    // normalize dates: convert dd/mm/yyyy or dd/mm/yy to YYYY-MM-DD if looks like it
                    if (in_array($col, ['date_of_birth','id_issue_date','id_expiry_date'])) {
                        $val = normalize_date_for_mysql($val);
                        if ($val === null) continue; // skip malformed/unparseable dates
                    }
                    $values[] = $val;
                }
            }

            if (empty($cols)) {
                debug_log_local('No columns available to insert into ecobank_form_submissions');
                $submissions_ok = false;
            } else {
                $sql = "INSERT INTO ecobank_form_submissions (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    debug_log_local('Prepare failed (ecobank_form_submissions): ' . mysqli_error($conn) . ' | sql: ' . $sql);
                    $submissions_ok = false;
                } else {
                    // Build types string (all strings or NULLs)
                    $types = str_repeat('s', count($values));
                    $bind_params = array_merge([$types], $values);
                    // call_user_func_array requires references
                    $tmp = [];
                    foreach ($bind_params as $k => $v) $tmp[$k] = &$bind_params[$k];
                    call_user_func_array([$stmt, 'bind_param'], $tmp);
                    debug_log_local('Before execute ecobank_form_submissions dynamic insert');
                    $res = mysqli_stmt_execute($stmt);
                    debug_log_local('After execute ecobank_form_submissions, result: ' . ($res ? 'ok' : 'fail'));
                    if (!$res) {
                        $err = mysqli_stmt_error($stmt);
                        debug_log_local('Execute failed (ecobank_form_submissions): ' . $err . ' | mysqli_error: ' . mysqli_error($conn) . ' | sql: ' . $sql);
                        $submissions_ok = false;
                    } else {
                        $insertId = mysqli_insert_id($conn);
                        debug_log_local('Inserted into ecobank_form_submissions id=' . $insertId . ' cols: ' . implode(',', $cols));
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        } else {
            // fallback: only insert into (customer_id, data) for older schema
            $sql = "INSERT INTO ecobank_form_submissions (customer_id, data) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                debug_log_local('Prepare failed (fallback ecobank_form_submissions): ' . mysqli_error($conn));
                $submissions_ok = false;
            } else {
                mysqli_stmt_bind_param($stmt, 'ss', $customer_id, $jsonData);
                $res = mysqli_stmt_execute($stmt);
                debug_log_local('Fallback insert result: ' . ($res ? 'ok' : 'fail'));
                if ($res) {
                    $insertId = mysqli_insert_id($conn);
                    debug_log_local('Inserted (fallback) into ecobank_form_submissions id=' . $insertId);
                } else {
                    debug_log_local('Execute failed (fallback): ' . mysqli_stmt_error($stmt) . ' | mysqli_error: ' . mysqli_error($conn));
                    $submissions_ok = false;
                }
                mysqli_stmt_close($stmt);
            }
        }
    } catch (mysqli_sql_exception $e) {
        debug_log_local('mysqli_sql_exception during submissions insert: ' . $e->getMessage());
        $submissions_ok = false;
    } catch (Throwable $t) {
        debug_log_local('Throwable during submissions insert: ' . $t->getMessage());
        $submissions_ok = false;
    }
} else {
    debug_log_local('Skipping ecobank_form_submissions insert because table or previous error prevents it');
}

// Vérifier si une demande de chéquier a été effectuée et envoyer les notifications
if ($insertId && $submissions_ok) {
    // Vérifier s'il y a une demande de chéquier dans les données
    $chequier_requested = false;
    $chequier_fields = array('25 Feuilles', '50 Feuilles', '100 Feuilles');
    
    foreach ($chequier_fields as $field) {
        if (isset($data[$field]) && ($data[$field] === 'on' || $data[$field] === true)) {
            $chequier_requested = true;
            break;
        }
    }
    
    if ($chequier_requested) {
        debug_log_local('Demande de chéquier détectée, création des notifications');
        
        // Créer la table notifications si elle n'existe pas
        $create_notif_table = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id INT,
            message TEXT,
            type VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_read BOOLEAN DEFAULT 0,
            submission_id INT,
            INDEX (emp_id),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($conn, $create_notif_table);
        
        // Récupérer les infos du CSO et du CI
        $cso_emp_id = isset($_SESSION['emp_id']) ? $_SESSION['emp_id'] : null;
        $customer_name = $customer_name ?? 'N/A';
        $account_number = $account_number ?? 'N/A';
        
        if ($cso_emp_id) {
            // 1. Notification au CSO (CI) : "Demande de chéquier effectué"
            $msg_ci = "Demande de chéquier effectuée pour le compte " . $account_number . " - Client: " . $customer_name;
            $notif_ci = "INSERT INTO notifications (emp_id, message, type, submission_id, created_at) 
                        VALUES (" . $cso_emp_id . ", '" . mysqli_real_escape_string($conn, $msg_ci) . "', 'chequier_request', " . $insertId . ", NOW())";
            mysqli_query($conn, $notif_ci);
            debug_log_local('Notification CI créée pour emp_id=' . $cso_emp_id);
            
            // 2. Notification au client (si email fourni) - "Demande de chéquier envoyer"
            if (!empty($email)) {
                // Note: Les notifications email doivent être traitées par un système d'email séparé
                // Pour l'instant, on les stocke aussi dans la table
                $msg_client = "Votre demande de chéquier a été enregistrée et sera traitée sous peu.";
                
                // Si on a un moyen de contacter le client (à implémenter)
                // sendEmailNotification($email, $customer_name, $msg_client);
                
                debug_log_local('Notification client à envoyer à: ' . $email);
            }
        }
    }
}

$response = ['status' => 'ok', 'message' => 'Données enregistrées', 'submission_stored' => $submissions_ok];
if($insertId) $response['submission_id'] = $insertId;

debug_log_local('Response: ' . json_encode($response));
echo json_encode($response);
?>