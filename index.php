<?php include('includes/loginController.php') ?>

<!DOCTYPE html>
<html>
<head>
    <!-- Basic Page Info -->
    <meta charset="utf-8">
    <title>AO & KYC</title>
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
                        <form name="signin" method="post">
							<div class="select-role">
                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                    <label class="btn">
                                        <input type="radio" name="options" id="cso" value="cso" checked>
                                        <div class="icon"><img src="vendors/images/person.svg" class="svg" alt=""></div>
                                        <span>Je suis</span>CSO
                                    </label>
                                    <label class="btn">
                                        <input type="radio" name="options" id="CI" value="CI" >
                                        <div class="icon"><img src="vendors/images/briefcase.svg" class="svg" alt=""></div>
                                        <span>Je suis</span>CI
                                    </label>
                                </div>
                            </div>

                            <div class="input-group custom">
                                <input type="text" class="form-control form-control-lg" placeholder="Username" name="username" id="username" required>
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="icon-copy dw dw-user1"></i></span>
                                </div>
                            </div>
                            <div class="input-group custom">
                                <input type="password" class="form-control form-control-lg" placeholder="**************" name="password" id="password" required>
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0">
                                        <input class="btn btn-primary btn-lg btn-block" type="submit" name="signin" id="signin" value="Se Connecter">
                                    </div>
                                </div>
                            </div>
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
</body>
</html>
