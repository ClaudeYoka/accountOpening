<?php
// Endpoint to search for a compte by account number or other identifiers
// Returns JSON
include('../includes/config.php');
include('../includes/session.php');
include('../includes/flexcube_helpers.php');

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

// First try Flexcube API
$flexcube_result = fetchAccountFromFlexcube($term);
if ($flexcube_result) {
    echo json_encode([
        'status' => 'ok',
        'source' => 'flexcube',
        'data' => $flexcube_result
    ]);
    exit;
}

// Fallback to local database (ecobank_form_submissions)
$db_result = fetchAccountWithFallback($term, $conn);
if ($db_result && isset($db_result['data']) && $db_result['data']) {
    echo json_encode([
        'status' => 'ok',
        'source' => $db_result['source'],
        'data' => $db_result['data']
    ]);
    exit;
}

// Try legacy tblCompte table as last resort
$columns = ['id','account_number','id_num','nip'];
$intColumns = ['id'];
$found = false;

foreach ($columns as $col) {
    $sql = "SELECT * FROM tblCompte WHERE $col = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (in_array($col, $intColumns, true) && ctype_digit($term)) {
            $bindType = 'i';
            $param = (int)$term;
        } else {
            $bindType = 's';
            $param = filter_var($term, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        mysqli_stmt_bind_param($stmt, $bindType, $param);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $found = true;
            echo json_encode(['status' => 'ok', 'source' => 'tblcompte', 'data' => $row]);
            mysqli_stmt_close($stmt);
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

// Not found anywhere
echo json_encode(['status' => 'not_found', 'message' => "Compte n'existe pas ou pas encore enregistré dans IBPS."]);
exit;
?>