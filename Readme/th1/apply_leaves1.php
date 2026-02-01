<html>
<head>
    
    <?php include('includes/header.php'); ?>

</head>

<?php include('../includes/session.php');?>


<?php include('leaveController.php');?>

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
                                <h4>Demande de Congé</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Prendre congé</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div style="margin-left: 30px; margin-right: 30px;" class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Formulaire de demande de Congé</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>

                    <div class="wizard-content">
                    <form method="post" action="" class="tab-wizard wizard-circle wizard">
                           <h5>Infos Personnel</h5>
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
                                            <label> Email</label>
                                            <input name="EmailId" type="text" class="form-control" required="true" autocomplete="off" readonly value="<?php echo $row['EmailId']; ?>">
                                        </div>
                                    </div>
                                    <?php endif ?>
                                    
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Département</label>
                                            <input name="Department" type="text" class="form-control" required="true" autocomplete="off" readonly value="<?php echo $row['Department']; ?>">
                                        </div>
                                    </div>

                                    

                                </div>
                            </section>

                                  <!-- Step 2 -->
                        <h5>Infos Congé</h5>
                        <section>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Type de Congé :</label>
                                            <select name="leave_type" id="leave_type" class="custom-select form-control" required="true" autocomplete="off">
                                            <option value="">Select Type de Congé...</option>
                                            <?php $sql = "SELECT LeaveType from tblleavetype";
                                            $query = $dbh -> prepare($sql);
                                            $query->execute();
                                            $results=$query->fetchAll(PDO::FETCH_OBJ);
                                            $cnt=1;
                                            if($query->rowCount() > 0) {
                                                foreach($results as $result) { ?>
                                                    <option value="<?php echo h($result->LeaveType);?>"><?php echo h($result->LeaveType);?></option>
                                                <?php }} ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de Départ :</label>
                                            <input id="date_form" name="date_from" type="date" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de Retour:</label>
                                            <input id="date_to" name="date_to" type="date" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Step 2 -->
                              <h5>Infos Congé</h5>
                            <section>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Intérim (BACKUP):</label>
                                            <select name="work_cover" id="work_cover" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">Choisir votre Backup...</option>
                                                <?php 
                                                $sql5 = "SELECT * FROM tblemployees WHERE tblemployees.role = 'staff' AND tblemployees.Department = '$session_depart' AND emp_id != '$session_id' ";
                                                $query1 = $dbh->prepare($sql5);
                                                $query1->execute();
                                                $resultats = $query1->fetchAll(PDO::FETCH_OBJ);
                                                
                                                if ($query1->rowCount() > 0) {
                                                    foreach ($resultats as $result1) {
                                                        // Concaténer FirstName et LastName pour afficher le nom complet
                                                        $fullName = htmlentities($result1->FirstName) . ' ' . htmlentities($result1->LastName);
                                                ?>
                                                    <option value="<?php echo $fullName; ?>"><?php echo $fullName; ?></option>
                                                <?php 
                                                    } 
                                                } 
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Nombre de jours demandés</label>
                                            <input id="requested_days" name="requested_days" type="text" class="form-control" required="true" autocomplete="off" readonly value="">
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Nombre de Jours Restants</label>
                                            <input id="remaining_days" name="remaining_days" type="text" class="form-control" readonly value="">
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label style="font-size:16px;"><b></b></label>
                                            <button type="submit" name="apply" id="apply" class="btn btn-primary">Demande&nbsp;Congé</button>
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


<?php include('includes/Jsleave.php'); ?>

<?php include('includes/scripts.php')?>


</body>
</html>
