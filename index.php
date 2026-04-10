<?php
// Démarrer le buffer de sortie pour permettre les redirections même après du HTML
ob_start();

session_name('ACCOUNT_OPENING_SESSION');
session_start();
require_once __DIR__ . '/includes/security_config.php';

// Charger les variables d'environnement
$env = parse_ini_file('.env');
$loginController = $env['LOGIN_CONTROLLER'] ?? 'loginController';
include('includes/' . $loginController . '.php');

// Gérer les redirections AVANT tout output HTML
global $login_result;
if (isset($login_result['redirect_to_change_password']) && $login_result['redirect_to_change_password']) {
    error_log("Redirecting to change_password.php for user: " . ($_SESSION['alogin'] ?? 'unknown'));
    header('Location: change_password.php');
    exit;
} elseif (isset($login_result['redirect_url']) && $login_result['redirect_url']) {
    error_log("Redirecting to: " . $login_result['redirect_url'] . " for user: " . ($_SESSION['alogin'] ?? 'unknown'));
    header('Location: ' . $login_result['redirect_url']);
    exit;
}

// Vider le buffer si aucune redirection n'a été faite
ob_end_flush();
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>AO & KYC - Connexion</title>
    <!-- Site favicon -->
    <link rel="logo1" sizes="180x180" href="vendors/images/logo1.png">
    <link rel="icon" type="image/png" sizes="32x32" href="vendors/images/logo1.png">
    <link rel="icon" type="image/png" sizes="16x16" href="vendors/images/logo1.png">
    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/cores.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/style.css">
    <link rel="stylesheet" type="text/css" href="vendors/styles/indexstyle.css">
</head>
<body class="login-page">
    <div class="login-header box-shadow">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="brand-logo">
                <a href="#">
                    <img src="vendors/images/ecobank-bg.png" alt="">
                </a>
            </div>
			<div class="login-menu">
				<ul>
					<li><a href="forgot-password.html">Mot de Passe Oublié</a></li>
				</ul>
			</div>
        </div>
    </div>
    <div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-7">
                    <img src="vendors/images/loginpage.png" alt="">
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
							<center><img src="vendors/images/ecobank-bg.png" width="100"  alt=""></center>
						</div>

                        <?php if (!empty($_SESSION['login_error_message'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($_SESSION['login_error_message'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php unset($_SESSION['login_error_message']); ?>
                        <?php endif; ?>
                        <form name="signin" method="post" id="loginForm">
                            <?php echo get_csrf_field(); ?>
							<div class="select-role">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <label class="btn active" id="csoBtn">
                                        <input type="radio" name="options" id="cso" value="cso" checked>
                                        <div class="icon"><img src="vendors/images/person.svg" class="svg" alt=""></div>
                                        <span>Je suis</span>CSO
                                    </label>
                                    <label class="btn" id="ciBtn">
                                        <input type="radio" name="options" id="CI" value="CI" >
                                        <div class="icon"><img src="vendors/images/briefcase.svg" class="svg" alt=""></div>
                                        <span>Je suis</span>CI
                                    </label>
                                </div>
                            </div>

                            <div class="input-group custom">
                                <input type="text" class="form-control form-control-lg" placeholder="Nom d'utilisateur" name="username" id="username" required autocomplete="username">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="icon-copy dw dw-user1"></i></span>
                                </div>
                            </div>
                            <div class="input-group custom">
                                <input type="password" class="form-control form-control-lg" placeholder="Mot de passe" name="password" id="password" required autocomplete="current-password">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0">
                                        <button class="btn btn-primary btn-lg btn-block" type="submit" name="signin" id="signin">
                                            <span id="btnText">Se Connecter</span>
                                            <span id="btnLoading" class="loading" style="display: none;">★★★★★</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="mt-3 text-center">
                                <small class="text-muted">
                                    Pour l'authentification à deux facteurs (2FA), vous devez installer Microsoft Authenticator.<br>
                                    <a href="https://play.google.com/store/apps/details?id=com.azure.authenticator" target="_blank" class="text-primary">Télécharger sur Google Play</a> | 
                                    <a href="https://apps.apple.com/app/microsoft-authenticator/id983156458" target="_blank" class="text-primary">Télécharger sur l'App Store</a><br>
                                    Après connexion, scannez le QR code affiché pour configurer votre token.
                                </small>
                            </div> -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="vendors/scripts/sript-styleindex.js"></script>
</body>
</html>
