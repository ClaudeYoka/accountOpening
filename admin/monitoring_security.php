<?php
include('../includes/session.php');
include('../includes/config.php');

// Vérifier si l'utilisateur est connecté et a les droits d'accès
if (!isset($_SESSION['alogin']) || $_SESSION['arole'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Rediriger vers le dashboard Sécurité Grafana
$grafana_url = 'http://localhost:3000/d/security-dashboard?orgId=1&kiosk=1';
header('Location: ' . $grafana_url);
exit();
?>