# Deployment Quick Reference Card

**Feature:** Phone-Based Authentication with Phonetic Matching
**Date:** 2025-10-06
**Status:** 🔴 BLOCKED - DO NOT DEPLOY

---

## 🚨 CRITICAL BLOCKERS (4)

```
❌ 1. Git: NO COMMITS (no rollback possible)
❌ 2. Migration: DUPLICATE INDEX (will fail)
⚠️ 3. Cross-Tenant Search: NOT VERIFIED (GDPR risk)
⚠️ 4. Input Validation: NOT VERIFIED (DoS risk)
```

---

## ⚡ QUICK FIX COMMANDS

### 1. Initialize Git (2 hours)
```bash
cd /var/www/api-gateway
git add .
git commit -m "feat: production baseline before phonetic matching deployment"
git checkout -b feature/phonetic-matching-deployment
```

### 2. Remove Duplicate Migration (5 minutes)
```bash
rm database/migrations/2025_10_06_162757_add_phone_index_to_customers_table.php
git add database/migrations/
git commit -m "fix: remove duplicate phone index migration"
```

### 3. Verify Cross-Tenant Search (30 minutes)
```bash
grep -n "where.*company_id.*!=" app/Http/Controllers/Api/RetellApiController.php
grep -n "Cross-tenant" app/Http/Controllers/Api/RetellApiController.php
# Expected: No results (cross-tenant search removed)
```

### 4. Add Input Validation (1 hour)
```bash
# Add to PhoneticMatcher.php:encode() method:
if (mb_strlen($name) > 100) {
    Log::warning('Name too long', ['length' => mb_strlen($name)]);
    $name = mb_substr($name, 0, 100);
}
```

---

## 📋 PRE-DEPLOYMENT CHECKLIST

```
[ ] Git baseline commit exists
[ ] Feature branch created
[ ] Database backup completed (70 customers)
[ ] Migration file removed
[ ] Cross-tenant search verified removed
[ ] Input validation added
[ ] Tests passing: php artisan test --filter Phonetic
[ ] Rate limiting verified: 3 attempts/hour
[ ] PII masking verified: LogSanitizer exists
[ ] Feature flag OFF: FEATURE_PHONETIC_MATCHING_ENABLED=false
```

---

## 🚀 DEPLOYMENT COMMANDS (15 minutes)

### Pre-Deployment
```bash
# Create backup
mysqldump -u askproai_user -paskproai_secure_pass_2024 \
  -h 127.0.0.1 askproai_db \
  > /backup/phonetic-$(date +%Y%m%d_%H%M%S).sql

# Verify tests
php artisan test
```

### Deployment
```bash
# Merge feature branch
git checkout master
git merge --no-ff feature/phonetic-matching-deployment

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart PHP-FPM (zero-downtime)
sudo systemctl reload php8.3-fpm
```

### Verification
```bash
# Health check
curl -I http://localhost/api/health
# Expected: HTTP/1.1 200 OK

# Feature flag check
php artisan tinker
>>> config('features.phonetic_matching_enabled');
# Expected: false
>>> exit

# Database query performance
mysql -u askproai_user -paskproai_secure_pass_2024 -h 127.0.0.1 askproai_db -e "
  EXPLAIN SELECT * FROM customers
  WHERE company_id = 15 AND phone LIKE '%12345%' LIMIT 1;
"
# Expected: key: idx_customers_company_phone, rows: <10
```

---

## 🔄 ROLLBACK COMMANDS (10 minutes)

### Emergency Rollback
```bash
# Get previous commit
git log --oneline -5

# Reset to previous commit
git reset --hard <PREVIOUS_COMMIT_HASH>

# Clear caches
php artisan config:clear && php artisan config:cache

# Restart PHP-FPM
sudo systemctl reload php8.3-fpm

# Verify
curl -I http://localhost/api/health
```

### Database Rollback (if needed)
```bash
cd /backup
gunzip phonetic-*.sql.gz
mysql -u askproai_user -paskproai_secure_pass_2024 \
  -h 127.0.0.1 askproai_db < phonetic-*.sql
```

---

## 📊 MONITORING COMMANDS

### Application Health
```bash
# Real-time logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "error\|phonetic"

# PHP-FPM status
sudo systemctl status php8.3-fpm

# Response times
for i in {1..10}; do
  curl -s -o /dev/null -w "%{time_total}s\n" http://localhost/api/health
done
```

### Database Performance
```bash
# Active connections
mysql -u askproai_user -paskproai_secure_pass_2024 \
  -h 127.0.0.1 -e "SHOW PROCESSLIST;" askproai_db | wc -l

# Customer count (should be 70)
mysql -u askproai_user -paskproai_secure_pass_2024 \
  -h 127.0.0.1 -e "SELECT COUNT(*) FROM customers;" askproai_db
```

### Security Checks
```bash
# Check for PII in logs
grep -E "\+[0-9]{10,}" storage/logs/laravel-*.log
# Expected: No results (all masked)

# Rate limiting test
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo config('features.phonetic_matching_rate_limit') . PHP_EOL;
"
# Expected: 3
```

---

## ⏱️ TIMELINE ESTIMATE

```
Total Preparation: 14-16 hours
├─ Git initialization: 2 hours
├─ Code fixes: 6-8 hours
├─ Testing: 4 hours
└─ Security audit: 2 hours

Deployment: 15 minutes
├─ Code deployment: 5 min
├─ Cache clearing: 5 min
└─ Verification: 5 min

Post-Deployment: 24 hours monitoring
Expected Downtime: 0 minutes
```

---

## 🎯 SUCCESS CRITERIA

```
✅ HTTP 200 on health check
✅ Database queries <5ms
✅ Feature flag OFF (false)
✅ Customer count = 70 (unchanged)
✅ No errors in logs
✅ Rate limiting working (3/hour)
✅ PII masked in logs
✅ P95 response time <100ms
```

---

## 🚨 ROLLBACK TRIGGERS

```
🔴 Immediate Rollback:
- HTTP 500 errors on critical endpoints
- Database connection failures
- P95 response time >500ms
- PII found in logs
- Customer error rate >5%

🟡 Consider Rollback:
- Error rate 2-5%
- P95 response time 200-500ms
- Customer complaints increasing
- Unusual traffic patterns
```

---

## 📞 EMERGENCY CONTACTS

```
Backend Lead: <PHONE>
DevOps Engineer: <PHONE>
Database Admin: <PHONE>
Incident Commander: <PHONE>

On-Call: <PHONE>
Escalation: <PHONE>
```

---

## 📚 FULL DOCUMENTATION

```
Executive Summary:
/var/www/api-gateway/claudedocs/DEPLOYMENT_EXECUTIVE_SUMMARY.md

Complete Runbook (46KB):
/var/www/api-gateway/claudedocs/DEPLOYMENT_RUNBOOK_PHONETIC_MATCHING.md

Technical Analysis:
/var/www/api-gateway/ULTRATHINK_SYNTHESIS_NEXT_STEPS_PHONETIC_AUTH.md
```

---

## ✅ GO/NO-GO DECISION

**Current Status: 🔴 NO-GO**

**Blockers:**
1. Git repository not initialized
2. Duplicate index migration
3. Cross-tenant search not verified
4. Input validation missing

**After Fixes: 🟢 GO**
- Estimated: 14-16 hours of work
- Recommended deployment: Tuesday/Wednesday 2-5 AM CET
- Zero-downtime deployment possible

---

**Last Updated:** 2025-10-06
**Version:** 1.0
**Owner:** DevOps Team
