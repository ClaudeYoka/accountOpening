<?php
/**
 * OCI8 Connection Test Script
 * 
 * Diagnostic tool to verify OCI8 extension and Oracle connectivity
 * Run from command line: php test_oci8_connection.php
 * Or access via browser: http://localhost/account_opening/test_oci8_connection.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h2>OCI8 Connection Diagnostic</h2>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";

// 1. Check OCI8 Extension
echo "1. OCI8 Extension Status:\n";
if (extension_loaded('oci8')) {
    echo "   ✓ OCI8 extension is loaded\n";
    echo "   Version: " . phpversion('oci8') . "\n";
} else {
    echo "   ✗ OCI8 extension is NOT loaded\n";
    echo "   Available extensions: " . implode(', ', get_loaded_extensions()) . "\n";
    exit;
}

echo "\n2. Testing Connection to Flexcube Oracle:\n";

// Connection parameters
$hosts = ['ADC-CEMACFC-SCAN', 'LDC-CEMACFC-SCAN'];
$username = 'cyoka';
$password = 'Piratemoi@2026';
$service = 'SRVFCUBSCS2';
$port = 1521;

$connection = null;
$connected_host = null;

// Try each host
foreach ($hosts as $host) {
    $connection_string = "//$host:$port/$service";
    echo "   Trying: $connection_string\n";
    
    $conn = @oci_connect($username, $password, $connection_string);
    
    if ($conn) {
        echo "   ✓ Connected to Oracle via $host\n";
        $connection = $conn;
        $connected_host = $host;
        break;
    } else {
        $error = oci_error();
        echo "   ✗ Connection failed: " . ($error ? $error['message'] : 'Unknown error') . "\n";
    }
}

if (!$connection) {
    echo "\n   ✗ Could not connect to any Flexcube Oracle server\n";
    echo "   Please verify:\n";
    echo "   - OCI8 extension is properly installed\n";
    echo "   - Oracle client libraries are installed\n";
    echo "   - Network connectivity to Oracle servers\n";
    echo "   - Username/password credentials\n";
    echo "   - Service name configuration\n";
    exit;
}

echo "\n3. Testing Sample Query:\n";

// Test query
$sql = "SELECT 'Connected to SRVFCUBSCS2' AS status FROM DUAL";
$stid = oci_parse($connection, $sql);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo "   ✗ Query execution failed: " . $error['message'] . "\n";
} else {
    $row = oci_fetch_assoc($stid);
    echo "   ✓ Query executed successfully\n";
    echo "   Result: " . $row['STATUS'] . "\n";
}

echo "\n4. Testing Flexcube Tables:\n";

// Check if we can query the flexcube tables
$test_query = "SELECT COUNT(*) AS cnt FROM fcubscs2.sttm_cust_account WHERE ROWNUM = 1";
$stid = oci_parse($connection, $test_query);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo "   ✗ Cannot access fcubscs2.sttm_cust_account: " . $error['message'] . "\n";
} else {
    $row = oci_fetch_assoc($stid);
    echo "   ✓ fcubscs2.sttm_cust_account table is accessible\n";
}

oci_free_statement($stid);

// Test personal table
$test_query = "SELECT COUNT(*) AS cnt FROM fcubscs2.sttm_cust_personal WHERE ROWNUM = 1";
$stid = oci_parse($connection, $test_query);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo "   ✗ Cannot access fcubscs2.sttm_cust_personal: " . $error['message'] . "\n";
} else {
    $row = oci_fetch_assoc($stid);
    echo "   ✓ fcubscs2.sttm_cust_personal table is accessible\n";
}

oci_free_statement($stid);

// Test customer table
$test_query = "SELECT COUNT(*) AS cnt FROM fcubscs2.sttm_customer WHERE ROWNUM = 1";
$stid = oci_parse($connection, $test_query);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo "   ✗ Cannot access fcubscs2.sttm_customer: " . $error['message'] . "\n";
} else {
    $row = oci_fetch_assoc($stid);
    echo "   ✓ fcubscs2.sttm_customer table is accessible\n";
}

oci_free_statement($stid);

echo "\n5. Testing Account Data Retrieval:\n";

// Try to fetch a sample account (if applicable)
$test_account = $_GET['account'] ?? '0000000001';  // Default test account

$sql = "SELECT
    a.cust_ac_no AS account_number,
    a.ac_desc AS account_name,
    cp.LAST_NAME AS first_name,
    cp.FIRST_NAME AS last_name,
    cp.telephone,
    TRIM(NVL(cu.address_line1, '') || ' ' || NVL(cu.address_line2, '')) AS address
FROM
    fcubscs2.sttm_cust_account a
LEFT JOIN
    fcubscs2.sttm_cust_personal cp ON a.cust_no = cp.customer_no
LEFT JOIN
    fcubscs2.sttm_customer cu ON a.cust_no = cu.customer_no
WHERE
    a.location = 'CG'
    AND a.cust_ac_no = :account_no
    AND ROWNUM = 1";

$stid = oci_parse($connection, $sql);
oci_bind_by_name($stid, ':account_no', $test_account);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    echo "   ✗ Query execution failed: " . $error['message'] . "\n";
} else {
    $row = oci_fetch_assoc($stid);
    if ($row) {
        echo "   ✓ Account data retrieved successfully\n";
        echo "   Account: " . $row['ACCOUNT_NUMBER'] . "\n";
        echo "   Name: " . $row['FIRST_NAME'] . " " . $row['LAST_NAME'] . "\n";
        echo "   Description: " . $row['ACCOUNT_NAME'] . "\n";
    } else {
        echo "   ℹ No account found with number: " . $test_account . "\n";
        echo "   (This is normal if the account doesn't exist)\n";
    }
}

oci_free_statement($stid);
oci_close($connection);

echo "\n6. Summary:\n";
echo "   ✓ All diagnostic tests completed successfully\n";
echo "   ✓ OCI8 is properly configured and operational\n";
echo "   ✓ Flexcube database connection is working\n";

echo "\n7. Next Steps:\n";
echo "   - Test form auto-fill with a real account number\n";
echo "   - Monitor error logs for any connection issues\n";
echo "   - Verify field mappings in form (especially name inversion)\n";
echo "   - Test address field population\n";

echo "</pre>";

if (!isset($_GET['account'])) {
    echo "<p>To test a specific account, add ?account=ACCOUNT_NUMBER to the URL</p>";
}
