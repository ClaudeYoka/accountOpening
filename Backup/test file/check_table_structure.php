<?php
header('Content-Type: application/json; charset=utf-8');

include('../includes/config.php');

try {
    if (!$conn) {
        throw new Exception('Erreur de connexion à la base de données');
    }

    // Récupérer la structure de la table
    $result = mysqli_query($conn, "SHOW COLUMNS FROM tblproduits");
    
    if (!$result) {
        throw new Exception('Erreur lors de la requête: ' . mysqli_error($conn));
    }

    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'columns' => $columns,
        'column_count' => count($columns)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
