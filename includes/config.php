<?php

// Charger les variables d'environnement depuis .env
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    if ($env) {
        if (!defined('DB_HOST')) define('DB_HOST', $env['DB_HOST'] ?? '');
        if (!defined('DB_USER')) define('DB_USER', $env['DB_USER'] ?? '');
        if (!defined('DB_PASS')) define('DB_PASS', $env['DB_PASS'] ?? '');
        if (!defined('DB_NAME')) define('DB_NAME', $env['DB_NAME'] ?? '');
    } else {
        die('Erreur de chargement du fichier .env');
    }
} else {
    die('Fichier .env manquant. Veuillez créer .env avec les configurations.');
}

// Inclure le gestionnaire d'erreurs
require_once __DIR__ . '/error_handler.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    error_log('MySQLi connection failed: ' . mysqli_connect_error());
}

// Establish database connection.
try
{
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME,DB_USER, DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
    $dbh = null;
    error_log('PDO connection failed: ' . $e->getMessage());
}

// Safe htmlentities helper: never pass null to htmlentities (prevents PHP deprecation warnings)
if (!function_exists('h')) {
    function h($s) {
        return htmlentities($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

?>

