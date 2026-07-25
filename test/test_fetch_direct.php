<?php
/**
 * Direct Test of fetch_account_flexcube.php
 * 
 * This tests the endpoint directly
 */

header('Content-Type: text/html; charset=utf-8');

$test_account = isset($_GET['account']) ? trim($_GET['account']) : '0000000001';

echo "<h1>Test fetch_account_flexcube.php</h1>";
echo "<form method='get'>";
echo "<input type='text' name='account' value='" . htmlentities($test_account) . "' placeholder='Account number'>";
echo " <button type='submit'>Test</button>";
echo "</form>";

if ($test_account) {
    echo "<h2>Testing account: $test_account</h2>";
    
    // Use curl to test the endpoint
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/account_opening/cso/fetch_account_flexcube.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'account=' . urlencode($test_account));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    echo "<h3>Response (HTTP $http_code):</h3>";
    echo "<pre>";
    
    if ($curl_error) {
        echo "CURL Error: $curl_error\n";
    } else {
        // Split headers from body
        $parts = explode("\r\n\r\n", $response, 2);
        $headers = $parts[0];
        $body = $parts[1] ?? '';
        
        echo "=== HEADERS ===\n";
        echo $headers . "\n\n";
        
        echo "=== BODY ===\n";
        if (!empty($body)) {
            // Try to pretty-print JSON
            $json = json_decode($body, true);
            if ($json) {
                echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            } else {
                echo $body . "\n";
            }
        }
    }
    
    echo "</pre>";
    
    // Also check the error log
    echo "<h3>Recent Errors:</h3>";
    echo "<pre>";
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        $lines = array_slice(file($error_log), -20);
        foreach ($lines as $line) {
            if (strpos($line, 'fetch_account_flexcube') !== false || strpos($line, 'Flexcube') !== false) {
                echo $line;
            }
        }
    }
    echo "</pre>";
}
