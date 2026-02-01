<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php $get_id = $_GET['edit']; ?>
<?php
	if(isset($_POST['add_staff']))
	{
	
	$fname=$_POST['firstname'];
	$lname=$_POST['lastname'];
	$email=$_POST['email'];
	$department=$_POST['department'];
	$user_role=$_POST['user_role'];
	$phonenumber=$_POST['phonenumber'];
	$phonenumber2=$_POST['phonenumber2'];
	$Position=$_POST['Position'];
	$staff_id=$_POST['staff_id'];

	$result = mysqli_query($conn,"UPDATE tblemployees set FirstName='$fname', LastName='$lname', EmailId='$email', Department='$department', role='$user_role', Phonenumber='$phonenumber', Phonenumber2='$phonenumber2', Position='$Position' where emp_id='$get_id'         
		");
	if ($result) {
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