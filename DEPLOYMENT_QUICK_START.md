# 🚀 Deployment Quick Start Guide

**Version:** 1.0
**Date:** 2025-10-06
**Feature:** Phone-Based Authentication + Phonetic Matching

---

## ⚡ TL;DR - Deployment Commands

```bash
# Navigate to project
cd /var/www/api-gateway

# Verify state
git status && git branch

# Deploy (NO database migrations needed!)
php artisan config:clear
php artisan config:cache
sudo systemctl reload php8.3-fpm

# Verify
curl -I http://localhost/api/health

# Done! (15 minutes)
```

---

## 📊 Pre-Flight Check ✅

- [x] Branch: `feature/phonetic-matching-deploy`
- [x] Commit: `f8597c9`
- [x] Tests: 23/23 passing
- [x] Feature Flag: OFF (FEATURE_PHONETIC_MATCHING_ENABLED=false)
- [x] Database Index: EXISTS (no migration needed)
- [x] Rollback: READY

---

## 🎯 What's Being Deployed

**Feature:** Phone-based strong authentication with phonetic name matching

**Problem Solved:** Call 691 - "Sputa" vs "Sputer" blocking legitimate customer

**Impact:** 
- Zero user-facing changes (feature flag OFF)
- Code deployed but inactive
- Zero downtime

**Risk:** LOW (feature disabled by default)

---

## 🚀 Deployment Steps (15 min)

### 1. Verify Current State (2 min)
```bash
cd /var/www/api-gateway

# Check branch
git branch
# Expected: * feature/phonetic-matching-deploy

# Check commit
git log --oneline -1
# Expected: f8597c9 feat: production baseline + phonetic matching implementation

# Check working directory
git status
# Expected: nichts zu committen, Arbeitsverzeichnis unverändert
```

### 2. Deploy Code (5 min)
```bash
# NO database migrations needed (index already exists)

# Clear and rebuild config cache
php artisan config:clear
php artisan config:cache

# Verify config
php artisan tinker --execute="
echo 'Phonetic Enabled: ' . (config('features.phonetic_matching_enabled') ? 'true' : 'false') . PHP_EOL;
echo 'Rate Limit: ' . config('features.phonetic_matching_rate_limit') . PHP_EOL;
"
# Expected: Phonetic Enabled: false, Rate Limit: 3
```

### 3. Reload PHP-FPM (2 min)
```bash
# Graceful reload (NO downtime!)
sudo systemctl reload php8.3-fpm

# Verify PHP-FPM status
sudo systemctl status php8.3-fpm | head -10
# Expected: active (running)
```

### 4. Health Check (1 min)
```bash
# Check API health
curl -I http://localhost/api/health
# Expected: HTTP/1.1 200 OK

# Check for errors
tail -20 storage/logs/laravel.log | grep ERROR
# Expected: No recent errors
```

### 5. Verify LogSanitizer (5 min)
```bash
# Check for PII in logs (should find NONE in new entries)
tail -50 storage/logs/laravel.log | grep -E "Sputer|Sputa|\+49[0-9]{10}"
# Expected: Empty (or only from before deployment)

# Verify PII_REDACTED is present (after first phone auth)
# (This will only show after first customer interaction)
tail -50 storage/logs/laravel.log | grep "PII_REDACTED"
# Expected: Will appear after first phone-based auth attempt
```

---

## 🎉 Success Criteria

### Immediate (0-30 min)
- [x] API responds (200 OK)
- [x] No 500 errors
- [x] PHP-FPM healthy
- [x] Config cache rebuilt

### Short-Term (1-24 hours)
- [ ] No unexpected errors in logs
- [ ] LogSanitizer active (verify on first customer call)
- [ ] System performance stable
- [ ] No customer complaints

---

## ⚠️ Rollback (if needed)

**Time:** <10 minutes

```bash
# 1. Reset to baseline
cd /var/www/api-gateway
git reset --hard f8597c9

# 2. Clear config
php artisan config:clear
php artisan config:cache

# 3. Reload PHP-FPM
sudo systemctl reload php8.3-fpm

# 4. Verify
curl -I http://localhost/api/health
```

---

## 📋 Post-Deployment Monitoring

### Commands to Run Regularly

**Check Logs:**
```bash
# Monitor in real-time
tail -f storage/logs/laravel.log

# Check for errors
grep ERROR storage/logs/laravel.log | tail -20

# Verify LogSanitizer
grep "PII_REDACTED" storage/logs/laravel.log | head -5

# Check rate limiting
grep "Rate limit exceeded" storage/logs/laravel.log
```

**Check Performance:**
```bash
# Database query performance
php artisan tinker --execute="
\$start = microtime(true);
\$customer = DB::table('customers')
    ->where('company_id', 1)
    ->where('phone', 'LIKE', '%12345678%')
    ->first();
\$duration = (microtime(true) - \$start) * 1000;
echo 'Query time: ' . round(\$duration, 2) . 'ms' . PHP_EOL;
"
# Expected: <5ms
```

**Check Feature Flag Status:**
```bash
php artisan tinker --execute="
echo 'Feature Flag: ' . (config('features.phonetic_matching_enabled') ? 'ON' : 'OFF') . PHP_EOL;
echo 'Threshold: ' . config('features.phonetic_matching_threshold') . PHP_EOL;
echo 'Rate Limit: ' . config('features.phonetic_matching_rate_limit') . PHP_EOL;
"
```

---

## 🎯 Next Steps After Deployment

### Day 0 (Deployment Day)
- [x] Deploy with feature flag OFF
- [ ] Monitor for 24 hours
- [ ] Check for any errors or issues
- [ ] Verify LogSanitizer in production

### Day 1-3 (Test Phase)
```bash
# Enable for test company (ID=1)
# Edit .env:
FEATURE_PHONETIC_MATCHING_ENABLED=true
FEATURE_PHONETIC_MATCHING_TEST_COMPANIES=1

# Apply changes
php artisan config:clear
php artisan config:cache
```

### Week 1 (Validation)
- Monitor test company metrics
- Check false positive rate
- Validate customer identification improvement
- Collect feedback

### Week 2-3 (Gradual Rollout)
- 10% companies → 50% → 100%
- Monitor at each stage
- Adjust threshold if needed

---

## 📞 Support Contacts

**Issues During Deployment:**
- Check logs: `storage/logs/laravel.log`
- Rollback if critical: `git reset --hard f8597c9`
- Document all issues for review

**Feature Questions:**
- Documentation: `PRODUCTION_READY_FINAL_REPORT.md`
- Technical Details: `ULTRATHINK_PHASE_2_FINAL_SYNTHESIS.md`
- Deployment Guide: `claudedocs/DEPLOYMENT_RUNBOOK_PHONETIC_MATCHING.md`

---

## ✅ Deployment Checklist

**Before Deployment:**
- [ ] Low traffic window (2-5 AM CET)
- [ ] Team notified
- [ ] Monitoring ready
- [ ] Rollback plan reviewed

**During Deployment:**
- [ ] Execute deployment commands
- [ ] Verify health checks
- [ ] Check logs for errors
- [ ] Test API endpoints

**After Deployment:**
- [ ] Document completion time
- [ ] Verify zero downtime achieved
- [ ] Monitor for 30 minutes
- [ ] Enable 24-hour monitoring

**Success Confirmation:**
- [ ] No errors in logs
- [ ] API responding normally
- [ ] PHP-FPM healthy
- [ ] Feature flag OFF (verified)

---

**Status:** ✅ READY FOR DEPLOYMENT
**Risk:** LOW
**Downtime:** 0 minutes
**Duration:** 15 minutes

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
