	<div class="header">

		<?php require_once __DIR__ . '/../../includes/config.php'; ?>

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
							<a class="dropdown-item text-danger" href="../logout"><i class="dw dw-logout"></i> Déconnexion</a>
						</div>
					</div>
				</div>
				
			</div>
	</div>
