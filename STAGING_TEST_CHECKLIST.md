# 📋 STAGING TEST CHECKLIST - Customer Portal Phase 1

**Purpose**: Comprehensive testing checklist before production deployment
**Environment**: staging.askproai.de
**Branch**: feature/customer-portal
**Estimated Time**: 4-6 hours

---

## 🎯 TESTING OBJECTIVES

Before deploying to production, we must verify:

- ✅ **Security**: No vulnerabilities, proper isolation, feature flags work
- ✅ **Performance**: Indexes work, response times acceptable, handles load
- ✅ **Functional**: All features work as expected
- ✅ **Integration**: Admin panel unaffected, migrations safe
- ✅ **Rollback**: Can disable features instantly if needed

---

## 📊 TESTING SUMMARY

| Category | Tests | Duration | Scripts/Commands |
|----------|-------|----------|------------------|
| Security | 10 tests | 30 min | `vendor/bin/pest tests/Feature/CustomerPortal/SecurityTest.php` |
| Performance | 5 tests | 20 min | `php scripts/performance_test_indexes.php` |
| Load Testing | 3 scenarios | 30 min | `k6 run tests/load/customer_portal_load_test.js` |
| Functional | 15 tests | 90 min | Manual testing (see below) |
| Integration | 8 tests | 30 min | Manual testing (see below) |
| Rollback | 3 tests | 15 min | Manual testing (see below) |
| **TOTAL** | **44 tests** | **4-6 hours** | |

---

## 🔐 SECTION 1: SECURITY TESTING (30 min)

### Automated Security Tests

Run PHPUnit security test suite:

```bash
cd /var/www/api-gateway

# Run security tests
vendor/bin/pest tests/Feature/CustomerPortal/SecurityTest.php --verbose

# Expected output:
# ✓ admin cannot access customer portal (VULN-PORTAL-001)
# ✓ customer owner can access portal when enabled
# ✓ customer portal disabled by feature flag
# ✓ user without company cannot access portal
# ✓ user cannot view other company call sessions (VULN-PORTAL-002)
# ✓ user can view own company call sessions
# ✓ staff cannot update other company call sessions
# ✓ company scoped query only returns own data
# ✓ cannot directly query other company sessions
# ✓ appointments are company scoped
# ... (10 tests total)
```

**Pass Criteria**: All 10 tests must pass (100%)

### Manual Security Verification

#### Test 1.1: Panel Access Control (VULN-PORTAL-001)

- [ ] **Admin cannot access /portal**
  ```
  1. Login as: admin@staging.local / AdminPass123!
  2. Navigate to: https://staging.askproai.de/portal
  3. Expected: 404 Not Found or redirect to /admin
  ```

- [ ] **Customer can access /portal**
  ```
  1. Login as: customer@staging.local / TestPass123!
  2. Navigate to: https://staging.askproai.de/portal
  3. Expected: Customer dashboard loads
  ```

- [ ] **Admin cannot access /portal via direct URL**
  ```
  1. Logout, login as admin@staging.local
  2. Try: https://staging.askproai.de/portal/call-sessions
  3. Expected: 403 Forbidden or 404 Not Found
  ```

#### Test 1.2: Multi-Tenancy Isolation (VULN-PORTAL-002)

- [ ] **Company A cannot see Company B data**
  ```
  1. Login as Company A customer
  2. Navigate to Call Sessions
  3. Verify: Only Company A calls visible
  4. Check: company_id in URL queries = Company A ID
  ```

- [ ] **Direct API calls respect company scope**
  ```bash
  # Get Company A customer auth token
  TOKEN=$(curl -X POST https://staging.askproai.de/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"customer@staging.local","password":"TestPass123!"}' \
    | jq -r '.token')

  # Try to access other company's sessions (should fail)
  curl -H "Authorization: Bearer $TOKEN" \
    https://staging.askproai.de/api/customer-portal/call-sessions?company_id=999

  # Expected: Empty results or 403 Forbidden
  ```

#### Test 1.3: Feature Flag Kill-Switch

- [ ] **Master flag disables all portal access**
  ```bash
  # Disable feature flag
  echo "FEATURE_CUSTOMER_PORTAL=false" >> /var/www/api-gateway/.env.staging
  php artisan config:clear --env=staging

  # Try to access portal
  curl -I https://staging.askproai.de/portal

  # Expected: HTTP/1.1 404 Not Found

  # Re-enable
  sed -i 's/FEATURE_CUSTOMER_PORTAL=false/FEATURE_CUSTOMER_PORTAL=true/' \
    /var/www/api-gateway/.env.staging
  php artisan config:clear --env=staging
  ```

---

## ⚡ SECTION 2: PERFORMANCE TESTING (20 min)

### Automated Performance Tests

Run database index benchmarks:

```bash
cd /var/www/api-gateway

# Run performance tests
php scripts/performance_test_indexes.php

# Expected output:
# TEST 1: Company Dashboard Query
#   ✅ PASS - 12.34ms (< 50ms target)
#   Index Used: YES (idx_retell_sessions_company_status)
#
# TEST 2: Customer Call History
#   ✅ PASS - 8.21ms (< 20ms target)
#   Index Used: YES (idx_retell_sessions_customer_date)
# ...
```

**Pass Criteria**:
- All 5 tests must pass
- All queries must use indexes (no Seq Scan)
- Response times must meet targets

### Manual Performance Verification

#### Test 2.1: Company Dashboard Load Time

- [ ] **Dashboard loads in < 2 seconds**
  ```
  1. Open browser DevTools (Network tab)
  2. Login as customer@staging.local
  3. Navigate to /portal
  4. Check: Initial page load < 2s
  5. Check: No slow queries in logs
  ```

#### Test 2.2: Call Sessions List

- [ ] **50 call sessions load in < 1 second**
  ```
  1. Navigate to Call Sessions list
  2. Check Network tab: /api/customer-portal/call-sessions
  3. Verify: Response time < 1s
  4. Verify: No N+1 query warnings in logs
  ```

#### Test 2.3: Appointment Calendar

- [ ] **Calendar with 100 appointments loads in < 1.5s**
  ```
  1. Navigate to Appointments
  2. Load month view with many appointments
  3. Check: Response time < 1.5s
  4. Verify: Uses partial index (idx_appointments_customer_active)
  ```

---

## 🔥 SECTION 3: LOAD TESTING (30 min)

### k6 Load Test Scenarios

#### Scenario 3.1: Smoke Test (10 VUs, 1 minute)

```bash
cd /var/www/api-gateway

# Install k6 (if not installed)
# sudo apt install k6

# Run smoke test
k6 run --vus 10 --duration 1m tests/load/customer_portal_load_test.js

# Expected:
# ✓ http_req_duration: p(95)<2000
# ✓ http_req_failed: rate<0.05
# ✓ login_failures: rate<0.05
```

**Pass Criteria**:
- [ ] P95 response time < 2s
- [ ] Error rate < 5%
- [ ] Login success rate > 95%

#### Scenario 3.2: Load Test (100 VUs, 5 minutes)

```bash
# Run load test
k6 run --vus 100 --duration 5m tests/load/customer_portal_load_test.js

# Monitor during test:
# - CPU usage (should stay < 80%)
# - Memory usage (should stay < 85%)
# - Database connections (should not hit max)
```

**Pass Criteria**:
- [ ] P95 response time < 2s under load
- [ ] Error rate < 5%
- [ ] No database connection errors
- [ ] No memory leaks (memory stable)

#### Scenario 3.3: Stress Test (Ramp to 200 VUs)

```bash
# Run stress test
k6 run --stage 1m:50 --stage 2m:100 --stage 2m:150 --stage 1m:200 \
  tests/load/customer_portal_load_test.js

# Monitor for:
# - When does performance degrade?
# - Are there any error spikes?
# - How does system recover?
```

**Pass Criteria**:
- [ ] System remains stable up to 100 VUs
- [ ] Degrades gracefully beyond 100 VUs
- [ ] Recovers after ramp-down

---

## ✅ SECTION 4: FUNCTIONAL TESTING (90 min)

### Test 4.1: Authentication & Authorization

- [ ] **Login as customer user**
  ```
  Email: customer@staging.local
  Password: TestPass123!
  Expected: Redirect to /portal
  ```

- [ ] **Login as admin user**
  ```
  Email: admin@staging.local
  Password: AdminPass123!
  Expected: Redirect to /admin (NOT /portal)
  ```

- [ ] **Logout functionality**
  ```
  Click logout button
  Expected: Redirect to login page, session destroyed
  ```

- [ ] **Session timeout after 2 hours**
  ```
  1. Login
  2. Wait 2+ hours (or set SESSION_LIFETIME=1 in .env)
  3. Try to access portal
  4. Expected: Redirect to login
  ```

### Test 4.2: Customer Portal Dashboard

- [ ] **Dashboard displays correct company data**
  ```
  1. Login as customer user
  2. Check dashboard widgets:
     - Total calls (last 30 days)
     - Completed calls count
     - Failed calls count
     - Success rate percentage
  3. Verify: All data matches company scope
  ```

- [ ] **Dashboard charts render correctly**
  ```
  1. Check: Call volume chart displays
  2. Check: Success rate trend displays
  3. Check: No JavaScript errors in console
  ```

### Test 4.3: Call Sessions Management

- [ ] **List all call sessions**
  ```
  1. Navigate to Call Sessions
  2. Verify: Table displays with columns:
     - Customer name
     - Phone number
     - Date/Time
     - Status
     - Duration
  3. Verify: Only current company's calls
  ```

- [ ] **Filter call sessions by date**
  ```
  1. Use date range filter
  2. Select last 7 days
  3. Verify: Only calls from last 7 days shown
  ```

- [ ] **Filter call sessions by status**
  ```
  1. Filter by "Completed"
  2. Verify: Only completed calls shown
  3. Filter by "Failed"
  4. Verify: Only failed calls shown
  ```

- [ ] **Search call sessions**
  ```
  1. Enter customer name in search
  2. Verify: Results filtered correctly
  3. Enter phone number
  4. Verify: Finds matching calls
  ```

- [ ] **View call session details**
  ```
  1. Click on a call session
  2. Verify details page shows:
     - Full transcript
     - Call analysis
     - Function calls made
     - Timestamps
  3. Verify: Transcript loads quickly (< 1s)
  ```

### Test 4.4: Appointments Management

- [ ] **List all appointments**
  ```
  1. Navigate to Appointments
  2. Verify: Calendar view displays
  3. Verify: Only current company appointments
  ```

- [ ] **Calendar navigation**
  ```
  1. Navigate to next month
  2. Verify: Appointments load correctly
  3. Navigate to previous month
  4. Verify: Historical appointments show
  ```

- [ ] **View appointment details**
  ```
  1. Click on an appointment
  2. Verify details:
     - Service name
     - Staff member
     - Branch location
     - Customer info
     - Status
  ```

### Test 4.5: CRM Features (if enabled)

- [ ] **Customer list displays**
  ```
  1. Navigate to Customers
  2. Verify: List shows all company customers
  3. Verify: Customer count is accurate
  ```

- [ ] **Customer details page**
  ```
  1. Click on a customer
  2. Verify:
     - Contact information
     - Call history
     - Appointment history
     - Notes (if any)
  ```

### Test 4.6: Staff Management (if enabled)

- [ ] **Staff list displays**
  ```
  1. Navigate to Staff
  2. Verify: All company staff members shown
  3. Verify: Correct branch assignments
  ```

---

## 🔄 SECTION 5: INTEGRATION TESTING (30 min)

### Test 5.1: Admin Panel Compatibility

- [ ] **Admin panel still accessible**
  ```
  1. Login as admin@staging.local
  2. Navigate to /admin
  3. Expected: Admin dashboard loads normally
  4. Check: No JavaScript errors
  ```

- [ ] **Admin can manage all companies**
  ```
  1. Navigate to Companies resource
  2. Verify: Can view all companies
  3. Verify: Can edit company settings
  4. Verify: No permission issues
  ```

- [ ] **Admin resources unchanged**
  ```
  1. Check all admin resources:
     - Appointments
     - Call Sessions
     - Customers
     - Services
     - Staff
  2. Verify: All load correctly
  3. Verify: No broken layouts
  ```

### Test 5.2: Database Migration Safety

- [ ] **Migration adds indexes without errors**
  ```bash
  # Check migration status
  php artisan migrate:status --env=staging

  # Verify our migration is there
  grep "2025_10_26_115644_add_customer_portal_performance_indexes" \
    storage/database.sqlite

  # Check for any failed migrations
  php artisan migrate:status | grep "Pending"
  # Expected: Empty (no pending migrations)
  ```

- [ ] **Rollback works correctly**
  ```bash
  # Rollback migration
  php artisan migrate:rollback --step=1 --env=staging

  # Verify indexes dropped
  mysql -u askproai_staging_user -p -e "
    SHOW INDEXES FROM retell_call_sessions
    WHERE Key_name LIKE 'idx_retell_sessions_%';
  " askproai_staging

  # Expected: No customer portal indexes

  # Re-run migration
  php artisan migrate --env=staging
  ```

### Test 5.3: Cache Behavior

- [ ] **Cache warming works**
  ```bash
  # Clear cache
  php artisan cache:clear

  # Warm cache
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache

  # Test portal
  curl https://staging.askproai.de/portal
  # Expected: No errors
  ```

- [ ] **Redis cache isolation**
  ```bash
  # Check Redis keys
  redis-cli --scan --pattern "askpro_staging_*"

  # Verify: Only staging keys with staging prefix
  # Verify: No production keys affected
  ```

---

## 🚨 SECTION 6: ROLLBACK TESTING (15 min)

### Test 6.1: Feature Flag Instant Disable

- [ ] **Disable feature flag (< 30 seconds)**
  ```bash
  # Time this operation
  time {
    echo "FEATURE_CUSTOMER_PORTAL=false" >> .env.staging
    php artisan config:clear --env=staging
  }

  # Expected: < 30 seconds total

  # Verify portal is now 404
  curl -I https://staging.askproai.de/portal
  # Expected: HTTP/1.1 404 Not Found
  ```

- [ ] **Production continues working**
  ```bash
  # Check production is unaffected
  curl -I https://api.askproai.de/admin
  # Expected: HTTP/1.1 200 OK

  # Verify production has feature disabled
  grep FEATURE_CUSTOMER_PORTAL /var/www/api-gateway/.env
  # Expected: false or not present
  ```

### Test 6.2: Database Rollback

- [ ] **Rollback migration cleanly**
  ```bash
  # Rollback performance indexes
  php artisan migrate:rollback --step=1 --env=staging

  # Verify no errors in output
  # Verify portal still loads (slower, but functional)
  ```

### Test 6.3: Branch Rollback

- [ ] **Switch back to main branch**
  ```bash
  # Deploy main branch to staging
  ./scripts/deploy-staging.sh main

  # Verify:
  # - Deployment completes without errors
  # - Portal route returns 404 (feature not in main)
  # - Admin panel still works

  # Switch back to feature branch
  ./scripts/deploy-staging.sh feature/customer-portal
  ```

---

## 📈 SUCCESS CRITERIA

### Must Pass (Blockers)

- [ ] All 10 security tests pass (100%)
- [ ] All 5 performance tests meet targets
- [ ] Load test handles 100 VUs with < 5% errors
- [ ] P95 response time < 2s under normal load
- [ ] No multi-tenancy data leaks
- [ ] Feature flag kill-switch works instantly
- [ ] Admin panel unaffected

### Should Pass (Important)

- [ ] All functional tests pass (15/15)
- [ ] All integration tests pass (8/8)
- [ ] Rollback tests pass (3/3)
- [ ] No JavaScript console errors
- [ ] Database indexes used in all queries
- [ ] Stress test handles 150+ VUs gracefully

### Nice to Have

- [ ] P99 response time < 3s
- [ ] Stress test reaches 200 VUs
- [ ] Zero errors in load test
- [ ] Dashboard loads < 1s

---

## 🎯 TESTING WORKFLOW

### Before Testing

```bash
# 1. Ensure staging is set up
./scripts/deploy-staging.sh feature/customer-portal

# 2. Sync database
./scripts/sync-staging-database.sh

# 3. Reset test user passwords
php artisan tinker --env=staging
# Run password reset commands

# 4. Verify environment
php artisan config:show features --env=staging
```

### During Testing

```bash
# Monitor logs in real-time
tail -f storage/logs/laravel.log

# Monitor system resources
htop  # or top

# Monitor database
mysql -u askproai_staging_user -p askproai_staging -e "SHOW PROCESSLIST;"
```

### After Testing

```bash
# Generate test report
./scripts/generate_test_report.sh > TEST_REPORT_$(date +%Y%m%d).md

# Check for any errors
grep ERROR storage/logs/laravel.log | tail -50

# Verify cache cleared
php artisan cache:clear
php artisan config:clear
```

---

## 📝 TEST REPORT TEMPLATE

After completing all tests, create a report:

```markdown
# Test Report - Customer Portal Phase 1
Date: YYYY-MM-DD
Tester: [Name]
Environment: staging.askproai.de
Branch: feature/customer-portal

## Summary
- Total Tests: 44
- Passed: X
- Failed: Y
- Blocked: Z
- Pass Rate: XX%

## Security: ✅ / ❌
- All security tests passed: YES/NO
- Critical vulnerabilities: NONE/[list]
- Multi-tenancy verified: YES/NO

## Performance: ✅ / ❌
- Indexes working: YES/NO
- Response times acceptable: YES/NO
- Load test results: PASS/FAIL

## Functional: ✅ / ❌
- Portal accessible: YES/NO
- All features working: YES/NO
- No errors observed: YES/NO

## Recommendation
- [ ] ✅ APPROVED for production
- [ ] ⚠️  APPROVED with minor issues
- [ ] ❌ NOT APPROVED - blocking issues

## Issues Found
1. [Issue description]
2. [Issue description]

## Notes
[Additional observations]
```

---

## 🆘 TROUBLESHOOTING

### Issue: Tests failing with "Database connection error"

**Cause**: Staging database not configured

**Fix**:
```bash
# Check .env.staging
grep DB_ /var/www/api-gateway/.env.staging

# Test connection
mysql -u askproai_staging_user -p askproai_staging
```

---

### Issue: "Feature flag not working"

**Cause**: Cache not cleared

**Fix**:
```bash
php artisan config:clear --env=staging
php artisan cache:clear
```

---

### Issue: "Portal returns 500 error"

**Cause**: Missing migration or permission issue

**Fix**:
```bash
# Run migrations
php artisan migrate --env=staging

# Check permissions
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Check logs
tail -50 storage/logs/laravel.log
```

---

## 📚 RELATED DOCUMENTATION

- **Setup Guide**: `STAGING_SETUP_QUICK_START.md`
- **Security Analysis**: `CUSTOMER_PORTAL_SECURITY_ANALYSIS.md`
- **Performance Analysis**: `CUSTOMER_PORTAL_PERFORMANCE_ANALYSIS.md`
- **CRM Testing**: `CUSTOMER_PORTAL_CRM_TESTING.md`

---

**Last Updated**: 2025-10-26
**Version**: 1.0
**Status**: Ready for Testing
