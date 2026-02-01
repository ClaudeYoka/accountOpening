<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php
if(isset($_POST['add_staff']))
{
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $email = $_POST['email'];
    $password = password_hash("Passw0rd", PASSWORD_DEFAULT); // Mot de passe par défaut (hashé)
    $department = $_POST['department'];
    $username = $_POST['Username'];
    $user_role = $_POST['user_role'];
    $phonenumber = $_POST['phonenumber'];
    $Position = $_POST['Position'];
    $agence = $_POST['AgenceShortName'];
    $status = "Offline";

    $query = mysqli_query($conn, "SELECT * FROM tblemployees WHERE EmailId = '$email'");
    $count = mysqli_num_rows($query);
    
    if ($count > 0) { ?>
        <script>
            alert('Utilisateur existe déjà dans la base de donnée');
        </script>
    <?php
    } else {
        mysqli_query($conn, "INSERT INTO tblemployees(FirstName, LastName, Username, EmailId, Password, Department, AgenceShortName, role, Phonenumber, Status, location, Position, password_changed) VALUES('$fname', '$lname', '$username', '$email', '$password', '$department', '$agence', '$user_role', '$phonenumber', '$status', 'NO-IMAGE-AVAILABLE.jpg', '$Position', 0)"); ?>
        <script>alert('Nouvel utilisateur Ajouté avec succès');</script>
        <script>
            window.location = "staff";
        </script>
    <?php }
}
?>

<body>
    <!-- <div class="pre-loader">
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
    </div> -->

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
                                <h4>Utilisateur Portail</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Page nouveau Utilisateur</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Formulaire d'enregistrement de nouveaux employés</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>

                    <div class="wizard-content">
                        <form method="post" action="" class="tab-wizard wizard-circle wizard vertical">

                            <h5>Infos Personnelles</h5>
                            <section>
                                <div class="row">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Nom (S) :</label>
                                            <input name="firstname" type="text" class="form-control wizard-required" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Prénom (S) :</label>
                                            <input name="lastname" type="text" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Username :</label>
                                            <input name="Username" type="text" class="form-control" required="true"  autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Adresse Email :</label>
                                            <input name="email" type="email" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Agence :</label>
                                            <select name="AgenceShortName" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">Choisir l'Agence</option>
                                                <?php
                                                $query = mysqli_query($conn, "SELECT * from tblagences ");
                                                while($row = mysqli_fetch_array($query)){
                                                ?>
                                                <option value="<?php echo $row['AgenceShortName']; ?>"><?php echo $row['AgenceName']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Téléphone :</label>
                                            <input name="phonenumber" type="number" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                    <!-- <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Téléphone 2 (Opt):</label>
                                            <input name="phonenumber2" type="number" class="form-control" autocomplete="off">
                                        </div>
                                    </div> -->
                                    
                                </div>

                            </section>

                            <!-- Step 2 -->
                            <h5>Infos Professionnelles</h5>
                            <section>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Utilisateur Poste :</label>
                                            <input name="Position" type="text" class="form-control wizard-required" required="true" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Rôle de l'utilisateur :</label>
                                            <select name="user_role" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">-- Selectionner --</option>
                                                <option value="CI">CI</option>
                                                <option value="cso">CSO</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Département :</label>
                                            <select name="department" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">Choisir Département</option>
                                                <?php
                                                $query = mysqli_query($conn, "SELECT * from tbldepartments ");
                                                while($row = mysqli_fetch_array($query)){
                                                ?>
                                                <option value="<?php echo $row['DepartmentShortName']; ?>"><?php echo $row['DepartmentName']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

									
									    <!-- Le champ mot de passe est supprimé car le mot de passe par défaut est utilisé -->

                                    <div class="row">
										<div class="col-md-4 col-sm-12">
											<div class="form-group">
												<label style="font-size:16px;"><b></b></label>
												<button type="submit" name="add_staff" id="add_staff" class="btn btn-primary">Ajouter&nbsp;Utilisateur</button>
											</div>
										</div>
									</div>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>

            </div>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>
    <!-- js -->
    <?php include('includes/scripts.php')?>
</body>
</html>
