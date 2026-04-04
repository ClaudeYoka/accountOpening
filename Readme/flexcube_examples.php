<?php
/**
 * Exemples d'utilisation de l'API Flexcube
 * 
 * Ce fichier montre différents scénarios d'utilisation
 * À adapter selon vos besoins
 */

// ============================================
// EXEMPLE 1: Récupération Simple
// ============================================

echo "=== EXEMPLE 1: Récupération Simple ===\n";

include('includes/flexcube_helpers.php');

$account_number = '37220020391';
$account = fetchAccountFromFlexcube($account_number);

if ($account) {
    echo "✓ Compte trouvé:\n";
    echo "  Numéro: " . $account['account_number'] . "\n";
    echo "  Nom: " . $account['account_name'] . "\n";
    echo "  Solde: " . $account['balance'] . " " . $account['currency'] . "\n";
    echo "  Statut: " . $account['status'] . "\n";
} else {
    echo "✗ Compte non trouvé\n";
}

echo "\n";

// ============================================
// EXEMPLE 2: Avec Fallback vers la BD
// ============================================

echo "=== EXEMPLE 2: Avec Fallback vers la BD ===\n";

// Supposant que $conn est disponible (connexion BD)
// include('../includes/config.php');

$result = fetchAccountWithFallback('37220020391', $conn);

echo "Source: " . $result['source'] . "\n"; // flexcube, local_db, tblCompte
if ($result['data']) {
    echo "Données récupérées avec succès\n";
} else {
    echo "Compte non trouvé\n";
}

echo "\n";

// ============================================
// EXEMPLE 3: Test de Connexion
// ============================================

echo "=== EXEMPLE 3: Test de Connexion ===\n";

$test = testFlexcubeConnection();
echo "Status: " . $test['status'] . "\n";
echo "Message: " . $test['message'] . "\n";

if ($test['connected']) {
    echo "✓ API Flexcube est accessible\n";
} else {
    echo "✗ Impossible de se connecter à l'API\n";
    echo "Erreur: " . $test['details']['error'] . "\n";
}

echo "\n";

// ============================================
// EXEMPLE 4: Accès Direct à la Classe
// ============================================

echo "=== EXEMPLE 4: Accès Direct à la Classe ===\n";

$flexcube = getFlexcubeAPI();

// Configuration personnalisée (optionnel)
$flexcube->setSSLVerification(false); // À true en production

// Appel simple
$response = $flexcube->getAccountInfo('37220020391');

if ($response['success']) {
    $data = $response['data'];
    echo "✓ Succès\n";
    echo "  Account: " . $data['account_number'] . "\n";
    echo "  Name: " . $data['account_name'] . "\n";
} else {
    echo "✗ Erreur: " . $response['error'] . "\n";
}

echo "\n";

// ============================================
// EXEMPLE 5: Batch de Comptes
// ============================================

echo "=== EXEMPLE 5: Batch de Comptes ===\n";

$accounts_to_fetch = [
    '37220020391',
    '37220020392',
    '37220020393'
];

$batch_results = fetchMultipleAccountsFromFlexcube($accounts_to_fetch);

foreach ($batch_results as $account_num => $response) {
    if ($response['success']) {
        echo "✓ " . $account_num . ": " . $response['data']['account_name'] . "\n";
    } else {
        echo "✗ " . $account_num . ": " . $response['error'] . "\n";
    }
}

echo "\n";

// ============================================
// EXEMPLE 6: Mappage vers Format Local
// ============================================

echo "=== EXEMPLE 6: Mappage vers Format Local ===\n";

$flexcube_account = fetchAccountFromFlexcube('37220020391');

if ($flexcube_account) {
    // Mapper au format local
    $local_format = mapFlexcubeToLocal($flexcube_account);
    
    echo "Format Local:\n";
    echo "  account_number: " . $local_format['account_number'] . "\n";
    echo "  customer_name: " . $local_format['customer_name'] . "\n";
    echo "  status: " . $local_format['status'] . "\n";
    echo "  source: " . $local_format['source'] . "\n";
}

echo "\n";

// ============================================
// EXEMPLE 7: Enrichissement de Données BD
// ============================================

echo "=== EXEMPLE 7: Enrichissement de Données BD ===\n";

// Supposant une ligne BD existante
$db_row = [
    'id' => 1,
    'account_number' => '37220020391',
    'customer_name' => 'John Doe',
    'mobile' => '1234567890',
    'email' => 'john@example.com',
    'created_at' => '2024-01-15 10:00:00'
];

// Enrichir avec les données Flexcube
$enriched = enrichRowWithFlexcube($db_row);

echo "Avant: status = " . ($db_row['status'] ?? 'N/A') . "\n";
echo "Après: status = " . ($enriched['status'] ?? 'N/A') . "\n";
echo "Données Flexcube incluses: " . (isset($enriched['flexcube_data']) ? 'Oui' : 'Non') . "\n";

echo "\n";

// ============================================
// EXEMPLE 8: Gestion Complète des Erreurs
// ============================================

echo "=== EXEMPLE 8: Gestion Complète des Erreurs ===\n";

function safe_fetch_account($account_number, $use_local_fallback = true) {
    try {
        // Essayer Flexcube
        $account = fetchAccountFromFlexcube($account_number);
        
        if ($account) {
            return [
                'success' => true,
                'source' => 'flexcube',
                'data' => $account
            ];
        }
        
        // Si pas trouvé et fallback activé
        if ($use_local_fallback) {
            global $conn;
            $result = fetchAccountWithFallback($account_number, $conn);
            
            if ($result['data']) {
                return [
                    'success' => true,
                    'source' => $result['source'],
                    'data' => $result['data']
                ];
            }
        }
        
        return [
            'success' => false,
            'source' => null,
            'data' => null,
            'error' => 'Compte non trouvé dans tous les systèmes'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'source' => null,
            'data' => null,
            'error' => $e->getMessage()
        ];
    }
}

// Utiliser la fonction sécurisée
$result = safe_fetch_account('37220020391');

if ($result['success']) {
    echo "✓ Succès ({$result['source']})\n";
} else {
    echo "✗ Erreur: {$result['error']}\n";
}

echo "\n";

// ============================================
// EXEMPLE 9: Dashboard de Status
// ============================================

echo "=== EXEMPLE 9: Dashboard de Status ===\n";

function flexcube_status_dashboard() {
    $api = getFlexcubeAPI();
    
    echo "=== FLEXCUBE STATUS ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    // Test de connexion
    $test = $api->testConnection();
    echo "Connexion: " . ($test['connected'] ? '✓ OK' : '✗ FAIL') . "\n";
    
    if (!$test['connected']) {
        echo "Message d'erreur: " . $test['details']['error'] . "\n";
    }
    
    echo "\n";
}

flexcube_status_dashboard();

// ============================================
// EXEMPLE 10: Mise en Cache Manuelle
// ============================================

echo "=== EXEMPLE 10: À Propos du Cache ===\n";

echo "Le cache automatique fonctionne pour:\n";
echo "- Durée: 1 heure par défaut\n";
echo "- Clé: md5(account_number)\n";
echo "- Stockage: Mémoire PHP (session)\n";
echo "\nNote: Le cache est réinitialisé à chaque requête HTTP.\n";
echo "Pour un cache persistent, utiliser Redis ou Memcached.\n";

?>
