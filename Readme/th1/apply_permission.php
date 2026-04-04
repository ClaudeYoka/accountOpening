<html>
<head>
    
    <?php include('includes/header.php'); ?>

</head>

<?php include('../includes/session.php');?>

<?php include('permissionController.php');?>

<body>

    <?php include('includes/preloader.php')?>
    
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
                                <h4>Demande de permission</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Permission</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div style="margin-left: 30px; margin-right: 30px;" class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Formulaire de demande de Permission</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>

                    <div class="wizard-content">
                        <form method="post" action="">
                            <!-- Step 1 -->
                            <section>
                                <?php if ($role_id = 'staff'): ?>
                                <?php $query= mysqli_query($conn,"select * from tblemployees where emp_id = '$session_id'");
                                    $row = mysqli_fetch_array($query);
                                ?>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>NOM (S)</label>
                                            <input name="firstname" type="text" class="form-control wizard-required" required="true" readonly autocomplete="off" value="<?php echo $row['FirstName']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>PRENOM (S)</label>
                                            <input name="lastname" type="text" class="form-control" readonly required="true" autocomplete="off" value="<?php echo $row['LastName']; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Email :</label>
                                            <input name="EmailId" type="text" class="form-control" required="true" autocomplete="off" readonly value="<?php echo $row['EmailId']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Rôle :</label>
                                            <input name="postion" type="text" class="form-control" required="true" autocomplete="off" readonly value="<?php echo $row['Position']; ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-2 col-sm-12">
                                        <div class="form-group">
                                            <label>Département :</label>
                                            <input name="Department" type="text" class="form-control" required="true" autocomplete="off" readonly value="<?php echo $row['Department']; ?>">
                                        </div>
                                    </div>
                                    
                                    <?php endif ?>

                                </div>

                                <div class="row">

                                    <div class="col-md-6 col-sm-12">
                                       
                                    </div>

                                </div>
                            </section>

                            <!-- Step 2 -->
                            <section>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Date et Heure de Départ :</label>
                                            <input id="date_from" name="date_from" type="datetime-local" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Date et Heure de Retour :</label>
                                            <input id="date_to" name="date_to" type="datetime-local" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="row">

                                    <div class="col-md-12 col-sm-12">
                                        <div class="form-group">
                                            <label>Raison de la permission :</label>
                                            <input id="Raison" name="Raison" type="text" class="form-control" required="true" autocomplete="off" >
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Jours demandés :</label>
                                            <input id="requested_days" name="requested_days" type="text" class="form-control" required="true" autocomplete="off" readonly value="">
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Heures demandées :</label>
                                            <input id="requested_hours" name="requested_hours" type="text" class="form-control" autocomplete="off" readonly value="">
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label style="font-size:16px;"><b></b></label>
                                            <button type="submit" name="apply" id="apply" class="btn btn-primary">Demande&nbsp;Permission</button>
                                        </div>
                                    </div>
                                </div>
                                 
                            </section>
                        </form>
                </div>
            </div>
         </div>
        </div>
    </div>

<?php include('includes/JsPermission.php'); ?>

<?php include('includes/scripts.php'); ?>

</body>
</html>
