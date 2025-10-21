# 🔍 POST-DEPLOYMENT MONITORING & VERIFICATION GUIDE
**Purpose**: Verify system integrity after each phase deployment
**Status**: Ready to use
**Screenshots**: Automated with links to verify changes

---

## 🎯 OVERVIEW

After each phase deployment, run these tests to verify:
- ✅ Database is still functioning
- ✅ UI/Forms load correctly
- ✅ No schema errors
- ✅ Cache invalidation working
- ✅ API responding
- ✅ Performance acceptable
- ✅ No new errors in logs
- ✅ Screenshots for visual verification

---

## 🚀 QUICK START (After Each Phase)

### 1️⃣ Run Post-Deployment Checks

```bash
# Phase 1 (after hotfixes)
DEPLOYMENT_PHASE=1 bash scripts/post-deployment-check.sh

# Phase 2 (after consistency)
DEPLOYMENT_PHASE=2 bash scripts/post-deployment-check.sh

# Phase 3-8 (pattern continues)
DEPLOYMENT_PHASE=3 bash scripts/post-deployment-check.sh
```

### 2️⃣ View Reports & Screenshots

```bash
# Reports are automatically created in:
storage/reports/phase_X_YYYYMMDD_HHMMSS/

# Screenshots are saved in:
storage/screenshots/phase_X_YYYYMMDD_HHMMSS/

# Open the index to navigate all artifacts:
open storage/reports/phase_X_YYYYMMDD_HHMMSS/INDEX.md
```

### 3️⃣ Check CI/CD Pipeline

```bash
# If deployed via CI/CD:
# 1. Go to GitHub Actions
# 2. Find the deployment run
# 3. Check test artifacts
# 4. Download screenshots
```

---

## 📊 TEST SUITE COMPONENTS

### Component 1: PHP Unit Tests
**File**: `tests/PostDeploymentHealthCheck.php`
**Runs**: 10 automated checks
**Time**: ~2 minutes

```bash
vendor/bin/phpunit tests/PostDeploymentHealthCheck.php
```

**Checks Performed**:
1. ✅ Database connectivity
2. ✅ Redis connectivity
3. ✅ Schema integrity
4. ✅ Appointment creation
5. ✅ Cache operations
6. ✅ API endpoints
7. ✅ Queue status
8. ✅ Log file integrity
9. ✅ Data consistency
10. ✅ Performance metrics

**Output**: `storage/reports/phase_X/phpunit.log`

---

### Component 2: E2E Screenshots
**File**: `tests/E2E/ScreenshotMonitoring.spec.ts`
**Runs**: Browser automation with Playwright
**Time**: ~5 minutes

```bash
DEPLOYMENT_PHASE=1 npx playwright test tests/E2E/ScreenshotMonitoring.spec.ts
```

**Screenshots Captured**:
- Login page
- Appointment form (no schema errors!)
- Cache invalidation flow
- Error handling
- Performance metrics
- And more...

**Output**: `storage/screenshots/phase_X/`

---

### Component 3: Post-Deployment Script
**File**: `scripts/post-deployment-check.sh`
**Runs**: All checks + generates reports
**Time**: ~10 minutes total

```bash
DEPLOYMENT_PHASE=1 bash scripts/post-deployment-check.sh
```

**Output Files**:
```
storage/reports/phase_1_YYYYMMDD_HHMMSS/
├── phpunit.log           # PHP unit test results
├── e2e.log              # Playwright test results
├── coverage/            # Code coverage HTML report
├── summary.txt          # Human-readable summary
├── status.env           # CI/CD integration file
└── INDEX.md             # Navigation file
```

---

## 📸 SCREENSHOTS & VERIFICATION

### Where Screenshots Are Stored

```
storage/screenshots/phase_X_YYYY-MM-DD_HHHMMSS/
├── 1_1_login_page.png
├── 1_2_appointment_form.png
├── 1_3_cache_test.png
├── 2_1_idempotency_check.png
├── 2_2_webhook_idempotency.png
├── 2_3_consistency_check.png
├── 3_1_error_handling.png
├── 3_2_circuit_breaker.png
├── 4_1_performance_load.png
└── report.json
```

### How to View Screenshots

#### Option A: Local Machine
```bash
# Open screenshot directory
open storage/screenshots/phase_1_*/

# View specific screenshot
open storage/screenshots/phase_1_YYYY-MM-DD/1_1_login_page.png
```

#### Option B: Via Web Browser
```bash
# If local server running:
http://localhost:8000/storage/screenshots/phase_1_YYYY-MM-DD/1_1_login_page.png
```

#### Option C: Direct File Access
```bash
# SSH to server
scp -r user@server:/var/www/api-gateway/storage/screenshots/phase_1_*/ ~/screenshots/

# Then view locally
open ~/screenshots/phase_1_*/
```

#### Option D: CI/CD Artifacts
```
GitHub Actions:
1. Go to repo → Actions
2. Select deployment run
3. Scroll to "Artifacts"
4. Download "phase_X_screenshots"
5. Extract and view locally
```

---

## ✅ VERIFICATION CHECKLIST (After Each Phase)

### For Each Phase Deployment:

- [ ] Run post-deployment check script
  ```bash
  DEPLOYMENT_PHASE=X bash scripts/post-deployment-check.sh
  ```

- [ ] Review screenshots folder
  ```bash
  ls -la storage/screenshots/phase_X_*/
  ```

- [ ] Check report summary
  ```bash
  cat storage/reports/phase_X_*/summary.txt
  ```

- [ ] Verify no new errors
  ```bash
  grep -i ERROR storage/reports/phase_X_*/phpunit.log
  ```

- [ ] Check performance metrics
  ```bash
  grep -i "performance\|latency\|ms" storage/reports/phase_X_*/phpunit.log
  ```

- [ ] Confirm all tests passed
  ```bash
  tail -10 storage/reports/phase_X_*/e2e.log
  ```

- [ ] Sign off in deployment log
  ```bash
  echo "✅ Phase X verified - all checks passed" >> deployment.log
  ```

---

## 🔴 TROUBLESHOOTING

### Issue: Screenshots Not Generated

**Cause**: Playwright not installed or browser missing
**Fix**:
```bash
npm install
npx playwright install
npx playwright install-deps
```

### Issue: Database Connection Failed

**Cause**: Database not running or credentials wrong
**Fix**:
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials
cat .env | grep DB_

# Restart database
docker restart postgres  # or your DB service
```

### Issue: Cache Tests Failed

**Cause**: Redis not running
**Fix**:
```bash
# Check Redis
redis-cli ping

# Restart Redis
docker restart redis  # or your Redis service
```

### Issue: API Tests Failed

**Cause**: Application not running or URL wrong
**Fix**:
```bash
# Start application
php artisan serve

# Check URL in script
grep TEST_URL scripts/post-deployment-check.sh
```

### Issue: Coverage Report Empty

**Cause**: PHP XDebug extension not installed
**Fix**:
```bash
# This is optional, not critical
# Coverage reports are nice-to-have, not must-have
```

---

## 📈 INTERPRETING RESULTS

### Green Checkmarks ✅
- Feature is working as expected
- Safe to proceed to next phase
- No rollback needed

### Red X's ❌
- Feature has failed
- **DO NOT PROCEED** to next phase
- Investigate error in logs
- Consider rollback

### Yellow Warnings ⚠️
- Degraded performance
- Feature working but slower than expected
- Monitor closely
- May be acceptable for specific phases

---

## 🔗 REPORT LINKS & ARTIFACTS

### After Running Tests, You'll Get:

```
📁 Report Directory
   ├── 📋 INDEX.md               ← START HERE (navigation)
   ├── 📊 summary.txt            ← Quick overview
   ├── 📈 coverage/index.html    ← Code coverage report
   ├── 📝 phpunit.log           ← PHP test results
   ├── 🧪 e2e.log               ← E2E test results
   └── 📷 ../screenshots/       ← All screenshots

📸 Screenshots Directory
   ├── 1_1_login_page.png
   ├── 1_2_appointment_form.png
   ├── 1_3_cache_test.png
   ├── ... (more screenshots)
   └── report.json
```

### Copy-Paste Template for Reports

```markdown
## Phase X Verification Report

**Date**: [Generated date]
**Phase**: [Phase number]
**Status**: ✅ PASSED / ❌ FAILED

### Test Results
- PHP Unit Tests: [X/10 passed]
- E2E Screenshots: [X screenshots captured]
- API Health: [Status]
- Database: [Status]
- Cache: [Status]

### Screenshots
- Screenshot folder: [Link]
- Coverage report: [Link]
- Full report: [Link]

### Issues Found
[List any issues]

### Verification Sign-Off
- Checked by: [Your name]
- Date: [Date]
- Approved: ✅ Yes / ❌ No
```

---

## 🚨 ROLLBACK PROCEDURE (If Tests Fail)

If post-deployment tests show failures:

```bash
# 1. Immediately stop accepting new traffic
# (If possible - notify ops team)

# 2. Check error logs
tail -50 storage/logs/laravel.log

# 3. Decide: Fix vs Rollback
#    - If quick fix: implement and retest
#    - If major issue: proceed with rollback

# 4. Rollback command (from Phase 1 example)
git revert HEAD~1          # Revert last commit
git push origin main       # Deploy previous version
bash scripts/post-deployment-check.sh  # Verify rollback

# 5. Post-mortem
# Create incident report explaining what failed
```

---

## 📊 MONITORING DURING PHASE EXECUTION

### Real-Time Monitoring (While Tests Run)

```bash
# Terminal 1: Watch logs
tail -f storage/logs/laravel.log | grep -i error

# Terminal 2: Monitor database
watch -n 5 'mysql -e "SELECT COUNT(*) as appointments FROM appointments;"'

# Terminal 3: Check system resources
watch -n 2 'free -h && df -h'

# Terminal 4: Run tests
bash scripts/post-deployment-check.sh
```

---

## 🔄 AUTOMATED MONITORING (CI/CD Integration)

If using GitHub Actions:

```yaml
# In .github/workflows/test-automation.yml

- name: Post-Deployment Health Check
  env:
    DEPLOYMENT_PHASE: ${{ matrix.phase }}
  run: |
    bash scripts/post-deployment-check.sh

- name: Upload Screenshots
  if: always()
  uses: actions/upload-artifact@v3
  with:
    name: phase-${{ matrix.phase }}-screenshots
    path: storage/screenshots/phase_${{ matrix.phase }}_*/

- name: Upload Reports
  if: always()
  uses: actions/upload-artifact@v3
  with:
    name: phase-${{ matrix.phase }}-reports
    path: storage/reports/phase_${{ matrix.phase }}_*/
```

---

## 📞 COMMON COMMANDS

```bash
# Run all checks for a phase
DEPLOYMENT_PHASE=1 bash scripts/post-deployment-check.sh

# Just run PHP tests
vendor/bin/phpunit tests/PostDeploymentHealthCheck.php

# Just run E2E tests
DEPLOYMENT_PHASE=1 npx playwright test tests/E2E/ScreenshotMonitoring.spec.ts

# View latest report
cat storage/reports/$(ls -t storage/reports | head -1)/summary.txt

# View latest screenshots
open storage/screenshots/$(ls -t storage/screenshots | head -1)/

# Check specific error
grep "Error\|Exception" storage/reports/phase_1_*/phpunit.log

# Generate coverage report
vendor/bin/phpunit tests/PostDeploymentHealthCheck.php --coverage-html storage/coverage

# Check performance
grep -i "ms\|performance" storage/reports/phase_1_*/phpunit.log
```

---

## 🎬 EXAMPLE: PHASE 1 DEPLOYMENT & VERIFICATION

```bash
# 1. Deploy Phase 1 (hotfixes)
git pull origin main
php artisan migrate
php artisan cache:clear

# 2. Run immediate health check
curl -s http://localhost:8000/api/health | jq .

# 3. Run comprehensive tests
DEPLOYMENT_PHASE=1 bash scripts/post-deployment-check.sh

# 4. View results
cat storage/reports/phase_1_*/summary.txt

# 5. Review screenshots
open storage/screenshots/phase_1_*/1_1_login_page.png
open storage/screenshots/phase_1_*/1_2_appointment_form.png
open storage/screenshots/phase_1_*/1_3_cache_test.png

# 6. If all green ✅, proceed to Phase 2
# If any red ❌, investigate and fix before proceeding

# 7. Create deployment log entry
echo "✅ Phase 1 verification passed - $(date)" >> deployment.log
```

---

## 📋 VERIFICATION SIGN-OFF TEMPLATE

**After each successful phase deployment**, fill this out:

```
╔══════════════════════════════════════════════════════════════╗
║         PHASE X VERIFICATION SIGN-OFF                       ║
╚══════════════════════════════════════════════════════════════╝

Date: ________________
Phase: _______________
Deployment Time: ______ to ______

Test Results:
  [ ] PHP Unit Tests (10/10 passed)
  [ ] E2E Screenshots (all captured)
  [ ] API Health Check (200 OK)
  [ ] Database Integrity (verified)
  [ ] No Critical Errors (logs checked)
  [ ] Performance Acceptable (<3000ms)

Screenshots Reviewed:
  [ ] Login page loads
  [ ] Appointment form displays correctly
  [ ] No schema errors
  [ ] Cache working
  [ ] Error messages clear

Verified By: ________________
Date/Time: ________________
Status: ✅ APPROVED / ⚠️ ISSUES / ❌ FAILED

Notes:
_________________________________________________________________________
_________________________________________________________________________

Next Phase Ready: ✅ YES / ❌ NO - Reason: _______________________
```

---

## 🎯 SUMMARY

After each phase:
1. Run: `DEPLOYMENT_PHASE=X bash scripts/post-deployment-check.sh`
2. Wait for tests to complete (~10 min)
3. Review: `storage/reports/phase_X_*/summary.txt`
4. View: `storage/screenshots/phase_X_*/` (all screenshots)
5. Verify: All checks ✅ green
6. Sign off: Fill verification form
7. Proceed: To next phase or investigate ❌ failures

**Links are auto-generated in reports for easy verification.**

---

**Status**: 🟢 Ready to use immediately after each phase deployment
