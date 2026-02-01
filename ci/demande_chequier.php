<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
// Récupérer les données JSON de ecobank_form_submissions pour les demandes de chéquier
// Les chéquiers sont stockés dans le JSON data du formulaire

function get_chequier_requests($conn, $account_search = '') {
    $where = "tc.type_compte LIKE '%Feuilles%'";
    
    // Si recherche par numéro de compte
    if (!empty($account_search)) {
        $account_search = mysqli_real_escape_string($conn, $account_search);
        $where .= " AND tc.mobile1 = '$account_search'";
    }
    
    $query = "SELECT
                tc.id,
                tc.firstname as customer_name,
                tc.branch_code,
                tc.mobile1 as account_number,
                tc.email,
                tc.type_compte,
                tc.adr_rue as address,
                tc.nip as rib_key,
                tc.etabliss as quantity,
                tc.access as status,
                tc.date_enregistrement as created_at,
                tc.emp_id,
                tb.DepartmentName as agency_name,
                te.FirstName,
                te.LastName,
                te.EmailId as cso_email
            FROM tblcompte tc
            LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
            LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
            WHERE $where
            ORDER BY tc.date_enregistrement DESC";

    return mysqli_query($conn, $query);
}

// Ensure a table exists to track chequier request statuses and history
function ensureChequierStatusTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS chequier_status (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        status VARCHAR(100) NOT NULL,
        changed_by INT DEFAULT NULL,
        changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $sql);
}

// Traiter les données JSON et extraire les infos de chéquier
function extract_chequier_info($input) {
    // Accepte soit une ligne de tblcompte (array) soit une chaîne JSON
    $chequier_info = array('requested' => false, 'types' => array(), 'quantity' => 0);

    if (is_array($input)) {
        // Cas tblcompte : colonne 'type_compte' contient '25 Feuilles' etc.
        if (empty($input['type_compte'])) return null;
        if (strpos($input['type_compte'], 'Feuilles') === false) return null;

        if (preg_match_all('/(\d+)\s+Feuilles/', $input['type_compte'], $matches)) {
            foreach ($matches[1] as $value) {
                $chequier_info['requested'] = true;
                $chequier_info['types'][] = $value . ' Feuilles';
            }
        }
    } else {
        // Fallback: traiter comme JSON (ancien comportement)
        if (empty($input)) return null;
        $data = json_decode($input, true);
        if (!$data) return null;

        // recherche simple de la clé 'chequier' ou valeurs 25/50/100
        if (!empty($data['chequier']) && is_array($data['chequier'])) {
            foreach ($data['chequier'] as $v) {
                $val = is_string($v) ? trim($v) : $v;
                if ($val === '') continue;
                $chequier_info['requested'] = true;
                $chequier_info['types'][] = $val . ' Feuilles';
            }
        }
    }

    if (!empty($chequier_info['types'])) {
        $chequier_info['quantity'] = count($chequier_info['types']);
    }

    return $chequier_info;
}

// Fonction pour créer une notification
function create_notification($conn, $emp_id_recipient, $message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $insert_query = "INSERT INTO tblnotification (emp_id, message, type, created_at, is_read)
                     VALUES ('$emp_id_recipient', '$message', '$type', '$timestamp', 0)";
    return mysqli_query($conn, $insert_query);
}



// Traiter les demandes de chéquier et envoyer les notifications
$account_search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $account_search = trim($_GET['search']);
}

$result = get_chequier_requests($conn, $account_search);
$chequier_requests = array();

// Ensure status table exists
ensureChequierStatusTable($conn);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $chequier_info = extract_chequier_info($row);
        
        if ($chequier_info && $chequier_info['requested']) {
            $row['chequier_info'] = $chequier_info;
            // Fetch current status (latest) for this request
            $status_q = mysqli_query($conn, "SELECT status, changed_at, changed_by FROM chequier_status WHERE request_id = " . intval($row['id']) . " ORDER BY changed_at DESC LIMIT 1");
            if ($status_q && mysqli_num_rows($status_q) > 0) {
                $srow = mysqli_fetch_assoc($status_q);
                $row['current_status'] = $srow['status'];
                $row['status_changed_at'] = $srow['changed_at'];
                $row['status_changed_by'] = $srow['changed_by'];
            } else {
                $row['current_status'] = 'Nouvelle demande';
                $row['status_changed_at'] = null;
                $row['status_changed_by'] = null;
            }

            // Fetch full history
            $hist_q = mysqli_query($conn, "SELECT status, changed_at, changed_by FROM chequier_status WHERE request_id = " . intval($row['id']) . " ORDER BY changed_at DESC");
            $history = array();
            if ($hist_q && mysqli_num_rows($hist_q) > 0) {
                while ($hr = mysqli_fetch_assoc($hist_q)) {
                    $history[] = $hr;
                }
            }
            $row['status_history'] = $history;
            $chequier_requests[] = $row;
            
            // Vérifier si une notification a déjà été envoyée pour cette demande
            $check_notif = mysqli_query($conn, "SELECT id FROM notifications WHERE 
                                         message LIKE '%" . $row['id'] . "%' AND 
                                         emp_id = '" . $_SESSION['emp_id'] . "'");
            
            if ($check_notif && mysqli_num_rows($check_notif) == 0 && $row['emp_id']) {
                // Envoyer notification au CSO (CI)
                $msg_cso = "Demande de chéquier effectuée pour le compte " . $row['account_number'] . " - Client: " . $row['customer_name'] . " (Demande #" . $row['id'] . ")";
                create_notification($conn, $_SESSION['emp_id'], $msg_cso, 'chequier_created');
            }
        }
    }
}
?>

<body>
    <div class="pre-loader">
        <div class="pre-loader-box">
            <div class="loader-logo"><img src="../vendors/images/ecobank-bg3.png" alt=""></div>
            <div class='loader-progress' id="progress_div">
                <div class='bar' id='bar1'></div>
            </div>
            <div class='percent' id='percent1'>0%</div>
            <div class="loading-text">
                Loading...
            </div>
        </div>
    </div>

    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="title">
                            <h2 class="h3 mb-0">DEMANDES DE CHÉQUIERS</h2>
                        </div>
                        <nav aria-label="breadcrumb" role="navigation">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Demandes de Chéquiers</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <a href="export_chequier_xlsx.php" class="btn btn-sm btn-success" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); border: none; color: white;">
                            <i class="fa fa-file-excel-o"></i> Bon de Livraison (XLSX)
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <!-- Barre de recherche par numéro de compte -->
                    <div class="mb-20" style="display: flex; gap: 10px; align-items: center;">
                        <form method="GET" style="display: flex; gap: 10px;">
                            <input type="text" name="search" placeholder="Rechercher par numéro de compte..." value="<?php echo htmlspecialchars($account_search); ?>" class="form-control" style="width: 300px;">
                            <button type="submit" class="btn btn-sm btn-primary">Rechercher</button>
                            <?php if (!empty($account_search)): ?>
                                <a href="?" class="btn btn-sm btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($chequier_requests)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fa fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p style="color: #999; font-size: 16px;">Aucune demande de chéquier pour le moment</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-20">Total : <strong><?php echo count($chequier_requests); ?></strong> demande(s)</h4>
                        
                        <div class="table-responsive">
                            <table class="table table-hover nowrap">
                                <thead>
                                    <tr>
                                        <th class="table-plus">AGENCE</th>
                                        <th>NOM CLIENT</th>
                                        <th>COMPTE</th>
                                        <th>TYPE CHÉQUIER</th>
                                        <th>QUANTITÉ</th>
                                        <th>STATUT</th>
                                        <th>DATE DEMANDE</th>
                                        <th class="datatable-nosort">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chequier_requests as $req): ?>
                                        <tr ondblclick="showDetailsCI(<?php echo $req['id']; ?>)" style="cursor: pointer;" data-request-id="<?php echo $req['id']; ?>">
                                            <td class="table-plus">
                                                <span class="badge" style="background: #007db8; color: white; padding: 6px 12px; border-radius: 4px;">
                                                    <?php echo htmlspecialchars($req['branch_code']); ?>
                                                </span>
                                                <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                                    <?php echo htmlspecialchars($req['agency_name'] ?? ''); ?>
                                                </div>
                                            </td>

                                            <td>
                                                <small><div class="weight-600"><?php echo htmlspecialchars($req['customer_name']); ?></div></small> 
                                            </td>

                                            <td>
                                                <code style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px;">
                                                    <?php echo htmlspecialchars($req['account_number']); ?>
                                                </code>
                                            </td>
                                        
                                            <td>
                                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                    <?php foreach ($req['chequier_info']['types'] as $type): ?>
                                                        <span class="badge" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                                            <?php echo htmlspecialchars($type); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="font-size: 16px; color: #D32F2F;">
                                                    <?php echo $req['chequier_info']['quantity']; ?>
                                                </strong>
                                            </td>
                                            
                                            <td>
                                                <?php
                                                $status = $req['status'] ?? 'encours';
                                                $status_color = '#FFC107';
                                                $status_label = 'En cours';
                                                
                                                if ($status === 'reçu') {
                                                    $status_color = '#17A2B8';
                                                    $status_label = 'Reçu';
                                                } elseif ($status === 'livré') {
                                                    $status_color = '#28A745';
                                                    $status_label = 'Livré';
                                                }
                                                ?>
                                                <span class="badge" style="background: <?php echo $status_color; ?>; color: white; padding: 6px 12px; border-radius: 4px;">
                                                    <?php echo htmlspecialchars($status_label); ?>
                                                </span>
                                            </td>
                                            
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($req['created_at'])); ?></small>
                                            </td>
                                            
                                            <td>
                                                <div class="dropdown">
                                                    <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                        <i class="dw dw-more"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                
                                                        <div class="dropdown-divider"></div>
                                                        <h6 class="dropdown-header">Changer le statut</h6>
                                                        <a class="dropdown-item" href="#" onclick="updateChequierStatus(<?php echo $req['id']; ?>, 'encours')">En cours</a>
                                                        <a class="dropdown-item" href="#" onclick="updateChequierStatus(<?php echo $req['id']; ?>, 'reçu')">Reçu</a>
                                                        <a class="dropdown-item" href="#" onclick="updateChequierStatus(<?php echo $req['id']; ?>, 'livré')">Livré</a>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal Voir Détails -->
                                        <div class="modal fade" id="viewDetailsModal<?php echo $req['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white;">
                                                        <h5 class="modal-title" id="viewDetailsModalLabel">Détails Demande de Chéquier #<?php echo $req['id']; ?></h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Agence :</label>
                                                            <p><?php echo htmlspecialchars($req['branch_code'] . ' - ' . ($req['agency_name'] ?? 'N/A')); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Nom du Client :</label>
                                                            <p><?php echo htmlspecialchars($req['customer_name']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Numéro de Compte :</label>
                                                            <p><code><?php echo htmlspecialchars($req['account_number']); ?></code></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Email :</label>
                                                            <p><?php echo htmlspecialchars($req['email']); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Types de Chéquiers :</label>
                                                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                                <?php foreach ($req['chequier_info']['types'] as $type): ?>
                                                                    <span class="badge" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white; padding: 8px 12px; border-radius: 4px;">
                                                                        <?php echo htmlspecialchars($type); ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Quantité Totale :</label>
                                                            <p><strong style="font-size: 18px; color: #D32F2F;"><?php echo $req['chequier_info']['quantity']; ?> chéquier(s)</strong></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">Date de la Demande :</label>
                                                            <p><?php echo date('d/m/Y à H:i', strtotime($req['created_at'])); ?></p>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="font-weight-600">CSO (Responsable) :</label>
                                                            <p><?php echo htmlspecialchars(($req['FirstName'] ?? '') . ' ' . ($req['LastName'] ?? '') . ' (' . ($req['cso_email'] ?? '') . ')'); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                                    <!-- Hidden history JSON for JS modal -->
                                                    <script type="application/json" id="history-data-<?php echo $req['id']; ?>" style="display:none;">
                                                        <?php echo json_encode($req['status_history']); ?>
                                                    </script>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <?php include('includes/scriptJs.php')?>

    <script>
        function generateDeliveryForm(requestId) {
            // Ouvrir le bon de livraison pour l'impression
            window.open('generate_chequier_delivery.php?id=' + requestId, '_blank');
        }

        function markAsProcessed(requestId) {
            if (confirm('Marquer cette demande comme traitée ?')) {
                // Envoyer une requête pour marquer comme traité
                fetch('mark_chequier_processed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Demande marquée comme traitée');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue');
                });
            }
        }
    </script>
    <script>
        function updateChequierStatus(requestId, newStatus) {
            if (!confirm('Confirmer le changement de statut en : ' + newStatus + ' ?')) return;
            fetch('update_chequier_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: requestId, status: newStatus })
            })
            .then(r => r.json())
            .then(j => {
                if (j.status === 'success') {
                    alert('Statut mis à jour');
                    location.reload();
                } else {
                    alert('Erreur: ' + (j.message || 'Erreur inconnue'));
                }
            })
            .catch(err => { console.error(err); alert('Erreur de communication'); });
        }

        function showHistory(requestId) {
            // Chercher l'historique injecté côté PHP dans le DOM (data attribute technique)
            // Nous allons ouvrir un modal dynamique
            var historyData = null;
            // essayer de trouver la variable PHP via un element #history-data-<id>
            var el = document.getElementById('history-data-' + requestId);
            if (el) {
                try { historyData = JSON.parse(el.textContent); } catch(e){ historyData = null; }
            }

            var modalHtml = '<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">'
                + '<div class="modal-header" style="background:#007db8;color:#fff;"><h5 class="modal-title">Historique statut - demande #' + requestId + '</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>'
                + '<div class="modal-body">';

            if (historyData && historyData.length > 0) {
                modalHtml += '<ul class="list-group">';
                historyData.forEach(function(h){
                    modalHtml += '<li class="list-group-item">' + h.status + ' <br><small class="text-muted">Le ' + h.changed_at + (h.changed_by ? ' par ID#'+h.changed_by : '') + '</small></li>';
                });
                modalHtml += '</ul>';
            } else {
                modalHtml += '<p>Aucun historique disponible.</p>';
            }

            modalHtml += '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button></div></div></div></div>';

            // Append and show
            var wrapper = document.createElement('div');
            wrapper.innerHTML = modalHtml;
            document.body.appendChild(wrapper);
            $('#historyModal').modal('show');
            // cleanup on hide
            $('#historyModal').on('hidden.bs.modal', function(){ wrapper.remove(); });
        }

        function showDetailsCI(requestId) {
            fetch('get_chequier_details.php?request_id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const req = data.request;
                        let html = `
                            <div class="mb-3">
                                <label class="font-weight-600">Agence :</label>
                                <p>${escapeHtml(req.branch_code)} - ${escapeHtml(req.agency_name || 'N/A')}</p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Nom du Client :</label>
                                <p>${escapeHtml(req.customer_name)}</p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Numéro de Compte :</label>
                                <p><code>${escapeHtml(req.account_number)}</code></p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Email :</label>
                                <p>${escapeHtml(req.email || 'N/A')}</p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Type de Chéquier :</label>
                                <p>${escapeHtml(req.type_compte)}</p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Adresse :</label>
                                <p>${escapeHtml(req.address || 'N/A')}</p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Statut :</label>
                                <p>
                                    <span style="display: inline-block; padding: 6px 12px; background-color: ${getStatusColorCI(req.status).bg}; color: ${getStatusColorCI(req.status).color}; border-radius: 4px; font-weight: 500;">
                                        ${getStatusLabelCI(req.status)}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Date de Demande :</label>
                                <p>${new Date(req.created_at).toLocaleString('fr-FR')}</p>
                            </div>
                        `;
                        
                        let modalHtml = '<div class="modal fade" id="detailsModalCI" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header" style="background: linear-gradient(135deg, #D32F2F 0%, #F57C00 100%); color: white;"><h5 class="modal-title">Détails de la Demande de Chéquier</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">' + html + '</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button></div></div></div></div>';
                        
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = modalHtml;
                        document.body.appendChild(wrapper);
                        $('#detailsModalCI').modal('show');
                        $('#detailsModalCI').on('hidden.bs.modal', function(){ wrapper.remove(); });
                    } else {
                        alert('Erreur : ' + (data.error || 'Impossible de charger les détails'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur réseau');
                });
        }

        function getStatusColorCI(status) {
            const colors = {
                'encours': { bg: '#FFC107', color: 'white' },
                'reçu': { bg: '#17A2B8', color: 'white' },
                'livré': { bg: '#28A745', color: 'white' }
            };
            return colors[status] || { bg: '#6C757D', color: 'white' };
        }

        function getStatusLabelCI(status) {
            const labels = {
                'encours': 'En cours',
                'reçu': 'Reçu',
                'livré': 'Livré'
            };
            return labels[status] || status;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
