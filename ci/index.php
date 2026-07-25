<?php 
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
?>


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
			<div class="dashboard-section">
				<h2 class="dashboard-section-title">📋 Demandes de Chéquiers en Cours</h2>
				<div class="stats-grid">
					<?php
						// Définir les agences avec leurs infos
						$agencies = array(
							array('code' => 'T31', 'name' => 'SIÈGE', 'icon' => 'fa-building', 'color' => 'red'),
							array('code' => 'T32', 'name' => 'LUMUMBA', 'icon' => 'fa-building', 'color' => 'blue'),
							array('code' => 'T33', 'name' => 'ATLANTIC', 'icon' => 'fa-building', 'color' => 'teal'),
							array('code' => 'T34', 'name' => 'POTO-POTO', 'icon' => 'fa-building', 'color' => 'orange'),
							array('code' => 'T38', 'name' => 'DOLISIE', 'icon' => 'fa-building', 'color' => 'amber'),
							array('code' => 'T39', 'name' => 'OUESSO', 'icon' => 'fa-building', 'color' => 'purple'),
							array('code' => 'T41', 'name' => 'BACONGO', 'icon' => 'fa-briefcase', 'color' => 'green'),
						);

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
					<div class="stat-card <?php echo $agency['color']; ?>">
						<div class="stat-header">
							<div class="stat-icon"><i class="fa <?php echo $agency['icon']; ?>"></i></div>
						</div>
						<div class="stat-content">
							<div class="stat-number"><?php echo $chequier_count; ?></div>
							<div class="stat-label"><?php echo $agency['name']; ?></div>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
			

			<div class="dashboard-section">
				<h2 class="dashboard-section-title">Liste des Demandes du mois en cours</h2>
				
				<div class="filters-bar">
					<div class="filter-group">
						<div class="filter-item">
							<label>Mois</label>
							<select id="filter_month" class="form-control">
								<option value="">Tous les mois</option>
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
						<div class="filter-item">
							<label>Année</label>
							<select id="filter_year" class="form-control">
								<option value="">Toutes les années</option>
								<?php 
									$current_year = date('Y');
									for ($y = $current_year; $y >= 2025; $y--) {
										$selected = ($y == $current_year) ? 'selected' : '';
										echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
									}
								?>
							</select>
						</div>
						<button type="button" id="filter_btn" class="btn-filter">
							<i class="fa fa-filter"></i> Filtrer
						</button>
					</div>
				</div>

				<div class="dashboard-table">
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
										ORDER BY tc.date_enregistrement DESC;");
									
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
								<td colspan='7' style='text-align: center; vertical-align: middle;'>
									<div style='display: inline-block;'>
										<img src='../vendors/images/expertise-seo-hero.svg' alt='Aucune Demande pour le moment' style='max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;'/>
									</div>
								</td>
							</tr>
							<?php }  ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="dashboard-section" style="margin-top: 32px;">
				<h2 class="dashboard-section-title">📈 Évolution mensuelle des demandes de chéquiers</h2>
				<div class="filters-bar">
					<div class="filter-group">
						<div class="filter-item">
							<label>Année</label>
							<select id="chart_year_filter" class="form-control">
								<?php 
									$current_year = date('Y');
									for ($y = $current_year; $y >= 2025; $y--) {
										$selected = ($y == $current_year) ? 'selected' : '';
										echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
									}
								?>
							</select>
						</div>
					</div>
				</div>
				<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);">
					<canvas id="chequier_chart" height="90"></canvas>
				</div>
			</div>
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

			// Initialiser le graphique
			let chartInstance = null;
			const chartCanvas = document.getElementById('chequier_chart');
			const yearFilter = document.getElementById('chart_year_filter');

			function drawCanvasLineChart(ctx, labels, datasets) {
				const canvas = ctx.canvas;
				const width = canvas.clientWidth || 900;
				const height = canvas.clientHeight || 320;
				const dpr = window.devicePixelRatio || 1;
				canvas.width = width * dpr;
				canvas.height = height * dpr;
				ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
				ctx.clearRect(0, 0, width, height);

				const padding = { top: 24, right: 16, bottom: 40, left: 42 };
				const chartWidth = width - padding.left - padding.right;
				const chartHeight = height - padding.top - padding.bottom;
				const maxValue = Math.max(1, ...datasets.flatMap(ds => ds.data));
				const stepY = chartHeight / Math.max(1, maxValue);

				ctx.strokeStyle = 'rgba(0, 0, 0, 0.08)';
				ctx.lineWidth = 1;
				for (let i = 0; i <= 5; i++) {
					const y = padding.top + (chartHeight / 5) * i;
					ctx.beginPath();
					ctx.moveTo(padding.left, y);
					ctx.lineTo(width - padding.right, y);
					ctx.stroke();
				}

				ctx.beginPath();
				ctx.moveTo(padding.left, padding.top + chartHeight);
				ctx.lineTo(width - padding.right, padding.top + chartHeight);
				ctx.stroke();

				ctx.font = '12px Arial';
				ctx.fillStyle = '#666';
				ctx.textAlign = 'center';
				labels.forEach((label, index) => {
					const x = padding.left + (chartWidth / (labels.length - 1)) * index;
					ctx.fillText(label, x, height - 14);
				});

				datasets.forEach((dataset, datasetIndex) => {
					const color = dataset.borderColor || '#1f77b4';
					ctx.strokeStyle = color;
					ctx.fillStyle = color;
					ctx.lineWidth = 2;
					ctx.beginPath();
					dataset.data.forEach((value, index) => {
						const x = padding.left + (chartWidth / (labels.length - 1)) * index;
						const y = padding.top + chartHeight - (value * stepY);
						if (index === 0) {
							ctx.moveTo(x, y);
						} else {
							ctx.lineTo(x, y);
						}
					});
					ctx.stroke();

					dataset.data.forEach((value, index) => {
						const x = padding.left + (chartWidth / (labels.length - 1)) * index;
						const y = padding.top + chartHeight - (value * stepY);
						ctx.beginPath();
						ctx.arc(x, y, 3, 0, Math.PI * 2);
						ctx.fill();
					});
				});
			}

			function initChart(year) {
				if (!chartCanvas) return;
				const ctx = chartCanvas.getContext('2d');
				
				fetch(`get_chequier_stats.php?year=${year}`)
					.then(response => response.json())
					.then(data => {
						if (chartInstance) {
							chartInstance.destroy();
						}

						try {
							if (typeof Chart !== 'undefined' && ctx) {
								chartInstance = new Chart(ctx, {
									type: 'line',
									data: {
										labels: data.labels,
										datasets: data.datasets
									},
									options: {
										responsive: true,
										maintainAspectRatio: true,
										plugins: {
											legend: { position: 'top' },
											tooltip: { enabled: true }
										},
										scales: {
											y: {
												beginAtZero: true,
												ticks: { stepSize: 1 }
											}
										}
									}
								});
							} else {
								drawCanvasLineChart(ctx, data.labels, data.datasets);
							}
						} catch (error) {
							console.error('Erreur lors du chargement du graphique:', error);
							drawCanvasLineChart(ctx, data.labels, data.datasets);
						}
					})
					.catch(error => {
						console.error('Erreur lors du chargement du graphique:', error);
					});
			}

			// Initialiser au chargement
			if (chartCanvas && yearFilter) {
				const initialYear = yearFilter.value || new Date().getFullYear();
				initChart(initialYear);

				// Mettre à jour le graphique quand l'année change
				yearFilter.addEventListener('change', function() {
					initChart(this.value);
				});
			}
		});
	</script>
</body>
</html>