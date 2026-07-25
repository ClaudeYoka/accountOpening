<?php
/**
 * Diagnostic Script for PDO Errors
 * 
 * This script helps identify where the PDO error is coming from
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnostic - Recherche d'erreurs PDO</h1>";
echo "<pre>";

// Test 1: Check if OCI8 extension is loaded
echo "\n=== TEST 1: Extensions PHP ===\n";
echo "OCI8 chargé: " . (extension_loaded('oci8') ? "✓ OUI" : "✗ NON") . "\n";
echo "MySQLi chargé: " . (extension_loaded('mysqli') ? "✓ OUI" : "✗ NON") . "\n";
echo "PDO chargé: " . (extension_loaded('pdo') ? "✓ OUI" : "✗ NON") . "\n";
echo "PDO MySQL chargé: " . (extension_loaded('pdo_mysql') ? "✓ OUI" : "✗ NON") . "\n";

// Test 2: Check if .env file exists
echo "\n=== TEST 2: Fichier .env ===\n";
$env_path = __DIR__ . '/.env';
echo ".env existe: " . (file_exists($env_path) ? "✓ OUI" : "✗ NON") . "\n";

if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    echo "DB_HOST: " . ($env['DB_HOST'] ?? 'NOT SET') . "\n";
    echo "DB_USER: " . ($env['DB_USER'] ?? 'NOT SET') . "\n";
    echo "DB_NAME: " . ($env['DB_NAME'] ?? 'NOT SET') . "\n";
}

// Test 3: Try to check includes/config.php
echo "\n=== TEST 3: includes/config.php ===\n";
$config_path = __DIR__ . '/includes/config.php';
echo "config.php existe: " . (file_exists($config_path) ? "✓ OUI" : "✗ NON") . "\n";

if (file_exists($config_path)) {
    echo "Contenu des premières lignes:\n";
    $lines = file($config_path, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(10, count($lines)); $i++) {
        echo "  " . ($i+1) . ": " . $lines[$i] . "\n";
    }
}

// Test 4: Check flexcube_helpers.php
echo "\n=== TEST 4: flexcube_helpers.php ===\n";
$helper_path = __DIR__ . '/cso/includes/flexcube_helpers.php';
echo "flexcube_helpers.php existe: " . (file_exists($helper_path) ? "✓ OUI" : "✗ NON") . "\n";

if (file_exists($helper_path)) {
    echo "Contenu des 20 premières lignes:\n";
    $lines = file($helper_path, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        echo "  " . ($i+1) . ": " . substr($lines[$i], 0, 80) . "\n";
    }
}

// Test 5: List recent errors from error log
echo "\n=== TEST 5: Dernières erreurs PHP ===\n";
$error_log = ini_get('error_log');
echo "Fichier error.log: " . $error_log . "\n";

if ($error_log && file_exists($error_log)) {
    echo "Dernières 10 lignes:\n";
    $lines = array_slice(file($error_log), -10);
    foreach ($lines as $line) {
        echo "  " . trim($line) . "\n";
    }
} else {
    echo "  (fichier error.log non accessible)\n";
}

// Test 6: Try including config.php
echo "\n=== TEST 6: Tentative d'inclusion de config.php ===\n";
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "ERREUR PHP [{$errno}]: {$errstr} in {$errfile}:{$errline}\n";
    return true;
});

ob_start();
try {
    include_once(__DIR__ . '/includes/config.php');
    $output = ob_get_clean();
    if (!empty($output)) {
        echo "Sortie lors de l'inclusion: \n{$output}\n";
    } else {
        echo "Inclusion réussie, pas d'erreur\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "Exception lors de l'inclusion: " . $e->getMessage() . "\n";
}

restore_error_handler();

echo "\n=== FIN DIAGNOSTIC ===\n";
echo "</pre>";
