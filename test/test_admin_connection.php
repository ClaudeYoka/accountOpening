<?php
/**
 * TEST: Vérification de la connexion admin
 * Teste les problèmes:
 * 1. Ordre des includes
 * 2. Définition de $conn
 * 3. Accès aux variables de session
 */

// Test 1: Include session et config dans le bon ordre
echo "=== TEST 1: Include Order ===\n";
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

echo "✓ session.php included\n";

if (!isset($conn)) {
    echo "✗ ERREUR: $conn n'est pas défini après session.php\n";
} else {
    echo "✓ $conn est défini\n";
}

include('../includes/config.php');
echo "✓ config.php included\n";

// Test 2: Vérifier les variables de session
echo "\n=== TEST 2: Variables Session ===\n";
echo "SESSION variables:\n";
echo "  alogin: " . (isset($_SESSION['alogin']) ? $_SESSION['alogin'] : "NON DÉFINI") . "\n";
echo "  arole: " . (isset($_SESSION['arole']) ? $_SESSION['arole'] : "NON DÉFINI") . "\n";
echo "  adepart: " . (isset($_SESSION['adepart']) ? $_SESSION['adepart'] : "NON DÉFINI") . "\n";
echo "  emp_id: " . (isset($_SESSION['emp_id']) ? $_SESSION['emp_id'] : "NON DÉFINI") . "\n";

// Test 3: Vérifier la connexion mysqli
echo "\n=== TEST 3: Connexion mysqli ===\n";
if (isset($conn) && $conn instanceof mysqli) {
    echo "✓ $conn est une instance mysqli valide\n";
    echo "  Host: " . $conn->host_info . "\n";
    
    // Tester une requête simple
    $result = mysqli_query($conn, "SELECT 1");
    if ($result) {
        echo "✓ Requête test réussie\n";
        mysqli_free_result($result);
    } else {
        echo "✗ Erreur requête test: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "✗ ERREUR: $conn n'est pas une instance mysqli valide\n";
    echo "  Type: " . gettype($conn) . "\n";
}

// Test 4: Tester une requête réelle
echo "\n=== TEST 4: Requête tblemployees ===\n";
if (isset($conn) && $conn instanceof mysqli) {
    $query = "SELECT emp_id, FirstName, LastName FROM tblemployees LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo "✓ Employés trouvés\n";
            echo "  ID: " . $row['emp_id'] . "\n";
            echo "  Nom: " . $row['FirstName'] . " " . $row['LastName'] . "\n";
        } else {
            echo "⚠ Aucun employé trouvé\n";
        }
        mysqli_free_result($result);
    } else {
        echo "✗ Erreur requête: " . mysqli_error($conn) . "\n";
    }
}

echo "\n=== TEST COMPLET ===\n";
?>
