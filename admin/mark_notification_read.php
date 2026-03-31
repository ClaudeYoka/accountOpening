<?php
include('../includes/session.php');
include('../includes/config.php');
include('../includes/audit_helpers.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['id']) ? intval($data['id']) : 0;

if ($notification_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de notification invalide']);
    exit;
}

$update = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id AND emp_id = " . $_SESSION['emp_id'];
$result = mysqli_query($conn, $update);

if ($result) {
    // Audit logging for notification read
    log_admin_action('mark_notification_read', $notification_id, [
        'action' => 'mark_read',
        'table' => 'notifications'
    ]);
    echo json_encode(['status' => 'success', 'message' => 'Notification marquée comme lue']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour']);
}
?>
