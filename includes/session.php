<?php
 session_start(); 
//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['alogin']) || (trim($_SESSION['alogin']) == '')) { ?>
<script>
window.location = "../index.php";
</script>
<?php
}
$session_id=$_SESSION['alogin'];
$session_role = $_SESSION['arole'];
$session_depart = $_SESSION['adepart'];

// Stocker emp_id dans la session
if (!isset($_SESSION['emp_id'])) {
    $_SESSION['emp_id'] = $session_id;
}

// Récupérer le nom complet de l'utilisateur depuis la base de données
include('config.php');
$query = "SELECT FirstName, LastName FROM tblemployees WHERE emp_id = '$session_id'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $user_info = mysqli_fetch_assoc($result);
    $_SESSION['user_fullname'] = $user_info['FirstName'] . ' ' . $user_info['LastName'];
}
?>