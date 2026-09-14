<?php 
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
?>


<body>


	<?php include('includes/preloader.php')?>


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

						<?php 
							$query= mysqli_query($conn,"SELECT * from tblemployees where emp_id = '$session_id'");
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
					<h2 class="text-blue h4">Mes demandes de chéquier des 7 derniers jours</h2>
				</div>
				<div class="pb-20">
					<table class="data-table table hover multiple-select-row nowrap">
						<thead>
							<tr>
								<th class="table-plus">NOM CLIENT</th>
								<th>NUM COMPTE</th>
								<th>QUANTITÉ</th>
								<th>DATE DEMANDE</th>
								<th>STATUT</th>
								<th class="datatable-nosort">ACTION</th>
							</tr>
						</thead>
						<tbody>
							<?php
								$sql = "SELECT id, firstname, account_number, type_compte, etabliss, access, date_enregistrement
										FROM tblcompte
										WHERE emp_id = ?
										AND date_enregistrement >= DATE_SUB(NOW(), INTERVAL 7 DAY)
										ORDER BY date_enregistrement DESC";
								$stmt = mysqli_prepare($conn, $sql);
								mysqli_stmt_bind_param($stmt, 's', $session_id);
								mysqli_stmt_execute($stmt);
								$results = mysqli_stmt_get_result($stmt);
								$cnt = 1;
								if ($results && mysqli_num_rows($results) > 0) {
									while ($result = mysqli_fetch_assoc($results)) {
							?>
							<tr>
								<td><?php echo h($result['firstname']); ?></td>
								<td><?php echo h($result['account_number']); ?></td>
								<td><?php echo h($result['etabliss']); ?></td>
								<td><?php echo h(!empty($result['date_enregistrement']) ? date('d-m-Y H:i', strtotime($result['date_enregistrement'])) : ''); ?></td>
								<?php
									$status = strtolower(trim($result['access'] ?: 'encours'));
									$statusStyles = [
										'encours' => ['label' => 'En cours', 'background' => '#fff0d9', 'color' => '#a85d00'],
										'reçu' => ['label' => 'Reçu', 'background' => '#d9f3f0', 'color' => '#087f73'],
										'livré' => ['label' => 'Livré', 'background' => '#e3f5e6', 'color' => '#237a35'],
										'donné' => ['label' => 'Donné', 'background' => '#e3f5e6', 'color' => '#237a35'],
										'prestataire' => ['label' => 'Prestataire', 'background' => '#e3eefa', 'color' => '#265ed7']
									];
									$statusStyle = $statusStyles[$status] ?? ['label' => ucfirst($status), 'background' => '#edf0f2', 'color' => '#52606d'];
								?>
								<td><span style="display:inline-block;padding:6px 12px;border-radius:999px;background:<?php echo $statusStyle['background']; ?>;color:<?php echo $statusStyle['color']; ?>;font-weight:600;font-size:12px;"><?php echo h($statusStyle['label']); ?></span></td>

								<td>
									<div class="table-actions">
										<a title="Voir le détail complet" href="chequier_request_detail.php?request_id=<?php echo (int)$result['id']; ?>" data-color="#265ed7">
											<i class="icon-copy dw dw-eye"></i>
										</a>
									</div>
								</td>
							</tr>
							<?php 
									$cnt++;
									} 
										} else {
										echo "<tr><td colspan='6' style='text-align: center; vertical-align: middle;'><div style='display: inline-block;'>
									<img src='../vendors/images/expertise-seo-hero.svg' alt='Aucune Demande pour le moment' style='max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;'/></div></td></tr>";
									}
							?>
						</tbody>
					</table>
				</div>
			</div>
			
		</div>
	</div>

	<!-- <?php include('includes/footer.php')?> -->
	<!-- js -->

	<?php include('includes/scriptJs.php')?>
</body>
</html>