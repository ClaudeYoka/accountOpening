<?php
// Quick test for rib_lookup.php

include('../includes/config.php');
include('../includes/session.php');
include('includes/flexcube_helpers.php');
include('includes/FlexcubeAPI.php');

header('Content-Type: application/json; charset=utf-8');

$test_account = '37220026309'; // Account from user's debug output

echo json_encode([
    'test' => 'Testing rib_lookup with account: ' . $test_account,
    'flexcube_function_exists' => function_exists('fetchAccountFromFlexcube'),
    'flexcube_api_class_exists' => class_exists('FlexcubeAPI')
], JSON_PRETTY_PRINT);

try {
    $flexcube_data = fetchAccountFromFlexcube($test_account);
    echo "\n\nFlexcube Response:\n";
    echo json_encode($flexcube_data, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "\n\nFlexcube Error:\n";
    echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>
