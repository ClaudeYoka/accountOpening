<?php
session_name('ACCOUNT_OPENING_SESSION');
session_start();
include('config.php');
require __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['alogin'])) {
    header("Location: ../index.php");
    exit();
}

// Récupérer l'utilisateur
$emp_id = $_SESSION['alogin'];
$stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE emp_id = ?");
mysqli_stmt_bind_param($stmt, "s", $emp_id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($query);
mysqli_stmt_close($stmt);

if (!$user) {
    $_SESSION['login_error_message'] = "Utilisateur non trouvé";
    header("Location: ../index.php");
    exit();
}

if (empty($user['emp_id'])) {
    $_SESSION['login_error_message'] = "ID utilisateur invalide";
    header("Location: ../index.php");
    exit();
}

if (empty($user['role'])) {
    $_SESSION['login_error_message'] = "Rôle utilisateur non défini";
    header("Location: ../index.php");
    exit();
}

// Générer ou récupérer le secret
$g = new GoogleAuthenticator();
if (empty($user['twofa_secret'])) {
    $secret = $g->generateSecret();
    // Sauvegarder temporairement
    $_SESSION['temp_twofa_secret'] = $secret;
    $_SESSION['temp_user_id'] = $user['Username'];
} else {
    $secret = $user['twofa_secret'];
    // Pour reconfigurer, régénérer
    $secret = $g->generateSecret();
    $_SESSION['temp_twofa_secret'] = $secret;
    $_SESSION['temp_user_id'] = $user['Username'];
}

// Générer l'URL du QR code
$otpUrl = "otpauth://totp/ECOBANK%20AO%20%26%20KYC:" . urlencode($user['EmailId']) . "?secret=" . $secret . "&issuer=ECOBANK%20AO%20%26%20KYC";
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpUrl);

// Traitement du formulaire d'activation
if (isset($_POST['activate_2fa'])) {
    $code = $_POST['twofa_code'];
    
    if ($g->checkCode($_SESSION['temp_twofa_secret'], $code)) {
        // Sauvegarder la clé dans la base de données
        $emp_id_session = $_SESSION['temp_user_id'];
        $secret_to_save = $_SESSION['temp_twofa_secret'];
        
        $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET twofa_secret=?, twofa_enabled=1 WHERE Username=?");
        mysqli_stmt_bind_param($stmt, "ss", $secret_to_save, $emp_id_session);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Détruire les variables temporaires
        unset($_SESSION['temp_twofa_secret']);
        unset($_SESSION['temp_user_id']);
        
        // Rediriger vers la connexion complète
        $_SESSION['twofa_secret'] = $secret_to_save;
        
        // Mettre à jour le statut de connexion
        $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id=?");
        mysqli_stmt_bind_param($stmt, "s", $_SESSION['alogin']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Redirection selon le rôle
        switch ($_SESSION['arole']) {
            case 'Admin':
                header("Location: ../admin/admin_dashboard");
                break;
            case 'staff':
                header("Location: ../staff/index");
                break;
            case 'cso':
                header("Location: ../cso/index");
                break;
            case 'RH':
                header("Location: ../rh/index");
                break;
            case 'HOD':
                header("Location: ../heads/index");
                break;
            default:
                header("Location: ../index");
        }
        exit();
    } else {
        $_SESSION['login_error_message'] = "Code de vérification invalide pour l'activation 2FA";
        header("Location: ../index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activation 2FA</title>
    <!-- Site favicon -->
	<link rel="ecobank-bg" sizes="180x180" href="vendors/images/ecobank-bg.png">
	<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/ecobank-bg.png">
	<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/ecobank-bg.png">

	<!-- Mobile Specific Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">

    <style>
        .qr-container {
            text-align: center;
            margin-top: 50px;
        }
        .instructions {
            margin: 20px auto;
            max-width: 500px;
        }
        .verify-form {
            margin-top: 20px;
        }
    </style>

</head>
<body>
    <div class="qr-container">
        <h2>Configuration de l'authentification à deux facteurs</h2>
        
        <div class="instructions">
            <p>1. Téléchargez Microsoft Authenticator sur votre téléphone</p>
            <p>2. Ouvrez l'application et appuyez sur "+" pour ajouter un compte</p>
            <p>3. Choisissez "Autre (TOTP)" et scannez ce QR Code</p>
            <p>4. Entrez le code à 6 chiffres généré par l'application ci-dessous</p>
        </div>
        
        <img src="<?php echo $qrUrl; ?>" alt="QR Code pour 2FA" />
        
        <form method="post" class="verify-form">
            <input type="text" name="twofa_code" placeholder="Code à 6 chiffres" required>
            <input type="submit" name="activate_2fa" value="Activer 2FA">
        </form>
    </div>
</body>
</html>
