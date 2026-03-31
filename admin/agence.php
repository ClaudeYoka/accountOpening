<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php include('../includes/flash.php')?>
<?php include('../includes/audit_helpers.php')?>

<?php
	if (isset($_GET['delete'])) {
		$Agence_id = $_GET['delete'];
		$stmt = mysqli_prepare($conn, "DELETE FROM tblagences WHERE id = ?");
		mysqli_stmt_bind_param($stmt, "i", $Agence_id);
		$result = mysqli_stmt_execute($stmt);
		mysqli_stmt_close($stmt);
		if ($result) {
			// Audit logging for agency deletion
			log_admin_action('delete_agency', $Agence_id, [
				'action' => 'delete',
				'table' => 'tblagences'
			]);
			set_flash_message('Agence supprimée avec succès', 'success');
			header('Location: agence');
			exit;
		}
	}
?>

<?php
	if(isset($_POST['add']))
{
    // Validation des données d'agence
    $agename = trim($_POST['Agencename']);
    $ageshortname = trim($_POST['Agenceshortname']);

    $errors = [];

    // Validation du nom d'agence
    if (empty($agename)) {
        $errors[] = "Le nom de l'agence est requis";
    } elseif (strlen($agename) < 2 || strlen($agename) > 100) {
        $errors[] = "Le nom de l'agence doit contenir entre 2 et 100 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ0-9\s\-&]+$/", $agename)) {
        $errors[] = "Le nom de l'agence contient des caractères invalides";
    }

    // Validation du nom court d'agence
    if (empty($ageshortname)) {
        $errors[] = "Le nom court de l'agence est requis";
    } elseif (strlen($ageshortname) < 2 || strlen($ageshortname) > 10) {
        $errors[] = "Le nom court doit contenir entre 2 et 10 caractères";
    } elseif (!preg_match("/^[A-Z0-9\-]+$/", $ageshortname)) {
        $errors[] = "Le nom court ne peut contenir que des lettres majuscules, chiffres et tirets";
    }

    if (!empty($errors)) {
        set_flash_message(implode('<br>', $errors), 'error');
        header('Location: agence');
        exit;
    }

    // Échapper les données pour l'affichage HTML
    $agename = htmlspecialchars($agename, ENT_QUOTES, 'UTF-8');
    $ageshortname = htmlspecialchars(strtoupper($ageshortname), ENT_QUOTES, 'UTF-8');

    $stmt = mysqli_prepare($conn, "SELECT * FROM tblagences WHERE AgenceName = ?");
    mysqli_stmt_bind_param($stmt, "s", $agename);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($query);
    mysqli_stmt_close($stmt);
		
    if ($count > 0){
		set_flash_message("Cette agence existe déjà dans la base de données", 'error');
		header('Location: agence');
		exit;
		} else {
        $stmt = mysqli_prepare($conn, "INSERT INTO tblagences (AgenceName, AgenceShortName) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $agename, $ageshortname);
        $query = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

		if ($query) {
			// Audit logging for agency creation
			$new_agency_id = mysqli_insert_id($conn);
			log_admin_action('create_agency', $new_agency_id, [
				'agency_name' => $agename,
				'agency_short_name' => $ageshortname
			]);
			set_flash_message('Agence ajoutée avec succès', 'success');
			header('Location: agence');
			exit;
		}
    }

}

?>
<body>
	<?php render_flash_message(); ?>

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

	<div class="main-container">
		<div class="pd-ltr-20 xs-pd-20-10">
			<div class="min-height-200px">
					<div class="page-header">
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="title">
									<h4>Agence</h4>
								</div>
								<nav aria-label="breadcrumb" role="navigation">
									<ol class="breadcrumb">
										<li class="breadcrumb-item"><a href="index">Dashboard</a></li>
										<li class="breadcrumb-item active" aria-current="page">Page Agence </li>
									</ol>
								</nav>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-lg-4 col-md-6 col-sm-12 mb-30">
							<div class="card-box pd-30 pt-10 height-100-p">
								<h2 class="mb-30 h4">Nouvelle Agence</h2>
								<section>
									<form name="save" method="post">
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label >Agence</label>
												<input name="Agencename" type="text" class="form-control" required="true" autocomplete="off">
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
												<label>SIGNE</label>
												<input name="Agenceshortname" type="text" class="form-control" required="true" autocomplete="off" style="text-transform:uppercase">
											</div>
										</div>
									</div>
									<div class="col-sm-12 text-right">
										<div class="dropdown">
											<input class="btn btn-primary" type="submit" value="ENREGISTRER" name="add" id="add">
										</div>
									</div>
									</form>
								</section>
							</div>
						</div>
						
						<div class="col-lg-8 col-md-6 col-sm-12 mb-30">
							<div class="card-box pd-30 pt-10 height-100-p">
								<h2 class="mb-30 h4">Liste des Agences</h2>
								<div class="pb-20">
									<table class="data-table table stripe hover nowrap">
										<thead>
										<tr>
											<th class="table-plus">NOM AGENCE</th>
											<th>CODE AGENCE</th>
											<th class="datatable-nosort">ACTION</th>
										</tr>
										</thead>
										<tbody>

											<?php $sql = "SELECT * from tblagences";
												$query = $dbh -> prepare($sql);
												$query->execute();
												$results=$query->fetchAll(PDO::FETCH_OBJ);
												$cnt=1;
												if($query->rowCount() > 0)
												{
												foreach($results as $result)
												{
											?>

											<tr>
	                                            <td><?php echo htmlentities($result->AgenceName);?></td>
	                                            <td><?php echo htmlentities($result->AgenceShortName);?></td>
												<td>
													<div class="table-actions">
														<a href="edit_agence?edit=<?php echo htmlentities($result->id);?>" data-color="#265ed7"><i class="icon-copy dw dw-edit2"></i></a>
														<a href="agence?delete=<?php echo htmlentities($result->id);?>" data-color="#e95959"><i class="icon-copy dw dw-delete-3"></i></a>
													</div>
												</td>
											</tr>

											<?php $cnt++;} }?>

										</tbody>
									</table>
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
</body>
</html>