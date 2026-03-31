<?php
include('../includes/session.php');
include('../includes/config.php');
include('../includes/audit_helpers.php');

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);
$request_id = isset($data['request_id']) ? intval($data['request_id']) : 0;

if (!$request_id) {
    echo json_encode(['status' => 'error', 'message' => 'ID de demande invalide']);
    exit;
}

// Vérifier que l'utilisateur est authentifié
if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
    exit;
}

// Créer une table de suivi des demandes de chéquiers si elle n'existe pas
$create_table = "CREATE TABLE IF NOT EXISTS chequier_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT,
    status VARCHAR(50) DEFAULT 'pending',
    processed_by INT,
    processed_at DATETIME,
    delivery_date DATETIME,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES ecobank_form_submissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $create_table);

// Insérer ou mettre à jour le statut
$check = mysqli_query($conn, "SELECT id FROM chequier_requests WHERE submission_id = $request_id");

if ($check && mysqli_num_rows($check) > 0) {
    // Mettre à jour
    $update = "UPDATE chequier_requests SET status = 'processed', processed_by = " . $_SESSION['emp_id'] . ", processed_at = NOW() WHERE submission_id = $request_id";
    $result = mysqli_query($conn, $update);
} else {
    // Insérer
    $insert = "INSERT INTO chequier_requests (submission_id, status, processed_by, processed_at) VALUES ($request_id, 'processed', " . $_SESSION['emp_id'] . ", NOW())";
    $result = mysqli_query($conn, $insert);
}

if ($result) {
    // Audit logging for chequier processing
    log_admin_action('mark_chequier_processed', $request_id, [
        'status' => 'processed',
        'table' => 'chequier_requests'
    ]);
    
    // Récupérer les infos du client et du CSO pour envoyer les notifications
    $query = "SELECT efs.id, efs.customer_name, efs.account_number, efs.emp_id, efs.email, te.EmailId
              FROM ecobank_form_submissions efs
              LEFT JOIN tblemployees te ON efs.emp_id = te.emp_id
              WHERE efs.id = $request_id";
    
    $res = mysqli_query($conn, $query);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        // Envoyer notification à l'utilisateur qui a saisi le formulaire (CSO)
        $msg_for_cso = "Votre demande de chéquier pour le compte " . $row['account_number'] . " a été traitée.";
        
        $notif_insert = "INSERT INTO notifications (emp_id, message, type, created_at) 
                        VALUES (" . $row['emp_id'] . ", '" . mysqli_real_escape_string($conn, $msg_for_cso) . "', 'chequier_processed', NOW())";
        mysqli_query($conn, $notif_insert);
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Demande marquée comme traitée']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour']);
}
?>
