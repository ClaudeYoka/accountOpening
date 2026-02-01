<?php
session_start();
include('includes/config.php');

if(isset($_POST['change_password'])) {
    $new_password = $_POST['new_password'];
    $emp_id = $_SESSION['alogin'];

    // Hasher le mot de passe avec password_hash
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe et le statut
    $sql = "UPDATE tblemployees SET Password='$hashedPassword', password_changed=1 WHERE emp_id='$emp_id'";
    mysqli_query($conn, $sql);

    echo "<script>alert('Mot de passe changé avec succès.');</script>";
    echo "<script type='text/javascript'> document.location = 'index.php'; </script>";
} 
?>

<!DOCTYPE html>
<html>
<head>
	<!-- Basic Page Info -->
	<meta charset="utf-8">
	<title>Changer Mot de passe</title>

	<!-- Site favicon -->
	<link rel="logo1" sizes="180x180" href="vendors/images/logo1.png">
	<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/logo1.png">
	<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/logo1.png">

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
                            <div class="input-group custom">
                                <input class="form-control form-control-lg" type="password" name="new_password" placeholder=" Nouveau mot de passe" required>
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
