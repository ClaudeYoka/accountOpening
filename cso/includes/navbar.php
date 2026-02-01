	<div class="header">
	
		<div class="header-left">
			<?php include('recherche.php'); ?>
		</div>
			
		<div class="header-right">

			<?php include('notifications.php'); ?>

			<div class="user-info-dropdown">
				<div class="dropdown">

					<?php
						$query= mysqli_query($conn,"select * from tblemployees where emp_id = '$session_id'");
						$row = mysqli_fetch_array($query);
					?>

					<a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
						<span class="user-icon">
							<img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" alt="">
						</span>
						<span class="user-name"><?php echo $row['Username'] ?></span>
					</a>
					<div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
						<a class="dropdown-item" href="staff_profile"><i class="dw dw-user1"></i> Profil</a>
						<a class="dropdown-item" href="change_password"><i class="dw dw-help"></i> Reset Password</a>
						<a class="dropdown-item" href="../logout"><i class="dw dw-logout"></i> Déconnexion</a>
					</div>
				</div>
			</div>
			
		</div>
	</div>

<!-- Documents modal (added to navbar so it exists on pages without footer) -->
<div class="modal fade" id="documentsModal" tabindex="-1" role="dialog" aria-labelledby="documentsModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="documentsModalLabel">Impression</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body text-center">
				<p>Choisir le formulaire à Imprimer</p>
				<div class="mb-3">
					<a href="formulaire_ouverture_compte_tuteur.html" class="btn btn-primary btn-block" role="button">Formulaire Ouverture Compte Mineur</a>
					<a href="formulaire_produits" class="btn btn-primary btn-block" role="button">Formulaire Autres Produits</a>
					<a href="rib" class="btn btn-primary btn-block" role="button">RIB</a>
				</div>
			</div>
			<div class="modal-footer justify-content-center">
				<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

