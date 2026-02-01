<div class="header header-ecobank">
		<div class="header-left">
			<div class="menu-icon dw dw-menu" data-toggle="left-sidebar-toggle"></div>
			<div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
		</div>
		<div class="header-right">
						<?php include('notifications.php'); ?>

			<div class="dashboard-setting user-notification">
				<div class="dropdown">
					<a class="dropdown-toggle no-arrow" href="javascript:;" data-toggle="right-sidebar" title="Paramètres">
						<i class="dw dw-settings2"></i>
					</a>
				</div>
			</div>
			
			<div class="user-info-dropdown">
				<div class="dropdown">

					<?php $query= mysqli_query($conn,"select * from tblemployees where emp_id = '$session_id'")or die(mysqli_error($conn));
								$row = mysqli_fetch_array($query);
						?>

					<a class="dropdown-toggle user-dropdown-toggle" href="#" role="button" data-toggle="dropdown">
						<span class="user-icon">
							<img src="<?php echo (!empty($row['location'])) ? '../uploads/'.$row['location'] : '../uploads/NO-IMAGE-AVAILABLE.jpg'; ?>" alt="">
						</span>
						<span class="user-name"><?php echo $row['Username']; ?></span>
					</a>
					<div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list dropdown-menu-ecobank">
						<a class="dropdown-item" href="my_profile"><i class="dw dw-user1"></i> Profil</a>
						<a class="dropdown-item" href="change_password"><i class="dw dw-help"></i> Changer le mot de passe</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item text-danger" href="../logout"><i class="dw dw-logout"></i> Déconnexion</a>
					</div>
				</div>
			</div>
			
		</div>
	</div>