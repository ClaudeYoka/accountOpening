<?php
    include('../includes/config.php');

    $emp_id = $_GET['emp_id'] ?? 0;
    $last_checked = $_GET['last_checked'] ?? date('Y-m-d H:i:s');

    // Récupérer les nouvelles notifications
    $query = "SELECT message, created_at 
              FROM tblnotifications 
              WHERE emp_id = $emp_id 
              AND notification_type = 'leave'
              AND created_at > '$last_checked'
              ORDER BY created_at DESC
              LIMIT 1";

    $result = mysqli_query($conn, $query);
    $latest_notification = mysqli_fetch_assoc($result);
    $new_count = mysqli_num_rows($result);

    // Compter toutes les notifications non lues
    $unread_query = "SELECT COUNT(*) as count FROM tblnotifications 
                    WHERE emp_id = $emp_id 
                    AND notification_type = 'leave'
                    AND is_read = 0";
    $unread_result = mysqli_query($conn, $unread_query);
    $unread_count = mysqli_fetch_assoc($unread_result)['count'];

    header('Content-Type: application/json');
    echo json_encode([
        'new_notifications' => $new_count,
        'latest_message' => $latest_notification['message'] ?? '',
        'unread_count' => $unread_count,
        'last_created_at' => $latest_notification['created_at'] ?? $last_checked
    ]);
?>
