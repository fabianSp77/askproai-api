# Multi-Tenant Security - Test Execution Commands

**Quick Reference**: Copy-paste commands for immediate testing

---

## 🚀 Quick Start (5 Minutes)

```bash
# 1. Create test directories
mkdir -p tests/Unit/Observers
mkdir -p tests/Feature/Security
mkdir -p tests/Feature/Integration
mkdir -p tests/Feature/EdgeCases

# 2. Verify observer registration
grep -A 10 "protected \$observers" app/Providers/EventServiceProvider.php

# 3. Verify policy registration
grep -A 10 "protected \$policies" app/Providers/AuthServiceProvider.php

# 4. Check migration status
php artisan migrate:status | grep company_id

# 5. Verify trait usage
grep -l "use BelongsToCompany" app/Models/*.php
```

---

## 🧪 Manual Testing (Immediate Verification)

### Test 1: Observer Validation (Critical)

```bash
php artisan tinker
```

```php
// Test PolicyConfigurationObserver - Should FAIL with validation error
PolicyConfiguration::create([
    'policy_type' => 'cancellation',
    'config' => ['invalid' => 'data']
]);
// Expected: ValidationException "Required field 'hours_before' is missing"

// Test CallbackRequestObserver - Should FAIL with phone validation error
CallbackRequest::create([
    'customer_name' => 'Test',
    'phone_number' => 'invalid_phone',
    'priority' => 'normal'
]);
// Expected: ValidationException "Phone number must be in E.164 format"

// Test NotificationConfigurationObserver - Should FAIL with event type error
NotificationConfiguration::create([
    'event_type' => 'invalid.event',
    'channel' => 'email',
    'template_content' => 'Test',
    'configurable_type' => 'App\Models\Company',
    'configurable_id' => 1
]);
// Expected: ValidationException "Invalid or inactive event type"
```

### Test 2: XSS Sanitization (Critical)

```bash
php artisan tinker
```

```php
// Test XSS in CallbackRequest
$callback = CallbackRequest::create([
    'customer_name' => '<script>alert("xss")</script>John Doe',
    'phone_number' => '+491234567890',
    'notes' => '<img src=x onerror="alert(1)">Test notes',
    'priority' => 'normal'
]);

// Verify sanitization
$callback->customer_name;
// Expected: "John Doe" (script tags removed)

$callback->notes;
// Expected: "Test notes" (img tag removed)

// Should NOT contain:
strpos($callback->customer_name, '<script>');  // false
strpos($callback->notes, '<img');             // false
```

### Test 3: Cross-Tenant Isolation (Critical)

```bash
php artisan tinker
```

```php
// Create two companies
$companyA = Company::factory()->create();
$companyB = Company::factory()->create();

// Create user for Company A
$userA = User::factory()->create(['company_id' => $companyA->id]);
$userA->assignRole('admin');

// Create callback for Company B
$callbackB = CallbackRequest::factory()->create([
    'company_id' => $companyB->id
]);

// Login as Company A user
Auth::login($userA);

// Try to access Company B's callback
$userA->can('view', $callbackB);
// Expected: false (cross-tenant access blocked)

// Query all callbacks - should only see Company A's
$results = CallbackRequest::all();
$results->pluck('company_id')->unique();
// Expected: Only contains Company A's ID
```

### Test 4: Observer Triggering (Critical)

```bash
php artisan tinker
```

```php
// Verify observers are registered
PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration');
// Expected: true

CallbackRequest::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\CallbackRequest');
// Expected: true

NotificationConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\NotificationConfiguration');
// Expected: true
```

---

## 📝 Automated Test Execution

### All Security Tests (Complete Suite)

```bash
# Run all security tests (10-15 minutes)
php artisan test \
  --filter="Observer|MultiTenant|SuperAdmin|Assignment|BelongsToCompany|CompleteWorkflow|XSSAttack|EdgeCase" \
  --stop-on-failure \
  --coverage
```

### By Priority Level

**P0 Critical (Must Pass 100%)**
```bash
# Observer validation and triggering
php artisan test --filter=Observer --stop-on-failure

# Cross-tenant isolation
php artisan test --filter=MultiTenant --stop-on-failure

# Combined P0
php artisan test --filter="Observer|MultiTenant" --stop-on-failure
```

**P1 Important (Must Pass 95%+)**
```bash
# Authorization tests
php artisan test --filter="SuperAdmin|Assignment" --stop-on-failure

# XSS security
php artisan test --filter=XSSAttack --stop-on-failure

# Integration tests
php artisan test --filter="BelongsToCompany|CompleteWorkflow" --stop-on-failure

# Combined P1
php artisan test --filter="SuperAdmin|Assignment|XSSAttack|BelongsToCompany|CompleteWorkflow" --stop-on-failure
```

**P2 Edge Cases (Must Pass 90%+)**
```bash
# All edge cases
php artisan test --filter=EdgeCase --stop-on-failure
```

### By Component

```bash
# Observer tests only
php artisan test tests/Unit/Observers/

# Security tests only
php artisan test tests/Feature/Security/

# Integration tests only
php artisan test tests/Feature/Integration/

# Edge case tests only
php artisan test tests/Feature/EdgeCases/
```

### Individual Test Files

```bash
# Observer tests
php artisan test tests/Unit/Observers/PolicyConfigurationObserverTest.php
php artisan test tests/Unit/Observers/CallbackRequestObserverTest.php
php artisan test tests/Unit/Observers/NotificationConfigurationObserverTest.php
php artisan test tests/Unit/Observers/ObserverTriggeringTest.php

# Authorization tests
php artisan test tests/Feature/Security/MultiTenantAuthorizationTest.php
php artisan test tests/Feature/Security/SuperAdminAuthorizationTest.php
php artisan test tests/Feature/Security/AssignmentAuthorizationTest.php

# Security tests
php artisan test tests/Feature/Security/XSSAttackVectorTest.php

# Integration tests
php artisan test tests/Feature/Integration/BelongsToCompanyIntegrationTest.php
php artisan test tests/Feature/Integration/CompleteWorkflowTest.php

# Edge case tests
php artisan test tests/Feature/EdgeCases/MissingCompanyIdTest.php
php artisan test tests/Feature/EdgeCases/InvalidCompanyIdTest.php
php artisan test tests/Feature/EdgeCases/SoftDeleteTest.php
php artisan test tests/Feature/EdgeCases/ConcurrentOperationsTest.php
```

---

## 📊 Coverage Analysis

```bash
# Generate coverage report for all security components
php artisan test \
  --filter="Observer|Policy|MultiTenant" \
  --coverage \
  --min=80

# Coverage for specific components
php artisan test --filter=Observer --coverage --min=85
php artisan test --filter=Policy --coverage --min=85
php artisan test --filter=MultiTenant --coverage --min=90

# Coverage report with HTML output
php artisan test --coverage-html coverage-report/

# View coverage report
open coverage-report/index.html
```

---

## 🔍 Debugging Commands

### Find Failing Tests

```bash
# Run with verbose output
php artisan test --filter=Observer -v

# Run with debug output
php artisan test --filter=Observer -vvv

# Run single test method
php artisan test --filter=test_callback_request_sanitizes_customer_name

# Stop on first failure for debugging
php artisan test --stop-on-failure --stop-on-error
```

### Database State Inspection

```bash
# Check database after test run
php artisan tinker

# View recent callbacks
CallbackRequest::latest()->take(5)->get();

# Check for XSS in database
CallbackRequest::where('customer_name', 'like', '%<script%')->get();
# Expected: Empty (no XSS in database)

# Check company_id distribution
CallbackRequest::groupBy('company_id')->selectRaw('company_id, count(*) as count')->get();
```

### Cache Clearing (Fix Observer/Policy Issues)

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚨 Emergency Troubleshooting

### Observers Not Firing

```bash
# 1. Check registration
grep -A 10 "\$observers" app/Providers/EventServiceProvider.php

# 2. Clear caches
php artisan optimize:clear

# 3. Verify in tinker
php artisan tinker
>>> PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration')

# 4. Test manually
>>> PolicyConfiguration::create(['policy_type' => 'invalid', 'config' => []])
# Should throw ValidationException
```

### Policies Not Working

```bash
# 1. Check registration
grep -A 10 "\$policies" app/Providers/AuthServiceProvider.php

# 2. Clear caches
php artisan optimize:clear

# 3. Verify in tinker
php artisan tinker
>>> Gate::getPolicyFor(App\Models\CallbackRequest::class)

# 4. Test manually
>>> $user = User::find(1)
>>> $callback = CallbackRequest::find(1)
>>> $user->can('view', $callback)
```

### Cross-Tenant Leakage

```bash
# 1. Check BelongsToCompany trait usage
grep -l "use BelongsToCompany" app/Models/*.php

# 2. Check CompanyScope
cat app/Scopes/CompanyScope.php

# 3. Test in tinker
php artisan tinker
>>> Auth::loginUsingId(1)
>>> CallbackRequest::all()->pluck('company_id')->unique()
# Should only show user's company_id
```

### XSS Not Sanitized

```bash
# 1. Verify observer registration
php artisan tinker
>>> CallbackRequest::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\CallbackRequest')

# 2. Test sanitization
>>> $callback = CallbackRequest::create(['customer_name' => '<script>alert(1)</script>Test', 'phone_number' => '+491234567890', 'priority' => 'normal'])
>>> $callback->customer_name
# Should NOT contain <script>

# 3. Check database directly
>>> DB::table('callback_requests')->where('customer_name', 'like', '%<script%')->count()
# Should be 0
```

---

## ✅ Pre-Deployment Verification

### Complete Checklist

```bash
# Step 1: Verify all components registered
grep -A 10 "\$observers" app/Providers/EventServiceProvider.php
grep -A 10 "\$policies" app/Providers/AuthServiceProvider.php
php artisan migrate:status | grep company_id
grep -l "use BelongsToCompany" app/Models/*.php

# Step 2: Run all P0 tests
php artisan test --filter="Observer|MultiTenant" --stop-on-failure

# Step 3: Run all P1 tests
php artisan test --filter="SuperAdmin|Assignment|XSSAttack|BelongsToCompany|CompleteWorkflow" --stop-on-failure

# Step 4: Run all P2 tests
php artisan test --filter=EdgeCase

# Step 5: Generate coverage report
php artisan test --coverage --min=80

# Step 6: Manual verification
php artisan tinker
# Run manual tests from section above

# Step 7: Clear all caches
php artisan optimize:clear

# Step 8: Final test run
php artisan test --filter="Observer|MultiTenant|SuperAdmin|Assignment|XSSAttack"
```

### Success Criteria

- [ ] All P0 tests pass (100%)
- [ ] All P1 tests pass (95%+)
- [ ] All P2 tests pass (90%+)
- [ ] Coverage >80% overall
- [ ] Coverage >85% for observers
- [ ] Coverage >85% for policies
- [ ] Manual verification complete
- [ ] No XSS in database
- [ ] Cross-tenant isolation verified
- [ ] Observer triggering verified
- [ ] All caches cleared

---

## 🎯 Quick Test Results Interpretation

### Good Results ✅

```
Tests:    155 passed
Time:     00:08.234
Coverage: 87.3%
```

### Warning Signs ⚠️

```
Tests:    150 passed, 5 failed (P1/P2 failures acceptable if <5%)
Time:     00:08.234
Coverage: 78.5% (below 80% target - need more tests)
```

### Critical Failures 🔴

```
Tests:    145 passed, 10 failed (P0 failures - MUST FIX)
Time:     00:08.234
Coverage: 65.2% (critical coverage gap)

Failed Tests:
- Observer validation tests
- Cross-tenant isolation tests
- XSS prevention tests
```

**Action**: Do NOT deploy - fix P0 failures first

---

## 📅 Execution Timeline

**Day 1**: Create observer tests → Run P0 tests → Fix failures
**Day 2**: Create authorization tests → Run P1 tests → Fix failures
**Day 3**: Create security/integration tests → Run all tests
**Day 4**: Generate coverage → Fill gaps → Edge cases
**Day 5**: Final verification → Manual testing → Deploy

---

## 📚 Documentation Links

- **Full Test Plan**: `/var/www/api-gateway/claudedocs/MULTI_TENANT_SECURITY_TEST_PLAN.md`
- **Quick Start Guide**: `/var/www/api-gateway/claudedocs/TESTING_QUICK_START.md`
- **Executive Summary**: `/var/www/api-gateway/claudedocs/SECURITY_TEST_SUMMARY.md`
- **This Document**: `/var/www/api-gateway/claudedocs/TEST_EXECUTION_COMMANDS.md`

---

**Ready to Test?**

Start with manual verification (5 minutes):
```bash
php artisan tinker
# Copy-paste tests from "Manual Testing" section
```

Then proceed to automated tests:
```bash
php artisan test --filter="Observer|MultiTenant" --stop-on-failure
```
