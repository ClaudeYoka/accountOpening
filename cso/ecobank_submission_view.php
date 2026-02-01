<?php
// ecobank_submission_view.php
include('../includes/session.php');
include('../includes/config.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<div style='padding:20px;color:#900'>ID invalide.</div>";
    exit;
}

// Fetch submission
$stmt = mysqli_prepare($conn, "SELECT * FROM ecobank_form_submissions WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo "<div style='padding:20px;color:#900'>Erreur base de données: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    echo "<div style='padding:20px;color:#900'>Enregistrement introuvable (ID: " . htmlspecialchars($id) . ").</div>";
    exit;
}

// decode data
$data = [];
if (!empty($row['data'])) {
    $dec = json_decode($row['data'], true);
    if ($dec !== null) $data = $dec;
}

// try to find linked tblCompte (by email or mobile)
$linkedCompte = null;
if (!empty($row['email'])) {
    $q = mysqli_prepare($conn, "SELECT id FROM tblCompte WHERE email = ? LIMIT 1");
    if ($q) { mysqli_stmt_bind_param($q, 's', $row['email']); mysqli_stmt_execute($q); mysqli_stmt_bind_result($q, $linkedId); mysqli_stmt_fetch($q); if ($linkedId) $linkedCompte = (int)$linkedId; mysqli_stmt_close($q); }
}
if (!$linkedCompte && !empty($row['mobile'])) {
    $q = mysqli_prepare($conn, "SELECT id FROM tblCompte WHERE mobile1 = ? OR mobile2 = ? LIMIT 1");
    if ($q) { mysqli_stmt_bind_param($q, 'ss', $row['mobile'], $row['mobile']); mysqli_stmt_execute($q); mysqli_stmt_bind_result($q, $linkedId); mysqli_stmt_fetch($q); if ($linkedId) $linkedCompte = (int)$linkedId; mysqli_stmt_close($q); }
}

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visualisation du dossier Ecobank #<?php echo htmlspecialchars($row['id']); ?></title>
    <link rel="stylesheet" href="../vendors/styles/account_form.css">
    <style>
        .detail-table { width:100%; border-collapse: collapse; }
        .detail-table th, .detail-table td { text-align:left; padding:8px; border-bottom:1px solid #eee; }
        .kv { width: 30%; font-weight:600; }
        .pre-json { background:#f8f8f8; padding:10px; border:1px solid #eee; white-space:pre-wrap; font-family:monospace; }
    </style>
</head>
<body>
<?php include('includes/header.php'); ?>
<div class="container" style="padding:20px;">
    <h2>Dossier Ecobank #<?php echo htmlspecialchars($row['id']); ?></h2>
    <p>Enregistré le: <?php echo htmlspecialchars($row['created_at']); ?> &nbsp; | &nbsp; Customer ID: <?php echo htmlspecialchars($row['customer_id']); ?></p>

    <?php
    // Helper to pick first non-empty value from row columns or decoded data
    function pick_field($row, $data, $keys){
        foreach($keys as $k){
            if (!empty($row[$k])) return $row[$k];
            if (is_array($data) && isset($data[$k]) && $data[$k] !== '') return $data[$k];
        }
        return null;
    }

    $display = [
        'Account number' => pick_field($row, $data, ['account_number','customer_id','bank-account-number','bank_account_number','account-number']),
        'Customer name' => pick_field($row, $data, ['customer_name','full_name','noms','first-name','first_name','last-name','last_name','name']),
        'Email' => pick_field($row, $data, ['email','courriel','email_address']),
        'Mobile' => pick_field($row, $data, ['mobile','mobile1','mobile2','telephone','telephone1','telephone2','phone']),
        'Date of birth' => pick_field($row, $data, ['date_of_birth','date-of-birth','dob']),
        'Document number' => pick_field($row, $data, ['id_num','document-number','idnumber']),
        'Date issued' => pick_field($row, $data, ['document-issue-date','issue_date','issue-date','date_issued']),
        'Date expiry' => pick_field($row, $data, ['document-expiry-date','expiry_date','expiry-date','date_expiry']),
        'Employer' => pick_field($row, $data, ['employer-name','employer','employeur']),
        'Branch code' => pick_field($row, $data, ['branch-code','branch_code'])
    ];
    ?>

    <table class="detail-table">
    <?php foreach($display as $label => $val): ?>
        <tr><th class="kv"><?php echo htmlspecialchars($label); ?></th><td><?php echo htmlspecialchars($val !== null ? $val : ''); ?></td></tr>
    <?php endforeach; ?>
    </table>

    <?php
    // Show other fields (those not included above)
    $knownKeys = ['account_number','customer_id','bank-account-number','bank_account_number','account-number','customer_name','full_name','noms','first-name','first_name','last-name','last_name','name','email','courriel','email_address','mobile','mobile1','mobile2','telephone','telephone1','telephone2','phone','date_of_birth','date-of-birth','dob','id_num','document-number','idnumber','document-issue-date','issue_date','issue-date','date_issued','document-expiry-date','expiry_date','expiry-date','date_expiry','employer-name','employer','employeur','branch-code','branch_code'];
    $other = [];
    if (is_array($data)){
        foreach($data as $k => $v){
            if (in_array($k, $knownKeys)) continue;
            $other[$k] = $v;
        }
    }
    if (!empty($other)){
        echo '<h3 style="margin-top:18px;">Autres champs</h3>';
        echo '<table class="detail-table">';
        foreach($other as $k => $v){
            if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            echo '<tr><th class="kv">'.htmlspecialchars($k).'</th><td>'.htmlspecialchars((string)$v).'</td></tr>';
        }
        echo '</table>';
    }
    ?>

    <?php if ($linkedCompte): ?>
        <p style="margin-top:12px;">Compte lié trouvé: <a class="btn btn-sm btn-primary" href="view_compte.php?id=<?php echo $linkedCompte; ?>">Voir le compte #<?php echo $linkedCompte; ?></a></p>
    <?php endif; ?>

    <p style="margin-top:20px;"><a class="btn" href="ecobank_submissions_list.php">Retour à la liste</a> &nbsp; <a class="btn" href="ecobank_submission_edit.php?id=<?php echo $row['id']; ?>">Éditer (si autorisé)</a></p>
</div>
<?php include('includes/scripts.php'); ?>
</body>
</html>