<?php
// Endpoint to save tutor account form submissions
ob_start();
require_once __DIR__ . '/../includes/config.php';

// Ensure output is JSON, même si un warning a déjà été envoyé.
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

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

try {
    // Construire la liste des colonnes à insérer
$columns_in_table = [];
$result = mysqli_query($conn, 'SHOW COLUMNS FROM tutor_account_submissions');
while ($row = mysqli_fetch_assoc($result)) {
    if (in_array($row['Field'], ['id', 'created_at'])) {
        continue;
    }
    $columns_in_table[] = $row['Field'];
}

// Valeurs globales à partir des données du formulaire
$input = [];
foreach ($columns_in_table as $column) {
    if (array_key_exists($column, $data)) {
        $fieldValue = $data[$column];
        if ($column === 'genre' && is_array($fieldValue)) {
            $normalizedArray = array_map(function($v) {
                $v = trim((string)$v);
                if ($v === '♂' || strtolower($v) === 'homme') return 'Homme';
                if ($v === '♀' || strtolower($v) === 'femme') return 'Femme';
                return $v;
            }, $fieldValue);
            $input[$column] = $normalizedArray[0] ?? '';
        } elseif (is_array($fieldValue)) {
            $normalizedArray = array_map(function($v) {
                return trim((string)$v);
            }, $fieldValue);
            $input[$column] = implode(', ', array_filter($normalizedArray, function($v){return $v !== ''; }));
        } else {
            $input[$column] = $fieldValue;
        }
        continue;
    }

    // aliases possibles / normalisations
    $aliases = [
        'noms' => ['noms', 'nom', 'nom_s'],
        'prenoms' => ['prenoms', 'prenom', 'prenom_s'],
        'autresprenoms' => ['autresprenoms', 'autres_prenoms'],
        'lieudenaissance' => ['lieudenaissance', 'lieu_de_naissance'],
        'pays' => ['pays'],
        'datedenaissance' => ['datedenaissance', 'date_de_naissance'],
        'situationmatrimoniale' => ['situationmatrimoniale', 'situation_matrimoniale'],
        'typedepiece' => ['typedepiece', 'type_de_piece'],
        'npiece' => ['npiece', 'npiece'],
        'nidentificationfiscale' => ['nidentificationfiscale', 'n_identification_fiscale'],
        'datededelivrance' => ['datededelivrance', 'date_delivrance'],
        'dateexpiration' => ['dateexpiration', 'date_expiration'],
        'numero_de_referenceoptionnel' => ['numero_de_referenceoptionnel', 'numero_de_reference_optionnel'],
        'deuxiemetelephone' => ['deuxiemetelephone', 'deuxieme_telephone'],
        'adressemail' => ['adressemail', 'adresse_mail'],
        'nationalite_1' => ['nationalite_1', 'nationalite_cordonnee'],
        'ville_1' => ['ville_1'],
        'adresselegale' => ['adresselegale'],
        'ville_2' => ['ville_2'],
        'pays_2' => ['pays_2'],
        'nom_employeur' => ['nom_employeur', 'nomdelemployeur', 'nom_de_l_employeur'],
        'nomdelemployeur' => ['nomdelemployeur', 'nom_employeur', 'nom_de_l_employeur'],
        'nom_entreprise' => ['nom_entreprise', 'nomentreprise'],
        'type_contrat' => ['type_contrat', 'typecontrat'],
        'typecontrat' => ['typecontrat', 'type_contrat', 'type_de_contrat'],
        'estimation_revenu_salarie' => ['estimation_revenu_salarie', 'estimationrevenusalarie', 'estimation_revenu_mensuel', 'estimationrevenumensuel'],
        'estimation_revenu_entreprise' => ['estimation_revenu_entreprise', 'estimationrevenuentreprise', 'estimation_revenu_mensuel', 'estimationrevenumensuel'],
        'estimation_revenu_etudiante' => ['estimation_revenu_etudiante', 'estimationrevenuetudiante', 'estimation_revenu_mensuel', 'estimationrevenumensuel'],
        'estimationrevenumensuel' => ['estimationrevenumensuel', 'estimation_revenu_mensuel'],
        'nom_instruction' => ['nom_instruction', 'nomdelinstruction', 'nom_de_l_instruction'],
        'nomdelinstruction' => ['nomdelinstruction', 'nom_instruction', 'nom_de_l_instruction'],
        'carte_etudiant' => ['carte_etudiant', 'ncarteetudiant'],
        'ncarteetudiant' => ['ncarteetudiant', 'carte_etudiant', 'n_carte_etudiant'],
        'autres' => ['autres', 'autres_1'],
        'autres_1' => ['autres_1', 'autres'],
        'ncarteetudiant' => ['ncarteetudiant', 'n_carte_etudiant'],
        'autres_1' => ['autres_1', 'autres'],
        'contact_name' => ['contact_name'],
        'contact_address' => ['contact_address'],
        'contact_relationship' => ['contact_relationship'],
        'contact_phone' => ['contact_phone'],
        'contact_email' => ['contact_email'],
        'contact_income_source' => ['contact_income_source'],
        'has_other_bank_account' => ['has_other_bank_account'],
        'other_bank_details' => ['other_bank_details'],
        'other_bank_person_name' => ['other_bank_person_name'],
        'other_bank_person_address' => ['other_bank_person_address'],
        'salarie' => ['salarie'],
        'entreprise' => ['entreprise'],
        'etudiante' => ['etudiante']
    ];

    $value = null;
    if (isset($aliases[$column])) {
        foreach ($aliases[$column] as $alias) {
            if (array_key_exists($alias, $data)) {
                $value = $data[$alias];
                break;
            }
        }
    }

    if (is_array($value)) {
        // Nettoyer/normaliser le tableau s'il existe
        $normalizedArray = array_map(function($v){
            $v = trim((string)$v);
            if ($v === '♂' || strtolower($v) === 'homme') return 'Homme';
            if ($v === '♀' || strtolower($v) === 'femme') return 'Femme';
            return $v;
        }, $value);

        // Cas spécifique genre : garder 1 valeur simple
        if ($column === 'genre') {
            $value = $normalizedArray[0] ?? '';
        } else {
            $value = implode(', ', array_filter($normalizedArray, function($v){ return $v !== ''; }));
        }
    } else {
        // journaliser possible conversion de genre non standard
        if ($column === 'genre') {
            $raw = trim((string)$value);
            if ($raw === '♂') $value = 'Homme';
            if ($raw === '♀') $value = 'Femme';
            if (strcasecmp($raw, 'homme') === 0) $value = 'Homme';
            if (strcasecmp($raw, 'femme') === 0) $value = 'Femme';
        }
    }

    $input[$column] = $value;
}

// Type de colonne : mise en place pour int/boolean/date
$int_columns = ['salarie','entreprise','etudiante','has_other_bank_account'];
$date_columns = ['date_naissance','datedenaissance','date_delivrance','datededelivrance','dateexpiration','date_expiration'];
$insert_columns = [];
$placeholders = [];
$values = [];
$types = '';
foreach ($input as $col => $val) {
    $insert_columns[] = $col;

    if (in_array($col, $date_columns)) {
        $normalized = trim((string)$val);
        if ($normalized === '' || $normalized === '0000-00-00') {
            $placeholders[] = 'NULL';
            continue;
        }

        $ts = strtotime($normalized);
        if ($ts === false) {
            // Valeur date invalide -> null
            $placeholders[] = 'NULL';
            continue;
        }

        $placeholders[] = '?';
        $types .= 's';
        $values[] = date('Y-m-d', $ts);
        continue;
    }

    if (in_array($col, $int_columns)) {
        $placeholders[] = '?';
        $types .= 'i';
        $values[] = (int)($val ? $val : 0);
        continue;
    }

    $placeholders[] = '?';
    $types .= 's';
    $values[] = $val === null ? '' : (string)$val;
}

$sql = "INSERT INTO tutor_account_submissions (" . implode(',', $insert_columns) . ", created_at) VALUES (" . implode(',', $placeholders) . ", NOW())";
$stmt = mysqli_prepare($conn, $sql);

// Préparer bind_param dynamique (s'il y a des paramètres)
if (strlen($types) > 0) {
    $params = [];
    $params[] = &$types;
    foreach ($values as $key => $value) {
        $params[] = &$values[$key];
    }

    call_user_func_array([$stmt, 'bind_param'], $params);
}

if (mysqli_stmt_execute($stmt)) {
    $id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    // Supprime tout output intermédiaire pour garder JSON valide
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $error]);
}

} catch (Throwable $e) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
