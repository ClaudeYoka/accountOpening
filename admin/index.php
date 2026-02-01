<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
	if (isset($_GET['delete'])) {
		$delete = $_GET['delete'];
		$sql = "DELETE FROM tblemployees where emp_id = ".$delete;
		$result = mysqli_query($conn, $sql);
		if ($result) {
			echo "<script>alert('staff Supprimé avec succès');</script>";
			echo "<script type='text/javascript'> document.location = 'staff.php'; </script>";
			
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
				<h2 class="h3 mb-0">NOMBRE DE COMPTES PAR AGENCE</h2>
			</div>
			<div class="row pb-10">
				<?php
					// Définir les agences avec leurs infos
					$agencies = array(
						array('code' => 'T31', 'name' => 'Agence T31 - SIEGE', 'icon' => 'fa-building', 'color' => '#D32F2F'),
						array('code' => 'T32', 'name' => 'Agence T32 - LUMUMBA', 'icon' => 'fa-user-tie', 'color' => '#1976D2'),
						array('code' => 'T33', 'name' => 'Agence T33 - ATLANTIC', 'icon' => 'fa-cogs', 'color' => '#0aadb3'),
						array('code' => 'T34', 'name' => 'Agence T34 - POTO-POTO', 'icon' => 'fa-users', 'color' => '#F57C00'),
						array('code' => 'T38', 'name' => 'Agence T38 - DOLISIE', 'icon' => 'fa-cogs', 'color' => '#f8cd0f'),
						array('code' => 'T39', 'name' => 'Agence T39 - OUESSO', 'icon' => 'fa-cogs', 'color' => '#7B1FA2'),
						array('code' => 'T41', 'name' => 'Agence T41 - BACONGO', 'icon' => 'fa-briefcase', 'color' => '#388E3C'),

					);

					foreach ($agencies as $agency) {
						$query = mysqli_query($conn, "SELECT COUNT(*) AS account_number FROM ecobank_form_submissions WHERE branch_code = '" . $agency['code'] . "'");
						$result = mysqli_fetch_assoc($query);
						$count = $result['account_number'];
				?>
				<div class="col-xl-3 col-lg-4 col-md-6 mb-30">
					<div class="card-box height-100-p widget-style1 agency-card" style="border-top: 4px solid <?php echo $agency['color']; ?>; transition: all 0.3s ease;">
						<div class="d-flex flex-wrap align-items-center justify-content-between">
							<div class="widget-data">
								<div class="h4 mb-0" style="color: <?php echo $agency['color']; ?>; font-weight: 700; font-size: 28px;"><?php echo $count; ?></div>
								<div class="weight-600 font-14" style="color: #666;"><?php echo $agency['name']; ?></div>
							</div>
							<div class="widget-icon">
								<div class="icon" style="background: linear-gradient(135deg, <?php echo $agency['color']; ?>20 0%, <?php echo $agency['color']; ?>10 100%); border-radius: 12px; padding: 15px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="icon-copy fa <?php echo $agency['icon']; ?>" style="font-size: 24px; color: <?php echo $agency['color']; ?>;"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>

			<div class="card-box mb-30">
				<div class="pd-20 d-flex justify-content-between align-items-center">
					<h2 class="text-blue h4 mb-0">LISTE DES CSO</h2>
					<div class="filter-group d-flex gap-2">
						<div class="form-group mb-0">
							<label class="mb-2" style="font-weight: 600; font-size: 12px; color: #666;">Mois :</label>
							<select id="filter_month" class="form-control" style="width: 100px; height: 40px; border-radius: 6px;">
								<option value="">Tous</option>
								<?php 
									$current_month = date('m');
									for ($m = 1; $m <= 12; $m++) {
										$selected = ($m == $current_month) ? 'selected' : '';
										echo '<option value="' . str_pad($m, 2, '0', STR_PAD_LEFT) . '" ' . $selected . '>' . strftime('%B', mktime(0, 0, 0, $m, 1)) . '</option>';
									}
								?>
							</select>
						</div>
						<div class="form-group mb-0">
							<label class="mb-2" style="font-weight: 600; font-size: 12px; color: #666;">Année :</label>
							<select id="filter_year" class="form-control" style="width: 100px; height: 40px; border-radius: 6px;">
								<option value="">Toutes</option>
								<?php 
									$current_year = date('Y');
									for ($y = $current_year; $y >= $current_year - 5; $y--) {
										$selected = ($y == $current_year) ? 'selected' : '';
										echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
									}
								?>
							</select>
						</div>
						<button type="button" id="filter_btn" class="btn btn-sm" style="background: #D32F2F; color: white; border-radius: 6px; margin-top: 26px; border: none; padding: 8px 16px;">
							<i class="fa fa-filter"></i> Filtrer
						</button>
					</div>
				</div>
				<div class="pb-20">
					<table class="table hover multiple-select-row data-table-export nowrap">
						<thead>
							<tr>
								<th class="table-plus">NOM & PRÉNOM</th>
								<th>EMAIL</th>
								<th>AGENCE</th>
								<th>COMPTES CRÉÉS (2026)</th>
							</tr>
						</thead>
						<tbody id="cso-table-body">
							<?php
								$current_year = date('Y');
								$current_month = date('m');
								
								// Construire les conditions de filtre
								$month_filter = '';
								$year_filter = '';
								$where_clause = "te.role = 'cso'";
								
								// Appliquer les filtres uniquement si des paramètres sont passés
								if (!empty($_GET['filter_month']) || !empty($_GET['filter_year'])) {
									if (!empty($_GET['filter_month'])) {
										$month_filter = " AND MONTH(efs.created_at) = '" . intval($_GET['filter_month']) . "'";
									}
									
									if (!empty($_GET['filter_year'])) {
										$year_filter = " AND YEAR(efs.created_at) = '" . intval($_GET['filter_year']) . "'";
									}
									
									$teacher_query = mysqli_query($conn, "
										SELECT 
											te.emp_id,
											te.FirstName,
											te.LastName,
											te.EmailId,
											te.location,
											te.AgenceShortName,
											COUNT(efs.id) as account_count
										FROM tblemployees te
										LEFT JOIN ecobank_form_submissions efs ON te.emp_id = efs.emp_id
										WHERE " . $where_clause . $month_filter . $year_filter . "
										GROUP BY te.emp_id, te.FirstName, te.LastName, te.EmailId, te.location, te.AgenceShortName
										ORDER BY account_count DESC, te.FirstName ASC
									") or die(mysqli_error($conn));
								} else {
									// Pas de filtre: afficher tous les CSO avec le nombre de comptes de l'année courante
									$teacher_query = mysqli_query($conn, "
										SELECT 
											te.emp_id,
											te.FirstName,
											te.LastName,
											te.EmailId,
											te.location,
											te.AgenceShortName,
											COUNT(efs.id) as account_count
										FROM tblemployees te
										LEFT JOIN ecobank_form_submissions efs ON te.emp_id = efs.emp_id AND YEAR(efs.created_at) = $current_year
										WHERE " . $where_clause . "
										GROUP BY te.emp_id, te.FirstName, te.LastName, te.EmailId, te.location, te.AgenceShortName
										ORDER BY account_count DESC, te.FirstName ASC
									") or die(mysqli_error($conn));
								}
								
								while ($row = mysqli_fetch_array($teacher_query)) {
									$account_count = intval($row['account_count']);
							?>
							<tr>
								<td class="table-plus">
									<div class="name-avatar d-flex align-items-center">
										<div class="avatar mr-2 flex-shrink-0">
											<img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" class="border-radius-100 shadow" width="40" height="40" alt="">
										</div>
										<div class="txt">
											<div class="weight-600"><?php echo $row['FirstName'] . " " . $row['LastName']; ?></div>
										</div>
									</div>
								</td>
								<td><?php echo $row['EmailId']; ?></td>
								<td><?php echo $row['AgenceShortName']; ?></td>
								<td>
									<span class="badge" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white; padding: 8px 12px; border-radius: 20px; font-weight: 600;">
										<?php echo $account_count; ?>
									</span>
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

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const filterBtn = document.getElementById('filter_btn');
			const filterMonth = document.getElementById('filter_month');
			const filterYear = document.getElementById('filter_year');
			
			// Récupérer les paramètres actuels de l'URL
			const urlParams = new URLSearchParams(window.location.search);
			const currentMonth = urlParams.get('filter_month') || '';
			const currentYear = urlParams.get('filter_year') || '';
			
			// Appliquer les valeurs actuelles aux sélecteurs (seulement si des filtres sont actifs)
			if (currentMonth || currentYear) {
				if (currentMonth) {
					filterMonth.value = currentMonth;
				}
				if (currentYear) {
					filterYear.value = currentYear;
				}
			} else {
				// Réinitialiser les sélecteurs si pas de filtre
				filterMonth.value = '';
				filterYear.value = '';
			}
			
			// Événement du bouton Filtrer
			filterBtn.addEventListener('click', function() {
				const month = filterMonth.value;
				const year = filterYear.value;
				
				// Appliquer le filtre uniquement si au moins un critère est sélectionné
				if (month || year) {
					let url = window.location.pathname;
					const params = new URLSearchParams();
					
					if (month) params.append('filter_month', month);
					if (year) params.append('filter_year', year);
					
					if (params.toString()) {
						url += '?' + params.toString();
					}
					
					window.location.href = url;
				} else {
					// Si aucun filtre, retourner à la page sans paramètres
					window.location.href = window.location.pathname;
				}
			});

			// Permettre le filtrage avec Entrée dans les inputs
			filterMonth.addEventListener('keypress', function(e) {
				if (e.key === 'Enter') filterBtn.click();
			});
			filterYear.addEventListener('keypress', function(e) {
				if (e.key === 'Enter') filterBtn.click();
			});

			// Ajouter la classe d'animation hover aux cartes
			const agencyCards = document.querySelectorAll('.agency-card');
			agencyCards.forEach(card => {
				card.addEventListener('mouseenter', function() {
					this.style.boxShadow = '0 8px 24px rgba(0, 0, 0, 0.12)';
					this.style.transform = 'translateY(-4px)';
				});
				card.addEventListener('mouseleave', function() {
					this.style.boxShadow = '';
					this.style.transform = '';
				});
			});
		});
	</script>
</body>
</html>