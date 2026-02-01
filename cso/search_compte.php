<?php
// Endpoint to search for a compte by account number or other identifiers
// Returns JSON
include('../includes/config.php');
include('../includes/session.php');

header('Content-Type: application/json; charset=utf-8');

// Accept q from POST (preferred) or GET. Trim whitespace.
$term = null;
if (isset($_POST['q'])) {
    $term = trim($_POST['q']);
} elseif (isset($_GET['q'])) {
    $term = trim($_GET['q']);
}
if (!$term) {
    echo json_encode(['status' => 'error', 'message' => 'Aucun numéro fourni']);
    exit;
}

// Allow searching by account_number in addition to id, id_num and nip
$columns = ['id','account_number','id_num','nip'];
$intColumns = ['id']; // treat as integers when the search term is numeric
$found = false;
$response = ['status' => 'not_found', 'message' => "Compte n'existe pas ou  pas encore enregistré dans IBPS."];

foreach ($columns as $col) {
    $sql = "SELECT * FROM tblCompte WHERE $col = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Choose bind type according to column and input
        if (in_array($col, $intColumns, true) && ctype_digit($term)) {
            $bindType = 'i';
            $param = (int)$term;
        } else {
            $bindType = 's';
            // sanitize string input minimally; prepared statements will protect SQL injection
            $param = filter_var($term, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        mysqli_stmt_bind_param($stmt, $bindType, $param);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $found = true;
            // Return the row as JSON
            $response = ['status' => 'ok', 'data' => $row];
            mysqli_stmt_close($stmt);
            break;
        }
        mysqli_stmt_close($stmt);
    }
}

echo json_encode($response);
exit;
?>