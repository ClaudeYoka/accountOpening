# Système d'Auto-remplissage Formulaire Flexcube

## Vue d'ensemble

Cette intégration permet de récupérer automatiquement les informations d'un client depuis l'API Flexcube d'Ecobank et de pré-remplir le formulaire d'ouverture de compte.

## Fonctionnalités

✅ **Récupération des données** - Via l'API Flexcube  
✅ **Extraction UDF** - Tous les champs utilisateur personnalisés  
✅ **Mappage intelligent** - UDF → Champs formulaire  
✅ **Auto-remplissage** - Remplissage automatique du formulaire  
✅ **Gestion des erreurs** - Messages clairs et gestion des cas d'erreur  
✅ **Support multilingue** - Champs en français et anglais  
✅ **Validation** - Email et téléphone

## Architecture

```
┌─────────────────────────────────────────────────────┐
│  ecobank_account_form.php (HTML + JavaScript)       │
│  ├─ Formulaire d'ouverture de compte               │
│  └─ FormAutoFiller.js (remplissage côté client)    │
└──────────┬──────────────────────────────────────────┘
           │ POST (numéro de compte)
           ↓
┌──────────────────────────────────────────────────────┐
│  fetch_account_flexcube.php (API Gateway)            │
│  └─ mapFlexcubeDataToFormFields()                   │
└──────────┬───────────────────────────────────────────┘
           │ Appel API
           ↓
┌──────────────────────────────────────────────────────┐
│  FlexcubeAPI.php (Client API)                        │
│  ├─ buildXmlRequest()                               │
│  ├─ sendRequest()                                   │
│  └─ parseResponse()                                 │
└──────────┬───────────────────────────────────────────┘
           │ Parse XML
           ↓
┌──────────────────────────────────────────────────────┐
│  UDFDataMapper.php (Mapper)                          │
│  └─ mapUDFToFormFields()                            │
└──────────┬───────────────────────────────────────────┘
           │ JSON Response
           ↓
┌──────────────────────────────────────────────────────┐
│  FormAutoFiller.js (Remplissage)                     │
│  └─ autoFillForm()                                  │
└──────────────────────────────────────────────────────┘
```

## Fichiers

### Création

| Fichier | Description |
|---------|-------------|
| [UDFDataMapper.php](cso/includes/UDFDataMapper.php) | **NOUVEAU** - Mappe les champs UDF Flexcube vers les champs du formulaire |
| [form_auto_filler.js](vendors/js/form_auto_filler.js) | **NOUVEAU** - Classe JavaScript pour remplir automatiquement le formulaire |
| [FLEXCUBE_INTEGRATION_GUIDE.md](FLEXCUBE_INTEGRATION_GUIDE.md) | **NOUVEAU** - Guide complet d'intégration |
| [test_flexcube_integration.php](test_flexcube_integration.php) | **NOUVEAU** - Script de test |

### Modification

| Fichier | Changements |
|---------|-----------|
| [FlexcubeAPI.php](cso/includes/FlexcubeAPI.php) | Ajout de UDFDataMapper, amélioration de parseResponse() |
| [fetch_account_flexcube.php](cso/fetch_account_flexcube.php) | Amélioration de mapFlexcubeDataToFormFields() |
| [ecobank_account_form.php](cso/ecobank_account_form.php) | Amélioration de fillFormFromData(), ajout de FormAutoFiller |

## Utilisation

### 1. Recherche de Compte (Interface Utilisateur)

```
1. Ouvrir /cso/ecobank_account_form.php
2. Saisir le numéro de compte dans la barre de recherche
3. Cliquer sur "Chercher" ou appuyer sur Entrée
4. Le formulaire se remplit automatiquement
```

### 2. Flux Backend (Développeur)

#### a) Appel de l'API Flexcube

```php
// Depuis fetch_account_flexcube.php
$api = new FlexcubeAPI();
$response = $api->getAccountInfo($account_number);

// Réponse
[
    'success' => true,
    'data' => [
        'account_number' => '37220026306',
        'form_fields' => [
            'first-name' => 'Jean',
            'last-name' => 'Dupont',
            'email' => 'jean@example.com',
            'phone' => '+242 06 123 4567',
            'nationality' => 'CG',
            'sex' => 'M',
            'address' => '123 Rue de la Paix'
        ],
        'udf_raw' => [ /* Données brutes UDF */ ]
    ]
]
```

#### b) Mappage des Données UDF

```php
$mapped = UDFDataMapper::mapUDFToFormFields($udf_values);
```

#### c) Retour JSON au Client

```json
{
    "success": true,
    "source": "flexcube",
    "data": {
        "first-name": "Jean",
        "last-name": "Dupont",
        "email": "jean@example.com",
        "phone": "+242 06 123 4567",
        "nationality": "CG",
        "sex": "M",
        "address": "123 Rue de la Paix"
    }
}
```

### 3. Remplissage Frontend (JavaScript)

```javascript
// Charger les données depuis le serveur
fetch('fetch_account_flexcube.php', {
    method: 'POST',
    body: 'account=37220026306'
})
.then(r => r.json())
.then(json => {
    if (json.success) {
        // Remplir le formulaire
        FormAutoFiller.autoFillForm(json.data);
    }
});
```

## Champs Supportés

### Informations Personnelles

| Champ | ID Formulaire | Alternatives | Source |
|-------|---------------|--------------|--------|
| Prénom | `first-name` | first_name, prenom | FIRST_NAME |
| Nom | `last-name` | last_name, nom | LAST_NAME |
| Sexe | `sex` | sexe, gender | SEX |
| Nationalité | `nationality` | nationalite | NATIONALITY |
| Date de naissance | `date-of-birth` | dob, dateNaissance | DATE_OF_BIRTH |
| Titre | `title` | civility | TITLE |

### Coordonnées

| Champ | ID Formulaire | Alternatives | Source |
|-------|---------------|--------------|--------|
| Email | `email` | adresse_email | EMAIL |
| Téléphone 1 | `telephone` | phone, tel | PHONE, TELEPHONE1 |
| Téléphone 2 | `telephone2` | phone2, tel2 | TELEPHONE2, MOBILE |
| Adresse | `address` | adresse, contact_address | CONTACT_ADDRESS |

### Localisation

| Champ | ID Formulaire | Alternatives | Source |
|-------|---------------|--------------|--------|
| Pays | `country` | pays, residence-country | COUNTRY |
| Ville | `city` | ville | CITY |
| Code postal | `postal-code` | postal_code, zip_code | POSTAL_CODE |
| Lieu de naissance | `place-of-birth` | lieu_naiss, pob | PLACE_OF_BIRTH |

## Tests

### Test Local

```bash
# Accéder au script de test
http://localhost:8000/test_flexcube_integration.php

# Ou en ligne de commande
php test_flexcube_integration.php
```

### Test API Flexcube

```bash
# Tester la connexion
curl -X POST https://devtuat.ecobank.com/accountenquiryserviceaffiliate/accountenquiry/ecomobileflex/getaccountinfo \
  -H "Content-Type: application/xml" \
  -d @request.xml
```

### Test Formulaire

1. Ouvrir `ecobank_account_form.php`
2. Ouvrir la console du navigateur (F12)
3. Saisir un numéro de compte valide
4. Vérifier que les données s'affichent en console

## Configuration

### Variables de Configuration

[FlexcubeAPI.php](cso/includes/FlexcubeAPI.php) (lignes 16-18)

```php
private $api_url = 'https://devtuat.ecobank.com/...';  // URL de l'API
private $timeout = 30;                                  // Timeout (secondes)
private $verify_ssl = false;                            // SSL verification (false en dev)
```

### Variables de Configuration (Production)

```php
// À mettre dans config.php ou .env
define('FLEXCUBE_API_URL', 'https://api.ecobank.com/...');
define('FLEXCUBE_SOURCE_CODE', 'ECOBANKWEB');
define('FLEXCUBE_AFFILIATE_CODE', 'ECG');
define('FLEXCUBE_VERIFY_SSL', true);
```

## Données Exemple

### Réponse Flexcube Complète

```xml
<?xml version="1.0"?>
<AccountDetailInfoResponse>
    <hostHeaderInfo>
        <sourceCode>ECOBANKWEB</sourceCode>
        <requestId>78243348</requestId>
        <responseCode>000</responseCode>
        <responseMessage>SUCCESS</responseMessage>
    </hostHeaderInfo>
    <AccountDetailInfo>
        <accountNo>37220026306</accountNo>
        <accountName>JEAN DUPONT</accountName>
        <ccy>XAF</ccy>
        <branchCode>T32</branchCode>
        <accountStatus>ACTIVE</accountStatus>
        <customerID>370003189</customerID>
        
        <UDFData>
            <udfName>TITLE</udfName>
            <udfValue>M</udfValue>
        </UDFData>
        <UDFData>
            <udfName>FIRST_NAME</udfName>
            <udfValue>Jean</udfValue>
        </UDFData>
        <UDFData>
            <udfName>LAST_NAME</udfName>
            <udfValue>Dupont</udfValue>
        </UDFData>
        <UDFData>
            <udfName>EMAIL</udfName>
            <udfValue>jean.dupont@example.com</udfValue>
        </UDFData>
        <UDFData>
            <udfName>PHONE</udfName>
            <udfValue>+242 06 123 4567</udfValue>
        </UDFData>
        <UDFData>
            <udfName>NATIONALITY</udfName>
            <udfValue>CG</udfValue>
        </UDFData>
    </AccountDetailInfo>
</AccountDetailInfoResponse>
```

### Données Retournées au Client

```json
{
    "success": true,
    "source": "flexcube",
    "data": {
        "first-name": "Jean",
        "first_name": "Jean",
        "prenom": "Jean",
        "last-name": "Dupont",
        "last_name": "Dupont",
        "nom": "Dupont",
        "email": "jean.dupont@example.com",
        "adresse_email": "jean.dupont@example.com",
        "phone": "+242 06 123 4567",
        "telephone": "+242 06 123 4567",
        "numero_telephone": "+242 06 123 4567",
        "sex": "M",
        "sexe": "M",
        "nationality": "CG",
        "nationalite": "CG",
        "title": "M"
    }
}
```

## Gestion des Erreurs

### Messages d'Erreur

| Erreur | Cause | Solution |
|--------|-------|----------|
| "Numéro de compte manquant" | Input vide | Saisir un numéro de compte |
| "Format invalide" | Pas 10-20 chiffres | Vérifier le format du numéro |
| "Compte introuvable" | Pas trouvé dans API | Vérifier le numéro |
| "Erreur API" | Response code ≠ 000 | Contacter le support Flexcube |
| "Timeout" | Pas de réponse en 30s | Réessayer ou contacter le support |
| "SSL Error" | Certificat SSL invalide | Vérifier la configuration SSL |

### Debugging

Activer le mode debug dans `FormAutoFiller`:

```javascript
FormAutoFiller.autoFillForm(data, { debug: true });
```

Cela affichera tous les champs remplis dans la console du navigateur.

## Performances

- **Cache API**: 1 heure (configurable dans FlexcubeAPI.php)
- **Timeout**: 30 secondes
- **Taille réponse**: ~2-3 KB

## Sécurité

⚠️ **Important**: Les données de l'API Flexcube peuvent contenir des informations sensibles.

- ✅ HTTPS obligatoire en production
- ✅ Validation des entrées (numéro de compte)
- ✅ Authentification requise (session utilisateur)
- ✅ Données cryptées en transit
- ✅ Pas de stockage de données sensibles en localStorage

## Optimisations Futures

- [ ] Caching côté client (localStorage)
- [ ] Historique des comptes recherchés
- [ ] Pagination pour recherche multiple
- [ ] Export des données remplies
- [ ] Import depuis fichier
- [ ] Support de codes QR
- [ ] Webhook pour notifications
- [ ] Batch processing (multiple comptes)

## Support

### Documentation

- [FLEXCUBE_INTEGRATION_GUIDE.md](FLEXCUBE_INTEGRATION_GUIDE.md) - Guide complet
- [test_flexcube_integration.php](test_flexcube_integration.php) - Tests
- [form_auto_filler.js](vendors/js/form_auto_filler.js) - JSDoc

### Debugging

1. Vérifier la console du navigateur (F12)
2. Vérifier les logs du serveur
3. Exécuter `test_flexcube_integration.php`
4. Vérifier la connexion API avec curl

### Contact

Pour les problèmes:
- Vérifier les champs UDF supportés
- Vérifier le format du numéro de compte
- Vérifier la configuration API
- Contacter l'administrateur système

## Changelog

### v1.0 (20 janvier 2026)

- ✅ Création UDFDataMapper.php
- ✅ Création FormAutoFiller.js
- ✅ Modification FlexcubeAPI.php
- ✅ Modification fetch_account_flexcube.php
- ✅ Modification ecobank_account_form.php
- ✅ Documentation complète

---

**Version**: 1.0  
**Dernière mise à jour**: 20 janvier 2026  
**Auteur**: Ecobank Development Team
