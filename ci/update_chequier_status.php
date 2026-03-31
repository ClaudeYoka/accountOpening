<?php
session_start();
include('../includes/config.php');
include('../includes/audit_helpers.php');

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['request_id']) || empty($data['status'])) {
    echo json_encode(['status'=>'error','message'=>'Paramètres manquants']);
    exit;
}

$request_id = intval($data['request_id']);
$status = substr(trim($data['status']), 0, 100);

if ($request_id <= 0) {
    audit_log_error($conn, 'INVALID_REQUEST_ID', 'Identifiant demande invalide', ['request_id' => $request_id]);
    echo json_encode(['status' => 'error', 'message' => 'Identifiant demande invalide']);
    exit;
}

// Verify request exists before inserting status
$existing_check = mysqli_prepare($conn, "SELECT id FROM tblcompte WHERE id = ? LIMIT 1");
if (!$existing_check) {
    audit_log_error($conn, 'DB_ERROR', 'Erreur vérification existe : ' . mysqli_error($conn), ['request_id' => $request_id]);
    echo json_encode(['status' => 'error', 'message' => 'Erreur de vérification existe : ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($existing_check, 'i', $request_id);
mysqli_stmt_execute($existing_check);
mysqli_stmt_store_result($existing_check);
if (mysqli_stmt_num_rows($existing_check) === 0) {
    audit_log_error($conn, 'REQUEST_NOT_FOUND', 'Demande introuvable', ['request_id' => $request_id]);
    mysqli_stmt_close($existing_check);
    echo json_encode(['status' => 'error', 'message' => 'Demande introuvable']);
    exit;
}
mysqli_stmt_close($existing_check);

// Get current status before change
$current_status_stmt = mysqli_prepare($conn, "SELECT COALESCE(cs.status, tc.access, 'encours') as current_status FROM tblcompte tc LEFT JOIN (SELECT request_id, status FROM chequier_status cs1 WHERE cs1.changed_at = (SELECT MAX(cs2.changed_at) FROM chequier_status cs2 WHERE cs2.request_id = cs1.request_id)) cs ON tc.id = cs.request_id WHERE tc.id = ?");
if ($current_status_stmt) {
    mysqli_stmt_bind_param($current_status_stmt, 'i', $request_id);
    mysqli_stmt_execute($current_status_stmt);
    $current_result = mysqli_stmt_get_result($current_status_stmt);
    $old_status = 'encours'; // default
    if ($current_result && mysqli_num_rows($current_result) > 0) {
        $current_row = mysqli_fetch_assoc($current_result);
        $old_status = $current_row['current_status'];
    }
    mysqli_stmt_close($current_status_stmt);
}

function normalize_chequier_status($status) {
    $lower = strtolower(trim($status));
    $lower = str_replace([' ', '-'], '', $lower);

    if (in_array($lower, ['recu', 'reçu', 'rece', 'recue'])) {
        return 'reçu';
    }
    if (in_array($lower, ['livre', 'livré'])) {
        return 'livré';
    }
    if (in_array($lower, ['prestataire'])) {
        return 'prestataire';
    }
    if (in_array($lower, ['encours', 'en cours', 'encourse', 'en-cours'])) {
        return 'encours';
    }
    return $lower;
}

$status = normalize_chequier_status($status);
$changed_by = isset($_SESSION['emp_id']) ? intval($_SESSION['emp_id']) : null;

// Ensure table exists (safe: CREATE IF NOT EXISTS)
$sql_create = "CREATE TABLE IF NOT EXISTS chequier_status (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $sql_create);

$stmt = mysqli_prepare($conn, "INSERT INTO chequier_status (request_id, status, changed_by) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status'=>'error','message'=>mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($stmt, 'isi', $request_id, $status, $changed_by);
$ok = mysqli_stmt_execute($stmt);
if ($ok) {
    // Mettre à jour le champ access dans tblcompte
    $stmt_update = mysqli_prepare($conn, "UPDATE tblcompte SET access = ? WHERE id = ?");
    if (!$stmt_update) {
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'message' => 'Erreur mise à jour access : ' . mysqli_error($conn)]);
        exit;
    }

    mysqli_stmt_bind_param($stmt_update, 'si', $status, $request_id);
    if (!mysqli_stmt_execute($stmt_update)) {
        mysqli_stmt_close($stmt_update);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'error', 'message' => 'Impossible de mettre à jour access : ' . mysqli_stmt_error($stmt_update)]);
        exit;
    }
    mysqli_stmt_close($stmt_update);

    // Log successful status change
    log_admin_action('update_chequier_status', $request_id, [
        'old_status' => $old_status,
        'new_status' => $status,
        'table' => 'tblcompte'
    ]);

    echo json_encode(['status'=>'success','message'=>'Statut mis à jour avec succès']);
} else {
    audit_log_error($conn, 'STATUS_UPDATE_FAILED', 'Impossible de mettre à jour access : ' . mysqli_stmt_error($stmt_update), ['request_id' => $request_id, 'new_status' => $status]);
    echo json_encode(['status'=>'error','message'=>mysqli_stmt_error($stmt)]);
}
mysqli_stmt_close($stmt);
exit;

?>
