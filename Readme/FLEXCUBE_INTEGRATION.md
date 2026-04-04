# Intégration API Flexcube Ecobank

## Vue d'ensemble

Ce projet intègre l'API Flexcube d'Ecobank pour récupérer les informations de comptes en temps réel, remplaçant ou enrichissant les requêtes vers la table `tblCompte`.

**URL API:** `https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo`

---

## Architecture

### Fichiers créés

1. **`cso/includes/FlexcubeAPI.php`** - Classe principale pour l'intégration
2. **`cso/includes/flexcube_helpers.php`** - Fonctions utilitaires et wrappers
3. **Documentation** - Ce fichier

### Modification existante

- **`cso/ecobank_submissions_list.php`** - Intégration avec support du fallback

---

## Installation & Configuration

### 1. Fichier de configuration (optionnel)

Vous pouvez définir les paramètres dans votre config global:

```php
// Dans config.php ou un fichier .env
define('FLEXCUBE_API_URL', 'https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo');
define('FLEXCUBE_SOURCE_CODE', 'ECOBANKMOBILE');
define('FLEXCUBE_AFFILIATE_CODE', 'ECG');
define('FLEXCUBE_VERIFY_SSL', false); // true en production
```

### 2. Inclusion dans vos fichiers

```php
<?php
// Pour avoir accès aux fonctions helper
include('cso/includes/flexcube_helpers.php');

// La classe FlexcubeAPI est automatiquement incluse par flexcube_helpers.php
?>
```

---

## Utilisation

### Méthode 1: Fonction Simple (Recommandée)

```php
<?php
include('cso/includes/flexcube_helpers.php');

// Récupérer un compte
$account_data = fetchAccountFromFlexcube('37220020391');

if ($account_data) {
    echo "Compte: " . $account_data['account_number'];
    echo "Nom: " . $account_data['account_name'];
    echo "Solde: " . $account_data['balance'];
} else {
    echo "Compte non trouvé";
}
?>
```

### Méthode 2: Avec Fallback (BD Locale)

```php
<?php
include('cso/includes/flexcube_helpers.php');

// Essaye Flexcube, puis la BD locale
$result = fetchAccountWithFallback('37220020391', $conn);

echo "Source: " . $result['source']; // 'flexcube', 'local_db', ou 'tblCompte'

if ($result['data']) {
    $account = $result['data'];
    // Utiliser $account
}
?>
```

### Méthode 3: Classe Directe (Contrôle Total)

```php
<?php
include('cso/includes/FlexcubeAPI.php');

$flexcube = new FlexcubeAPI();

// Configuration optionnelle
$flexcube->setAuthConfig('ECOBANKMOBILE', 'ECG');
$flexcube->setSSLVerification(false); // true en production
$flexcube->setApiUrl('https://devtuat.ecobank.com/...');

// Récupérer un compte
$response = $flexcube->getAccountInfo('37220020391');

if ($response['success']) {
    $account = $response['data'];
    // Traiter...
} else {
    echo "Erreur: " . $response['error'];
}
?>
```

### Méthode 4: Batch (Plusieurs Comptes)

```php
<?php
include('cso/includes/flexcube_helpers.php');

$accounts = ['37220020391', '37220020392', '37220020393'];
$results = fetchMultipleAccountsFromFlexcube($accounts);

foreach ($results as $account_num => $response) {
    if ($response['success']) {
        echo "✓ $account_num: " . $response['data']['account_name'];
    } else {
        echo "✗ $account_num: " . $response['error'];
    }
}
?>
```

---

## Format de Réponse

### Réponse Réussie

```php
[
    'success' => true,
    'data' => [
        'account_number' => '37220020391',
        'account_name' => 'John Doe',
        'account_type' => 'SAVINGS',
        'currency' => 'XAF',
        'status' => 'ACTIVE',
        'balance' => '1500000.00',
        'customer_id' => 'CUST001',
        'branch_code' => '00001',
        'opening_date' => '2024-01-15',
        'raw_response' => '[XML brut]'
    ],
    'error' => null,
    'timestamp' => '2025-01-18 14:30:45'
]
```

### Réponse en Erreur

```php
[
    'success' => false,
    'data' => null,
    'error' => 'Compte introuvable ou réponse invalide',
    'timestamp' => '2025-01-18 14:30:45'
]
```

---

## Exemple d'Intégration Complète

### Dans `ecobank_submissions_list.php`

```php
<?php 
include('includes/header.php');
include('../includes/session.php');
include('includes/flexcube_helpers.php');

// Activer/désactiver Flexcube
$use_flexcube_fallback = true;

// ... code existant ...

while($r = mysqli_fetch_assoc($res)) {
    
    // Enrichir avec Flexcube si activé
    $row_data = $r;
    if ($use_flexcube_fallback && !empty($r['account_number'])) {
        $row_data = enrichRowWithFlexcube($r);
    }
    
    echo "<td>" . $row_data['account_number'] . "</td>";
    echo "<td>" . $row_data['customer_name'] . "</td>";
    // ...
}
?>
```

---

## Test de Connexion

```php
<?php
include('cso/includes/flexcube_helpers.php');

// Tester la connexion à Flexcube
$test = testFlexcubeConnection();

echo "Status: " . $test['status']; // 'OK' ou 'FAIL'
echo "Message: " . $test['message'];

if (!$test['connected']) {
    echo "Détails: " . $test['details']['error'];
}
?>
```

---

## Cache

Le service inclut un cache automatique de **1 heure** pour éviter les appels répétés.

```php
// Forcer une nouvelle requête en réinitialisant le cache
// À adapter selon vos besoins
```

---

## Gestion des Erreurs

### Erreurs Courantes

| Erreur | Cause | Solution |
|--------|-------|----------|
| `Erreur cURL` | Problème de connexion réseau | Vérifier la connexion internet, proxy |
| `Erreur HTTP 401` | Authentification invalide | Vérifier sourceCode, affiliateCode |
| `Erreur HTTP 404` | Compte inexistant | Vérifier le numéro de compte |
| `Erreur parsing XML` | Réponse invalide | Contacter le support Ecobank |

### Logs

Les erreurs sont loggées avec `error_log()`:

```php
// Voir les logs PHP (php.ini: error_log)
tail -f /var/log/php-errors.log
```

---

## Sécurité

### Recommandations

1. **SSL en Production**: Toujours utiliser `FLEXCUBE_VERIFY_SSL = true`
   ```php
   $flexcube->setSSLVerification(true); // Production
   ```

2. **Authentification**: Sécuriser les credentials
   ```php
   // Utiliser des variables d'environnement
   $source_code = $_ENV['FLEXCUBE_SOURCE_CODE'];
   ```

3. **Timeout**: Configurer un timeout approprié (30s par défaut)

4. **Validation**: Valider les numéros de compte
   ```php
   if (!preg_match('/^[0-9]{10,20}$/', $account_number)) {
       throw new Exception('Invalid account number');
   }
   ```

---

## Maintenance

### Mettre à jour les Paramètres

```php
// Modifier les constantes dans config.php
define('FLEXCUBE_SOURCE_CODE', 'NOUVEAU_CODE');
define('FLEXCUBE_AFFILIATE_CODE', 'NOUVEAU_CODE');
```

### Désactiver Flexcube (Fallback à la BD)

```php
// Dans ecobank_submissions_list.php
$use_flexcube_fallback = false; // Utiliser uniquement la BD locale
```

### Monitoring

Créer un script de monitoring:

```php
<?php
include('cso/includes/flexcube_helpers.php');

// Vérifier la connexion toutes les heures
$test = testFlexcubeConnection();

if (!$test['connected']) {
    // Envoyer une alerte
    mail(ADMIN_EMAIL, "Flexcube API DOWN", $test['message']);
}
?>
```

---

## Dépannage

### Le compte n'est pas trouvé

1. Vérifier le numéro de compte (format correct?)
2. Vérifier les paramètres d'authentification
3. Tester avec le numéro `37220020391`
4. Vérifier les logs

### Performance Lente

1. Vérifier la connexion réseau
2. Augmenter le timeout
3. Mettre en cache les résultats
4. Utiliser le batch pour plusieurs comptes

### Erreur d'SSL

```php
// Désactiver SSL pour développement (non recommandé)
$flexcube->setSSLVerification(false);

// Mais toujours utiliser true en production!
```

---

## Roadmap / Améliorations Futures

- [ ] Support des webhooks Flexcube
- [ ] Synchronisation planifiée des comptes
- [ ] Dashboard de monitoring Flexcube
- [ ] Support de la pagination Flexcube
- [ ] Cache distribué (Redis)
- [ ] Audit trail des appels API

---

## Support

Pour les problèmes:
1. Vérifier la documentation Ecobank
2. Consulter les logs d'erreur
3. Tester avec le numéro de compte de démo
4. Contacter le support technique

---

## Historique des Modifications

- **18 Jan 2025** - Création initiale avec support Flexcube
