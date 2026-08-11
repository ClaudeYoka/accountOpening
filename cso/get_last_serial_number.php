<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

if (!isset($_SESSION['emp_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    $result = mysqli_query($conn, "SELECT MAX(CAST(ident_etud AS UNSIGNED)) AS last_serial FROM tblcompte WHERE ident_etud REGEXP '^[0-9]+$'");
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($result);
    $lastSerial = isset($row['last_serial']) && $row['last_serial'] !== null ? (int)$row['last_serial'] : 0;

    echo json_encode([
        'success' => true,
        'last_serial' => $lastSerial,
        'serial_number1' => $lastSerial + 1
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
