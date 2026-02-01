<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<body>
    
    <!-- <?php include('includes/preloader.php')?> -->
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
                                    <li class="breadcrumb-item active" aria-current="page">Congé</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <div class="dropdown show">
                                <?php 
                                    // Vérifiez si l'ID de congé est présent dans l'URL
                                    if (isset($_GET['tracking'])) {
                                        $trackingId = intval($_GET['tracking']);
                                ?>
                                <a class="btn btn-primary" href="report_pdf?trackingid=<?php echo $trackingId; ?>">
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
                            <h4 class="text-blue h4">Détails de la demande de congé</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>
                    <div class="wizard-content">
                    <form method="post" action="" class="tab-wizard wizard-circle wizard">
                            <?php 
                                if (!isset($_GET['tracking']) || empty($_GET['tracking'])) {
                                    header('Location: index.php');
                                    exit;
                                } else {
                                    $lid = intval($_GET['tracking']);
                                    $sql = "SELECT tbltracking.id as lid, tbltracking.donneur_ordre, tbltracking.date_depot, tbltracking.ref98, tbltracking.beneficiaire, tbltracking.devise,tbltracking.montant_ordre,tbltracking.montant_devise, tbltracking.situation_dossier, tbltracking.date_beac,tbltracking.date_decision, 
                                                   tbltracking.decision, tbltracking.date_mt999,tbltracking.date_couverture_xaf,tbltracking.date_reception_devise,tbltracking.decision_cover,tbltracking.date_traitement,tbltracking.ref_transaction,tbltracking.ref_transaction,tbltracking.commentaire,
                                                    tbltracking.delai_traitement,tbltracking.date_enregistrement
                                            FROM tbltracking 
                                            JOIN tblemployees ON tbltracking.emp_id = tblemployees.emp_id 
                                            WHERE tbltracking.id = :lid";
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
                                <div class="col-md-4 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Donneur D'ordre</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-primary" readonly value="<?php echo h($result->donneur_ordre); ?>">
                                    </div>
                                </div>
                                 <div class="col-md-4 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Béneficiaire</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->beneficiaire); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Date dépôt </b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h(!empty($result->date_depot) ? date('d-m-Y', strtotime($result->date_depot)) : ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Ref 98</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->ref98); ?>">
                                    </div>
                                </div>
                        
                                <div class="col-md-1 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Devise</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->devise); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Montant sur l'ordre </b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h($result->montant_ordre); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Montant en devise </b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h($result->montant_devise); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Ref Transaction </b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h($result->ref_transaction); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Delai Traitement </b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo h($result->delai_traitement); ?>">
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
                                        <label style="font-size:16px;"><b>Période de congé demandée</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="Du <?php echo date('d-m-Y', strtotime($result->FromDate)); ?> Au <?php echo date('d-m-Y', strtotime($result->ToDate)); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Date de la demande</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-success" readonly value="<?php echo date('d-m-Y H:i:s', strtotime($result->PostingDate)); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Jour demandé</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->num_days); ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <div class="form-group">
                                        <label style="font-size:16px;"><b>Jours restants</b></label>
                                        <input type="text" class="selectpicker form-control" data-style="btn-outline-info" readonly value="<?php echo h($result->RemainingDays); ?>">
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
                                1 => "Approuvée",
                                2 => "non Approuvé",
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
                                            Vous avez soumis une demande de Congé de : <?php echo h($result->num_days);?> jours 
                                            et la demande est : <?php echo $statusMessage; ?>
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
