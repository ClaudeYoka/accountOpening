<?php include('includes/header.php'); ?>
<?php include('../includes/session.php');?>
<body>
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
                                <h4>Formulaire Produits Banque</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Formulaire Produits</li>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="pd-20 card-box mb-30">
                    <h5 class="mb-20">Générateur de Formulaire de Souscription aux autres Produits</h5>
                    <p>Saisissez le numéro de compte , puis cliquez sur <strong>Générer</strong> pour ouvrir le formulaire pré-rempli.</p>

                    <form id="digital-form" class="mt-20">
                        <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Numéro de compte :</label>
                            <div class="col-sm-6">
                                <input type="text" id="account-number" name="account" class="form-control" autocomplete="off" placeholder="Saisir le numéro de compte ici" required>
                            </div>
                        </div>
                        <!-- <div class="form-group row">
                            <label class="col-sm-2 col-form-label">Code Agence</label>
                            <div class="col-sm-2">
                                <input type="text" id="branch-code" name="branch" class="form-control" placeholder="Optionnel">
                            </div>
                        </div> -->

                        <div class="form-group row">
                            <div class="col-sm-8">
                                <button type="submit" class="btn btn-primary">Générer</button>
                                <button type="button" id="clear-btn" class="btn btn-light">Effacer</button>
                            </div>
                        </div>
                    </form>

                    <script>
                        (function(){
                            var form = document.getElementById('digital-form');
                            var clear = document.getElementById('clear-btn');
                            form.addEventListener('submit', function(ev){
                                ev.preventDefault();
                                var acct = document.getElementById('account-number').value.trim();
                                // var branch = document.getElementById('branch-code').value.trim();
                                if(!acct){
                                    alert('Veuillez saisir un numéro de compte.');
                                    return;
                                }
                                var url = 'formulaire_produits.html?account=' + encodeURIComponent(acct) ;
                                window.location = url;
                            });
                            clear.addEventListener('click', function(){
                                document.getElementById('account-number').value = '';
                                // document.getElementById('branch-code').value = '';
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>

 <?php include('includes/scriptJs.php')?>
</body>
</html>