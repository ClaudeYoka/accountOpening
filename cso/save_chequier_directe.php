<?php
// always return JSON and hide any accidental output
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// ensure even fatal errors are returned as JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        http_response_code(500);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur fatale: ' . $err['message']
        ]);
        exit;
    }
});

// start a full-page output buffer so we can discard anything that leaks
ob_start();

try {
    // Capturer la sortie de session.php (inner buffer)
    ob_start();
    include('../includes/session.php');
    $sess_output = ob_get_clean();

    // Si la session a produit un script de redirection (non authentifié), échouer
    if (strpos($sess_output, 'window.location') !== false) {
        throw new Exception('Utilisateur non authentifié. Veuillez vous reconnecter.');
    }

    include('../includes/config.php');
    include('../includes/audit_helpers.php');

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
    // Accepter plusieurs clés possibles pour le téléphone (formulaire direct, airtel, mobile money)
    $phone_number = trim($data['phone_number'] ?? $data['airtel_phone'] ?? $data['mobilemoney_phone'] ?? '');
    $email = $data['email'] ?? '';
    $chequier = $data['chequier'] ?? array();
    $account_type = $data['account_type'] ?? array();
    // récupérer la première valeur des cases à cocher sans appeler reset() sur une expression
    $has_card = '';
    if (isset($data['carte'])) {
        if (is_array($data['carte'])) {
            $has_card = $data['carte'][0] ?? '';
        } else {
            $has_card = $data['carte'];
        }
    }
    $fees = '';
    if (isset($data['frais'])) {
        if (is_array($data['frais'])) {
            $fees = $data['frais'][0] ?? '';
        } else {
            $fees = $data['frais'];
        }
    }
    $enrolled = '';
    if (isset($data['enrolled'])) {
        if (is_array($data['enrolled'])) {
            $enrolled = $data['enrolled'][0] ?? '';
        } else {
            $enrolled = $data['enrolled'];
        }
    }
    $serial_number = $data['serial_number'] ?? '';    // Statut par défaut cohérent : 'En cours'
    $status = $data['status'] ?? 'encours';
    $manual_quantity = $data['quantity'] ?? null;
    $emp_id = $_SESSION['emp_id'];

    // Validations améliorées
    $errors = [];

    // Validation du nom du client
    if (empty($client_name)) {
        $errors[] = "Le nom du client est requis";
    } elseif (strlen($client_name) < 2 || strlen($client_name) > 100) {
        $errors[] = "Le nom du client doit contenir entre 2 et 100 caractères";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/", $client_name)) {
        $errors[] = "Le nom du client contient des caractères invalides";
    }

    // Validation du code d'agence
    if (empty($branch_code)) {
        $errors[] = "Le code d'agence est requis";
    } elseif (!preg_match("/^[A-Z0-9\-]{2,10}$/", $branch_code)) {
        $errors[] = "Le code d'agence n'est pas valide";
    }

    // Validation du numéro de compte
    if (empty($account_number)) {
        $errors[] = "Le numéro de compte est requis";
    } elseif (!preg_match("/^[0-9]{10,20}$/", $account_number)) {
        $errors[] = "Le numéro de compte doit contenir entre 10 et 20 chiffres";
    }

    // Validation de la clé RIB
    // if (empty($rib_key)) {
    //     $errors[] = "La clé RIB est requise";
    // } elseif (!preg_match("/^[0-9]{2}$/", $rib_key)) {
    //     $errors[] = "La clé RIB doit être composée de 2 chiffres";
    // }

    // Validation de l'adresse
    if (empty($address)) {
        $errors[] = "L'adresse est requise";
    } elseif (strlen($address) < 5 || strlen($address) > 200) {
        $errors[] = "L'adresse doit contenir entre 5 et 200 caractères";
    }

    // Validation du numéro de téléphone
    if (empty($phone_number)) {
        $errors[] = "Le numéro de téléphone est requis";
    } elseif (!preg_match("/^[0-9+\-\s()]{9,15}$/", $phone_number)) {
        $errors[] = "Le numéro de téléphone n'est pas valide";
    }

    // Validation de l'email
    if (empty($email)) {
        $errors[] = "L'adresse email est requise";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide";
    }

    if (!empty($errors)) {
        throw new Exception(implode("\\n", $errors));
    }

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
    $type_compte = implode(', ', $chequier);

    // Construire la valeur services à partir du type de compte (COURANT/EPARGNE)
    if (is_array($account_type)) {
        $services = implode(', ', $account_type);
    } else {
        $services = trim((string)$account_type);
    }

    // Insérer dans tblcompte (utiliser colonnes existantes + branch_code)
    // Stocker rib_key dans nip et quantité dans etabliss
    $stmt = mysqli_prepare($conn, "INSERT INTO tblcompte (
        emp_id,
        firstname,
        account_number,
        services,
        type_compte,
        mobile1,
        email,
        adr_rue,
        branch_code,
        nip,
        etabliss,
        access,
        titre,
        objectif,
        devise_pref,
        ident_etud,
        date_enregistrement
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    mysqli_stmt_bind_param($stmt, "ssssssssssssssss", 
        $emp_id,
        $client_name,
        $account_number,
        $services,
        $type_compte,
        $phone_number,
        $email,
        $address,
        $branch_code,
        $rib_key,
        $quantity,
        $status,
        $has_card,
        $fees,
        $enrolled,
        $serial_number
    );

    $result = mysqli_stmt_execute($stmt);

    if (!$result) {
        throw new Exception('Erreur d\'insertion: ' . mysqli_error($conn));
    }

    mysqli_stmt_close($stmt);

    $submission_id = mysqli_insert_id($conn);

    // Audit logging for direct chequier request
    log_form_submission_success($conn, 'direct_chequier', $submission_id, [
        'client_name' => $client_name,
        'account_number' => $account_number,
        'branch_code' => $branch_code,
        'rib_key' => $rib_key,
        'address' => $address,
        'phone_number' => $phone_number,
        'email' => $email,
        'chequier_types' => $chequier,
        'account_type' => $account_type,
        'quantity' => $quantity,
        'has_card' => $has_card,
        'fees' => $fees,
        'enrolled' => $enrolled,
        'serial_number' => $serial_number,
        'status' => $status
    ]);

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
    
    $msg = "Demande de chéquier effectuée - Client: " . $client_name . "  - Type: " . $type_compte;
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO tblnotification (emp_id, message, type, submission_id, created_at) VALUES (?, ?, 'chequier_request', ?, NOW())");
    mysqli_stmt_bind_param($notif_stmt, "ssi", $emp_id, $msg, $submission_id);
    @mysqli_stmt_execute($notif_stmt);
    @mysqli_stmt_close($notif_stmt);

    // Réponse de succès
    $response = [
        'status' => 'success',
        'message' => 'Demande enregistrée avec succès',
        'submission_id' => $submission_id
    ];

} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// wipe any buffered output and send only JSON
while (ob_get_level() > 0) {
    ob_end_clean();
}
echo json_encode($response);
exit;
?>
