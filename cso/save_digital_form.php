<?php
// Éviter les redirects pendant le traitement JSON
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Ne pas afficher les erreurs directement

try {
    // Capturer la sortie de session.php
    ob_start();
    include('../includes/session.php');
    $sess_output = ob_get_clean();

    // Si session.php a essayé de rediriger, on l'ignore
    if ($sess_output && stripos($sess_output, 'window.location') !== false) {
        // Session invalide mais on continue
    }

    include('../includes/config.php');

    // Vérifier la connexion DB
    if (!$conn) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Récupérer les données JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Données JSON invalides');
    }

    // Vérifier l'authentification
    if (!isset($_SESSION['emp_id'])) {
        throw new Exception('Utilisateur non authentifié. Veuillez vous reconnecter.');
    }

    $account_number = $data['account_number'] ?? '';
    $branch_code = $data['branch_code'] ?? '';
    $emp_id = $_SESSION['emp_id'];

    if (empty($account_number) || empty($branch_code)) {
        throw new Exception('Compte ou agence manquant');
    }

    // Préparer les données pour insertion dans tblcompte
    $firstname = $data['text_field_0'] ?? 'N/A';
    $services = '';
    $type_compte = '';
    $chequier_requested = false;

    // Extraction des données de chéquier
    if (!empty($data['chequier']) && is_array($data['chequier'])) {
        $chequier_requested = true;
        $type_compte = implode(', ', $data['chequier']) . ' Feuilles';
    }

    // Extraction des services
    $services_array = array();
    if (!empty($data['depot']) && is_array($data['depot'])) {
        $services_array = array_merge($services_array, $data['depot']);
    }

    $services = implode(', ', $services_array);

    // Vérifier que la table tblcompte existe
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tblcompte'");
    if (!$check_table || mysqli_num_rows($check_table) == 0) {
        throw new Exception('Table tblcompte inexistante');
    }

    // Préparer et exécuter l'insertion dans tblcompte
    $sql = "INSERT INTO tblcompte (
        emp_id, 
        firstname, 
        services, 
        type_compte,
        mobile1,
        email,
        date_enregistrement
    ) VALUES (
        '" . mysqli_real_escape_string($conn, $emp_id) . "',
        '" . mysqli_real_escape_string($conn, $firstname) . "',
        '" . mysqli_real_escape_string($conn, $services) . "',
        '" . mysqli_real_escape_string($conn, $type_compte) . "',
        '" . mysqli_real_escape_string($conn, $account_number) . "',
        '" . mysqli_real_escape_string($conn, $account_number . '@ecobank.cg') . "',
        NOW()
    )";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception('Erreur d\'insertion: ' . mysqli_error($conn));
    }

    $submission_id = mysqli_insert_id($conn);

    // Si une demande de chéquier, créer une notification dans tblnotification
    if ($chequier_requested) {
        // Créer la table tblnotification si elle n'existe pas
        $create_notif_table = "CREATE TABLE IF NOT EXISTS tblnotification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emp_id INT,
            message TEXT,
            type VARCHAR(50),
            submission_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_read BOOLEAN DEFAULT 0,
            INDEX (emp_id),
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        @mysqli_query($conn, $create_notif_table);
        
        $msg = "Demande de chéquier effectuée - Compte: " . $account_number . " - Type: " . $type_compte;
        $notif_sql = "INSERT INTO tblnotification (emp_id, message, type, submission_id, created_at) 
                      VALUES ('" . mysqli_real_escape_string($conn, $emp_id) . "', '" . mysqli_real_escape_string($conn, $msg) . "', 'chequier_request', " . intval($submission_id) . ", NOW())";
        @mysqli_query($conn, $notif_sql);
    }

    // Réponse de succès
    echo json_encode([
        'status' => 'success',
        'message' => 'Formulaire enregistré avec succès',
        'submission_id' => $submission_id,
        'chequier_requested' => $chequier_requested
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
?>
