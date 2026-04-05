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
			<div class="row pb-10">
				<?php
					// Définir les agences avec leurs infos
					$agencies = array(
						array('code' => 'T31', 'name' => 'T31 - Agence SIEGE', 'icon' => 'fa-building', 'color' => '#D32F2F'),
						array('code' => 'T32', 'name' => 'T32 - Agence LUMUMBA', 'icon' => 'fa-user-tie', 'color' => '#1976D2'),
						array('code' => 'T33', 'name' => 'T33 - Agence ATLANTIC', 'icon' => 'fa-cogs', 'color' => '#0aadb3'),
						array('code' => 'T34', 'name' => 'T34 - Agence POTO-POTO', 'icon' => 'fa-users', 'color' => '#F57C00'),
						array('code' => 'T38', 'name' => 'T38 - Agence DOLISIE', 'icon' => 'fa-cogs', 'color' => '#f8cd0f'),
						array('code' => 'T39', 'name' => 'T39 - Agence OUESSO', 'icon' => 'fa-cogs', 'color' => '#7B1FA2'),
						array('code' => 'T41', 'name' => 'T41 - Agence BACONGO', 'icon' => 'fa-briefcase', 'color' => '#388E3C'),

					);

					
				?>
			
			</div>

			<div class="title pb-20" style="margin-top: 30px;">
				<h2 class="h3 mb-0">DEMANDES DE CHÉQUIERS EN COURS PAR AGENCE</h2>
			</div>
			<div class="row pb-10">
				<?php
					foreach ($agencies as $agency) {
						$chequier_query = mysqli_query($conn, "SELECT COUNT(*) AS chequier_count FROM tblcompte tc
							LEFT JOIN (
								SELECT request_id, status
								FROM chequier_status cs1
								WHERE cs1.changed_at = (
									SELECT MAX(cs2.changed_at)
									FROM chequier_status cs2
									WHERE cs2.request_id = cs1.request_id
								)
							) cs ON tc.id = cs.request_id
						WHERE tc.branch_code COLLATE utf8mb4_0900_ai_ci = '" . mysqli_real_escape_string($conn, $agency['code']) . "'
						AND tc.type_compte IS NOT NULL
						AND tc.type_compte COLLATE utf8mb4_0900_ai_ci != ''
						AND LOWER(COALESCE(cs.status COLLATE utf8mb4_0900_ai_ci, tc.access COLLATE utf8mb4_0900_ai_ci, 'encours' COLLATE utf8mb4_0900_ai_ci)) = 'encours' COLLATE utf8mb4_0900_ai_ci");
						$chequier_count = 0;
						if ($chequier_query) {
							$chequier_result = mysqli_fetch_assoc($chequier_query);
							$chequier_count = $chequier_result['chequier_count'];
						} else {
							error_log("SQL Error in ci/index.php (agency query): " . mysqli_error($conn));
						}
				?>
				<div class="col-xl-3 col-lg-4 col-md-6 mb-30">
					<div class="card-box height-100-p widget-style1 agency-card" style="border-top: 4px solid <?php echo $agency['color']; ?>; transition: all 0.3s ease; opacity: 0.85;">
						<div class="d-flex flex-wrap align-items-center justify-content-between">
							<div class="widget-data">
								<div class="h4 mb-0" style="color: <?php echo $agency['color']; ?>; font-weight: 700; font-size: 28px;"><?php echo $chequier_count; ?></div>
						<div class="weight-600 font-14" style="color: #666;"><?php echo $agency['name']; ?> - En cours</div>
							</div>
							<div class="widget-icon">
								<div class="icon" style="background: linear-gradient(135deg, <?php echo $agency['color']; ?>20 0%, <?php echo $agency['color']; ?>10 100%); border-radius: 12px; padding: 15px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
									<i class="icon-copy fa fa-file-text" style="font-size: 24px; color: <?php echo $agency['color']; ?>;"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>


			<div class="card-box mb-30">
				<div class="pd-20 d-flex justify-content-between align-items-center">
					<h2 class="text-blue h4 mb-0">LISTE DES DEMANDES DE CHÉQUIERS</h2>
					<div class="filter-group d-flex gap-2">
						<div class="form-group mb-0">
							<label class="mb-2" style="font-weight: 600; font-size: 12px; color: #666;">Mois :</label>
							<select id="filter_month" class="form-control" style="width: 100px; height: 40px; border-radius: 6px;">
								<option value="">Tous</option>
								<?php
									$current_month = date('m');
									$month_names = [
										1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
										5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
										9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
									];
									for ($m = 1; $m <= 12; $m++) {
										$selected = ($m == $current_month) ? 'selected' : '';
										echo '<option value="' . str_pad($m, 2, '0', STR_PAD_LEFT) . '" ' . $selected . '>' . $month_names[$m] . '</option>';
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
					<table class="data-table table hover multiple-select-row nowrap">
						<thead>
							<tr>
								<th class="table-plus">AGENCE</th>
								<th>NOM DU CLIENT</th>
								<th>CSO</th>
								<th>TYPES DE CHÉQUIERS</th>
								<th>QUANTITÉ</th>
								<th>STATUT</th>
								<th>DATE</th>
							</tr>
						</thead>
						<tbody id="cso-table-body">
							<?php
								$current_year = date('Y');
								$current_month = date('m');
								
								// Construire les conditions de filtre
								$month_filter = '';
								$year_filter = '';
								$where_clause = "1=1";
								
								// Appliquer les filtres uniquement si des paramètres sont passés
								if (!empty($_GET['filter_month']) || !empty($_GET['filter_year'])) {
									if (!empty($_GET['filter_month'])) {
										$month_filter = " AND MONTH(tc.date_enregistrement) = '" . intval($_GET['filter_month']) . "'";
									}
									
									if (!empty($_GET['filter_year'])) {
										$year_filter = " AND YEAR(tc.date_enregistrement) = '" . intval($_GET['filter_year']) . "'";
									}
									
									$chequier_query = mysqli_query($conn, "
										SELECT 
											tc.id,
											tc.firstname,
											tc.branch_code,
											tc.type_compte,
											tc.etabliss,
											tc.access,
											tc.date_enregistrement,
											CONCAT(te.FirstName, ' ', te.LastName) as cso_name
										FROM tblcompte tc
										LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
										WHERE " . $where_clause . $month_filter . $year_filter . "
										ORDER BY tc.date_enregistrement DESC
									");
									
									if (!$chequier_query) {
										error_log("SQL Error in ci/index.php (filtered query): " . mysqli_error($conn));
										$chequier_query = null;
									}
								} else {
									// Pas de filtre: afficher toutes les demandes de l'année courante
									$chequier_query = mysqli_query($conn, "
										SELECT
											tc.id,
											tc.firstname,
											tc.branch_code,
											tc.type_compte,
											tc.etabliss,
											tc.access,
											tc.date_enregistrement,
											CONCAT(te.FirstName, ' ', te.LastName) AS cso_name
										FROM tblcompte tc
										LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
										WHERE tc.date_enregistrement >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
										ORDER BY tc.date_enregistrement DESC;
");
									
									if (!$chequier_query) {
										error_log("SQL Error in ci/index.php (default query): " . mysqli_error($conn));
										$chequier_query = null;
									}
								}
								
								if ($chequier_query && mysqli_num_rows($chequier_query) > 0) {
									while ($row = mysqli_fetch_array($chequier_query)) {
										// Déterminer la couleur du statut
										$status_color = '#FFC107';
										if ($row['access'] === 'En Cours') {
											$status_color = '#FF9800';
									} elseif ($row['access'] === 'Traité') {
										$status_color = '#4CAF50';
									} elseif ($row['access'] === 'Rejeté') {
										$status_color = '#F44336';
									}
							?>
							<tr>
								<td class="table-plus">
									<div class="weight-600"><?php echo htmlspecialchars($row['branch_code']); ?></div>
								</td>
								<td><?php echo htmlspecialchars($row['firstname']); ?></td>
								<td><?php echo htmlspecialchars($row['cso_name']); ?></td>
								<td><?php echo htmlspecialchars($row['type_compte']); ?></td>
								<td>
									<span class="badge" style="background: #2196F3; color: white; padding: 6px 10px; border-radius: 20px;">
										<?php echo intval($row['etabliss']); ?>
									</span>
								</td>
								<td>
									<span class="badge" style="background: <?php echo $status_color; ?>; color: white; padding: 6px 10px; border-radius: 20px; font-weight: 600;">
										<?php echo htmlspecialchars($row['access']); ?>
									</span>
								</td>
								<td><?php echo date('d/m/Y', strtotime($row['date_enregistrement'])); ?></td>
							</tr>
							<?php } // end while
									} else { // if no query results
							?>
							<tr>
								<td colspan="7" style="text-align: center; padding: 20px; color: #999;">
									Aucune demande de chéquier trouvée
								</td>
							</tr>
							<?php } // end if chequier_query ?>
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