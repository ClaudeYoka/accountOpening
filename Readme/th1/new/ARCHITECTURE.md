# Architecture Complète du Système AO & KYC

## Table des Matières
1. [Vue d'ensemble](#vue-densemble)
2. [Architecture en couches](#architecture-en-couches)
3. [Diagrammes UML](#diagrammes-uml)
4. [Flux d'authentification](#flux-dauthentification)
5. [Base de données](#base-de-données)
6. [API & Requêtes](#api--requêtes)

---

## Vue d'ensemble

Le système **AO & KYC** est une application web PHP/MySQL pour la gestion:
- **Ouverture de comptes bancaires** (formulaires Ecobank)
- **Gestion des employés** et leurs rôles
- **Gestion des congés** (Leave Management)
- **Chat/Notifications** inter-utilisateurs
- **Authentification sécurisée** avec 2FA

### Technologies utilisées
- **Backend**: PHP 7.4+
- **Base de données**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Authentification**: Session PHP + 2FA (Google Authenticator)
- **Génération PDF**: DOMPDF
- **Email**: PHPMailer

---

## Architecture en Couches

```
┌─────────────────────────────────────────────────────────────┐
│                    COUCHE PRÉSENTATION (UI)                 │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐   │
│  │  Admin   │ │   CSO    │ │  Heads   │ │     ROA      │   │
│  │Dashboard │ │Dashboard │ │Dashboard │ │  Dashboard   │   │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘   │
└─────────────────────────────────────────────────────────────┘
         ↓ HTTP/HTTPS Requests avec Sessions
┌─────────────────────────────────────────────────────────────┐
│              COUCHE CONTRÔLE (Business Logic)               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  loginController.php  - Authentification & Sessions │   │
│  │  verify_2fa.php       - Vérification 2FA           │   │
│  │  Controllers spécifiques par module                 │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
         ↓ SQL Queries & PDO/MySQLi
┌─────────────────────────────────────────────────────────────┐
│           COUCHE DONNÉES (Data Access Layer)                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         config.php - Connexion à la BD               │  │
│  │  Database abstraction & Query builders               │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────────────────────────┐
│         COUCHE PERSISTANCE (MySQL Database)                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  accountopening_db / ecoleaves_db                    │  │
│  │  ├── tblemployees                                    │  │
│  │  ├── tblleave                                        │  │
│  │  ├── ecobank_form_submissions                        │  │
│  │  ├── tbl_logins                                      │  │
│  │  └── autres tables...                                │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Structure des dossiers

```
account_opening/
│
├── 📁 admin/                     # Module Administration
│   ├── admin_dashboard.php       # Tableau de bord Admin
│   ├── staff.php                 # Gestion des employés
│   ├── department.php            # Gestion des départements
│   ├── leave_type.php            # Configuration types de congés
│   ├── leaves.php                # Gestion des demandes de congés
│   ├── change_password.php       # Changement de mot de passe
│   └── includes/
│
├── 📁 cso/                       # Customer Service Officer
│   ├── index.php                 # Tableau de bord CSO
│   ├── ecobank_submissions_list.php
│   ├── ecobank_submission_view.php
│   ├── ecobank_submission_edit.php
│   ├── save_ecobank_form.php
│   ├── rib.php
│   └── includes/
│
├── 📁 heads/                     # Department Heads
│   ├── index.php                 # Tableau de bord chef dept
│   ├── leaves.php                # Gestion congés équipe
│   ├── staff.php                 # Personnel du département
│   ├── leaveController.php       # Logique métier congés
│   └── includes/
│
├── 📁 roa/                       # Regional Operations Area
│   ├── index.php
│   └── ... (similaire structure)
│
├── 📁 includes/                  # Fichiers partagés
│   ├── config.php                # Connexion BD + constants
│   ├── session.php               # Gestion sessions
│   ├── loginController.php       # Authentification
│   ├── verify_2fa.php            # Vérification 2FA
│   ├── header.php                # En-tête HTML
│   ├── navbar.php                # Navigation
│   ├── footer.php                # Pied de page
│   └── scriptJs.php              # Scripts JS globaux
│
├── 📁 db/                        # Scripts SQL
│   ├── ecoleaves_db.sql
│   ├── create_tblCompte.sql
│   └── create_tutor_account_submissions.sql
│
├── 📁 vendors/                   # Frameworks & libraires
│   ├── styles/
│   ├── images/
│   └── scripts/
│
├── index.php                     # Page de connexion
├── logout.php
├── change_password.php
├── forgot-password.html
├── composer.json                 # Dépendances PHP
└── package.json                  # Dépendances Node.js
```

---

## Flux d'Authentification

### 1. Processus de Connexion (Login Flow)

```
Utilisateur remplit formulaire
         ↓
   index.php (formulaire)
         ↓
POST → loginController.php
         ↓
   Valide username/password
         ↓
MD5 ou password_hash vérification
         ↓
   ✓ Valide? Créer SESSION
         ✗ Invalide? Afficher erreur
         ↓
Récupérer rôle (Admin/cso/HOD/staff)
         ↓
Insérer dans tbl_logins (log)
         ↓
UPDATE tblemployees.status='Online'
         ↓
Redirection selon rôle:
├─ Admin    → admin/admin_dashboard
├─ cso      → cso/index
├─ HOD      → heads/index
└─ staff    → roa/index (ou autre)
         ↓
Session active: $_SESSION['alogin'] = emp_id
                $_SESSION['arole'] = role
                $_SESSION['adepart'] = department
```

### 2. Authentification à Deux Facteurs (2FA)

```
┌─ Session créée dans loginController
│
├─ Check: $row['twofa_enabled'] == 1?
│
├─ OUI → Rediriger vers formulaire 2FA
│  │
│  ├─ Afficher QR code / enter code
│  │
│  └─ verify_2fa.php
│     └─ Google Authenticator validation
│        └─ Si correct: session activée
│        └─ Si incorrect: rejeter
│
└─ NON → Session activée directement
```

### 3. Sécurisation des Sessions

```
session.php (inclus dans chaque page protégée)
         ↓
Vérifier: isset($_SESSION['alogin']) ?
         ↓
   ✓ OUI → Afficher contenu
   ✗ NON → Redirection index.php
         ↓
$session_id = $_SESSION['alogin']
$session_role = $_SESSION['arole']
```

---

## Base de Données

### Schéma Principal

```sql
accountopening_db/
├── tblemployees
│   ├── emp_id (PK)
│   ├── FirstName, LastName
│   ├── Username, Password
│   ├── Email, Phone
│   ├── Department (FK → tbldepartments)
│   ├── role (Admin/cso/HOD/staff)
│   ├── status (Online/Offline)
│   ├── twofa_secret (Google Auth)
│   ├── twofa_enabled (boolean)
│   └── password_changed
│
├── ecobank_form_submissions
│   ├── id (PK)
│   ├── customer_id
│   ├── account_number
│   ├── customer_name
│   ├── mobile, email
│   ├── form_data (JSON)
│   └── created_at
│
├── tblleave
│   ├── id (PK)
│   ├── emp_id (FK → tblemployees)
│   ├── LeaveType (FK)
│   ├── RequestedDays, DaysOutstand
│   ├── FromDate, ToDate
│   ├── Status (Pending/Approved/Rejected)
│   └── PostingDate
│
├── tbldepartments
│   ├── id (PK)
│   ├── DepartmentName
│   └── DepartmentShortName
│
├── tbl_logins (Audit trail)
│   ├── id (PK)
│   ├── emp_id (FK)
│   └── login_timestamp
│
└── tblleave_types
    ├── id (PK)
    └── Name (Annual/Sick/...)
```

---

## API & Requêtes

### 1. Patterns de Requêtes HTTP

#### GET Requests (Lecture)
```http
GET /cso/ecobank_submissions_list?q=ACC001 HTTP/1.1
GET /admin/staff?status=Active HTTP/1.1
GET /heads/leaves?month=01&year=2025 HTTP/1.1
```

#### POST Requests (Écriture)
```http
POST /includes/loginController.php HTTP/1.1
Content-Type: application/x-www-form-urlencoded

username=admin&password=pass123&signin=1

---

POST /cso/save_ecobank_form.php HTTP/1.1
Content-Type: application/json

{
  "account_number": "1234567890",
  "customer_name": "Jean Dupont",
  "customer_id": "ID123",
  "mobile": "+237699000000",
  "email": "jean@example.com"
}
```

### 2. Structure des Réponses

#### Succès
```php
// Avant redirection
header('Location: dashboard.php');
echo "<script>window.location='dashboard.php';</script>";

// Réponse JSON (API)
{
  "success": true,
  "message": "Opération réussie",
  "data": {
    "id": 123,
    "status": "approved"
  }
}
```

#### Erreur
```php
// Alert + Redirection
echo "<script>alert('Erreur: Identifiants invalides');</script>";

// JSON Response
{
  "success": false,
  "error": "Unauthorized",
  "message": "Votre session a expiré"
}
```

### 3. Points d'API Internes

| Endpoint | Méthode | Authentification | Retour |
|----------|---------|-----------------|--------|
| `loginController.php` | POST | Non | Redirection |
| `verify_2fa.php` | POST | Session (partielle) | JSON/Redirection |
| `logout.php` | GET | Oui | Redirection |
| `cso/save_ecobank_form.php` | POST | Oui (cso) | JSON/Redirection |
| `cso/ecobank_submission_edit.php` | POST | Oui (cso) | JSON/Redirection |
| `admin/add_staff.php` | POST | Oui (admin) | Redirection |
| `heads/leaveController.php` | POST | Oui (HOD) | JSON |
| `check_new_notifications.php` | GET | Oui | JSON |

### 4. Headers HTTPS/Sécurité

```php
// À ajouter dans config.php pour sécurisation
// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000');

// CORS (si API)
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
```

---

## Diagrammes UML

### Diagramme de Cas d'Utilisation

```
                                    ┌─────────────────────┐
                                    │   ACTEURS/RÔLES     │
                                    ├─────────────────────┤
                                    │ • Admin             │
                                    │ • CSO               │
                                    │ • Department Head   │
                                    │ • Staff             │
                                    └─────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│                      SYSTÈME AO & KYC                                │
│                                                                      │
│  Admin        ┌─────────────────────────────────────────┐            │
│    │          │  • Gérer les employés                   │            │
│    │          │  • Configurer les départements          │            │
│    │          │  • Approuver les congés                 │ CSO        │
│    │          │  • Voir les rapports                    │  │         │
│    │          │  • Gérer les types de congés            │  │         │
│    │          │  • Modifier identifiants                │  │         │
│    └──────────┤                                         │──┤         │
│               │  • Créer comptes Ecobank                │  │         │
│               │  • Consulter liste soumissions          │  │         │
│    ┌──────────┤  • Valider formulaires                  ├──┤         │
│    │          │  • Générer RIB                          │  │         │
│    │          │                                         │  │         │
│    │ HOD      │  • Approuver congés équipe              │  │         │
│    │    │     │  • Voir staff du département            │  │         │
│    │    │     │  • Permissions                          │  │         │
│    │    └────────────────────────────────────────────┘  │         │
│    │                                                    │         │
│    │ Staff    ┌─────────────────────────────────────┐  │         │
│    │    │     │  • Demander congé                   │  │         │
│    │    │     │  • Voir historique congés           │  │         │
│    │    │     │  • Modifier profil                  │  │         │
│    │    │     │  • Chat/Notifications               │  │         │
│    │    └────────────────────────────────────────┘  │         │
│    │                                                │         │
│    └────────────────────────────────────────────────┘         │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Diagramme de Séquence - Processus Login & 2FA

```
Utilisateur  index.php   loginController   BD(MySQL)   verify_2fa   Session
    │            │              │             │            │           │
    │ 1.Saisir   │              │             │            │           │
    │────────────>              │             │            │           │
    │            │ 2.POST       │             │            │           │
    │            │  username/pwd │            │            │           │
    │            │──────────────>             │            │           │
    │            │              │ 3.Query    │            │           │
    │            │              │ SELECT emp │            │           │
    │            │              │ WHERE user │            │           │
    │            │              │───────────>             │           │
    │            │              │            │ 4.Return  │            │
    │            │              │            │  rows     │            │
    │            │              │<───────────            │           │
    │            │              │            │            │           │
    │            │              │ 5.Vérifier            │           │
    │            │              │ pwd MD5 ou            │           │
    │            │              │ password_hash        │           │
    │            │              │            │            │           │
    │            │ 6.2FA enabled?            │            │           │
    │            │<──────────────            │            │           │
    │            │              │            │            │           │
    │ 7.OUI: Afficher form 2FA  │            │            │           │
    │<────────────              │            │            │           │
    │            │              │            │            │           │
    │ 8.Saisir code 2FA         │            │            │           │
    │────────────>              │            │            │           │
    │            │ 9.POST code  │            │            │           │
    │            │──────────────────────────────────────>             │
    │            │              │            │            │ 10.Valider│
    │            │              │            │            │ (Google   │
    │            │              │            │            │  Auth)    │
    │            │              │            │            │           │
    │            │              │ 11.UPDATE status       │           │
    │            │              │────────────>           │           │
    │            │              │            │ 12.OK     │           │
    │            │              │<───────────            │           │
    │            │ 13.Créer SESSION                       │           │
    │            │─────────────────────────────────────────────────────>
    │            │              │            │            │           │
    │ 14.Redirect dashboard      │            │            │           │
    │<──────────────             │            │            │           │
    │            │              │            │            │           │
```

### Diagramme de Classes

```
┌─────────────────────────────┐
│       Employee (BD)         │
├─────────────────────────────┤
│ - emp_id: INT (PK)          │
│ - FirstName: VARCHAR        │
│ - LastName: VARCHAR         │
│ - Username: VARCHAR         │
│ - Password: VARCHAR         │
│ - Email: VARCHAR            │
│ - Phone: CHAR               │
│ - Department: VARCHAR (FK)  │
│ - role: VARCHAR             │
│ - status: VARCHAR           │
│ - twofa_secret: VARCHAR     │
│ - twofa_enabled: BOOLEAN    │
├─────────────────────────────┤
│ + login(): bool             │
│ + changePassword(): bool    │
│ + enableTwoFA(): void       │
│ + logoutSession(): void     │
└─────────────────────────────┘
         △  △  △
         │  │  │
┌────────┴──┴──┴─────────┐
│                        │
│  Role Hierarchy        │
│                        │
├─ Admin                 │
├─ CSO                   │
├─ DepartmentHead (HOD)  │
└─ Staff                 │


┌─────────────────────────────┐
│  EcobankSubmission (BD)     │
├─────────────────────────────┤
│ - id: INT (PK)              │
│ - customer_id: VARCHAR      │
│ - account_number: VARCHAR   │
│ - customer_name: VARCHAR    │
│ - mobile: VARCHAR           │
│ - email: VARCHAR            │
│ - form_data: JSON           │
│ - created_at: TIMESTAMP     │
├─────────────────────────────┤
│ + create(): INT             │
│ + update(): bool            │
│ + view(): array             │
│ + generateRIB(): PDF        │
│ + approve(): bool           │
└─────────────────────────────┘


┌─────────────────────────────┐
│   LeaveRequest (BD)         │
├─────────────────────────────┤
│ - id: INT (PK)              │
│ - emp_id: INT (FK)          │
│ - LeaveType: VARCHAR        │
│ - RequestedDays: INT        │
│ - FromDate: DATE            │
│ - ToDate: DATE              │
│ - status: VARCHAR           │
│ - PostingDate: TIMESTAMP    │
├─────────────────────────────┤
│ + requestLeave(): INT       │
│ + approveLeave(): bool      │
│ + rejectLeave(): bool       │
│ + calculateBalance(): INT   │
└─────────────────────────────┘
         △
         │ (1...*)
         │
┌─────────────────────────────┐
│    Department (BD)          │
├─────────────────────────────┤
│ - id: INT (PK)              │
│ - DepartmentName: VARCHAR   │
│ - ShortName: VARCHAR        │
│ - CreationDate: TIMESTAMP   │
├─────────────────────────────┤
│ + create(): INT             │
│ + getStaff(): array         │
│ + delete(): bool            │
└─────────────────────────────┘
```

### Diagramme de Flux - Gestion des Congés

```
┌─ START
│
├─ Employé remplit formulaire congé
│  (FromDate, ToDate, LeaveType)
│
├─ INSERT INTO tblleave
│  └─ status = 'Pending'
│  └─ RequestedDays = calculé
│
├─ NOTIFICATION envoyée au HOD
│  └─ check_new_notifications.php
│  └─ notification_details.php
│
├─ HOD examine demande
│  ├─ Vérifie solde disponible
│  ├─ Vérifie chevauchements
│  └─ Décision:
│     ├─ APPROVE → leaveController.php
│     │  └─ UPDATE status = 'Approved'
│     │  └─ NOTIFICATION employé
│     │
│     └─ REJECT
│        └─ UPDATE status = 'Rejected'
│        └─ NOTIFICATION employé
│
├─ Si approuvé + montée: Admin voit aussi
│
├─ Employé voit historique
│  └─ myleave.php / leaves.php
│
└─ END
```

---

## Configuration Sécurité

### Bonnes pratiques implémentées

✅ **Authentification**
- Hachage des mots de passe (MD5 legacy → password_hash)
- Sessions PHP sécurisées
- 2FA optionnel (Google Authenticator)

✅ **Validation des données**
- `htmlspecialchars()` pour l'affichage
- `mysqli_real_escape_string()` pour SQL
- Type casting: `(int)$r['id']`

⚠️ **À améliorer**
- Utiliser PDO prepared statements
- CSRF tokens sur formulaires
- Rate limiting sur login
- HTTPS obligatoire
- Logs d'audit complets

### Fichiers clés de sécurité

```php
// config.php - Connexion
define('DB_HOST','localhost');
define('DB_USER','admin');
define('DB_PASS','Passw@rd');  // À externaliser!

// loginController.php - Authentification
if (md5($password) === $row['Password']) {
    // Upgrade vers password_hash
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
}

// session.php - Protection
if (!isset($_SESSION['alogin'])) {
    window.location = "../index.php";
}
```

---

## Métriques et Performance

### Requêtes optimisées

```sql
-- Liste soumissions (avec pagination recommandée)
SELECT id, customer_id, account_number, customer_name, 
       mobile, email, created_at 
FROM ecobank_form_submissions 
ORDER BY created_at DESC 
LIMIT 500;  -- À paginer

-- Congés avec infos employé
SELECT l.*, e.FirstName, e.LastName, e.Department
FROM tblleave l
JOIN tblemployees e ON l.emp_id = e.emp_id
WHERE l.status = 'Pending'
ORDER BY l.PostingDate DESC;
```

### Points de scalabilité

1. **Pagination**: Implémenter LIMIT/OFFSET
2. **Caching**: Redis pour sessions/données statiques
3. **Indices BD**: Ajouter sur emp_id, account_number
4. **API REST**: Découpler frontend/backend
5. **Microservices**: Service d'authentification séparé

---

## Déploiement

### Pré-requis
- PHP 7.4+ avec extensions: mysqli, PDO, OpenSSL
- MySQL 8.0+
- Serveur web: Apache/Nginx
- Certificat SSL/TLS pour HTTPS

### Variables d'environnement recommandées
```bash
DB_HOST=localhost
DB_USER=admin
DB_PASS=SecurePassword123!
DB_NAME=accountopening_db
APP_ENV=production
SESSION_TIMEOUT=1800
```

---

**Document généré**: 16 janvier 2026
**Version**: 1.0
**Système**: AO & KYC Account Opening Management System
