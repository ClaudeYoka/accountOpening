<?php
// ecobank_submission_edit.php
include('../includes/session.php');
include('../includes/config.php');

function debug_log_local($msg){
    $path = __DIR__ . '/../logs/save_ecobank_form_debug.log';
    $line = '['.date('c').'] ' . $msg . PHP_EOL;
    error_log('[ecobank_submission_edit] ' . $msg);
    @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
}

function normalize_date_for_mysql($s){
    if ($s === null || $s === '') return null;
    $s = trim($s);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
    $formats = ['d/m/Y','d-m-Y','d/m/y','d-m-y','Y/m/d','Y-m-d','m/d/Y','m-d-Y'];
    foreach($formats as $fmt){
        $dt = DateTime::createFromFormat($fmt, $s);
        if ($dt && $dt->format($fmt) === $s) return $dt->format('Y-m-d');
    }
    $ts = strtotime($s);
    if ($ts !== false) return date('Y-m-d', $ts);
    return null;
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if ($id <= 0) {
    echo "<div style='padding:20px;color:#900'>ID invalide.</div>";
    exit;
}

// fetch submission
$stmt = mysqli_prepare($conn, "SELECT * FROM ecobank_form_submissions WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);
if (!$row) { echo "<div style='padding:20px;color:#900'>Enregistrement introuvable.</div>"; exit; }

// decode data snapshot
$data = [];
if (!empty($row['data'])) {
    $dec = json_decode($row['data'], true);
    if ($dec !== null) $data = $dec;
}

// fetch existing columns to know what can be updated
$existing = [];
$schema = defined('DB_NAME') ? DB_NAME : null;
if ($schema) {
    $esc = mysqli_real_escape_string($conn, $schema);
    $resCols = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$esc."' AND TABLE_NAME='ecobank_form_submissions'");
    if ($resCols) {
        while($r = mysqli_fetch_assoc($resCols)) $existing[] = $r['COLUMN_NAME'];
        mysqli_free_result($resCols);
    }
}

$editable = [
    'account_number','bank_account_number','customer_name','first_name','middle_name','last_name','date_of_birth','id_number','id_issue_date','id_expiry_date','nationality','residence_country','telephone','telephone2','mobile','email','employer_name','branch_code','account_officer','approved_by','account_purpose','currency','services'
];

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    // collect posted values
    $candidates = [];
    foreach($editable as $k){
        $val = isset($_POST[$k]) ? trim($_POST[$k]) : null;
        // normalize dates
        if (in_array($k, ['date_of_birth','id_issue_date','id_expiry_date'])) $val = normalize_date_for_mysql($val);
        // services may be submitted as textarea with JSON or comma list; normalize to JSON string
        if ($k === 'services' && $val !== null && $val !== '') {
            // if JSON array string, keep as is; if comma separated, convert to json
            $maybe = json_decode($val, true);
            if ($maybe === null) {
                $parts = array_map('trim', explode(',', $val));
                $val = json_encode(array_values(array_filter($parts)), JSON_UNESCAPED_UNICODE);
            } else {
                $val = json_encode($maybe, JSON_UNESCAPED_UNICODE);
            }
        }
        $candidates[$k] = ($val === '') ? null : $val;
    }

    // build update only for columns that exist and with non-null values (allow explicit empty -> null)
    $sets = [];
    $vals = [];
    foreach ($candidates as $k => $v) {
        if (!in_array($k, $existing)) continue;
        // include even if null to allow clearing
        $sets[] = "`$k` = ?";
        $vals[] = $v;
    }

    if (!empty($sets)) {
        $sql = "UPDATE ecobank_form_submissions SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            // build types and bind
            $types = '';
            foreach ($vals as $v) $types .= 's';
            $types .= 'i';
            $bind = array_merge([$types], $vals, [$id]);
            $tmp = [];
            foreach ($bind as $k => $v) $tmp[$k] = &$bind[$k];
            call_user_func_array([$stmt, 'bind_param'], $tmp);
            $ok = mysqli_stmt_execute($stmt);
            if ($ok) $messages[] = 'Mise à jour enregistrée.';
            else $messages[] = 'Erreur lors de la mise à jour: ' . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $messages[] = 'Préparation de la mise à jour impossible: ' . mysqli_error($conn);
        }
    } else {
        $messages[] = 'Aucun champ modifiable présent dans la table.';
    }

    // update JSON snapshot to reflect new values
    $updatedData = $data;
    foreach ($candidates as $k => $v) {
        // store null as empty string or remove? we will set null => remove key
        if ($v === null) {
            if (isset($updatedData[$k])) unset($updatedData[$k]);
        } else {
            $updatedData[$k] = $v;
        }
    }
    $jsonData = json_encode($updatedData, JSON_UNESCAPED_UNICODE);
    if ($jsonData !== false) {
        $q = mysqli_prepare($conn, "UPDATE ecobank_form_submissions SET data = ? WHERE id = ?");
        if ($q) { mysqli_stmt_bind_param($q, 'si', $jsonData, $id); mysqli_stmt_execute($q); mysqli_stmt_close($q); }
    }

    // refetch row to show updated values
    $stmt = mysqli_prepare($conn, "SELECT * FROM ecobank_form_submissions WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : $row;
    mysqli_stmt_close($stmt);
    // refresh decoded data
    $data = [];
    if (!empty($row['data'])) { $dec = json_decode($row['data'], true); if ($dec !== null) $data = $dec; }
}

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Éditer soumission Ecobank #<?php echo (int)$row['id']; ?></title>
    <link rel="stylesheet" href="../vendors/styles/account_form.css">
</head>
<body>
<?php include('includes/header.php'); ?>
<div class="container" style="padding:20px;">
    <h2>Éditer soumission #<?php echo (int)$row['id']; ?></h2>
    <p>Enregistré le: <?php echo htmlspecialchars($row['created_at']); ?> &nbsp; | &nbsp; Customer ID: <?php echo htmlspecialchars($row['customer_id']); ?></p>

    <?php if (!empty($messages)): ?>
        <div class="alert alert-info">
            <?php foreach($messages as $m) echo '<div>'.htmlspecialchars($m).'</div>'; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php echo get_csrf_field(); ?>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <?php foreach($editable as $field):
                $val = isset($row[$field]) && $row[$field] !== null ? $row[$field] : (isset($data[$field]) ? $data[$field] : '');
                $label = ucwords(str_replace(['_','-'],' ',$field));
                ?>
                <div>
                    <label for="<?php echo $field; ?>"><?php echo htmlspecialchars($label); ?></label>
                    <?php if (in_array($field, ['services'])): ?>
                        <textarea id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control" rows="3"><?php echo htmlspecialchars(is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val); ?></textarea>
                    <?php elseif (in_array($field, ['date_of_birth','id_issue_date','id_expiry_date'])): ?>
                        <input type="date" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                    <?php else: ?>
                        <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:12px;">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn" href="ecobank_submission_view?id=<?php echo (int)$row['id']; ?>">Annuler</a>
        </p>
    </form>

    <!-- Snapshot JSON display removed for privacy. If required for debugging, show to admins only or retrieve server-side. -->

</div>
<?php include('includes/scripts.php'); ?>
</body>
</html>
