<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';



$empid = $session_id;
$delete_query = mysqli_query($conn, "DELETE FROM tblnotification WHERE emp_id = '$empid'");

if($delete_query) {
    header("Location: ".$_SERVER['HTTP_REFERER']."");
    exit();
} else {
    echo "Erreur lors de la suppression des notifications: " . mysqli_error($conn);
}
?>
