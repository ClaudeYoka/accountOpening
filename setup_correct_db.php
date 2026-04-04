<?php
// Script pour diagnostiquer et créer la base de données correcte
require_once 'includes/config.php';

echo "<h2>Diagnostic de la base de données</h2>";
echo "<pre>";

try {
    echo "Configuration actuelle :\n";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DB_PASS: " . (DB_PASS ? "***" : "vide") . "\n";
    echo "DB_NAME: " . DB_NAME . "\n\n";

    // Tester la connexion sans base spécifique
    $conn_test = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    if (!$conn_test) {
        die("Erreur de connexion MySQL: " . mysqli_connect_error());
    }
    echo "✓ Connexion MySQL réussie\n";

    // Lister les bases de données disponibles
    $result = mysqli_query($conn_test, "SHOW DATABASES");
    echo "\nBases de données disponibles :\n";
    $databases = [];
    while ($row = mysqli_fetch_row($result)) {
        $databases[] = $row[0];
        echo "- " . $row[0] . "\n";
    }

    // Vérifier si accountopening_db existe
    if (in_array('accountopening_db', $databases)) {
        echo "\n✓ Base 'accountopening_db' trouvée\n";

        // Se connecter à cette base
        mysqli_select_db($conn_test, 'accountopening_db');

        // Lister les tables
        $tables_result = mysqli_query($conn_test, "SHOW TABLES");
        echo "Tables dans accountopening_db :\n";
        $tables = [];
        while ($table = mysqli_fetch_row($tables_result)) {
            $tables[] = $table[0];
            echo "- " . $table[0] . "\n";
        }

        // Vérifier les tables essentielles
        $required_tables = ['tblemployees', 'tblcompte', 'chequier_status'];
        $missing_tables = [];
        foreach ($required_tables as $table) {
            if (!in_array($table, $tables)) {
                $missing_tables[] = $table;
            }
        }

        if (empty($missing_tables)) {
            echo "\n✓ Toutes les tables requises sont présentes\n";
        } else {
            echo "\n✗ Tables manquantes : " . implode(', ', $missing_tables) . "\n";
        }

    } else {
        echo "\n✗ Base 'accountopening_db' non trouvée\n";

        // Créer la base
        echo "\nCréation de la base accountopening_db...\n";
        if (mysqli_query($conn_test, "CREATE DATABASE accountopening_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            echo "✓ Base accountopening_db créée\n";

            // Se connecter à la nouvelle base
            mysqli_select_db($conn_test, 'accountopening_db');

            // Importer les tables depuis ecoleaves_db si elle existe
            if (in_array('ecoleaves_db', $databases)) {
                echo "Importation des tables depuis ecoleaves_db...\n";

                // Copier les tables essentielles
                $tables_to_copy = ['tblemployees', 'tbldepartments', 'tblcompte', 'chequier_status'];

                foreach ($tables_to_copy as $table) {
                    // Vérifier si la table existe dans ecoleaves_db
                    mysqli_select_db($conn_test, 'ecoleaves_db');
                    $check_table = mysqli_query($conn_test, "SHOW TABLES LIKE '$table'");

                    if (mysqli_num_rows($check_table) > 0) {
                        // Créer la table dans accountopening_db
                        mysqli_select_db($conn_test, 'accountopening_db');

                        // Obtenir la structure de la table
                        $create_result = mysqli_query($conn_test, "SHOW CREATE TABLE ecoleaves_db.$table");
                        if ($create_result) {
                            $create_row = mysqli_fetch_row($create_result);
                            $create_sql = str_replace('CREATE TABLE `', 'CREATE TABLE `accountopening_db`.`', $create_row[1]);

                            if (mysqli_query($conn_test, $create_sql)) {
                                echo "✓ Table $table créée\n";

                                // Copier les données
                                $copy_result = mysqli_query($conn_test, "INSERT INTO $table SELECT * FROM ecoleaves_db.$table");
                                if ($copy_result) {
                                    $count_result = mysqli_query($conn_test, "SELECT COUNT(*) as count FROM $table");
                                    $count_row = mysqli_fetch_assoc($count_result);
                                    echo "✓ " . $count_row['count'] . " enregistrements copiés dans $table\n";
                                }
                            }
                        }
                    }
                }
            } else {
                echo "✗ Base ecoleaves_db non trouvée pour l'importation\n";
            }
        } else {
            echo "✗ Erreur création base: " . mysqli_error($conn_test) . "\n";
        }
    }

    mysqli_close($conn_test);

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='test_db.php'>Tester la connexion</a>";
echo "<br><a href='index.php'>Aller à la page de connexion</a>";
?>