<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
	if (isset($_GET['delete'])) {
		$delete = intval($_GET['delete']);

		// Démarrer une transaction pour assurer la consistance
		mysqli_begin_transaction($conn);
		$ok = true;
		$error_msg = '';

		// Supprimer d'abord les enregistrements dépendants pour éviter les erreurs de FK
		if (!mysqli_query($conn, "DELETE FROM tbl_logins WHERE emp_id = $delete")) {
			$ok = false;
			$error_msg = mysqli_error($conn);
		}

		if ($ok && mysqli_query($conn, "DELETE FROM tblnotification WHERE emp_id = $delete") === false) {
			$ok = false;
			$error_msg = mysqli_error($conn);
		}

		// Ajouter ici d'autres suppressions si nécessaire (ex: tbl_message, etc.)

		// Si tout est OK, supprimer l'utilisateur
		if ($ok) {
			if (!mysqli_query($conn, "DELETE FROM tblemployees WHERE emp_id = $delete")) {
				$ok = false;
				$error_msg = mysqli_error($conn);
			}
		}

		if ($ok) {
			mysqli_commit($conn);
			echo "<script>alert('Utilisateur Supprimé avec succès');</script>";
			echo "<script type='text/javascript'> document.location = 'staff'; </script>";
		} else {
			mysqli_rollback($conn);
			// Loguer et informer l'utilisateur
			error_log("Échec suppression utilisateur $delete : " . $error_msg);
			echo "<script>alert('Erreur lors de la suppression : Le compte est lié à d\\'autres enregistrements ou une erreur est survenue.');</script>";
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

	<div class="main-container">
		<div class="pd-ltr-20">
			<div class="title pb-20">
				<h2 class="h3 mb-0">Administration des Utilisateurs</h2>
			</div>
			
			

			<div class="card-box mb-30">
				<div class="pd-20">
						<h2 class="text-blue h4">LISTE DES UTILISATEURS</h2>
					</div>
				<div class="pb-20">
					<table class="table hover multiple-select-row data-table-export nowrap">
						<thead>
							<tr>
								<th class="table-plus">NOM & PRENOM</th>
								<th>EMAIL</th>
								<th>DEPARTEMENT</th>
								<th>AGENCE</th>
								<th class="datatable-nosort">ACTION</th>
							</tr>
						</thead>
						<tbody>
							<tr>

								<?php
									$teacher_query = mysqli_query($conn,"SELECT * from tblemployees LEFT JOIN tbldepartments ON tblemployees.Department = tbldepartments.DepartmentShortName where role != 'Admin' ORDER BY tblemployees.emp_id");
									while ($row = mysqli_fetch_array($teacher_query)) {
									$id = $row['emp_id'];
									
		                    	?>

								<td class="table-plus">
									<div class="name-avatar d-flex align-items-center">
										<div class="avatar mr-2 flex-shrink-0">
											<img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" class="border-radius-100 shadow" width="40" height="40" alt="">
										</div>
										<div class="txt">
											<div class="weight-600"><?php echo htmlspecialchars($row['FirstName'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['LastName'], ENT_QUOTES, 'UTF-8'); ?></div>
										</div>
									</div>
								</td>
								<td><?php echo htmlspecialchars($row['EmailId'], ENT_QUOTES, 'UTF-8'); ?></td>
	                            <td><?php echo htmlspecialchars($row['DepartmentName'], ENT_QUOTES, 'UTF-8'); ?></td>
								<td><?php echo htmlspecialchars($row['AgenceShortName'], ENT_QUOTES, 'UTF-8'); ?></td>
								<td>
									<div class="dropdown">
										<a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
											<i class="dw dw-more"></i>
										</a>
										<div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
											<!-- <a class="dropdown-item" href="chat.php?sender=<?php echo $session_id; ?>&receiver=<?php echo $row['emp_id']; ?>"><i class="micon dw dw-chat3"></i> Chat staff</a> -->
											<a class="dropdown-item" href="edit_staff?edit=<?php echo $row['emp_id'];?>"><i class="dw dw-edit2"></i> Edit</a>
											<a class="dropdown-item" href="staff?delete=<?php echo $row['emp_id'] ?>"><i class="dw dw-delete-3"></i> Delete</a>
										</div>
									</div>
								</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>

			<?php include('includes/footer.php'); ?>
		</div>
	</div>
	<!-- js -->

	<?php include('includes/scriptJs.php')?>
</body>
</html>