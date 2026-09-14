<?php
ob_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/audit_helpers.php';

header('Content-Type: application/json; charset=utf-8');

function respond_json(array $payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur serveur lors de la mise à jour du statut'], JSON_UNESCAPED_UNICODE);
    }
});

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['status'])) {
    respond_json(['status' => 'error', 'message' => 'Paramètres manquants'], 400);
}

$request_ids = [];
if (isset($data['request_ids']) && is_array($data['request_ids'])) {
    $request_ids = $data['request_ids'];
} elseif (isset($data['request_id'])) {
    $request_ids = [$data['request_id']];
}

$request_ids = array_values(array_unique(array_filter(array_map('intval', $request_ids), function ($id) {
    return $id > 0;
})));

if (empty($request_ids)) {
    respond_json(['status' => 'error', 'message' => 'Sélectionnez au moins une demande valide'], 400);
}

function normalize_chequier_status($status) {
    $value = strtolower(trim($status));
    $value = str_replace([' ', '-'], '', $value);

    if (in_array($value, ['recu', 'reçu', 'rece', 'recue'], true)) return 'reçu';
    if (in_array($value, ['livre', 'livré'], true)) return 'livré';
    if (in_array($value, ['donne', 'donné'], true)) return 'donné';
    if ($value === 'prestataire') return 'prestataire';
    if (in_array($value, ['encours', 'encourse'], true)) return 'encours';
    return $value;
}

$status = normalize_chequier_status(substr(trim($data['status']), 0, 100));
if (!in_array($status, ['encours', 'prestataire', 'reçu', 'livré', 'donné'], true)) {
    respond_json(['status' => 'error', 'message' => 'Statut non autorisé'], 400);
}

$changed_by = isset($_SESSION['emp_id']) ? intval($_SESSION['emp_id']) : null;
$create_table = "CREATE TABLE IF NOT EXISTS chequier_status (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!mysqli_query($conn, $create_table)) {
    respond_json(['status' => 'error', 'message' => mysqli_error($conn)], 500);
}

$insert_stmt = mysqli_prepare($conn, "INSERT INTO chequier_status (request_id, status, changed_by) VALUES (?, ?, ?)");
$update_stmt = mysqli_prepare($conn, "UPDATE tblcompte SET access = ? WHERE id = ?");
if (!$insert_stmt || !$update_stmt) {
    respond_json(['status' => 'error', 'message' => mysqli_error($conn)], 500);
}

mysqli_begin_transaction($conn);
$updated_count = 0;
$failed_ids = [];

foreach ($request_ids as $request_id) {
    $current_stmt = mysqli_prepare($conn, "SELECT COALESCE((SELECT status FROM chequier_status WHERE request_id = ? ORDER BY changed_at DESC, id DESC LIMIT 1), access, 'encours') AS current_status FROM tblcompte WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($current_stmt, 'ii', $request_id, $request_id);
    mysqli_stmt_execute($current_stmt);
    $current_result = mysqli_stmt_get_result($current_stmt);
    $current_row = $current_result ? mysqli_fetch_assoc($current_result) : null;
    mysqli_stmt_close($current_stmt);

    if ($current_row) {
        $current_status = normalize_chequier_status($current_row['current_status']);
        if ($current_status === 'donné') {
            $failed_ids[] = $request_id;
            continue;
        }
        if ($current_status === 'prestataire' && $status !== 'livré') {
            $failed_ids[] = $request_id;
            continue;
        }
    }

    $exists_stmt = mysqli_prepare($conn, "SELECT id FROM tblcompte WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($exists_stmt, 'i', $request_id);
    mysqli_stmt_execute($exists_stmt);
    mysqli_stmt_store_result($exists_stmt);
    $exists = mysqli_stmt_num_rows($exists_stmt) > 0;
    mysqli_stmt_close($exists_stmt);

    if (!$exists) {
        $failed_ids[] = $request_id;
        continue;
    }

    mysqli_stmt_bind_param($insert_stmt, 'isi', $request_id, $status, $changed_by);
    mysqli_stmt_bind_param($update_stmt, 'si', $status, $request_id);
    if (!mysqli_stmt_execute($insert_stmt) || !mysqli_stmt_execute($update_stmt)) {
        $failed_ids[] = $request_id;
        break;
    }

    try {
        log_admin_action('update_chequier_status', $request_id, [
            'new_status' => $status,
            'table' => 'tblcompte',
            'bulk' => count($request_ids) > 1
        ]);
    } catch (Throwable $audit_error) {
        error_log('[update_chequier_status] Audit non enregistré: ' . $audit_error->getMessage());
    }
    $updated_count++;
}

if (!empty($failed_ids)) {
    mysqli_rollback($conn);
    respond_json([
        'status' => 'error',
        'success' => false,
        'message' => 'Aucune modification appliquée : demande(s) introuvable(s) ou erreur SQL.',
        'failed_ids' => $failed_ids
    ], 409);
} else {
    mysqli_commit($conn);
    respond_json([
        'status' => 'success',
        'success' => true,
        'updated_count' => $updated_count,
        'message' => $updated_count . ' demande(s) mise(s) à jour.'
    ]);
}

mysqli_stmt_close($insert_stmt);
mysqli_stmt_close($update_stmt);
exit;
