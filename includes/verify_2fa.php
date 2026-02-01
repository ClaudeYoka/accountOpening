<!DOCTYPE html>
<html>
<head>
    <title>Vérification 2FA</title>
    <!-- Site favicon -->
	<link rel="logo1" sizes="180x180" href="vendors/images/logo1.png">
	<link rel="icon" type="image/png" sizes="32x32" href="vendors/images/logo1.png">
	<link rel="icon" type="image/png" sizes="16x16" href="vendors/images/logo1.png">

	<!-- Mobile Specific Metas -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
	<!-- CSS -->
	<link rel="stylesheet" type="text/css" href="vendors/styles/core.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
	<link rel="stylesheet" type="text/css" href="vendors/styles/style.css">

    <style>
        .verify-container {
            text-align: center;
            margin-top: 50px;
        }
    </style>
    
</head>
<body>
    <div class="login-header box-shadow">
		<div class="container-fluid d-flex justify-content-between align-items-center">
			<div class="brand-logo">
				<a href="index">
					<img src="vendors/images/ecobank-bg.png" alt="">
				</a>
			</div>
			<div class="login-menu">
				<ul>
					<li><a href="index">Se Connecter</a></li>
				</ul>
			</div>
		</div>
	</div>
    <div class="verify-container">
        <h2>Authentification à deux facteurs</h2>
        <br>
        <p>Entrez le code à 6 chiffres généré par votre application d'authentification</p>
        
        <form method="post">
            <input type="text" name="twofa_code" placeholder="Code à 6 chiffres" required>
            <input class="btn btn-outline-primary" type="submit" name="verify_2fa" value="Vérifier">
        </form>
    </div>
</body>
</html>
