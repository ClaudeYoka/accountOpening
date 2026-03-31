# Account Opening - Système de Monitoring

Un système de monitoring complet basé sur Prometheus et Grafana pour surveiller l'application Account Opening.

## 🏗️ Architecture

Le système de monitoring comprend :

- **Prometheus** : Collecte et stockage des métriques
- **Grafana** : Visualisation et tableaux de bord
- **Node Exporter** : Métriques système (CPU, mémoire, disque)
- **MySQL Exporter** : Métriques base de données
- **cAdvisor** : Métriques conteneurs Docker
- **Exporters PHP personnalisés** : Métriques business spécifiques

## 🚀 Démarrage Rapide

### Prérequis
- Docker et Docker Compose installés
- L'application Account Opening fonctionnelle

### Lancement
```bash
# Sur Linux/Mac
./start-monitoring.sh

# Sur Windows
start-monitoring.bat
```

Ou manuellement :
```bash
docker-compose up -d
```

## 📊 Tableaux de Bord Disponibles

### 1. Account Opening - Vue d'ensemble
**URL**: http://localhost:3000/d/account-opening-overview

Métriques principales :
- Statut de l'application et base de données
- Utilisateurs actifs et total
- Comptes clients
- Demandes de chéquiers (en cours/livrées)
- Soumissions de formulaires
- Statistiques de connexion
- Notifications et erreurs

### 2. Account Opening - Métriques Système
**URL**: http://localhost:3000/d/system-metrics

Métriques système :
- Utilisation CPU et mémoire
- Utilisation disque
- Charge système
- Mémoire PHP
- Temps de réponse HTTP
- Requêtes HTTP
- Métriques MySQL (connexions, requêtes, buffer pool)

### 3. Account Opening - Sécurité
**URL**: http://localhost:3000/d/security-dashboard

Métriques de sécurité :
- Échecs et succès de connexion
- Taux de succès des authentifications
- Évolution temporelle des tentatives
- Erreurs système
- Sessions actives suspectes
- Notifications de sécurité

## 🔧 Métriques Collectées

### Métriques Business (`/business_metrics.php`)
- `app_total_users` : Nombre total d'utilisateurs
- `app_active_sessions` : Sessions actives (dernière heure)
- `app_total_accounts` : Nombre total de comptes clients
- `app_pending_chequier_requests` : Demandes de chéquiers en cours
- `app_completed_chequier_requests` : Demandes de chéquiers livrées
- `app_form_submissions_today` : Soumissions de formulaires aujourd'hui
- `app_failed_logins_24h` : Échecs de connexion (24h)
- `app_successful_logins_24h` : Connexions réussies (24h)
- `app_pending_notifications` : Notifications non lues
- `app_database_size_mb` : Taille de la base de données
- `app_recent_errors` : Erreurs récentes (1h)
- `app_uptime_seconds` : Uptime de l'application

### Métriques Système (`/metrics.php`)
- `php_info` : Version PHP
- `php_memory_usage_bytes` : Utilisation mémoire PHP
- `php_memory_peak_usage_bytes` : Pic d'utilisation mémoire PHP
- `system_load1/5/15` : Charge système
- `disk_free/total/used_bytes` : Métriques disque
- `disk_usage_percent` : Pourcentage d'utilisation disque
- `http_requests_total` : Nombre total de requêtes HTTP
- `http_request_duration_milliseconds` : Temps de réponse HTTP

## 🌐 URLs d'Accès

| Service | URL | Description |
|---------|-----|-------------|
| Grafana | http://localhost:3000 | Interface de visualisation (admin/admin) |
| Prometheus | http://localhost:9090 | Interface de requêtage des métriques |
| Node Exporter | http://localhost:9100 | Métriques système |
| MySQL Exporter | http://localhost:9104 | Métriques MySQL |
| cAdvisor | http://localhost:8081 | Métriques conteneurs |
| Application | http://localhost:8080 | Application Account Opening |

## 📈 Alertes Recommandées

### Alertes Système
```yaml
# CPU élevé
- alert: HighCPUUsage
  expr: 100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
  for: 5m

# Mémoire faible
- alert: LowMemory
  expr: (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes) * 100 < 10
  for: 5m

# Disque plein
- alert: DiskFull
  expr: (node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100 < 10
  for: 5m
```

### Alertes Business
```yaml
# Beaucoup d'échecs de connexion
- alert: HighFailedLogins
  expr: app_failed_logins_24h > 10
  for: 5m

# Erreurs système
- alert: SystemErrors
  expr: app_recent_errors > 0
  for: 5m

# Base de données indisponible
- alert: DatabaseDown
  expr: db_connection_status == 0
  for: 1m
```

## 🔧 Configuration

### Prometheus
Configuration dans `monitoring/prometheus.yml` :
- Intervalle de scrape : 15s
- Rétention : 200h
- Jobs configurés pour tous les exporters

### Grafana
- Auto-provisioning activé
- Datasources configurées automatiquement
- Dashboards importés automatiquement

## 📝 Logs et Debugging

### Logs Docker
```bash
# Tous les logs
docker-compose logs

# Logs d'un service spécifique
docker-compose logs prometheus
docker-compose logs grafana

# Logs en temps réel
docker-compose logs -f [service]
```

### Debugging Métriques
```bash
# Tester les métriques PHP
curl http://localhost:8080/metrics.php
curl http://localhost:8080/business_metrics.php

# Vérifier Prometheus targets
curl http://localhost:9090/api/v1/targets
```

## 🛠️ Maintenance

### Sauvegarde
```bash
# Sauvegarder les données Grafana
docker run --rm -v account-opening_grafana_data:/source -v $(pwd)/backups:/backup alpine tar czf /backup/grafana-$(date +%Y%m%d).tar.gz -C /source .

# Sauvegarder Prometheus
docker run --rm -v account-opening_prometheus_data:/source -v $(pwd)/backups:/backup alpine tar czf /backup/prometheus-$(date +%Y%m%d).tar.gz -C /source .
```

### Mise à jour
```bash
# Arrêter les services
docker-compose down

# Mettre à jour les images
docker-compose pull

# Redémarrer
docker-compose up -d
```

## 📊 Personnalisation

### Ajouter une nouvelle métrique
1. Modifier `business_metrics.php` ou `metrics.php`
2. Redémarrer les conteneurs si nécessaire
3. Ajouter la métrique aux dashboards Grafana

### Créer un nouveau dashboard
1. Créer un fichier JSON dans `monitoring/grafana/dashboards/`
2. Le dashboard sera automatiquement importé

## 🤝 Contribution

Pour améliorer le système de monitoring :
1. Ajouter de nouvelles métriques pertinentes
2. Créer des dashboards spécialisés
3. Configurer des alertes appropriées
4. Documenter les changements

## 📞 Support

En cas de problème :
1. Vérifier les logs Docker
2. Tester l'accès aux URLs
3. Vérifier la configuration réseau
4. Consulter la documentation Prometheus/Grafana