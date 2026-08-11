<?php
// Session déjà démarrée dans index.php, pas besoin de la redémarrer
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_logger.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

$login_result = [
    'redirect_to_change_password' => false,
    'redirect_url' => null,
    'error' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signin'])) {
    check_csrf();

    $rate_check = ['allowed' => true, 'blocked' => false];
    try {
        $rate_check = middleware_rate_limit($dbh, 'login', 5, 300);
    } catch (Exception $e) {
        error_log("Rate limiter error in loginController1: " . $e->getMessage());
    }

    if (!empty($rate_check['blocked'])) {
        $login_result['error'] = $rate_check['message'] ?? 'Trop de tentatives. Veuillez réessayer plus tard.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $login_result['error'] = 'Veuillez renseigner le nom d\'utilisateur et le mot de passe.';
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM tblemployees WHERE Username = ?");
            mysqli_stmt_bind_param($stmt, 's', $username);
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
                        mysqli_stmt_bind_param($updateStmt, 'ss', $hashedPassword, $row['emp_id']);
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

                    try {
                        $limiter = new RateLimiter($dbh);
                        $limiter->clearLogs('login', $limiter->getClientIdentifier());
                    } catch (Exception $e) {
                        error_log("Failed to clear rate limit on successful loginController1: " . $e->getMessage());
                    }

                    $loginStmt = mysqli_prepare($conn, "INSERT INTO tbl_logins (emp_id) VALUES (?)");
                    mysqli_stmt_bind_param($loginStmt, 's', $row['emp_id']);
                    mysqli_stmt_execute($loginStmt);
                    mysqli_stmt_close($loginStmt);

                    if ($row['password_changed'] == 0) {
                        $login_result['redirect_to_change_password'] = true;
                    } else {
                        if (empty($row['twofa_secret'])) {
                            $g = new GoogleAuthenticator();
                            $secret = $g->generateSecret();

                            $_SESSION['temp_twofa_secret'] = $secret;
                            $_SESSION['temp_user_id'] = $row['Username'];

                            $otpUrl = "otpauth://totp/ECOBANK%20AO%20%26%20KYC:" . urlencode($row['EmailId']) . "?secret=" . $secret . "&issuer=ECOBANK%20AO%20%26%20KYC";
                            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpUrl);

                            include __DIR__ . '/display_qrcode.php';
                            exit();
                        }

                        $_SESSION['verify_twofa'] = true;
                        $_SESSION['twofa_secret'] = $row['twofa_secret'];
                        include __DIR__ . '/verify_2fa.php';
                        exit();
                    }
                } else {
                    audit_log_login($conn, $username, false);
                    $login_result['error'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
                }
            } else {
                audit_log_login($conn, $username, false);
                $login_result['error'] = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['verify_2fa'])) {
    $code = $_POST['twofa_code'] ?? '';
    $g = new GoogleAuthenticator();

    if (!empty($_SESSION['twofa_secret']) && $g->checkCode($_SESSION['twofa_secret'], $code)) {
        completeLogin($conn);
    } else {
        $_SESSION['login_error_message'] = 'Code 2FA invalide';
        header('Location: index.php');
        exit();
    }
}

if (isset($_POST['activate_2fa'])) {
    $code = $_POST['twofa_code'] ?? '';
    $g = new GoogleAuthenticator();

    if (!empty($_SESSION['temp_twofa_secret']) && $g->checkCode($_SESSION['temp_twofa_secret'], $code)) {
        $emp_id = $_SESSION['temp_user_id'];
        $secret = $_SESSION['temp_twofa_secret'];

        $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET twofa_secret=?, twofa_enabled=1 WHERE Username=?");
        mysqli_stmt_bind_param($stmt, 'ss', $secret, $emp_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        unset($_SESSION['temp_twofa_secret'], $_SESSION['temp_user_id']);

        completeLogin($conn);
    } else {
        $_SESSION['login_error_message'] = 'Code de vérification invalide';
        header('Location: index.php');
        exit();
    }
}

if (!empty($login_result['error'])) {
    $_SESSION['login_error_message'] = $login_result['error'];
}

function completeLogin($conn) {
    $emp_id = $_SESSION['alogin'];
    $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET status='Online' WHERE emp_id=?");
    mysqli_stmt_bind_param($stmt, 's', $emp_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    switch ($_SESSION['arole']) {
        case 'Admin':
            header('Location: admin/index');
            break;
        case 'cso':
            header('Location: cso/index');
            break;
        case 'CI':
            header('Location: ci/index');
            break;
        default:
            header('Location: index');
            break;
    }
    exit();
}
?>
