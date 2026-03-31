<?php
// Configuration d'erreur globale
error_reporting(E_ALL);
ini_set('display_errors', '0');  // Ne PAS afficher aux utilisateurs
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Créer répertoire logs s'il n'existe pas
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0700, true);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $message = "[$errno] $errstr in $errfile:$errline";
    error_log($message);

    // En développement, afficher les erreurs (modifier pour production)
    if (ini_get('display_errors')) {
        echo "Erreur: $errstr";
    }
});

// Custom exception handler
set_exception_handler(function($exception) {
    $message = "Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine();
    error_log($message);

    if (ini_get('display_errors')) {
        echo "Exception: " . $exception->getMessage();
    } else {
        echo "Une erreur est survenue. Veuillez réessayer.";
    }
});
?>