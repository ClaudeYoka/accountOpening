<?php
include __DIR__ . '/../includes/config.php';
$r = mysqli_query($conn, "SELECT id, account_number, bank_account_number, data FROM ecobank_form_submissions WHERE account_number IS NOT NULL AND account_number <> '' LIMIT 10");
$out = [];
while($row = mysqli_fetch_assoc($r)) $out[] = $row;
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
?>