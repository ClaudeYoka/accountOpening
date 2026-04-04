<?php
header('Content-Type: application/json; charset=utf-8');

include('../includes/config.php');

try {
    if (!$conn) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Récupérer les dernières données enregistrées
    $result = mysqli_query($conn, "SELECT 
        id, customer_id, first_name, last_name, mobile,
        card_classic, card_gold, card_platinum,
        srv_ecobank_app, srv_mobile_money,
        date_enregistrement
    FROM tblproduits 
    ORDER BY id DESC LIMIT 10");
    
    if (!$result) {
        throw new Exception('Erreur lors de la requête: ' . mysqli_error($conn));
    }

    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Convertir les valeurs numériques en booléens pour l'affichage
        foreach ($row as $key => &$value) {
            if (in_array($key, ['card_classic', 'card_gold', 'card_platinum', 'srv_ecobank_app', 'srv_mobile_money'])) {
                $value = (bool)$value;
            }
        }
        $records[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'records' => $records,
        'count' => count($records)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
