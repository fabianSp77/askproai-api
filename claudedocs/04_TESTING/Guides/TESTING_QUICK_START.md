# Multi-Tenant Security Testing - Quick Start Guide

**Generated**: 2025-10-02
**Related Document**: MULTI_TENANT_SECURITY_TEST_PLAN.md

## 🚀 Quick Execution

### Run All Security Tests
```bash
php artisan test \
  --filter="Observer|MultiTenant|SuperAdmin|Assignment|BelongsToCompany|CompleteWorkflow|XSSAttack" \
  --stop-on-failure
```

### Run By Priority

**P0 Critical (Must Pass 100%)**
```bash
php artisan test --filter=Observer
php artisan test --filter=MultiTenant
```

**P1 Important (Must Pass 95%+)**
```bash
php artisan test --filter=XSSAttack
php artisan test --filter=BelongsToCompany
```

**P2 Edge Cases (Must Pass 90%+)**
```bash
php artisan test --filter=EdgeCase
```

---

## 📋 Test File Locations

**Create These Test Files**:

### Unit Tests - Observers
- `/var/www/api-gateway/tests/Unit/Observers/PolicyConfigurationObserverTest.php`
- `/var/www/api-gateway/tests/Unit/Observers/CallbackRequestObserverTest.php`
- `/var/www/api-gateway/tests/Unit/Observers/NotificationConfigurationObserverTest.php`
- `/var/www/api-gateway/tests/Unit/Observers/ObserverTriggeringTest.php`

### Feature Tests - Security
- `/var/www/api-gateway/tests/Feature/Security/MultiTenantAuthorizationTest.php`
- `/var/www/api-gateway/tests/Feature/Security/SuperAdminAuthorizationTest.php`
- `/var/www/api-gateway/tests/Feature/Security/AssignmentAuthorizationTest.php`
- `/var/www/api-gateway/tests/Feature/Security/XSSAttackVectorTest.php`

### Feature Tests - Integration
- `/var/www/api-gateway/tests/Feature/Integration/BelongsToCompanyIntegrationTest.php`
- `/var/www/api-gateway/tests/Feature/Integration/CompleteWorkflowTest.php`

### Feature Tests - Edge Cases
- `/var/www/api-gateway/tests/Feature/EdgeCases/MissingCompanyIdTest.php`
- `/var/www/api-gateway/tests/Feature/EdgeCases/InvalidCompanyIdTest.php`
- `/var/www/api-gateway/tests/Feature/EdgeCases/SoftDeleteTest.php`
- `/var/www/api-gateway/tests/Feature/EdgeCases/ConcurrentOperationsTest.php`

---

## ⚡ Top 10 Critical Tests to Run First

**Run these to verify core security immediately**:

```bash
# 1. Observer triggering
php artisan tinker
>>> PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration')

# 2. Cross-tenant isolation
# Create test in: tests/Feature/Security/MultiTenantAuthorizationTest.php::test_notification_event_mapping_prevents_cross_tenant_view

# 3. XSS prevention in CallbackRequest
# Test: CallbackRequestObserverTest::test_callback_request_sanitizes_customer_name

# 4. Phone validation
# Test: CallbackRequestObserverTest::test_callback_request_validates_e164_format

# 5. Policy config schema validation
# Test: PolicyConfigurationObserverTest::test_cancellation_policy_validates_required_fields

# 6. Notification event type validation
# Test: NotificationConfigurationObserverTest::test_notification_config_validates_event_type_exists

# 7. Super admin bypass
# Test: SuperAdminAuthorizationTest::test_super_admin_bypasses_callback_request_policy

# 8. Assignment-based authorization
# Test: AssignmentAuthorizationTest::test_assigned_staff_can_view_callback_request

# 9. BelongsToCompany auto-fill
# Test: BelongsToCompanyIntegrationTest::test_belongs_to_company_auto_fills_company_id_on_create

# 10. Complete workflow
# Test: CompleteWorkflowTest::test_complete_callback_request_workflow
```

---

## 🔍 Manual Verification Steps

### 1. Observer Registration
```bash
# Check EventServiceProvider
grep -A 20 "protected \$observers" app/Providers/EventServiceProvider.php
```

**Expected Output**:
```php
protected $observers = [
    PolicyConfiguration::class => [PolicyConfigurationObserver::class],
    CallbackRequest::class => [CallbackRequestObserver::class],
    NotificationConfiguration::class => [NotificationConfigurationObserver::class],
];
```

### 2. Policy Registration
```bash
# Check AuthServiceProvider
grep -A 20 "protected \$policies" app/Providers/AuthServiceProvider.php
```

**Expected Output**:
```php
protected $policies = [
    NotificationEventMapping::class => NotificationEventMappingPolicy::class,
    CallbackEscalation::class => CallbackEscalationPolicy::class,
    CallbackRequest::class => CallbackRequestPolicy::class,
    NotificationConfiguration::class => NotificationConfigurationPolicy::class,
];
```

### 3. Migration Status
```bash
php artisan migrate:status | grep company_id
```

**Expected**: 6 migrations with company_id columns

### 4. BelongsToCompany Trait Usage
```bash
grep -l "use BelongsToCompany" app/Models/*.php
```

**Expected Models**:
- NotificationConfiguration.php
- CallbackEscalation.php
- NotificationEventMapping.php
- CallbackRequest.php
- PolicyConfiguration.php
- AppointmentModification.php

---

## 🛡️ Security Risk Matrix

| Component | Risk | Impact | Test Priority |
|-----------|------|--------|---------------|
| Observer XSS Prevention | 🔴 CRITICAL | High | P0 |
| Cross-Tenant Isolation | 🔴 CRITICAL | High | P0 |
| Observer Validation | 🔴 CRITICAL | High | P0 |
| Assignment Authorization | 🟡 HIGH | Medium | P1 |
| Polymorphic Authorization | 🟡 HIGH | Medium | P1 |
| Edge Case Handling | 🟢 MEDIUM | Low | P2 |

---

## ✅ Success Criteria

**Before Production Deployment**:
- [ ] All Observer tests pass (100%)
- [ ] All MultiTenant tests pass (100%)
- [ ] All SuperAdmin tests pass (100%)
- [ ] All Assignment tests pass (100%)
- [ ] XSSAttack tests pass (95%+)
- [ ] BelongsToCompany tests pass (95%+)
- [ ] EdgeCase tests pass (90%+)
- [ ] Test coverage >80% for security components
- [ ] Manual verification checklist complete

---

## 🚨 Failure Response

**If Observer Tests Fail**:
```bash
# Clear caches
php artisan optimize:clear
php artisan config:cache

# Verify observer registration
php artisan tinker
>>> PolicyConfiguration::getEventDispatcher()->hasListeners('eloquent.creating: App\Models\PolicyConfiguration')
```

**If Authorization Tests Fail**:
```bash
# Clear policy cache
php artisan optimize:clear

# Verify policy registration
php artisan tinker
>>> Gate::getPolicyFor(App\Models\CallbackRequest::class)
```

**If XSS Tests Fail**:
- Review observer sanitization logic
- Check sanitization is executing before save
- Test with specific XSS payload in isolation

---

## 📊 Test Metrics

**Total Test Cases**: ~155 comprehensive tests

**Estimated Execution Time**: 5-10 minutes

**Coverage Target**: >85% for security components

**Component Breakdown**:
- Observer Validation: 50+ tests
- Authorization Policies: 40+ tests
- Integration Tests: 20+ tests
- Edge Cases: 20+ tests
- XSS Security: 25+ tests

---

## 🔧 Quick Test Template

**Use this template for new security tests**:

```php
<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if needed
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'super_admin']);
    }

    public function test_cross_tenant_isolation(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userA->assignRole('admin');

        // Create resource for Company B
        $resourceB = YourModel::factory()->create([
            'company_id' => $companyB->id,
        ]);

        $this->actingAs($userA);

        // User from Company A should NOT see Company B's data
        $this->assertFalse($userA->can('view', $resourceB));
    }
}
```

---

## 📖 Additional Resources

- **Full Test Plan**: `/var/www/api-gateway/claudedocs/MULTI_TENANT_SECURITY_TEST_PLAN.md`
- **Observer Code**: `/var/www/api-gateway/app/Observers/`
- **Policy Code**: `/var/www/api-gateway/app/Policies/`
- **Trait Code**: `/var/www/api-gateway/app/Traits/BelongsToCompany.php`

---

**Ready to Start Testing?**

```bash
# Step 1: Create test directories
mkdir -p tests/Unit/Observers
mkdir -p tests/Feature/Security
mkdir -p tests/Feature/Integration
mkdir -p tests/Feature/EdgeCases

# Step 2: Start with critical observer tests
# Copy test code from MULTI_TENANT_SECURITY_TEST_PLAN.md Section 1

# Step 3: Run your first test
php artisan test --filter=PolicyConfigurationObserverTest

# Step 4: Continue with authorization tests
# Copy test code from MULTI_TENANT_SECURITY_TEST_PLAN.md Section 2

# Step 5: Run complete test suite
php artisan test --filter="Observer|MultiTenant|SuperAdmin"
```
