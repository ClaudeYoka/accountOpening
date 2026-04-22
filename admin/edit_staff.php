<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php require_once('../includes/audit_logger.php')?>
<?php $get_id = $_GET['edit']; ?>
<?php
	if(isset($_POST['add_staff']))
	{
    check_csrf();
    // Validation des données du personnel
    $fname = trim($_POST['firstname']);
    $lname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $user_role = trim($_POST['user_role']);
    $phonenumber = trim($_POST['phonenumber']);
    $phonenumber2 = trim($_POST['phonenumber2']);
    $Position = trim($_POST['Position']);
    $staff_id = trim($_POST['staff_id']);

    $errors = [];

    // Validation du prénom
    if (empty($fname)) {
        $errors[] = "Le prénom est requis";
    } elseif (strlen($fname) < 2 || strlen($fname) > 50) {
        $errors[] = "Le prénom doit contenir entre 2 et 50 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $fname)) {
        $errors[] = "Le prénom contient des caractères invalides";
    }

    // Validation du nom
    if (empty($lname)) {
        $errors[] = "Le nom est requis";
    } elseif (strlen($lname) < 2 || strlen($lname) > 50) {
        $errors[] = "Le nom doit contenir entre 2 et 50 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/", $lname)) {
        $errors[] = "Le nom contient des caractères invalides";
    }

    // Validation de l'email
    if (empty($email)) {
        $errors[] = "L'adresse email est requise";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }

    // Validation du numéro de téléphone
    if (!empty($phonenumber) && !preg_match("/^[0-9+\-\s()]{9,15}$/", $phonenumber)) {
        $errors[] = "Le numéro de téléphone n'est pas valide";
    }

    // Validation du deuxième numéro de téléphone
    if (!empty($phonenumber2) && !preg_match("/^[0-9+\-\s()]{9,15}$/", $phonenumber2)) {
        $errors[] = "Le deuxième numéro de téléphone n'est pas valide";
    }

    // Validation du poste
    if (empty($Position)) {
        $errors[] = "Le poste est requis";
    } elseif (strlen($Position) < 2 || strlen($Position) > 100) {
        $errors[] = "Le poste doit contenir entre 2 et 100 caractères";
    }

    // Validation du rôle
    $valid_roles = ['Admin', 'CI', 'CSO'];
    if (empty($user_role) || !in_array($user_role, $valid_roles)) {
        $errors[] = "Le rôle sélectionné n'est pas valide";
    }

    if (!empty($errors)) {
        echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
        return;
    }

    // Échapper les données pour l'affichage HTML
    $fname = htmlspecialchars($fname, ENT_QUOTES, 'UTF-8');
    $lname = htmlspecialchars($lname, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $department = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');
    $user_role = htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8');
    $phonenumber = htmlspecialchars($phonenumber, ENT_QUOTES, 'UTF-8');
    $phonenumber2 = htmlspecialchars($phonenumber2, ENT_QUOTES, 'UTF-8');
    $Position = htmlspecialchars($Position, ENT_QUOTES, 'UTF-8');
    $staff_id = htmlspecialchars($staff_id, ENT_QUOTES, 'UTF-8');

    $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET FirstName=?, LastName=?, EmailId=?, Department=?, role=?, Phonenumber=?, Phonenumber2=?, Position=? WHERE emp_id=?");
    mysqli_stmt_bind_param($stmt, "sssssssss", $fname, $lname, $email, $department, $user_role, $phonenumber, $phonenumber2, $Position, $get_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

	if ($result) {
		// Log successful staff update
		audit_log_user_action($conn, 'updated', $_SESSION['alogin'], [
			'updated_user_id' => $get_id,
			'updated_fields' => ['FirstName', 'LastName', 'EmailId', 'Department', 'role', 'Phonenumber', 'Phonenumber2', 'Position']
		]);

		echo "<script>alert('Record Successfully Updated');</script>";
		echo "<script type='text/javascript'> document.location = 'staff.php'; </script>";
	} else{
			die(mysqli_error($conn));
	}
		
}

?>

<body>
	<div class="pre-loader">
		<div class="pre-loader-box">
			<div class="loader-logo"><img src="../vendors/images/ecobank-bg3.png" alt=""></div>
			<div class='loader-progress' id="progress_div">
				<div class='bar' id='bar1'></div>
			</div>
			<div class='percent' id='percent1'>0%</div>
			<div class="loading-text">
				Loading...
			</div>
		</div>
	</div>

	<?php include('includes/navbar.php')?>

	<?php include('includes/right_sidebar.php')?>

	<?php include('includes/left_sidebar.php')?>

	<div class="mobile-menu-overlay"></div>

	<div class="mobile-menu-overlay"></div>

	<div class="main-container">
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
				<div class="page-header">
					<div class="row">
						<div class="col-md-6 col-sm-12">
							<div class="title">
								<h4>Page modifications</h4>
							</div>
							<nav aria-label="breadcrumb" role="navigation">
								<ol class="breadcrumb">
									<li class="breadcrumb-item"><a href="index">Dashboard</a></li>
									<li class="breadcrumb-item active" aria-current="page">Modification</li>
								</ol>
							</nav>
						</div>
					</div>
				</div>

				<div class="pd-20 card-box mb-30">
					<div class="clearfix">
						<div class="pull-left">
							<h4 class="text-blue h4">Modification des infos staff</h4>
							<p class="mb-20"></p>
						</div>
					</div>
					<div class="wizard-content">
						<form method="post" action="">
			<?php echo get_csrf_field(); ?>
							<section>
								<?php
									$query = mysqli_query($conn,"select * from tblemployees where emp_id = '$get_id' ");
									$row = mysqli_fetch_array($query);
									?>

								<div class="row">
									<div class="col-md-5 col-sm-12">
										<div class="form-group">
											<label >Nom (S) :</label>
											<input name="firstname" type="text" class="form-control wizard-required" readonly autocomplete="off" value="<?php echo $row['FirstName']; ?>">
										</div>
									</div>
									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label >Prénom (S) :</label>
											<input name="lastname" type="text" class="form-control" readonly autocomplete="off" value="<?php echo $row['LastName']; ?>">
										</div>
									</div>
									<div class="col-md-3 col-sm-12">
										<div class="form-group">
											<label>Adresse Mail :</label>
											<input name="email" type="email" class="form-control"readonly autocomplete="off" value="<?php echo $row['EmailId']; ?>">
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label >staff Poste :</label>
											<input name="Position" type="text" class="form-control wizard-required" required="true" autocomplete="off" value="<?php echo $row['Position'] ?>">
										</div>
									</div>
									

									<div class="col-md-2 col-sm-12">
										<div class="form-group">
											<label>Téléphone :</label>
											<input name="phonenumber" type="number" class="form-control" required="true" autocomplete="off"value="<?php echo $row['Phonenumber']; ?>">
										</div>
									</div>
									<div class="col-md-2 col-sm-12">
										<div class="form-group">
											<label>Téléphone 2 (OPT) :</label>
											<input name="phonenumberZ" type="number" class="form-control" required="true" autocomplete="off"value="<?php echo $row['Phonenumber']; ?>">
										</div>
									</div>

									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label>Division :</label>
											<select name="department" class="custom-select form-control" required="true" autocomplete="off">
												<?php
													$query_staff = mysqli_query($conn,"select * from tblemployees join  tbldepartments where emp_id = '$get_id'");
													$row_staff = mysqli_fetch_array($query_staff);
												?>
												<option value="<?php echo $row_staff['DepartmentShortName']; ?>"><?php echo $row_staff['DepartmentName']; ?></option>
													<?php
													$query = mysqli_query($conn,"select * from tbldepartments where DepartmentShortName = '$session_depart'");
													while($row = mysqli_fetch_array($query)){
													
													?>
													<option value="<?php echo $row['DepartmentShortName']; ?>"><?php echo $row['DepartmentName']; ?></option>
													<?php } ?>
											</select>
										</div>
									</div>

								</div>
								
								
								<?php
									$query = mysqli_query($conn,"select * from tblemployees where emp_id = '$get_id' ");
									$new_row = mysqli_fetch_array($query);
									?>
								<div class="row">
									
									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label>Rôle :</label>
											<select name="user_role" class="custom-select form-control" readonly autocomplete="off">
												<option value="<?php echo $new_row['role']; ?>"><?php echo $new_row['role']; ?></option>
												<option value="cso">CSO</option>
												<option value="CI">CI</option>
											</select>
										</div>
									</div>
									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label>Password :</label>
											<input name="password" type="password" placeholder="**********" class="form-control" readonly required="true" autocomplete="off" value="<?php echo $row['Password']; ?>">
										</div>
									</div>
									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label>Nouveau Mot de Passe :</label>
											<input name="newpassword" type="password" placeholder="Saisissez votre mot de passe" class="form-control" required="true" autocomplete="on">
										</div>
									</div>

									<div class="col-md-4 col-sm-12">
										<div class="form-group">
											<label style="font-size:16px;"><b></b></label>
											<div class="modal-footer justify-content-center">
												<button class="btn btn-primary" name="add_staff" id="add_staff" data-toggle="modal">Modifier&nbsp;infos</button>
											</div>
										</div>
									</div>

								</div>

							</section>
						</form>
					</div>
				</div>

			</div>
			<?php include('includes/footer.php'); ?>
		</div>
	</div>
	<!-- js -->
	<?php include('includes/scripts.php')?>
</body>
</html>