<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<body>

	<?php include('includes/navbar.php')?>

	<?php include('includes/right_sidebar.php')?>

	<?php include('includes/left_sidebar.php')?>

	<div class="mobile-menu-overlay"></div>

	<div class="main-container">
		<div class="pd-ltr-20">
			<div class="card-box pd-20 height-100-p mb-30">
				<div class="row align-items-center">
					<div class="col-md-4 user-icon">
						<img src="../vendors/images/indexlog.png" alt="">
					</div>
					<div class="col-md-8">

						<?php $query= mysqli_query($conn,"SELECT * from tblemployees where emp_id = '$session_id'");
								$row = mysqli_fetch_array($query);
						?>

						<h4 class="font-20 weight-500 mb-10 text-capitalize">
							BIENVENUE <div class="weight-600 font-30 text-blue"><?php echo htmlspecialchars($row['FirstName'], ENT_QUOTES, 'UTF-8') . " " . htmlspecialchars($row['LastName'], ENT_QUOTES, 'UTF-8'); ?>,</div>
						</h4>
						<p class="font-18 max-width-600">Vous êtes sur l'application d'ouverture de Compte d'Ecobank .</p>
					</div>
				</div>
			</div>

			<div class="card-box mb-30">
				<div class="pd-20">
					<h2 class="text-blue h4">Mes Comptes Ouverts au cours de 7 derniers jours</h2>
				</div>
				<div class="pb-20">
					<table class="data-table table hover multiple-select-row nowrap">
						<thead>
							<tr>
								<th class="table-plus">NUM COMPTE</th>
								<th>NOM & PRENOM</th>
								<th>TYPE COMPTE</th>
								<th>DATE OUVERTURE </th>
								<!-- <th>DECISION RH</th> -->
								<th class="datatable-nosort">ACTION</th>
							</tr>
						</thead>
						<tbody>
							<?php
								$sql = "SELECT * from ecobank_form_submissions where emp_id = '$session_id' AND ecobank_form_submissions.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY ecobank_form_submissions.created_at desc";
								$query = $dbh->prepare($sql);
								$query->execute();
								$results = $query->fetchAll(PDO::FETCH_OBJ);
								$cnt = 1;
								if ($query->rowCount() > 0) {
									foreach ($results as $result) {
							?>
							<tr>
								<td><?php echo h($result->account_number); ?></td>
								<td><?php echo h($result->customer_name); ?></td>
								<td><?php echo h($result->account_type); ?></td>
								<td><?php echo h(!empty($result->created_at) ? date('d-m-Y', strtotime($result->created_at)) : ''); ?></td>
					
								<td>
									<div class="table-actions">
										<a title="VIEW" href="ecobank_submission_view?id=<?php echo h($result->id); ?>" data-color="#265ed7">
											<i class="icon-copy dw dw-eye"></i>
										</a>
									</div>
								</td>
							</tr>
							<?php 
									$cnt++;
									} 
								} else {
									echo "<tr><td colspan='7' style='text-align: center; vertical-align: middle;'><div style='display: inline-block;'>
									<img src='../vendors/images/expertise-seo-hero.svg' alt='Aucune Demande pour le moment' style='max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;'/></div></td></tr>";
									}
							?>
						</tbody>
					</table>
				</div>
			</div>
			
		</div>
	</div>

	<?php include('includes/footer.php')?>
	<!-- js -->

	<?php include('includes/scriptJs.php')?>
</body>
</html>