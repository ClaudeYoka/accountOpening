# 📊 ARCHITECTURE COMPLÈTE - Projet Account Opening & KYC

**Date d'analyse:** 5 Avril 2026  
**Status:** Prêt pré-production  
**Dernière version PHP:** 8.1  

---

## 🏗️ ARCHITECTURE GÉNÉRALE DU PROJET

```
┌─────────────────────────────────────────────────────────────────┐
│                        COUCHE PRÉSENTATION                       │
│  ┌──────────────────┬──────────────────┬──────────────────┐      │
│  │   CSO Module     │   ADMIN Module   │    CI Module     │      │
│  │ (Clients/Tiers)  │ (Management)     │ (Back-Office)    │      │
│  └──────────────────┴──────────────────┴──────────────────┘      │
└─────────────────────────────────────────────────────────────────┘
                              ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                     COUCHE APPLICATIF                            │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ • Session & Authentication (2FA)                         │   │
│  │ • Audit Logging & Compliance                             │   │
│  │ • Flexcube API Integration (Banque)                       │   │
│  │ • File Upload & PDF Generation                           │   │
│  │ • QR Code & OTP Management                               │   │
│  │ • Data Validation & Error Handling                        │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                     COUCHE DONNÉES                               │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │   MySQL 8.0 Database (Docker)                           │    │
│  │  • Comptes clients (tblcompte)                          │    │
│  │  • Employés (tblemployees)                              │    │
│  │  • Formulaires Ecobank (ecobank_form_submissions)       │    │
│  │  • Messages internes (tbl_message)                      │    │
│  │  • Logs audit (audit_logs)                              │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              ↓↓↓
┌─────────────────────────────────────────────────────────────────┐
│                    PILE DE MONITORING                            │
│  ┌──────────┬──────────────┬─────────────┬─────────────┐        │
│  │ Prometheus│ Node Exporter│ MySQL Exp  │   Grafana   │        │
│  └──────────┴──────────────┴─────────────┴─────────────┘        │
│  Scrape interval: 15s | Data retention: 200h                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    INTÉGRATIONS EXTERNES                         │
│  ┌──────────────────────────────────────────────────────┐       │
│  │ Flexcube API (API Bancaire) ← → Synchro comptes     │       │
│  │ SMTP Email (PHPMailer) ← → Notifications            │       │
│  └──────────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📂 STRUCTURE DES MODULES PRINCIPAUX

### 🔵 **MODULE CSO** (c:\account_opening\cso\)
**Rôle:** Interface pour les clients externes et tiers (officers de relation client)

| Fichier | Fonction |
|---------|----------|
| **ecobank_account_form.php** | Formulaire ouverture compte Ecobank - 4 sections |
| **ecobank_submissions_list.php** | Liste des soumissions (CRUD) |
| **ecobank_submission_edit.php** | Édition/modification des soumissions |
| **ecobank_submission_view.php** | Consultation détaillée des dossiers |
| **save_ecobank_form.php** | Backend save formulaire + validation |
| **fetch_account_flexcube.php** | Fetchs données depuis l'API Flexcube |
| **demande_chequier.php** | Demande chéquier pour clients |
| **demande_chequier_directe.php** | Demande chéquier directe (accélérée) |
| **save_chequier_directe.php** | Sauvegarde demande chéquier directe |
| **rib.php** / **rib_ecobank.html** | Génération/consultation RIB (Relevé d'Identité Bancaire) |
| **rib_lookup.php** | Recherche RIB dans la BD |
| **formulaire_bic_pysique.php** | Formulaire pour personnes physiques |
| **formulaire_produits.php** | Choix des produits financiers |
| **formulaire_ouverture_compte_tuteur.html** | Formulaire comptes tuteur |
| **save_tutor_account.php** | Sauvegarde comptes tuteur |
| **account_part.php** | Section comptes partenaires |
| **search_compte.php** | Recherche comptes |
| **staff_profile.php** | Profil de l'employé CSO |
| **FLEXCUBE_ENDPOINTS.php** | Configuration endpoints Flexcube |
| **flexcube_config.template.php** | Template configuration Flexcube |
| **migrate_ecobank_form_columns.php** | Migration BD formules |
| **ensure_ecobank_columns.php** | Vérification colonnes BD |
| **change_password.php** | Changement mot de passe |
| **index.php** | Dashboard CSO accueil |

**Tableaux principaux utilisés:**
- `ecobank_form_submissions` → Formulaires soumis
- `tblcompte` → Comptes ouverts
- `tblemployees` → Employés

---

### 🟠 **MODULE ADMIN** (c:\account_opening\admin\)
**Rôle:** Gestion administrative système, monitoring, audit, sécurité

| Fichier | Fonction |
|---------|----------|
| **monitoring.php** | Tableau bord monitoring général |
| **monitoring_overview.php** | Vue d'ensemble santé système |
| **monitoring_system.php** | Métriques système (CPU, RAM, Disk) |
| **monitoring_notification.php** | Alertes/notifications sistema |
| **monitoring_security.php** | Dashboard sécurité (failed logins, etc) |
| **monitoring_grafana.php** | Panel iframe vers Grafana |
| **audit_logs.php** | Historique audit complet |
| **staff.php** | Gestion employés (CRUD) |
| **add_staff.php** | Ajouter nouvel employé |
| **edit_staff.php** | Editer employé |
| **agence.php** | Gestion des agences |
| **edit_agence.php** | Éditer agence |
| **department.php** | Gestion départements |
| **edit_department.php** | Éditer département |
| **demande_chequier.php** | Validation demandes chéquier |
| **mark_chequier_processed.php** | Marquer chéquier comme traité |
| **update_chequier_status.php** | Mise à jour statut chéquier |
| **liste_chequier.php** | Liste complète des chéquiers |
| **export_chequier_excel.php** | Export chéquiers en Excel |
| **export_chequier_xlsx.php** | Export chéquiers en XLSX |
| **generate_chequier_delivery.php** | Générer bon de livraison |
| **ecobank_submissions_list.php** | Supervision soumissions Ecobank |
| **ecobank_submission_edit.php** | Correction/validation soumissions |
| **ecobank_submission_view.php** | Visualisation dossiers Ecobank |
| **get_notifications.php** | Récupération notifications système |
| **mark_notification_read.php** | Marquer notification comme lue |
| **notification_details.php** | Détails complets notification |
| **notification_details_new.php** | Nouveau format details notif |
| **my_profile.php** | Profil administrateur |
| **change_password.php** | Changement password admin |
| **report_pdf.php** | Génération rapports PDF |
| **index.php** | Dashboard admin principal |

**Sécurité spécifique:**
- Audit logging automatique (`audit_logger.php`)
- Tracking toutes les actions sensitives
- 2FA requis pour accès

---

### 🟢 **MODULE CI** (c:\account_opening\ci\)
**Rôle:** Back-office interne - Customer Integration (traitement des demandes)

| Fichier | Fonction |
|---------|----------|
| **index.php** | Dashboard CI - Vue d'ensemble agences |
| **staff.php** | Gestion staff internes (différent de admin) |
| **add_staff.php** | Ajouter staff interne |
| **edit_staff.php** | Éditer staff interne |
| **agence.php** | Gestion des agences par CI |
| **demande_chequier.php** | Traitement demandes chéquier |
| **mark_chequier_processed.php** | Validation chéquier traité |
| **update_chequier_status.php** | Changement status chéquier |
| **historique_demande_chequier.php** | Historique des demandes |
| **get_chequier_details.php** | Récupération détails chéquier |
| **department.php** | Gestion dépôts/departments |
| **edit_department.php** | Éditer department |
| **export_chequier_excel.php** | Export données chéquier |
| **export_chequier_xlsx.php** | Export en format XLSX |
| **generate_chequier_delivery.php** | Bon de livraison chéquier |
| **get_notifications.php** | Notifs CI |
| **mark_notification_read.php** | Marquer notif lue |
| **my_profile.php** | Profil user CI |
| **notification_details.php** | Détails notification |
| **report_pdf.php** | Rapports PDF (ex: historique) |
| **change_password.php** | Changement password |

**Caractéristiques:**
- Pas d'audit logging aussi strict que admin
- Focus sur traitement processus chequier
- Gestion d'agences multiples

---

## 🔧 FICHIERS SYSTEME CLÉS (Racine)

### 🐳 **DOCKER & CONTENEURISATION**

| Fichier | Rôle |
|---------|------|
| **Dockerfile** | Image PHP 8.1 Apache avec extensions MySQL |
| **docker-compose.yml** | Orchestration complète (web, bd, monitoring) |
| **docker-compose.override.yml** | Surcharges environnement local/dev |

**Services Docker lancés:**
- `web` → Application PHP (port 8080)
- `db` → MySQL 8.0 (port 3306)
- `prometheus` → Collecte métriques (port 9090)
- `grafana` → Visualisation (port 3000)
- `node-exporter` → Métriques système (port 9100)
- `mysql-exporter` → Métriques BD (port 9104)

---

### 📊 **MONITORING & MÉTRIQUES**

| Fichier | Fonction |
|---------|----------|
| **metrics.php** | Export métriques PHP (mémoire, CPU, disque) format Prometheus |
| **business_metrics.php** | Export métriques applicatives (utilisateurs, comptes, sessions) |
| **start-monitoring.bat** | Démarrage stack monitoring (Windows) |
| **start-monitoring.sh** | Démarrage stack monitoring (Linux/Mac) |
| **stop_monitoring.bat** | Arrêt stack monitoring (Windows) |
| **monitoring/prometheus.yml** | Configuration scrape jobs Prometheus |
| **monitoring/init_monitoring.sql** | Initialisation tables monitoring BD |
| **monitoring/grafana/** | Dashboards et provisioning Grafana |

**Métriques collectées:**

```
PHP Metrics:
  • php_memory_usage_bytes → Mémoire utilisée
  • php_memory_peak_usage_bytes → Pic mémoire
  • system_load1/5/15 → Charge système
  • disk_free_bytes, disk_total_bytes, disk_usage_percent
  • http_requests_total → Nombre requêtes
  • http_request_duration_milliseconds → Latence

Business Metrics:
  • db_connection_status → Connectivité BD
  • app_total_users → Nombre utilisateurs
  • app_active_sessions → Sessions actives (dernière heure)
  • app_total_accounts → Comptes ouverts
  • app_pending_chequier_requests → Demandes en cours
  • app_completed_chequier_requests → Demandes complétées
  • app_form_submissions_today → Formulaires du jour
  • app_failed_logins_24h → Tentatives login échouées
  • app_successful_logins_24h → Connexions réussies
```

**Scrape Interval:** 15s (Prometheus), 30-60s (métiers)  
**Rétention données:** 200 heures

---

### 💬 **SYSTÈME DE MESSAGERIE INTERNE**

| Fichier | Fonction |
|---------|----------|
| **chat_loader.php** | Charge messages entre deux utilisateurs |
| **chatlog.php** | Enregistre nouveau message en BD |

**Table:** `tbl_message` (incoming_msg_id, outgoing_msg_id, text_message, curr_date/time)  
**Timezone:** Accra (GMT+0)

---

### 🔐 **SÉCURITÉ & AUTHENTIFICATION**

| Fichier | Fonction | Détails |
|---------|----------|---------|
| **includes/session.php** | Gestion sessions sécurisées | Headers sécurité, cookies HTTPOnly, CSRF tokens |
| **includes/loginController.php** | Authentification principale | Login/logout, hash passwords |
| **includes/verify_2fa.php** | Vérification 2 facteurs | Google Authenticator, TOTP |
| **includes/audit_logger.php** | Logging audit compliance | Enregistre actions critiques BD + fichier |
| **includes/audit_helpers.php** | Fonctions audit utilitaires | Log user mgmt, login tracking, etc |
| **includes/Validator.php** | Validation données utilisateur | Prévention injection SQL, XSS |
| **includes/error_handler.php** | Gestion centralisée erreurs | Logs fichier/BD, affichage sécurisé |
| **forgot-password.html** | Page réinitialisation password | Form HTML statique |

---

### 📝 **OUTILS & UTILITAIRES**

| Fichier | Fonction |
|---------|----------|
| **gulpfile.js** | Build automation (CSS/JS compilation) |
| **check_db.php** | Script diagnostic BD (test connexion, tables) |
| **update_passwords.php** | Batch update passwords |
| **logout.php** | Déconnexion sécurisée |
| **change_password.php** | Changement password utilisateur |
| **index.php** | Page d'accueil/login (redirection modules) |
| **app_uptime.txt** | Suivi uptime application |

---

## 📦 DÉPENDANCES PRINCIPALES

### PHP (Composer)
```json
{
  "phpmailer/phpmailer": "^6.10"          // Email sending
  "dompdf/dompdf": "^3.1"                 // PDF generation
  "sonata-project/google-authenticator": "^2.3"  // 2FA
  "phpoffice/phpspreadsheet": "^5.4"      // Excel export
  "spomky-labs/otphp": "^11.4"            // OTP management
  "endroid/qr-code": "^5.1"               // QR code generation
}
```

### Node.js
```json
{
  "chart.js": "^4.5.0"                    // Graphiques (Dashboard)
}
```

### Docker Images
- `php:8.1-apache` → Application
- `mysql:8.0` → Base de données
- `prom/prometheus:latest` → Monitoring
- `grafana/grafana:latest` → Visualisation
- `prom/node-exporter:latest` → Métriques système
- `prom/mysqld-exporter:latest` → Métriques BD

---

## 📊 STRUCTURE BASE DE DONNÉES PRINCIPALE

```sql
-- Clients & Comptes
tblcompte
  ├── id, num_compte, solde
  ├── client_id, date_ouverture
  ├── access (statut: 'encours', 'livré', etc)
  └── type_compte

-- Utilisateurs
tblemployees
  ├── emp_id, FirstName, LastName
  ├── email, phone, role, agence
  ├── password (hashed)
  └── is_2fa_enabled

-- Formulaires Ecobank
ecobank_form_submissions
  ├── id, created_at, updated_at
  ├── customer_info, account_type
  ├── document_attachments
  └── submission_status

-- Audit & Logs
audit_logs
  ├── id, user_id, action
  ├── table_affected, old_value, new_value
  ├── ip_address, user_agent
  └── timestamp

-- Messages
tbl_message
  ├── id, incoming_msg_id, outgoing_msg_id
  ├── text_message
  └── curr_date, curr_time

-- Logins
tbl_logins
  ├── id, emp_id, login_time
  └── logout_time (nullable)
```

---

## 🌐 FLUX DE DONNÉES PRINCIPAL

```
┌─────────────────────────────────────────────────────────┐
│ 1. UTILISATEUR ACCÈDE À CSO/ADMIN/CI                   │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 2. AUTHENTIFICATION (includes/loginController.php)      │
│    • Vérification credentials                            │
│    • 2FA (verify_2fa.php) si configuré                   │
│    • Création session sécurisée                          │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 3. AUDIT LOGGING (audit_logger.php)                     │
│    • Enregistrement login réussi/échoué                  │
│    • IP address, user agent                              │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 4. ACCÈS MODULE (CSO/Admin/CI)                          │
│    • Vérification permissions via role                   │
│    • Chargement dashboard/formulaire                     │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 5. MÉTIER (ex: Ouverture compte)                        │
│    • Validation données (Validator.php)                  │
│    • Upload fichiers si nécessaire                       │
│    • Appel API Flexcube si besoin                        │
│    • Sauvegarde BD                                       │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 6. LOGGING & NOTIFICATIONS                              │
│    • Enregistrement action audit                         │
│    • Notification utilisateurs/managers                  │
│    • Mise à jour métriques (business_metrics.php)        │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 7. RÉPONSE UTILISATEUR                                  │
│    • Confirmation/erreur                                │
│    • Redirection ou affichage données                    │
│    ↓                                                     │
├─────────────────────────────────────────────────────────┤
│ 8. MONITORING (Prometheus/Grafana)                      │
│    • Scrape métriques system/business                    │
│    • Mise à jour dashboards                              │
│    • Alertes si seuils dépassés                          │
└─────────────────────────────────────────────────────────┘
```

---

## 🎯 POINTS CLÉS D'INTÉGRATION

### 1️⃣ Flexcube API (Banque)
- **Endpoint Config:** `cso/FLEXCUBE_ENDPOINTS.php`
- **Usage:** Synchronisation comptes bancaires en temps réel
- **Fonction:** `includes/FlexcubeAPI.php`
- **Helpers:** `includes/flexcube_helpers.php`

### 2️⃣ Email (PHPMailer)
- Config SMTP dans `.env`
- Notifications automatiques
- Confirmation comptes, alertes

### 3️⃣ PDF Generation (DOMPDF)
- Rapports PDF
- Bons de livraison
- Impressions formulaires

### 4️⃣ Excel Export (PHPSpreadsheet)
- Export chéquiers
- Rapports data
- Migration BD

### 5️⃣ 2FA (Google Authenticator)
- TOTP (Time-based OTP)
- QR code generation
- Récupération codes

---

## ✅ RÉSUMÉ MODÈLES DE RÔLE DES MODULES

| Module | Utilisateurs | Fonction Métier | Audit |
|--------|--------------|-----------------|-------|
| **CSO** | Officers relation client, Tiers | Saisie demandes, Ouverture comptes, Chéquiers | Standard |
| **ADMIN** | Administrateurs système, Managers | Supervision, Monitoring, Gestion staff, Audit | **Strict** ⚠️ |
| **CI** | Back-office interne, Traitants | Traitement chéquiers, Historique | Standard |

---

## 🔄 FLOW DÉPLOIEMENT ACTUEL

1. **Développement local:** Laragon (PHP 8.1 + MySQL)
2. **Conteneurisation:** Docker Compose (web + db + monitoring)
3. **Monitoring Stack:** Prometheus (scrape) → Grafana (visualisation)
4. **Logs centralisés:** Fichiers + Base de données
5. **Backups:** Dossier `/Backup/`

---

*Documentation générée automatiquement - 5 Avril 2026*
