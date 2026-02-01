<?php
include('includes/header.php');
include('../includes/session.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die('ID invalide');
}

// Récupérer le dossier
$result = mysqli_query($conn, "SELECT * FROM tblCompte WHERE id = $id");
if (!$result || mysqli_num_rows($result) == 0) {
    die('Dossier non trouvé');
}

$compte = mysqli_fetch_assoc($result);
?>
<html>
<head>
    <?php include('includes/header.php'); ?>
</head>
<body>
<?php include('includes/preloader.php')?>
<?php include('includes/navbar.php')?>
<?php include('includes/right_sidebar.php')?>
<?php include('includes/left_sidebar.php')?>

<div class="main-container">
    <div class="pd-ltr-20 xs-pd-20-10">
        <div class="min-height-200px">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-8 col-sm-12">
                        <div class="title">
                            <h4>Détails du Dossier #<?php echo $id; ?></h4>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 text-right">
                        <a href="javascript:window.print()" class="btn btn-sm btn-primary">Imprimer</a>
                        <a href="generate_pdf.php?id=<?php echo $id; ?>" class="btn btn-sm btn-success">Télécharger PDF</a>
                        <a href="list_tblCompte.php" class="btn btn-sm btn-secondary">Retour</a>
                    </div>
                </div>
            </div>

            <div class="pd-20 card-box mb-30">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID Dossier :</strong> <?php echo htmlspecialchars($compte['id']); ?></p>
                        <p><strong>Emp ID :</strong> <?php echo htmlspecialchars($compte['emp_id']); ?></p>
                        <p><strong>Prénom / Nom :</strong> <?php echo htmlspecialchars($compte['firstname']); ?></p>
                        <p><strong>Email :</strong> <?php echo htmlspecialchars($compte['email'] ?? ''); ?></p>
                        <p><strong>Mobile Principal :</strong> <?php echo htmlspecialchars($compte['mobile1'] ?? ''); ?></p>
                        <p><strong>Mobile Alternatif :</strong> <?php echo htmlspecialchars($compte['mobile2'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ville :</strong> <?php echo htmlspecialchars($compte['ville'] ?? ''); ?></p>
                        <p><strong>Pays :</strong> <?php echo htmlspecialchars($compte['adr_pays'] ?? ''); ?></p>
                        <p><strong>Services intéressés :</strong> <?php echo htmlspecialchars($compte['services'] ?? ''); ?></p>
                        <p><strong>Type de Compte :</strong> <?php echo htmlspecialchars($compte['account_type'] ?? ''); ?></p>
                        <p><strong>Devise Préférée :</strong> <?php echo htmlspecialchars($compte['devise_pref'] ?? ''); ?></p>
                        <p><strong>Date Enregistrement :</strong> <?php echo htmlspecialchars($compte['date_enregistrement']); ?></p>
                    </div>
                </div>

                <hr>
                <h5>Informations Personnelles</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Titre :</strong> <?php echo htmlspecialchars($compte['titre'] ?? ''); ?></p>
                        <p><strong>Noms :</strong> <?php echo htmlspecialchars($compte['noms'] ?? ''); ?></p>
                        <p><strong>Prénoms :</strong> <?php echo htmlspecialchars($compte['prenom2'] ?? ''); ?></p>
                        <p><strong>Date de Naissance :</strong> <?php echo htmlspecialchars($compte['dob'] ?? ''); ?></p>
                        <p><strong>Genre :</strong> <?php echo htmlspecialchars($compte['genre'] ?? ''); ?></p>
                        <p><strong>Nationalité :</strong> <?php echo htmlspecialchars($compte['nationalite'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lieu de Naissance :</strong> <?php echo htmlspecialchars($compte['lieu_naiss'] ?? ''); ?></p>
                        <p><strong>Situation Matrimoniale :</strong> <?php echo htmlspecialchars($compte['situation'] ?? ''); ?></p>
                        <p><strong>Pays de Résidence :</strong> <?php echo htmlspecialchars($compte['pays'] ?? ''); ?></p>
                        <p><strong>Adresse Rue :</strong> <?php echo htmlspecialchars($compte['adr_rue'] ?? ''); ?></p>
                        <p><strong>Numéro de Document :</strong> <?php echo htmlspecialchars($compte['id_num'] ?? ''); ?></p>
                        <p><strong>Type de Document :</strong> <?php echo htmlspecialchars($compte['id_type'] ?? ''); ?></p>
                    </div>
                </div>

                <hr>
                <h5>Informations Fiscales & Emploi</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Pays Fiscal :</strong> <?php echo htmlspecialchars($compte['fiscal_pays'] ?? ''); ?></p>
                        <p><strong>NIP/NIF/SSN :</strong> <?php echo htmlspecialchars($compte['nip'] ?? ''); ?></p>
                        <p><strong>Employeur :</strong> <?php echo htmlspecialchars($compte['employeur'] ?? ''); ?></p>
                        <p><strong>Conditions d'Emploi :</strong> <?php echo htmlspecialchars($compte['cond'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Fourchette Revenu :</strong> <?php echo htmlspecialchars($compte['revenu'] ?? ''); ?></p>
                        <p><strong>Établissement :</strong> <?php echo htmlspecialchars($compte['etabliss'] ?? ''); ?></p>
                        <p><strong>Identifiant Étudiant :</strong> <?php echo htmlspecialchars($compte['ident_etud'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include('includes/scripts.php'); ?>
</body>
</html>
