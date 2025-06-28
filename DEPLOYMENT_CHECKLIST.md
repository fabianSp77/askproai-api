# 🚀 AskProAI Deployment Checklist

> **Zero-Downtime Deployment in < 10 Minuten**

## ⏱️ Quick Deploy Command
```bash
./deploy.sh production --safety-check
```

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### 1️⃣ **Code Quality Gates** (5 min)
```bash
# ✅ All tests passing
php artisan test --parallel

# ✅ No linting errors  
./vendor/bin/pint --test

# ✅ No security vulnerabilities
composer audit

# ✅ Migration dry-run
php artisan migrate:status
php artisan migrate --pretend
```

### 2️⃣ **Performance Check** (2 min)
```bash
# ✅ Build assets
npm run build

# ✅ Check bundle size < 2MB
du -sh public/build/

# ✅ Database query analysis
php artisan db:show --counts
```

### 3️⃣ **Backup Current State** (3 min)
```bash
# ✅ Database backup
php artisan backup:run --only-db

# ✅ Config backup
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# ✅ Note current version
git rev-parse HEAD > LAST_WORKING_VERSION
```

---

## 🔄 DEPLOYMENT PROCESS

### Phase 1: **Preparation** (No Downtime)
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# 3. Build assets
npm run build

# 4. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Phase 2: **Database Updates** (< 30s Downtime)
```bash
# 1. Enable maintenance mode with bypass
php artisan down --secret="secret-key-here"

# 2. Run migrations
php artisan migrate --force

# 3. Clear caches
php artisan optimize:clear
```

### Phase 3: **Service Restart** (< 10s)
```bash
# 1. Restart services
sudo systemctl reload php8.3-fpm
php artisan horizon:terminate

# 2. Disable maintenance
php artisan up
```

---

## ✅ POST-DEPLOYMENT VALIDATION

### 🔍 **Smoke Tests** (2 min)
```bash
# 1. Health check
curl -f https://api.askproai.de/health || exit 1

# 2. Critical endpoints
curl -f https://api.askproai.de/api/v1/status
curl -f https://api.askproai.de/api/retell/webhook -X POST

# 3. Database connectivity
php artisan tinker --execute="DB::select('SELECT 1')"

# 4. Queue processing
php artisan queue:work --stop-when-empty
```

### 📊 **Monitoring Checks** (3 min)
```bash
# 1. Error rate normal?
tail -n 100 storage/logs/laravel.log | grep -c ERROR
# Expected: < 5

# 2. Response times OK?
grep "duration" storage/logs/laravel.log | tail -20
# Expected: < 200ms avg

# 3. Queue backlog?
php artisan horizon:status
# Expected: No failed jobs

# 4. Memory usage stable?
free -m
# Expected: < 80% used
```

### 🎯 **Business Validation** (5 min)
- [ ] Test phone call → Creates appointment
- [ ] Admin panel loads correctly  
- [ ] Can create manual appointment
- [ ] Webhook receives test event
- [ ] Email notifications sent

---

## 🔴 ROLLBACK PROCEDURE (< 2 min)

### Immediate Rollback:
```bash
# 1. Enable maintenance
php artisan down

# 2. Revert code
git reset --hard $(cat LAST_WORKING_VERSION)

# 3. Restore database
php artisan backup:restore --latest --only-db

# 4. Restore config
cp .env.backup.* .env

# 5. Clear everything
php artisan optimize:clear

# 6. Restart services
sudo systemctl restart php8.3-fpm
php artisan horizon:terminate

# 7. Disable maintenance
php artisan up
```

---

## 📈 DEPLOYMENT METRICS

Track these KPIs:
- **Deployment Duration**: Target < 10 min
- **Rollback Time**: Target < 2 min  
- **Post-Deploy Errors**: Target < 5 in first hour
- **Zero-Downtime Success**: Target 95%

---

## 🚨 EMERGENCY CONTACTS

**If deployment fails:**
1. Check #deployments Slack channel
2. Run rollback procedure
3. Document issue in ERROR_PATTERNS.md

**Escalation:**
- DevOps Lead: [Contact]
- CTO: [Contact] (if customer impact)

---

## 🤖 AUTOMATION SCRIPTS

### deploy.sh
```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Pre-deployment checks
./pre-deploy-check.sh || exit 1

# Backup
php artisan backup:run --only-db

# Deploy
git pull
composer install --no-dev
npm ci && npm run build

# Database
php artisan down --secret="deploy-secret"
php artisan migrate --force
php artisan up

# Validate
./post-deploy-validate.sh || ./rollback.sh

echo "✅ Deployment complete!"
```

> 💡 **Pro Tip**: Run deployment during low-traffic hours (2-4 AM CET) for minimal impact