<?php
include('../includes/session.php');
include('../includes/config.php');

header('Content-Type: application/json; charset=utf-8');

// Récupérer les notifications pour l'utilisateur connecté
if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

$emp_id = $_SESSION['emp_id'];

// Créer la table notifications si elle n'existe pas
$create_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT,
    message TEXT,
    type VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT 0,
    submission_id INT,
    INDEX (emp_id),
    INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $create_table);

// Récupérer les notifications non lues
$query = "SELECT * FROM notifications WHERE emp_id = $emp_id AND is_read = 0 ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $query);

$notifications = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
}

echo json_encode([
    'status' => 'success',
    'count' => count($notifications),
    'notifications' => $notifications
]);
?>
