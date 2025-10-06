# PHASE A: Critical Security Fixes - Implementation Plan

**Estimated Total Time**: 60 hours
**Priority**: CRITICAL - Start immediately
**Risk Level**: HIGH if not addressed

---

## Executive Summary

5 Critical vulnerabilities requiring immediate attention:
1. **Multi-Tenant Isolation Incomplete** - 40+ models missing BelongsToCompany trait
2. **Admin Role Bypasses Multi-Tenant Scope** - Super admins can see all company data
3. **Webhook Endpoints Unprotected** - 4 routes lack authentication/signature verification
4. **User Model Not Company-Scoped** - Users can see cross-company data
5. **Service Discovery Lacks Validation** - Service lookups bypass company_id checks

---

## EXECUTION ORDER

Critical path: A2 → A4 → A1 → A5 → A3

**Rationale**: Fix the scope bypass first (A2), then protect User model (A4), then systematically protect all other models (A1), validate service lookups (A5), and finally secure webhooks (A3).

---

## A1: Multi-Tenant Model Protection (35 hours)

### Phase 1: Model Discovery & Categorization (3 hours)

**Objective**: Identify ALL models and categorize by priority and requirement for company scoping.

#### Step 1.1: Read All Model Files (1 hour)
```bash
# Execute in parallel
find /var/www/api-gateway/app/Models -name "*.php" | sort
```

**Models Already Protected** (6 models - verified):
- `/var/www/api-gateway/app/Models/NotificationConfiguration.php`
- `/var/www/api-gateway/app/Models/CallbackEscalation.php`
- `/var/www/api-gateway/app/Models/NotificationEventMapping.php`
- `/var/www/api-gateway/app/Models/CallbackRequest.php`
- `/var/www/api-gateway/app/Models/PolicyConfiguration.php`
- `/var/www/api-gateway/app/Models/AppointmentModification.php`

**Total Models Found**: 45 models

#### Step 1.2: Categorize Models (2 hours)

**P0 - CRITICAL (Must fix first - 12 models)**:
- User.php - Authentication & authorization
- Customer.php - Customer data isolation
- Appointment.php - Booking data
- Service.php - Service definitions
- Staff.php - Staff assignments
- Branch.php - Location data
- Call.php - Call records
- PhoneNumber.php - Contact information
- CustomerNote.php - Customer notes
- Invoice.php - Financial records
- InvoiceItem.php - Financial line items
- Transaction.php - Payment records

**P1 - HIGH (Fix second - 15 models)**:
- RetellAgent.php - AI agent configurations
- Integration.php - Third-party integrations
- WorkingHour.php - Staff availability
- ActivityLog.php - Audit trails
- BalanceTopup.php - Financial transactions
- PricingPlan.php - Pricing configurations
- RecurringAppointmentPattern.php - Recurring bookings
- NotificationQueue.php - Notification queue
- NotificationTemplate.php - Templates
- NotificationProvider.php - Provider configs
- WebhookLog.php - Webhook audit
- WebhookEvent.php - Webhook events
- CalcomEventMap.php - Cal.com mappings
- CalcomTeamMember.php - Team member sync
- TeamEventTypeMapping.php - Event type mappings

**P2 - MEDIUM (Fix third - 6 models)**:
- UserPreference.php - User settings
- PlatformCost.php - Cost tracking
- MonthlyCostReport.php - Cost reports
- CurrencyExchangeRate.php - Exchange rates
- NestedBookingSlot.php - Booking slots
- AppointmentModificationStat.php - Statistics

**EXCLUDE - No company scoping needed (6 models)**:
- Role.php - Global roles (Spatie)
- Tenant.php - Tenant management (higher level than company)
- SystemSetting.php - Global system settings
- BalanceBonusTier.php - Global tier definitions
- Company.php - Company itself (no self-reference)
- Permission.php - Global permissions (if exists)

### Phase 2: Database Schema Verification (2 hours)

#### Step 2.1: Check Existing company_id Columns
```bash
# Check migrations directory
find /var/www/api-gateway/database/migrations -name "*.php" -exec grep -l "company_id" {} \;

# Check actual database schema (if accessible)
php artisan db:show --counts
php artisan db:table appointments --show-indexes
php artisan db:table customers --show-indexes
php artisan db:table services --show-indexes
```

#### Step 2.2: Identify Missing Columns
**Action**: For each P0/P1/P2 model, verify:
1. Table has `company_id` column
2. Column is indexed
3. Column is nullable or has default

**Expected Results**:
- Most tables likely have `company_id` (based on code review)
- May need to create migration for missing columns

### Phase 3: P0 Model Protection (12 hours)

**Order**: Fix in dependency order to avoid cascading errors.

#### A1.1: User Model (1.5 hours)
**File**: `/var/www/api-gateway/app/Models/User.php`
**Current Status**: NO BelongsToCompany trait, has company_id column

**⚠️ SPECIAL HANDLING REQUIRED**:
- User model is used for authentication
- Must NOT apply global scope in authentication flows
- Need conditional scoping

**Implementation**:

**File**: `/var/www/api-gateway/app/Models/User.php`

**Location**: After line 16 (after `use HasFactory, Notifiable, HasRoles;`)

**Add**:
```php
use App\Traits\BelongsToCompany;
```

**Change Line 16 from**:
```php
use HasFactory, Notifiable, HasRoles;
```

**To**:
```php
use HasFactory, Notifiable, HasRoles, BelongsToCompany;
```

**Validation Command**:
```bash
# Test that authenticated users only see their company users
php artisan tinker
>>> User::count() // Should return only current company users
>>> User::withoutCompanyScope()->count() // Should return all users
```

**Risk**: MEDIUM - May break authentication middleware
**Mitigation**: Test login flow immediately after change

---

#### A1.2: Customer Model (1.5 hours)
**File**: `/var/www/api-gateway/app/Models/Customer.php`
**Current Status**: NO BelongsToCompany trait, has company_id column

**Implementation**:

**Line 13 - Add import**:
```php
use App\Traits\BelongsToCompany;
```

**Line 13 - Change from**:
```php
use HasFactory, SoftDeletes;
```

**To**:
```php
use HasFactory, SoftDeletes, BelongsToCompany;
```

**Validation Command**:
```bash
php artisan tinker
>>> Customer::count() // Should only show current company customers
>>> Customer::allCompanies()->count() // Should show all
```

---

#### A1.3: Appointment Model (1.5 hours)
**File**: `/var/www/api-gateway/app/Models/Appointment.php`
**Current Status**: NO BelongsToCompany trait, has company_id column, has $guarded protection

**Implementation**:

**Line 11 - Add import**:
```php
use App\Traits\BelongsToCompany;
```

**Line 14 - Change from**:
```php
use HasFactory, SoftDeletes;
```

**To**:
```php
use HasFactory, SoftDeletes, BelongsToCompany;
```

**Validation Command**:
```bash
php artisan tinker
>>> Appointment::count() // Should only show current company appointments
>>> Appointment::where('company_id', 1)->count() // Should fail or show filtered
```

---

#### A1.4: Service Model (1.5 hours)
**File**: `/var/www/api-gateway/app/Models/Service.php`
**Current Status**: NO BelongsToCompany trait, has company_id column, has $guarded protection

**Implementation**:

**Line 12 - Add import**:
```php
use App\Traits\BelongsToCompany;
```

**Line 15 - Change from**:
```php
use HasFactory, SoftDeletes, HasConfigurationInheritance;
```

**To**:
```php
use HasFactory, SoftDeletes, HasConfigurationInheritance, BelongsToCompany;
```

**Validation Command**:
```bash
php artisan tinker
>>> Service::count() // Should only show current company services
>>> Service::find(1) // Should return null if service belongs to another company
```

---

#### A1.5-A1.12: Remaining P0 Models (6 hours)

**Apply same pattern to**:
1. Staff.php
2. Branch.php
3. Call.php
4. PhoneNumber.php
5. CustomerNote.php
6. Invoice.php
7. InvoiceItem.php
8. Transaction.php

**Pattern for each**:
1. Read model file
2. Add `use App\Traits\BelongsToCompany;` import
3. Add `BelongsToCompany` to trait usage line
4. Verify company_id column exists
5. Test with tinker

**Time per model**: 45 minutes
- 15 min: Read and modify
- 15 min: Test with tinker
- 15 min: Check relationships and fix breaking queries

---

### Phase 4: P1 Model Protection (15 hours)

**Apply BelongsToCompany trait to 15 models**

**Batch 1 - Retell & Integrations (3 hours)**:
- RetellAgent.php
- Integration.php
- CalcomEventMap.php
- CalcomTeamMember.php
- TeamEventTypeMapping.php

**Batch 2 - Notifications (3 hours)**:
- NotificationQueue.php
- NotificationTemplate.php
- NotificationProvider.php
- WebhookLog.php
- WebhookEvent.php

**Batch 3 - Configuration (3 hours)**:
- WorkingHour.php
- ActivityLog.php
- PricingPlan.php
- RecurringAppointmentPattern.php
- BalanceTopup.php

**Time per model**: 1 hour (including testing)

---

### Phase 5: P2 Model Protection (3 hours)

**Models**:
- UserPreference.php
- PlatformCost.php
- MonthlyCostReport.php
- CurrencyExchangeRate.php
- NestedBookingSlot.php
- AppointmentModificationStat.php

**Time per model**: 30 minutes (lower risk, simpler models)

---

### Phase 6: Testing & Validation (2 hours)

#### Comprehensive Test Suite

**File**: `/var/www/api-gateway/tests/Feature/MultiTenantIsolationTest.php`

**Create test**:
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_their_company_data()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);

        $this->assertEquals(1, Customer::count());
        $this->assertEquals($customer1->id, Customer::first()->id);
    }

    public function test_super_admin_bypass_still_works()
    {
        // Test after fixing CompanyScope
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        Customer::factory()->create(['company_id' => $company1->id]);
        Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($superAdmin);

        // Super admin should see all
        $this->assertEquals(2, Customer::count());
    }
}
```

**Run tests**:
```bash
php artisan test --filter MultiTenantIsolationTest
```

---

## A2: Fix CompanyScope Admin Bypass (2 hours)

### Issue Analysis

**File**: `/var/www/api-gateway/app/Scopes/CompanyScope.php`
**Line 22**: `if ($user->hasAnyRole(['super_admin', 'admin'])) { return; }`

**Problem**: Admin users can see ALL company data, not just their own company.

**Fix**: Only `super_admin` should bypass scope, `admin` should still be scoped to their company.

### Implementation

**Step 2.1: Modify CompanyScope (30 minutes)**

**File**: `/var/www/api-gateway/app/Scopes/CompanyScope.php`

**Change Line 22 from**:
```php
if ($user->hasAnyRole(['super_admin', 'admin'])) {
    return;
}
```

**To**:
```php
// Only super_admin bypasses multi-tenant scope
// Admin users are still scoped to their company
if ($user->hasRole('super_admin')) {
    return;
}
```

**Alternative (if you want to keep admin bypass but log it)**:
```php
if ($user->hasRole('super_admin')) {
    return; // Super admin sees everything
}

if ($user->hasRole('admin')) {
    // Admin bypass with audit trail
    \Log::info('Admin bypassing company scope', [
        'user_id' => $user->id,
        'user_email' => $user->email,
        'model' => get_class($model)
    ]);
    return;
}
```

### Step 2.2: Create Test Case (30 minutes)

**File**: `/var/www/api-gateway/tests/Feature/CompanyScopeAdminTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyScopeAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bypasses_company_scope()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $superAdmin = User::factory()->create(['company_id' => $company1->id]);
        $superAdmin->assignRole('super_admin');

        Customer::factory()->create(['company_id' => $company1->id]);
        Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($superAdmin);

        // Super admin sees all companies
        $this->assertEquals(2, Customer::count());
    }

    public function test_admin_only_sees_own_company()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $admin = User::factory()->create(['company_id' => $company1->id]);
        $admin->assignRole('admin');

        Customer::factory()->create(['company_id' => $company1->id]);
        Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($admin);

        // Admin only sees their company
        $this->assertEquals(1, Customer::count());
    }

    public function test_regular_user_only_sees_own_company()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user = User::factory()->create(['company_id' => $company1->id]);

        Customer::factory()->create(['company_id' => $company1->id]);
        Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user);

        // Regular user only sees their company
        $this->assertEquals(1, Customer::count());
    }
}
```

### Step 2.3: Run Tests & Validate (30 minutes)

```bash
# Run specific test
php artisan test --filter CompanyScopeAdminTest

# Run all multi-tenant tests
php artisan test tests/Feature/CompanyScopeAdminTest.php

# Manual validation with tinker
php artisan tinker
>>> $admin = User::where('email', 'admin@example.com')->first()
>>> $admin->assignRole('admin')
>>> Auth::login($admin)
>>> Customer::count() // Should only show admin's company
```

### Step 2.4: Update Documentation (30 minutes)

**File**: `/var/www/api-gateway/claudedocs/SECURITY_POLICIES.md`

Add section:
```markdown
## Multi-Tenant Role Access Levels

### Super Admin
- **Scope**: ALL companies
- **Use Case**: Platform administrators, support staff
- **Bypass**: Bypasses CompanyScope global scope
- **Audit**: All actions logged

### Admin
- **Scope**: SINGLE company only
- **Use Case**: Company administrators
- **Bypass**: Does NOT bypass CompanyScope
- **Audit**: Standard audit logging

### Regular Users
- **Scope**: SINGLE company only
- **Use Case**: Staff, managers
- **Bypass**: Does NOT bypass CompanyScope
- **Audit**: Standard audit logging
```

---

## A3: Webhook Authentication (15 hours)

### Current State Analysis

**Webhook Routes Analysis** (from `/var/www/api-gateway/routes/api.php`):

**PROTECTED (Already have signature verification)**:
- Line 36-39: `/calcom/webhook` - Has `calcom.signature` middleware ✅
- Line 47-50: `/webhooks/calcom` - Has `calcom.signature` middleware ✅
- Line 53-55: `/webhooks/retell` - Has `retell.signature` middleware ✅
- Line 83-85: `/webhooks/stripe` - Has `stripe.webhook` middleware ✅

**PARTIALLY PROTECTED**:
- Line 58-75: Retell function call routes - Have `retell.function.whitelist` and rate limiting
  - `/webhooks/retell/function`
  - `/webhooks/retell/function-call`
  - `/webhooks/retell/collect-appointment`
  - `/webhooks/retell/check-availability`

**UNPROTECTED (Need authentication)**:
- Line 27-29: `/webhook` (legacy Retell) - Only throttling, NO signature ❌
- Line 88-89: `/webhooks/monitor` - NO authentication ⚠️
- Line 78-80: `/webhooks/retell/diagnostic` - Has auth:sanctum ✅

### Implementation Plan

#### A3.1: Protect Legacy Retell Webhook (2 hours)

**Issue**: Line 27-29 in routes/api.php has NO signature verification

**Current Code**:
```php
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->name('webhook.retell.legacy')
    ->middleware(['throttle:60,1']);
```

**Step 1: Check if middleware exists**
```bash
grep -rn "retell.signature" /var/www/api-gateway/bootstrap/app.php
```

**Step 2: Apply middleware**

**File**: `/var/www/api-gateway/routes/api.php`

**Change Line 27-29 from**:
```php
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->name('webhook.retell.legacy')
    ->middleware(['throttle:60,1']);
```

**To**:
```php
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->name('webhook.retell.legacy')
    ->middleware(['retell.signature', 'throttle:60,1']);
```

**Step 3: Test**
```bash
# Send test webhook without signature
curl -X POST http://localhost/api/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
# Expected: 401 Unauthorized

# Send with valid signature (get from Retell dashboard)
curl -X POST http://localhost/api/webhook \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: sha256=VALID_SIGNATURE" \
  -d '{"test": "data"}'
# Expected: 200 OK
```

---

#### A3.2: Protect Webhook Monitor Endpoint (1 hour)

**Issue**: Line 88-89 - Monitor endpoint is completely open

**Current Code**:
```php
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor'])
    ->name('webhooks.monitor');
```

**Decision Required**: Should this be public or protected?
- **Option A**: Public monitoring (read-only, no sensitive data)
- **Option B**: Require authentication

**Recommended: Option B - Require Authentication**

**Change Line 88-89 from**:
```php
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor'])
    ->name('webhooks.monitor');
```

**To**:
```php
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor'])
    ->name('webhooks.monitor')
    ->middleware(['auth:sanctum', 'throttle:30,1']);
```

**Test**:
```bash
# Without authentication
curl http://localhost/api/webhooks/monitor
# Expected: 401 Unauthorized

# With authentication
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost/api/webhooks/monitor
# Expected: 200 OK with monitoring data
```

---

#### A3.3: Validate Retell Function Call Security (3 hours)

**Routes to Review**:
- `/webhooks/retell/function` (Line 58-60)
- `/webhooks/retell/function-call` (Line 63-65)
- `/webhooks/retell/collect-appointment` (Line 68-70)
- `/webhooks/retell/check-availability` (Line 73-75)

**Current Protection**:
- `retell.function.whitelist` - IP whitelist
- `retell.call.ratelimit` - Rate limiting
- `throttle:100,1` - General throttle

**Action Required**: Verify middleware implementations

**Step 1: Check VerifyRetellFunctionSignatureWithWhitelist.php**
```bash
cat /var/www/api-gateway/app/Http/Middleware/VerifyRetellFunctionSignatureWithWhitelist.php
```

**Step 2: Verify IP whitelist configuration**
```bash
grep -rn "RETELL_WHITELIST\|retell.*whitelist" /var/www/api-gateway/.env.example
```

**Step 3: Add signature verification if missing**

**If whitelist is insufficient, enhance to**:
```php
->middleware(['retell.function.signature', 'retell.function.whitelist', 'retell.call.ratelimit', 'throttle:100,1']);
```

**Step 4: Document expected headers**

**File**: `/var/www/api-gateway/claudedocs/WEBHOOK_SECURITY.md`

```markdown
## Retell Function Call Security

### Required Headers
- `X-Retell-Signature`: HMAC-SHA256 signature
- `X-Retell-Call-Id`: Unique call identifier

### IP Whitelist
Retell function calls MUST originate from:
- 54.173.237.218
- 3.80.240.110
- (Add Retell's official IPs)

### Rate Limits
- Function calls: 100/minute per IP
- Global throttle: 100/minute
- Call-specific rate limit: per call_id
```

---

#### A3.4: Create Webhook Security Tests (4 hours)

**File**: `/var/www/api-gateway/tests/Feature/WebhookSecurityTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class WebhookSecurityTest extends TestCase
{
    public function test_calcom_webhook_rejects_invalid_signature()
    {
        $payload = ['event' => 'booking.created'];

        $response = $this->postJson('/api/calcom/webhook', $payload);

        $response->assertStatus(401);
    }

    public function test_calcom_webhook_accepts_valid_signature()
    {
        $payload = json_encode(['event' => 'booking.created']);
        $secret = config('services.calcom.webhook_secret');
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson('/api/calcom/webhook',
            json_decode($payload, true),
            ['X-Cal-Signature-256' => $signature]
        );

        $response->assertStatus(200);
    }

    public function test_retell_legacy_webhook_requires_signature()
    {
        $payload = ['event' => 'call.ended'];

        $response = $this->postJson('/api/webhook', $payload);

        $response->assertStatus(401);
    }

    public function test_stripe_webhook_rejects_invalid_signature()
    {
        $payload = ['type' => 'payment_intent.succeeded'];

        $response = $this->postJson('/api/webhooks/stripe', $payload);

        $response->assertStatus(401);
    }

    public function test_webhook_monitor_requires_authentication()
    {
        $response = $this->getJson('/api/webhooks/monitor');

        $response->assertStatus(401);
    }

    public function test_retell_function_call_validates_whitelist()
    {
        // Mock request from non-whitelisted IP
        $response = $this->postJson('/api/webhooks/retell/function', [
            'call_id' => 'test-call-123'
        ]);

        // Should be rejected if IP not in whitelist
        $response->assertStatus(403);
    }
}
```

**Run tests**:
```bash
php artisan test --filter WebhookSecurityTest
```

---

#### A3.5: Audit & Documentation (5 hours)

**Step 1: Create Webhook Inventory (2 hours)**

**File**: `/var/www/api-gateway/claudedocs/WEBHOOK_INVENTORY.md`

```markdown
# Webhook Security Inventory

## Production Webhooks

### Cal.com Webhooks
- **Endpoint**: POST /api/calcom/webhook
- **Authentication**: HMAC-SHA256 signature
- **Middleware**: calcom.signature, throttle:60,1
- **Secret Location**: config('services.calcom.webhook_secret')
- **Expected Headers**: X-Cal-Signature-256
- **Status**: ✅ PROTECTED

### Retell Webhooks
- **Endpoint**: POST /api/webhooks/retell
- **Authentication**: HMAC-SHA256 signature
- **Middleware**: retell.signature, throttle:60,1
- **Secret Location**: config('services.retell.webhook_secret')
- **Expected Headers**: X-Retell-Signature
- **Status**: ✅ PROTECTED

### Stripe Webhooks
- **Endpoint**: POST /api/webhooks/stripe
- **Authentication**: Stripe signature verification
- **Middleware**: stripe.webhook, throttle:60,1
- **Secret Location**: config('services.stripe.webhook_secret')
- **Expected Headers**: Stripe-Signature
- **Status**: ✅ PROTECTED

### Retell Legacy Webhook
- **Endpoint**: POST /api/webhook
- **Authentication**: HMAC-SHA256 signature (NEWLY ADDED)
- **Middleware**: retell.signature, throttle:60,1
- **Status**: ✅ FIXED IN PHASE A

### Retell Function Calls
- **Endpoints**:
  - POST /api/webhooks/retell/function
  - POST /api/webhooks/retell/function-call
  - POST /api/webhooks/retell/collect-appointment
  - POST /api/webhooks/retell/check-availability
- **Authentication**: IP whitelist + signature
- **Middleware**: retell.function.whitelist, retell.call.ratelimit, throttle:100,1
- **Status**: ⚠️ VERIFY WHITELIST CONFIGURATION

### Webhook Monitor
- **Endpoint**: GET /api/webhooks/monitor
- **Authentication**: Sanctum token (NEWLY ADDED)
- **Middleware**: auth:sanctum, throttle:30,1
- **Status**: ✅ FIXED IN PHASE A

## Security Checklist

- [x] All webhook endpoints have authentication
- [x] Signature verification middleware implemented
- [x] Rate limiting applied to all endpoints
- [x] Secrets stored in environment variables
- [x] Failed signature attempts logged
- [x] Test suite covers all endpoints
- [ ] Retell IP whitelist verified (ACTION REQUIRED)
- [ ] Monitoring alerts configured
```

**Step 2: Create Runbook (2 hours)**

**File**: `/var/www/api-gateway/claudedocs/WEBHOOK_SECURITY_RUNBOOK.md`

```markdown
# Webhook Security Runbook

## Rotating Webhook Secrets

### Cal.com Secret Rotation
1. Generate new secret in Cal.com dashboard
2. Update `CALCOM_WEBHOOK_SECRET` in .env
3. Deploy to all environments
4. Monitor for signature failures
5. Verify Cal.com is using new secret

### Retell Secret Rotation
1. Generate new secret in Retell dashboard
2. Update `RETELL_WEBHOOK_SECRET` in .env
3. Deploy to all environments
4. Test with Retell test webhook
5. Monitor webhook logs

### Stripe Secret Rotation
1. Generate new webhook endpoint in Stripe dashboard
2. Update `STRIPE_WEBHOOK_SECRET` in .env
3. Deploy to all environments
4. Disable old webhook endpoint
5. Verify payment webhooks working

## Investigating Signature Failures

### Check Logs
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "Signature"
```

### Common Issues
1. **Secret mismatch**: Verify .env matches provider dashboard
2. **Timestamp drift**: Check server time sync
3. **Payload tampering**: Verify raw body used for signature
4. **Header format**: Check header name and format

### Testing Signatures Manually
```bash
# Cal.com
echo -n 'PAYLOAD' | openssl dgst -sha256 -hmac 'SECRET'

# Retell
echo -n 'PAYLOAD' | openssl dgst -sha256 -hmac 'SECRET'

# Stripe uses webhook library (cannot test manually)
```

## Responding to Security Incidents

### Unauthorized Webhook Access Detected
1. Immediately rotate affected secret
2. Review logs for scope of breach
3. Check for data exfiltration
4. Update IP whitelist if applicable
5. File incident report

### IP Whitelist Bypass Attempt
1. Log IP address and request details
2. Block IP at firewall level
3. Review Retell dashboard for suspicious activity
4. Notify Retell support if needed
```

**Step 3: Create Monitoring Alerts (1 hour)**

**File**: `/var/www/api-gateway/app/Console/Commands/MonitorWebhookSecurity.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookLog;

class MonitorWebhookSecurity extends Command
{
    protected $signature = 'webhook:monitor-security';
    protected $description = 'Monitor webhook security events';

    public function handle()
    {
        $failedSignatures = WebhookLog::where('created_at', '>=', now()->subHour())
            ->where('status', 'signature_failed')
            ->count();

        if ($failedSignatures > 10) {
            $this->error("⚠️ HIGH RATE OF SIGNATURE FAILURES: {$failedSignatures} in last hour");
            // Send alert to monitoring system
        }

        $this->info("✅ Webhook security check complete");
        $this->info("Failed signatures (last hour): {$failedSignatures}");
    }
}
```

**Schedule in** `/var/www/api-gateway/app/Console/Kernel.php`:
```php
$schedule->command('webhook:monitor-security')->hourly();
```

---

## A4: User Model Scoping (3 hours)

**COVERED IN A1.1 - See Above**

This task is integrated into A1 Phase 3, but separated here for tracking purposes.

**Status**: Implementation details in A1.1
**Time**: 1.5 hours (already counted in A1 total)

---

## A5: Service Discovery Validation (5 hours)

### Issue Analysis

**File**: `/var/www/api-gateway/app/Http/Controllers/Api/V2/BookingController.php`
**Line 41**: `$service = Service::findOrFail($validated['service_id']);`

**Problem**: Service lookup does NOT verify company_id, allowing cross-company service booking.

### Vulnerability Scenarios

1. **Malicious User**: User from Company A requests service_id from Company B
2. **Data Leakage**: Service details exposed across company boundaries
3. **Pricing Manipulation**: Book service with different company's pricing

### Implementation

#### A5.1: Fix Service Discovery (2 hours)

**Step 1: Identify All Vulnerable Lookups**

```bash
# Find all Service::find() calls
grep -rn "Service::find" /var/www/api-gateway/app/Http/Controllers/

# Expected locations:
# - BookingController.php (Line 41)
# - AvailabilityController.php (possible)
# - RetellApiController.php (possible)
```

**Step 2: Fix BookingController.php Line 41**

**File**: `/var/www/api-gateway/app/Http/Controllers/Api/V2/BookingController.php`

**Change Line 41 from**:
```php
$service = Service::findOrFail($validated['service_id']);
```

**To**:
```php
// Ensure service belongs to authenticated user's company
$service = Service::where('id', $validated['service_id'])
    ->where('company_id', auth()->user()->company_id)
    ->firstOrFail();
```

**OR (if BelongsToCompany trait is applied in A1)**:
```php
// CompanyScope will automatically filter by company_id
$service = Service::findOrFail($validated['service_id']);
// This is now safe because BelongsToCompany trait applies global scope
```

**IMPORTANT**: After A1.4 is complete, this line becomes safe automatically due to global scope.

---

#### A5.2: Fix Additional Service Lookups (1 hour)

**Check AvailabilityController.php**:
```bash
cat /var/www/api-gateway/app/Http/Controllers/Api/V2/AvailabilityController.php | grep -n "Service::find"
```

**Apply same fix pattern to ANY Service::find() calls**

---

#### A5.3: Create Validation Helper (1 hour)

**To prevent future issues, create a helper trait**

**File**: `/var/www/api-gateway/app/Traits/ValidatesCompanyOwnership.php`

```php
<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ValidatesCompanyOwnership
{
    /**
     * Find model by ID and verify it belongs to authenticated user's company
     *
     * @param string $modelClass
     * @param int $id
     * @return Model
     * @throws NotFoundHttpException
     */
    protected function findOwnedByCompany(string $modelClass, int $id): Model
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            throw new NotFoundHttpException("Resource not found");
        }

        return $modelClass::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();
    }

    /**
     * Verify model belongs to authenticated user's company
     *
     * @param Model $model
     * @return bool
     * @throws NotFoundHttpException
     */
    protected function verifyCompanyOwnership(Model $model): bool
    {
        $user = auth()->user();

        if (!$user || !$user->company_id) {
            throw new NotFoundHttpException("Resource not found");
        }

        if ($model->company_id !== $user->company_id) {
            throw new NotFoundHttpException("Resource not found");
        }

        return true;
    }
}
```

**Usage in BookingController**:
```php
use App\Traits\ValidatesCompanyOwnership;

class BookingController extends Controller
{
    use ApiResponse, ValidatesCompanyOwnership;

    public function create(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Secure lookup with company validation
        $service = $this->findOwnedByCompany(Service::class, $validated['service_id']);

        // Rest of method...
    }
}
```

---

#### A5.4: Create Test Cases (1 hour)

**File**: `/var/www/api-gateway/tests/Feature/ServiceDiscoverySecurityTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceDiscoverySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_book_service_from_other_company()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user = User::factory()->create(['company_id' => $company1->id]);
        $service = Service::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $service->id,
            'branch_id' => 1,
            'start' => now()->addDay()->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com'
            ]
        ]);

        // Should return 404 not 403 (to prevent information disclosure)
        $response->assertStatus(404);
    }

    public function test_user_can_book_service_from_own_company()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v2/bookings', [
            'service_id' => $service->id,
            'branch_id' => 1,
            'start' => now()->addDay()->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com'
            ]
        ]);

        // Should succeed (or fail for other reasons, not 404)
        $response->assertStatus([200, 201, 422]); // Not 404
    }
}
```

**Run tests**:
```bash
php artisan test --filter ServiceDiscoverySecurityTest
```

---

## VALIDATION & ROLLOUT

### Phase A Validation Checklist

**Before Production Deployment**:

- [ ] All P0 models have BelongsToCompany trait
- [ ] All P1 models have BelongsToCompany trait
- [ ] All P2 models have BelongsToCompany trait
- [ ] CompanyScope admin bypass removed
- [ ] User model scoping tested
- [ ] All webhook endpoints authenticated
- [ ] Service discovery validates company_id
- [ ] All test suites passing
- [ ] Documentation updated
- [ ] Rollback plan prepared

### Testing Protocol

**Step 1: Run Full Test Suite**
```bash
php artisan test
```

**Step 2: Manual Testing in Staging**
```bash
# Test multi-tenant isolation
php artisan tinker
>>> $user1 = User::find(1) // Company 1
>>> $user2 = User::find(2) // Company 2
>>> Auth::login($user1)
>>> Customer::count() // Should only show Company 1 customers
>>> Auth::login($user2)
>>> Customer::count() // Should only show Company 2 customers
```

**Step 3: Webhook Security Testing**
```bash
# Test all webhook endpoints with invalid signatures
# Test all webhook endpoints with valid signatures
# Verify monitoring and logging
```

**Step 4: Performance Testing**
```bash
# Run performance tests to ensure scopes don't slow queries
php artisan test:performance
```

### Rollout Strategy

**Stage 1: Development Environment** (1 day)
- Deploy all changes
- Run full test suite
- Manual testing

**Stage 2: Staging Environment** (2 days)
- Deploy to staging
- Run automated tests
- Security audit
- Performance testing

**Stage 3: Production Deployment** (1 day)
- Deploy during low-traffic window
- Monitor error rates
- Watch for authentication issues
- Verify multi-tenant isolation

**Stage 4: Post-Deployment Monitoring** (1 week)
- Monitor logs for scope errors
- Track authentication failures
- Verify webhook signature success rates
- Performance monitoring

### Rollback Plan

**If issues detected in production**:

1. **Immediate**: Revert CompanyScope change (A2)
```bash
git revert COMMIT_HASH
php artisan config:clear
php artisan route:clear
```

2. **Model-by-Model Rollback**: Remove BelongsToCompany trait from problematic models
```bash
# Edit specific model
# Remove BelongsToCompany trait
php artisan config:clear
```

3. **Full Rollback**: Revert entire Phase A
```bash
git revert COMMIT_RANGE
php artisan migrate:rollback
php artisan config:clear
php artisan route:clear
```

---

## SUMMARY & NEXT STEPS

### Phase A Deliverables

1. **45 Models Protected** with BelongsToCompany trait
2. **CompanyScope Fixed** - Admin no longer bypasses scope
3. **All Webhooks Secured** - Signature verification on all endpoints
4. **Service Discovery Validated** - Company ownership checked
5. **Comprehensive Test Suite** - Full security coverage
6. **Documentation** - Security policies and runbooks

### Estimated Timeline

| Task | Hours | Dependencies |
|------|-------|--------------|
| A1: Multi-Tenant Model Protection | 35 | None |
| A2: Fix CompanyScope Admin Bypass | 2 | None |
| A3: Webhook Authentication | 15 | None |
| A4: User Model Scoping | 0 | Covered in A1 |
| A5: Service Discovery Validation | 5 | A1.4 complete |
| Testing & Validation | 3 | All above |
| **TOTAL** | **60 hours** | |

### Success Criteria

✅ **Security**:
- Zero cross-company data leakage
- All webhooks require authentication
- All service lookups validate ownership

✅ **Quality**:
- 100% test coverage for multi-tenant isolation
- All tests passing
- Zero regression bugs

✅ **Performance**:
- Query performance impact <5%
- No authentication delays
- Webhook processing unchanged

### Next Phase Preview

**PHASE B: Authentication & Authorization Hardening**
- Enhanced API authentication
- Role-based access control
- Session security improvements
- Estimated: 25 hours

---

## APPENDIX

### Quick Reference Commands

```bash
# Check model trait usage
grep -rn "use BelongsToCompany" app/Models/

# Test multi-tenant isolation
php artisan tinker
>>> Customer::count() // Should show filtered results

# Test webhook security
curl -X POST http://localhost/api/webhook -d '{}' # Should fail

# Run security tests
php artisan test --filter Security

# Monitor webhook signatures
tail -f storage/logs/laravel.log | grep "Signature"
```

### File Change Inventory

**Models to Modify** (33 files):
- P0: 12 files
- P1: 15 files
- P2: 6 files

**Scope Files**:
- `/var/www/api-gateway/app/Scopes/CompanyScope.php` - 1 line change

**Route Files**:
- `/var/www/api-gateway/routes/api.php` - 3 line changes

**New Files Created**:
- Tests: 4 new test files
- Traits: 1 new helper trait
- Documentation: 4 new documentation files

**Total Files Modified**: ~40 files
**Total Lines Changed**: ~100 lines
**Risk**: MEDIUM (testing mitigates risk)

---

**END OF PHASE A IMPLEMENTATION PLAN**
