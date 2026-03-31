<?php
session_name('ACCOUNT_OPENING_SESSION');
session_start();
include('includes/config.php');

// Connexion à la base de données
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Erreur de connexion: " . mysqli_connect_error());
}

echo "<h2>Mise à jour des mots de passe MD5 vers Bcrypt</h2>";

// Récupérer tous les utilisateurs
$result = mysqli_query($conn, "SELECT emp_id, Password FROM tblemployees");

$updated = 0;
$errors = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $emp_id = $row['emp_id'];
    $current_password = $row['Password'];

    // Vérifier si c'est un hash MD5 (32 caractères)
    if (strlen($current_password) == 32 && ctype_xdigit($current_password)) {
        // Générer un nouveau hash bcrypt
        // Note: Nous ne pouvons pas récupérer le mot de passe en clair, donc nous allons définir un mot de passe temporaire
        // En production, il faudrait demander aux utilisateurs de réinitialiser leur mot de passe
        $temp_password = 'TempPass123!'; // Mot de passe temporaire
        $new_hash = password_hash($temp_password, PASSWORD_DEFAULT);

        // Mettre à jour le mot de passe
        $stmt = mysqli_prepare($conn, "UPDATE tblemployees SET Password = ?, password_changed = 0 WHERE emp_id = ?");
        mysqli_stmt_bind_param($stmt, "ss", $new_hash, $emp_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "Utilisateur ID $emp_id : Mot de passe mis à jour (nouveau mot de passe temporaire : $temp_password)<br>";
            $updated++;
        } else {
            echo "Erreur lors de la mise à jour de l'utilisateur ID $emp_id : " . mysqli_error($conn) . "<br>";
            $errors++;
        }
        mysqli_stmt_close($stmt);
        } else {
        echo "Utilisateur ID $emp_id : Mot de passe déjà sécurisé (bcrypt)<br>";
    }
    
    }


echo "<br><strong>Résumé :</strong><br>";
echo "Utilisateurs mis à jour : $updated<br>";
echo "Erreurs : $errors<br>";

if ($updated > 0) {
    echo "<br><strong>Important :</strong> Les utilisateurs dont le mot de passe a été mis à jour devront utiliser le mot de passe temporaire 'TempPass123!' pour se connecter, puis changer leur mot de passe.";
}

mysqli_close($conn);
?>