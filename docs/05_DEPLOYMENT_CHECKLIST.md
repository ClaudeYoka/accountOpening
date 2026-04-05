# 📋 RATE LIMITING - CHECKLIST DE DÉPLOIEMENT

| État | Tâche | Durée | Notes |
|------|-------|-------|-------|
| ✅ | Classe RateLimiter créée | N/A | [includes/RateLimiter.php](includes/RateLimiter.php) |
| ✅ | Migration BD préparée | N/A | [db/migrate_rate_limiting.sql](db/migrate_rate_limiting.sql) |
| ✅ | Dashboard admin créé | N/A | [admin/rate_limiting_dashboard.php](admin/rate_limiting_dashboard.php) |
| ✅ | Login intégré | N/A | [includes/loginController.php](includes/loginController.php) |
| ✅ | Form intégré | N/A | [cso/save_ecobank_form.php](cso/save_ecobank_form.php) |
| ⏳ | ÉTAPE 1: Exécuter setup | 5 min | `setup_rate_limiting.bat` (Windows) ou `setup_rate_limiting.sh` (Linux) |
| ⏳ | ÉTAPE 2: Vérifier tables | 2 min | Vérifier 3 tables créées en BD |
| ⏳ | ÉTAPE 3: Intégrer 6 fichiers | 45 min | Voir [INTEGRATIONS_COPY_PASTE.md](INTEGRATIONS_COPY_PASTE.md) |
| ⏳ | ÉTAPE 4: Tester localement | 30 min | Voir RATE_LIMITING_GUIDE.md |
| ⏳ | ÉTAPE 5: Vérifier dashboard | 5 min | Accéder à `/admin/rate_limiting_dashboard.php` |
| ⏳ | ÉTAPE 6: Deploy staging | 1 heure | Après test local réussi |
| ⏳ | ÉTAPE 7: UAT complet | 2 heures | Tests users |
| ⏳ | ÉTAPE 8: Production ready | 30 min | Configurer monitoring alerts |
| ⏳ | ÉTAPE 9: Go-live | 1 heure | Déployer en production |

---

## 🚀 DÉMARRAGE RAPIDE

### Jour 1 - Setup (1 heure):
```bash
# 1. Exécuter setup
./setup_rate_limiting.bash    # Linux/Mac
# OU
setup_rate_limiting.bat       # Windows

# 2. Vérifier MySQL
mysql -u root -p account_opening
SHOW TABLES LIKE 'rate_limit%';

# 3. Lancer tests
http://localhost/admin/rate_limiting_dashboard.php
```

### Jour 1-2 - Intégrations (1-2 heures):
```php
// Pour chaque fichier dans INTEGRATIONS_COPY_PASTE.md:
// 1. Copier le code
// 2. Intégrer dans le fichier
// 3. Tester

// ci/demande_chequier.php
// ci/change_password.php
// ci/fetch_account_flexcube.php
// [etc - voir INTEGRATIONS_COPY_PASTE.md]
```

### Jour 2-3 - Testing (2 heures):
```
- Tester chaque protection
- Vérifier dashboard mises à jour
- Configurations réelles pour production
- Tests de débit élevé (load test)
```

### Semaine 2 - Production:
```
- Deploy à staging
- UAT 24-48h
- Monitoring alerts configurées
- Go-live production
```

---

## 📝 FICHIERS À INTÉGRER

> Voir [INTEGRATIONS_COPY_PASTE.md](INTEGRATIONS_COPY_PASTE.md) pour le code exact

### 1. `ci/demande_chequier.php`
```php
// Include au début
include(__DIR__ . '/../includes/RateLimiter.php');

// Dans la fonction POST
$rate_check = middleware_rate_limit($dbh, 'demande_chequier', 3, 300);
if ($rate_check['blocked']) {
    http_response_code(429);
    die(json_encode(['error' => $rate_check['message']]));
}

// Après succès
rate_limit_clear_on_success($dbh, 'demande_chequier');
```

### 2. `ci/change_password.php`
```php
// Idem pattern: include, check, clear
```

### 3. `ci/fetch_account_flexcube.php`
```php
// pattern API (100/heure, log-only)
```

### 4. `includes/FileUploadHandler.php`
```php
// pattern file upload (10/10min)
```

### 5. `cso/chatlog.php`
```php
// pattern message (10/60sec)
```

### 6. `cso/export_chequier.php`
```php
// pattern export (5/1heure)
```

### 7. `admin/get_notifications.php`
```php
// pattern notifications (20/1heure)
```

### 8. `admin/index.php` (bonus)
```php
// pattern admin forms (5/5min)
```

---

## ✅ CHECKLIST PRÉ-PRODUCTION

- [ ] Tous les fichiers intégrés
- [ ] Tests locaux 100% pass
- [ ] Dashboard accessible
- [ ] Whitelist configurée (prod IPs)
- [ ] RATE_LIMITING_CONFIG.php hash_salt changé
- [ ] Email de violation configuré
- [ ] Cron de cleanup ajouté
- [ ] Logs configurés
- [ ] Monitoring alerts configurées
- [ ] Documentation lue par équipe
- [ ] Permission d'accès vérifiées
- [ ] Rollback plan prêt

---

## ❌ PROBLÈMES COURANTS

### "429 Too Many Requests" au login
```
→ 5 tentatives échouées en 5 min = bloqué
→ Attendre 5 min OU accès admin dashboard → Unblock
```

### Pas de BD après setup
```
→ Vérifier credentials .env
→ Vérifier MySQL en cours d'exécution  
→ Exécuter manuelle: mysql -u user -p db < migrate_rate_limiting.sql
```

### Dashboard vide
```
→ Tester l'endpoint (faire 6 tentatives login)
→ Vérifier table rate_limit_logs
→ Vérifier logs d'erreur PHP
```

### Whitelist ne fonctionne pas
```
→ Vérifier IP client réelle (admin panel affiche)
→ Utiliser IP exacte en whitelist
→ Tester avec IP de développeur
```

---

## 📞 SUPPORT RESSOURCES

| Question | Réponse |
|----------|--------|
| Comment ça marche? | Voir RATE_LIMITING_GUIDE.md |
| Quoi intégrer? | Voir INTEGRATIONS_COPY_PASTE.md |
| Détail du code? | Voir RateLimiter.php (270 lignes commentées) |
| Configuration? | Voir RATE_LIMITING_CONFIG.php |
| Dashboard? | `/admin/rate_limiting_dashboard.php` |
| Tests? | Voir RATE_LIMITING_GUIDE.md section Testing |

---

## 🎯 TIMELINE ESTIMÉE

```
Setup script        : 5 min
Intégrer fichiers   : 45 min  
Test local          : 30 min
--------------------------------------
TOTAL JOUR 1        : 1 h 20 min ✅

Staging deploy      : 30 min
UAT + fixes         : 2-4 heures
Production deploy   : 1 heure
--------------------------------------
TOTAL SEMAINE 1     : 4-6 heures ✅
```

---

## 📊 RÉFÉRENCES RAPIDES

| État | Signification | Action |
|------|---------------|--------|
| ✅ (coché) | Complété | None |
| ⏳ (horloge) | À faire | Lire guide |
| ⚠️ (alert) | Attention requise | Voir GUIDE |
| ❌ (X) | Bloqué/erreur | Contacter support |

---

**Document créé:** 5 Avril 2026  
**Système:** PROD-READY  
**Next step:** Exécuter setup script  
**Estimated Go-Live:** Cette semaine

