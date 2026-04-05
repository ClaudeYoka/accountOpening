<?php
// Session déjà démarrée dans index.php, pas besoin de la redémarrer
include('includes/config.php');
require 'vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

if (isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['options'];

    // Récupérer l'utilisateur par son nom d'utilisateur
    $stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE Username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($query);
    
    if ($count > 0) {
        $row = mysqli_fetch_assoc($query);
        
        // Vérifier le mot de passe
        $passwordValid = false;
        
        if (strlen($row['Password']) == 32 && ctype_xdigit($row['Password'])) {
            // Ancien hash MD5 - vérifier et mettre à jour
            if (md5($password) === $row['Password']) {
                $passwordValid = true;
                // Mettre à jour vers bcrypt
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = mysqli_prepare($conn, "UPDATE tblemployees SET Password=? WHERE emp_id=?");
                mysqli_stmt_bind_param($updateStmt, "ss", $hashedPassword, $row['emp_id']);
                mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
            }
        } else {
            // Hash bcrypt moderne
            $passwordValid = password_verify($password, $row['Password']);
        }
        
        if ($passwordValid) {
                $_SESSION['alogin'] = $row['emp_id'];
                $_SESSION['arole'] = $row['role'];
                $_SESSION['adepart'] = $row['Department'];

                // Enregistrer la connexion dans tbl_logins
                $loginStmt = mysqli_prepare($conn, "INSERT INTO tbl_logins (emp_id) VALUES (?)");
                mysqli_stmt_bind_param($loginStmt, "s", $row['emp_id']);
                mysqli_stmt_execute($loginStmt);
                mysqli_stmt_close($loginStmt);

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
                    
                    // Générer l'URL du QR code pour Microsoft Authenticator
                    $otpUrl = "otpauth://totp/ECOBANK%20AO%20%26%20KYC:" . urlencode($row['EmailId']) . "?secret=" . $secret . "&issuer=ECOBANK%20AO%20%26%20KYC";
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpUrl);
                    
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
            $_SESSION['login_error_message'] = "Nom d'utilisateur ou mot de passe incorrect";
        }
    } else {
        $_SESSION['login_error_message'] = "Nom d'utilisateur ou mot de passe incorrect";
    }
    
    mysqli_stmt_close($stmt);
}

// Traitement de la vérification 2FA
if (isset($_POST['verify_2fa'])) {
    $code = $_POST['twofa_code'];
    $g = new GoogleAuthenticator();
    
    if ($g->checkCode($_SESSION['twofa_secret'], $code)) {
        // Code valide, compléter la connexion
        completeLogin($conn); // Passer la connexion ici
    } else {
        $_SESSION['login_error_message'] = "Code 2FA invalide";
        header("Location: ../index.php");
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
        
        $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET twofa_secret=?, twofa_enabled=1 WHERE Username=?");
        mysqli_stmt_bind_param($stmt, "ss", $secret, $emp_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Détruire les variables temporaires
        unset($_SESSION['temp_twofa_secret']);
        unset($_SESSION['temp_user_id']);
        
        // Compléter la connexion
        completeLogin($conn);
    } else {
        echo "<script>alert('Code de vérification invalide');</script>";
        include('includes/display_qrcode.php');
        exit();
    }
}

function completeLogin($conn) {
    // Mettre à jour le statut de connexion
    $emp_id = $_SESSION['alogin'];
    $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id=?");
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirection selon le rôle
    switch ($_SESSION['arole']) {
        case 'Admin':
            header("Location: admin/index");
            break;
        case 'cso':
            header("Location: cso/index");
            break;
        case 'CI':
            header("Location: ci/index");
            break;
        default:
            header("Location: index");
    }
    exit();
}
?>
