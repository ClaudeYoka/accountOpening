<?php
include('../includes/session.php');
include('../includes/config.php');

// Vérifier si l'utilisateur est connecté et a les droits d'accès
if (!isset($_SESSION['alogin']) || $_SESSION['arole'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Rediriger vers le dashboard Vue d'ensemble Grafana
$grafana_url = 'http://localhost:3000/d/account-opening-overview?orgId=1&kiosk=1';
header('Location: ' . $grafana_url);
exit();
?>