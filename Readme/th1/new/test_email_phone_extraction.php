<?php
/**
 * Test - Extraction Email et Téléphone depuis API Flexcube
 */

require_once(__DIR__ . '/cso/includes/UDFDataMapper.php');

echo "=== TEST: Email et Téléphone depuis Flexcube API ===\n\n";

// Simuler les données de Flexcube avec les nouveaux champs
$test_cases = [
    'Case 1: EMAIL et PHONE' => [
        'EMAIL' => 'jean@example.com',
        'PHONE' => '+242 06 123 4567'
    ],
    'Case 2: EMAILID et PHONENO' => [
        'EMAILID' => 'marie@example.com',
        'PHONENO' => '+242 07 987 6543'
    ],
    'Case 3: EMAIL_ID et PHONE_NO' => [
        'EMAIL_ID' => 'pierre@example.com',
        'PHONE_NO' => '+242 05 111 2222'
    ],
    'Case 4: Tous les formats' => [
        'FIRST_NAME' => 'Jean',
        'LAST_NAME' => 'Dupont',
        'EMAIL' => 'jean.dupont@example.com',
        'PHONE' => '+242 06 123 4567',
        'EMAILID' => 'jean.dupont@company.com',
        'PHONENO' => '+242 07 987 6543',
        'NATIONALITY' => 'CG',
        'SEX' => 'M'
    ]
];

foreach ($test_cases as $case_name => $udf_values) {
    echo "📌 $case_name\n";
    echo str_repeat("-", 60) . "\n";
    
    $mapped = UDFDataMapper::mapUDFToFormFields($udf_values);
    
    // Vérifier les champs critiques
    $fields_to_check = ['email', 'telephone', 'phone', 'first-name', 'last-name', 'nationality', 'sex'];
    
    foreach ($fields_to_check as $field) {
        if (isset($mapped[$field])) {
            echo "  ✅ $field: " . $mapped[$field] . "\n";
        }
    }
    
    echo "\n";
}

// Test de validation
echo "🔍 TEST: Validation Email et Téléphone\n";
echo str_repeat("-", 60) . "\n";

$test_emails = [
    'jean@example.com',
    'marie.dupont@company.co.uk',
    'invalid-email',
    'user+tag@domain.org'
];

foreach ($test_emails as $email) {
    $valid = UDFDataMapper::isEmail($email);
    $status = $valid ? '✅' : '❌';
    echo "$status Email: $email\n";
}

echo "\n";

$test_phones = [
    '+242 06 123 4567',
    '06 123 4567',
    '+242-7-987-6543',
    '123',
    'not-a-phone'
];

foreach ($test_phones as $phone) {
    $valid = UDFDataMapper::isPhoneNumber($phone);
    $status = $valid ? '✅' : '❌';
    echo "$status Phone: $phone\n";
}

echo "\n=== Tests Complétés ===\n";
?>
