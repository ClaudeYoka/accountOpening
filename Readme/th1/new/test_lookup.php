<?php
// Test direct de rib_lookup.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include('rib_lookup.php');
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage();
}
?>
