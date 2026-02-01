<?php
include('../includes/session.php');
include('../includes/config.php'); 

if(isset($_GET['id'])) {
    $notification_id = $_GET['id'];
    $empid = $session_id;
    
    // Vérifier que l'utilisateur a le droit de supprimer cette notification
    $check_query = mysqli_query($conn, "SELECT * FROM tblnotifications WHERE id = '$notification_id' AND emp_id = '$empid'");
    
    if(mysqli_num_rows($check_query) > 0) {
        $delete_query = mysqli_query($conn, "DELETE FROM tblnotifications WHERE id = '$notification_id'");
        
        if($delete_query) {
            header("Location: ".$_SERVER['HTTP_REFERER']."");
            exit();
        } else {
            echo "Erreur lors de la suppression: " . mysqli_error($conn);
        }
    } else {
        echo "Vous n'avez pas la permission de supprimer cette notification.";
    }
} else {
    echo "ID de notification non spécifié.";
}
?>
