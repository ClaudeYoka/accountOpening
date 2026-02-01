<html>
<head>
    
    <?php include('includes/header.php'); ?>

</head>
<?php include('../includes/session.php');?>
<?php include('includes/trackingController.php');?>

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
                                <h4>Enregistrement Tracking </h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Enregistrer Dossier</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <div style="margin-left: 30px; margin-right: 30px;" class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Formulaire d'enregistrement du dossier</h4>
                            <p class="mb-20"></p>
                        </div>
                    </div>

                    <div class="wizard-content">
                        <form method="post" action="">
                            <!-- Step 1 -->
                            <section>
                                <?php if ($role_id = 'Staff'): ?>
                                <?php $query= mysqli_query($conn,"select * from tblemployees where emp_id = '$session_id'");
                                    $row = mysqli_fetch_array($query);
                                ?>
                                <div class="row">
                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>NOM (S)</label>
                                            <input name="firstname" type="text" class="form-control wizard-required" required="true" readonly autocomplete="off" 
                                                   value="<?php echo $row['FirstName']. " " .$row['LastName']; ?>">
                                        </div>
                                    </div>

                                    <?php endif ?>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de Dépôt :</label>
                                            <input id="date_form" name="date_from" type="date" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                                <label>Ref 98</label>
                                                <input id="ref98" name="ref98" type="number" class="form-control" required="true" autocomplete="off" value="">
                                        </div>
                                    </div>

                                </div>
                            
                                <div class="row">

                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Donneur D'ordre:</label>
                                            <input id="donneur_ordre" name="donneur_ordre" type="text" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-sm-12">
                                        <div class="form-group">
                                            <label>Béneficiaire:</label>
                                            <input id="beneficiaire" name="beneficiaire" type="text" class="form-control" required="true" autocomplete="off">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2 col-sm-12">
                                        <div class="form-group">
                                            <label>Devise:</label>
                                            <select name="devise" id="devise" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">Select devise..</option>
                                                <option value="EUR">EUR</option>
                                                <option value="USD">USD</option>
                                                <option value="MAR">MAD</option>
                                                <option value="XAF">XAF</option>
                                                <option value="XAF">XOF</option>
                                                <option value="ZAD">ZAD</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Montant sur l'odre:</label>
                                            <input id="mdo" name="mdo" type="number" class="form-control" required="true"  >
                                        </div>
                                    </div>

                                     <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Montant en devise (Préfinancé):</label>
                                            <input id="med" name="med" type="number" class="form-control" required="true" >
                                        </div>
                                    </div>

                                     <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label>Situation Dossier:</label>
                                            <select name="worker" id="worker" class="custom-select form-control" required="true" autocomplete="off">
                                                <option value="">....Select situation Dossier....</option>
                                                <option value="Prefinancement">Prefinancement.</option>
                                                <option value="Allocation">Allocation</option>
                                                <option value="Instances">Instances</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                </div>

                            </section>

                            <!-- Step 2 -->
                            <section>
                                <div class="row">

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date d'envoie à la BEAC:</label>
                                            <input id="date_beac" name="date_beac" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                     <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de la décision:</label>
                                            <input id="date_des" name="date_des" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Décision:</label>
                                            <select name="Decision" id="Decision" class="custom-select form-control"  autocomplete="off">
                                                <option value="">Choisir votre Backup...</option>
                                                <option value="Favorable">Favorable</option>
                                                <option value="Suspent BEAC">Suspent BEAC</option>
                                                <option value="Rejet">Rejet</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date du MT999:</label>
                                            <input id="date_mt" name="date_mt" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date Couverture XAF:</label>
                                            <input id="date_cover" name="date_cover" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de Reception Devise:</label>
                                            <input id="date_devise" name="date_devise" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                     <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Décision:</label>
                                            <select name="work_cover" id="work_cover" class="custom-select form-control"  autocomplete="off">
                                                <option value="">Choisir votre Backup...</option>
                                                <option value="Traité">Traité</option>
                                                <option value="Non Traité">Non Traité</option>
                                                <option value="Rejet">Rejeté</option>

                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Date de Traitement:</label>
                                            <input id="date_traitement" name="date_traitement" type="date" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-12">
                                        <div class="form-group">
                                            <label>Ref Transaction</label>
                                            <input id="refT" name="refT" type="text" class="form-control" required="true" autocomplete="off"  value="">
                                        </div>
                                    </div>
                                    <div class="col-md-7 col-sm-12">
                                        <div class="form-group">
                                            <label>Commanrtaire</label>
                                            <input id="commentaire" name="commentaire" type="text" class="form-control"  value="">
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-12">
                                        <div class="form-group">
                                            <label>Delai (Jours):</label>
                                            <input id="delai_traitement" name="delai_traitement" type="text" class="form-control"  autocomplete="off">
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-4 col-sm-12">
                                        <div class="form-group">
                                            <label style="font-size:16px;"><b></b></label>
                                            <button type="submit" name="apply" id="apply" class="btn btn-primary">Enregitrer&nbsp;Dossier</button>
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


<?php include('includes/Jstracking.php'); ?>

<?php include('includes/scripts.php'); ?>


</body>
</html>
