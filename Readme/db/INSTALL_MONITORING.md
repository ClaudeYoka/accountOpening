# Guide d'Installation - Monitoring Account Opening

## 🚀 Déploiement du Système de Monitoring

### Prérequis
- **Docker Desktop** installé et en cours d'exécution
- **Git** pour cloner les repositories
- **Navigateur web** moderne

### Étape 1 : Vérification de Docker
```bash
# Vérifier que Docker est installé et fonctionne
docker --version
docker-compose --version

# Vérifier que Docker daemon est en cours
docker info
```

### Étape 2 : Clonage et Configuration
```bash
# Se positionner dans le répertoire du projet
cd /path/to/account-opening

# Donner les permissions d'exécution (Linux/Mac)
chmod +x start-monitoring.sh

# Ou utiliser le script batch sur Windows
# start-monitoring.bat
```

### Étape 3 : Lancement du Monitoring
```bash
# Démarrer tous les services
docker-compose up -d

# Ou utiliser le script fourni
./start-monitoring.sh
```

### Étape 4 : Vérification du Déploiement
```bash
# Vérifier que tous les conteneurs sont en cours d'exécution
docker-compose ps

# Attendre que les services soient prêts (environ 30 secondes)
# Puis vérifier l'accès aux URLs
```

### Étape 5 : Configuration Initiale de Grafana
1. Ouvrir http://localhost:3000
2. Se connecter avec `admin` / `admin`
3. Changer le mot de passe par défaut
4. Les dashboards seront automatiquement disponibles

## 🔧 Dépannage

### Problème : Docker n'est pas installé
**Solution** :
- Télécharger Docker Desktop depuis https://www.docker.com/products/docker-desktop
- L'installer et le démarrer
- Redémarrer le terminal

### Problème : Port déjà utilisé
**Solution** :
```bash
# Vérifier quels ports sont utilisés
netstat -tulpn | grep :3000
netstat -tulpn | grep :9090

# Modifier les ports dans docker-compose.yml si nécessaire
```

### Problème : Conteneurs ne démarrent pas
**Solution** :
```bash
# Voir les logs détaillés
docker-compose logs

# Redémarrer les services
docker-compose down
docker-compose up -d

# Vérifier les ressources système
docker system df
```

### Problème : Métriques PHP non disponibles
**Solution** :
```bash
# Tester l'accès aux métriques
curl http://localhost:8080/metrics.php
curl http://localhost:8080/business_metrics.php

# Vérifier que l'application fonctionne
curl http://localhost:8080
```

### Problème : Base de données non accessible
**Solution** :
```bash
# Vérifier la connectivité MySQL
docker-compose exec db mysql -u user -ppassword account_opening -e "SELECT 1"

# Vérifier les variables d'environnement
docker-compose exec web env | grep DB_
```

## 📊 Tests des Métriques

### Test des Métriques Système
```bash
# Accéder aux métriques PHP
curl -s http://localhost:8080/metrics.php | head -20

# Vérifier dans Prometheus
curl -s "http://localhost:9090/api/v1/query?query=php_info" | jq .
```

### Test des Métriques Business
```bash
# Métriques business
curl -s http://localhost:8080/business_metrics.php | head -20

# Vérifier une métrique spécifique
curl -s "http://localhost:9090/api/v1/query?query=app_total_users" | jq .
```

### Test des Dashboards Grafana
1. Ouvrir http://localhost:3000
2. Aller dans "Dashboards" → "Browse"
3. Sélectionner "Account Opening - Vue d'ensemble"
4. Vérifier que les panels se remplissent

## 🔄 Mise à Jour du Système

### Mise à jour des Images Docker
```bash
# Arrêter les services
docker-compose down

# Mettre à jour les images
docker-compose pull

# Redémarrer
docker-compose up -d
```

### Mise à jour de la Configuration
```bash
# Modifier les fichiers de configuration
# Puis redémarrer
docker-compose restart
```

## 📈 Optimisation des Performances

### Configuration Prometheus
- **Rétention** : 200h en production, 24h en développement
- **Intervalle de scrape** : 15s pour les métriques critiques, 60s pour les business metrics

### Configuration Grafana
- **Rafraîchissement automatique** : 30s pour les dashboards temps réel
- **Cache** : Activer le cache des requêtes
- **Alertes** : Configurer des notifications par email/Slack

### Optimisation Base de Données
```sql
-- Créer des indexes pour les métriques
CREATE INDEX idx_audit_action_time ON audit_logs(action, timestamp);
CREATE INDEX idx_login_emp_time ON tbl_logins(emp_id, login_time);
CREATE INDEX idx_notif_emp_read ON tblnotification(emp_id, is_read);
```

## 🔒 Sécurité

### Configuration de Production
1. **Changer les mots de passe par défaut**
2. **Utiliser des secrets Docker**
3. **Configurer HTTPS pour Grafana**
4. **Restreindre l'accès réseau**
5. **Activer l'authentification Grafana**

### Secrets Docker
```yaml
# Exemple pour docker-compose.prod.yml
services:
  grafana:
    environment:
      - GF_SECURITY_ADMIN_PASSWORD_FILE=/run/secrets/grafana_admin_password
    secrets:
      - grafana_admin_password

secrets:
  grafana_admin_password:
    file: ./secrets/grafana_admin_password.txt
```

## 📞 Support et Monitoring

### Commandes Utiles
```bash
# État des services
docker-compose ps

# Logs en temps réel
docker-compose logs -f grafana

# Statistiques des conteneurs
docker stats

# Nettoyage
docker system prune -a
```

### Métriques à Surveiller
- **Disponibilité des services** : up/down status
- **Performance** : temps de réponse, utilisation CPU/mémoire
- **Business** : connexions utilisateurs, soumissions formulaires
- **Sécurité** : tentatives de connexion, erreurs système

### Alertes Recommandées
- Service down > 1 minute
- CPU > 80% pendant 5 minutes
- Mémoire disponible < 10%
- Plus de 10 échecs de connexion/heure
- Erreurs système détectées

---

## 🎯 Checklist de Déploiement

- [ ] Docker installé et fonctionnel
- [ ] Application Account Opening opérationnelle
- [ ] Base de données accessible
- [ ] Services démarrés (`docker-compose up -d`)
- [ ] Grafana accessible (http://localhost:3000)
- [ ] Dashboards visibles et fonctionnels
- [ ] Métriques collectées (vérifier dans Prometheus)
- [ ] Mots de passe changés
- [ ] Sauvegarde configurée

**Temps estimé de déploiement** : 15-30 minutes