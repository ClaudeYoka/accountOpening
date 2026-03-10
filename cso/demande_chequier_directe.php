<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<body>
    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Demande de Chéquier</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="demande_chequier">Demandes de Chéquiers</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Nouvelle Demande</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="pd-20 card-box mb-30">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Formulaire de Demande de Chéquier</h4>
                            <p class="mb-20">Remplissez les informations du client et sélectionnez les types de chéquiers demandés</p>
                        </div>
                    </div>

                    <form id="chequerForm">
                        <div class="row">
                            <!-- NOM DU CLIENT -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Nom du Client <span style="color: red;">*</span></label>
                                    <input type="text" id="client_name" name="client_name" class="form-control" required placeholder="Ex: Jean Dupont" autocomplete="off">
                                </div>
                            </div>

                            <!-- AGENCE / BRANCH -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Agence <span style="color: red;">*</span></label>
                                    <select id="branch_code" name="branch_code" class="custom-select form-control" required autocomplete="off">
                                        <option value="">-- Sélectionner une agence --</option>
                                        <?php
                                        include('../includes/config.php');
                                        $query = mysqli_query($conn, "SELECT * FROM tblagences ORDER BY AgenceName ASC");
                                        while($row = mysqli_fetch_array($query)){
                                            echo "<option value=\"" . htmlspecialchars($row['AgenceShortName']) . "\">" . htmlspecialchars($row['AgenceName']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- NUMÉRO DE COMPTE -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Compte <span style="color: red;">*</span></label>
                                    <input type="text" id="account_number" name="account_number" class="form-control" required placeholder="Numéro de compte" autocomplete="off">
                                </div>
                            </div>

                            <!-- CLÉ RIB -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>RIB<span style="color: red;">*</span></label>
                                    <input type="text" id="rib_key" name="rib_key" class="form-control" required placeholder="RIB du client" maxlength="2" autocomplete="off">
                                </div>
                            </div>

                            <!-- ADRESSE DU CLIENT -->
                            <div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <label>Adresse <span style="color: red;">*</span></label>
                                    <textarea id="address" name="address" class="form-control" rows="2" required placeholder="Ex: 123 rue de la Paix, Brazzaville" autocomplete="off"></textarea>
                                </div>
                            </div>

                            <!-- NUMÉRO DE TÉLÉPHONE -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Téléphone <span style="color: red;">*</span></label>
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control" required placeholder="Ex: +242 06 123 45 67" autocomplete="off">
                                </div>
                            </div>

                            <!-- EMAIL -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Email <span style="color: red;">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" required placeholder="Ex: client@example.com" autocomplete="off">
                                </div>
                            </div>

                            <!-- NOMBRE DE FEUILLES (CHÉQUIERS) -->
                            <div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <label>Nombre de Feuilles <span style="color: red;">*</span></label>
                                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input chequier-checkbox" id="chequier_25" name="chequier" value="25">
                                            <label class="custom-control-label" for="chequier_25">
                                                <strong>25 Feuilles</strong>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input chequier-checkbox" id="chequier_50" name="chequier" value="50">
                                            <label class="custom-control-label" for="chequier_50">
                                                <strong>50 Feuilles</strong>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input chequier-checkbox" id="chequier_100" name="chequier" value="100">
                                            <label class="custom-control-label" for="chequier_100">
                                                <strong>100 Feuilles</strong>
                                            </label>
                                        </div>
                                        <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Au moins un chéquier doit être sélectionné</small>
                                    </div>
                                </div>
                            </div>

                            <!-- QUANTITÉ SÉLECTIONNÉE / MANUELLE -->
                            <div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <label>Quantité Totale de Chéquiers <span style="color: red;">*</span></label>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12">
                                            <div style="background: #e8f5e9; padding: 15px; border-radius: 4px; text-align: center;">
                                                <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Basée sur les types sélectionnés</div>
                                                <h3 style="color: #2e7d32; margin: 0;">
                                                    <span id="autoQuantity">0</span> chéquier(s)
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-12">
                                            <input type="number" id="manual_quantity" name="manual_quantity" class="form-control" min="1" placeholder="Saisir une quantité " autocomplete="off">
                                            <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Laissez vide si la quantité est d'un seul chéquier </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BUTTONS -->
                            <div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #011e2563 0%, #007ff5 100%); border: none;">
                                        <i class="icon-copy dw dw-check"></i> Soumettre la Demande
                                    </button>
                                    <button type="reset" class="btn btn-secondary ml-2">
                                        <i class="icon-copy dw dw-refresh-2"></i> Réinitialiser
                                    </button>
                                    <a href="demande_chequier.php" class="btn btn-light ml-2">
                                        <i class="icon-copy dw dw-arrow-left"></i> Retour
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>

    <script>
        // Mettre à jour la quantité automatique
        function updateQuantity() {
            const chequerChecked = Array.from(document.querySelectorAll('input[name="chequier"]:checked')).length;
            const manualQuantity = document.getElementById('manual_quantity').value;
            const displayQuantity = manualQuantity ? parseInt(manualQuantity) : chequerChecked;
            document.getElementById('autoQuantity').textContent = displayQuantity;
        }

        document.querySelectorAll('.chequier-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateQuantity);
        });

        // Écouter les changements de quantité manuelle
        document.getElementById('manual_quantity').addEventListener('input', updateQuantity);

        // Soumettre le formulaire
        document.getElementById('chequerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const chequerChecked = Array.from(document.querySelectorAll('input[name="chequier"]:checked')).map(cb => cb.value);
            if (chequerChecked.length === 0) {
                alert('❌ Veuillez sélectionner au moins un type de chéquier');
                return;
            }

            // Déterminer la quantité (manuelle ou automatique)
            const manualQuantity = document.getElementById('manual_quantity').value;
            const quantityToUse = manualQuantity ? parseInt(manualQuantity) : chequerChecked.length;

            if (quantityToUse < 1) {
                alert('❌ La quantité doit être au minimum 1');
                return;
            }

            const formData = {
                client_name: document.getElementById('client_name').value,
                branch_code: document.getElementById('branch_code').value,
                account_number: document.getElementById('account_number').value,
                rib_key: document.getElementById('rib_key').value,
                address: document.getElementById('address').value,
                phone_number: document.getElementById('phone_number').value,
                email: document.getElementById('email').value,
                chequier: chequerChecked,
                quantity: quantityToUse,
                status: 'En Cours'
            };

            if (!confirm('✓ Êtes-vous sûr de vouloir soumettre cette demande de chéquier ?\n\nClient: ' + formData.client_name + '\nTéléphone: ' + formData.phone_number + '\nEmail: ' + formData.email + '\nCompte: ' + formData.account_number + '\nQuantité: ' + quantityToUse)) {
                return;
            }

            fetch('save_chequier_directe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✓ Demande enregistrée avec succès !\n\nID: ' + data.submission_id);
                    setTimeout(() => {
                        window.location.href = 'demande_chequier.php';
                    }, 1500);
                } else {
                    alert('✗ Erreur: ' + (data.message || 'Une erreur s\'est produite'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ Erreur de communication avec le serveur');
            });
        });
    </script>
</body>
</html>

