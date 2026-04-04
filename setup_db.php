<?php
// Script pour créer et importer la base de données
require_once 'includes/config.php';

try {
    echo "Création de la base de données...<br>";

    // Créer la base si elle n'existe pas
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS ecoleaves_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if (mysqli_query($conn, $create_db_sql)) {
        echo "✓ Base de données créée ou déjà existante<br>";
    } else {
        echo "✗ Erreur création base: " . mysqli_error($conn) . "<br>";
    }

    // Sélectionner la base
    mysqli_select_db($conn, 'ecoleaves_db');

    // Lire et exécuter le fichier SQL
    $sql_file = 'db/ecoleaves_db.sql';
    if (file_exists($sql_file)) {
        echo "Importation du fichier SQL...<br>";
        $sql_content = file_get_contents($sql_file);

        // Diviser en requêtes individuelles
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));

        $success_count = 0;
        $error_count = 0;

        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^--/', $query)) {
                if (mysqli_query($conn, $query)) {
                    $success_count++;
                } else {
                    $error_count++;
                    echo "✗ Erreur SQL: " . mysqli_error($conn) . "<br>";
                }
            }
        }

        echo "✓ Requêtes réussies: $success_count<br>";
        if ($error_count > 0) {
            echo "⚠ Requêtes échouées: $error_count<br>";
        }

    } else {
        echo "✗ Fichier SQL non trouvé: $sql_file<br>";
    }

    echo "<br><a href='test_db.php'>Tester la connexion</a><br>";
    echo "<a href='index.php'>Aller à la page de connexion</a>";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>