<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Capturer la sortie de session.php
    ob_start();
    include('../includes/session.php');
    $sess_output = ob_get_clean();

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

    $client_name = $data['client_name'] ?? '';
    $branch_code = $data['branch_code'] ?? '';
    $account_number = $data['account_number'] ?? '';
    $rib_key = $data['rib_key'] ?? '';
    $address = $data['address'] ?? '';
    $phone_number = $data['phone_number'] ?? '';
    $email = $data['email'] ?? '';
    $chequier = $data['chequier'] ?? array();
    $status = $data['status'] ?? 'En Cours';
    $manual_quantity = $data['quantity'] ?? null;
    $emp_id = $_SESSION['emp_id'];

    // Validations
    if (empty($client_name) || empty($branch_code) || empty($account_number) || empty($rib_key) || empty($address) || empty($phone_number) || empty($email) || empty($chequier)) {
        throw new Exception('Tous les champs sont obligatoires');
    }

    if (!is_array($chequier) || count($chequier) === 0) {
        throw new Exception('Au moins un type de chéquier doit être sélectionné');
    }

    // Utiliser la quantité manuelle si fournie, sinon utiliser le nombre de types
    $quantity = $manual_quantity ? intval($manual_quantity) : count($chequier);
    
    if ($quantity < 1) {
        throw new Exception('La quantité doit être au minimum 1');
    }

    // Vérifier que la table tblcompte existe
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tblcompte'");
    if (!$check_table || mysqli_num_rows($check_table) == 0) {
        throw new Exception('Table tblcompte inexistante');
    }

    // Construire les valeurs de type_compte
    $type_compte = implode(', ', $chequier) . ' Feuilles';

    // Insérer dans tblcompte (utiliser colonnes existantes + branch_code)
    // Stocker rib_key dans nip et quantité dans etabliss
    $sql = "INSERT INTO tblcompte (
        emp_id, 
        firstname, 
        type_compte,
        mobile1,
        email,
        adr_rue,
        branch_code,
        nip,
        etabliss,
        access,
        date_enregistrement
    ) VALUES (
        '" . mysqli_real_escape_string($conn, $emp_id) . "',
        '" . mysqli_real_escape_string($conn, $client_name) . "',
        '" . mysqli_real_escape_string($conn, $type_compte) . "',
        '" . mysqli_real_escape_string($conn, $phone_number) . "',
        '" . mysqli_real_escape_string($conn, $email) . "',
        '" . mysqli_real_escape_string($conn, $address) . "',
        '" . mysqli_real_escape_string($conn, $branch_code) . "',
        '" . mysqli_real_escape_string($conn, $rib_key) . "',
        '" . intval($quantity) . "',
        '" . mysqli_real_escape_string($conn, $status) . "',
        NOW()
    )";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception('Erreur d\'insertion: ' . mysqli_error($conn));
    }

    $submission_id = mysqli_insert_id($conn);

    // Créer une notification dans tblnotification
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
    
    $msg = "Demande de chéquier effectuée - Client: " . $client_name . " - Compte: " . $account_number . " - Type: " . $type_compte;
    $notif_sql = "INSERT INTO tblnotification (emp_id, message, type, submission_id, created_at) 
                  VALUES ('" . mysqli_real_escape_string($conn, $emp_id) . "', '" . mysqli_real_escape_string($conn, $msg) . "', 'chequier_request', " . intval($submission_id) . ", NOW())";
    @mysqli_query($conn, $notif_sql);

    // Réponse de succès
    echo json_encode([
        'status' => 'success',
        'message' => 'Demande enregistrée avec succès',
        'submission_id' => $submission_id
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
