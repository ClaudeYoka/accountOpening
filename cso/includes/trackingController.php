<?php
if (isset($_POST['apply'])) {
    // Récupérer l'ID employé
    $empid = $session_id; // ID de l'employé connecté

    // firstname peut provenir du formulaire ou être récupéré depuis la table des employés
    if (isset($_POST['firstname']) && $_POST['firstname'] !== '') {
        $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    } else {
        $qemp = mysqli_query($conn, "SELECT FirstName, LastName FROM tblemployees WHERE emp_id = '$session_id'");
        if ($qemp && $re = mysqli_fetch_assoc($qemp)) {
            $firstname = $re['FirstName'] . ' ' . $re['LastName'];
        } else {
            $firstname = '';
        }
    }

    // Lire tous les champs du formulaire (comme dans account_part.php)
    $services = isset($_POST['srv']) ? $_POST['srv'] : array();
    $type_compte = isset($_POST['type_compte']) ? $_POST['type_compte'] : array();
    $devise_pref = isset($_POST['devise_pref']) ? mysqli_real_escape_string($conn, $_POST['devise_pref']) : '';
    $objectif = isset($_POST['objectif']) ? mysqli_real_escape_string($conn, $_POST['objectif']) : '';
    $access = isset($_POST['access']) ? $_POST['access'] : array();
    $titre = isset($_POST['titre']) ? mysqli_real_escape_string($conn, $_POST['titre']) : '';
    $noms = isset($_POST['noms']) ? mysqli_real_escape_string($conn, $_POST['noms']) : '';
    $prenom2 = isset($_POST['prenom2']) ? mysqli_real_escape_string($conn, $_POST['prenom2']) : '';
    $nationalite = isset($_POST['nationalite']) ? mysqli_real_escape_string($conn, $_POST['nationalite']) : '';
    $lieu_naiss = isset($_POST['lieu_naiss']) ? mysqli_real_escape_string($conn, $_POST['lieu_naiss']) : '';
    $pays = isset($_POST['pays']) ? mysqli_real_escape_string($conn, $_POST['pays']) : '';
    $dob = isset($_POST['dob']) && !empty($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : NULL;
    $genre = isset($_POST['genre']) ? mysqli_real_escape_string($conn, $_POST['genre']) : '';
    $situation = isset($_POST['situation']) ? mysqli_real_escape_string($conn, $_POST['situation']) : '';
    $id_type = isset($_POST['id_type']) ? $_POST['id_type'] : array();
    $id_num = isset($_POST['id_num']) ? mysqli_real_escape_string($conn, $_POST['id_num']) : '';
    $date_deliv = isset($_POST['date_deliv']) && !empty($_POST['date_deliv']) ? mysqli_real_escape_string($conn, $_POST['date_deliv']) : NULL;
    $date_exp = isset($_POST['date_exp']) && !empty($_POST['date_exp']) ? mysqli_real_escape_string($conn, $_POST['date_exp']) : NULL;
    $fiscal_pays = isset($_POST['fiscal_pays']) ? mysqli_real_escape_string($conn, $_POST['fiscal_pays']) : '';
    $nip = isset($_POST['nip']) ? mysqli_real_escape_string($conn, $_POST['nip']) : '';
    $mobile1 = isset($_POST['mobile1']) ? mysqli_real_escape_string($conn, $_POST['mobile1']) : '';
    $mobile2 = isset($_POST['mobile2']) ? mysqli_real_escape_string($conn, $_POST['mobile2']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $adr_rue = isset($_POST['adr_rue']) ? mysqli_real_escape_string($conn, $_POST['adr_rue']) : '';
    $ville = isset($_POST['ville']) ? mysqli_real_escape_string($conn, $_POST['ville']) : '';
    $adr_pays = isset($_POST['adr_pays']) ? mysqli_real_escape_string($conn, $_POST['adr_pays']) : '';
    $employeur = isset($_POST['employeur']) ? mysqli_real_escape_string($conn, $_POST['employeur']) : '';
    $cond = isset($_POST['cond']) ? $_POST['cond'] : array();
    $revenu = isset($_POST['revenu']) ? mysqli_real_escape_string($conn, $_POST['revenu']) : '';
    $etabliss = isset($_POST['etabliss']) ? mysqli_real_escape_string($conn, $_POST['etabliss']) : '';
    $ident_etud = isset($_POST['ident_etud']) ? mysqli_real_escape_string($conn, $_POST['ident_etud']) : '';

    // Convertir tableaux en CSV pour stockage
    $services_str = is_array($services) ? implode(',', $services) : $services;
    $type_compte_str = is_array($type_compte) ? implode(',', $type_compte) : $type_compte;
    $access_str = is_array($access) ? implode(',', $access) : $access;
    $id_type_str = is_array($id_type) ? implode(',', $id_type) : $id_type;
    $cond_str = is_array($cond) ? implode(',', $cond) : $cond;

    // --- Validations serveur (option C) ---
    $errors = array();

    // Email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    // Téléphones (autorise + et chiffres, entre 6 et 15 chiffres)
    $phonePattern = '/^\+?[0-9]{6,15}$/';
    if (!empty($mobile1) && !preg_match($phonePattern, $mobile1)) {
        $errors[] = 'Numéro mobile principal invalide.';
    }
    if (!empty($mobile2) && !preg_match($phonePattern, $mobile2)) {
        $errors[] = 'Numéro mobile alternatif invalide.';
    }

    // Dates : s'assurer du bon format Y-m-d
    $validateDate = function ($d) {
        if ($d === null || $d === '') return true;
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    };
    if (!empty($dob) && !$validateDate($dob)) {
        $errors[] = 'Date de naissance invalide (format attendu YYYY-MM-DD).';
    }
    if (!empty($date_deliv) && !$validateDate($date_deliv)) {
        $errors[] = 'Date de délivrance invalide (format attendu YYYY-MM-DD).';
    }
    if (!empty($date_exp) && !$validateDate($date_exp)) {
        $errors[] = 'Date d\'expiration invalide (format attendu YYYY-MM-DD).';
    }

    // Cohérence dates : expiration >= délivrance
    if (!empty($date_deliv) && !empty($date_exp)) {
        try {
            $d1 = new DateTime($date_deliv);
            $d2 = new DateTime($date_exp);
            if ($d2 < $d1) {
                $errors[] = 'La date d\'expiration doit être postérieure à la date de délivrance.';
            }
        } catch (Exception $e) {
            // ignore, déjà vérifié
        }
    }

    // Si erreurs, afficher et ne pas insérer
    if (!empty($errors)) {
        $msg = implode("\\n", $errors);
        echo "<script>alert('Erreur(s) de validation:\\n" . addslashes($msg) . "'); window.history.back();</script>";
        exit;
    }

    // Ensure `account_number` column exists in tblCompte (add if missing)
    if (defined('DB_NAME')) {
        $schema = mysqli_real_escape_string($conn, DB_NAME);
        $colCheck = mysqli_query($conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".$schema."' AND TABLE_NAME='tblCompte' AND COLUMN_NAME='account_number' LIMIT 1");
        if ($colCheck && mysqli_num_rows($colCheck) === 0) {
            // add column
            $alter = "ALTER TABLE tblCompte ADD COLUMN `account_number` VARCHAR(64) DEFAULT NULL";
            mysqli_query($conn, $alter);
        }
        if ($colCheck) mysqli_free_result($colCheck);
    }

    // Préparer l'insertion dans tblCompte
    $sql_compte = "INSERT INTO tblCompte (
        emp_id, firstname, services, type_compte, devise_pref, objectif, access, titre, noms, prenom2,
        nationalite, lieu_naiss, pays, dob, genre, situation, id_type, id_num, date_deliv, date_exp,
        fiscal_pays, nip, mobile1, mobile2, email, adr_rue, ville, adr_pays, employeur, cond, revenu, etabliss, ident_etud, date_enregistrement
    ) VALUES (
        ?,?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?,?,?,?,?, ?,?,? , NOW()
    )";

    $stmt_compte = mysqli_prepare($conn, $sql_compte);
    if ($stmt_compte) {
        // Colonnes (sans date_enregistrement) et valeurs dans le même ordre
        $cols = array(
            'emp_id','firstname','services','type_compte','devise_pref','objectif','access','titre','noms','prenom2',
            'nationalite','lieu_naiss','pays','dob','genre','situation','id_type','id_num','date_deliv','date_exp',
            'fiscal_pays','nip','mobile1','mobile2','email','adr_rue','ville','adr_pays','employeur','cond','revenu','etabliss','ident_etud'
        );

        $values = array(
            $empid, $firstname, $services_str, $type_compte_str, $devise_pref, $objectif, $access_str, $titre, $noms, $prenom2,
            $nationalite, $lieu_naiss, $pays, $dob, $genre, $situation, $id_type_str, $id_num, $date_deliv, $date_exp,
            $fiscal_pays, $nip, $mobile1, $mobile2, $email, $adr_rue, $ville, $adr_pays, $employeur, $cond_str, $revenu, $etabliss, $ident_etud
        );

        // types: i puis autant de s qu'il y a de valeurs - 1 (empid est int)
        $types = 'i' . str_repeat('s', count($values)-1);

        // Préparer le binding dynamique
        $bind_params = array();
        $bind_params[] = $types;
        // mysqli requires references
        foreach ($values as $k => $v) {
            $bind_params[] = & $values[$k];
        }

        // Appel dynamique
        call_user_func_array(array($stmt_compte, 'bind_param'), $bind_params);

        if (mysqli_stmt_execute($stmt_compte)) {
            // After insert, generate an account number with prefix 3715 and total length 11 digits (3715 + 7 digits)
            $newId = mysqli_insert_id($conn);
            $suffix = str_pad((string)$newId, 7, '0', STR_PAD_LEFT);
            $generatedAccount = '3715' . $suffix; // total length: 4 + 7 = 11 digits
            $u = mysqli_prepare($conn, "UPDATE tblCompte SET account_number = ? WHERE id = ?");
            if ($u) {
                mysqli_stmt_bind_param($u, 'si', $generatedAccount, $newId);
                mysqli_stmt_execute($u);
                mysqli_stmt_close($u);
            }
            // show the generated account to the user
            $jsMsg = addslashes('Dossier enregistré dans tblCompte. Numéro de compte : ' . $generatedAccount);
            echo "<script>alert('" . $jsMsg . "'); window.location.href='account_part';</script>";
        } else {
            echo "<script>alert('Erreur en enregistrant tblCompte: " . mysqli_stmt_error($stmt_compte) . "');</script>";
        }

        mysqli_stmt_close($stmt_compte);
    } else {
        echo "<script>alert('Erreur de préparation tblCompte: " . mysqli_error($conn) . "');</script>";
    }
}

// Optionnel : Récupérer les données de l'employé
$query = mysqli_query($conn, "SELECT * FROM tblemployees WHERE emp_id = '$session_id'");
$row = mysqli_fetch_assoc($query);
?>
