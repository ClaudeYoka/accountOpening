# Guide d'Intégration Flexcube API - Extraction de Données UDF

## Vue d'ensemble

Ce guide explique comment récupérer et remplir automatiquement le formulaire d'ouverture de compte avec les données de l'API Flexcube d'Ecobank.

## Structure de Réponse API Flexcube

L'API Flexcube retourne les données dans ce format XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<AccountDetailInfoResponse>
    <hostHeaderInfo>
        <sourceCode>ECOBANKWEB</sourceCode>
        <requestId>78243348</requestId>
        <affiliateCode>ECG</affiliateCode>
        <responseCode>000</responseCode>
        <responseMessage>SUCCESS</responseMessage>
    </hostHeaderInfo>
    <AccountDetailInfo>
        <!-- Champs de compte standard -->
        <accountNo>37220026306</accountNo>
        <accountName>ENCRYPTED DATA3435578</accountName>
        <ccy>XAF</ccy>
        <branchCode>T32</branchCode>
        <availableBalance>3303601.0</availableBalance>
        <currentBalance>3303601.0</currentBalance>
        <accountStatus>ACTIVE</accountStatus>
        <customerID>370003189</customerID>
        
        <!-- Champs UDF (User Defined Fields) -->
        <UDFData>
            <udfName>TITLE</udfName>
            <udfValue>M</udfValue>
        </UDFData>
        <UDFData>
            <udfName>FIRST_NAME</udfName>
            <udfValue>John</udfValue>
        </UDFData>
        <UDFData>
            <udfName>LAST_NAME</udfName>
            <udfValue>Doe</udfValue>
        </UDFData>
        <!-- ... plus de UDFData -->
    </AccountDetailInfo>
</AccountDetailInfoResponse>
```

## Champs UDF Supportés

### Informations Personnelles
| UDF Name | Description | Type | Exemple |
|----------|-------------|------|---------|
| TITLE | Titre/Civilité (M, Mme, Dr, etc) | STRING | M |
| FIRST_NAME | Prénom | STRING | Jean |
| LAST_NAME | Nom de famille | STRING | Dupont |
| MIDDLE_NAME | Deuxième prénom | STRING | Paul |
| SEX | Sexe (M/F) | STRING | M |
| DATE_OF_BIRTH | Date de naissance | DATE | 1986-03-21 |
| NATIONALITY | Nationalité | STRING | CG |
| MARITAL_STATUS | Statut marital | STRING | Single |

### Coordonnées de Contact
| UDF Name | Description | Exemple |
|----------|-------------|---------|
| CONTACT_ADDRESS | Adresse résidentielle | BP 1219 SC ECG |
| PHONE | Numéro de téléphone principal | +242 06 123 4567 |
| TELEPHONE1 | Téléphone primaire | +242 06 123 4567 |
| TELEPHONE2 | Téléphone secondaire | +242 07 123 4567 |
| MOBILE | Numéro de mobile | +242 05 123 4567 |
| EMAIL | Adresse email | user@example.com |
| FAX | Numéro de fax | +242 123 4567 |

### Localisation
| UDF Name | Description | Exemple |
|----------|-------------|---------|
| COUNTRY | Pays de résidence | CG |
| CITY | Ville | Brazzaville |
| STATE | Province/État | Région |
| POSTAL_CODE | Code postal | 123 |
| PLACE_OF_BIRTH | Lieu de naissance | Pointe-Noire |

### Documents d'Identification
| UDF Name | Description | Exemple |
|----------|-------------|---------|
| ID_TYPE | Type d'identité | NATIONAL_ID_CARD |
| ID_ISSUE_DATE | Date d'émission | 2020-01-15 |
| ID_EXP_DATE | Date d'expiration | 2030-01-15 |
| BVN | Numéro BVN | 1 |
| PASSPORT_NO | Numéro de passeport | AA123456 |

### Informations Professionnelles
| UDF Name | Description | Exemple |
|----------|-------------|---------|
| EMPLOYER_NAME | Nom de l'employeur | ABC Company |
| OCCUPATION | Profession | Engineer |
| BUSINESS_SECTOR | Secteur d'activité | Technology |

## Architecture de Traitement

### 1. Récupération des données (FlexcubeAPI.php)

```php
// Utilisation
$api = new FlexcubeAPI();
$response = $api->getAccountInfo('37220026306');

// Réponse
[
    'success' => true,
    'data' => [
        'account_number' => '37220026306',
        'account_name' => '...',
        'form_fields' => [  // Données mappées
            'first-name' => 'John',
            'last-name' => 'Doe',
            'email' => 'user@example.com',
            'phone' => '+242 06 123 4567',
            'nationality' => 'CG',
            'sex' => 'M'
        ],
        'udf_raw' => [  // Données brutes UDF
            'FIRST_NAME' => 'John',
            'LAST_NAME' => 'Doe',
            ...
        ]
    ]
]
```

### 2. Mappage des UDF (UDFDataMapper.php)

La classe `UDFDataMapper` mappe les champs UDF de Flexcube vers les IDs de champs du formulaire:

```php
$mapped = UDFDataMapper::mapUDFToFormFields($udf_values);
// Résultat:
[
    'first-name' => 'John',
    'first_name' => 'John',
    'prenom' => 'John',
    'last-name' => 'Doe',
    'last_name' => 'Doe',
    'email' => 'user@example.com',
    'telephone' => '+242 06 123 4567',
    'phone' => '+242 06 123 4567',
    'nationality' => 'CG',
    'nationalite' => 'CG',
    'sex' => 'M',
    'sexe' => 'M'
]
```

### 3. Remplissage du Formulaire (FormAutoFiller.js)

```javascript
// Utilisation
FormAutoFiller.autoFillForm(data);

// Ou avec des options
FormAutoFiller.autoFillForm(data, {
    form: document.querySelector('form'),
    debug: true
});
```

## Flux Complet d'Utilisation

### Côté Client (JavaScript)

1. L'utilisateur saisit le numéro de compte et clique sur "Chercher"
2. `handleAccountSearch()` est appelée
3. `fetchAccountFromFlexcube()` envoie une requête POST à `fetch_account_flexcube.php`

```javascript
function handleAccountSearch(accountNumber) {
    showLoader(true);
    
    fetchAccountFromFlexcube(accountNumber)
        .then(function(data) {
            fillFormFromData(data);
            showMessage('✓ Compte trouvé', false);
        })
        .catch(function(err) {
            showMessage('❌ ' + err.message, true);
        })
        .finally(function() {
            showLoader(false);
        });
}
```

### Côté Serveur (PHP)

1. `fetch_account_flexcube.php` reçoit le numéro de compte
2. Appelle `FlexcubeAPI->getAccountInfo()`
3. `UDFDataMapper` mappe les champs UDF
4. Retourne les données en JSON

```php
$api = new FlexcubeAPI();
$response = $api->getAccountInfo($account_number);

if ($response['success']) {
    $form_data = mapFlexcubeDataToFormFields($response['data']);
    
    echo json_encode([
        'success' => true,
        'source' => 'flexcube',
        'data' => $form_data
    ]);
}
```

## Champs Formulaire Correspondants

Les champs UDF sont mappés vers les IDs HTML du formulaire suivants:

| Champ UDF | ID Formulaire | Alternatives |
|-----------|---------------|----|
| FIRST_NAME | first-name | first_name, prenom, firstName |
| LAST_NAME | last-name | last_name, nom, lastName |
| SEX | sex | sexe, gender |
| NATIONALITY | nationality | nationalite, citizenship |
| CONTACT_ADDRESS | address | adresse, contact_address, residential-address |
| EMAIL | email | adresse_email, email_address |
| PHONE/TELEPHONE1 | telephone | phone, tel, numero_telephone, mobile |
| TELEPHONE2 | telephone2 | phone2, tel2, numero_telephone2 |
| COUNTRY | country | pays, residence-country, residence_country |
| TITLE | title | civility, civility_id |
| DATE_OF_BIRTH | date-of-birth | dob, dateNaissance |

## Gestion des Erreurs

### Cas d'Erreur Possibles

1. **Compte introuvable**
   - `responseCode` != '000'
   - Retourner HTTP 404

2. **Données cryptées**
   - Si `udfValue` contient "ENCRYPTED"
   - Le mapper saute ces champs
   - Laisser l'utilisateur les remplir manuellement

3. **Erreur de connexion API**
   - cURL error
   - Timeout (30s)
   - Retourner HTTP 500

4. **Format XML invalide**
   - LibXML parse error
   - Retourner HTTP 500

## Exemple Complet

### 1. Appel API

```bash
curl -X POST https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0"?>
<AccountDetailInfoRequest>
  <hostHeaderInfo>
    <sourceCode>ECOBANKWEB</sourceCode>
    <requestId>REQ-20240120142530-1234</requestId>
    <requestToken>REQ-20240120142530-1234</requestToken>
    <requestType>GETACCINFO</requestType>
    <affiliateCode>ECG</affiliateCode>
    <sourceChannelId>WEB</sourceChannelId>
  </hostHeaderInfo>
  <accountNo>37220026306</accountNo>
</AccountDetailInfoRequest>'
```

### 2. Traitement PHP

```php
<?php
include('includes/FlexcubeAPI.php');
include('includes/UDFDataMapper.php');

$api = new FlexcubeAPI();
$response = $api->getAccountInfo('37220026306');

if ($response['success']) {
    echo json_encode([
        'success' => true,
        'data' => $response['data']['form_fields'],
        'raw' => $response['data']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $response['error']
    ]);
}
```

### 3. Remplissage Formulaire

```javascript
fetch('fetch_account_flexcube.php', {
    method: 'POST',
    body: 'account=37220026306'
})
.then(r => r.json())
.then(json => {
    if (json.success) {
        FormAutoFiller.autoFillForm(json.data);
    }
});
```

## Optimisations Futures

### À Ajouter

1. **Cache local** - Mémoriser les requêtes récentes
2. **Validation de données** - Vérifier email, téléphone, etc.
3. **Formatage automatique** - Formater les dates, téléphones
4. **Support multilingue** - Labels en français/anglais
5. **Historique** - Garder les derniers comptes recherchés

### Champs Potentiels à Demander à Flexcube

- Images de documents
- Historique de compte
- Transactions récentes
- Limites de crédit
- Produits liés

## Support et Debugging

### Activer le debug

```javascript
FormAutoFiller.autoFillForm(data, { debug: true });
```

### Vérifier les champs disponibles

```php
$known_fields = UDFDataMapper::getKnownUDFFields();
print_r($known_fields);
```

### Valider une adresse email

```php
if (UDFDataMapper::isEmail($value)) {
    // C'est une email valide
}
```

### Valider un numéro de téléphone

```php
if (UDFDataMapper::isPhoneNumber($value)) {
    // C'est un numéro de téléphone
}
```

## Configuration Production

### Variables d'Environnement

```php
define('FLEXCUBE_API_URL', getenv('FLEXCUBE_API_URL'));
define('FLEXCUBE_SOURCE_CODE', getenv('FLEXCUBE_SOURCE_CODE') ?: 'ECOBANKWEB');
define('FLEXCUBE_AFFILIATE_CODE', getenv('FLEXCUBE_AFFILIATE_CODE') ?: 'ECG');
define('FLEXCUBE_VERIFY_SSL', getenv('FLEXCUBE_VERIFY_SSL') === 'true');
```

### .env Exemple

```
FLEXCUBE_API_URL=https://api.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo
FLEXCUBE_SOURCE_CODE=ECOBANKWEB
FLEXCUBE_AFFILIATE_CODE=ECG
FLEXCUBE_VERIFY_SSL=true
```

## Tests

### Test Connection

```php
$api = new FlexcubeAPI();
$test = $api->testConnection('37220020391');
echo json_encode($test);
```

### Batch Processing

```php
$accounts = ['37220026306', '37220020391', '37220025902'];
$results = $api->getMultipleAccounts($accounts);
```

---

**Dernière mise à jour:** 20 janvier 2026
**Version:** 1.0
