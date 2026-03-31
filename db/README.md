# Architecture et Fonctionnement du Projet "Account Opening"

## Vue d'ensemble
Ce projet est une application web PHP pour la gestion des ouvertures de comptes à Ecobank, avec authentification à deux facteurs (2FA) utilisant Microsoft Authenticator. Il permet aux utilisateurs de rôles différents (Admin, CSO, CI, etc.) d'accéder à des interfaces spécifiques.

## Technologies utilisées
- **Backend** : PHP 7+ avec MySQL
- **Frontend** : HTML, CSS, JavaScript, Bootstrap
- **Authentification** : Sessions PHP, Google Authenticator pour 2FA
- **Dépendances** : Composer (PHP), npm (JS)
- **Serveur** : Apache (via Laragon en dev)

## Structure du projet
```
account opening/
├── .env                    # Configuration (DB, API, contrôleur)
├── index.php               # Page de connexion principale
├── composer.json           # Dépendances PHP
├── package.json            # Dépendances JS
├── admin/                  # Pages pour les admins
├── ci/                     # Pages pour les CI
├── cso/                    # Pages pour les CSO
├── includes/               # Code backend
│   ├── config.php          # Connexion DB
│   ├── loginController.php # Auth sans 2FA
│   ├── loginController1.php# Auth avec 2FA
│   ├── display_qrcode.php  # Génération QR 2FA
│   ├── verify_2fa.php      # Vérification code 2FA
│   ├── FlexcubeAPI.php     # Intégration API Flexcube
│   └── session.php         # Gestion sessions
├── vendor/                 # Dépendances PHP (Composer)
├── node_modules/           # Dépendances JS (npm)
├── logs/                   # Logs d'erreurs
├── uploads/                # Fichiers uploadés
├── db/                     # Dumps DB
└── vendors/                # Assets (CSS, JS, images)
```

## Flux d'authentification
1. **Connexion** : Utilisateur saisit username/password sur `index.php`.
2. **Vérification** : `loginController1.php` vérifie en DB.
3. **2FA** :
   - Si pas configuré : Génère secret, affiche QR (`display_qrcode.php`).
   - Si configuré : Demande code (`verify_2fa.php`).
4. **Redirection** : Selon rôle (admin, cso, etc.).

## Configuration
- **Changer contrôleur** : Dans `.env`, `LOGIN_CONTROLLER=loginController` (sans 2FA) ou `loginController1` (avec 2FA).
- **DB** : Config dans `includes/config.php` ou `.env`.
- **API Flexcube** : URL dans `includes/FlexcubeAPI.php`.

## Modification API Flexcube
Pour changer l'URL de l'API Flexcube :
1. Ouvrir `includes/FlexcubeAPI.php`.
2. Chercher la variable `$apiUrl` ou similaire.
3. Modifier l'URL (ex: `https://nouvelle-api.flexcube.com`).

## Modification nom dans Authenticator
Le nom affiché dans Microsoft Authenticator est l'issuer du QR code.
Pour changer :
1. Dans `includes/loginController1.php` et `includes/display_qrcode.php`.
2. Chercher `'ECOBANK AO & KYC'`.
3. Remplacer par le nouveau nom (ex: `'Mon Nouveau Nom'`).

## Améliorations pour production
- **Sécurité** :
  - Activer HTTPS.
  - Masquer erreurs PHP (`display_errors=off`).
  - Sécuriser `.env` (hors web root).
  - Utiliser prepared statements (déjà fait).
  - Logs sécurisés.
- **Performance** :
  - Cache (OPcache).
  - Optimiser DB (index).
  - CDN pour assets.
- **Monitoring** : Logs, alertes erreurs.
- **Backup** : DB automatique.

## Nettoyage du projet
Le projet pèse >40MB principalement à cause de :
- `node_modules/` (~20-30MB) : Dépendances JS. Supprimer si pas utilisé (pas de build JS).
- `vendor/` (~10MB) : Dépendances PHP. Garder en prod, mais déployer avec Composer.
- `logs/` : Supprimer anciens logs.
- `uploads/` : Vider fichiers temporaires.
- `db/` : Supprimer dumps inutiles.
- `.git/` : Si déployé, supprimer.

Commandes de nettoyage :
```bash
rm -rf node_modules/
rm -rf logs/*.log  # Garder récent
rm -rf uploads/temp/
rm -rf db/old_dumps/
```

## Passage à Docker
1. **Dockerfile** (pour PHP/Apache) :
   ```dockerfile
   FROM php:8.1-apache
   COPY . /var/www/html
   RUN docker-php-ext-install mysqli
   ```
2. **docker-compose.yml** :
   ```yaml
   version: '3'
   services:
     web:
       build: .
       ports:
         - "80:80"
     db:
       image: mysql:8
       environment:
         MYSQL_ROOT_PASSWORD: root
   ```
3. Build : `docker-compose up --build`

Cela containerise l'app pour prod.</content>
<parameter name="filePath">c:\laragon\www\account opening\README.md