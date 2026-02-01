<?php
// Endpoint to save tutor account form submissions
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Read JSON input
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    // fallback to form-encoded
    $data = $_POST;
}

if (!$data || !is_array($data)) {
    echo json_encode(['success'=>false, 'error'=>'Invalid input']);
    exit;
}

// Basic sanitization: keep the JSON as is but ensure it's valid UTF-8
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
if ($json === false) {
    echo json_encode(['success'=>false, 'error'=>'Unable to encode submission']);
    exit;
}

// Insert into DB
$escaped = mysqli_real_escape_string($conn, $json);
$sql = "INSERT INTO tutor_account_submissions (submission) VALUES ('$escaped')";
if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode(['success'=>true, 'id'=>$id]);
} else {
    echo json_encode(['success'=>false, 'error'=>mysqli_error($conn)]);
}
