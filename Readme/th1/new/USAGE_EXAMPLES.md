<?php
/**
 * Exemples d'Utilisation - Intégration Flexcube
 * 
 * Cas d'usage pratiques et exemples de code
 */

echo "=== EXEMPLES D'UTILISATION FLEXCUBE INTEGRATION ===\n\n";

// ============================================
// EXEMPLE 1: Récupération Simple
// ============================================
echo "EXEMPLE 1: Récupération simple d'un compte\n";
echo str_repeat("-", 50) . "\n";

// Code PHP (dans un controller ou page)
$example1 = <<<'PHP'
<?php
require_once('cso/includes/FlexcubeAPI.php');

$api = new FlexcubeAPI();
$response = $api->getAccountInfo('37220026306');

if ($response['success']) {
    // Les données sont déjà mappées par UDFDataMapper
    $data = $response['data'];
    
    echo "Compte trouvé: " . $data['account_number'];
    echo "Email: " . $data['email'];
    echo "Téléphone: " . $data['phone'];
} else {
    echo "Erreur: " . $response['error'];
}
?>
PHP;

echo $example1 . "\n\n";

// ============================================
// EXEMPLE 2: Utilisation dans Fetch API
// ============================================
echo "EXEMPLE 2: Appel API via JavaScript (fetch)\n";
echo str_repeat("-", 50) . "\n";

$example2 = <<<'JS'
// JavaScript côté client
function searchAccount(accountNumber) {
    fetch('fetch_account_flexcube.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'account=' + encodeURIComponent(accountNumber)
    })
    .then(response => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
    })
    .then(json => {
        if (json.success) {
            console.log('Données reçues:', json.data);
            
            // Remplir le formulaire automatiquement
            FormAutoFiller.autoFillForm(json.data, {debug: true});
            
            console.log('✓ Formulaire rempli');
        } else {
            console.error('❌ Erreur:', json.error);
        }
    })
    .catch(error => {
        console.error('❌ Erreur réseau:', error);
    });
}

// Utilisation:
searchAccount('37220026306');
JS;

echo $example2 . "\n\n";

// ============================================
// EXEMPLE 3: Mappage des Données UDF
// ============================================
echo "EXEMPLE 3: Mappage des champs UDF\n";
echo str_repeat("-", 50) . "\n";

$example3 = <<<'PHP'
<?php
require_once('cso/includes/UDFDataMapper.php');

// Données brutes de Flexcube
$udf_values = [
    'FIRST_NAME' => 'Jean',
    'LAST_NAME' => 'Dupont',
    'EMAIL' => 'jean@example.com',
    'PHONE' => '+242 06 123 4567',
    'NATIONALITY' => 'CG',
    'SEX' => 'M'
];

// Mappage automatique
$mapped = UDFDataMapper::mapUDFToFormFields($udf_values);

// Résultat (multiples alias pour chaque champ):
// [
//     'first-name' => 'Jean',
//     'first_name' => 'Jean',
//     'prenom' => 'Jean',
//     'last-name' => 'Dupont',
//     'last_name' => 'Dupont',
//     'email' => 'jean@example.com',
//     'adresse_email' => 'jean@example.com',
//     'phone' => '+242 06 123 4567',
//     'telephone' => '+242 06 123 4567',
//     'numero_telephone' => '+242 06 123 4567',
//     'nationality' => 'CG',
//     'nationalite' => 'CG',
//     'sex' => 'M',
//     'sexe' => 'M'
// ]
?>
PHP;

echo $example3 . "\n\n";

// ============================================
// EXEMPLE 4: Validation de Données
// ============================================
echo "EXEMPLE 4: Validation email et téléphone\n";
echo str_repeat("-", 50) . "\n";

$example4 = <<<'PHP'
<?php
require_once('cso/includes/UDFDataMapper.php');

// Validation email
if (UDFDataMapper::isEmail('user@example.com')) {
    echo "✓ Email valide";
}

if (!UDFDataMapper::isEmail('not-an-email')) {
    echo "✓ Non une email";
}

// Validation téléphone
if (UDFDataMapper::isPhoneNumber('+242 06 123 4567')) {
    echo "✓ Numéro de téléphone valide";
}

if (!UDFDataMapper::isPhoneNumber('abc')) {
    echo "✓ Non un numéro de téléphone";
}
?>
PHP;

echo $example4 . "\n\n";

// ============================================
// EXEMPLE 5: Remplissage Formulaire JavaScript
// ============================================
echo "EXEMPLE 5: Remplissage automatique du formulaire\n";
echo str_repeat("-", 50) . "\n";

$example5 = <<<'JS'
// Exemple de données reçues
const data = {
    'first-name': 'Jean',
    'last-name': 'Dupont',
    'email': 'jean@example.com',
    'telephone': '+242 06 123 4567',
    'nationality': 'CG',
    'sex': 'M',
    'address': '123 Rue de la Paix'
};

// Remplir automatiquement le formulaire
FormAutoFiller.autoFillForm(data);

// Le formulaire a maintenant:
// <input id="first-name" value="Jean" />
// <input id="email" value="jean@example.com" />
// <input id="telephone" value="+242 06 123 4567" />
// etc.

// Avec debug:
FormAutoFiller.autoFillForm(data, {debug: true});
// Console affiche toutes les modifications
JS;

echo $example5 . "\n\n";

// ============================================
// EXEMPLE 6: Cas d'Erreur
// ============================================
echo "EXEMPLE 6: Gestion des erreurs\n";
echo str_repeat("-", 50) . "\n";

$example6 = <<<'PHP'
<?php
require_once('cso/includes/FlexcubeAPI.php');

$api = new FlexcubeAPI();

// Cas 1: Compte non trouvé
$response = $api->getAccountInfo('99999999999');
// Résultat: 
// [
//     'success' => false,
//     'error' => 'Compte introuvable ou réponse invalide',
//     'data' => null
// ]

// Cas 2: Format invalide
$response = $api->getAccountInfo('abc');
// Résultat:
// [
//     'success' => false,
//     'error' => 'Format de numéro de compte invalide',
//     'data' => null
// ]

// Cas 3: Erreur API
$response = $api->getAccountInfo('12345678901');
// Flexcube retourne responseCode != '000'
// Résultat:
// [
//     'success' => false,
//     'error' => 'API Error: Invalid Account (Code: 400)',
//     'data' => null
// ]

// Cas 4: Timeout
$response = $api->getAccountInfo('37220026306');
// Pas de réponse en 30 secondes
// Résultat:
// [
//     'success' => false,
//     'error' => 'Erreur cURL: Operation timed out',
//     'data' => null
// ]
?>
PHP;

echo $example6 . "\n\n";

// ============================================
// EXEMPLE 7: Batch Processing
// ============================================
echo "EXEMPLE 7: Traitement multiple (batch)\n";
echo str_repeat("-", 50) . "\n";

$example7 = <<<'PHP'
<?php
require_once('cso/includes/FlexcubeAPI.php');

$api = new FlexcubeAPI();

// Récupérer plusieurs comptes
$accounts = [
    '37220026306',
    '37220020391',
    '37220025902'
];

$results = $api->getMultipleAccounts($accounts);

// Résultat:
// [
//     '37220026306' => ['success' => true, 'data' => [...], ...],
//     '37220020391' => ['success' => true, 'data' => [...], ...],
//     '37220025902' => ['success' => false, 'error' => '...', ...]
// ]

// Traiter les résultats
foreach ($results as $account => $response) {
    if ($response['success']) {
        echo "✓ $account: " . $response['data']['email'];
    } else {
        echo "✗ $account: " . $response['error'];
    }
}
?>
PHP;

echo $example7 . "\n\n";

// ============================================
// EXEMPLE 8: Configuration par Environnement
// ============================================
echo "EXEMPLE 8: Configuration multi-environnement\n";
echo str_repeat("-", 50) . "\n";

$example8 = <<<'PHP'
<?php
require_once('cso/includes/FlexcubeAPI.php');
require_once('cso/includes/flexcube_config.php');

// Charger la config
$config = require('cso/includes/flexcube_config.php');

// Récupérer l'environnement
$env = getenv('APP_ENV') ?: 'development';
$env_config = $config['environments'][$env];

// Configurer l'API
$api = new FlexcubeAPI();
$api->setApiUrl($env_config['api_url']);
$api->setSSLVerification($env_config['verify_ssl']);

// Maintenant utiliser l'API
$response = $api->getAccountInfo('37220026306');
?>
PHP;

echo $example8 . "\n\n";

// ============================================
// EXEMPLE 9: Tests et Debugging
// ============================================
echo "EXEMPLE 9: Tests et debugging\n";
echo str_repeat("-", 50) . "\n";

$example9 = <<<'PHP'
<?php
require_once('cso/includes/FlexcubeAPI.php');
require_once('cso/includes/UDFDataMapper.php');

// Test 1: Vérifier la connexion
$api = new FlexcubeAPI();
$test = $api->testConnection();
echo "Connexion API: " . ($test['status'] === 'OK' ? 'OK' : 'FAIL') . "\n";

// Test 2: Lister les champs UDF connus
$known_fields = UDFDataMapper::getKnownUDFFields();
echo "Champs UDF connus: " . count($known_fields) . "\n";
print_r($known_fields);

// Test 3: Vérifier un champ spécifique
$description = UDFDataMapper::getUDFDescription('FIRST_NAME');
echo "Description FIRST_NAME: $description\n";

// Test 4: Valider les données
$email = 'test@example.com';
$phone = '+242 06 123 4567';

echo "Email '$email' valide? " . (UDFDataMapper::isEmail($email) ? 'OUI' : 'NON') . "\n";
echo "Téléphone '$phone' valide? " . (UDFDataMapper::isPhoneNumber($phone) ? 'OUI' : 'NON') . "\n";
?>
PHP;

echo $example9 . "\n\n";

// ============================================
// EXEMPLE 10: Intégration Complète
// ============================================
echo "EXEMPLE 10: Intégration complète (flow complet)\n";
echo str_repeat("-", 50) . "\n";

$example10 = <<<'HTML'
<!-- HTML du formulaire -->
<form id="account-form">
    <input type="text" id="account-number" 
           placeholder="Numéro de compte">
    <button type="button" id="search-btn">Chercher</button>
    
    <input type="text" id="first-name" 
           placeholder="Prénom">
    <input type="text" id="last-name" 
           placeholder="Nom">
    <input type="email" id="email" 
           placeholder="Email">
    <input type="tel" id="telephone" 
           placeholder="Téléphone">
    <select id="nationality">
        <option>-- Sélectionner --</option>
        <option value="CG">Congo</option>
        <option value="CM">Cameroon</option>
    </select>
    <select id="sex">
        <option>-- Sélectionner --</option>
        <option value="M">Homme</option>
        <option value="F">Femme</option>
    </select>
    <textarea id="address" 
              placeholder="Adresse"></textarea>
</form>

<script src="vendors/js/form_auto_filler.js"></script>
<script>
document.getElementById('search-btn').addEventListener('click', function() {
    const account = document.getElementById('account-number').value;
    
    if (!account) {
        alert('Saisir un numéro de compte');
        return;
    }
    
    // Faire l'appel API
    fetch('fetch_account_flexcube.php', {
        method: 'POST',
        body: 'account=' + encodeURIComponent(account)
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            // Remplir le formulaire
            FormAutoFiller.autoFillForm(json.data);
            alert('✓ Formulaire rempli');
        } else {
            alert('❌ ' + json.error);
        }
    })
    .catch(e => alert('❌ Erreur: ' + e.message));
});
</script>
HTML;

echo $example10 . "\n\n";

echo "=== FIN DES EXEMPLES ===\n";
?>
