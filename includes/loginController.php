<?php
// Session déjà démarrée dans index.php, pas besoin de la redémarrer
include('config.php');
include('audit_logger.php');

// Variable utilisée dans index.php
$login_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $login_error = 'Veuillez renseigner le nom d\'utilisateur et le mot de passe.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE Username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            $passwordValid = false;

            if (strlen($row['Password']) === 32 && ctype_xdigit($row['Password'])) {
                if (md5($password) === $row['Password']) {
                    $passwordValid = true;

                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = mysqli_prepare($conn, "UPDATE tblemployees SET Password=? WHERE emp_id=?");
                    mysqli_stmt_bind_param($updateStmt, "ss", $hashedPassword, $row['emp_id']);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }
            } else {
                $passwordValid = password_verify($password, $row['Password']);
            }

            if ($passwordValid) {
                $_SESSION['alogin'] = $row['emp_id'];
                $_SESSION['arole'] = $row['role'];
                $_SESSION['adepart'] = $row['Department'];

                audit_log_login($conn, $row['emp_id'], true);

                $loginStmt = mysqli_prepare($conn, "INSERT INTO tbl_logins (emp_id) VALUES (?)");
                mysqli_stmt_bind_param($loginStmt, "s", $row['emp_id']);
                mysqli_stmt_execute($loginStmt);
                mysqli_stmt_close($loginStmt);

                $statusStmt = mysqli_prepare($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id=?");
                mysqli_stmt_bind_param($statusStmt, "s", $row['emp_id']);
                mysqli_stmt_execute($statusStmt);
                mysqli_stmt_close($statusStmt);

                if ($row['password_changed'] == 0) {
                    header('Location: change_password.php');
                    exit;
                }

                $redirectUrl = 'ci/index';
                if ($row['role'] === 'Admin') {
                    $redirectUrl = 'admin/index';
                } elseif ($row['role'] === 'cso') {
                    $redirectUrl = 'cso/index';
                }

                header('Location: ' . $redirectUrl);
                exit;
            }

            audit_log_login($conn, $username, false);
            $login_error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        } else {
            audit_log_login($conn, $username, false);
            $login_error = 'Aucun utilisateur trouvé avec ce nom d\'utilisateur.';
        }

        mysqli_stmt_close($stmt);
    }
}

// Pour affichage dans le template
if (!empty($login_error)) {
    $_SESSION['login_error_message'] = $login_error;
}
?>