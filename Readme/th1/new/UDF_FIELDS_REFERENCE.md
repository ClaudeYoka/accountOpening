# Référence Complète des Champs UDF Flexcube

## Résumé

- **Total de champs supportés**: 30+
- **Catégories**: 8 (Personnelles, Contact, Localisation, Documents, Profession, Famille, Autres)
- **Champs obligatoires**: 4
- **Champs recommandés**: 15+

---

## 1. INFORMATIONS PERSONNELLES (7 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 1 | TITLE | Titre/Civilité (M/Mme/Dr) | STRING | `title` | civility, titre |
| 2 | FIRST_NAME | Prénom ⭐ | STRING | `first-name` | first_name, prenom, firstName |
| 3 | LAST_NAME | Nom de famille ⭐ | STRING | `last-name` | last_name, nom, lastName |
| 4 | MIDDLE_NAME | Deuxième prénom | STRING | `middle-name` | middle_name, prenom2 |
| 5 | SEX | Sexe (M/F) | STRING | `sex` | sexe, gender, genre |
| 6 | DATE_OF_BIRTH | Date de naissance | DATE | `date-of-birth` | dob, dateNaissance, date_naissance |
| 7 | NATIONALITY | Nationalité ⭐ | STRING | `nationality` | nationalite, citizenship, pays_origine |

**Notes**:
- TITLE: Valeurs: M, Mme, Dr, Me, Mr, Mrs, Ms
- SEX: Normalisé à M ou F
- DATE_OF_BIRTH: Format flexible (accepte YYYY-MM-DD, DD/MM/YYYY)

---

## 2. COORDONNÉES DE CONTACT (7 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 8 | CONTACT_ADDRESS | Adresse résidentielle ⭐ | STRING | `address` | adresse, residential-address, contact_address |
| 9 | EMAIL | Adresse email | STRING | `email` | adresse_email, email_address, mail |
| 10 | PHONE | Numéro principal | STRING | `telephone` | phone, tel, phone_number, numero_telephone |
| 11 | TELEPHONE1 | Téléphone primaire | STRING | `telephone` | (même que PHONE) |
| 12 | TELEPHONE2 | Téléphone secondaire | STRING | `telephone2` | phone2, tel2, phone_number_2, numero_telephone2 |
| 13 | MOBILE | Numéro de mobile | STRING | `telephone` | (même que PHONE) |
| 14 | FAX | Numéro de fax | STRING | `fax` | fax_number |

**Notes**:
- EMAIL: Validé automatiquement
- PHONE/TELEPHONE1/MOBILE: Tous mappés à `telephone`
- Formats acceptés: +242 06 123 4567, 06 123 4567, +242-6-123-4567
- TELEPHONE2 est distinct et permet 2 numéros

---

## 3. LOCALISATION (5 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 15 | COUNTRY | Pays de résidence | STRING | `country` | pays, residence-country, residence_country, nation |
| 16 | CITY | Ville | STRING | `city` | ville, town |
| 17 | STATE | Province/État | STRING | `state` | state, province, province_name |
| 18 | POSTAL_CODE | Code postal | STRING | `postal-code` | postal_code, zip_code, code_postal, zip |
| 19 | PLACE_OF_BIRTH | Lieu de naissance | STRING | `place-of-birth` | lieu_naiss, pob, birthplace |

**Notes**:
- COUNTRY: Codes ISO 2 lettres (CG, CM, US, FR, etc.)
- CITY/STATE: Texte libre
- POSTAL_CODE: Format flexible (accepte chiffres + tirets)

---

## 4. DOCUMENTS D'IDENTIFICATION (5 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 20 | ID_TYPE | Type d'identité | STRING | `id-type` | id_type, identification_type |
| 21 | ID_ISSUE_DATE | Date d'émission | DATE | `id-issue-date` | id_issue_date, issue_date |
| 22 | ID_EXP_DATE | Date d'expiration | DATE | `id-exp-date` | id_exp_date, exp_date, expiration_date |
| 23 | BVN | Numéro BVN | STRING | `bvn` | bvn_number |
| 24 | PASSPORT_NO | Numéro de passeport | STRING | `passport-number` | passport_number, passport_no |

**Notes**:
- ID_TYPE: Valeurs: NATIONAL_ID_CARD, PASSPORT, DRIVING_LICENSE, VOTER_ID
- Dates: Format flexible
- BVN: Numéro de Bankers Verification Number (Nigeria/West Africa)

---

## 5. INFORMATIONS PROFESSIONNELLES (3 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 25 | EMPLOYER_NAME | Nom de l'employeur | STRING | `employer` | employer-name, employer_name, company, societe |
| 26 | OCCUPATION | Profession/Métier | STRING | `occupation` | occupation, profession, job, metier |
| 27 | BUSINESS_SECTOR | Secteur d'activité | STRING | `business-sector` | business_sector, sector, industrie |

**Notes**:
- OCCUPATION: Libre (Engineer, Teacher, Doctor, Business Owner, etc.)
- BUSINESS_SECTOR: Secteur économique

---

## 6. INFORMATIONS FAMILIALES (4 champs)

| # | UDF Name | Description | Type | Form ID | Alias |
|----|----------|-------------|------|---------|-------|
| 28 | FATHER_NAME | Nom du père | STRING | `father-name` | father_name, nom_pere, pere |
| 29 | MOTHER_NAME | Nom de la mère | STRING | `mother-name` | mother_name, nom_mere, mere |
| 30 | SPOUSE_NAME | Nom du conjoint | STRING | `spouse-name` | spouse_name, nom_conjoint |
| 31 | MARITAL_STATUS | Statut marital | STRING | `marital-status` | marital_status, status_marital |

**Notes**:
- MARITAL_STATUS: Single, Married, Divorced, Widowed, Separated

---

## 7. INFORMATIONS DE COMPTE (Standard)

Ces champs ne sont pas des UDF mais font partie de la réponse standard.

| # | Field | Description | Type | Form ID |
|----|-------|-------------|------|---------|
| 32 | accountNo | Numéro de compte | STRING | `account-number` |
| 33 | accountName | Nom du titulaire | STRING | `account-name` |
| 34 | accountType | Type de compte (U, SAVINGS, etc) | STRING | `account-type` |
| 35 | currency | Devise (XAF, USD) | STRING | `currency` |
| 36 | branchCode | Code agence | STRING | `branch-code` |
| 37 | accountStatus | Statut (ACTIVE, BLOCKED) | STRING | `account-status` |
| 38 | customerID | ID Client | STRING | `customer-id` |
| 39 | availableBalance | Solde disponible | DECIMAL | `balance-available` |
| 40 | currentBalance | Solde actuel | DECIMAL | `balance-current` |

---

## Mappage Complet UDF → Form Fields

### Tableau de Référence Rapide

```
TITLE               → title, civility
FIRST_NAME          → first-name, first_name, prenom
LAST_NAME           → last-name, last_name, nom
MIDDLE_NAME         → middle-name, middle_name, prenom2
SEX                 → sex, sexe, gender
DATE_OF_BIRTH       → date-of-birth, dob, dateNaissance
NATIONALITY         → nationality, nationalite, citizenship
CONTACT_ADDRESS     → address, adresse, residential-address
EMAIL               → email, adresse_email, email_address
PHONE/TELEPHONE1    → telephone, phone, tel, numero_telephone
TELEPHONE2          → telephone2, phone2, tel2
MOBILE              → telephone, phone
COUNTRY             → country, pays, residence-country
CITY                → city, ville
STATE               → state, province
POSTAL_CODE         → postal-code, postal_code, zip_code
PLACE_OF_BIRTH      → place-of-birth, lieu_naiss, pob
ID_TYPE             → id-type, id_type
ID_ISSUE_DATE       → id-issue-date, id_issue_date
ID_EXP_DATE         → id-exp-date, id_exp_date
BVN                 → bvn, bvn_number
PASSPORT_NO         → passport-number, passport_number
EMPLOYER_NAME       → employer, employer-name, employeur
OCCUPATION          → occupation, profession, metier
BUSINESS_SECTOR     → business-sector, sector
FATHER_NAME         → father-name, nom_pere
MOTHER_NAME         → mother-name, nom_mere
SPOUSE_NAME         → spouse-name, nom_conjoint
MARITAL_STATUS      → marital-status, status_marital
```

---

## Données Exemple

### Réponse XML Complète

```xml
<?xml version="1.0"?>
<AccountDetailInfoResponse>
    <hostHeaderInfo>
        <sourceCode>ECOBANKWEB</sourceCode>
        <requestId>12345678</requestId>
        <responseCode>000</responseCode>
        <responseMessage>SUCCESS</responseMessage>
    </hostHeaderInfo>
    <AccountDetailInfo>
        <!-- Champs standard -->
        <accountNo>37220026306</accountNo>
        <accountName>Jean Dupont</accountName>
        <ccy>XAF</ccy>
        <branchCode>T32</branchCode>
        <accountStatus>ACTIVE</accountStatus>
        <customerID>370003189</customerID>
        <availableBalance>3303601.0</availableBalance>
        <currentBalance>3303601.0</currentBalance>
        
        <!-- UDF Fields -->
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
            <udfName>SEX</udfName>
            <udfValue>M</udfValue>
        </UDFData>
        <UDFData>
            <udfName>DATE_OF_BIRTH</udfName>
            <udfValue>1980-05-15</udfValue>
        </UDFData>
        <UDFData>
            <udfName>NATIONALITY</udfName>
            <udfValue>CG</udfValue>
        </UDFData>
        <UDFData>
            <udfName>COUNTRY</udfName>
            <udfValue>CG</udfValue>
        </UDFData>
        <UDFData>
            <udfName>CONTACT_ADDRESS</udfName>
            <udfValue>123 Rue de la Paix, Brazzaville</udfValue>
        </UDFData>
        <UDFData>
            <udfName>CITY</udfName>
            <udfValue>Brazzaville</udfValue>
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
            <udfName>TELEPHONE2</udfName>
            <udfValue>+242 07 987 6543</udfValue>
        </UDFData>
        <UDFData>
            <udfName>OCCUPATION</udfName>
            <udfValue>Engineer</udfValue>
        </UDFData>
        <UDFData>
            <udfName>EMPLOYER_NAME</udfName>
            <udfValue>ABC Company</udfValue>
        </UDFData>
    </AccountDetailInfo>
</AccountDetailInfoResponse>
```

### Données Mappées JSON

```json
{
    "title": "M",
    "first-name": "Jean",
    "first_name": "Jean",
    "prenom": "Jean",
    "last-name": "Dupont",
    "last_name": "Dupont",
    "sex": "M",
    "sexe": "M",
    "date-of-birth": "1980-05-15",
    "dob": "1980-05-15",
    "nationality": "CG",
    "nationalite": "CG",
    "country": "CG",
    "pays": "CG",
    "city": "Brazzaville",
    "ville": "Brazzaville",
    "address": "123 Rue de la Paix, Brazzaville",
    "adresse": "123 Rue de la Paix, Brazzaville",
    "email": "jean.dupont@example.com",
    "adresse_email": "jean.dupont@example.com",
    "telephone": "+242 06 123 4567",
    "phone": "+242 06 123 4567",
    "telephone2": "+242 07 987 6543",
    "phone2": "+242 07 987 6543",
    "occupation": "Engineer",
    "profession": "Engineer",
    "employer": "ABC Company",
    "employer-name": "ABC Company"
}
```

---

## Validations et Normalisations

### Genre (SEX)

| Input | Sortie |
|-------|--------|
| M, MALE, Masculin, Man, H, Homme | M |
| F, FEMALE, Féminin, Woman, Femme | F |
| Autre | Retourné tel quel |

### Email

Validation utilisant `filter_var(..., FILTER_VALIDATE_EMAIL)`

Exemples valides:
- user@example.com ✓
- jean.dupont@company.co.uk ✓
- user+tag@domain.org ✓

Exemples invalides:
- plaintext ✗
- user@domain (pas de TLD) ✗
- @example.com ✗

### Téléphone

Minimum 5 chiffres après suppression des caractères de formatage

Formats acceptés:
- +242 06 123 4567 ✓
- 06 123 4567 ✓
- +242-6-123-4567 ✓
- 06123-4567 ✓

Rejettés:
- 123 (moins de 5 chiffres) ✗
- abc (pas de chiffres) ✗

### Date

Formats acceptés (autoconversion):
- YYYY-MM-DD (ISO) → affiche DD/MM/YYYY
- DD/MM/YYYY → stocke ISO
- MM/DD/YYYY (US)
- DD-MM-YYYY

---

## Cas Particuliers

### Données Cryptées

Si une `udfValue` contient "ENCRYPTED", elle est ignorée et non mappée.

Exemple:
```xml
<UDFData>
    <udfName>FIRST_NAME</udfName>
    <udfValue>ENCRYPTED DATA2874512</udfValue>
</UDFData>
```

Résultat: Ce champ n'apparaît pas dans `form_fields`

### Champs Manquants

Si un UDF n'est pas retourné par Flexcube, le champ formulaire correspondant reste vide.

Les champs optionnels peuvent être remplis manuellement par l'utilisateur.

### Champs Multiples

Si un même champ UDF a plusieurs aliases, tous reçoivent la même valeur:

```json
{
    "first-name": "Jean",
    "first_name": "Jean",
    "prenom": "Jean"
}
```

---

## Champs Obligatoires vs Optionnels

### Obligatoires ⭐ (4 champs)

Importants pour l'ouverture de compte:
1. FIRST_NAME - Prénom
2. LAST_NAME - Nom
3. NATIONALITY - Nationalité
4. CONTACT_ADDRESS - Adresse

### Fortement Recommandés (5 champs)

Très utiles pour la connexion et communication:
1. EMAIL - Pour notifications
2. PHONE - Pour contact primaire
3. COUNTRY - Pour localisation
4. SEX - Information personnelle
5. DATE_OF_BIRTH - Vérification âge

### Optionnels (20+ champs)

Complètent le profil mais pas strictement nécessaires:
- Adresse secondaire, codes postaux
- Numéros alternatifs
- Documents d'identité
- Informations professionnelles
- Données familiales

---

## Compatibilité Navigateur

Tous les champs sont supportés par:
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Internet Explorer: Remplissage basique (pas d'ES6)

---

## Fréquence de Mise à Jour

Les champs UDF de Flexcube peuvent être étendus à tout moment par Ecobank.

Pour ajouter un nouveau champ:
1. Ajouter à UDFDataMapper::$KNOWN_UDF_FIELDS
2. Ajouter le mappage dans mapUDFToFormFields()
3. Ajouter le champ formulaire HTML
4. Tester avec un compte contenant le nouveau champ

---

**Document créé**: 20 janvier 2026  
**Version**: 1.0  
**Total de champs**: 40+
