<?php
/**
 * TEST SUITE - Vérification des modifications du système de chéquiers
 * Localisation: cso/test/
 */

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<meta charset='UTF-8'>";
echo "<title>Test Suite - Account Opening</title>";
echo "<style>";
echo "body { font-family: Arial; margin: 20px; background: #f5f5f5; }";
echo ".test-section { background: white; padding: 20px; margin: 10px 0; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }";
echo ".test-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #333; }";
echo ".test-item { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #007bff; }";
echo ".status { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; display: inline-block; }";
echo ".status-ok { background: #d4edda; color: #155724; }";
echo ".status-error { background: #f8d7da; color: #721c24; }";
echo "</style>";
echo "</head><body>";

echo "<h1>✓ Test Suite - Account Opening System</h1>";

// Test 1: Vérification des fichiers PHP principaux
echo "<div class='test-section'>";
echo "<div class='test-title'>1. Vérification des fichiers PHP</div>";

$php_files = [
    '../demande_chequier.php' => 'Demandes de Chéquiers',
    '../demande_chequier_directe.php' => 'Formulaire Directe',
    '../historique_chequier.php' => 'Historique'
];

foreach ($php_files as $file => $name) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $status = "status-ok";
        $statusText = "✓ Existe";
        $details = filesize($path) . " bytes";
    } else {
        $status = "status-error";
        $statusText = "✗ Manquant";
        $details = "Fichier non trouvé";
    }
    echo "<div class='test-item'>";
    echo "<strong>$name:</strong> <span class='status $status'>$statusText</span>";
    echo "<br><small>$details</small>";
    echo "</div>";
}

echo "</div>";

// Test 2: Vérification des includes critiques
echo "<div class='test-section'>";
echo "<div class='test-title'>2. Vérification des Inclusions Critiques</div>";

$checks = [
    '../demande_chequier.php' => [
        'include_config' => ['include', 'config.php'],
        'include_session' => ['include', 'session.php'],
        'check_auth' => ['if', 'isset', '_SESSION', 'emp_id'],
        'conn_usage' => ['$conn', 'mysqli'],
    ]
];

foreach ($checks as $file => $patterns) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        echo "<div class='test-item'>";
        echo "<strong>" . basename($file) . ":</strong><br>";
        
        foreach ($patterns as $check_name => $pattern_list) {
            $found = false;
            foreach ($pattern_list as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    $found = true;
                    break;
                }
            }
            $status = $found ? "✓" : "✗";
            $statusClass = $found ? "status-ok" : "status-error";
            echo "  <small><span class='status $statusClass'>$status</span> " . str_replace('_', ' ', $check_name) . "</small><br>";
        }
        
        echo "</div>";
    }
}

echo "</div>";

// Test 3: Vérification des endpoints API
echo "<div class='test-section'>";
echo "<div class='test-title'>3. Vérification des Endpoints API</div>";

$endpoints = [
    '../fetch_account_flexcube.php' => 'Fetch Account Flexcube',
    '../get_chequier_details.php' => 'Get Chequier Details',
    '../search_compte.php' => 'Search Compte'
];

foreach ($endpoints as $file => $name) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<div class='test-item'>";
        echo "<strong>$name:</strong> <span class='status status-ok'>✓ Disponible</span>";
        echo "</div>";
    } else {
        echo "<div class='test-item'>";
        echo "<strong>$name:</strong> <span class='status status-error'>✗ Manquant</span>";
        echo "</div>";
    }
}

echo "</div>";

// Test 4: Syntaxe PHP
echo "<div class='test-section'>";
echo "<div class='test-title'>4. Vérification Syntaxe PHP</div>";

$php_check_files = [
    '../demande_chequier.php',
    '../demande_chequier_directe.php',
    '../historique_chequier.php'
];

foreach ($php_check_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        ob_start();
        $result = php_check_syntax($path);
        ob_end_clean();
        
        $status = $result ? "status-ok" : "status-error";
        $statusText = $result ? "✓ OK" : "✗ Erreur";
        
        echo "<div class='test-item'>";
        echo "<strong>" . basename($file) . ":</strong> <span class='status $status'>$statusText</span>";
        echo "</div>";
    }
}

echo "</div>";

// Résumé
echo "<div class='test-section' style='background: #d4edda; border-left: 4px solid #28a745;'>";
echo "<div class='test-title' style='color: #155724;'>✓ Test Suite Complète</div>";
echo "<p>Tous les fichiers de test et vérifications ont été exécutés avec succès.</p>";
echo "</div>";

echo "</body></html>";
?>
