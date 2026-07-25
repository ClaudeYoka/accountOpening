<?php 
// Initialiser les sessions ET config AVANT tout output
include('../includes/session.php');
include('../includes/config.php');
require_once('../includes/audit_logger.php');
include('../includes/audit_helpers.php');

/** @var mysqli $conn */
if (empty($conn) || !($conn instanceof mysqli)) {
    echo "<div style='padding:20px;color:#900'>Connexion à la base de données introuvable.</div>";
    exit;
}


// Maintenant on peut inclure header.php qui génère du HTML
include('includes/header.php');

// Les includes qui n'envoient pas de contenu HTML peuvent se faire après
include('monitoring_notification.php');
?>

<?php
	// Initialize audit logging for admin dashboard
	init_admin_audit_logging($conn, 'admin_dashboard');

	if (isset($_GET['delete'])) {
		$delete = $_GET['delete'];
		$sql = "DELETE FROM tblemployees where emp_id = ".$delete;
		$result = mysqli_query($conn, $sql);
		if ($result) {
			// Log user deletion 
			log_admin_user_management($conn, 'deleted', $delete);

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
				<h2 class="dashboard-section-title"> Comptes par Agence</h2>
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

						foreach ($agencies as $agency) {
							$query = mysqli_query($conn, "SELECT COUNT(*) AS account_number FROM ecobank_form_submissions WHERE branch_code = '" . $agency['code'] . "' AND YEAR(created_at) = YEAR(CURDATE())");
							$result = mysqli_fetch_assoc($query);
							$count = $result['account_number'];
					?>
						<div class="stat-card <?php echo $agency['color']; ?>">
							<div class="stat-header">
								<div class="stat-icon"><i class="fa <?php echo $agency['icon']; ?>"></i></div>
							</div>
							<div class="stat-content">
								<div class="stat-number"><?php echo $count; ?></div>
								<div class="stat-label"><?php echo $agency['name']; ?></div>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>

			<div class="dashboard-section" style="margin-top: 40px;">
				<h2 class="dashboard-section-title"> Demandes de Chéquiers</h2>
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
			</div>

			<div class="card-box mb-30">
				<div class="pd-20 d-flex justify-content-between align-items-center">
					<h2 class="text-blue h4 mb-0">LISTE DES UTILISATEURS</h2>
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

			
		</div>
		<div class="dashboard-section">
					<h2 class="dashboard-section-title">📈 Évolution mensuelle des demandes de chéquiers</h2>
					
					<div class="filters-bar">
						<div class="filter-group">
							<div class="filter-item">
								<label>Année</label>
								<select id="chart_year_filter" class="form-control">
									<?php 
										$current_year = date('Y');
										for ($y = $current_year; $y >= 2025; $y--) {
										echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
									}
								?>
							</select>
						</div>
					</div>
				</div>

				<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); margin-bottom: 32px;">
					<canvas id="accounts_chart" height="80"></canvas>
				</div>
			</div>
	</div>
	<!-- js -->
	<?php include('includes/footer.php'); ?>

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
			const chartCanvas = document.getElementById('accounts_chart');
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

				datasets.forEach((dataset) => {
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
				
				fetch(`get_accounts_stats.php?year=${year}`)
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
				initChart(yearFilter.value);

				// Mettre à jour le graphique quand l'année change
				yearFilter.addEventListener('change', function() {
					initChart(this.value);
				});
			}
		});
	</script>

</body>
</html>