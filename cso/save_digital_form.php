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

    // Si session.php a essayé de rediriger, on renvoie une erreur JSON cohérente
    if ($sess_output && stripos($sess_output, 'window.location') !== false) {
        http_response_code(401);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode([
            'status' => 'error',
            'message' => 'Session expirée ou non authentifiée. Veuillez vous reconnecter.'
        ]);
        exit;
    }

    // Si session.php a renvoyé autre chose, logger pour debug (mais continuer)
    if ($sess_output && !stripos($sess_output, 'window.location')) {
        error_log('[save_digital_form] sortie incomprise depuis session.php: ' . trim(strip_tags($sess_output)));
    }

    require_once('../includes/config.php');
    require_once('../includes/flexcube_helpers.php');
    require_once('../includes/audit_helpers.php');

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

    $account_number = $data['account_number'] ?? $data['account'] ?? '';
    // récupérer automatiquement le code agence depuis la session si disponible
    $branch_code = $data['branch_code'] ?? $data['branch'] ?? ($_SESSION['adepart'] ?? '');
    $emp_id = $_SESSION['emp_id'];

    // Validations améliorées - moins strictes
    $errors = [];

    // Validation du numéro de compte
    if (empty($account_number)) {
        $errors[] = "Le numéro de compte est requis";
    } elseif (!preg_match("/^[0-9]{10,20}$/", $account_number)) {
        $errors[] = "Le numéro de compte doit contenir entre 10 et 20 chiffres";
    }

    if (!empty($errors)) {
        throw new Exception(implode("\\n", $errors));
    }

    // Si branch_code n'existe pas, on utilise une valeur par défaut ou on le rend optionnel
    if (empty($branch_code)) {
        $branch_code = 'DEFAULT';  // Valeur par défaut pour éviter les erreurs
    }

    // Préparer les données pour insertion dans tblcompte
    // Déterminer le nom complet : priorité au champ pré-rempli dans le formulaire,
    // sinon tenter Flexcube via account_number, sinon session.
    $firstname = trim($data['text_field_0'] ?? '');
    $flex_data = null;
    if (empty($firstname) && !empty($account_number)) {
        $flex_resp = fetchAccountFromFlexcube($account_number);
        if (!empty($flex_resp) && is_array($flex_resp)) {
            $flex_data = $flex_resp;
        }

        if (!empty($flex_data)) {
            if (!empty($flex_data['account_name'])) {
                $firstname = $flex_data['account_name'];
            } else {
                $fn = $flex_data['first_name'] ?? $flex_data['form_fields']['first-name'] ?? '';
                $ln = $flex_data['last_name'] ?? $flex_data['form_fields']['last-name'] ?? '';
                $fullname = trim($fn . ' ' . $ln);
                if ($fullname) $firstname = $fullname;
            }
        }
    }

    if (empty($firstname)) {
        $firstname = $_SESSION['user_fullname'] ?? 'N/A';
    }
    // Pour compatibilité, on écrira aussi dans `noms` la même valeur (nom complet)
    $services = '';
    $type_compte = '';
    $chequier_requested = false;

    // Extraction des données de chéquier
    if (!empty($data['chequier']) && is_array($data['chequier'])) {
        $chequier_requested = true;
        $type_compte = implode(', ', $data['chequier']);
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
    // Préparer un champ sûr pour le mobile (peut provenir du formulaire ou Flexcube)
    $mobile = trim($data['mobile'] ?? $data['mobile1'] ?? '');
    if (empty($mobile) && empty($flex_data) && !empty($account_number)) {
        $flex_resp = fetchAccountFromFlexcube($account_number);
        if (!empty($flex_resp) && is_array($flex_resp)) {
            $flex_data = $flex_resp;
        }
    }
    if (!empty($flex_data) && empty($mobile)) {
        $mobile = $flex_data['phone'] ?? $flex_data['mobile'] ?? $flex_data['form_fields']['telephone'] ?? '';
    }
    if (empty($branch_code) && !empty($flex_data)) {
        $branch_code = $flex_data['branch_code'] ?? $flex_data['form_fields']['branch_code'] ?? $branch_code;
    }

    // --- Simplifier l'enregistrement : n'enregistrer QUE les champs demandés ---
    // Le champ `services` doit contenir le type de compte (provenant de Flexcube si disponible)
    $services = trim($flex_data['account_type'] ?? $services);

    // Type de chéquier (ex: "25 Feuilles") déjà extrait dans $type_compte
    // Montant du premier dépôt : champ `depot_montant` envoyé par le formulaire
    $deposit_amount = trim($data['depot_montant'] ?? $data['depot'] ?? '');
    // Enregistrer le montant du premier dépôt dans la colonne `cond`
    $cond = $deposit_amount;

    // Nous n'enregistrons plus le snapshot JSON dans ecobank_form_submissions
    $submission_id = null; // garde la variable disponible pour la réponse

    // Insertion dans table tblproduits (produit digital)
    // Split du nom complet en first_name / last_name
    $full_name = trim($firstname);
    $first_name = $full_name;
    $last_name = '';
    if (strpos($full_name, ' ') !== false) {
        $parts = preg_split('/\s+/', $full_name);
        $first_name = array_shift($parts);
        $last_name = trim(implode(' ', $parts));
    }

    // Extraire TOUS les champs du formulaire produits
    $card_classic = (int)($data['card_classic'] ?? 0);
    $card_gold = (int)($data['card_gold'] ?? 0);
    $card_platinum = (int)($data['card_platinum'] ?? 0);
    $srv_ecobank_app = (int)($data['srv_ecobank_app'] ?? 0);
    $srv_airtel_money = (int)($data['srv_airtel_money'] ?? 0);
    $srv_insurance = (int)($data['srv_insurance'] ?? 0);
    $srv_mobile_money = (int)($data['srv_mobile_money'] ?? 0);
    $srv_estatement = (int)($data['srv_estatement'] ?? 0);
    $srv_sms_alert = (int)($data['srv_sms_alert'] ?? 0);
    $online_transfer = (int)($data['online_transfer'] ?? 0);
    $online_western_union = (int)($data['online_western_union'] ?? 0);
    
    $gestionnaire = trim($data['gestionnaire'] ?? '');
    $chef_agence = trim($data['chef_agence'] ?? '');
    $airtel_phone = trim($data['airtel_phone'] ?? '');
    $mobilemoney_phone = trim($data['mobilemoney_phone'] ?? '');
    
    // Convertir les tableaux en JSON pour stockage
    $chequier_types = !empty($data['chequier']) ? json_encode($data['chequier']) : null;
    $deposit_type = !empty($data['depot']) ? json_encode($data['depot']) : null;

    // Requête INSERT avec TOUS les champs
    $produit_stmt = mysqli_prepare($conn, "INSERT INTO tblproduits (
        customer_id,
        first_name,
        last_name,
        mobile,
        services,
        deposit_amount,
        emp_id,
        branch_code,
        card_classic,
        card_gold,
        card_platinum,
        srv_ecobank_app,
        srv_airtel_money,
        srv_insurance,
        srv_mobile_money,
        srv_estatement,
        srv_sms_alert,
        online_transfer,
        online_western_union,
        gestionnaire,
        chef_agence,
        airtel_phone,
        mobilemoney_phone,
        chequier_types,
        deposit_type,
        date_enregistrement
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        mobile = VALUES(mobile),
        services = VALUES(services),
        deposit_amount = VALUES(deposit_amount),
        branch_code = VALUES(branch_code),
        card_classic = VALUES(card_classic),
        card_gold = VALUES(card_gold),
        card_platinum = VALUES(card_platinum),
        srv_ecobank_app = VALUES(srv_ecobank_app),
        srv_airtel_money = VALUES(srv_airtel_money),
        srv_insurance = VALUES(srv_insurance),
        srv_mobile_money = VALUES(srv_mobile_money),
        srv_estatement = VALUES(srv_estatement),
        srv_sms_alert = VALUES(srv_sms_alert),
        online_transfer = VALUES(online_transfer),
        online_western_union = VALUES(online_western_union),
        gestionnaire = VALUES(gestionnaire),
        chef_agence = VALUES(chef_agence),
        airtel_phone = VALUES(airtel_phone),
        mobilemoney_phone = VALUES(mobilemoney_phone),
        chequier_types = VALUES(chequier_types),
        deposit_type = VALUES(deposit_type),
        date_enregistrement = NOW()");

    // Bind parameters pour 26 colonnes (y compris le NOW() qui n'est pas bindé)
    mysqli_stmt_bind_param($produit_stmt, "ssssssssiiiiiiiiiiissssss",
        $account_number,
        $first_name,
        $last_name,
        $mobile,
        $services,
        $deposit_amount,
        $emp_id,
        $branch_code,
        $card_classic,
        $card_gold,
        $card_platinum,
        $srv_ecobank_app,
        $srv_airtel_money,
        $srv_insurance,
        $srv_mobile_money,
        $srv_estatement,
        $srv_sms_alert,
        $online_transfer,
        $online_western_union,
        $gestionnaire,
        $chef_agence,
        $airtel_phone,
        $mobilemoney_phone,
        $chequier_types,
        $deposit_type
    );

    $produit_result = mysqli_stmt_execute($produit_stmt);
    if (!$produit_result) {
        throw new Exception('Erreur d\'insertion produit: ' . mysqli_error($conn));
    }

    mysqli_stmt_close($produit_stmt);

    $produit_db_id = mysqli_insert_id($conn);
    if (!$produit_db_id) {
        $lookup = mysqli_prepare($conn, "SELECT id FROM tblproduits WHERE customer_id = ? LIMIT 1");
        mysqli_stmt_bind_param($lookup, 's', $account_number);
        mysqli_stmt_execute($lookup);
        $lookup_res = mysqli_stmt_get_result($lookup);
        $prod_row = mysqli_fetch_assoc($lookup_res);
        $produit_db_id = $prod_row['id'] ?? null;
        mysqli_stmt_close($lookup);
    }

    $submission_id = $produit_db_id;

    // Si c'est également une demande de chéquier, on l'enregistre en plus dans tblcompte
    if ($chequier_requested) {
        $stmt = mysqli_prepare($conn, "INSERT INTO tblcompte (
            emp_id,
            firstname,
            account_number,
            services,
            type_compte,
            branch_code,
            mobile1,
            cond,
            access,
            date_enregistrement
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'encours', NOW())");

        mysqli_stmt_bind_param($stmt, "ssssssss",
            $emp_id,
            $firstname,
            $account_number,
            $services,
            $type_compte,
            $branch_code,
            $mobile,
            $cond
        );

        $result = mysqli_stmt_execute($stmt);
        if (!$result) {
            throw new Exception('Erreur d\'insertion chéquier: ' . mysqli_error($conn));
        }

        mysqli_stmt_close($stmt);
    }

    // Audit logging for digital form submission
    log_form_submission_success($conn, 'digital_form', $submission_id, [
        'account_number' => $account_number,
        'branch_code' => $branch_code,
        'firstname' => $firstname,
        'services' => $services,
        'type_compte' => $type_compte,
        'mobile' => $mobile,
        'deposit_amount' => $deposit_amount,
        'chequier_requested' => $chequier_requested
    ]);

    // Si une demande de chéquier, créer une notification dans tblnotification
    if ($chequier_requested) {
        
        $msg = "Demande de chéquier effectuée - Compte: " . $account_number . " - Type: " . $type_compte;
        $notif_stmt = mysqli_prepare($conn, "INSERT INTO tblnotification (emp_id, message, type, submission_id, created_at) VALUES (?, ?, 'chequier_request', ?, NOW())");
        mysqli_stmt_bind_param($notif_stmt, "ssi", $emp_id, $msg, $submission_id);
        @mysqli_stmt_execute($notif_stmt);
        @mysqli_stmt_close($notif_stmt);
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
