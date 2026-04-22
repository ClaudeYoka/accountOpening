<?php
session_name('ACCOUNT_OPENING_SESSION');
session_start();
require_once __DIR__ . '/includes/security_config.php';
include('includes/config.php');
require_once('includes/audit_logger.php');
include('includes/flash.php');

// Verifier que l'utilisateur est connecte
if (!isset($_SESSION['alogin'])) {
    error_log("change_password.php: alogin not set, redirecting to index.php");
    header('Location: index.php');
    exit;
} else {
    error_log("change_password.php: alogin is set to " . $_SESSION['alogin']);
}

// Variable pour stocker les messages directement sur cette page
$message = '';
$message_type = '';
$redirect_after_success = false;

if(isset($_POST['change_password'])) {
    check_csrf();
    // Validation du mot de passe
    $new_password = $_POST['new_password'];

    $errors = [];

    // Validation du mot de passe
    if (empty($new_password)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caracteres";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $new_password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule et un chiffre";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'error';
    } else {
        $emp_id = $_SESSION['alogin'];

        // Verifier que l'utilisateur existe et que le mot de passe n'a pas deja ete change
        $check_stmt = mysqli_prepare($conn, "SELECT password_changed FROM tblemployees WHERE emp_id = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $emp_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $user_data = mysqli_fetch_assoc($check_result);
        mysqli_stmt_close($check_stmt);

        if (!$user_data) {
            $message = "Utilisateur non trouve.";
            $message_type = 'error';
        } elseif ($user_data['password_changed'] == 1) {
            $message = "Le mot de passe a deja ete change.";
            $message_type = 'error';
        } else {
            // Hasher le mot de passe avec password_hash
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

            // Mettre a jour le mot de passe et le statut
            $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET Password=?, password_changed=1 WHERE emp_id=?");
            mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $emp_id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                // Log password change
                audit_log_user_action($conn, 'password_changed', $emp_id);

                $message = 'Mot de passe change avec succes. Vous allez etre redirige vers la page de connexion.';
                $message_type = 'success';
                $redirect_after_success = true;
            } else {
                mysqli_stmt_close($stmt);
                $message = "Erreur lors de la mise a jour du mot de passe.";
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
	<!-- Basic Page Info -->
	<meta charset="utf-8">
	<title>Changer Mot de passe</title>

	<!-- Site favicon -->
	<link rel="ecobank-bg" sizes="180x180" href="vendors/images/ecobank-bg.png">
	<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/ecobank-bg.png">
	<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/ecobank-bg.png">

	<!-- Mobile Specific Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- Google Font -->
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">


	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119386393-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-119386393-1');
	</script>
</head>
<body>
    <div class="login-header box-shadow">
		<div class="container-fluid d-flex justify-content-between align-items-center">
			<div class="brand-logo">
				<a href="login.html">
					<img src="vendors/images/ecobank-bg.png" alt="">
				</a>
			</div>
			<div class="login-menu">
				<ul>
					<li><a href="index">Login</a></li>
				</ul>
			</div>
		</div>
	</div>

    <?php render_flash_message(); ?>

    <!-- Afficher les messages directement sur cette page -->
    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $message_type === 'error' ? 'alert-danger' : 'alert-success'; ?>" role="alert">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php if ($redirect_after_success): ?>
            <script>
                setTimeout(function() {
                    window.location.href = 'logout.php';
                }, 3000);
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-md-6">
					<img src="vendors/images/illustration-log.png" alt="">
                </div>

                <div class="col-md-6">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <h2>Changer le Mot de Passe</h2>
                        </div>

                        <form method="post">
                            <?php echo get_csrf_field(); ?>
                            <div class="input-group custom">
                                <input class="form-control form-control-lg" type="password" name="new_password" placeholder="Nouveau mot de passe" required>
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="dw dw-padlock" aria-hidden="true"></i></span>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-12">
                                    <input class="btn btn-primary btn-lg btn-block" type="submit" name="change_password" value="Changer le Mot de Passe">
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
