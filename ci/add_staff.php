<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php include('../includes/audit_helpers.php')?>

<?php
// Fonction de validation côté serveur
function validateStaffData($data) {
    $errors = [];

    // Validation du prénom
    $data['firstname'] = trim($data['firstname']);
    if (empty($data['firstname'])) {
        $errors[] = "Le prénom est requis";
    } elseif (strlen($data['firstname']) < 2 || strlen($data['firstname']) > 50) {
        $errors[] = "Le prénom doit contenir entre 2 et 50 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s-]+$/", $data['firstname'])) {
        $errors[] = "Le prénom ne peut contenir que des lettres, espaces et tirets";
    }

    // Validation du nom
    $data['lastname'] = trim($data['lastname']);
    if (empty($data['lastname'])) {
        $errors[] = "Le nom est requis";
    } elseif (strlen($data['lastname']) < 2 || strlen($data['lastname']) > 50) {
        $errors[] = "Le nom doit contenir entre 2 et 50 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s-]+$/", $data['lastname'])) {
        $errors[] = "Le nom ne peut contenir que des lettres, espaces et tirets";
    }

    // Validation de l'email
    $data['email'] = trim($data['email']);
    if (empty($data['email'])) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    } elseif (strlen($data['email']) > 100) {
        $errors[] = "L'email ne peut pas dépasser 100 caractères";
    }

    // Validation du nom d'utilisateur
    $data['Username'] = trim($data['Username']);
    if (empty($data['Username'])) {
        $errors[] = "Le nom d'utilisateur est requis";
    } elseif (strlen($data['Username']) < 3 || strlen($data['Username']) > 30) {
        $errors[] = "Le nom d'utilisateur doit contenir entre 3 et 30 caractères";
    } elseif (!preg_match("/^[a-zA-Z0-9_-]+$/", $data['Username'])) {
        $errors[] = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres, underscores et tirets";
    }

    // Validation du poste
    $data['Position'] = trim($data['Position']);
    if (empty($data['Position'])) {
        $errors[] = "Le poste est requis";
    } elseif (strlen($data['Position']) > 100) {
        $errors[] = "Le poste ne peut pas dépasser 100 caractères";
    }

    // Validation du rôle
    $allowed_roles = ['Admin', 'cso', 'CI'];
    if (empty($data['user_role']) || !in_array($data['user_role'], $allowed_roles)) {
        $errors[] = "Rôle utilisateur invalide";
    }

    // Validation du département
    if (empty($data['department'])) {
        $errors[] = "Le département est requis";
    }

    // Validation de l'agence
    if (empty($data['AgenceShortName'])) {
        $errors[] = "L'agence est requise";
    }

    return ['data' => $data, 'errors' => $errors];
}

if(isset($_POST['add_staff']))
{
    // Validation des données
    $validation = validateStaffData($_POST);
    if (!empty($validation['errors'])) {
        echo "<script>alert('" . implode("\\n", $validation['errors']) . "');</script>";
        return;
    }

    $data = $validation['data'];

    $fname = htmlspecialchars($data['firstname'], ENT_QUOTES, 'UTF-8');
    $lname = htmlspecialchars($data['lastname'], ENT_QUOTES, 'UTF-8');
    $email = $data['email'];
    $password = password_hash("Passw0rd", PASSWORD_DEFAULT); // Mot de passe par défaut (hashé)
    $department = $data['department'];
    $username = $data['Username'];
    $user_role = $data['user_role'];
    $Position = htmlspecialchars($data['Position'], ENT_QUOTES, 'UTF-8');
    $agence = $data['AgenceShortName'];
    $status = "Offline";

    $stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE EmailId = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    $count = mysqli_num_rows($query);
    mysqli_stmt_close($stmt);
    
    if ($count > 0) { ?>
        <script>
            alert('Utilisateur existe déjà dans la base de donnée');
        </script>
    <?php
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO tblemployees(FirstName, LastName, Username, EmailId, Password, Department, AgenceShortName, role, Status, location, Position, password_changed) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, 'NO-IMAGE-AVAILABLE.jpg', ?, 0)");
        mysqli_stmt_bind_param($stmt, "ssssssssss", $fname, $lname, $username, $email, $password, $department, $agence, $user_role, $status, $Position);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Audit logging for staff creation
        $new_staff_id = mysqli_insert_id($conn);
        log_admin_action('create_staff', $new_staff_id, [
            'firstname' => $fname,
            'lastname' => $lname,
            'username' => $username,
            'email' => $email,
            'department' => $department,
            'agency' => $agence,
            'role' => $user_role,
            'position' => $Position,
            'status' => $status
        ]);
        
        ?>
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
                            <h4 class="text-blue h4">Formulaire d'enregistrement de nouveaux utilisateurs</h4>
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
                                                $query = mysqli_query($conn, "SELECT * FROM tblagences");
                                                while($row = mysqli_fetch_array($query)){
                                                ?>
                                                <option value="<?php echo htmlspecialchars($row['AgenceShortName'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['AgenceName'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    
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
                                                <option value="<?php echo htmlspecialchars($row['DepartmentShortName'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['DepartmentName'], ENT_QUOTES, 'UTF-8'); ?></option>
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
