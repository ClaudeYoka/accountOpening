<!DOCTYPE html>
<html>
<head>
    <title>Activation 2FA</title>
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
            <p>1. Téléchargez une application 2FA comme Google ou Microsoft Authentificator</p>
            <p>2. Scannez ce QR Code avec votre application</p>
            <p>3. Entrez le code à 6 chiffres généré par l'application ci-dessous</p>
        </div>
        
        <img src="<?php echo $qrUrl; ?>" alt="QR Code pour 2FA" />
        
        <form method="post" class="verify-form">
            <input type="text" name="twofa_code" placeholder="Code à 6 chiffres" required>
            <input type="submit" name="activate_2fa" value="Activer 2FA">
        </form>
    </div>
</body>
</html>
