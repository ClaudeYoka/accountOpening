<?php
include('../includes/session.php');
include('../includes/config.php');

// Retourner les détails d'une demande de chéquier
if (!isset($_GET['request_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'request_id manquant']);
    exit;
}

$request_id = intval($_GET['request_id']);

$query = "SELECT
            tc.id,
            tc.firstname as customer_name,
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
            tc.titre as has_card,
            tc.objectif as fees,
            tc.devise_pref as enrolled,
            tc.ident_etud as serial_number,
            tb.DepartmentName as agency_name
        FROM tblcompte tc
        LEFT JOIN tbldepartments tb ON tc.branch_code COLLATE utf8mb4_general_ci = tb.DepartmentShortName COLLATE utf8mb4_general_ci
        WHERE tc.id = $request_id
        LIMIT 1";

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur requête : ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Demande non trouvée']);
    exit;
}

$row = mysqli_fetch_assoc($result);

// Formater la date
$row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));

echo json_encode([
    'success' => true,
    'request' => $row
]);
?>
