<?php
header('Content-Type: application/json; charset=utf-8');

include('../includes/config.php');
include('../includes/session.php');

echo json_encode([
    'full_name' => $_SESSION['user_fullname'] ?? ''
], JSON_UNESCAPED_UNICODE);
