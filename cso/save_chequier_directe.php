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
    $enrolled = '';
    if (isset($data['enrolled'])) {
        if (is_array($data['enrolled'])) {
            $enrolled = $data['enrolled'][0] ?? '';
        } else {
            $enrolled = $data['enrolled'];
        }
    }
    $serial_number1 = trim((string)($data['serial_number1'] ?? $data['serial_number'] ?? ''));
    $serial_number2 = trim((string)($data['serial_number2'] ?? $data['cond'] ?? ''));
    $status = $data['status'] ?? 'encours';
    $manual_quantity = $data['quantity'] ?? null;
    $flexcube_account_type = trim((string)($data['flexcube_account_type'] ?? ''));
    $emp_id = $_SESSION['emp_id'];

    function parse_leaf_count_from_type_compte($value) {
        if (empty($value)) {
            return 0;
        }

        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map('trim', $value)));
        }

        if (!is_string($value)) {
            $value = (string) $value;
        }

        $matches = [];
        preg_match_all('/(\d+)/', $value, $matches);

        $total = 0;
        foreach ($matches[1] as $number) {
            $total += (int)$number;
        }

        return $total;
    }

    $type_compte = is_array($chequier) ? implode(', ', array_filter($chequier)) : trim((string)$chequier);
    $quantity = $manual_quantity ? intval($manual_quantity) : (is_array($chequier) ? count($chequier) : 0);
    $requested_fees = $data['frais'] ?? '';
    if (is_array($requested_fees)) {
        $requested_fees = $requested_fees[0] ?? '';
    }
    $requested_fees = strtoupper(trim((string)$requested_fees));
    $check_only = !empty($data['check_only']);
    $force_submit = !empty($data['force_submit']);

    // Vérifier si une demande en cours existe déjà pour ce client
    $pending_request = null;
    if (!empty($account_number)) {
        $pending_stmt = mysqli_prepare($conn, "SELECT branch_code, id FROM tblcompte WHERE account_number = ? AND type_compte IS NOT NULL AND type_compte != '' AND (access = 'encours' OR access = 'ENCOURS' OR access = 'En cours' OR access = 'en cours') ORDER BY date_enregistrement DESC LIMIT 1");
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, 's', $account_number);
            mysqli_stmt_execute($pending_stmt);
            $pending_result = mysqli_stmt_get_result($pending_stmt);
            if ($pending_result && mysqli_num_rows($pending_result) > 0) {
                $pending_request = mysqli_fetch_assoc($pending_result);
            }
            mysqli_stmt_close($pending_stmt);
        }
    }

    if ($pending_request && !$force_submit) {
        $agency_name = $pending_request['branch_code'] ?? 'Agence non renseignée';
        if ($check_only) {
            http_response_code(200);
            echo json_encode([
                'status' => 'duplicate',
                'message' => !empty($agency_name)
                    ? "Une demande de chéquier est déjà en cours pour ce compte. Elle a été enregistrée à l'agence {$agency_name}."
                    : 'Une demande de chéquier est déjà en cours pour ce compte.'
            ]);
            exit;
        }

        http_response_code(409);
        echo json_encode([
            'status' => 'duplicate',
            'message' => !empty($agency_name)
                ? "Une demande de chéquier est déjà en cours pour ce compte. Elle a été enregistrée à l'agence {$agency_name}."
                : 'Une demande de chéquier est déjà en cours pour ce compte.'
        ]);
        exit;
    }

    // Vérifier le cumul annuel des feuilles pour déterminer si les frais sont prélevés
    $annual_total_leaves = 0;
    $annual_stmt = mysqli_prepare($conn, "SELECT type_compte, etabliss FROM tblcompte WHERE account_number = ? AND type_compte IS NOT NULL AND type_compte != '' AND YEAR(date_enregistrement) = YEAR(CURDATE())");
    if ($annual_stmt) {
        mysqli_stmt_bind_param($annual_stmt, 's', $account_number);
        mysqli_stmt_execute($annual_stmt);
        $annual_result = mysqli_stmt_get_result($annual_stmt);
        if ($annual_result) {
            while ($annual_row = mysqli_fetch_assoc($annual_result)) {
                $leaf_count = parse_leaf_count_from_type_compte($annual_row['type_compte'] ?? '');
                $existing_quantity = max(1, (int)($annual_row['etabliss'] ?? 1));
                $annual_total_leaves += $leaf_count * $existing_quantity;
            }
        }
        mysqli_stmt_close($annual_stmt);
    }

    $normalized_account_type = strtoupper(trim(preg_replace('/\s+/', ' ', $flexcube_account_type)));
    $is_savings_account = strpos($normalized_account_type, 'EPARGNE') !== false || strpos($normalized_account_type, 'SAVINGS') !== false;
    $is_current_account = strpos($normalized_account_type, 'COURANT') !== false || strpos($normalized_account_type, 'CURRENT') !== false;
    $is_physical_current = strpos($normalized_account_type, 'COMPTE COURANT CLASSIC') !== false
        && strpos($normalized_account_type, 'PERSONNES PHYSIQUES') !== false;

    if ($is_savings_account) {
        throw new Exception('Un compte épargne ne peut pas recevoir de chéquier.');
    }

    $fees_required = $annual_total_leaves >= 50 || ($is_current_account && !$is_physical_current);
    $calculated_fees = $fees_required ? 'OUI' : 'NON';
    $fees = in_array($requested_fees, ['OUI', 'NON'], true) ? $requested_fees : $calculated_fees;
    $fees_reason = $fees_required
        ? "Les frais sont obligatoires : le cumul antérieur est de {$annual_total_leaves} feuille(s) ou le type de compte impose le prélèvement."
        : "Les frais ne sont pas obligatoires : le cumul antérieur est de {$annual_total_leaves} feuille(s) et le compte est un compte courant classic pour personnes physiques."
    ;
    $fees_calculation = "Cumul avant cette demande : {$annual_total_leaves} feuille(s) => Frais prélevés : {$calculated_fees}";

    if ($requested_fees !== $calculated_fees) {
        throw new Exception("Le choix des frais est incorrect. Selon le cumul et le type de compte Flexcube, sélectionnez « {$calculated_fees} » puis recommencez.");
    }

    if ($check_only) {
        http_response_code(200);
        echo json_encode([
            'status' => 'ready',
            'message' => 'Vérification réussie. Veuillez confirmer l’envoi de la demande.',
            'fees_status' => $fees,
            'request_summary' => [
                'client' => $client_name,
                'account' => $account_number,
                'agency' => $branch_code,
                'quantity' => $quantity,
                'types' => $type_compte,
                'fees' => $fees,
                'fees_calculation' => $fees_calculation,
                'fees_reason' => $fees_reason
            ]
        ]);
        exit;
    }

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

    if ($quantity < 1) {
        throw new Exception('La quantité doit être au minimum 1');
    }

    // Vérifier que la table tblcompte existe
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tblcompte'");
    if (!$check_table || mysqli_num_rows($check_table) == 0) {
        throw new Exception('Table tblcompte inexistante');
    }

    $check_cond_column = mysqli_query($conn, "SHOW COLUMNS FROM tblcompte LIKE 'cond'");
    if (!$check_cond_column || mysqli_num_rows($check_cond_column) == 0) {
        mysqli_query($conn, "ALTER TABLE tblcompte ADD COLUMN cond VARCHAR(255) DEFAULT NULL");
    }

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
        cond,
        date_enregistrement
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $types = str_repeat('s', 17);
    mysqli_stmt_bind_param($stmt, $types, 
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
        $serial_number1,
        $serial_number2
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
        'serial_number1' => $serial_number1,
        'serial_number2' => $serial_number2,
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
        'message' => 'Demande enregistrée avec succès. Frais prélevés : ' . ($fees === 'OUI' ? 'Oui' : 'Non'),
        'submission_id' => $submission_id,
        'fees_status' => $fees
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
