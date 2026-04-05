<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>

<?php
// Récupérer TOUTES les demandes de chéquier (historique complet) à partir de tblcompte
function get_chequier_requests_history($conn, $account_search = '', $filter_month = '', $filter_year = '') {
    $where = "tc.type_compte IS NOT NULL AND tc.type_compte != ''";

    // Si recherche par numéro de compte
    if (!empty($account_search)) {
        $account_search = mysqli_real_escape_string($conn, $account_search);
        $where .= " AND tc.account_number = '$account_search'";
    }

    // Filtre par mois et année
    if (!empty($filter_month)) {
        $where .= " AND MONTH(tc.date_enregistrement) = '" . intval($filter_month) . "'";
    }

    if (!empty($filter_year)) {
        $where .= " AND YEAR(tc.date_enregistrement) = '" . intval($filter_year) . "'";
    }

    $query = "SELECT
                tc.id,
                tc.firstname as client_name,
                tc.branch_code,
                tc.account_number as account_number,
                tc.mobile1 as phone_number,
                tc.email,
                tc.type_compte,
                tc.adr_rue as address,
                tc.nip as rib_key,
                tc.etabliss as quantity,
                tc.access as status,
                tc.date_enregistrement as created_at,
                tc.emp_id,
                tc.titre as has_card,
                tc.objectif as fees,
                tc.devise_pref as enrolled,
                tc.ident_etud as serial_number,
                tb.DepartmentName as agency_name,
                te.FirstName,
                te.LastName,
                te.EmailId as cso_email
            FROM tblcompte tc
            LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
            LEFT JOIN tblemployees te ON tc.emp_id = te.emp_id
            WHERE $where
            ORDER BY tc.date_enregistrement DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("SQL Error in get_chequier_requests_history: " . mysqli_error($conn));
        return array();
    }
    
    return $result;
}

function extract_chequier_info($row) {
    // Extrait les infos de chéquier depuis les colonnes directes
    if (empty($row['type_compte'])) return null;

    $chequier_info = array('requested' => false, 'types' => array(), 'quantity' => 0);

    // type_compte contient "25, 50, 100"
    if (!empty($row['type_compte'])) {
        $chequier_info['requested'] = true;
        // Parser les feuilles
        if (preg_match_all('/(\d+)\s*(?:Feuilles)?/', $row['type_compte'], $matches)) {
            foreach ($matches[1] as $value) {
                $chequier_info['types'][] = $value . ' Feuilles';
            }
        }
    }

    if (!empty($chequier_info['types'])) {
        // Utiliser la quantité depuis la base de données (colonne etabliss)
        $chequier_info['quantity'] = isset($row['quantity']) ? (int)$row['quantity'] : count($chequier_info['types']);
    }

    return $chequier_info;
}

// Traiter les demandes de chéquier
$account_search = '';
$filter_month = '';
$filter_year = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $account_search = trim($_GET['search']);
}

if (isset($_GET['filter_month']) && !empty($_GET['filter_month'])) {
    $filter_month = $_GET['filter_month'];
}

if (isset($_GET['filter_year']) && !empty($_GET['filter_year'])) {
    $filter_year = $_GET['filter_year'];
}

$result = get_chequier_requests_history($conn, $account_search, $filter_month, $filter_year);
$chequier_requests = array();

// Ensure status table exists
$sql_ensure = "CREATE TABLE IF NOT EXISTS chequier_status (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $sql_ensure);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $chequier_info = extract_chequier_info($row);
        if ($chequier_info && $chequier_info['requested']) {
            $row['chequier_info'] = $chequier_info;

            // Fetch last status change date and current status
            $status_q = mysqli_query($conn, "SELECT status, changed_at FROM chequier_status WHERE request_id = " . intval($row['id']) . " ORDER BY changed_at DESC LIMIT 1");
            $current_status = $row['status'] ?? 'encours';
            
            if ($status_q && mysqli_num_rows($status_q) > 0) {
                $srow = mysqli_fetch_assoc($status_q);
                $row['status_changed_at'] = $srow['changed_at'];
                $current_status = $srow['status'];
            } else {
                $row['status_changed_at'] = $row['created_at'];
            }
            
            $row['current_status'] = $current_status;
            $chequier_requests[] = $row;
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
                            <h2 class="h3 mb-0">HISTORIQUE DES DEMANDES DE CHÉQUIERS</h2>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12 text-right">
                        <a href="demande_chequier" class="btn btn-sm btn-secondary">
                            <i class="dw dw-arrow-left"></i> Retour aux demandes actives
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-box mb-30">
                <div class="pd-20">
                    <!-- Barre de recherche et filtres -->
                    <div class="mb-20" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                        <form method="GET" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Rechercher par N°</label>
                                <input type="text" name="search" placeholder="N° de compte" value="<?php echo htmlspecialchars($account_search); ?>" class="form-control" style="width: 150px;">
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Mois :</label>
                                <select id="filter_month" name="filter_month" class="form-control" style="width: 100px;">
                                    <option value="">Tous</option>
                                    <?php
                                        for ($m = 1; $m <= 12; $m++) {
                                            $selected = ($m == $filter_month) ? 'selected' : '';
                                            $month_name = date('F', mktime(0, 0, 0, $m, 1));
                                            echo '<option value="' . str_pad($m, 2, '0', STR_PAD_LEFT) . '" ' . $selected . '>' . $month_name . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <div>
                                <label style="font-weight: 600; font-size: 12px; color: #666; display: block; margin-bottom: 4px;">Année :</label>
                                <select id="filter_year" name="filter_year" class="form-control" style="width: 100px;">
                                    <option value="">Toutes</option>
                                    <?php
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                            $selected = ($y == $filter_year) ? 'selected' : '';
                                            echo '<option value="' . $y . '" ' . $selected . '>' . $y . '</option>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary">Appliquer</button>

                            <?php if (!empty($account_search) || !empty($filter_month) || !empty($filter_year)): ?>
                                <a href="historique_chequier" class="btn btn-sm btn-secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <?php if (empty($chequier_requests)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <i class="fa fa-history" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                            <p style="color: #999; font-size: 16px;">Aucune demande dans l'historique</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-20">Total : <strong><?php echo count($chequier_requests); ?></strong> demande(s) dans l'historique</h4>
                        <div class="table-responsive">
                            <table class="data-table table hover multiple-select-row nowrap">
                                <thead>
                                    <tr>
                                        <th>AGENCE</th>
                                        <th>NOM CLIENT</th>
                                        <th>COMPTE</th>
                                        <th>CHÉQUIER</th>
                                        <th>STATUT</th>
                                        <th>DATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($chequier_requests as $req):
                                        $status = strtolower($req['current_status'] ?? $req['status'] ?? 'encours');
                                        $statusColors = [
                                            'encours' => ['bg' => '#ffe4cd', 'color' => '#eb6c05', 'label' => 'En cours'],
                                            'reçu' => ['bg' => '#d1ecf1', 'color' => '#0c5460', 'label' => 'Reçu'],
                                            'donné' => ['bg' => '#d4edda', 'color' => '#155724', 'label' => 'Donné'],
                                            'livré' => ['bg' => '#d4edda', 'color' => '#155724', 'label' => 'Livré']
                                        ];
                                        $badgeStyle = $statusColors[$status] ?? ['bg' => '#e2e3e5', 'color' => '#383d41', 'label' => ucfirst($status)];
                                    ?>
                                        <tr ondblclick="showDetailsCSO(<?php echo $req['id']; ?>)" style="cursor: pointer;" data-request-id="<?php echo $req['id']; ?>">
                                            <td><?php echo htmlspecialchars($req['branch_code'] ?? ''); ?><br><small><?php echo htmlspecialchars($req['agency_name'] ?? ''); ?></small></td>
                                            <td><small><?php echo htmlspecialchars($req['client_name'] ?? ''); ?></small></td>
                                            <td><code><?php echo htmlspecialchars($req['account_number'] ?? ''); ?></code></td>
                                            <td><?php echo htmlspecialchars(implode(', ', $req['chequier_info']['types'])); ?></td>
                                            <td>
                                                <span style="display: inline-block; padding: 6px 12px; background-color: <?php echo $badgeStyle['bg']; ?>; color: <?php echo $badgeStyle['color']; ?>; border-radius: 4px; font-weight: 500; font-size: 12px;">
                                                    <?php echo htmlspecialchars($badgeStyle['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td><small><?php echo date('d/m/Y', strtotime($req['status_changed_at'] ?: $req['created_at'])); ?></small></td>
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
                    if (data.success || data.status === 'success') {
                        alert('Statut mis à jour avec succès');
                        location.reload();
                    } else {
                        alert('Erreur : ' + (data.error || data.message || 'Statut non mis à jour'));
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
                                <label class="font-weight-600">Agence : ${escapeHtml(req.branch_code)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Client : ${escapeHtml(req.customer_name || req.client_name)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Numéro de compte : ${escapeHtml(req.account_number)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Type de chéquier : ${escapeHtml(req.chequier_types || req.type_compte)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Quantité : ${escapeHtml(req.quantity || req.etabliss)}</label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Statut : <span class="badge badge-info">${escapeHtml(req.status || 'encours')}</span></label>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-600">Date de demande : ${new Date(req.created_at).toLocaleDateString('fr-FR')}</label>
                            </div>
                        `;
                        document.getElementById('detailsContent').innerHTML = html;
                    } else {
                        document.getElementById('detailsContent').innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails</div>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('detailsContent').innerHTML = '<div class="alert alert-danger">Erreur réseau</div>';
                });
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return (text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    </script>
</body>
</html>