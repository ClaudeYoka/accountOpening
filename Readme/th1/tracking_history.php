<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<body>
	
	<?php include('includes/preloader.php')?>

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
                                <h4>MON HISTORIQUE</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"></a>Historique</li>
                                    <li class="breadcrumb-item active" aria-current="page">Congé</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
							<div class="dropdown show">
								<a class="btn btn-secondary" id="daysButton">Calcul</a>
							</div>
						</div>
                    </div>
                </div>


				<div class="card-box mb-30">
					<div class="pd-20">
						<h2 class="text-blue h4">TOUTES MES DEMANDES</h2>
					</div>
					<div class="pb-20">
						<table class="data-table table hover multiple-select-row nowrap">
							<thead>
								<tr>
									<th class="table-plus datatable-nosort">TYPE CONGE</th>
									<th>DATE DEPART</th>
									<th>DATE RETOUR</th>
									<th>DEMANDE</th>
									<th>RELICAT</th>
									<th>DECISION HEAD </th>
									<th>DECISION RH</th>
									<th class="datatable-nosort">ACTION</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<?php 
										
										// Récupérer les demandes de congé
										if (isset($_POST['search_leave'])) {
											$from_date = $_POST['from_date'];
											$to_date = $_POST['to_date'];

											// Requête pour récupérer les demandes de congé dans l'intervalle de dates
											$sql = "SELECT * FROM tblleave WHERE empid = '$session_id' AND FromDate >= :from_date AND ToDate <= :to_date";
											$query = $dbh->prepare($sql);
											$query->bindParam(':from_date', $from_date);
											$query->bindParam(':to_date', $to_date);
										} else {
											// Obtenir l'année en cours
												$current_year = date('Y');

												// Requête pour afficher les demandes de congé de l'année en cours
												$sql = "SELECT * FROM tblleave WHERE empid = :empid AND YEAR(FromDate) = :current_year";
												$query = $dbh->prepare($sql);
												$query->bindParam(':empid', $session_id);
												$query->bindParam(':current_year', $current_year);
												$query->execute();
												$results = $query->fetchAll(PDO::FETCH_OBJ);
										}

										$query->execute();
										$results = $query->fetchAll(PDO::FETCH_OBJ);
										$cnt = 1;
										$totalDaysTaken = 0; // Variable pour stocker le total des jours pris
										
										if ($query->rowCount() > 0) {
											foreach ($results as $result) {           
												$totalDaysTaken += $result->num_days; // Additionner les jours pris
									?>  

								  	<td><?php echo h($result->LeaveType);?></td>
                    			  	<td><?php echo h(!empty($result->FromDate) ? date('d-m-Y', strtotime($result->FromDate)) : ''); ?></td>
                    				<td><?php echo h(!empty($result->ToDate) ? date('d-m-Y', strtotime($result->ToDate)) : ''); ?></td>
                                  	<td><?php echo h($result->num_days);?> Jours</td>
									<td><?php echo h($result->RemainingDays);?> Jours</td>
                                  	<td><?php $stats = $result->HodRemarks;
                                       if ($stats == 1) {
                                        ?>
                                           <span style="color: green">Approuvée</span>
                                            <?php } if ($stats == 2) { ?>
                                           <span style="color: red">non Aprouvée</span>
                                            <?php } if ($stats == 0) { ?>
	                                       <span style="color: orange">En cours</span>
	                                       <?php } ?>
                                    </td>
                                    <td><?php $stats = $result->RegRemarks;
                                       if ($stats == 1) {
                                        ?>
                                           <span style="color: green">Approuvée</span>
                                            <?php } if ($stats == 2) { ?>
                                           <span style="color: red">non Aprouvée</span>
                                            <?php } if ($stats == 0) { ?>
	                                       <span style="color: orange">En cours</span>
	                                       <?php } ?>
                                    </td>
								   <td>
									  <div class="table-actions">
										<a title="VIEW" href="view_tracking?leave=<?php echo h($result->id);?>" data-color="#265ed7"><i class="icon-copy dw dw-eye"></i></a>
									  </div>
								   </td>
							</tr>
							<?php  
									$cnt++;
									} 

								} else {
									echo "<tr><td colspan='7' style='text-align: center; vertical-align: middle;'><div style='display: inline-block;'><img src='../vendors/images/expertise-seo-hero.svg' alt='Aucune Demande pour le moment' style='max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;'/></div></td></tr>";
								}
							?>
							
						</tbody>
					</table>
			   </div>
			</div>
		</div>
			<?php include('includes/footer.php'); ?>
		</div>
	</div>
	<!-- js -->
	<script>
		document.getElementById('daysButton').addEventListener('click', function() {
			// Calculer le nombre total de jours de congé pris et le nombre de jours restants
			var totalDaysTaken = <?php echo $totalDaysTaken; ?>;
			var remainingDays = <?php echo $result->RemainingDays; ?>;
			
			// Afficher une fenêtre contextuelle avec les informations
			alert('Vos jours de congé autorisés restants : ' + remainingDays +  '\nTotal de jours de congé demandés : ' + totalDaysTaken );
		});
	</script>

	<?php include('includes/scriptJs.php')?>
</body>
</html>
