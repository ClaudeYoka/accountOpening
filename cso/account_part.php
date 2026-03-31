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
                                <h4>Enregistrement Client </h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="">Enregistrer Dossier</li>
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
                        <style>
                        /* Local styles to mimic the screenshot */
                        .section-title {background:#9ec0e9;padding:8px 12px;color:#fff;font-weight:600;margin-bottom:10px}
                        .sub-section {background:#f1f4f8;padding:8px;margin-bottom:10px;border:1px solid #d6e3f3}
                        .field-label{font-weight:600;color:#2b5a80}
                        .box-activity{border:2px solid #50a3e0;padding:12px;background:#fff}
                        .box-activity.student{border-color:#2fb673}
                        .form-row{margin-bottom:10px}
                        .required{color:#d00}
                        </style>

                        <form method="post" action="" class="">
                            <div class="row form-row">
                                <div class="col-md-8">
                                    <label class="field-label">Rechercher par numéro de compte / ID / Numéro de document (id_num) / NIP :</label>
                                    <input type="text" id="accountSearch" class="form-control" placeholder="Entrez numéro de compte, ID, id_num ou NIP" />
                                </div>
                                <div class="col-md-2" style="padding-top: 32px;">
                                    <button type="button" id="btnSearchAccount" class="btn btn-primary">Rechercher</button>
                                </div>
                                <div class="col-md-2" style="padding-top: 36px;">
                                    <div id="searchMessage" class="text-danger"></div>
                                </div>
                            </div>
                            <?php if (isset($row) && !empty($row['FirstName'])): ?>
                                <input type="hidden" name="firstname" value="<?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName'], ENT_QUOTES); ?>">
                            <?php endif; ?>

                            <div class="section">
                                <div class="section-title">Aidez-nous à personnaliser votre expérience bancaire</div>
                                <div class="sub-section">
                                    <div class="row form-row">
                                        <div class="col-md-12">
                                            <label class="field-label">Quels services financiers vous intéressent ?</label>
                                            <div>
                                                <label class="mr-3"><input type="checkbox" name="srv[]" value="Epargne"> Epargne</label>
                                                <label class="mr-3"><input type="checkbox" name="srv[]" value="Transferts"> Transferts</label>
                                                <label class="mr-3"><input type="checkbox" name="srv[]" value="Assurance"> Assurance</label>
                                                <label class="mr-3"><input type="checkbox" name="srv[]" value="Investissements"> Investissements</label>
                                                <label class="mr-3"><input type="checkbox" name="srv[]" value="Autre"> Autre</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-4">
                                            <label class="field-label">Type de compte :</label>
                                            <div>
                                                <label class="mr-2"><input type="checkbox" name="account_type[]" value="Compte courant"> Compte Courant</label>
                                                <label class="mr-2"><input type="checkbox" name="account_type[]" value="Compte épargne"> Compte Epargne</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="field-label">Devise préférée :</label>
                                            <input type="text" name="devise_pref" class="form-control" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="field-label">Objectif Principal du Compte :</label>
                                            <input type="text" name="objectif" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-12">
                                            <label class="field-label">Comment préférez-vous accéder aux services bancaires ?</label>
                                            <div>
                                                <label class="mr-3"><input type="checkbox" name="access[]" value="Cheque"> Chéquier</label>
                                                <label class="mr-3"><input type="checkbox" name="access[]" value="DebitCard"> Carte de débit</label>
                                                <label class="mr-3"><input type="checkbox" name="access[]" value="Prepaid"> Carte prépayée</label>
                                                <label class="mr-3"><input type="checkbox" name="access[]" value="Mobile"> Services bancaires mobiles</label>
                                                <label class="mr-3"><input type="checkbox" name="access[]" value="Online"> Services bancaires en ligne</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <div class="section-title">À propos de Vous</div>
                                <div class="sub-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="field-label">Nom(s) :</label>
                                            <input type="text" name="noms" class="form-control" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="field-label">Prénom(s) :</label>
                                            <input type="text" name="prenom2" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-4">
                                            <label class="field-label">Nationalité :</label>
                                            <input type="text" name="nationalite" class="form-control" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="field-label">Lieu de naissance :</label>
                                            <input type="text" name="lieu_naiss" class="form-control" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="field-label">Pays de résidence :</label>
                                            <input type="text" name="pays" class="form-control" />
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-6">
                                            <label class="field-label">Date de naissance :</label>
                                            <input type="date" name="dob" class="form-control" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="field-label">Situation matrimoniale :</label>
                                                <select name="situation" class="form-control">
                                                    <option value="">--Selectionner----</option>
                                                    <option value="Marie">Marié</option>
                                                    <option value="Celib">Célibataire</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-6">
                                            <label class="field-label">Pièce d'identité :</label>
                                            <div>
                                                <label class="mr-2"><input type="checkbox" name="id_type[]" value="CNI"> Carte d'identité nationale</label>
                                                <label class="mr-2"><input type="checkbox" name="id_type[]" value="Passport"> Passeport</label>
                                            </div>
                                            <input type="text" name="id_num" class="form-control mt-2" placeholder="Numéro du document" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="field-label">Date de délivrance / expiration :</label>
                                            <div class="row">
                                                <div class="col-md-6"><input type="date" name="date_deliv" class="form-control" /></div>
                                                <div class="col-md-6"><input type="date" name="date_exp" class="form-control" /></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row form-row">
                                        <div class="col-md-6">
                                            <label class="field-label">Informations fiscales : Pays</label>
                                            <input type="text" name="fiscal_pays" class="form-control" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="field-label">NIP/NIF/SSN :</label>
                                            <input type="text" name="nip" class="form-control" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <div class="section-title">Comment rester en contact avec vous ?</div>
                                <div class="sub-section">
                                    <div class="row form-row">
                                        <div class="col-md-6"><label class="field-label">Numéro mobile principal :</label><input type="number" name="mobile1" class="form-control" /></div>
                                        <div class="col-md-6"><label class="field-label">Numéro mobile alternatif :</label><input type="number" name="mobile2" class="form-control" /></div>
                                    </div>
                                    <div class="row form-row">
                                        <div class="col-md-6"><label class="field-label">Adresse résidentielle (Rue/Avenue):</label><input type="text" name="adr_rue" class="form-control" /></div>
                                        <div class="col-md-3"><label class="field-label">Ville/Comté :</label><input type="text" name="ville" class="form-control" /></div>
                                        <div class="col-md-3"><label class="field-label">Pays :</label><input type="text" name="adr_pays" class="form-control" /></div>
                                    </div>
                                </div>
                            </div>

                            <div class="section">
                                <div class="section-title">Parlez-nous de votre activité</div>
                                <div class="sub-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="box-activity">
                                                <label class="field-label">Salarié</label>
                                                <div class="form-row"><input type="text" name="employeur" class="form-control" placeholder="Nom de l'employeur" /></div>
                                                <div class="form-row"><label class="field-label">Conditions d'emploi :</label> <br/>
                                                    <label class="mr-2"><input type="checkbox" name="cond[]" value="CDI"> CDI</label>
                                                    <label class="mr-2"><input type="checkbox" name="cond[]" value="CDD"> CDD</label>
                                                </div>
                                                <div class="form-row"><label class="field-label">Fourchette de revenu mensuel brut :</label>
                                                    <select name="revenu" class="form-control">
                                                        <option value="">--</option>
                                                        <option value="<150000"> Inférieur à 150 000</option>
                                                        <option value="150000-500000">150 000–500 000</option>
                                                        <option value=">500000">Supérieur à 500 000</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="box-activity student">
                                                <label class="field-label">Étudiant</label>
                                                <div class="form-row"><input type="text" name="etabliss" class="form-control" placeholder="Nom de l'établissement" /></div>
                                                <div class="form-row"><input type="text" name="ident_etud" class="form-control" placeholder="Identifiant étudiant" /></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <button type="submit" name="apply" id="apply" class="btn btn-primary">Enregistrer Dossier</button>
                                </div>
                            </div>

                        </form>
                    </div>
            </div>
            </div>
        </div>
    </div>

<?php include('includes/scripts.php'); ?>

<script>
// AJAX search for account and fill form
$(document).ready(function(){
    function showMessage(msg, isError) {
        $('#searchMessage').text(msg).toggleClass('text-danger', !!isError).toggleClass('text-success', !isError);
    }

    $('#btnSearchAccount').on('click', function(e){
        e.preventDefault();
        var q = $('#accountSearch').val().trim();
        if (!q) { showMessage('Veuillez saisir un numéro.', true); return; }
        showMessage('Recherche en cours...', false);
        // Clean up numeric entries a bit before sending to server
        var cleanedQ = q;
        if (/^[+\d][\d\s\-\.()+]*$/.test(q)) {
            cleanedQ = q.replace(/[^0-9]/g, '');
        }
        $.post('search_compte.php', { q: cleanedQ }, function(resp){
            if (resp && resp.status === 'ok' && resp.data) {
                var data = resp.data;
                // fill relevant fields if present
                if (data.noms) $('input[name="noms"]').val(data.noms);
                if (data.prenom2) $('input[name="prenom2"]').val(data.prenom2);
                if (data.nationalite) $('input[name="nationalite"]').val(data.nationalite);
                if (data.lieu_naiss) $('input[name="lieu_naiss"]').val(data.lieu_naiss);
                if (data.pays) $('input[name="pays"]').val(data.pays);
                if (data.dob) $('input[name="dob"]').val(data.dob);
                if (data.situation) $('select[name="situation"]').val(data.situation);
                if (data.id_num) $('input[name="id_num"]').val(data.id_num);
                if (data.date_deliv) $('input[name="date_deliv"]').val(data.date_deliv);
                if (data.date_exp) $('input[name="date_exp"]').val(data.date_exp);
                if (data.fiscal_pays) $('input[name="fiscal_pays"]').val(data.fiscal_pays);
                if (data.nip) $('input[name="nip"]').val(data.nip);
                if (data.mobile1) $('input[name="mobile1"]').val(data.mobile1);
                if (data.mobile2) $('input[name="mobile2"]').val(data.mobile2);
                if (data.adr_rue) $('input[name="adr_rue"]').val(data.adr_rue);
                if (data.ville) $('input[name="ville"]').val(data.ville);
                if (data.adr_pays) $('input[name="adr_pays"]').val(data.adr_pays);
                if (data.employeur) $('input[name="employeur"]').val(data.employeur);
                if (data.revenu) $('select[name="revenu"]').val(data.revenu);
                if (data.etabliss) $('input[name="etabliss"]').val(data.etabliss);
                if (data.ident_etud) $('input[name="ident_etud"]').val(data.ident_etud);
                // services & type_compte & access are stored as comma-separated in DB
                if (data.services) {
                    var svc = data.services.split(',').map(function(s){ return s.trim(); });
                    $('input[name="srv[]"]').prop('checked', false);
                    svc.forEach(function(s){ $('input[name="srv[]"][value="'+s+'"]').prop('checked', true); });
                }
                if (data.account_type) {
                    var t = data.account_type.split(',').map(function(s){ return s.trim(); });
                    $('input[name="account_type[]"]').prop('checked', false);
                    t.forEach(function(s){ $('input[name="account_type[]"][value="'+s+'"]').prop('checked', true); });
                }
                if (data.access) {
                    var a = data.access.split(',').map(function(s){ return s.trim(); });
                    $('input[name="access[]"]').prop('checked', false);
                    a.forEach(function(s){ $('input[name="access[]"][value="'+s+'"]').prop('checked', true); });
                }
                if (data.devise_pref) $('input[name="devise_pref"]').val(data.devise_pref);
                if (data.objectif) $('input[name="objectif"]').val(data.objectif);
                showMessage('Dossier trouvé et rempli.', false);
            } else {
                showMessage(resp.message || 'Compte non trouvé ou non autorisé.', true);
            }
        }, 'json').fail(function(){
            showMessage('Erreur de recherche. Réessayez.', true);
        });
    });
});
</script>


</body>
</html>
