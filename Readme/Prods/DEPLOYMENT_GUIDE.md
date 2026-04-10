# Production Deployment Guide

## Quick Start

### 1️⃣ Pre-Deployment (Local)

```bash
# Copy environment template
cp .env.example .env.production

# Edit with real credentials
nano .env.production

# Run checklist
chmod +x deploy-checklist.sh
./deploy-checklist.sh
```

### 2️⃣ Build Docker Image

```bash
docker build -t account-opening:latest .
docker tag account-opening:latest account-opening:v1.0.0
```

### 3️⃣ Deploy to Production

**Option A: Docker Compose (Small Deployment)**
```bash
docker-compose -f docker-compose.prod.yml up -d
```

**Option B: Kubernetes (Enterprise)**
```bash
kubectl apply -f k8s-deployment.yaml
```

**Option C: AWS ECS**
```bash
aws ecs create-service --cli-input-json file://ecs-service.json
```

### 4️⃣ Post-Deployment Verification

```bash
# Health check
curl https://yourapp.com/health

# Run database migrations
php artisan migrate --force

# Warm up cache
php bin/console cache:warmup

# Check logs
tail -f logs/app.log
```

---

## Security Hardening Checklist

### SSL/TLS
- [ ] Install SSL certificate (Let's Encrypt)
- [ ] Force HTTPS redirect
- [ ] Set HSTS header (1 year)
- [ ] Update docker-compose.prod.yml with SSL cert mounts

### Secrets Management
- [ ] Migrate credentials to AWS Secrets Manager / HashiCorp Vault
- [ ] Remove .env from git history: `git-filter-repo --path .env --invert-paths`
- [ ] Implement secret rotation policy

### Firewalls & Access Control
- [ ] Enable AWS WAF / CloudFlare WAF
- [ ] Whitelist admin IPs only
- [ ] Implement rate limiting (nginx/Apache)
- [ ] Block suspicious user agents

### Monitoring & Logging
- [ ] Setup centralized logging (ELK, CloudWatch)
- [ ] Configure error tracking (Sentry)
- [ ] Setup APM (DataDog, New Relic)
- [ ] Configure alerts for high error rates

### Database Security
- [ ] Enable encryption at rest
- [ ] Enable encryption in transit
- [ ] Setup automated backups to S3
- [ ] Configure read replicas for reporting
- [ ] Implement audit logging

---

## Performance Optimization

### CDN Configuration
```nginx
# Cloudflare recommended caching rules
/cso/Ecobank* -> Cache 24h
/vendors/images/* -> Cache 30d
/vendors/styles/* -> Cache 30d
```

### Database Optimization
```sql
-- Add missing indexes
ALTER TABLE tblcompte ADD INDEX idx_access (access);
ALTER TABLE tblcompte ADD INDEX idx_created_at (created_at);
ALTER TABLE tblemployees ADD INDEX idx_emp_id (emp_id);
ALTER TABLE tblnotification ADD INDEX idx_emp_id_read (emp_id, is_read);
```

### PHP-FPM Tuning
```ini
[www]
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 35
max_execution_time = 300
memory_limit = 256M
```

### Redis Configuration
```yaml
# For session caching
SESSION_DRIVER=redis
CACHE_DRIVER=redis
RATE_LIMITER=redis
```

---

## Rollback Procedure

```bash
# If deployment fails:
docker-compose -f docker-compose.prod.yml down
git revert HEAD
docker-compose -f docker-compose.prod.yml up -d
```

---

## Ongoing Maintenance

### Weekly
- [ ] Review monitoring dashboards
- [ ] Check error logs
- [ ] Verify backups completed

### Monthly
- [ ] Security patches (PHP, MySQL)
- [ ] Performance analysis
- [ ] Cost optimization review

### Quarterly
- [ ] Security audit
- [ ] Penetration testing
- [ ] Load testing
- [ ] Disaster recovery drill

---

## Support & Escalation

**Critical Issues:**
1. Page unavailable → ops@bank.com
2. Data loss → ciso@bank.com + backup team
3. Security breach → security@bank.com + legal

**Phone:**
+1-XXX-XXX-XXXX (24/7 on-call)

