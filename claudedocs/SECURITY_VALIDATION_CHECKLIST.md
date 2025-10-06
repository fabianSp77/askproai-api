# Security Validation Checklist - Quick Reference

## 🔴 CRITICAL - BLOCKING PRODUCTION DEPLOYMENT

### 1. Multi-Tenant Isolation Verification

**Test Command**:
```bash
php artisan tinker
```

```php
// Test 1: Admin user bypass check (MUST FAIL)
$admin = User::where('email', 'admin@company-a.com')->first();
Auth::login($admin);
$allAppointments = Appointment::all(); // Should ONLY return Company A appointments
echo "Admin sees appointments: " . $allAppointments->count() . "\n";
echo "Companies in results: " . $allAppointments->pluck('company_id')->unique()->count() . "\n";
// ✅ PASS: Count = 1 (only admin's company)
// ❌ FAIL: Count > 1 (multiple companies visible)

// Test 2: Service discovery isolation
$companyAUser = User::where('company_id', 1)->first();
Auth::login($companyAUser);
$companyBService = Service::where('company_id', 2)->first();
echo "Can access other company service: " . ($companyBService ? 'YES - VULNERABLE' : 'NO - SECURE') . "\n";
// ✅ PASS: null (cannot access)
// ❌ FAIL: Service object returned
```

**SQL Validation Queries**:
```sql
-- Run these queries to verify company_id filtering

-- 1. Check if User queries are scoped (CRITICAL)
SET @test_user_id = 1;
SET @test_company_id = (SELECT company_id FROM users WHERE id = @test_user_id);

-- This should return ONLY users from same company
SELECT COUNT(*) as total_visible,
       COUNT(DISTINCT company_id) as unique_companies
FROM users;
-- Expected: unique_companies = 1
-- Vulnerable: unique_companies > 1

-- 2. Verify appointments are company-scoped
SELECT COUNT(*) as total_appointments,
       COUNT(DISTINCT company_id) as unique_companies
FROM appointments;
-- Expected with company scope: unique_companies = 1
-- Vulnerable without scope: unique_companies > 1

-- 3. Check for models WITHOUT company_id column (these need migration)
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'askproai_db'
  AND TABLE_TYPE = 'BASE TABLE'
  AND TABLE_NAME NOT IN (
    SELECT TABLE_NAME
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'askproai_db'
      AND COLUMN_NAME = 'company_id'
  )
  AND TABLE_NAME NOT LIKE '%migrations%'
  AND TABLE_NAME NOT LIKE '%password%'
  AND TABLE_NAME NOT LIKE '%jobs%'
  AND TABLE_NAME NOT LIKE '%cache%'
  AND TABLE_NAME NOT IN ('companies', 'roles', 'permissions', 'role_has_permissions', 'model_has_roles', 'model_has_permissions');
-- Expected: Empty result (all relevant tables have company_id)
-- Issue: Any table name returned needs company_id column added
```

### 2. Webhook Authentication Check

**Test Commands**:
```bash
# Test 1: Unsigned webhook should FAIL
curl -X POST https://api.askproai.de/api/webhooks/retell/function-call \
  -H "Content-Type: application/json" \
  -d '{"function_name":"book_appointment","call_id":"test123"}' \
  -w "\nStatus: %{http_code}\n"
# ✅ PASS: Status 401 (Unauthorized)
# ❌ FAIL: Status 200 (Accepted without signature)

# Test 2: Invalid signature should FAIL
curl -X POST https://api.askproai.de/api/webhooks/calcom \
  -H "Content-Type: application/json" \
  -H "X-Cal-Signature-256: invalid_signature" \
  -d '{"triggerEvent":"BOOKING_CREATED"}' \
  -w "\nStatus: %{http_code}\n"
# ✅ PASS: Status 401 (Unauthorized)
# ❌ FAIL: Status 200 (Accepted with invalid signature)

# Test 3: Rate limiting check (send 150 requests rapidly)
for i in {1..150}; do
  curl -s -o /dev/null -w "%{http_code}\n" \
    -X POST https://api.askproai.de/api/webhooks/retell \
    -H "Content-Type: application/json" \
    -d '{"event":"test"}' &
done | sort | uniq -c
# ✅ PASS: Shows mix of 200 and 429 (rate limited)
# ❌ FAIL: All 200 (no rate limiting)
```

### 3. CompanyScope Admin Bypass Fix

**Verification**:
```bash
php artisan tinker
```

```php
// Check CompanyScope code
$reflection = new ReflectionClass(App\Scopes\CompanyScope::class);
$method = $reflection->getMethod('apply');
$code = file_get_contents($method->getFileName());
preg_match('/hasAnyRole\(\[(.*?)\]\)/', $code, $matches);
echo "Roles that bypass scope: " . ($matches[1] ?? 'NONE') . "\n";
// ✅ PASS: Only 'super_admin'
// ❌ FAIL: 'super_admin', 'admin'

// Functional test
$admin = User::role('admin')->first();
$admin->company_id = 1;
$admin->save();
Auth::login($admin);

$appointmentOtherCompany = Appointment::where('company_id', '!=', 1)->first();
echo "Admin can access other company: " . ($appointmentOtherCompany ? 'YES - VULNERABLE' : 'NO - SECURE') . "\n";
// ✅ PASS: NO - SECURE (null returned)
// ❌ FAIL: YES - VULNERABLE (appointment returned)
```

---

## 🟡 HIGH PRIORITY - RECOMMENDED BEFORE PRODUCTION

### 4. Input Validation Observer Check

**Test XSS Prevention**:
```bash
php artisan tinker
```

```php
// Test CallbackRequest sanitization
$callback = new App\Models\CallbackRequest([
    'company_id' => 1,
    'customer_name' => 'Test<script>alert(1)</script>',
    'phone_number' => '+491234567890',
    'notes' => '<img src=x onerror=alert(1)>Important note',
    'priority' => 'high'
]);
$callback->save();
$callback->refresh();

echo "Customer name: " . $callback->customer_name . "\n";
echo "Notes: " . $callback->notes . "\n";
// ✅ PASS: No <script> or <img> tags present, sanitized
// ❌ FAIL: Script tags or event handlers still present

// Test PolicyConfiguration validation
try {
    $policy = App\Models\PolicyConfiguration::create([
        'company_id' => 1,
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 'INVALID', // Should be integer
            'fee_percentage' => 50
        ]
    ]);
    echo "❌ FAIL: Validation not working, accepted invalid data\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "✅ PASS: Validation working - " . $e->getMessage() . "\n";
}
```

### 5. Policy Enforcement Verification

**Quick Policy Check**:
```bash
php artisan tinker
```

```php
// Test all 18 policies enforce company_id
$policies = [
    'Appointment', 'CallbackRequest', 'Customer', 'Service', 'Staff',
    'Branch', 'Transaction', 'Call', 'Invoice', 'PhoneNumber',
    'PolicyConfiguration', 'NotificationConfiguration', 'User',
    'SystemSetting', 'AppointmentModification', 'NotificationEventMapping',
    'CallbackEscalation', 'Company'
];

$companyAUser = User::where('company_id', 1)->first();
Auth::login($companyAUser);

foreach ($policies as $model) {
    $class = "App\\Models\\{$model}";
    $otherCompanyRecord = $class::where('company_id', '!=', 1)->first();

    if ($otherCompanyRecord) {
        $canView = Gate::allows('view', $otherCompanyRecord);
        echo "{$model}: " . ($canView ? '❌ FAIL - Can view other company' : '✅ PASS - Blocked') . "\n";
    }
}
// ✅ PASS: All show "Blocked"
// ❌ FAIL: Any show "Can view other company"
```

---

## 🟢 RECOMMENDED - POST-PRODUCTION

### 6. Automated Security Test Suite

**Run Full Test Suite**:
```bash
# Run security-specific tests
php artisan test --testsuite=Security

# Run all tests including security
php artisan test --coverage

# Expected output:
# - Multi-tenant isolation tests: PASSED
# - Authorization policy tests: PASSED
# - Input validation tests: PASSED
# - XSS prevention tests: PASSED
# - SQL injection prevention tests: PASSED
```

### 7. Database Privilege Audit

```sql
-- Check database user privileges
SHOW GRANTS FOR 'askproai_user'@'localhost';

-- Expected privileges (minimal):
-- GRANT SELECT, INSERT, UPDATE, DELETE ON askproai_db.* TO 'askproai_user'@'localhost';

-- Should NOT have:
-- - GRANT ALL PRIVILEGES
-- - SUPER privilege
-- - FILE privilege
-- - Access to mysql.* database
-- - GRANT OPTION

-- Verify no dangerous privileges
SELECT
    GRANTEE,
    PRIVILEGE_TYPE,
    IS_GRANTABLE
FROM information_schema.USER_PRIVILEGES
WHERE GRANTEE LIKE '%askproai_user%';
-- ✅ PASS: Only SELECT, INSERT, UPDATE, DELETE, and IS_GRANTABLE = 'NO'
-- ❌ FAIL: SUPER, FILE, or other dangerous privileges present
```

### 8. Environment Security Audit

```bash
# Check critical environment variables
cd /var/www/api-gateway

# 1. Verify production settings
grep -E "APP_ENV|APP_DEBUG" .env
# ✅ PASS: APP_ENV=production, APP_DEBUG=false
# ❌ FAIL: APP_ENV=local or APP_DEBUG=true

# 2. Check session security
grep -E "SESSION_SECURE_COOKIE|SESSION_SAME_SITE" .env
# ✅ PASS: SESSION_SECURE_COOKIE=true, SESSION_SAME_SITE=lax or strict
# ❌ FAIL: Missing or set to false

# 3. Verify Redis security
redis-cli ping
# If no password required:
# ❌ FAIL: Redis accessible without authentication
# If password required:
# ✅ PASS: (error) NOAUTH Authentication required

# 4. Check file permissions
ls -la .env
# ✅ PASS: -rw------- (600) - only owner can read
# ❌ FAIL: -rw-r--r-- (644) - world-readable

# 5. Verify no sensitive data in git
git log --all --full-history -- "*.env" "*.key" "*secret*"
# ✅ PASS: No results (no secrets in git history)
# ❌ FAIL: Results found (secrets committed to git)
```

---

## Quick Security Score Calculator

Run this script to get overall security posture:

```bash
#!/bin/bash
# File: security-check.sh

PASS=0
FAIL=0

echo "=== SECURITY VALIDATION SUMMARY ==="
echo ""

# Test 1: Multi-tenant isolation
echo -n "1. Multi-tenant isolation... "
php artisan tinker --execute="
\$admin = User::role('admin')->first();
Auth::login(\$admin);
\$count = Appointment::all()->pluck('company_id')->unique()->count();
echo \$count == 1 ? 'PASS' : 'FAIL';
" && ((PASS++)) || ((FAIL++))

# Test 2: Webhook authentication
echo -n "2. Webhook authentication... "
STATUS=$(curl -s -o /dev/null -w "%{http_code}" \
  -X POST https://api.askproai.de/api/webhooks/retell/function-call \
  -H "Content-Type: application/json" \
  -d '{"test":"data"}')
[ "$STATUS" = "401" ] && echo "PASS" && ((PASS++)) || echo "FAIL" && ((FAIL++))

# Test 3: CompanyScope admin bypass fix
echo -n "3. CompanyScope admin bypass fix... "
grep -q "hasRole('super_admin')" app/Scopes/CompanyScope.php && \
! grep -q "hasAnyRole.*admin" app/Scopes/CompanyScope.php && \
echo "PASS" && ((PASS++)) || echo "FAIL" && ((FAIL++))

# Test 4: Environment security
echo -n "4. Production environment config... "
grep -q "APP_ENV=production" .env && \
grep -q "APP_DEBUG=false" .env && \
echo "PASS" && ((PASS++)) || echo "FAIL" && ((FAIL++))

# Test 5: File permissions
echo -n "5. .env file permissions... "
[ "$(stat -c %a .env)" = "600" ] && echo "PASS" && ((PASS++)) || echo "FAIL" && ((FAIL++))

echo ""
echo "=== RESULTS ==="
echo "Passed: $PASS / 5"
echo "Failed: $FAIL / 5"
echo ""

if [ $FAIL -eq 0 ]; then
    echo "✅ Security posture: GOOD - Ready for production"
    exit 0
elif [ $FAIL -le 2 ]; then
    echo "⚠️ Security posture: MODERATE - Address failures before production"
    exit 1
else
    echo "🔴 Security posture: CRITICAL - NOT ready for production"
    exit 2
fi
```

**Usage**:
```bash
chmod +x security-check.sh
./security-check.sh
```

---

## Emergency Rollback Procedure

If security vulnerabilities are discovered in production:

```bash
# 1. Immediate response - Block affected endpoints
sudo nginx -s stop  # or specific route blocking

# 2. Restore from last known secure state
cd /var/www/api-gateway
git checkout [last-secure-commit-hash]
composer install --no-dev
php artisan migrate:fresh --force
php artisan db:seed --force

# 3. Restore database from backup
mysql -u askproai_user -p askproai_db < /backups/askproai_db_[timestamp].sql

# 4. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL

# 5. Revoke all active sessions
php artisan session:flush

# 6. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart redis

# 7. Verify system is secure
./security-check.sh
```

---

## Sign-off Checklist

Before production deployment, all items must be checked:

**Critical Security Items**:
- [ ] All models use BelongsToCompany trait or equivalent scoping
- [ ] CompanyScope only allows super_admin bypass (not admin)
- [ ] User model properly scoped to company_id
- [ ] All webhook endpoints require authentication
- [ ] Service discovery enforces company_id validation
- [ ] All security tests pass (0 failures)

**High Priority Items**:
- [ ] Input validation observers created for all models with user input
- [ ] All 18 policies enforce company_id checks
- [ ] Rate limiting configured on all public endpoints
- [ ] XSS prevention verified on all input fields
- [ ] SQL injection tests pass

**Environment & Infrastructure**:
- [ ] APP_ENV=production, APP_DEBUG=false
- [ ] Redis password configured
- [ ] MySQL user has minimal required privileges
- [ ] .env file permissions set to 600
- [ ] SSL/TLS certificates valid and installed
- [ ] Firewall rules configured (UFW/iptables)
- [ ] fail2ban installed and configured
- [ ] Backup procedures tested and verified

**Monitoring & Logging**:
- [ ] Security monitoring alerts configured
- [ ] Cross-tenant access attempts logged and alerted
- [ ] Failed authorization attempts monitored
- [ ] Webhook signature failures tracked
- [ ] Log aggregation service configured (ELK/Datadog/Splunk)

**Sign-off**:
- [ ] Security Team Lead: _________________ Date: _______
- [ ] Development Team Lead: ______________ Date: _______
- [ ] CTO/Technical Director: _____________ Date: _______
- [ ] Legal/Compliance Officer: ___________ Date: _______

---

**Checklist Version**: 1.0
**Last Updated**: 2025-10-02
**Valid Until**: Security fixes implemented and verified
