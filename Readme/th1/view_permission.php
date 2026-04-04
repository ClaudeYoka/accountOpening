<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<body>

    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>DETAILS</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index"></a>Page demande</li>
                                    <li class="breadcrumb-item active" aria-current="page">Permission</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <div class="dropdown show">
                                <?php 
                                    // Vérifiez si l'ID de congé est présent dans l'URL
                                    if (isset($_GET['permission'])) {
                                        $permissionId = intval($_GET['permission']);
                                ?>
                                <a class="btn btn-primary" href="permission_pdf?permissionid=<?php echo $permissionId; ?>">
                                    Générer PDF
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Détails de la Permission</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>
                    <div class="wizard-content">
                    <form method="post" action="" class="tab-wizard wizard-circle wizard">
                            <?php 
                                if (!isset($_GET['permission']) || empty($_GET['permission'])) {
                                    header('Location: index.php');
                                    exit;
                                } else {
                                    $lid = intval($_GET['permission']);
                                    $sql = "SELECT tblpermission.id as lid, tblemployees.FirstName, tblemployees.LastName, tblemployees.emp_id, tblemployees.Gender, tblemployees.Department,tblemployees.Phonenumber, tblemployees.EmailId, tblemployees.Position, tblpermission.ToDate, tblpermission.FromDate, tblpermission.requested_days, tblpermission.Raison, tblpermission.PostingDate, tblpermission.requested_hours, tblpermission.HodRemarks, tblpermission.RegRemarks,tblpermission.HodDate, tblpermission.RegDate
                                            FROM tblpermission 
                                            JOIN tblemployees ON tblpermission.empid = tblemployees.emp_id 
                                            WHERE tblpermission.id = :lid";
                                    $query = $dbh->prepare($sql);
                                    $query->bindParam(':lid', $lid, PDO::PARAM_STR);
                                    $query->execute();
                                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                                    
                                    if ($query->rowCount() > 0) {
                                        foreach ($results as $result) {
                            ?>  

                        <h5>Infos Personnel</h5>
					<section>
                        <div class="row">
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Nom & Prénom</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo h($result->FirstName . " " . $result->LastName); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Poste </b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->Position); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Adresse Mail</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->EmailId); ?>">
                                </div>
                            </div>
                        
				
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Division </b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h($result->Department); ?>">
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2 -->
					<h5>Infos Congé</h5>
					<section>
						<div class="row">
                            
							<div class="col-md-4">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Permission demandée</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="Du <?php echo h(!empty($result->FromDate) ? date('d-m-Y', strtotime($result->FromDate)) : ''); ?> Au <?php echo h(!empty($result->ToDate) ? date('d-m-Y', strtotime($result->ToDate)) : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Jours</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->requested_days); ?>">
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Heures</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->requested_hours); ?>">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Date de la demande</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo date('d-m-Y H:i:s', strtotime($result->PostingDate)); ?>">
                                </div>
                            </div>
							<div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Raison de la permission</b></label>
                                    <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->Raison); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                                <div class="col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Décision du HEAD</b></label>
                                        <?php $stats = $result->HodRemarks; ?>
                                        <?php if ($stats == 1): ?>
                                            <input type="text" style="color: green;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "Approuvé"; ?>">
                                        <?php elseif ($stats == 2): ?>
                                            <input type="text" style="color: red; font-size: 16px;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "Rejeté"; ?>">
                                        <?php else: ?>
                                            <input type="text" style="color: orange;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "En cours"; ?>">
                                        <?php endif ?>
                                    </div>
                                </div>

                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Décision du RH</b></label>
                                    <?php $stats = $result->RegRemarks; ?>
                                    <?php if ($stats == 1): ?>
                                        <input type="text" style="color: green;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "Approuvé"; ?>">
                                    <?php elseif ($stats == 2): ?>
                                        <input type="text" style="color: red; font-size: 16px;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "Rejeté"; ?>">
                                    <?php else: ?>
                                        <input type="text" style="color: orange;" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "En cours"; ?>">
                                    <?php endif ?>
                                </div>
                            </div>

                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Date Réponse du Head</b></label>
                                    <?php if ($result->HodDate == ""): ?>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "NA"; ?>">
                                    <?php else: ?>
                                        <div class="avatar mr-2 flex-shrink-0">
                                            <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo date('d-m-Y', strtotime($result->HodDate)); ?>">
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label style="font-size:16px;"><b>Date Réponse du RH</b></label>
                                    <?php if ($result->RegDate == ""): ?>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo "NA"; ?>">
                                    <?php else: ?>
                                        <div class="avatar mr-2 flex-shrink-0">
                                            <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo date('d-m-Y', strtotime($result->RegDate)); ?>">
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>

                        <?php
                            } // Fin de la boucle foreach
                                } else {
                                    echo "<p>Aucun détail trouvé pour ce congé.</p>";
                                }
                            } // Fin du else
                        ?>
					</section>

                     
                     <div class="form-group">
                        <?php 
                            $stats = $result->RegRemarks; 
                            $statusMessages = [
                                1 => "Approuvé",
                                2 => "Rejeté",
                                0 => "En cours" // On suppose que 0 est pour "En cours"
                            ];
                            
                            $statusColors = [
                                1 => "green",
                                2 => "red",
                                0 => "orange"
                            ];
                            
                            $statusMessage = isset($statusMessages[$stats]) ? $statusMessages[$stats] : "Statut inconnu";
                            $statusColor = isset($statusColors[$stats]) ? $statusColors[$stats] : "black"; // Couleur par défaut
                        ?>
   

                        <!-- success Popup html Start -->
                            <div class="modal fade" id="success-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-body text-center font-18">
                                            <h3 class="mb-20">Demande de Congé</h3>
                                            <!-- <div class="mb-30 text-center"><img src="vendors/images/success.png"></div> -->
                                            Vous avez soumis une demande de Congé de : <?php echo h($result->requested_days);?> jours 
                                            Vous avez soumis une demande de Congé de : <?php echo h($result->requested_hours);?> H
                                            <br>Le statut de la demande est : <?php echo $statusMessage; ?>
                                        </div>
                                        <div class="modal-footer justify-content-center">
                                            <button type="button" class="btn btn-primary" data-dismiss="modal">Valider</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <!-- success Popup html End -->
                         
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
