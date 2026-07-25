<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID invalide']);
    exit;
}

$emp = $_SESSION['emp_id'];
$upd = "UPDATE tblnotification SET is_read = 1 WHERE id = " . $id . " AND emp_id = '" . mysqli_real_escape_string($conn, $emp) . "'";
if (mysqli_query($conn, $upd)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}
?>
