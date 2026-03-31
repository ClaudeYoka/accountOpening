<?php
    session_start();
    include('includes/config.php');
    include('includes/audit_logger.php');

    if(isset($_SESSION['alogin'])) {
        $emp_id = $_SESSION['alogin'];

        // Log logout before updating status
        audit_log_logout($conn, $emp_id);

        // Mettre à jour le statut de l'utilisateur à 'Offline'
        $result = mysqli_query($conn, "UPDATE tblemployees SET status='Offline' WHERE emp_id='$emp_id'");

        // Mettre à jour le logout_time pour l'enregistrement de connexion le plus récent
        mysqli_query($conn, "UPDATE tbl_logins SET logout_time = NOW() WHERE emp_id = '$emp_id' AND logout_time IS NULL");
    }

    // Détruire la session
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 60*60,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    unset($_SESSION['alogin']);
    session_destroy(); // Détruire la session

    header("location:index");
    exit();
?>
