<?php
/**
 * Test API Response
 * Teste exactement ce que l'API retourne
 */

header('Content-Type: application/json; charset=utf-8');

// Inclure les helpers
include('../includes/config.php');
include('../includes/session.php');
include('../includes/flexcube_helpers.php');

// Numéro de compte à tester (à remplacer)
$test_account = isset($_GET['account']) ? trim($_GET['account']) : '0000000001';

error_log("[TEST] Début du test avec account: $test_account");

// Essayer une connexion Flexcube directe
error_log("[TEST] Appel de fetchAccountFromOracleDatabase()");
$result = fetchAccountFromOracleDatabase($test_account);

if ($result) {
    error_log("[TEST] Résultat reçu: " . json_encode($result));
    echo json_encode([
        'success' => true,
        'message' => 'Données récupérées avec succès',
        'data' => $result,
        'keys' => array_keys($result),
        'account_name_exists' => isset($result['account_name']),
        'account_name_value' => $result['account_name'] ?? 'NOT FOUND'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    error_log("[TEST] Aucune donnée retournée");
    echo json_encode([
        'success' => false,
        'message' => 'Impossible de récupérer les données',
        'account' => $test_account
    ], JSON_PRETTY_PRINT);
}
