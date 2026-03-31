<?php
include('../includes/config.php');

// Vérifier si c'est un admin
if (isset($_SESSION['alogin']) && $_SESSION['arole'] === 'Admin') {
    // Vérifier si la notification de monitoring existe déjà
    $check_query = "SELECT id FROM tblnotification
                   WHERE emp_id = ? AND message LIKE '%système de monitoring%'
                   AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";

    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $_SESSION['alogin']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
        // Créer la notification de monitoring
        $message = "🎉 Nouveau système de monitoring disponible ! Accédez aux métriques en temps réel depuis le menu 'Monitoring'.";
        $insert_query = "INSERT INTO tblnotification (emp_id, message, type, created_at, is_read)
                        VALUES (?, ?, 'system', NOW(), 0)";

        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ss", $_SESSION['alogin'], $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    mysqli_stmt_close($stmt);
}
?>