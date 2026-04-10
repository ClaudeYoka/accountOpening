# 🔒 INTÉGRATIONS RATE LIMITING - COPY-PASTE READINESS

**Status:** Fichiers clés prêts à intégrer  
**Time to complete:** ~30 min  

---

## ✅ DÉJÀ INTÉGRÉS

- ✅ `includes/loginController.php` - Authentication (5 attempts/5min)
- ✅ `cso/save_ecobank_form.php` - Form submit (5 attempts/60s)

---

## ⏳ À INTÉGRER (COPY-PASTE)

### 1️⃣ **Demande Chéquier** (`cso/demande_chequier.php`)

**Location:** Après la première include de session (~ligne 5-10)

```php
<?php 
include('../includes/header.php');
include('../includes/session.php');
include('../includes/RateLimiter.php');  // ← ADD THIS LINE

// ... rest of code ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ← ADD THESE LINES
    $rate_check = middleware_rate_limit($dbh, 'form_submit_chequier', 3, 300);
    
    // ... your existing POST handler ...
}
?>
```

**Limit:** 3 attempts / 5 minutes (demande sensible)

---

### 2️⃣ **Changement Password** (`change_password.php`)

**Location:** Après session include (~ligne 5-10)

```php
<?php
// In BOTH files: cso/change_password.php, admin/change_password.php
include('../includes/session.php');
include('../includes/RateLimiter.php');  // ← ADD THIS

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // ← ADD THESE LINES
    $rate_check = middleware_rate_limit($dbh, 'password_reset', 3, 600);
    
    // ... existing password change logic ...
}
?>
```

**Limit:** 3 attempts / 10 minutes (sécurité critique)

---

### 3️⃣ **Fetch Flexcube API** (`cso/fetch_account_flexcube.php`)

**Location:** Après includes (~ligne 10)

```php
<?php
include('../includes/session.php');
include('../includes/RateLimiter.php');  // ← ADD

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['account_number'])) {
    // ← ADD THESE LINES (avant appel API)
    $rate_check = middleware_rate_limit($dbh, 'api_flexcube', 100, 3600);
    
    // ... your API call logic ...
}
?>
```

**Limit:** 100 attempts / 1 hour (API normale)

---

### 4️⃣ **File Upload Handler** (`includes/FileUploadHandler.php`)

**Location:** Début de la classe, dans handleUpload()

```php
public function handleUpload($file, $allowed_types = []) {
    // ← ADD THESE LINES (au début de la méthode)
    include('RateLimiter.php');
    $rate_check = middleware_rate_limit($GLOBALS['dbh'], 'file_upload', 10, 600);
    
    // ... rest of upload logic ...
}
```

**Limit:** 10 attempts / 10 minutes (protection DDoS)

---

### 5️⃣ **Messages Chat** (`chatlog.php`)

**Location:** Après session include (~ligne 5)

```php
<?php
include('includes/session.php');
include('includes/RateLimiter.php');  // ← ADD

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ← ADD THESE LINES
    $rate_check = middleware_rate_limit($dbh, 'message_send', 10, 60);
    
    // ... your message saving logic ...
}
?>
```

**Limit:** 10 attempts / 60 seconds (anti-spam chat)

---

### 6️⃣ **Export Data** (`cso/export_chequier_excel.php` et `export_chequier_xlsx.php`)

**Location:** Après includes + POST check (~ligne 15-20)

```php
<?php
include('../includes/session.php');
include('../includes/RateLimiter.php');  // ← ADD

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ← ADD THESE LINES (avant générer file)
    $rate_check = middleware_rate_limit($dbh, 'export_data', 5, 3600);
    
    // ... your export logic ...
}
?>
```

**Limit:** 5 attempts / 1 hour (anti-extraction massive)

---

### 7️⃣ **Get Notifications** (`admin/get_notifications.php`, `cso/notification_details.php`)

**Location:** Après session include (~ligne 5)

```php
<?php
include('../includes/session.php');
include('../includes/RateLimiter.php');  // ← ADD

// ← ADD THESE LINES (pour GET requests)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rate_check = middleware_rate_limit($dbh, 'notifications_fetch', 20, 3600);
}

// ... existing code ...
?>
```

**Limit:** 20 attempts / 1 hour (normal API)

---

### 8️⃣ **Autres Formulaires Admin** 

Pour chaque formulaire admin (add_staff, edit_staff, demande_chequier, etc):

```php
<?php
// Pattern générique pour tous les formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('../includes/RateLimiter.php');
    
    // ← ADD: Identifier unique par action
    $rate_check = middleware_rate_limit($dbh, 'admin_action_' . $action_name, 5, 300);
    
    // ... process form ...
}
?>
```

**Limit:** 5 attempts / 5 minutes (par défaut sauf indiqué)

---

## 🎯 RAPIDE CHECK LIST

```
[ ] 1. Exécuter setup_rate_limiting.bat ou .sh
[ ] 2. Intégrer demande_chequier.php (CSO + Admin + CI)
[ ] 3. Intégrer change_password.php (ROOT + CSO + Admin)
[ ] 4. Intégrer fetch_account_flexcube.php
[ ] 5. Intégrer FileUploadHandler.php
[ ] 6. Intégrer chatlog.php
[ ] 7. Intégrer export scripts (2 fichiers)
[ ] 8. Intégrer get_notifications.php
[ ] 9. Tester login rate limit (voir RATE_LIMITING_GUIDE.md)
[ ] 10. Test formulaires (soumettre 6x rapidement = doit bloquer 6ème)
```

---

## 🔍 VÉRIFICATION

Après chaque intégration, tester:

```bash
# 1. Vérifier le fichier a la syntaxe OK
php -l cso/demande_chequier.php

# 2. Soumettre le formulaire 6 fois rapidement
# Résultat attendu: 6ème request = HTTP 429

# 3. Vérifier les logs BD
SELECT * FROM rate_limit_logs 
WHERE action = 'form_submit_chequier' 
ORDER BY timestamp DESC LIMIT 10;
```

---

## ⚡ QUICK REFERENCE TABLE

| Fichier | Action | Limit | Window |
|---------|--------|-------|--------|
| login | `login` | 5 | 5 min |
| password | `password_reset` | 3 | 10 min |
| demande_chequier | `form_submit_chequier` | 3 | 5 min |
| fetch_flexcube | `api_flexcube` | 100 | 1 hour |
| file_upload | `file_upload` | 10 | 10 min |
| chatlog | `message_send` | 10 | 60 sec |
| export | `export_data` | 5 | 1 hour |
| notifications | `notifications_fetch` | 20 | 1 hour |

---

## 💡 TIPS

1. **Use consistent action names** - Helps monitoring
2. **Test locally first** - Avant production
3. **Monitor violations** - Via admin dashboard
4. **Whitelist admins** - Si besoin de bypass
5. **Adjust limits** - Si trop strict pour users

---

## 🚀 GO-LIVE CHECKLIST

- [ ] All files integrated
- [ ] Local testing done
- [ ] No PHP syntax errors
- [ ] Dashboard accessible
- [ ] Monitoring alerts setup
- [ ] Cron jobs configured
- [ ] UAT passed
- [ ] Production deployment ready

---

**Time estimate:** 30 min integration + 1 hour testing = **~1.5 hours total**

