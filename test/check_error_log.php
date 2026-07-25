<?php
header('Content-Type: text/plain; charset=utf-8');

$log_file = __DIR__ . '/logs/error.log';

if (!file_exists($log_file)) {
    echo "Fichier error.log non trouvé à: $log_file";
    exit;
}

// Lire les 100 dernières lignes
$lines = [];
$fp = fopen($log_file, 'r');
if ($fp) {
    while (!feof($fp)) {
        $line = fgets($fp);
        if ($line !== false) {
            $lines[] = trim($line);
        }
    }
    fclose($fp);
}

// Filtrer les logs pertinents
$relevant = array_filter($lines, function($line) {
    return  stripos($line, 'fetch_account_flexcube') !== false ||
            stripos($line, 'Flexcube') !== false ||
            stripos($line, 'OCI8') !== false ||
            stripos($line, 'Error') !== false ||
            stripos($line, 'Exception') !== false;
});

echo "=== DERNIERS LOGS PERTINENTS ===\n\n";
$relevant = array_slice($relevant, -50);  // Derniers 50

foreach ($relevant as $line) {
    echo $line . "\n";
}

if (empty($relevant)) {
    echo "Aucun log pertinent trouvé.\n";
    echo "\nDernières lignes du fichier:\n";
    $last = array_slice($lines, -20);
    foreach ($last as $line) {
        echo $line . "\n";
    }
}
?>
