<?php
header('Content-Type: application/json; charset=utf-8');

include('../includes/config.php');

$result = [
    'database_connection' => false,
    'table_exists' => false,
    'row_count' => 0,
    'columns_count' => 0,
    'missing_columns' => []
];

try {
    if (!$conn) {
        throw new Exception('Connexion échouée');
    }
    $result['database_connection'] = true;

    // Vérifier si la table existe
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tblproduits'");
    if ($check_table && mysqli_num_rows($check_table) > 0) {
        $result['table_exists'] = true;

        // Compter les lignes
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tblproduits");
        $count_row = mysqli_fetch_assoc($count_result);
        $result['row_count'] = intval($count_row['cnt']);

        // Vérifier les colonnes
        $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM tblproduits");
        $columns = [];
        while ($col = mysqli_fetch_assoc($columns_result)) {
            $columns[] = $col['Field'];
        }
        $result['columns_count'] = count($columns);

        // Les colonnes requises
        $required_columns = [
            'id', 'customer_id', 'first_name', 'last_name', 'mobile', 'services',
            'deposit_amount', 'emp_id', 'branch_code', 'date_enregistrement',
            'card_classic', 'card_gold', 'card_platinum',
            'srv_ecobank_app', 'srv_airtel_money', 'srv_insurance', 'srv_mobile_money',
            'srv_estatement', 'srv_sms_alert', 'online_transfer', 'online_western_union',
            'gestionnaire', 'chef_agence', 'airtel_phone', 'mobilemoney_phone',
            'chequier_types', 'deposit_type'
        ];

        $result['missing_columns'] = array_diff($required_columns, $columns);
    }

    echo json_encode($result);

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    echo json_encode($result);
}
?>
