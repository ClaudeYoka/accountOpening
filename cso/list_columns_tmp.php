<?php
include __DIR__ . '/../includes/config.php';
$r = mysqli_query($conn, "SHOW COLUMNS FROM ecobank_form_submissions");
if(!$r) { echo "Query failed: " . mysqli_error($conn) . "\n"; exit(1); }
$cols = [];
while($row = mysqli_fetch_assoc($r)) { $cols[] = $row['Field']; }
echo json_encode($cols, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
?>