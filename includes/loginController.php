<?php
session_start();
include('includes/config.php');

if (isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Récupérer l'utilisateur par son nom d'utilisateur
    $sql = "SELECT * FROM tblemployees WHERE Username ='$username'";
    $query = mysqli_query($conn, $sql);
    $count = mysqli_num_rows($query);
    
    if ($count > 0) {
        $row = mysqli_fetch_assoc($query);
        
        // // Vérifier si l'utilisateur appartient à l'agence sélectionnée
        // if ($row['Department'] !== $selected_agency) {
        //     echo "<script>alert('Veuillez sélectionner votre agence.');</script>";
        //     return; // Sortir de la fonction si l'agence ne correspond pas
        // }

        // Vérifier si le mot de passe est stocké en MD5
        if (strlen($row['Password']) == 32) { // MD5 produit un hachage de 32 caractères
            // Vérifier le mot de passe avec MD5
            if (md5($password) === $row['Password']) {
                // Mettre à jour le mot de passe avec password_hash
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE tblemployees SET Password='$hashedPassword' WHERE emp_id='{$row['emp_id']}'");
                
                // Authentifier l'utilisateur
                $_SESSION['alogin'] = $row['emp_id'];
                $_SESSION['arole'] = $row['role'];
                $_SESSION['adepart'] = $row['Department'];

                // Enregistrer la connexion dans tbl_logins
                $emp_id = $row['emp_id'];
                $login_sql = "INSERT INTO tbl_logins (emp_id) VALUES ('$emp_id')";
                mysqli_query($conn, $login_sql);
                
                // Mettre à jour le statut de connexion
                $result = mysqli_query($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id='$emp_id'");

                // Si l'utilisateur n'a pas encore changé son mot de passe, le rediriger
                if ($row['password_changed'] == 0) {
                    // Assurer la session et rediriger vers la page de changement de mot de passe
                    $_SESSION['alogin'] = $row['emp_id'];
                    $_SESSION['arole'] = $row['role'];
                    $_SESSION['adepart'] = $row['Department'];
                    echo "<script type='text/javascript'> document.location = 'change_password.php'; </script>";
                    exit();
                }

                // Rediriger vers le tableau de bord approprié
                if ($row['role'] == 'Admin') {
                    echo "<script type='text/javascript'> document.location = 'admin/admin_dashboard'; </script>";
                } elseif ($row['role'] == 'cso') {
                    echo "<script type='text/javascript'> document.location = 'cso/index'; </script>";
                } else {
                    echo "<script type='text/javascript'> document.location = 'ci/index'; </script>";
                }
            } else {
                echo "<script>alert('Username ou mot de passe incorrect');</script>";
            }
        } else {
            // Vérifier le mot de passe avec password_verify pour les mots de passe hachés
            if (password_verify($password, $row['Password'])) {
                // Authentifier l'utilisateur
                $_SESSION['alogin'] = $row['emp_id'];
                $_SESSION['arole'] = $row['role'];
                $_SESSION['adepart'] = $row['Department'];

                // Enregistrer la connexion dans tbl_logins
                $emp_id = $row['emp_id'];
                $login_sql = "INSERT INTO tbl_logins (emp_id) VALUES ('$emp_id')";
                mysqli_query($conn, $login_sql);
                
                // Mettre à jour le statut de connexion
                $result = mysqli_query($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id='$emp_id'");

                // Si l'utilisateur n'a pas encore changé son mot de passe, le rediriger
                if ($row['password_changed'] == 0) {
                    // Assurer la session et rediriger vers la page de changement de mot de passe
                    $_SESSION['alogin'] = $row['emp_id'];
                    $_SESSION['arole'] = $row['role'];
                    $_SESSION['adepart'] = $row['Department'];
                    echo "<script type='text/javascript'> document.location = 'change_password.php'; </script>";
                    exit();
                }

                // Rediriger vers le tableau de bord approprié
                if ($row['role'] == 'Admin') {
                    echo "<script type='text/javascript'> document.location = 'admin/index'; </script>";
                } elseif ($row['role'] == 'cso') {
                    echo "<script type='text/javascript'> document.location = 'cso/index'; </script>";
                }  else {
                    echo "<script type='text/javascript'> document.location = 'ci/index'; </script>";
                }
            } else {
                echo "<script>alert('Username ou mot de passe incorrect');</script>";
            }
        }
    } else {
        echo "<script>alert('Informations inconnues');</script>";
    }
}
?>