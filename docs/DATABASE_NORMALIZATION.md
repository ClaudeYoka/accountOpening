# Migration Base de Données - Normalisation Ecobank

## Problème Résolu

La table `ecobank_form_submissions` stockait les données de manière dénormalisée :
- **Colonnes séparées** pour chaque champ (customer_name, email, address, etc.)
- **Champ JSON `data`** contenant la même information en double
- **Violation des principes de normalisation** (répétition de données, difficulté de maintenance)

## Nouvelle Architecture Normalisée

### Tables Créées

#### 1. `customers` - Informations client de base
```sql
- id (PK)
- customer_id (unique)
- title, gender, first_name, middle_name, last_name
- father_name, mother_name
- date_of_birth, place_of_birth
- nationality, residence_country
- id_type, id_number, id_issue_date, id_expiry_date
- created_at, updated_at
```

#### 2. `customer_addresses` - Adresses des clients
```sql
- id (PK)
- customer_id (FK → customers.id)
- address_type ('primary', 'secondary')
- address, city, postal_code, bp, country
```

#### 3. `customer_contacts` - Contacts des clients
```sql
- id (PK)
- customer_id (FK → customers.id)
- contact_type ('telephone', 'mobile', 'email')
- contact_value
- is_primary (boolean)
```

#### 4. `customer_employment` - Informations professionnelles
```sql
- id (PK)
- customer_id (FK → customers.id)
- employment_type, employer_name, occupation, industry
- gross_income_range
- business_name, business_registration, business_kra_pin
- business_nature, turnover_range
- institution_name, student_id
```

#### 5. `accounts` - Comptes bancaires
```sql
- id (PK)
- account_number (unique)
- bank_account_number
- customer_id (FK → customers.id)
- account_type, type_compte, currency
- branch_code, account_officer, account_handler
- account_purpose, services, tax_registration
- date_open, approved_by, approved_signature
- status ('pending', 'approved', 'active', 'closed')
```

#### 6. `emergency_contacts` - Contacts d'urgence
```sql
- id (PK)
- customer_id (FK → customers.id)
- contact_name, relationship, contact_info
```

#### 7. `form_submissions` - Soumissions de formulaires
```sql
- id (PK)
- submission_id (référence vers ancienne table)
- customer_id (FK → customers.id)
- account_id (FK → accounts.id)
- form_type ('account_opening')
- status ('draft', 'submitted', 'processing', 'approved', 'rejected')
- submitted_by, submitted_at
- processed_by, processed_at, notes
```

## Migration Effectuée

### Script de Migration
- **Fichier** : `cso/migrate_normalize_db.php`
- **Action** : Migre automatiquement toutes les données existantes
- **Sauvegarde** : Crée `ecobank_form_submissions_backup` avant migration
- **Logs** : `migration_normalize_db.log`

### Données Migrées
1. **Customers** : Informations personnelles depuis JSON + colonnes
2. **Addresses** : Adresses depuis JSON
3. **Contacts** : Téléphones, mobiles, emails depuis JSON
4. **Employment** : Informations professionnelles depuis JSON
5. **Accounts** : Informations de compte depuis JSON + colonnes
6. **Emergency Contacts** : Contacts d'urgence depuis JSON
7. **Form Submissions** : Métadonnées des soumissions

## Code Mis à Jour

### 1. `includes/flexcube_helpers.php`
- ✅ `fetchAccountWithFallback()` : Utilise d'abord les tables normalisées, puis fallback legacy
- ✅ `getCustomerData()` : Nouvelle fonction pour récupérer données client complètes
- ✅ `searchAccounts()` : Nouvelle fonction de recherche dans tables normalisées

### 2. `cso/ecobank_submissions_list.php`
- ✅ Requête mise à jour pour utiliser `form_submissions`, `accounts`, `customers`
- ✅ Jointures appropriées pour récupérer toutes les données nécessaires

### 3. Compatibilité Maintenue
- **Fallback automatique** vers anciennes tables pendant transition
- **Pas de rupture** immédiate pour le code existant
- **Migration progressive** possible

## Avantages de la Normalisation

### ✅ Intégrité des Données
- **Pas de duplication** : Chaque information stockée une seule fois
- **Cohérence** : Mise à jour dans une table = cohérence partout
- **Contraintes FK** : Garantissent l'intégrité référentielle

### ✅ Performance
- **Requêtes optimisées** : Index sur colonnes fréquemment recherchées
- **Jointures efficaces** : Structure relationnelle normale
- **Pas de JSON parsing** : Accès direct aux colonnes

### ✅ Maintenabilité
- **Évolution facile** : Ajout de champs sans casser le schéma
- **Requêtes lisibles** : SQL standard au lieu de JSON_EXTRACT
- **Debugging** : Données visibles directement en base

### ✅ Évolutivité
- **Nouvelles entités** : Facilement ajoutables (ex: `customer_documents`)
- **Relations complexes** : Supportées nativement
- **Reporting** : Agrégations et analyses simplifiées

## Utilisation des Nouvelles Tables

### Récupérer un client complet
```php
$customer_data = getCustomerData($customer_id, $conn);
// Retourne : customer, addresses[], contacts[], employment, emergency_contact, account
```

### Rechercher des comptes
```php
$criteria = ['customer_name' => 'John', 'status' => 'active'];
$accounts = searchAccounts($criteria, $conn, 50);
```

### Requête avec Flexcube fallback
```php
$result = fetchAccountWithFallback($account_number, $conn);
// Sources possibles : 'flexcube', 'normalized_db', 'legacy_db', 'tblcompte', 'not_found'
```

## Prochaines Étapes

### Phase 1 - Validation (Courante)
- [x] Migration des données existantes
- [x] Mise à jour des helpers principaux
- [x] Mise à jour des listes d'affichage
- [ ] Tests fonctionnels complets

### Phase 2 - Migration Complète
- [ ] Mise à jour de tous les fichiers utilisant `ecobank_form_submissions`
- [ ] Suppression du champ JSON `data`
- [ ] Suppression des colonnes redondantes
- [ ] Optimisation des index

### Phase 3 - Nouvelles Fonctionnalités
- [ ] API REST pour les nouvelles entités
- [ ] Interface d'administration des clients
- [ ] Système de documents joints
- [ ] Historique des modifications

## Fichiers Modifiés

### Core
- `cso/migrate_normalize_db.php` - Script de migration
- `includes/flexcube_helpers.php` - Helpers mis à jour

### Interfaces
- `cso/ecobank_submissions_list.php` - Liste utilisant nouvelles tables
- `admin/ecobank_submissions_list.php` - (À mettre à jour)

### Documentation
- `docs/DATABASE_NORMALIZATION.md` - Cette documentation

## Commandes de Migration

```bash
# Exécuter la migration
php cso/migrate_normalize_db.php

# Vérifier les logs
tail -f cso/migration_normalize_db.log

# Vérifier la sauvegarde
mysql -e "SELECT COUNT(*) FROM ecobank_form_submissions_backup"

# Vérifier les nouvelles données
mysql -e "SELECT COUNT(*) FROM customers"
mysql -e "SELECT COUNT(*) FROM accounts"
mysql -e "SELECT COUNT(*) FROM form_submissions"
```

## Rollback (si nécessaire)

```sql
-- Restaurer depuis la sauvegarde
DROP TABLE IF EXISTS ecobank_form_submissions;
ALTER TABLE ecobank_form_submissions_backup RENAME TO ecobank_form_submissions;

-- Supprimer les nouvelles tables
DROP TABLE IF EXISTS form_submissions;
DROP TABLE IF EXISTS emergency_contacts;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS customer_employment;
DROP TABLE IF EXISTS customer_contacts;
DROP TABLE IF EXISTS customer_addresses;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS migration_flags;
```

---

**Status** : Migration initiale terminée ✅
**Compatibilité** : Maintenue pendant transition
**Performance** : Améliorée pour les nouvelles données
**Évolutivité** : Prête pour extensions futures