<?php
// migrate_ecobank_form_columns.php
// Simple one-off script to backfill dedicated columns from JSON `data` in ecobank_form_submissions
// Run from CLI or via browser (recommended: CLI)

include('../includes/config.php');

function debug_log_local($msg){
    $path = __DIR__ . '/../logs/save_ecobank_form_debug.log';
    $line = '['.date('c').'] ' . $msg . PHP_EOL;
    error_log('[migrate_ecobank] ' . $msg);
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

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
function normalize_date_for_mysql($s){
    if ($s === null || $s === '') return null;
    $s = trim($s);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
    $formats = ['d/m/Y','d-m-Y','d/m/y','d-m-y','Y/m/d','Y-m-d','m/d/Y','m-d-Y'];
    foreach($formats as $fmt){
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt && $dt->format($fmt) === $s) return $dt->format('Y-m-d');
    }
    $ts = strtotime($s);
    if ($ts !== false) return date('Y-m-d', $ts);
    return null;
}

// list of columns to populate
$columns = [
    'account_number','bank_account_number','customer_id','customer_name','title','gender','first_name','middle_name','last_name','father_name','mother_name','date_of_birth','pob','id_type','id_number','id_issue_date','id_expiry_date','nationality','residence_country','address','city','postal_code','bp','telephone','telephone2','mobile','email','employer_name','employment_type','salaried_terms','salaried_occupation','salaried_industry','gross_income_range','business_name','business_registration','business_kra_pin','business_nature','turnover_range','institution_name','student_id','client_fingerprint','magistrat_name','agent_name','emergency_contact_name','account_officer','account_handler','branch_code','approved_by','approved_signature','account_purpose','currency','services','tax_registration'
];

// fetch existing columns for the table so we only update those that exist
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

$limit = 500; // process in batches
$offset = 0;
$totalUpdated = 0;

do {
    $q = mysqli_query($conn, "SELECT id, data FROM ecobank_form_submissions LIMIT $limit OFFSET $offset");
    if (!$q) { debug_log_local('Query failed: ' . mysqli_error($conn)); break; }
    $rows = [];
    while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    mysqli_free_result($q);
    if (empty($rows)) break;

    foreach ($rows as $row) {
        $id = $row['id'];
        $data = json_decode($row['data'], true);
        if (!is_array($data)) continue;
        $toUpdate = [];
        // extract same keys as in save_ecobank_form
        $account_number = pick_first_nonempty($data, ['bank-account-number', 'bank_account_number', 'account_number', 'account-number', 'customer_id']);
        $first_name = pick_first_nonempty($data, ['first-name','first_name','prenom','prenom1','firstname']);
        $middle_name = pick_first_nonempty($data, ['middle-name','middle_name','prenom2']);
        $last_name = pick_first_nonempty($data, ['last-name','last_name','noms','name','full_name']);
        $pob = pick_first_nonempty($data, ['pob','place_of_birth','place-of-birth']);
        $father_name = pick_first_nonempty($data, ['father-name','father_name','father']);
        $mother_name = pick_first_nonempty($data, ['mother-name','mother_name','mother']);
        $customer_name = pick_first_nonempty($data, ['customer_name','full_name','noms','name']);
        if (!$customer_name){ $parts = array_filter([$first_name, $middle_name, $last_name]); if(!empty($parts)) $customer_name = implode(' ', $parts); }
        $date_of_birth = pick_first_nonempty($data, ['date_of_birth','date-of-birth','dob']);
        $customer_id = pick_first_nonempty($data, ['customer-id','customer_id','customerid']);
        $title = pick_first_nonempty($data, ['title','titre']);
        $gender = pick_first_nonempty($data, ['gender','sex','genre']);
        $id_type = pick_first_nonempty($data, ['national-id','id_type','id-type','id-type']);
        $id_number = pick_first_nonempty($data, ['id_num','document-number','idnumber']);
        $id_issue_date = pick_first_nonempty($data, ['document-issue-date','issue_date','issue-date']);
        $id_expiry_date = pick_first_nonempty($data, ['document-expiry-date','expiry_date','expiry-date']);
        $telephone = pick_first_nonempty($data, ['telephone','telephone1','mobile','mobile1','phone']);
        $telephone2 = pick_first_nonempty($data, ['telephone2','mobile2']);
        $mobile = pick_first_nonempty($data, ['mobile','mobile1','mobile2','telephone','telephone1','telephone2','phone']);
        $email = pick_first_nonempty($data, ['email', 'courriel', 'email_address']);
        $nationality = pick_first_nonempty($data, ['nationality','nationalite']);
        $residence_country = pick_first_nonempty($data, ['residence-country','residence_country','pays','country']);
        $employer_name = pick_first_nonempty($data, ['employer-name','employer','employeur']);
        $employment_type = normalize_checkbox_value(pick_first_nonempty($data, ['employment-type','employment_type']), $data);
        $salaried_terms = normalize_checkbox_value(pick_first_nonempty($data, ['salaried-terms','salaried_terms']), $data);
        $salaried_occupation = pick_first_nonempty($data, ['salaried-occupation','salaried_occupation']);
        $salaried_industry = pick_first_nonempty($data, ['salaried-industry','salaried_industry']);
        $gross_income_range = normalize_checkbox_value(pick_first_nonempty($data, ['gross-income-range','gross_income_range']), $data);
        $business_name = pick_first_nonempty($data, ['business-name','business_name']);
        $business_registration = pick_first_nonempty($data, ['business-registration','business_registration']);
        $business_kra_pin = pick_first_nonempty($data, ['business-kra-pin','business_kra_pin']);
        $business_nature = pick_first_nonempty($data, ['business-nature','business_nature']);
        $turnover_range = normalize_checkbox_value(pick_first_nonempty($data, ['turnover-range','turnover_range']), $data);
        $institution_name = pick_first_nonempty($data, ['institution-name','institution_name']);
        $student_id = pick_first_nonempty($data, ['student-id','student_id']);
        $client_fingerprint = pick_first_nonempty($data, ['client-fingerprint','client_fingerprint']);
        $magistrat_name = pick_first_nonempty($data, ['magistrat-name','magistrat_name']);
        $agent_name = pick_first_nonempty($data, ['agent-name','agent_name']);
        $emergency_contact_name = pick_first_nonempty($data, ['emergency-contact-name','emergency_contact_name']);
        $branch_code = pick_first_nonempty($data, ['branch-code','branch_code']);
        $account_officer = pick_first_nonempty($data, ['account-officer','account_officer']);
        $account_handler = pick_first_nonempty($data, ['account-handler','account_handler']);
        $approved_by = pick_first_nonempty($data, ['approved-by','approved_by']);
        $approved_signature = pick_first_nonempty($data, ['approved-signature','approved_signature']);
        $account_purpose = normalize_checkbox_value(pick_first_nonempty($data, ['account-purpose','account_purpose']), $data, ['other-purpose','other_purpose']);
        $currency = normalize_checkbox_value(pick_first_nonempty($data, ['currency','devise_pref','other-currency']), $data, ['other-currency','other_currency']);
        // services: mirror save logic - flatten, dedupe, single string or JSON array
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
        $tax_registration = pick_first_nonempty($data, ['tax-registration','tax_registration']);
        $bank_account_number = pick_first_nonempty($data, ['bank-account-number','bank_account_number']);
        $address = pick_first_nonempty($data, ['address','adr_rue','adresse','residential_address']);
        $city = pick_first_nonempty($data, ['city','ville']);
        $postal_code = pick_first_nonempty($data, ['postal-code','postal_code','code_postal']);
        $bp = pick_first_nonempty($data, ['bp']);

        $candidates = compact('account_number','bank_account_number','customer_id','customer_name','title','gender','first_name','middle_name','last_name','father_name','mother_name','date_of_birth','pob','id_type','id_number','id_issue_date','id_expiry_date','nationality','residence_country','address','city','postal_code','bp','telephone','telephone2','mobile','email','employer_name','employment_type','salaried_terms','salaried_occupation','salaried_industry','gross_income_range','business_name','business_registration','business_kra_pin','business_nature','turnover_range','institution_name','student_id','client_fingerprint','magistrat_name','agent_name','emergency_contact_name','account_officer','account_handler','branch_code','approved_by','approved_signature','account_purpose','currency','services','tax_registration');

        $sets = [];
        $vals = [];
            foreach ($candidates as $k => $v) {
            // only attempt to update columns that exist in the table
            if (!in_array($k, $existing)) continue;
            if ($v === null || $v === '') continue;
            // normalize arrays: flatten and prefer non-'on' values
            if (is_array($v)) {
                $tmp = [];
                array_walk_recursive($v, function($x) use (&$tmp){ $x = trim((string)$x); if ($x !== '' && strtolower($x) !== 'on') $tmp[] = $x; });
                $tmp = array_values(array_unique($tmp));
                if (empty($tmp)) { continue; }
                if (count($tmp) === 1) $v = $tmp[0];
                else $v = implode(' | ', $tmp);
            }
            if (in_array($k, ['date_of_birth','id_issue_date','id_expiry_date'])) {
                $v = normalize_date_for_mysql($v);
                if ($v === null) continue;
            }
            $sets[] = "$k = ?";
            $vals[] = $v;
        }
        if (empty($sets)) continue;
        $sql = "UPDATE ecobank_form_submissions SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { debug_log_local('Prepare failed for id '.$id.': '.mysqli_error($conn).' | sql: '.$sql); continue; }
        $types = str_repeat('s', count($vals)) . 'i';
        $bind = array_merge([$types], $vals, [$id]);
        $tmp = [];
        foreach ($bind as $k => $v) $tmp[$k] = &$bind[$k];
        call_user_func_array([$stmt, 'bind_param'], $tmp);
        $res = mysqli_stmt_execute($stmt);
        if ($res) { $totalUpdated++; debug_log_local('Updated row id '.$id); }
        else { debug_log_local('Update failed id '.$id.': '.mysqli_stmt_error($stmt)); }
        mysqli_stmt_close($stmt);
    }

    $offset += $limit;
} while(true);

echo "Backfill completed. Updated $totalUpdated rows.\n";
debug_log_local('Backfill completed. Updated '.$totalUpdated.' rows.');
