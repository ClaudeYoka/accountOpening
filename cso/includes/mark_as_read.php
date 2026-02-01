<?php

include('../../includes/session.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_id = $_POST['id'];
    $update_query = "UPDATE tblnotification SET is_read = 1 WHERE id = '$notification_id'";
    mysqli_query($conn, $update_query);
}

?>
