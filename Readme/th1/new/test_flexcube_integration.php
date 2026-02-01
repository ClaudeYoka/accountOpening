<?php
/**
 * Flexcube API Integration Test Script
 * Test the complete flow of extracting and mapping UDF data
 */

// Include required files
require_once(__DIR__ . '/cso/includes/FlexcubeAPI.php');
require_once(__DIR__ . '/cso/includes/UDFDataMapper.php');

echo "=== Flexcube API Integration Test ===\n\n";

// Test 1: Create API instance
echo "Test 1: Creating FlexcubeAPI instance...\n";
try {
    $api = new FlexcubeAPI();
    echo "✓ FlexcubeAPI instance created successfully\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Test connection
echo "Test 2: Testing API connection...\n";
$test_result = $api->testConnection();
echo "Status: " . $test_result['status'] . "\n";
echo "Message: " . $test_result['message'] . "\n";
if ($test_result['status'] === 'FAIL') {
    echo "Connection details: " . json_encode($test_result['details']) . "\n";
}
echo "\n";

// Test 3: UDFDataMapper - Known Fields
echo "Test 3: Testing UDFDataMapper known fields...\n";
$known_fields = UDFDataMapper::getKnownUDFFields();
echo "Number of known UDF fields: " . count($known_fields) . "\n";
echo "Sample fields: " . implode(", ", array_slice($known_fields, 0, 5)) . "\n\n";

// Test 4: UDFDataMapper - Mapping
echo "Test 4: Testing UDF field mapping...\n";

// Sample UDF data (similar to what Flexcube returns)
$sample_udf = [
    'TITLE' => 'M',
    'FIRST_NAME' => 'Jean',
    'LAST_NAME' => 'Dupont',
    'SEX' => 'M',
    'CONTACT_ADDRESS' => '123 Rue de la Paix, Brazzaville',
    'NATIONALITY' => 'CG',
    'COUNTRY' => 'CG',
    'EMAIL' => 'jean.dupont@example.com',
    'PHONE' => '+242 06 123 4567',
    'TELEPHONE2' => '+242 07 123 4567'
];

$mapped = UDFDataMapper::mapUDFToFormFields($sample_udf);

echo "Mapping results:\n";
foreach (['first-name', 'last-name', 'email', 'telephone', 'phone', 'sex', 'nationality'] as $field) {
    if (isset($mapped[$field])) {
        echo "  ✓ $field = " . $mapped[$field] . "\n";
    } else {
        echo "  ✗ $field NOT FOUND\n";
    }
}
echo "\n";

// Test 5: Gender normalization
echo "Test 5: Testing gender normalization...\n";
$genders = ['M', 'MALE', 'Masculin', 'F', 'FEMALE', 'Féminin', 'X'];
foreach ($genders as $gender) {
    $normalized = UDFDataMapper::mapUDFToFormFields(['SEX' => $gender]);
    echo "  $gender → " . ($normalized['sex'] ?? 'UNKNOWN') . "\n";
}
echo "\n";

// Test 6: Email validation
echo "Test 6: Testing email validation...\n";
$emails = [
    'valid@example.com' => true,
    'invalid.email' => false,
    'user+tag@domain.co.uk' => true,
    'plaintext' => false
];
foreach ($emails as $email => $expected) {
    $result = UDFDataMapper::isEmail($email);
    $status = ($result === $expected) ? '✓' : '✗';
    echo "  $status $email: " . ($result ? 'VALID' : 'INVALID') . "\n";
}
echo "\n";

// Test 7: Phone number validation
echo "Test 7: Testing phone number validation...\n";
$phones = [
    '+242 06 123 4567' => true,
    '06 123 4567' => true,
    '+242-6-123-4567' => true,
    '123' => false,
    'not a phone' => false
];
foreach ($phones as $phone => $expected) {
    $result = UDFDataMapper::isPhoneNumber($phone);
    $status = ($result === $expected) ? '✓' : '✗';
    echo "  $status '$phone': " . ($result ? 'VALID' : 'INVALID') . "\n";
}
echo "\n";

// Test 8: Complete data mapping
echo "Test 8: Testing complete account data structure...\n";

// Simulate a complete Flexcube response
$flexcube_response = [
    'success' => true,
    'data' => [
        'account_number' => '37220026306',
        'account_name' => 'Jean Dupont',
        'account_type' => 'SAVINGS',
        'account_class' => 'BJPVCI',
        'currency' => 'XAF',
        'status' => 'ACTIVE',
        'available_balance' => '3303601.0',
        'current_balance' => '3303601.0',
        'customer_id' => '370003189',
        'customer_type' => 'I',
        'branch_code' => 'T32',
        'od_limit' => '0.0',
        'blocked_amount' => '0.0',
        
        // Mapped form fields
        'form_fields' => $mapped,
        
        // Raw UDF
        'udf_raw' => $sample_udf,
        
        // Legacy fields
        'first_name' => $mapped['first-name'] ?? null,
        'last_name' => $mapped['last-name'] ?? null,
        'email' => $mapped['email'] ?? null,
        'phone' => $mapped['phone'] ?? null,
        'nationality' => $mapped['nationality'] ?? null
    ]
];

echo "Response structure:\n";
echo "  Account: " . $flexcube_response['data']['account_number'] . "\n";
echo "  Form fields available: " . count($flexcube_response['data']['form_fields']) . "\n";
echo "  Raw UDF available: " . count($flexcube_response['data']['udf_raw']) . "\n";
echo "  Status: " . $flexcube_response['data']['status'] . "\n\n";

// Test 9: Form field discovery
echo "Test 9: Testing form field discovery...\n";
$test_fields = [
    'FIRST_NAME',
    'CONTACT_ADDRESS',
    'EMAIL',
    'PHONE',
    'NATIONALITY',
    'SEX',
    'UNKNOWN_FIELD'
];

foreach ($test_fields as $udf_name) {
    $description = UDFDataMapper::getUDFDescription($udf_name);
    if ($description) {
        echo "  ✓ $udf_name: $description\n";
    } else {
        echo "  ? $udf_name: NOT IN KNOWN FIELDS\n";
    }
}
echo "\n";

// Test 10: JSON output format
echo "Test 10: Testing JSON output (like fetch_account_flexcube.php returns)...\n";
$json_output = json_encode([
    'success' => true,
    'source' => 'flexcube',
    'data' => $flexcube_response['data']['form_fields']
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

echo "JSON output length: " . strlen($json_output) . " bytes\n";
echo "Sample (first 500 chars):\n";
echo substr($json_output, 0, 500) . "...\n\n";

echo "=== All Tests Completed ===\n";
echo "✓ If all tests passed, the integration should work correctly\n";
echo "✓ Verify that the FormAutoFiller.js library loads correctly in the browser\n";
echo "✓ Check browser console for any JavaScript errors\n";
?>
