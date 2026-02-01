<?php
session_start();
include('includes/config.php');
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

if (isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['options'];

    // Récupérer l'utilisateur par son nom d'utilisateur
    $sql = "SELECT * FROM tblemployees WHERE Username ='$username'";
    $query = mysqli_query($conn, $sql);
    $count = mysqli_num_rows($query);
    
    if ($count > 0) {
        $row = mysqli_fetch_assoc($query);
        
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

                // Vérifier si l'utilisateur doit changer son mot de passe
                if ($row['password_changed'] == 0) {
                    // Rediriger vers la page de changement de mot de passe
                    echo "<script type='text/javascript'> document.location = 'change_password.php'; </script>";
                    exit();
                }

                // Vérifier si 2FA est configuré
                if (empty($row['twofa_secret'])) {
                    // Générer une nouvelle clé secrète
                    $g = new GoogleAuthenticator();
                    $secret = $g->generateSecret();
                    
                    // Sauvegarder dans la session temporairement
                    $_SESSION['temp_twofa_secret'] = $secret;
                    $_SESSION['temp_user_id'] = $row['Username'];
                    
                    // Générer l'URL du QR code
                    $qrUrl = GoogleQrUrl::generate($row['EmailId'], $secret, 'Eco Leave Système');
                    
                    // Afficher la page avec le QR code
                    include('includes/display_qrcode.php');
                    exit();
                } else {
                    // Rediriger vers la vérification du code
                    $_SESSION['verify_twofa'] = true;
                    $_SESSION['twofa_secret'] = $row['twofa_secret'];
                    include('includes/verify_2fa.php');
                    exit();
                }
            } else {
                echo "<script>alert('Détails Invalides');</script>";
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

                // Vérifier si l'utilisateur doit changer son mot de passe
                if ($row['password_changed'] == 0) {
                    // Rediriger vers la page de changement de mot de passe
                    echo "<script type='text/javascript'> document.location = 'change_password.php'; </script>";
                    exit();
                }

                // Vérifier si 2FA est configuré
                if (empty($row['twofa_secret'])) {
                    // Générer une nouvelle clé secrète
                    $g = new GoogleAuthenticator();
                    $secret = $g->generateSecret();
                    
                    // Sauvegarder dans la session temporairement
                    $_SESSION['temp_twofa_secret'] = $secret;
                    $_SESSION['temp_user_id'] = $row['Username'];
                    
                    // Générer l'URL du QR code
                    $qrUrl = GoogleQrUrl::generate($row['EmailId'], $secret, 'Eco Leave Système');
                    
                    // Afficher la page avec le QR code
                    include('includes/display_qrcode.php');
                    exit();
                } else {
                    // Rediriger vers la vérification du code
                    $_SESSION['verify_twofa'] = true;
                    $_SESSION['twofa_secret'] = $row['twofa_secret'];
                    include('includes/verify_2fa.php');
                    exit();
                }
            } else {
                echo "<script>alert('Détails Invalides');</script>";
            }
        }
    } else {
        echo "<script>alert('Détails Invalides');</script>";
    }
}

// Traitement de la vérification 2FA
if (isset($_POST['verify_2fa'])) {
    $code = $_POST['twofa_code'];
    $g = new GoogleAuthenticator();
    
    if ($g->checkCode($_SESSION['twofa_secret'], $code)) {
        // Code valide, compléter la connexion
        completeLogin($conn); // Passer la connexion ici
    } else {
        echo "<script>alert('Code 2FA invalide');</script>";
        include('includes/verify_2fa.php');
        exit();
    }
}

// Traitement de l'activation 2FA
if (isset($_POST['activate_2fa'])) {
    $code = $_POST['twofa_code'];
    $g = new GoogleAuthenticator();
    
    if ($g->checkCode($_SESSION['temp_twofa_secret'], $code)) {
        // Sauvegarder la clé dans la base de données
        $emp_id = $_SESSION['temp_user_id'];
        $secret = $_SESSION['temp_twofa_secret'];
        
        mysqli_query($conn, "UPDATE tblemployees SET twofa_secret='$secret', twofa_enabled=1 WHERE Username='$emp_id'");
        
        // Détruire les variables temporaires
        unset($_SESSION['temp_twofa_secret']);
        unset($_SESSION['temp_user_id']);
        
        // Compléter la connexion
        completeLogin($conn); // Passer la connexion ici
    } else {
        echo "<script>alert('Code de vérification invalide');</script>";
        include('includes/display_qrcode.php');
        exit();
    }
}

function completeLogin($conn) { // Accepter la connexion comme argument
    // Mettre à jour le statut de connexion
    $emp_id = $_SESSION['alogin'];
    mysqli_query($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id='$emp_id'");

    // Redirection selon le rôle
    switch ($_SESSION['arole']) {
        case 'Admin':
            header("Location: admin/admin_dashboard");
            break;
        case 'staff':
            header("Location: staff/index");
            break;
        case 'RH':
            header("Location: rh/index");
            break;
        case 'HOD':
            header("Location: heads/index");
            break;
        default:
            header("Location: index");
    }
    exit();
}
?>
