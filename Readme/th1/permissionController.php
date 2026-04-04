

<?php

    if(isset($_POST['apply'])) {
        $empid = $session_id;
        $raison = $_POST['Raison'];
        $fromdate = date('Y-m-d', strtotime($_POST['date_from']));
        $todate = date('Y-m-d', strtotime($_POST['date_to']));
        $requested_days = $_POST['requested_days'];
        $requested_hours = $_POST['requested_hours'];
        $hod_status = 0; 
        $reg_status = 0; 
        $datePosting = date("Y-m-d");
        $admremarkdate = date('Y-m-d G:i:s');


        $DF = date_create($_POST['date_from']);
        $DT = date_create($_POST['date_to']);
        $diff = date_diff($DF, $DT);
        $num_days = (1 + $diff->format("%a"));

        $query = mysqli_query($conn, "SELECT * FROM tblemployees WHERE emp_id = '$session_id'");
        $row = mysqli_fetch_assoc($query);

        if($fromdate > $todate) {
        echo "<script>alert('La date de fin doit être postérieure à la date de début');</script>";
    } else {
        // Insérer la demande de congé
        $sql = "INSERT INTO tblpermission (ToDate, FromDate, requested_days, requested_hours, Raison, HodRemarks, RegRemarks, empid, PostingDate, HodDate, RegDate) 
               VALUES ('$todate', '$fromdate', '$requested_days', '$requested_hours','$raison', $hod_status, $reg_status, '$empid', NOW(), NULL, NULL)";
        
        $lastInsertId = mysqli_query($conn, $sql);
        
        if ($lastInsertId) {
            // Insérer une notification
            $notification_message = " Permission envoyé pour approbation.";
            $notification_sql = "INSERT INTO tblnotifications (emp_id, message , created_at, notification_type) VALUES ('$empid', '$notification_message','$admremarkdate', 'demande' )";
            mysqli_query($conn, $notification_sql);
            
            // Compter le nombre de notifications
            $count_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblnotifications WHERE emp_id = '$empid'");
            $count_result = mysqli_fetch_assoc($count_query);
            $notification_count = $count_result['count'];

            echo "<script>
                    alert('Demande de permission envoyée avec succès. En attente des approbations du HEAD et des Ressources Humaines'); 
                    window.location.href = 'apply_permission';
                </script>";
        } else {
            echo "<script>alert('Erreur lors de l\\'enregistrement: " . mysqli_error($conn) . "');</script>";
        }
    }
    }

  
?>


