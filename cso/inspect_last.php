<?php
include __DIR__ . '/../includes/config.php';
$r = mysqli_query($conn, "SELECT id, account_type, data FROM ecobank_form_submissions ORDER BY created_at DESC LIMIT 1");
if(!$r) { echo "Query failed: " . mysqli_error($conn) . "\n"; exit(1); }
$row = mysqli_fetch_assoc($r);
echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>