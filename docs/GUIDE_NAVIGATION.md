# 📁 GUIDE DE NAVIGATION - STRUCTURE COMPLÈTE DU PROJET

**Dernière mise à jour:** 5 Avril 2026

---

## 🗺️ ARBORESCENCE COMPLÈTE ANNOTÉE

```
account_opening/
│
├── 📄 FICHIERS RACINE (Système & Configuration)
│   ├── index.php                    ← Page d'accueil PRINCIPALE
│   ├── .env                         ← Configuration BD/Email [⚠️ À sécuriser]
│   ├── .htaccess                    ← Règles Apache rewrite
│   ├── docker-compose.yml           ← Orchestration services (web+db+monitoring)
│   ├── docker-compose.override.yml  ← Surcharges local/dev
│   ├── Dockerfile                   ← Image PHP 8.1 Apache
│   ├── composer.json                ← Dépendances PHP
│   ├── package.json                 ← Dépendances Node.js
│   ├── gulpfile.js                  ← Build automation (CSS/JS)
│   ├── logout.php                   ← Déconnexion sécurisée
│   ├── change_password.php          ← Changement password
│   ├── forgot-password.html         ← Form réinitialisation
│   ├── check_db.php                 ← Diagnostic BD
│   ├── metrics.php                  ← Métriques Prometheus (système)
│   ├── business_metrics.php         ← Métriques métier (applications)
│   ├── update_passwords.php         ← Batch update passwords
│   ├── chat_loader.php              ← Chargement messages internes
│   ├── chatlog.php                  ← Enregistrement messages
│   ├── app_uptime.txt               ← Suivi uptime
│   ├── INDEX_DOCUMENTATION.txt      ← Doc CSO formulaire
│   │
│   └── FICHIERS GÉNÉRÉS (📊 ANALYSE)
│       ├── ARCHITECTURE_ANALYSIS.md          ← Architecture détaillée
│       ├── PRODUCTION_RECOMMENDATIONS.md     ← Recommandations Go-Live
│       └── RESUME_EXECUTIF.md                ← Synthèse 1-page
│
├── 📂 includes/                     [NOYAU APPLICATIF]
│   ├── config.php                   ← Configuration centrale BD/.env
│   ├── session.php                  ← Gestion sessions sécurisées (headers, CSRF)
│   ├── loginController.php          ← Authentification principale
│   ├── loginController1.php         ← Alt controller (legacy?)
│   ├── verify_2fa.php               ← Vérification 2FA (Google Authenticator)
│   ├── error_handler.php            ← Gestion centralisée erreurs
│   ├── audit_logger.php             ← Logging audit compliance
│   ├── audit_helpers.php            ← Fonctions utilitaires audit
│   ├── flash.php                    ← Messages temporaires session
│   ├── Validator.php                ← Validation données utilisateur
│   ├── FileUploadHandler.php        ← Gestion uploads fichiers sécurisés
│   ├── display_qrcode.php           ← Génération QR code
│   ├── FlexcubeAPI.php              ← Intégration API banque Flexcube
│   ├── flexcube_helpers.php         ← Fonctions utilitaires Flexcube
│   └── UDFDataMapper.php            ← Mappage données utilisateur
│
├── 🔵 cso/                          [MODULE CLIENT-FACING]
│   ├── index.php                    ← Dashboard CSO accueil
│   ├── ecobank_account_form.php     ← Form ouverture compte (section 1-4)
│   ├── save_ecobank_form.php        ← Backend save + validation
│   ├── ecobank_submissions_list.php ← Liste soumissions (consulter/modifier)
│   ├── ecobank_submission_edit.php  ← Édition soumissions
│   ├── ecobank_submission_view.php  ← Consultation détaillée
│   ├── fetch_account_flexcube.php   ← Synchronisation Flexcube
│   ├── demande_chequier.php         ← Demande chéquier client
│   ├── demande_chequier_directe.php ← Demande accélérée
│   ├── save_chequier_directe.php    ← Sauvegarde chéquier rapide
│   ├── account_part.php             ← Section comptes partenaires
│   ├── rib.php                      ← Gestion RIB (Relevé Identité Bancaire)
│   ├── rib_ecobank.html             ← Template RIB Ecobank
│   ├── rib_lookup.php               ← Recherche RIB
│   ├── formulaire_bic_pysique.html  ← Form personnes physiques
│   ├── formulaire_produits.html     ← Selection produits financiers
│   ├── formulaire_produits.php      ← Traitement produits
│   ├── formulaire_ouverture_compte_tuteur.html ← Comptes tuteur
│   ├── save_tutor_account.php       ← Sauvegarde tuteur
│   ├── search_compte.php            ← Recherche comptes
│   ├── get_chequier_details.php     ← Fetch détails chéquier
│   ├── historique_chequier.php      ← Historique demandes
│   ├── staff_profile.php            ← Profil employé CSO
│   ├── change_password.php          ← Changement pass CSO
│   ├── bic_personne_physique.php    ← Traitement BIC données physiques
│   ├── check_submission_account_numbers.php ← Validation comptes soumis
│   ├── clear_notifications.php      ← Nettoyage notifications
│   ├── delete_notification.php      ← Suppression notification unique
│   ├── mark_notification_read.php   ← Marquer notification lue
│   ├── notification_details.php     ← Détails notification
│   ├── report_pdf.php               ← Génération rapports PDF
│   ├── save_digital_form.php        ← Sauvegarde formulaire digital
│   │
│   ├── FLEXCUBE INTEGRATION
│   │   ├── FLEXCUBE_ENDPOINTS.php         ← Endpoints API config
│   │   ├── flexcube_config.template.php   ← Template config
│   │   ├── migrate_ecobank_form_columns.php ← Migration BD
│   │   ├── ensure_ecobank_columns.php     ← Vérification colonnes
│   │   ├── inspect_last.php               ← Debug dernière action
│   │   ├── list_columns_tmp.php           ← Listing colonnes
│   │   └── save_ecobank_form_debug.log    ← Log debug
│   │
│   └── includes/        ← Headers, navigations CSO
│       └── header.php, navbar.php, left_sidebar.php, right_sidebar.php
│
├── 🟠 admin/                        [MODULE SUPERVISION & MONITORING]
│   ├── index.php                    ← Dashboard admin PRINCIPAL
│   ├── monitoring.php               ← Vue d'ensemble monitoring
│   ├── monitoring_overview.php      ← Santé globale système
│   ├── monitoring_system.php        ← Métriques CPU/RAM/Disk
│   ├── monitoring_notification.php  ← Alertes système
│   ├── monitoring_security.php      ← Dashboard sécurité (failed logins)
│   ├── monitoring_grafana.php       ← Panel Grafana iframe
│   ├── audit_logs.php               ← Historique COMPLET audit
│   ├── staff.php                    ← Gestion employés (CRUD)
│   ├── add_staff.php                ← Nouvel employé
│   ├── edit_staff.php               ← Éditer employé
│   ├── agence.php                   ← Gestion agences
│   ├── edit_agence.php              ← Éditer agence
│   ├── department.php               ← Gestion départements
│   ├── edit_department.php          ← Éditer département
│   ├── ecobank_submissions_list.php ← Supervision soumissions Ecobank
│   ├── ecobank_submission_edit.php  ← Correction/validation soumissions
│   ├── ecobank_submission_view.php  ← Visualisation dossiers
│   ├── demande_chequier.php         ← Validation demandes chéquier
│   ├── liste_chequier.php           ← Liste complète chéquiers
│   ├── mark_chequier_processed.php  ← Mark chéquier traité
│   ├── update_chequier_status.php   ← Update status chéquier
│   ├── export_chequier_excel.php    ← Export Excel
│   ├── export_chequier_xlsx.php     ← Export XLSX
│   ├── generate_chequier_delivery.php ← Bon livraison
│   ├── get_notifications.php        ← Get notifications AJAX
│   ├── mark_notification_read.php   ← Mark notif lue
│   ├── notification_details.php     ← Détails notification
│   ├── notification_details_new.php ← Nouveau format détails
│   ├── my_profile.php               ← Profil admin
│   ├── change_password.php          ← Change pass admin
│   └── report_pdf.php               ← Rapports PDF admin
│
├── 🟢 ci/                           [MODULE BACK-OFFICE INTERNE]
│   ├── index.php                    ← Dashboard CI accueil
│   ├── staff.php                    ← Gestion staff interne
│   ├── add_staff.php                ← Ajouter staff
│   ├── edit_staff.php               ← Éditer staff
│   ├── agence.php                   ← Gestion agences
│   ├── department.php               ← Gestion dépôts
│   ├── edit_department.php          ← Éditer dépôt
│   ├── demande_chequier.php         ← Traitement chéquiers
│   ├── mark_chequier_processed.php  ← Mark traité
│   ├── update_chequier_status.php   ← Update status
│   ├── historique_demande_chequier.php ← Historique
│   ├── get_chequier_details.php     ← Fetch détails
│   ├── export_chequier_excel.php    ← Export Excel
│   ├── export_chequier_xlsx.php     ← Export XLSX
│   ├── generate_chequier_delivery.php ← Bon livraison
│   ├── get_notifications.php        ← Notifications
│   ├── mark_notification_read.php   ← Mark notif lue
│   ├── my_profile.php               ← Profil user
│   ├── notification_details.php     ← Détails notif
│   ├── report_pdf.php               ← Rapports PDF
│   ├── change_password.php          ← Change pass
│   └── includes/        ← Headers, nav CI
│
├── 📊 monitoring/                   [STACK MONITORING]
│   ├── prometheus.yml               ← Config Prometheus scraping jobs
│   ├── init_monitoring.sql          ← Init tables monitoring BD
│   ├── alert_rules.yml              ← [À CRÉER] Alertes Prometheus
│   ├── alertmanager.yml             ← [À CRÉER] Config Alertmanager
│   ├── grafana/
│   │   ├── provisioning/            ← Datasources + dashboards auto
│   │   └── dashboards/              ← Fichiers JSON dashboards
│   └── logstash.conf                ← [À CRÉER] Config ELK Stack
│
├── 📂 db/                           [BASE DE DONNÉES]
│   ├── accountopening_db.sql        ← Schema complet BD
│   ├── Allnewdata_db.sql            ← Données test/migration
│   ├── create_tblCompte.sql         ← Creation table comptes
│   ├── create_tutor_account_submissions.sql ← Schéma tuteur
│   ├── README.md                    ← Documentation BD
│   ├── INSTALL_MONITORING.md        ← Installation monitoring
│   └── MONITORING_README.md         ← Guide monitoring
│
├── 📂 src/                          [ASSETS SOURCES]
│   ├── css/                         ← Fichiers CSS sources
│   ├── js/                          ← Fichiers JS sources
│   ├── styles/                      ← Stylesheets (SCSS/LESS)
│   ├── scripts/                     ← Scripts JS/jQuery
│   ├── fonts/                       ← Font files
│   ├── plugins/                     ← Plugins JS/CSS
│   └── images/                      ← Images sources
│
├── 📂 vendors/                      ← Assets compilés/minifiés
│   ├── images/                      ← Images finales (ecobank-bg3.png, etc)
│   ├── styles/                      ← CSS compilés (cores.css, style.css)
│   ├── scripts/                     ← JS compilés (jQuery, Bootstrap, etc)
│   └── fonts/                       ← Fonts compilées
│
├── 📂 logs/                         ← Logs application (runtime)
│   ├── error.log                    ← Erreurs PHP
│   ├── audit.log                    ← Actions audit
│   ├── app.log                      ← Logs applicatif
│   └── ...                          ← Autres logs générés
│
├── 📂 uploads/                      ← Fichiers uploadés utilisateurs
│   ├── cso/                         ← Uploads CSO
│   ├── admin/                       ← Uploads admin
│   └── ci/                          ← Uploads CI
│
├── 📂 Backup/                       ← Backups & fichiers test
│   └── test file/                   ← Fichiers de test
│
├── 📂 docs/                         ← Documentation projet
│   └── ...                          ← Divers docs
│
├── 📂 .git/                         ← Repo Git (version control)
│
├── 📂 Readme/                       ← Documentation utilisateurs/dev
│   └── README.md                    ← Guide principal
│
├── 📂 ci/                           [DÉPRÉCIÉ? - Double with CI Module]
│   └── [Identique au module ci/]
│
├── 📚 Fichiers Configuration
│   ├── .gitignore                   ← Ignore patterns Git
│   ├── .dockerignore                ← Ignore patterns Docker
│   ├── .env                         ← Variables env (⚠️ sécurité)
│   ├── .idea/                       ← Config IDE (PHPStorm)
│   ├── composer.lock                ← Lock dépendances PHP
│   ├── package-lock.json            ← Lock dépendances Node
│   └── cacert.pem                   ← Certificats SSL/TLS
│
└── 📋 Fichiers Systèmes
    ├── desktop.ini                  ← Config dossier Windows
    ├── start-monitoring.bat         ← Script démarrage monitoring (Windows)
    ├── start-monitoring.sh          ← Script démarrage monitoring (Linux)
    ├── start_monitoring.bat         ← Alt script Windows
    ├── stop_monitoring.bat          ← Arrêt monitoring Windows
    └── update_passwords.php         ← Batch password update
```

---

## 🎯 NAVIGATION RAPIDE PAR CAS D'USAGE

### 👤 Je suis un **Utilisateur CSO** (Client Relation Officer)
```
Accès: http://localhost:8080/cso/
    ↓
cso/index.php (Accueil)
    ↓
Créer ouverture compte? → ecobank_account_form.php
Demander chéquier? → demande_chequier.php
Consulter RIB? → rib_lookup.php
Historique? → historique_chequier.php
```

### 👨‍💼 Je suis un **Admin** (Supervisor/Manager)
```
Accès: http://localhost:8080/admin/
    ↓
admin/index.php (Dashboard)
    ↓
Monitoring? → monitoring.php → monitoring_grafana.php
Audit logs? → audit_logs.php
Gestion staff? → staff.php
Valider chéquiers? → demande_chequier.php
Rapports? → report_pdf.php
```

### 🔧 Je suis un **Développeur Backend**
```
Configuration? → includes/config.php
Authentification? → includes/loginController.php
Validation? → includes/Validator.php
API Flexcube? → includes/FlexcubeAPI.php
Base de données? → db/accountopening_db.sql
```

### 🔐 Je suis un **DevOps/SRE**
```
Docker orchestration? → docker-compose.yml
Monitoring setup? → monitoring/ directory
Metrics export? → metrics.php, business_metrics.php
Deploy script? → Dockerfile
Logs? → logs/ directory
Backup? → Backup/ directory
Alerting? → monitoring/alertmanager.yml [À CRÉER]
```

### 📊 Je veux un **Rapport PDF**
```
Admin rapport? → admin/report_pdf.php
CSO rapport? → cso/report_pdf.php
CI rapport? → ci/report_pdf.php
Chéquier livraison? → generate_chequier_delivery.php
```

### 💾 Je dois **Exporter données**
```
Excel chéquiers? → export_chequier_excel.php
XLSX format? → export_chequier_xlsx.php
Tous les formats? → gulpfile.js (build tasks)
```

---

## 📌 FICHIERS CRITIQUES À NE PAS OUBLIER

### 🔐 Sécurité (PRIORITIES)
- `includes/session.php` - Session security headers
- `includes/loginController.php` - Auth logic
- `includes/Validator.php` - Input validation
- `includes/audit_logger.php` - Compliance logging

### 📊 Métier (Core)
- `cso/ecobank_account_form.php` - Main form
- `cso/save_ecobank_form.php` - Form backend
- `admin/audit_logs.php` - Audit trail
- `admin/monitoring.php` - System health

### 🔧 Infrastructure (DevOps)
- `docker-compose.yml` - Services orchestration
- `Dockerfile` - Container image
- `monitoring/prometheus.yml` - Monitoring config
- `.env` - Configuration ⚠️ DON'T TRACK

### 📚 Intégration (Externe)
- `includes/FlexcubeAPI.php` - Bank integration
- `cso/FLEXCUBE_ENDPOINTS.php` - API endpoints config

---

## ⚠️ FICHIERS À RÉVISER AVANT PRODUCTION

1. ✅ `.env` - Sécuriser passwords
2. ✅ `docker-compose.yml` - Secrets management
3. ✅ `Dockerfile` - Optimisation image
4. ✅ `includes/config.php` - Gestion erreurs
5. ✅ `cso/ecobank_account_form.php` - Validation robuste
6. ✅ `admin/audit_logs.php` - Retention policy
7. ✅ `monitoring/prometheus.yml` - Alerting
8. ✅ `includes/FlexcubeAPI.php` - Error handling

---

## 📞 SUPPORT

Pour chaque module, consulter le fichier **index.php** pour comprendre son rôle principal.

Pour l'intégration Flexcube, voir: `cso/FLEXCUBE_ENDPOINTS.php`

Pour les logs/audits, voir: `logs/` et `admin/audit_logs.php`

---

**Généré automatiquement - 5 Avril 2026**

