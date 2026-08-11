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
                        <div class="col-md-6 col-sm-12 text-right">
                        <button type="button" id="btn_open_form" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #05b7e4 0%, #00455a 100%); border: none;">
                            <i class="dw dw-file"></i> Formulaire
                        </button>
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

                    <div id="chequier_form_section">
                    <form id="chequerForm">
                        <!-- RECHERCHE ET AUTO-REMPLISSAGE FLEXCUBE -->
                        <div class="row mb-20" style="border: 1px solid #e8e8e8; padding: 18px 20px; background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%); border-radius: 10px; margin-bottom: 12px; box-shadow: inset 0 1px 0 rgba(255,255,255,.8);">
                            <div class="col-md-12 col-sm-12">
                                <h5 style="margin-bottom: 6px; color: #0f2f45;">🔎 Recherche du compte client</h5>
                                <p style="margin-bottom: 12px; color: #56708a;">Saisissez le numéro du compte pour charger automatiquement les informations du client puis finalisez votre demande de chéquier.</p>
                            </div>
                            <div class="col-md-8 col-sm-12">
                                <div class="form-group">
                                    <input type="text" id="flexcube_search" class="form-control" placeholder="Saisissez le numéro de compte" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-12" style="display: flex; align-items: flex-end; gap: 10px;">
                                <button type="button" id="btn_search_flexcube" class="btn btn-info w-100" style="background: linear-gradient(135deg, #05b7e4 0%, #00455a 100%); border: none;">
                                    <i class="dw dw-search"></i> Rechercher
                                </button>
                                <button type="button" id="btn_apply_search" class="btn btn-outline-primary" style="white-space: nowrap;">
                                    <i class="dw dw-file"></i> Appliquer
                                </button>
                            </div>
                            <div class="col-md-12 col-sm-12" id="search_result" style="margin-top: 10px;"></div>
                        </div>

                        <div class="row" id="chequier_form_section" style="background: #ffffff; border: 1px solid #e8e8e8; border-radius: 10px; padding: 20px; margin-top: 4px; box-shadow: 0 4px 12px rgba(0,0,0,.03);">
                            <!-- NOM DU CLIENT -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Intitulé du Compte <span style="color: red;">*</span></label>
                                    <input type="text" id="client_name" name="client_name" class="form-control" required placeholder="Ex: YOKA MOSSA CLAUDE" autocomplete="off">
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
                                    <input type="text" id="account_number" name="account_number" class="form-control" required placeholder="N° Compte client" autocomplete="off" readonly>
                                </div>
                            </div>

                            <!-- TYPE DE COMPTE -->
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Type de Compte <span style="color: red;">*</span></label>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <label style="margin:0"><input type="checkbox" name="account_type" value="COURANT" id="acct_courant" checked> Courant</label>
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
                                    <input type="text" id="rib_key" name="rib_key" class="form-control" required placeholder="RIB du client" autocomplete="off" readonly>
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

                            <!-- EMAIL -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Email <span style="color: red;">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" required placeholder="Adresse Mail du client" autocomplete="off">
                                </div>
                            </div>

                            <!-- NUMÉRO DE SÉRIE -->
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Série De : </label>
                                        <input type="text" id="serial_number1" name="serial_number1" class="form-control" placeholder="Numéro de série carte" autocomplete="off" readonly>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Numéro de Série A : </label> <br>
                                        <input type="text" id="serial_number2" name="serial_number2" class="form-control" placeholder="Numéro de série carte" autocomplete="off" readonly>
                                </div>
                            </div>
                            
                        
                            <!-- ADRESSE DU CLIENT -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label>Adresse du Client <span style="color: red;">*</span></label>
                                    <textarea id="address" name="address" class="form-control" rows="2" required  autocomplete="off"></textarea>
                                </div>
                            </div>


                            <!-- NOMBRE DE FEUILLES (CHÉQUIERS) -->
                            <div class="col-md-6 col-sm-12">
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
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label>Quantité Totale de Chéquiers <span style="color: red;">*</span></label>
                                    <div class="row">
                                        <div class="col-md-8 col-sm-12">
                                            <div style="background: #e8f5e9; padding: 15px; border-radius: 4px; text-align: center;">
                                                <div style="font-size: 12px; color: #666; margin-bottom: 8px;">Quantité</div>
                                                <h3 style="color: #2e7d32; margin: 0;">
                                                    <span id="autoQuantity">0</span> chéquier(s)
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-6">
                                            <input type="number" id="manual_quantity" name="manual_quantity" class="form-control" min="1" autocomplete="off">
                                            <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Laissez vide si le nombre est 1 chéquier </small>
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
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>

    <div class="modal fade" id="duplicateRequestModal" tabindex="-1" role="dialog" aria-labelledby="duplicateRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:#fff3cd; border-bottom:1px solid #ffeeba;">
                    <h5 class="modal-title" id="duplicateRequestModalLabel" style="color:#856404;">Demande déjà existante</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="duplicateRequestModalBody">
                    Une demande de chéquier est déjà en cours pour ce compte.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btnContinueDuplicateRequest">Poursuivre quand même</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmSubmitModal" tabindex="-1" role="dialog" aria-labelledby="confirmSubmitModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background:#e8f5e9; border-bottom:1px solid #c8e6c9;">
                    <h5 class="modal-title" id="confirmSubmitModalLabel" style="color:#1b5e20;">Confirmation de la demande</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="confirmSubmitModalBody">
                    Vérification en cours...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" id="btnConfirmSubmitRequest">Confirmer l’envoi</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentAccountData = null;

        function focusFormSection() {
            const formSection = document.getElementById('chequier_form_section') || document.getElementById('chequerForm');
            if (!formSection) return;

            formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            formSection.style.transition = 'all 0.25s ease';
            formSection.style.boxShadow = '0 0 0 3px rgba(5, 183, 228, 0.18), 0 10px 24px rgba(0, 69, 90, 0.12)';
            setTimeout(() => {
                formSection.style.boxShadow = '';
            }, 1400);
        }

        function getFirstValue(source, keys) {
            if (!source || typeof source !== 'object') return '';
            for (const key of keys) {
                const value = source[key];
                if (typeof value === 'string' && value.trim()) return value.trim();
                if (typeof value === 'number' && value !== 0) return String(value);
            }
            return '';
        }

        function getSerialQuantity() {
            const manualQuantity = document.getElementById('manual_quantity').value;
            if (manualQuantity && Number(manualQuantity) > 0) {
                return Number(manualQuantity);
            }

            const selectedChequiers = Array.from(document.querySelectorAll('input[name="chequier"]:checked'));
            return selectedChequiers.length > 0 ? selectedChequiers.length : 1;
        }

        function getSelectedLeafCount() {
            const selectedChequiers = Array.from(document.querySelectorAll('input[name="chequier"]:checked'));
            if (selectedChequiers.length === 0) return 25;

            return selectedChequiers.reduce((total, checkbox) => total + Number(checkbox.value || 0), 0);
        }

        async function loadSerialNumbers() {
            try {
                const response = await fetch('get_last_serial_number.php');
                const data = await response.json();
                if (data.success) {
                    const start = Number(data.serial_number1 || 1);
                    const quantity = getSerialQuantity();
                    const leafCount = getSelectedLeafCount();
                    const end = start + (leafCount * quantity) - 1;
                    document.getElementById('serial_number1').value = start;
                    document.getElementById('serial_number2').value = end;
                }
            } catch (error) {
                console.error('Erreur génération numéros de série', error);
            }
        }

        function populateFormFromAccountData(accountData) {
            if (!accountData || typeof accountData !== 'object') return;

            const fullNameFromFields = [
                getFirstValue(accountData, ['first_name']),
                getFirstValue(accountData, ['last_name']),
                getFirstValue(accountData, ['middle_name'])
            ].filter(Boolean).join(' ');

            const fullName = getFirstValue(accountData, ['account_title', 'customer_name', 'account_name', 'name', 'full_name']) || fullNameFromFields;

            const accountNumber = getFirstValue(accountData, ['account_number', 'customer_account_number', 'numero_compte']);
            const phoneValue = getFirstValue(accountData, ['phone_number', 'telephone', 'mobile', 'phone', 'mobile_phone', 'tel']);
            const ribValue = getFirstValue(accountData, ['rib_key', 'rib', 'clearing_ac_no', 'RIB']);
            const emailValue = getFirstValue(accountData, ['email', 'email_address', 'adresse_email', 'mail', 'e_mail', 'Email']);
            const addressValue = getFirstValue(accountData, ['customer_address', 'address', 'account_address', 'adr_rue']);
            const branchValue = getFirstValue(accountData, ['branch_code', 'agency_code', 'branch']);

            const setValue = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.value = value || '';
            };

            setValue('client_name', fullName);
            setValue('account_number', accountNumber);
            setValue('phone_number', phoneValue);
            setValue('rib_key', ribValue);
            setValue('email', emailValue);
            setValue('address', addressValue);
            setValue('branch_code', branchValue);

            if (branchValue) {
                const branchSelect = document.getElementById('branch_code');
                if (branchSelect) {
                    const found = Array.from(branchSelect.options).some(option => option.value === branchValue);
                    if (found) branchSelect.value = branchValue;
                }
            }

            focusFormSection();
        }

        // Mettre à jour la quantité automatique
        function updateQuantity() {
            const chequerChecked = Array.from(document.querySelectorAll('input[name="chequier"]:checked')).length;
            const manualQuantity = document.getElementById('manual_quantity').value;
            const displayQuantity = manualQuantity ? parseInt(manualQuantity) : chequerChecked;
            document.getElementById('autoQuantity').textContent = displayQuantity;
        }

        document.querySelectorAll('.chequier-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateQuantity();
                loadSerialNumbers();
            });
        });

        // Écouter les changements de quantité manuelle
        document.getElementById('manual_quantity').addEventListener('input', () => {
            updateQuantity();
            loadSerialNumbers();
        });

        window.addEventListener('load', () => {
            updateQuantity();
            loadSerialNumbers();
        });

        let pendingFormData = null;
        let pendingSubmitMode = 'normal';

        function showDuplicateModal(message) {
            const body = document.getElementById('duplicateRequestModalBody');
            if (body) body.textContent = message || 'Une demande de chéquier est déjà en cours pour ce compte.';
            const modal = document.getElementById('duplicateRequestModal');
            if (modal) {
                $('#duplicateRequestModal').modal('show');
            }
        }

        function showConfirmSubmitModal(summary) {
            const body = document.getElementById('confirmSubmitModalBody');
            if (body) {
                const feesText = summary && summary.fees ? summary.fees : 'Non défini';
                body.innerHTML = '';
                body.innerHTML = `
                    <div style="font-size:15px; line-height:1.6;">
                        <p><strong>Confirmez l’envoi de cette demande de chéquier.</strong></p>
                        <ul style="padding-left:18px; margin:0 0 10px 0;">
                            <li><strong>Client :</strong> ${summary.client || 'Non renseigné'}</li>
                            <li><strong>Compte :</strong> ${summary.account || 'Non renseigné'}</li>
                            <li><strong>Agence :</strong> ${summary.agency || 'Non renseignée'}</li>
                            <li><strong>Quantité :</strong> ${summary.quantity || 0} chéquier(s)</li>
                            <li><strong>Type(s) :</strong> ${summary.types || 'Non renseigné'}</li>
                            <li><strong>Frais :</strong> ${feesText}</li>
                        </ul>
                        <p style="margin:0; color:#1b5e20;">Si les informations sont correctes, cliquez sur <strong>Confirmer l’envoi</strong>.</p>
                    </div>
                `;
            }

            const modal = document.getElementById('confirmSubmitModal');
            if (modal) {
                $('#confirmSubmitModal').modal('show');
            }
        }

        function submitChequierRequest(formData, options = {}) {
            const payload = {
                ...formData,
                check_only: Boolean(options.checkOnly),
                force_submit: Boolean(options.forceSubmit)
            };

            return fetch('save_chequier_directe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
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
                    return null;
                }

                return data;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ Erreur de communication avec le serveur');
                return null;
            });
        }

        // Soumettre le formulaire
        document.getElementById('chequerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const chequerChecked = Array.from(document.querySelectorAll('input[name="chequier"]:checked')).map(cb => cb.value);
            if (chequerChecked.length === 0) {
                alert('❌ Veuillez sélectionner au moins un type de chéquier');
                return;
            }

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
                serial_number: document.getElementById('serial_number1')?.value || '',
                serial_number1: document.getElementById('serial_number1')?.value || '',
                serial_number2: document.getElementById('serial_number2')?.value || '',
                rib_key: document.getElementById('rib_key').value,
                address: document.getElementById('address').value,
                phone_number: document.getElementById('phone_number').value,
                email: document.getElementById('email').value,
                chequier: chequerChecked,
                quantity: quantityToUse,
                status: 'encours'
            };

            pendingFormData = formData;

            submitChequierRequest(formData, { checkOnly: true }).then(data => {
                if (!data) {
                    return;
                }

                if (data.status === 'duplicate') {
                    showDuplicateModal(data.message || 'Une demande de chéquier est déjà en cours pour ce compte.');
                    return;
                }

                if (data.status === 'ready') {
                    showConfirmSubmitModal(data.request_summary || {});
                    return;
                }

                if (data.status === 'success') {
                    submitChequierRequest(formData, { checkOnly: false }).then(finalData => {
                        if (!finalData) {
                            return;
                        }

                        if (finalData.status === 'success') {
                            alert('✓ ' + (finalData.message || 'Demande enregistrée avec succès !') + '\n\nID: ' + finalData.submission_id);
                            setTimeout(() => {
                                window.location.href = 'demande_chequier.php';
                            }, 1500);
                        } else {
                            alert('✗ Erreur: ' + (finalData.message || 'Une erreur s\'est produite'));
                        }
                    });
                } else {
                    alert('✗ Erreur: ' + (data.message || 'Une erreur s\'est produite'));
                }
            });
        });

        document.getElementById('btnConfirmSubmitRequest').addEventListener('click', function() {
            if (!pendingFormData) {
                $('#confirmSubmitModal').modal('hide');
                return;
            }

            $('#confirmSubmitModal').modal('hide');

            submitChequierRequest(pendingFormData, { checkOnly: false, forceSubmit: pendingSubmitMode === 'force' }).then(data => {
                if (!data) {
                    return;
                }

                if (data.status === 'success') {
                    alert('✓ ' + (data.message || 'Demande enregistrée avec succès !') + '\n\nID: ' + data.submission_id);
                    setTimeout(() => {
                        window.location.href = 'demande_chequier.php';
                    }, 1500);
                } else {
                    alert('✗ Erreur: ' + (data.message || 'Une erreur s\'est produite'));
                }
            });
        });

        document.getElementById('btnContinueDuplicateRequest').addEventListener('click', function() {
            if (!pendingFormData) {
                $('#duplicateRequestModal').modal('hide');
                return;
            }

            $('#duplicateRequestModal').modal('hide');
            pendingSubmitMode = 'force';
            showConfirmSubmitModal({
                client: pendingFormData.client_name || '',
                account: pendingFormData.account_number || '',
                agency: pendingFormData.branch_code || '',
                quantity: pendingFormData.quantity || 0,
                types: (pendingFormData.chequier || []).join(', '),
                fees: 'Vérification en cours'
            });
        });

        function redirectToChequierForm(data) {
            const payload = data ? JSON.stringify(data) : '{}';
            const url = 'formulaire_chequier.html?clientData=' + encodeURIComponent(payload);
            window.location.href = url;
        }

        document.getElementById('btn_apply_search').addEventListener('click', function() {
            if (currentAccountData) {
                redirectToChequierForm(currentAccountData);
            } else {
                alert('⚠ Aucune donnée de recherche n\'a encore été chargée.');
            }
        });

        document.getElementById('btn_open_form').addEventListener('click', function() {
            if (currentAccountData) {
                redirectToChequierForm(currentAccountData);
            } else {
                redirectToChequierForm(null);
            }
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
                        const accountData = data.data || data;
                        const rawData = data.raw || {};
                        const mergedAccountData = { ...rawData, ...accountData };
                        currentAccountData = mergedAccountData;
                        
                        console.log('Données à utiliser:', mergedAccountData);
                        populateFormFromAccountData(mergedAccountData);
                        focusFormSection();

                        resultDiv.innerHTML = '<div style="color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px;"><strong>✓ Compte trouvé!</strong> Les informations du client ont été chargées dans le formulaire.</div>';
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

