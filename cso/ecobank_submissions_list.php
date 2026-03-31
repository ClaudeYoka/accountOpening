<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php include('../includes/flexcube_helpers.php')?>
<?php
// Helper to safely escape output and avoid passing null to htmlspecialchars (PHP 8.1+ deprecation)
function safe_h($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

// Option pour utiliser Flexcube comme source principale
$use_flexcube = false; // DISABLED: Mettre à false pour utiliser uniquement la BD locale
$use_flexcube_fallback = false; // DISABLED: Fallback vers Flexcube si données manquantes locales (TRÈS LENT - fait une requête API par ligne)
?>

<body>

	<!-- <?php include('includes/preloader.php')?> -->

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
                                <h4>LISTE DES COMPTES ECOBANK</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">LISTE DE COMPTE</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
						<!-- Placeholder for future actions -->
					</div>
                    </div>
                </div>


				<div class="card-box mb-30">
					<div class="pd-20">
						<h2 class="text-blue h4">Dernières soumissions</h2>
					</div>
					<div class="pd-20 pb-20">
						<!-- Recherche -->
						<!-- <form method="get" class="form-inline" style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
							<input type="text" name="q" class="form-control" placeholder="Rechercher par numéro de compte ou nom du client" value="<?php echo safe_h($_GET['q'] ?? ''); ?>" style="flex:1;">
							<button type="submit" class="btn btn-primary">Rechercher</button>
							<?php if(isset($_GET['q']) && $_GET['q'] !== ''): ?>
								<a class="btn" href="ecobank_submissions_list" style="margin-left:8px;">Réinitialiser</a>
							<?php endif; ?>
						</form> -->

						<table class="data-table table hover multiple-select-row nowrap">
							<thead>
								<tr>
									<th class="table-plus datatable-nosort">#</th>
									<th>COMPTE</th>
									<th>NOM</th>
									<th>TYPE COMPTE</th>
									<th>DATE</th>
									<th class="datatable-nosort">ACTION</th>
								</tr>
							</thead>
							<tbody>
							<?php
                                $q = isset($_GET['q']) ? trim($_GET['q']) : '';
                                $escq = mysqli_real_escape_string($conn, $q);

                                // Utiliser les nouvelles tables normalisées
                                $where = '';
                                $params = [];
                                $types = '';

                                if ($escq !== '') {
                                    $where = "WHERE (a.account_number LIKE ? OR a.bank_account_number LIKE ? OR
                                               c.customer_name LIKE ? OR CONCAT(c.first_name, ' ', c.last_name) LIKE ?)";
                                    $search_term = '%' . $escq . '%';
                                    $params = [$search_term, $search_term, $search_term, $search_term];
                                    $types = 'ssss';
                                }

                                $sql = "SELECT id, customer_id, account_number, customer_name, account_type, email, created_at
                                        FROM ecobank_form_submissions
                                        $where
                                        ORDER BY created_at DESC
                                        LIMIT 100";

                                $stmt = mysqli_prepare($conn, $sql);
                                if (!empty($params)) {
                                    mysqli_stmt_bind_param($stmt, $types, ...$params);
                                }
                                mysqli_stmt_execute($stmt);
                                $res = mysqli_stmt_get_result($stmt);

                                if ($res) {
								while($r = mysqli_fetch_assoc($res)){

                                    // Enrichir avec Flexcube si activé
                                    $row_data = $r;
                                    if ($use_flexcube_fallback && !empty($r['account_number'])) {
                                        $row_data = enrichRowWithFlexcube($r);
                                    }
							?>
									<tr>
										<td class="table-plus"><?php echo (int)$row_data['id']; ?></td>
										<td><?php echo safe_h($row_data['account_number'] ?? ''); ?></td>
										<td><?php echo safe_h($row_data['customer_name'] ?? ''); ?></td>
										<td><?php echo safe_h($row_data['account_type'] ?? ''); ?></td>
										<td><?php echo safe_h($row_data['created_at'] ?? ''); ?></td>
										<td>
											<div class="table-actions">
												<a title="VIEW" href="ecobank_submission_view?id=<?php echo (int)$row_data['id']; ?>" data-color="#265ed7"><i class="icon-copy dw dw-eye"></i></a>
												<!-- <a title="EDIT" href="ecobank_submission_edit?id=<?php echo (int)$row_data['id']; ?>" data-color="#265ed7"><i class="icon-copy dw dw-edit2"></i></a> -->
											</div>
										</td>
									</tr>
							<?php
                                    }
                                } else {
									echo "<tr><td colspan='7' style='text-align: center; vertical-align: middle;'>
											<div style='display: inline-block;'><img src='../vendors/images/expertise-seo-hero.svg'
												alt='Aucune Demande pour le moment' style='max-width: 250px; width: 100%; height: auto; display: block; margin: 0 auto;'/>
											</div></td>
										</tr>";
                                }
							?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
			<?php include('includes/footer.php'); ?>
		</div>
	</div>

	<?php include('includes/scriptJs.php')?>
</body>
</html>