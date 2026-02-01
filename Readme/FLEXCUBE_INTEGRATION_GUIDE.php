<?php
/**
 * Guide d'Intégration Flexcube dans d'Autres Fichiers
 * 
 * Montre comment intégrer Flexcube dans les fichiers existants du projet
 */

// ============================================
// 1. DANS ecobank_submission_view.php
// ============================================

/*

Au début du fichier (après les includes):

<?php include('includes/header.php')?>
<?php include('../includes/session.php')?>
<?php include('includes/flexcube_helpers.php')?>  // ← AJOUTER

// Récupérer la soumission
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM ecobank_form_submissions WHERE id = $id LIMIT 1";
$res = mysqli_query($conn, $sql);

if (!$res || mysqli_num_rows($res) == 0) {
    echo "<div class='alert alert-danger'>Soumission non trouvée</div>";
    exit;
}

$submission = mysqli_fetch_assoc($res);

// ← AJOUTER: Enrichir avec Flexcube
if (!empty($submission['account_number'])) {
    $flexcube_data = fetchAccountFromFlexcube($submission['account_number']);
    if ($flexcube_data) {
        // Mettre à jour les champs si vides
        if (empty($submission['status'])) {
            $submission['status'] = $flexcube_data['status'];
        }
        if (empty($submission['balance'])) {
            $submission['balance'] = $flexcube_data['balance'];
        }
        $submission['flexcube_data'] = $flexcube_data;
    }
}

// Afficher les données
?>

<!-- HTML -->
<div class="row">
    <div class="col-md-6">
        <h4>Informations BD Locale</h4>
        <p><strong>Compte:</strong> <?php echo safe_h($submission['account_number']); ?></p>
        <p><strong>Nom:</strong> <?php echo safe_h($submission['customer_name']); ?></p>
    </div>
    
    <!-- ← AJOUTER: Afficher les données Flexcube -->
    <?php if (isset($submission['flexcube_data'])): ?>
    <div class="col-md-6">
        <h4>Données Flexcube</h4>
        <p><strong>Statut:</strong> <span class="badge"><?php echo safe_h($submission['flexcube_data']['status']); ?></span></p>
        <p><strong>Solde:</strong> <?php echo safe_h($submission['flexcube_data']['balance']); ?> <?php echo safe_h($submission['flexcube_data']['currency']); ?></p>
        <p><strong>Type:</strong> <?php echo safe_h($submission['flexcube_data']['account_type']); ?></p>
        <p><em style="color: #999;">Données récupérées de Flexcube</em></p>
    </div>
    <?php endif; ?>
</div>

*/

// ============================================
// 2. DANS save_ecobank_form.php
// ============================================

/*

Au moment de sauvegarder le formulaire:

<?php
include('../includes/config.php');
include('includes/flexcube_helpers.php'); // ← AJOUTER

$account_number = $_POST['account_number'] ?? '';
$customer_name = $_POST['customer_name'] ?? '';

// Valider le compte avec Flexcube AVANT sauvegarde
$flexcube_account = fetchAccountFromFlexcube($account_number);

if (!$flexcube_account) {
    // Compte introuvable dans Flexcube
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Compte introuvable. Veuillez vérifier le numéro',
        'source' => 'flexcube'
    ]);
    exit;
}

// Compte valide, continuer la sauvegarde
$sql = "INSERT INTO ecobank_form_submissions (account_number, customer_name, ...) 
        VALUES ('$account_number', '$customer_name', ...)";

// ← AJOUTER: Stocker aussi les données Flexcube
$json_snapshot = json_encode([
    'account_info' => $flexcube_account,
    'submitted_at' => date('Y-m-d H:i:s'),
    'validated_by' => 'flexcube'
]);

$sql = "INSERT INTO ecobank_form_submissions 
        (account_number, customer_name, json_snapshot, ...) 
        VALUES ('$account_number', '$customer_name', '$json_snapshot', ...)";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true,
        'message' => 'Compte validé et sauvegardé',
        'account_data' => $flexcube_account
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>

*/

// ============================================
// 3. DANS generate_pdf.php (RAPPORT)
// ============================================

/*

Pour générer des rapports avec les données Flexcube:

<?php
include('includes/flexcube_helpers.php');

$account_number = $_GET['account'] ?? '';

// Récupérer les données
$submission = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM ecobank_form_submissions WHERE account_number = '$account_number'"
));

// Enrichir avec Flexcube
if ($submission) {
    $flexcube_data = fetchAccountFromFlexcube($submission['account_number']);
    if ($flexcube_data) {
        $submission['flexcube_data'] = $flexcube_data;
    }
}

// Générer le PDF
$pdf = new DOMPDF();

$html = '
    <h1>Rapport Compte Ecobank</h1>
    <h2>Informations Locales</h2>
    <p>Numéro: ' . $submission['account_number'] . '</p>
    <p>Nom: ' . $submission['customer_name'] . '</p>
    
    <h2>Données Flexcube</h2>
    <p>Statut: ' . ($submission['flexcube_data']['status'] ?? 'N/A') . '</p>
    <p>Solde: ' . ($submission['flexcube_data']['balance'] ?? 'N/A') . '</p>
    <p>Type: ' . ($submission['flexcube_data']['account_type'] ?? 'N/A') . '</p>
';

$pdf->loadHtml($html);
$pdf->render();
$pdf->stream('rapport_' . $account_number . '.pdf');
?>

*/

// ============================================
// 4. DANS UN MIDDLEWARE CUSTOM
// ============================================

/*

Créer un middleware qui valide les comptes Flexcube:

<?php
// middleware/FlexcubeValidator.php

class FlexcubeValidator {
    
    private $flexcube;
    
    public function __construct() {
        include(__DIR__ . '/../cso/includes/flexcube_helpers.php');
        $this->flexcube = getFlexcubeAPI();
    }
    
    public function validate_account($account_number) {
        $response = $this->flexcube->getAccountInfo($account_number);
        
        if (!$response['success']) {
            return [
                'valid' => false,
                'error' => $response['error']
            ];
        }
        
        $account = $response['data'];
        
        // Vérifier le statut
        if ($account['status'] !== 'ACTIVE') {
            return [
                'valid' => false,
                'error' => "Compte en statut: {$account['status']}"
            ];
        }
        
        return [
            'valid' => true,
            'account' => $account
        ];
    }
    
    public function get_account($account_number) {
        return $this->flexcube->getAccountInfo($account_number);
    }
}

// Utilisation:
// $validator = new FlexcubeValidator();
// $result = $validator->validate_account('37220020391');

*/

// ============================================
// 5. DANS UN API ENDPOINT
// ============================================

/*

Créer un endpoint JSON pour l'API:

<?php
// cso/api/account.php

include('../../includes/config.php');
include('../includes/flexcube_helpers.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$account = $_GET['account'] ?? '';

if (!$account) {
    http_response_code(400);
    echo json_encode(['error' => 'Account number required']);
    exit;
}

switch ($action) {
    case 'get':
        // Récupérer un compte
        $result = fetchAccountWithFallback($account, $conn);
        echo json_encode([
            'success' => true,
            'source' => $result['source'],
            'data' => $result['data']
        ]);
        break;
    
    case 'validate':
        // Valider un compte
        $flexcube_data = fetchAccountFromFlexcube($account);
        echo json_encode([
            'valid' => $flexcube_data !== null,
            'data' => $flexcube_data
        ]);
        break;
    
    case 'status':
        // Vérifier le statut
        $test = testFlexcubeConnection();
        echo json_encode($test);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
?>

// Appel: /cso/api/account.php?action=get&account=37220020391

*/

// ============================================
// 6. DANS UNE FONCTION EXISTANTE
// ============================================

/*

Exemple de modification d'une fonction existante:

// AVANT:
function get_account_info($account_number, $conn) {
    $sql = "SELECT * FROM tblCompte WHERE account_number = '$account_number' LIMIT 1";
    return mysqli_fetch_assoc(mysqli_query($conn, $sql));
}

// APRÈS:
function get_account_info($account_number, $conn) {
    // Essayer Flexcube d'abord
    $flexcube_data = fetchAccountFromFlexcube($account_number);
    
    if ($flexcube_data) {
        return $flexcube_data;
    }
    
    // Fallback vers tblCompte
    $sql = "SELECT * FROM tblCompte WHERE account_number = '$account_number' LIMIT 1";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    
    return $result;
}

*/

// ============================================
// 7. POUR FAIRE UN BATCH UPDATE
// ============================================

/*

Mettre à jour multiple comptes:

<?php
include('includes/flexcube_helpers.php');

$accounts = ['37220020391', '37220020392', '37220020393'];

// Récupérer les données de tous les comptes
$batch_results = fetchMultipleAccountsFromFlexcube($accounts);

// Mettre à jour la BD avec les données Flexcube
foreach ($batch_results as $account_num => $response) {
    if ($response['success']) {
        $data = $response['data'];
        
        $sql = "UPDATE ecobank_form_submissions 
                SET status = '{$data['status']}', 
                    balance = '{$data['balance']}',
                    account_type = '{$data['account_type']}'
                WHERE account_number = '$account_num'";
        
        mysqli_query($conn, $sql);
    }
}

echo "Batch update terminé";
?>

*/

// ============================================
// 8. POUR SYNCHRONISER AVEC FLEXCUBE
// ============================================

/*

Créer une tâche planifiée:

<?php
// cso/tasks/sync_flexcube.php
// À exécuter via cron: 0 * / 6 * * * php /path/to/sync_flexcube.php

include('../../includes/config.php');
include('../includes/flexcube_helpers.php');

// Récupérer tous les comptes
$sql = "SELECT DISTINCT account_number FROM ecobank_form_submissions 
        WHERE account_number IS NOT NULL 
        LIMIT 100";

$res = mysqli_query($conn, $sql);
$accounts = [];

while ($row = mysqli_fetch_assoc($res)) {
    $accounts[] = $row['account_number'];
}

// Récupérer les données Flexcube
$batch_results = fetchMultipleAccountsFromFlexcube($accounts);

// Mettre à jour la BD
$updated = 0;
foreach ($batch_results as $account_num => $response) {
    if ($response['success']) {
        $data = $response['data'];
        $json = json_encode($data);
        
        $sql = "UPDATE ecobank_form_submissions 
                SET json_snapshot = '$json',
                    last_flexcube_sync = NOW()
                WHERE account_number = '$account_num'";
        
        if (mysqli_query($conn, $sql)) {
            $updated++;
        }
    }
}

echo "Synchronisation terminée: $updated comptes mis à jour";

// Log
error_log("Flexcube sync completed: $updated accounts updated at " . date('Y-m-d H:i:s'));
?>

*/

echo "Guide d'intégration créé. Voir les exemples ci-dessus.";
?>
