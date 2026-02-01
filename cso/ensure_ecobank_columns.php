<?php
// ensure_ecobank_columns.php
// Ensure ecobank_form_submissions contains all expected columns (safe: only adds missing columns)

include('../includes/config.php');

function debug_log_local($msg){
    $path = __DIR__ . '/save_ecobank_form_debug.log';
    $line = '['.date('c').'] ' . $msg . PHP_EOL;
    error_log('[ensure_ecobank_columns] ' . $msg);
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

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
    'date_open' => 'DATE DEFAULT NULL',
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
    'account_type' => 'VARCHAR(128) DEFAULT NULL',
    'type_compte' => 'VARCHAR(255) DEFAULT NULL',
    'date_open' => 'DATE DEFAULT NULL',
    'approved_by' => 'VARCHAR(128) DEFAULT NULL',
    'approved_signature' => 'TEXT DEFAULT NULL',
    'account_purpose' => 'VARCHAR(128) DEFAULT NULL',
    'currency' => 'VARCHAR(64) DEFAULT NULL',
    'services' => 'TEXT DEFAULT NULL',
    'tax_registration' => 'VARCHAR(64) DEFAULT NULL'
];

// fetch existing columns
$existing = [];
$schema = defined('DB_NAME') ? DB_NAME : null;
if ($schema) {
    $esc = mysqli_real_escape_string($conn, $schema);
    $resCols = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$esc."' AND TABLE_NAME='ecobank_form_submissions'");
    if ($resCols) {
        while ($r = mysqli_fetch_assoc($resCols)) $existing[] = $r['COLUMN_NAME'];
        mysqli_free_result($resCols);
    }
}

foreach ($needed as $col => $definition) {
    if (!in_array($col, $existing)) {
        $alterSql = "ALTER TABLE ecobank_form_submissions ADD COLUMN `".$col."` " . $definition;
        if (!mysqli_query($conn, $alterSql)) {
            debug_log_local('ALTER TABLE add '.$col.' failed: '.mysqli_error($conn).' | sql: '.$alterSql);
            echo 'Failed to add '.$col.': '.mysqli_error($conn)."\n";
        } else {
            debug_log_local('ALTER TABLE added column '.$col);
            echo 'Added '.$col."\n";
            $existing[] = $col;
        }
    } else {
        echo 'Exists: '.$col."\n";
    }
}

// optionally add indexes on common search columns
$indexes = [
    'account_number','customer_name','mobile','email'
];
foreach ($indexes as $idx) {
    if (in_array($idx, $existing)) {
        $idxName = 'idx_ecobank_'.$idx;
        $q = mysqli_query($conn, "SHOW INDEX FROM ecobank_form_submissions WHERE Key_name='".mysqli_real_escape_string($conn,$idxName)."'");
        if ($q && mysqli_num_rows($q) === 0) {
            $sqlIdx = "ALTER TABLE ecobank_form_submissions ADD INDEX `".$idxName."` (`".$idx."`)";
            if (mysqli_query($conn, $sqlIdx)) echo "Added index on $idx\n";
            else echo "Failed adding index on $idx: ".mysqli_error($conn)."\n";
        }
    }
}

echo "Done.\n";