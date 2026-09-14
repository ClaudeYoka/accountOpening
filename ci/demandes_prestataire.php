<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS chequier_status (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, request_id INT NOT NULL, status VARCHAR(100) NOT NULL, changed_by INT DEFAULT NULL, changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(request_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$query = "SELECT tc.id, tc.account_number, tc.firstname AS customer_name, tc.branch_code, tc.type_compte, tc.etabliss AS quantity, tc.date_enregistrement AS created_at, COALESCE(tb.DepartmentName, tc.branch_code) AS agency_name, COALESCE(cs.status, tc.access, 'encours') AS current_status
          FROM tblcompte tc
          LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
          LEFT JOIN (SELECT request_id, status FROM chequier_status s1 WHERE s1.changed_at = (SELECT MAX(s2.changed_at) FROM chequier_status s2 WHERE s2.request_id = s1.request_id)) cs ON tc.id = cs.request_id
          WHERE LOWER(REPLACE(COALESCE(cs.status, tc.access, 'encours'), ' ', '')) COLLATE utf8mb4_general_ci IN ('prestataire', 'reçu')
          AND tc.type_compte IS NOT NULL AND tc.type_compte <> ''
          ORDER BY tc.date_enregistrement DESC";
$result = mysqli_query($conn, $query);
$requests = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $requests[] = $row;
?>
<body>
<?php include('includes/navbar.php'); include('includes/right_sidebar.php'); include('includes/left_sidebar.php'); ?>
<div class="mobile-menu-overlay"></div><div class="main-container"><div class="pd-ltr-20">
<div class="page-header"><div class="title"><h2 class="h3 mb-0">Demandes chez le prestataire</h2><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index">Dashboard</a></li><li class="breadcrumb-item active">Chez le prestataire</li></ol></nav></div></div>
<div class="card-box mb-30"><div class="pd-20">
<div id="status-feedback" class="alert" style="display:none"></div>
<p><strong><?php echo count($requests); ?></strong> demande(s) chez le prestataire.</p>
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:15px;"><strong>Actions groupées</strong><button type="button" class="btn btn-primary" onclick="updateSelectedPrestataireStatuses()">Marquer comme livré</button><span id="selectedPrestataireCount" class="text-muted">0 sélectionnée(s)</span></div>
<div class="table-responsive"><table class="data-table table hover nowrap"><thead><tr><th><input type="checkbox" id="selectAllPrestataire" title="Sélectionner toutes les demandes"></th><th>#</th><th>Compte</th><th>Client</th><th>Agence</th><th>Type</th><th>Quantité</th><th>Statut</th></tr></thead><tbody>
<?php foreach ($requests as $index => $request): ?>
<tr><td onclick="event.stopPropagation();"><input type="checkbox" class="prestataire-select" value="<?php echo (int)$request['id']; ?>" aria-label="Sélectionner la demande <?php echo (int)$request['id']; ?>"></td><td><?php echo $index + 1; ?></td><td><?php echo htmlspecialchars($request['account_number']); ?></td><td><?php echo htmlspecialchars($request['customer_name']); ?></td><td><?php echo htmlspecialchars($request['agency_name']); ?></td><td><?php echo htmlspecialchars($request['type_compte']); ?></td><td><?php echo htmlspecialchars($request['quantity']); ?></td><td><span class="badge" style="background:<?php echo $request['current_status'] === 'reçu' ? '#ec3f93' : '#17a2b8'; ?>;color:#fff;"><?php echo $request['current_status'] === 'reçu' ? 'Reçu' : 'Prestataire'; ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>
<?php include('includes/footer.php'); ?></div></div><?php include('includes/scriptJs.php'); ?>
<script>
function refreshSelectedPrestataireCount() { document.getElementById('selectedPrestataireCount').textContent = document.querySelectorAll('.prestataire-select:checked').length + ' sélectionnée(s)'; }
document.getElementById('selectAllPrestataire').addEventListener('change', function () { document.querySelectorAll('.prestataire-select').forEach(function (checkbox) { checkbox.checked = this.checked; }, this); refreshSelectedPrestataireCount(); });
document.querySelectorAll('.prestataire-select').forEach(function (checkbox) { checkbox.addEventListener('change', refreshSelectedPrestataireCount); });
function updateSelectedPrestataireStatuses() { var selected = Array.from(document.querySelectorAll('.prestataire-select:checked')).map(function (checkbox) { return Number(checkbox.value); }); if (!selected.length) { showFeedback('Sélectionnez au moins une demande.', true); return; } if (!confirm('Marquer ' + selected.length + ' demande(s) comme livrée(s) ?')) return; fetch('update_chequier_status.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({request_ids:selected,status:'livré'})}).then(function(r){return r.json();}).then(function(data){ if(data.status==='success'){ location.reload(); } else { showFeedback(data.message || 'Erreur de mise à jour', true); } }).catch(function(){ showFeedback('Erreur de communication', true); }); }
function showFeedback(message, error) { var node=document.getElementById('status-feedback'); node.textContent=message; node.className='alert '+(error?'alert-danger':'alert-success'); node.style.display='block'; }
</script></body></html>
