# AO & KYC - Système d’ouverture de compte Ecobank

Ce projet est une application web PHP permettant de gérer le parcours d’ouverture de compte et de traitement des demandes de chéquier au sein d’Ecobank. Il couvre l’authentification des utilisateurs, la saisie et le suivi des demandes, l’intégration avec Flexcube pour l’enquête de compte, ainsi que la génération de documents de livraison.

## Aperçu du projet

L’application est organisée autour de trois grandes volets :

- portail CSO : saisie des demandes, consultation des dossiers et gestion des demandes de chéquier
- portail CI/Admin : suivi des demandes, mise à jour des statuts, génération de documents et supervision
- services techniques : intégration Flexcube, journalisation d’audit, notifications et monitoring

## Architecture fonctionnelle

### Portails utilisateurs

- Page de connexion : [index.php](index.php)
- Espace CSO : [cso/index.php](cso/index.php)
- Espace CI : [ci/index.php](ci/index.php)
- Espace administrateur : [admin/index.php](admin/index.php)

### Modules principaux

- Formulaire d’ouverture de compte : [cso/ecobank_account_form.php](cso/ecobank_account_form.php)
- Soumission et stockage des formulaires : [cso/save_ecobank_form.php](cso/save_ecobank_form.php)
- Recherche de compte via Flexcube : [cso/fetch_account_flexcube.php](cso/fetch_account_flexcube.php)
- Demande de chéquier directe : [cso/demande_chequier_directe.php](cso/demande_chequier_directe.php)
- Consultation des demandes : [cso/demande_chequier.php](cso/demande_chequier.php)
- Historique des demandes : [cso/historique_chequier.php](cso/historique_chequier.php)
- Suivi des statuts : [admin/update_chequier_status.php](admin/update_chequier_status.php)
- Traitement et livraison : [admin/mark_chequier_processed.php](admin/mark_chequier_processed.php) et [admin/generate_chequier_delivery.php](admin/generate_chequier_delivery.php)

## Workflow de demande de chéquier

Le processus de chéquier suit ce parcours :

1. Le CSO saisit les informations du client et du compte.
2. Une demande de chéquier est enregistrée dans la base de données.
3. Les écrans de consultation affichent la demande avec ses informations et son statut.
4. Le CI ou l’administrateur met à jour le statut de traitement.
5. La demande peut être marquée comme traitée et un bon de livraison peut être généré.

Une documentation détaillée du processus est disponible ici : [docs/processus-demande-chequier.md](docs/processus-demande-chequier.md)

## Base de données

Le système repose sur plusieurs tables métier principales :

- tblcompte : stockage des demandes de chéquier et des informations associées
- ecobank_form_submissions : sauvegarde des soumissions de formulaires
- chequier_status : historique des statuts
- tblnotification : notifications système
- tblemployees et tbldepartments : gestion des utilisateurs et agences

## Intégrations

- Flexcube : enrichissement et validation des informations de compte via l’API d’Enquiry Service
- monitoring : tableaux de bord Prometheus/Grafana via [docker-compose.yml](docker-compose.yml)
- export Excel/XLSX : génération de documents et de livraisons pour le chéquier

## Démarrage rapide

### Prérequis

- PHP 8+
- MySQL/MariaDB
- Composer
- Docker (optionnel)

### Exécution locale

1. Copier les variables d’environnement si nécessaire.
2. Importer la base de données et vérifier les tables requises.
3. Servir le projet depuis le dossier racine avec Apache ou PHP built-in.
4. Ouvrir l’application via votre navigateur.

### Avec Docker

```bash
docker compose up --build
```

Puis ouvrir :

- http://localhost:8080/
- http://localhost:3000/ pour Grafana
- http://localhost:9090/ pour Prometheus

## Structure du dépôt

- [admin](admin) : interfaces de gestion et traitements administratifs
- [ci](ci) : interfaces CI
- [cso](cso) : interfaces CSO et formulaires clients
- [includes](includes) : bibliothèques PHP communes, helpers et intégrations
- [monitoring](monitoring) : configuration du monitoring
- [test](test) : scripts et pages de test
- [docs](docs) : documentation fonctionnelle

## Notes de maintenance

- Vérifier la configuration de la base de données et le fichier d’environnement avant toute mise en production.
- Consulter les logs PHP et les journaux d’audit en cas d’anomalie.
- Les écrans de test sont utiles pour valider les intégrations Flexcube et le workflow de chéquier.

## Documentation complémentaire

- [docs/processus-demande-chequier.md](docs/processus-demande-chequier.md)
- [cso/FLEXCUBE_ENDPOINTS.php](cso/FLEXCUBE_ENDPOINTS.php)
- [cso/fetch_account_flexcube.php](cso/fetch_account_flexcube.php)
