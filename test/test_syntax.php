<?php
// Test simple pour vérifier les modifications
echo "Test PHP Syntax Check\n";
echo "=====================\n\n";

// Vérifier les modifications manuellement
$files_to_check = [
    'demande_chequier.php' => [
        'contains' => ['if (!isset($_SESSION[\'emp_id\'])', 'exit(\'Authentification requise\')'],
        'name' => 'Authentification ajoutée'
    ],
    'demande_chequier_directe.php' => [
        'contains' => ['if (!isset($_SESSION[\'emp_id\'])', 'id="flexcube_search"', 'fetch_account_flexcube.php'],
        'name' => 'Recherche Flexcube et authentification'
    ]
];

foreach ($files_to_check as $file => $checks) {
    echo "Vérification de $file: " . $checks['name'] . "\n";
    $content = file_get_contents($file);
    
    if (!$content) {
        echo "  ✗ Fichier non trouvé\n";
        continue;
    }
    
    $all_found = true;
    foreach ($checks['contains'] as $search) {
        if (strpos($content, $search) !== false) {
            echo "  ✓ Trouvé: " . substr($search, 0, 40) . "...\n";
        } else {
            echo "  ✗ Non trouvé: " . substr($search, 0, 40) . "...\n";
            $all_found = false;
        }
    }
    
    echo "\n";
}

echo "Vérification terminée\n";
?>

