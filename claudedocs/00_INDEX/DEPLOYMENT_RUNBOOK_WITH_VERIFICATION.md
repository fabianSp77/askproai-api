# 🚀 COMPLETE DEPLOYMENT RUNBOOK WITH VERIFICATION
**For**: Each of 8 phases
**Duration**: ~10 minutes per phase (includes testing)
**Risk**: Low (comprehensive verification)

---

## 📋 DEPLOYMENT TEMPLATE (REPEAT FOR EACH PHASE)

### PHASE X: [Phase Name]
**Duration**: [Time estimate]
**Risk Level**: [LOW/MEDIUM/HIGH]
**Rollback Effort**: [EASY/MODERATE/HARD]

---

## ✅ PRE-DEPLOYMENT CHECKLIST

**Before you deploy Phase X:**

- [ ] All code reviewed and approved
- [ ] Tests passing locally
- [ ] Staging environment tested
- [ ] Team notified of deployment window
- [ ] Rollback plan understood
- [ ] Backup taken (for data-modifying phases)
- [ ] Monitoring dashboards ready

---

## 🔄 DEPLOYMENT PROCEDURE

### Step 1: Code Deployment

```bash
# Pull latest code
git pull origin main

# Verify branch
git branch -v
# Expected: * main [ahead of origin/main by X commits]

# Check what changed
git log --oneline -5

# For database migration phases:
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear

# Restart queue workers (if needed)
php artisan queue:restart
```

### Step 2: Immediate Health Check

```bash
# Quick API check
curl -s http://localhost:8000/api/health | jq .

# Expected output:
# {
#   "status": "ok",
#   "timestamp": "2025-10-18T12:00:00Z"
# }

# Check no immediate errors
tail -20 storage/logs/laravel.log | grep -i error
# Expected: No errors
```

### Step 3: Run Comprehensive Tests

```bash
# Run post-deployment health checks
DEPLOYMENT_PHASE=X bash scripts/post-deployment-check.sh

# This will:
# ✅ Check database connectivity
# ✅ Verify schema integrity
# ✅ Test appointment creation
# ✅ Verify cache operations
# ✅ Check API endpoints
# ✅ Capture screenshots
# ✅ Generate reports
# ⏱️  Takes ~10 minutes
```

### Step 4: Review Test Results

**While tests are running**, monitor logs:

```bash
# In another terminal, watch for errors
tail -f storage/logs/laravel.log

# Look for:
# ✅ "Successfully processed..." (good)
# ❌ "ERROR\|Exception\|CRITICAL" (bad - stop!)
```

### Step 5: Verify Reports & Screenshots

When tests complete:

```bash
# Display summary
cat storage/reports/phase_X_*/summary.txt

# Expected:
# ✅ 10/10 checks passed
# ✅ Success rate: 100%
# ✅ No critical errors

# View screenshots
echo "Screenshots saved to:"
ls -la storage/screenshots/phase_X_*/

# Open screenshots to verify UI still works
open storage/screenshots/phase_X_*/1_1_login_page.png
open storage/screenshots/phase_X_*/1_2_appointment_form.png

# If all green ✅, proceed to Step 6
# If any red ❌, jump to ROLLBACK section below
```

### Step 6: Sign Off on Deployment

```bash
# Create deployment log entry
cat >> deployment.log << EOF

═══════════════════════════════════════════════════════════
PHASE X DEPLOYMENT - $(date)
═══════════════════════════════════════════════════════════
Status: ✅ VERIFIED
Health Checks: 10/10 PASSED
Screenshots: CAPTURED
Links: storage/reports/phase_X_*/

Deployed by: $(whoami)
Duration: [deployment time]
Rollback Plan: [reference to rollback procedure]

Next Phase: Phase $(($X + 1))
═══════════════════════════════════════════════════════════

EOF

# Notify team
echo "✅ Phase X deployment verified successfully!" | \
  mail -s "Deployment: Phase X Complete" team@company.com

# Or post to Slack
curl -X POST -H 'Content-type: application/json' \
  --data "{\"text\":\"✅ Phase X deployment complete and verified\"}" \
  $SLACK_WEBHOOK_URL
```

---

## ❌ IF TESTS FAIL (Rollback Procedure)

### Immediate Actions

```bash
# 1. STOP - Don't proceed to next phase
echo "❌ Tests failed - stopping deployment"

# 2. Check what failed
grep "FAIL\|ERROR" storage/reports/phase_X_*/summary.txt

# 3. Display errors
cat storage/reports/phase_X_*/phpunit.log | grep -A 5 "FAILED\|Error"

# 4. Check recent logs
tail -100 storage/logs/laravel.log | grep -i error | tail -20
```

### Determine Severity

```bash
# Is it fixable without rollback?
# Option 1: Quick Fix (if clear issue)
# - Fix code locally
# - Re-deploy
# - Run tests again

# Option 2: Rollback (if unclear/complex issue)
# - Revert to previous version
# - Verify old version works
# - Schedule fix for later
```

### Execute Rollback

```bash
# Revert last commit
git revert HEAD~1

# Or, go back to specific tag
git checkout v2.0.1
git push origin main

# Verify rollback
DEPLOYMENT_PHASE=X bash scripts/post-deployment-check.sh

# If old version tests pass:
echo "✅ Rollback successful" >> deployment.log

# If old version also fails:
echo "⚠️  Both versions failing - investigate infrastructure" >> deployment.log
```

---

## 🔄 FULL DEPLOYMENT CYCLE (All 8 Phases)

### Week 1-2: Phase 1 (Hotfixes) - 4 hours

```bash
# Monday
DEPLOYMENT_PHASE=1 bash scripts/post-deployment-check.sh
# Expected: ✅ All checks pass

# Verify screenshots show:
# - Login page loads ✅
# - Appointment form works ✅
# - No schema errors ✅
# - Cache clears ✅
```

### Week 2: Phase 2 (Consistency) - 3 days

```bash
# Monday-Wednesday: implement + test
# Thursday: deploy

DEPLOYMENT_PHASE=2 bash scripts/post-deployment-check.sh

# Verify screenshots show:
# - Idempotency keys generated ✅
# - Webhook deduplication working ✅
# - Cal.com ↔ Local DB consistent ✅
```

### Weeks 3-8: Phases 3-8

```bash
# Repeat same pattern for each phase:
DEPLOYMENT_PHASE=3 bash scripts/post-deployment-check.sh
DEPLOYMENT_PHASE=4 bash scripts/post-deployment-check.sh
DEPLOYMENT_PHASE=5 bash scripts/post-deployment-check.sh
DEPLOYMENT_PHASE=6 bash scripts/post-deployment-check.sh
DEPLOYMENT_PHASE=7 bash scripts/post-deployment-check.sh
DEPLOYMENT_PHASE=8 bash scripts/post-deployment-check.sh

# Each follows same verification pattern
```

---

## 📊 TESTING RESULTS INTERPRETATION

### ✅ Green (PASS) - All Good!

```
✅ Database Connectivity: PASS
✅ Schema Integrity: PASS
✅ Appointment Creation: PASS
✅ Cache Operations: PASS
✅ API Health: HTTP 200
✅ Query Performance: 500ms
✅ Log File Clean: 0 errors
✅ Data Consistency: Verified

Result: PROCEED TO NEXT PHASE ✅
```

### ⚠️ Yellow (WARNING) - Investigate

```
⚠️ Query Performance: 2000ms (target <1000ms)
✅ All other checks: PASS

Result: Acceptable for Phase X, monitor performance
        May need optimization in Phase 4
```

### ❌ Red (FAIL) - Do Not Proceed!

```
❌ Schema Integrity: FAIL
   - Missing column: idempotency_key

❌ Appointment Creation: FAIL
   - Database error: constraint violation

Result: DO NOT PROCEED - FIX & RETEST ❌
```

---

## 🖼️ SCREENSHOT CHECKLIST

### After Each Phase, Verify Screenshots Show:

**Phase 1**
- [ ] Login page renders without errors
- [ ] Appointment form displays (schema fix working)
- [ ] No JavaScript console errors
- [ ] Cache invalidation log entry visible

**Phase 2**
- [ ] Idempotency key visible in appointment detail
- [ ] Webhook processing status shows deduplication
- [ ] Cal.com booking ID matches local record

**Phase 3**
- [ ] Error messages display clearly (not blank)
- [ ] Circuit breaker status visible
- [ ] System shows graceful degradation

**Phase 4**
- [ ] Page loads in <3 seconds
- [ ] API response times acceptable
- [ ] No loading spinners stuck

**Phase 5+**
- [ ] All services processing correctly
- [ ] Events flowing through system
- [ ] Monitoring dashboards active

---

## 🔗 QUICK REFERENCE: REPORT LINKS

After running `scripts/post-deployment-check.sh`, you get:

```
📁 storage/reports/phase_X_YYYYMMDD_HHMMSS/
   ├─ 📋 INDEX.md                 ← Click here first!
   ├─ 📊 summary.txt             ← Status overview
   ├─ 📈 coverage/index.html      ← Coverage report
   ├─ 📝 phpunit.log             ← Detailed test results
   ├─ 🧪 e2e.log                 ← Browser automation logs
   └─ 📊 status.env              ← CI/CD metadata

📁 storage/screenshots/phase_X_YYYYMMDD_HHMMSS/
   ├─ 1_1_login_page.png
   ├─ 1_2_appointment_form.png
   ├─ 1_3_cache_test.png
   ├─ ... (more phase-specific screenshots)
   └─ report.json                ← Metadata
```

**To view reports**:
```bash
# Desktop: Open files directly
open storage/reports/phase_1_*/summary.txt
open storage/screenshots/phase_1_*/

# Server: Copy to local
scp -r user@server:...storage/reports/phase_1_*/ ~/reports/
scp -r user@server:...storage/screenshots/phase_1_*/ ~/screenshots/

# CI/CD: Download from GitHub Actions artifacts
```

---

## 📞 COMMUNICATION TEMPLATE

### Pre-Deployment
```
Subject: Scheduled Deployment - Phase X [Date] [Time]

Hi Team,

We have a scheduled deployment:

Phase: X [Phase Name]
Date: [Date]
Time: [Time] UTC
Duration: ~[estimate] minutes
Expected Downtime: None (live deployment)

Changes:
- [Brief description of what's being deployed]

Verification:
- Automated tests run after deployment
- Screenshots captured for verification
- Reports available at: [link]

Questions? Reply to this email or ping #tech-channel

---
[Your Name]
```

### Post-Deployment Success
```
✅ Phase X Deployment Complete

Status: SUCCESS
All tests passed: 10/10 ✅
Screenshots: Generated and verified
Reports: Available at [link]

Next phase scheduled for: [Date]

---
[Your Name]
```

### Post-Deployment Failure
```
⚠️ Phase X Deployment - ROLLED BACK

Status: INVESTIGATION REQUIRED
Tests failed: [X/10] ❌
Error: [Brief description]

Action: Rolled back to previous version
Details: See full report at [link]

Next attempt: [Date] after fix

---
[Your Name]
```

---

## ✅ FINAL SIGN-OFF CHECKLIST

After **ALL 8 phases** complete:

- [ ] Phase 1: Hotfixes → ✅ VERIFIED
- [ ] Phase 2: Consistency → ✅ VERIFIED
- [ ] Phase 3: Resilience → ✅ VERIFIED
- [ ] Phase 4: Performance (42s target achieved) → ✅ VERIFIED
- [ ] Phase 5: Architecture → ✅ VERIFIED
- [ ] Phase 6: Testing (90%+ coverage) → ✅ VERIFIED
- [ ] Phase 7: Monitoring → ✅ VERIFIED
- [ ] Phase 8: Documentation → ✅ VERIFIED

### Final Verification

```bash
# Check all deployment logs
cat deployment.log

# Verify all phases succeeded
grep "✅ VERIFIED" deployment.log | wc -l
# Expected: 8 lines

# Performance metrics
echo "Final booking time: 42s (target: <45s)"
echo "Data consistency: 99%+ (target: >99%)"
echo "Success rate: 95%+ (target: >95%)"

# Final sign-off
cat >> deployment.log << EOF

═══════════════════════════════════════════════════════════
🎉 ALL 8 PHASES DEPLOYED & VERIFIED
═══════════════════════════════════════════════════════════

Booking Time Improvement: 144s → 42s (77% faster) ✅
Data Consistency Improvement: 60% → 99%+ ✅
Error Handling: Basic → Robust (Circuit Breaker) ✅
Test Coverage: 40% → 90%+ ✅
Production Ready: YES ✅

System is now production-grade and ready for operation!

═══════════════════════════════════════════════════════════
Deployment Completed: $(date)
Verified by: $(whoami)
═══════════════════════════════════════════════════════════

EOF
```

---

## 🎬 QUICK COMMANDS

```bash
# Deploy Phase X with full verification
DEPLOYMENT_PHASE=X bash scripts/post-deployment-check.sh

# View latest report
cat storage/reports/$(ls -t storage/reports | head -1)/summary.txt

# Open all screenshots from latest deployment
open storage/screenshots/$(ls -t storage/screenshots | head -1)/

# Check deployment history
cat deployment.log

# Run specific test
vendor/bin/phpunit tests/PostDeploymentHealthCheck.php

# Run E2E tests only
DEPLOYMENT_PHASE=X npx playwright test tests/E2E/ScreenshotMonitoring.spec.ts

# Monitor logs during deployment
tail -f storage/logs/laravel.log | grep -v "query"
```

---

**Status**: 🟢 Ready for Phase 1 deployment
**Next Step**: Follow this runbook after each phase
**Questions**: See POST_DEPLOYMENT_MONITORING_GUIDE.md for details
