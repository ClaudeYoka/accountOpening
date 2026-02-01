<?php

if (!defined('DB_HOST')) {
    define('DB_HOST','localhost');
    define('DB_USER','admin');
    define('DB_PASS','Passw@rd');
    define('DB_NAME','accountopening_db');
}

$conn = mysqli_connect('localhost','admin','Passw@rd','accountopening_db');

// Establish database connection.
try
{
$dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME,DB_USER, DB_PASS,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
}
catch (PDOException $e)
{
exit("Error: " . $e->getMessage());
}

// Safe htmlentities helper: never pass null to htmlentities (prevents PHP deprecation warnings)
if (!function_exists('h')) {
    function h($s) {
        return htmlentities($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

?>

