<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
include('../includes/audit_helpers.php');

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['request_id']) || empty($data['status'])) {
    echo json_encode(['status'=>'error','message'=>'Paramètres manquants']);
    exit;
}

$request_id = intval($data['request_id']);
$status = substr(trim($data['status']), 0, 100);
$changed_by = isset($_SESSION['emp_id']) ? intval($_SESSION['emp_id']) : null;

$current_stmt = mysqli_prepare($conn, "SELECT COALESCE((SELECT status FROM chequier_status WHERE request_id = ? ORDER BY changed_at DESC, id DESC LIMIT 1), access, 'encours') AS current_status FROM tblcompte WHERE id = ? LIMIT 1");
if ($current_stmt) {
    mysqli_stmt_bind_param($current_stmt, 'ii', $request_id, $request_id);
    mysqli_stmt_execute($current_stmt);
    $current_result = mysqli_stmt_get_result($current_stmt);
    $current_row = $current_result ? mysqli_fetch_assoc($current_result) : null;
    mysqli_stmt_close($current_stmt);
    if ($current_row && in_array(strtolower(trim($current_row['current_status'])), ['donné', 'donne', 'donnee', 'donnée'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Une demande donnée ne peut plus être modifiée']);
        exit;
    }
}

// Ensure table exists (safe: CREATE IF NOT EXISTS)
$sql_create = "CREATE TABLE IF NOT EXISTS chequier_status (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $sql_create);

$stmt = mysqli_prepare($conn, "INSERT INTO chequier_status (request_id, status, changed_by) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'isi', $request_id, $status, $changed_by);
$ok = mysqli_stmt_execute($stmt);
if ($ok) {
    // Mettre à jour le champ access dans tblcompte
    $stmt_update = mysqli_prepare($conn, "UPDATE tblcompte SET access = ? WHERE id = ?");
    if ($stmt_update) {
        mysqli_stmt_bind_param($stmt_update, 'si', $status, $request_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }
    // Audit logging for chequier status update
    log_admin_action('update_chequier_status', $request_id, [
        'new_status' => $status,
        'table' => 'tblcompte'
    ]);
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>mysqli_stmt_error($stmt)]);
}
mysqli_stmt_close($stmt);
exit;

?>
