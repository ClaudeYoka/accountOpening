<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
?>


<?php
// Vérifier que l'utilisateur est authentifié AVANT d'inclure header.php
if (!isset($_SESSION['emp_id'])) {
    header('Location: ../index.php');
    exit('Authentification requise');
}
?>

<?php include('includes/header.php')?>

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
                        <!-- RECHERCHE ET AUTO-REMPLISSAGE FLEXCUBE -->
                        <div class="row mb-30" style="border: 1px solid #e8e8e8; padding: 15px; background: #f5f5f5; border-radius: 4px; margin-bottom: 20px;">
                            <div class="col-md-12 col-sm-12">
                                <h5 style="margin-bottom: 15px; color: #333;">📋 Rechercher un Compte</h5>
                            </div>
                            <div class="col-md-8 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Compte Flexcube</label>
                                    <input type="text" id="flexcube_search" class="form-control" placeholder="Ex: 37155023238" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12" style="display: flex; align-items: flex-end;">
                                <button type="button" id="btn_search_flexcube" class="btn btn-info w-100" style="background: linear-gradient(135deg, #05b7e4 0%, #00455a 100%); border: none;">
                                    <i class="dw dw-search"></i> Rechercher
                                </button>
                            </div>
                            <div class="col-md-12 col-sm-12" id="search_result" style="margin-top: 10px;"></div>
                        </div>

                        <hr style="margin: 30px 0;">

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
                                    <label>Numéro Compte <span style="color: red;">*</span></label>
                                    <input type="text" id="account_number" name="account_number" class="form-control" required placeholder="Numéro de compte client" autocomplete="off">
                                </div>
                            </div>

                            <!-- TYPE DE COMPTE -->
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Type de Compte <span style="color: red;">*</span></label>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <label style="margin:0"><input type="checkbox" name="account_type" value="COURANT" id="acct_courant"> Courant</label>
                                        <label style="margin:0"><input type="checkbox" name="account_type" value="EPARGNE" id="acct_epargne"> Épargne</label>
                                    </div>
                                </div>
                            </div>

                            <!-- CARTE ? -->
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>CLient a une Carte ? <span style="color: red;">*</span></label>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <label style="margin:0"><input type="checkbox" name="carte" value="OUI" id="acct_courant"> OUI</label>
                                        <label style="margin:0"><input type="checkbox" name="carte" value="NON" id="acct_epargne"> NON</label>
                                    </div>
                                </div>
                            </div>

                            <!-- CLÉ RIB -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>RIB<span style="color: red;">*</span></label>
                                    <input type="text" id="rib_key" name="rib_key" class="form-control" required placeholder="RIB du client" autocomplete="off">
                                </div>
                            </div>

                            
                            <!-- FRAIS ? -->
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Frais Prélevé ? <span style="color: red;">*</span></label>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <label style="margin:0"><input type="checkbox" name="frais" value="OUI" id="acct_courant"> OUI</label>
                                        <label style="margin:0"><input type="checkbox" name="frais" value="NON" id="acct_epargne"> NON</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Client Enrollé ? <span style="color: red;">*</span></label>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <label style="margin:0"><input type="checkbox" name="enrolled" value="OUI" id="acct_courant"> OUI</label>
                                        <label style="margin:0"><input type="checkbox" name="enrolled" value="NON" id="acct_epargne"> NON</label>
                                    </div>
                                </div>
                            </div>


                            <!-- NUMÉRO DE TÉLÉPHONE -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Téléphone <span style="color: red;">*</span></label>
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control" required placeholder="Téléphone du client" autocomplete="off">
                                </div>
                            </div>
                            <!-- NUMÉRO DE SÉRIE -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Série</label>
                                    <input type="text" id="serial_number" name="serial_number" class="form-control" placeholder="Numéro de série carte" autocomplete="off">
                                </div>
                            </div>

                            <!-- EMAIL -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Email <span style="color: red;">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" required placeholder="Adresse Mail du client" autocomplete="off">
                                </div>
                            </div>

                            <!-- ADRESSE DU CLIENT -->
                            <div class="col-md-12 col-sm-12">
                                <div class="form-group">
                                    <label>Adresse <span style="color: red;">*</span></label>
                                    <textarea id="address" name="address" class="form-control" rows="2" required placeholder="Ex: 123 rue de la Paix, Brazzaville" autocomplete="off"></textarea>
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
                                        <div class="col-md-6 col-sm-6">
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
                account_type: Array.from(document.querySelectorAll('input[name="account_type"]:checked')).map(cb => cb.value),
                carte: Array.from(document.querySelectorAll('input[name="carte"]:checked')).map(cb => cb.value),
                frais: Array.from(document.querySelectorAll('input[name="frais"]:checked')).map(cb => cb.value),
                enrolled: Array.from(document.querySelectorAll('input[name="enrolled"]:checked')).map(cb => cb.value),
                serial_number: document.getElementById('serial_number')?.value || '',
                rib_key: document.getElementById('rib_key').value,
                address: document.getElementById('address').value,
                phone_number: document.getElementById('phone_number').value,
                email: document.getElementById('email').value,
                chequier: chequerChecked,
                quantity: quantityToUse,
                status: 'encours'
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
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error('Response not JSON:', text);
                    let msg = '✗ Erreur de communication avec le serveur.';
                    if (!text || text.trim() === '') {
                        msg += '\nAucune donnée retournée (vide).';
                    } else if (text.match(/<\/?html|<\/?script/i)) {
                        msg += '\nLe serveur renvoie une page HTML (possiblement une redirection de session). Veuillez vérifier que vous êtes toujours connecté.';
                    } else {
                        msg += '\nRéponse inattendue: ' + text.substring(0, 400);
                    }
                    alert(msg);
                    return;
                }
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

        // Recherche et auto-remplissage Flexcube
        document.getElementById('btn_search_flexcube').addEventListener('click', function() {
            const accountNumber = document.getElementById('flexcube_search').value.trim();
            
            if (!accountNumber) {
                alert('Veuillez entrer un numéro de compte');
                return;
            }

            const resultDiv = document.getElementById('search_result');
            resultDiv.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Recherche en cours...';

            fetch('fetch_account_flexcube.php?account=' + encodeURIComponent(accountNumber))
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);
                    
                    if (data.success || data.account_number) {
                        // Auto-remplissage des champs
                        console.log('Remplissage des champs...');
                        
                        // Les données sont imbriquées dans data.data si on utilise la nouvelle API
                        let accountData = data.data || data;
                        
                        console.log('Données à utiliser:', accountData);
                        
                        // Remplir account_number
                        const accField = document.getElementById('account_number');
                        if (accField) {
                            accField.value = accountData.account_number || '';
                            console.log('account_number set to:', accField.value);
                        }
                        
                        // Remplir client_name
                        const nameField = document.getElementById('client_name');
                        if (nameField) {
                            nameField.value = (accountData.first_name || '') + ' ' + (accountData.last_name || '');
                            console.log('client_name set to:', nameField.value);
                        }
                        
                        // Remplir phone_number
                        const phoneField = document.getElementById('phone_number');
                        if (phoneField) {
                            phoneField.value = accountData.phone_number || accountData.telephone || '';
                            console.log('phone_number set to:', phoneField.value);
                        }

                        // Remplir rib_key
                        const ribField = document.getElementById('rib_key');
                        if (ribField) {
                            ribField.value = accountData.rib_key || accountData.rib || accountData.clearing_ac_no || '';
                            console.log('rib_key set to:', ribField.value);
                        }
                        
                        // Remplir email
                        const emailField = document.getElementById('email');
                        if (emailField) {
                            emailField.value = accountData.email || '';
                            console.log('email set to:', emailField.value);
                        }
                        
                        // Remplir address
                        const addrField = document.getElementById('address');
                        if (addrField) {
                            addrField.value = accountData.customer_address || '';
                            console.log('address set to:', addrField.value);
                        }
                        
                        // Remplir branch_code
                        const branchField = document.getElementById('branch_code');
                        if (branchField) {
                            branchField.value = accountData.branch_code || '';
                            console.log('branch_code set to:', branchField.value);
                        }

                        resultDiv.innerHTML = '<div style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px;"><strong>✓ Compte trouvé!</strong> Les informations ont été pré-remplies.</div>';
                    } else {
                        resultDiv.innerHTML = '<div style="color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px;"><strong>✗ Compte non trouvé</strong><br>' + (data.error || 'Veuillez vérifier le numéro de compte') + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    resultDiv.innerHTML = '<div style="color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px;"><strong>✗ Erreur de communication</strong><br>Impossible de récupérer les données du compte. Veuillez réessayer.</div>';
                });
        });

        // Permettre la recherche avec la touche Entrée
        document.getElementById('flexcube_search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('btn_search_flexcube').click();
            }
        });
    </script>
</body>
</html>

