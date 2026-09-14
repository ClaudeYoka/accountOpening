<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/includes/flexcube_helpers.php';

$name = isset($_GET['name']) ? trim((string) $_GET['name']) : '';
if ($name === '' || strlen($name) < 2) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Saisissez au moins deux caractères.'
    ]);
    exit;
}

try {
    $rows = searchAccountsByName($name);
    $results = [];

    foreach ($rows as $row) {
        $results[] = [
            'account_number' => $row['account_number'] ?? '',
            'account_title' => $row['account_name'] ?? '',
            'account_type' => $row['account_type'] ?? '',
            'date_of_birth' => $row['date_of_birth'] ?? '',
            'branch_code' => $row['branch_code'] ?? '',
            'customer_id' => $row['customer_id'] ?? '',
            'account_address' => $row['account_address'] ?? ''
        ];
    }

    ob_end_clean();
    echo json_encode([
        'status' => 'ok',
        'results' => $results
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur lors de la recherche.'
    ]);
}