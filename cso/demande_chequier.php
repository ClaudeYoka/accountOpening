<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
// Récupérer les demandes de chéquier à partir de tblcompte
function get_chequier_requests($conn, $account_search = '') {
    $where = "tc.type_compte LIKE '%Feuilles%'";
    
    // Si recherche par numéro de compte
    if (!empty($account_search)) {
        $account_search = mysqli_real_escape_string($conn, $account_search);
        $where .= " AND tc.mobile1 = '$account_search'";
    }
    
    $query = "SELECT
                tc.id,
                tc.firstname as client_name,
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

function extract_chequier_info($row) {
    // Extrait les infos de chéquier depuis les colonnes directes
    if (empty($row['type_compte'])) return null;
    
    $chequier_info = array('requested' => false, 'types' => array(), 'quantity' => 0);
    
    // type_compte contient "25, 50, 100 Feuilles" ou "25 Feuilles, 50 Feuilles, 100 Feuilles"
    if (strpos($row['type_compte'], 'Feuilles') !== false) {
        $chequier_info['requested'] = true;
        // Parser les feuilles
        if (preg_match_all('/(\d+)\s+Feuilles/', $row['type_compte'], $matches)) {
            foreach ($matches[1] as $value) {
                $chequier_info['types'][] = $value . ' Feuilles';
            }
        }
    }
    
    if (!empty($chequier_info['types'])) {
        $chequier_info['quantity'] = count($chequier_info['types']);
    }
    
    return $chequier_info;
}

// Créer une notification dans la table tblnotification
function create_notification($conn, $emp_id_recipient, $message, $type = 'info', $submission_id = null) {
    $timestamp = date('Y-m-d H:i:s');
    $submission_val = $submission_id ? intval($submission_id) : 'NULL';
    $insert_query = "INSERT INTO tblnotification (emp_id, message, type, submission_id, created_at, is_read) 
                        VALUES ('".mysqli_real_escape_string($conn,$emp_id_recipient)."', '".mysqli_real_escape_string($conn,$message)."', '".mysqli_real_escape_string($conn,$type)."', $submission_val, '$timestamp', 0)";
    return mysqli_query($conn, $insert_query);
}

// Traiter les demandes de chéquier
$account_search = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $account_search = trim($_GET['search']);
}

$result = get_chequier_requests($conn, $account_search);
$chequier_requests = array();

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $chequier_info = extract_chequier_info($row);
        if ($chequier_info && $chequier_info['requested']) {
            $row['chequier_info'] = $chequier_info;
            $chequier_requests[] = $row;

            // Envoyer une notification au CSO responsable si nécessaire
            $check_notif = mysqli_query($conn, "SELECT id FROM tblnotification WHERE message LIKE '%" . $row['id'] . "%' AND emp_id = '" . mysqli_real_escape_string($conn, $_SESSION['emp_id']) . "'");
            if ($check_notif && mysqli_num_rows($check_notif) == 0 && $row['emp_id']) {
                $msg_cso = "Demande de chéquier effectuée pour le compte " . $row['account_number'] . " - Client: " . $row['customer_name'] . " (Demande #" . $row['id'] . ")";
                create_notification($conn, $_SESSION['emp_id'], $msg_cso, 'chequier_created', $row['id']);
            }
        }
    }
}
?>

<body>
    <?php include('includes/navbar.php')?>
    <?php include('includes/right_sidebar.php')?>
    <?php include('includes/left_sidebar.php')?>

    <div class="main-container">
        <div class="pd-ltr-20">
            <div class="page-header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="title">
                            <h2 class="h3 mb-0">DEMANDES DE CHÉQUIERS</h2>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <a href="demande_chequier_directe" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #05b7e4 0%, #00455a 100%); border: none;">
                            <i class="dw dw-plus"></i> Nouvelle Demande
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <!-- Barre de recherche par numéro de compte -->
                    <!-- <div class="mb-20" style="display: flex; gap: 10px; align-items: center;">
                        <form method="GET" style="display: flex; gap: 10px;">
                            <input type="text" name="search" placeholder="Rechercher par numéro de compte..." value="<?php echo htmlspecialchars($account_search); ?>" class="form-control" style="width: 300px;">
                            <button type="submit" class="btn btn-sm btn-primary">Rechercher</button>
                            <?php if (!empty($account_search)): ?>
                                <a href="?" class="btn btn-sm btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div> -->

                    <?php if (empty($chequier_requests)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fa fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p style="color: #999; font-size: 16px;">Aucune demande de chéquier pour le moment</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-20">Total : <strong><?php echo count($chequier_requests); ?></strong> demande(s)</h4>
                        <div class="table-responsive">
                            <table class="data-table table hover multiple-select-row nowrap">
                                <thead>
                                    <tr>
                                        <th>AGENCE</th>
                                        <th>NOM CLIENT</th>
                                        <th>COMPTE</th>
                                        <th>CHÉQUIER</th>
                                        <!-- <th>QUANTITÉ</th> -->
                                        <th>STATUT</th>
                                        <th>DATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chequier_requests as $req): 
                                        $status = $req['status'] ?? 'encours';
                                        $statusColors = [
                                            'encours' => ['bg' => '#ffe4cd', 'color' => '#eb6c05', 'label' => 'En cours'],
                                            'reçu' => ['bg' => '#d1ecf1', 'color' => '#0c5460', 'label' => 'Reçu'],
                                            'donné' => ['bg' => '#d4edda', 'color' => '#155724', 'label' => 'Donné'],
                                            'livré' => ['bg' => '#d4edda', 'color' => '#155724', 'label' => 'Livré']

                                            
                                        ];
                                        $badgeStyle = $statusColors[$status] ?? ['bg' => '#e2e3e5', 'color' => '#383d41', 'label' => $status];
                                    ?>
                                        <tr ondblclick="showDetailsCSO(<?php echo $req['id']; ?>)" style="cursor: pointer;" data-request-id="<?php echo $req['id']; ?>">
                                            <td><?php echo htmlspecialchars($req['branch_code'] ?? ''); ?><br><small><?php echo htmlspecialchars($req['agency_name'] ?? ''); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($req['client_name'] ?? ''); ?></small></td>
                                            <td><code><?php echo htmlspecialchars($req['account_number'] ?? ''); ?></code></td>
                                            <td><?php echo htmlspecialchars(implode(', ', $req['chequier_info']['types'])); ?></td>
                                            <!-- <td><strong><?php echo $req['chequier_info']['quantity']; ?></strong></td> -->
                                            <td>
                                                <span style="display: inline-block; padding: 6px 12px; background-color: <?php echo $badgeStyle['bg']; ?>; color: <?php echo $badgeStyle['color']; ?>; border-radius: 4px; font-weight: 500; font-size: 12px;">
                                                    <?php echo $badgeStyle['label']; ?>
                                                </span>
                                                <div class="dropdown" style="display: inline-block; margin-left: 5px;">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" title="Modifier le statut">
                                                        ⋮
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="#" onclick="updateStatusCSO(<?php echo $req['id']; ?>, 'Donné'); return false;">Donné</a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><small><?php echo date('d/m/Y', strtotime($req['created_at'])); ?></small></td>
                                            <!-- <td><?php echo htmlspecialchars(($req['FirstName'] ?? '') . ' ' . ($req['LastName'] ?? '')); ?></td> -->
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Détails Demande CSO -->
    <div id="detailsModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #05b7e4 0%, #00455a 100%); color: white;">
                    <h5 class="modal-title">Détails de la Demande de Chéquier</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <div style="text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #05b7e4;"></i> Chargement...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <?php include('includes/scriptJs.php')?>
    <script>
        function updateStatusCSO(requestId, newStatus) {
            if (confirm('Êtes-vous sûr de vouloir changer le statut en : ' + newStatus + ' ?')) {
                fetch('../ci/update_chequier_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId, status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Statut mis à jour avec succès');
                        location.reload();
                    } else {
                        alert('Erreur : ' + (data.error || 'Statut non mis à jour'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur réseau');
                });
            }
        }

        function showDetailsCSO(requestId) {
            document.getElementById('detailsContent').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fa fa-spinner fa-spin" style="font-size: 24px; color: #05b7e4;"></i> Chargement...</div>';
            
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
                                <p>${escapeHtml(req.client_name)}</p>
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
                                    <span style="display: inline-block; padding: 6px 12px; background-color: ${getStatusColor(req.status).bg}; color: ${getStatusColor(req.status).color}; border-radius: 4px; font-weight: 500;">
                                        ${getStatusLabel(req.status)}
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Date de Demande :</label>
                                <p>${new Date(req.created_at).toLocaleString('fr-FR')}</p>
                            </div>
                        `;
                        document.getElementById('detailsContent').innerHTML = html;
                    } else {
                        document.getElementById('detailsContent').innerHTML = '<p style="color: red;">Erreur : ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('detailsContent').innerHTML = '<p style="color: red;">Erreur réseau</p>';
                });
            
            $('#detailsModal').modal('show');
        }

        function getStatusColor(status) {
            const colors = {
                'encours': { bg: '#ffe4cd', color: '#eb6c05' },
                'reçu': { bg: '#d1ecf1', color: '#0c5460' },
                'livré': { bg: '#d4edda', color: '#155724' },
                'donné': { bg: '#d4edda', color: '#155724' }
            };
            return colors[status] || { bg: '#e2e3e5', color: '#383d41' };
        }

        function getStatusLabel(status) {
            const labels = {
                'encours': 'En cours',
                'reçu': 'Reçu',
                'livré': 'Livré',
                'donné': 'Donné'
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
