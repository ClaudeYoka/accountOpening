<?php
/**
 * Direct OCI8 Test
 * Test la requête OCI8 directement sans abstraction
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Test Direct OCI8 - Flexcube Query</h1>";
echo "<pre>";

// Test account number
$test_account = isset($_GET['account']) ? trim($_GET['account']) : '1234567890';

echo "=== Test Account: $test_account ===\n\n";

// Step 1: Check if OCI8 is loaded
echo "1. Vérifier OCI8 extension:\n";
if (extension_loaded('oci8')) {
    echo "   ✓ OCI8 est chargé\n";
    echo "   Version: " . phpversion('oci8') . "\n";
} else {
    echo "   ✗ OCI8 N'EST PAS CHARGÉ\n";
    echo "   Extensions disponibles: " . implode(', ', get_loaded_extensions()) . "\n";
    exit;
}

// Step 2: Try to connect
echo "\n2. Tester la connexion OCI8:\n";
$hosts = ['ADC-CEMACFC-SCAN', 'LDC-CEMACFC-SCAN'];
$port = '1521';
$service = 'SRVFCUBSCS2';
$username = 'cyoka';
$password = 'Welcometo@2026';

$conn = null;
foreach ($hosts as $host) {
    $conn_string = "//$host:$port/$service";
    echo "   Essai: $conn_string\n";
    $conn = @oci_connect($username, $password, $conn_string);
    if ($conn) {
        echo "   ✓ Connecté via $host\n";
        break;
    } else {
        $err = oci_error();
        echo "   ✗ Erreur: " . ($err['message'] ?? 'Unknown') . "\n";
    }
}

if (!$conn) {
    echo "\n   ✗ Impossible de se connecter à Oracle\n";
    exit;
}

// Step 3: Execute the query
echo "\n3. Exécuter la requête SQL:\n";

$sql = "SELECT
    a.branch_code,
    a.cust_ac_no AS account_number,
    a.ac_desc AS account_name,
    cp.FIRST_NAME AS first_name,
    cp.LAST_NAME AS last_name,
    cp.MIDDLE_NAME AS middle_name,
    cp.telephone,
    TRIM(NVL(cu.address_line1, '') || ' ' || NVL(cu.address_line2, '') || ' ' || NVL(cu.address_line3, '') || ' ' || NVL(cu.address_line4, '')) AS customer_address
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

echo "   SQL préparée...\n";
$stid = oci_parse($conn, $sql);

if (!$stid) {
    $err = oci_error($conn);
    echo "   ✗ Erreur de parsing: " . $err['message'] . "\n";
    oci_close($conn);
    exit;
}

echo "   ✓ SQL parsée avec succès\n";

echo "   Binden le paramètre: $test_account\n";
oci_bind_by_name($stid, ':account_no', $test_account);
echo "   ✓ Paramètre bindé\n";

echo "   Exécuter la requête...\n";
if (!oci_execute($stid)) {
    $err = oci_error($stid);
    echo "   ✗ Erreur d'exécution: " . $err['message'] . "\n";
    oci_free_statement($stid);
    oci_close($conn);
    exit;
}

echo "   ✓ Requête exécutée\n";

// Step 4: Fetch results
echo "\n4. Récupérer les résultats:\n";

$row_count = 0;
while ($row = oci_fetch_assoc($stid)) {
    $row_count++;
    echo "\n   Ligne $row_count:\n";
    echo "   " . str_repeat("-", 60) . "\n";
    
    foreach ($row as $key => $value) {
        $key_lower = strtolower($key);
        $display_val = (strlen($value) > 50) ? substr($value, 0, 50) . '...' : $value;
        echo "   $key_lower = " . ($value === null ? 'NULL' : "'" . $display_val . "'") . "\n";
    }
    
    echo "   " . str_repeat("-", 60) . "\n";
}

if ($row_count === 0) {
    echo "   ⚠ Aucune ligne trouvée pour le compte: $test_account\n";
} else {
    echo "   ✓ $row_count ligne(s) récupérée(s)\n";
}

// Cleanup
oci_free_statement($stid);
oci_close($conn);

echo "\n=== Test Terminé ===\n";
echo "</pre>";
echo "<p><a href='?account='>Retour au test</a></p>";
