<?php
session_name('ACCOUNT_OPENING_SESSION');
session_start();
// Charger les variables d'environnement
$env = parse_ini_file('.env');
$loginController = $env['LOGIN_CONTROLLER'] ?? 'loginController';
include('includes/' . $loginController . '.php');
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
    <style>
        /* Styles améliorés pour une interface interactive et belle */
        body.login-page {
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
        }

        .login-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .login-header:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .brand-logo img {
            transition: transform 0.3s ease;
        }

        .brand-logo img:hover {
            transform: scale(1.05);
        }

        .login-wrap {
            min-height: 100vh;
            background: url('vendors/images/login-bg-pattern.png') repeat;
            background-size: 50px 50px;
            animation: backgroundMove 20s ease-in-out infinite;
        }

        @keyframes backgroundMove {
            0% { background-position: 0 0; }
            50% { background-position: 50px 50px; }
            100% { background-position: 0 0; }
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        }

        .login-title img {
            transition: transform 0.3s ease;
        }

        .login-title img:hover {
            transform: rotate(5deg) scale(1.1);
        }

        .select-role .btn {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid transparent;
            border-radius: 15px;
            margin: 5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .select-role .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .select-role .btn:hover::before {
            left: 100%;
        }

        .select-role .btn.active {
            background: #007eb6;
            color: white;
            box-shadow: 0 5px 15px rgba(0, 126, 182, 0.3);
            transform: scale(1.05);
        }

        .select-role .btn .icon img {
            transition: transform 0.3s ease;
        }

        .select-role .btn:hover .icon img {
            transform: scale(1.1);
        }

        .input-group.custom {
            margin-bottom: 20px;
            position: relative;
        }

        .input-group.custom input {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .input-group.custom input:focus {
            border-color: #007eb6;
            box-shadow: 0 0 0 3px rgba(0, 126, 182, 0.1);
            background: white;
        }

        .input-group.custom .input-group-text {
            background: #007eb6;
            color: white;
            border: none;
            border-radius: 0 10px 10px 0;
            transition: background 0.3s ease;
        }

        .input-group.custom input:focus + .input-group-append .input-group-text {
            background: #005a8d;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007eb6 0%, #005a8d 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 126, 182, 0.3);
        }

        .alert {
            border-radius: 10px;
            border: none;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .login-menu a {
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.3s ease;
        }

        .login-menu a:hover {
            color: white;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-wrap .row {
                flex-direction: column;
            }

            .col-md-6 {
                margin-bottom: 30px;
            }

            .login-box {
                margin: 20px;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-header box-shadow">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="brand-logo">
                <a href="#">
                    <img src="vendors/images/ecobank-bg.png" alt="">
                </a>
            </div>
			<!-- <div class="login-menu">
				<ul>
					<li><a href="forgot-password.html">Mot de Passe Oublié</a></li>
				</ul>
			</div> -->
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
						</div>                        <?php if (!empty($_SESSION['login_error_message'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($_SESSION['login_error_message'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php unset($_SESSION['login_error_message']); ?>
                        <?php endif; ?>                        <form name="signin" method="post" id="loginForm">
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
                                            <span id="btnLoading" class="loading" style="display: none;"></span>
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
    <script>
        // JavaScript pour une interface interactive
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des boutons de rôle
            const roleBtns = document.querySelectorAll('.select-role .btn');
            roleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    roleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Animation du formulaire de connexion
            const loginForm = document.getElementById('loginForm');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');

            loginForm.addEventListener('submit', function(e) {
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline-block';
                // Le formulaire se soumet normalement
            });

            // Effets visuels sur les inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            });

            // Animation de particules en arrière-plan (optionnel)
            function createParticle() {
                const particle = document.createElement('div');
                particle.style.position = 'absolute';
                particle.style.width = '4px';
                particle.style.height = '4px';
                particle.style.background = 'rgba(255, 255, 255, 0.3)';
                particle.style.borderRadius = '50%';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = '100%';
                particle.style.animation = 'floatUp ' + (Math.random() * 10 + 10) + 's linear infinite';
                document.body.appendChild(particle);

                setTimeout(() => {
                    particle.remove();
                }, 15000);
            }

            // Créer des particules toutes les 2 secondes
            setInterval(createParticle, 2000);

            // CSS pour l'animation des particules
            const style = document.createElement('style');
            style.textContent = `
                @keyframes floatUp {
                    to {
                        transform: translateY(-100vh);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>
