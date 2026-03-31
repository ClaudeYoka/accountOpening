<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php
if(isset($_POST['new_update']))
{
    // Validation du mot de passe
    $newpassword = $_POST['newpassword'];
    $errors = [];

    // Validation du mot de passe
    if (empty($newpassword)) {
        $errors[] = "Le mot de passe est requis";
    } elseif (strlen($newpassword) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $newpassword)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule et un chiffre";
    }

    if (!empty($errors)) {
        echo "<script>alert('" . implode("\\n", $errors) . "');</script>";
        return;
    }

	$empid=$session_id;
	$hashePassword=password_hash($newpassword, PASSWORD_DEFAULT);


    $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET Password=? WHERE emp_id=?");
    mysqli_stmt_bind_param($stmt, "ss", $hashePassword, $session_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
     	echo "<script>alert('Mot de passe modifer avec succès');</script>";
     	echo "<script type='text/javascript'> document.location = 'staff_profile.php'; </script>";
	} else{
	  die(mysqli_error($conn));
   }

}

if (isset($_POST["update_image"])) {

	$image = $_FILES['image']['name'];

	if(!empty($image)){
		move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/'.$image);
		$location = $image;	
	}
	else {
		echo "<script>alert('Please Select Picture to Update');</script>";
	}

    $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET location=? WHERE emp_id=?");
    mysqli_stmt_bind_param($stmt, "ss", $location, $session_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
     	echo "<script>alert('Photo de profil Modifier avec succès');</script>";
     	echo "<script type='text/javascript'> document.location = 'staff_profile.php'; </script>";
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
						<div class="col-md-12 col-sm-12">
							<div class="title">
								<h4>Profile</h4>
							</div>
							<nav aria-label="breadcrumb" role="navigation">
								<ol class="breadcrumb">
									<li class="breadcrumb-item"><a href="admin_dashboard">Dashboard</a></li>
									<li class="breadcrumb-item active" aria-current="page">Profil</li>
								</ol>
							</nav>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 mb-30">
						<div class="pd-20 card-box height-100-p">

							<?php
								$stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees LEFT JOIN tbldepartments ON tblemployees.Department = tbldepartments.DepartmentShortName WHERE emp_id = ?");
								mysqli_stmt_bind_param($stmt, "s", $session_id);
								mysqli_stmt_execute($stmt);
								$query = mysqli_stmt_get_result($stmt);
								$row = mysqli_fetch_array($query);
								mysqli_stmt_close($stmt);
							?>

							<div class="profile-photo">
								<a href="modal" data-toggle="modal" data-target="#modal" class="edit-avatar"><i class="fa fa-pencil"></i></a>
								<img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" alt="" class="avatar-photo">
								<form method="post" enctype="multipart/form-data">
									<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
										<div class="modal-dialog modal-dialog-centered" role="document">
											<div class="modal-content">
												<div class="weight-500 col-md-12 pd-5">
													<div class="form-group">
														<div class="custom-file">
															<input name="image" id="file" type="file" class="custom-file-input" accept="image/*" onchange="validateImage('file')">
															<label class="custom-file-label" for="file" id="selector">Choisir Image</label>		
														</div>
													</div>
												</div>
												<div class="modal-footer">
													<input type="submit" name="update_image" value="Update" class="btn btn-primary">
													<button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
												</div>
											</div>
										</div>
									</div>
								</form>
							</div>
							<h5 class="text-center h5 mb-0"><?php echo htmlspecialchars($row['FirstName'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['LastName'], ENT_QUOTES, 'UTF-8'); ?></h5>
							<p class="text-center text-muted font-14"><?php echo htmlspecialchars($row['DepartmentName'], ENT_QUOTES, 'UTF-8'); ?></p>
							<div class="profile-info">
								<h5 class="mb-20 h5 text-blue">Informations Personnels</h5>
								<ul>
									<li>
										<span>Email :</span>
										<?php echo htmlspecialchars($row['EmailId'], ENT_QUOTES, 'UTF-8'); ?>
									</li>

								</ul>
							</div>
						</div>
					</div>
					<div class="col-xl-8 col-lg-8 col-md-8 col-sm-12 mb-30">
						<div class="card-box height-100-p overflow-hidden">
							<div class="profile-tab height-100-p">
								<div class="tab height-100-p">
									<ul class="nav nav-tabs customtab" role="tablist">
										<li class="nav-item">
											<a class="nav-link" data-toggle="tab" href="#setting" role="tab">Mot de Passe</a>
										</li>
									</ul>
									<div class="tab-content">
									
										<!-- Setting Tab start -->
										<div class="tab-pane fade height-100-p" id="setting" role="tabpanel">
											<div class="profile-setting">
												<form method="POST" enctype="multipart/form-data">
													<div class="profile-edit-list row">
														<div class="col-md-12"><h4 class="text-blue h5 mb-20">Modifier vos infos personnelles</h4></div>

														<?php
                                                            $stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE emp_id = ?");
                                                            mysqli_stmt_bind_param($stmt, "s", $session_id);
                                                            mysqli_stmt_execute($stmt);
                                                            $query = mysqli_stmt_get_result($stmt);
                                                            $row = mysqli_fetch_array($query);
                                                            mysqli_stmt_close($stmt);
														?>

														<div class="col-md-6 col-sm-12">
															<div class="form-group">
																<label>Nouveau Mot de Passe :</label>
																<input name="newpassword" type="password" placeholder="Saisissez votre mot de passe" class="form-control" required="true" autocomplete="on">
															</div>
														</div>

														<div class="weight-500 col-md-6">
															<div class="form-group">
																<label></label>
																<div class="modal-footer justify-content-center">
																	<button class="btn btn-primary" name="new_update" id="new_update" data-toggle="modal">Nouveau</button>
																</div>
															</div>
														</div>
													</div>
												</form>
											</div>
										</div>
										<!-- Setting Tab End -->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php include('includes/footer.php'); ?>
		</div>
	</div>
	<!-- js -->
	<?php include('includes/scripts.php')?>

	<script type="text/javascript">
		var loader = function(e) {
			let file = e.target.files;

			let show = "<span>Selected file : </span>" + file[0].name;
			let output = document.getElementById("selector");
			output.innerHTML = show;
			output.classList.add("active");
		};

		let fileInput = document.getElementById("file");
		fileInput.addEventListener("change", loader);
	</script>
	<script type="text/javascript">
		 function validateImage(id) {
		    var formData = new FormData();
		    var file = document.getElementById(id).files[0];
		    formData.append("Filedata", file);
		    var t = file.type.split('/').pop().toLowerCase();
		    if (t != "jpeg" && t != "jpg" && t != "png") {
		        alert('Please select a valid image file');
		        document.getElementById(id).value = '';
		        return false;
		    }
		    if (file.size > 1050000) {
		        alert('Max Upload size is 1MB only');
		        document.getElementById(id).value = '';
		        return false;
		    }

		    return true;
		}
	</script>
</body>
</html>