<?php include('../includes/session.php')?>
<?php include('../includes/config.php')?>
<?php include('includes/header.php')?>

<?php
/** @var mysqli $conn */
if (empty($conn) || !($conn instanceof mysqli)) {
    echo "<div style='padding:20px;color:#900'>Connexion à la base de données introuvable.</div>";
    exit;
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
			<div class="page-header">
				<div class="row">
					<div class="col-md-6 col-sm-12">
						<div class="title">
							<h4>Demandes de Chéquiers</h4>
						</div>
						<nav aria-label="breadcrumb" role="navigation">
							<ol class="breadcrumb">
								<li class="breadcrumb-item"><a href="index">Dashboard</a></li>
								<li class="breadcrumb-item active" aria-current="page">Liste des Demandes de Chéquiers</li>
							</ol>
						</nav>
					</div>
				</div>
			</div>
			<div class="dashboard-section">
				<div class="stats-grid">
					<?php
						$agencies = array(
							array('code' => 'T31', 'name' => 'SIÈGE', 'icon' => 'fa-building', 'color' => 'red'),
							array('code' => 'T32', 'name' => 'LUMUMBA', 'icon' => 'fa-building', 'color' => 'blue'),
							array('code' => 'T33', 'name' => 'ATLANTIC', 'icon' => 'fa-building', 'color' => 'teal'),
							array('code' => 'T34', 'name' => 'POTO-POTO', 'icon' => 'fa-building', 'color' => 'orange'),
							array('code' => 'T38', 'name' => 'DOLISIE', 'icon' => 'fa-building', 'color' => 'amber'),
							array('code' => 'T39', 'name' => 'OUESSO', 'icon' => 'fa-building', 'color' => 'purple'),
							array('code' => 'T41', 'name' => 'BACONGO', 'icon' => 'fa-building', 'color' => 'green'),
						);
					
					?>
				</div>
			</div>

			<div class="stats-grid">
					<?php
						foreach ($agencies as $agency) {
							$chequier_query = mysqli_query($conn, "SELECT COUNT(*) AS chequier_count FROM tblcompte WHERE branch_code = '" . $agency['code'] . "'");
							$chequier_result = mysqli_fetch_assoc($chequier_query);
							$chequier_count = $chequier_result['chequier_count'];
						?>
						<div class="stat-card <?php echo $agency['color']; ?>">
							<div class="stat-header">
								<div class="stat-icon"><i class="fa fa-file-text"></i></div>
							</div>
							<div class="stat-content">
								<div class="stat-number"><?php echo $chequier_count; ?></div>
								<div class="stat-label"><?php echo $agency['name']; ?></div>
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
					<table class="table hover multiple-select-row data-table-export nowrap">
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
						<tbody id="chequier-table-body">
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
									") or die(mysqli_error($conn));
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
											CONCAT(te.FirstName, ' ', te.LastName) as cso_name
										FROM tblcompte tc
										LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
										WHERE YEAR(tc.date_enregistrement) = $current_year
										ORDER BY tc.date_enregistrement DESC
									") or die(mysqli_error($conn));
								}
								
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
								<td><?php echo date('d/m/Y H:i', strtotime($row['date_enregistrement'])); ?></td>
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
		});
	</script>
</body>
</html>
