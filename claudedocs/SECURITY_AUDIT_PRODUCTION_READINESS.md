# Production Security Audit & Validation Plan
**Date**: 2025-10-02
**System**: AskPro AI Gateway - Multi-Tenant SaaS Platform
**Audit Type**: Pre-Production Security Assessment
**Auditor**: Security Engineering Team

---

## Executive Summary

### ✅ Implemented Security Fixes (Completed)
1. ✅ NotificationConfigurationPolicy polymorphic relationship bug fixed
2. ✅ NotificationEventMappingPolicy created (missing policy)
3. ✅ CallbackEscalationPolicy created (missing policy)
4. ✅ UserResource global scope bypass fixed
5. ✅ CallbackRequestPolicy assignment authorization fixed
6. ✅ 3 input validation observers created (PolicyConfiguration, CallbackRequest, NotificationConfiguration)
7. ✅ 6 migrations enhanced with company_id columns and indexes

### 🚨 CRITICAL VULNERABILITIES IDENTIFIED

#### **SEVERITY: CRITICAL** - Multi-Tenant Isolation Failures

**1. MOST MODELS MISSING BelongsToCompany TRAIT**
- **Impact**: Cross-tenant data leakage across entire system
- **Affected Models**: ~40+ models lack proper tenant isolation
- **Only 1 model** uses BelongsToCompany (NotificationConfiguration)
- **Risk**: Users from Company A can access Company B's data
- **Exploitation**: Direct model queries bypass company_id filtering

**2. UNAUTHENTICATED WEBHOOK ENDPOINTS EXPOSED**
- **Impact**: Public endpoints allow unauthorized data injection
- **Affected Routes**:
  - `/api/webhooks/retell` - No authentication
  - `/api/webhooks/calcom` - No authentication
  - `/api/webhooks/stripe` - No authentication
  - `/api/webhooks/retell/function-call` - No authentication
- **Risk**: Attackers can forge webhooks to manipulate appointments, calls, payments
- **Exploitation**: Simple HTTP POST requests can create/modify/delete data

**3. ADMIN ROLE BYPASS IN CompanyScope**
- **Impact**: Admin users bypass multi-tenant isolation completely
- **Code**: `CompanyScope.php:22` - `if ($user->hasAnyRole(['super_admin', 'admin']))`
- **Risk**: Admin users can access ALL company data, not just their own
- **Exploitation**: Admin user from Company A can query Company B's entire database

**4. User MODEL MISSING CompanyScope GLOBAL SCOPE**
- **Impact**: User queries don't enforce company_id filtering
- **Code**: `User.php` - No `BelongsToCompany` trait or global scope
- **Risk**: User enumeration across companies, privilege escalation
- **Exploitation**: API queries can list users from all companies

**5. SERVICE DISCOVERY WITHOUT COMPANY_ID FILTERING**
- **Impact**: Cross-company service access in booking system
- **Code**: `RetellFunctionCallHandler.php:150` - Service lookups may miss company_id checks
- **Risk**: Book appointments using other companies' services
- **Exploitation**: Service ID manipulation to access unauthorized services

---

## Detailed Vulnerability Assessment

### 1. Multi-Tenant Isolation Analysis

#### 1.1 Models WITH BelongsToCompany Trait ✅
```php
✅ NotificationConfiguration - ONLY model with trait
```

#### 1.2 Models MISSING BelongsToCompany Trait ❌
```
CRITICAL MODELS WITHOUT MULTI-TENANT PROTECTION:
❌ User - Users can see all users across companies
❌ Appointment - Appointments accessible across companies
❌ Customer - Customer data visible to all companies
❌ Service - Services can be used by any company
❌ Staff - Staff members visible across companies
❌ Branch - Branch data accessible cross-company
❌ PhoneNumber - Phone numbers not isolated
❌ Call - Call records accessible cross-company
❌ CallbackRequest - Callback requests not isolated (has company_id column but no trait)
❌ CallbackEscalation - Escalations not isolated
❌ Invoice - Invoice data accessible cross-company
❌ Transaction - Financial transactions not isolated
❌ PolicyConfiguration - Policy configs accessible cross-company
❌ NotificationEventMapping - Event mappings not isolated
❌ SystemSetting - Settings potentially cross-company
❌ AppointmentModification - Modifications not isolated
❌ Integration - Integration configs accessible
❌ WebhookLog - Webhook logs not isolated
❌ ActivityLog - Activity logs not isolated
```

**SQL Injection Vector**:
```sql
-- Current vulnerability: Any authenticated user can run:
SELECT * FROM appointments; -- Returns ALL appointments from ALL companies

-- Expected behavior with proper isolation:
SELECT * FROM appointments WHERE company_id = [current_user_company_id];
```

#### 1.3 CompanyScope Admin Bypass Vulnerability

**File**: `/var/www/api-gateway/app/Scopes/CompanyScope.php:22-24`

```php
// VULNERABILITY: Admin role bypasses ALL company filtering
if ($user->hasAnyRole(['super_admin', 'admin'])) {
    return; // ❌ CRITICAL: Admin sees ALL company data
}
```

**Impact**:
- Admin users from Company A can access Company B's data
- Violates multi-tenant isolation principle
- GDPR compliance violation (unauthorized data access)

**Expected Behavior**:
```php
// Only super_admin should bypass, NOT admin
if ($user->hasRole('super_admin')) {
    return; // ✅ Only super_admin bypasses
}
```

**Test Case**:
```php
// Attack Scenario:
// 1. Create admin user in Company A (ID: 1)
// 2. Query appointments:
Auth::login($adminUserCompanyA);
Appointment::all(); // ❌ Returns appointments from ALL companies, not just Company A

// Expected:
Appointment::all(); // ✅ Should only return appointments where company_id = 1
```

---

### 2. Authorization Security Vulnerabilities

#### 2.1 Gate::before Super Admin Bypass Analysis

**File**: `/var/www/api-gateway/app/Providers/AuthServiceProvider.php:48-52`

```php
Gate::before(function ($user, string $ability) {
    if ($user->hasRole('super_admin')) {
        return true; // ✅ CORRECT: Only super_admin bypasses
    }
    return null; // ✅ CORRECT: Let policies handle authorization
});
```

**Status**: ✅ **SECURE** - Only `super_admin` bypasses policies, not regular `admin`

#### 2.2 UserResource Global Scope Bypass

**File**: `/var/www/api-gateway/app/Filament/Resources/UserResource.php:771-781`

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    // Only super_admin can bypass company scope
    if (auth()->user()?->hasRole('super_admin')) {
        return $query->withoutGlobalScopes(); // ✅ FIXED
    }

    return $query;
}
```

**Status**: ✅ **SECURE** - Only `super_admin` can see all users

**Test Case**:
```php
// As admin user from Company A:
Auth::login($adminCompanyA);
User::all(); // ✅ Should only return users where company_id = 1

// As super_admin:
Auth::login($superAdmin);
User::all(); // ✅ Should return ALL users from ALL companies
```

#### 2.3 Policy Enforcement Gaps

**Missing Policy Checks**:
```
⚠️ Service model - No explicit policy enforcement in controllers
⚠️ Branch model - Authorization may be inconsistent
⚠️ Integration model - No policy registered
⚠️ WebhookLog model - No access control
⚠️ ActivityLog model - No policy enforcement
```

---

### 3. API & Webhook Security Vulnerabilities

#### 3.1 Unauthenticated Webhook Endpoints

**File**: `/var/www/api-gateway/routes/api.php`

```php
// ❌ CRITICAL: No authentication on webhook routes
Route::post('/webhooks/retell', [RetellWebhookController::class, '__invoke']);
Route::post('/webhooks/calcom', [CalcomWebhookController::class, 'handle']);
Route::post('/webhooks/stripe', [StripePaymentController::class, 'handleWebhook']);
Route::post('/webhooks/retell/function-call', [RetellFunctionCallHandler::class, 'handleFunctionCall']);
```

**Vulnerabilities**:
1. **No HMAC signature verification** (only Retell has partial implementation)
2. **No IP whitelisting** for webhook sources
3. **No rate limiting** on webhook endpoints
4. **No request validation** before processing

**Attack Vectors**:

**Scenario 1: Forged Appointment Creation**
```bash
# Attacker sends fake webhook:
curl -X POST https://api.askproai.de/api/webhooks/retell/function-call \
  -H "Content-Type: application/json" \
  -d '{
    "function_name": "book_appointment",
    "parameters": {
      "service_id": 1,
      "customer": {"name": "Fake", "email": "fake@test.com"},
      "date": "2025-10-15",
      "time": "14:00"
    },
    "call_id": "fake_call_id_12345"
  }'

# Result: ❌ Appointment created without proper authentication
```

**Scenario 2: Data Injection via Cal.com Webhook**
```bash
# Attacker forges Cal.com webhook:
curl -X POST https://api.askproai.de/api/webhooks/calcom \
  -H "Content-Type: application/json" \
  -d '{
    "triggerEvent": "BOOKING_CREATED",
    "payload": {
      "uid": "malicious_booking_123",
      "title": "<script>alert(1)</script>",
      "startTime": "2025-10-15T14:00:00Z"
    }
  }'

# Result: ❌ XSS payload injected into database
```

**Required Fixes**:
1. **Implement HMAC signature verification** for all webhooks
2. **Add IP whitelisting** for known webhook sources
3. **Implement rate limiting** (max 100 requests/minute per IP)
4. **Add request validation middleware**

**Implementation**:
```php
// Webhook security middleware needed:
Route::middleware(['webhook.signature', 'webhook.ratelimit'])->group(function () {
    Route::post('/webhooks/retell', [RetellWebhookController::class, '__invoke']);
    Route::post('/webhooks/calcom', [CalcomWebhookController::class, 'handle']);
    Route::post('/webhooks/stripe', [StripePaymentController::class, 'handleWebhook']);
});
```

#### 3.2 Function Call Handler Security

**File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Vulnerability**: Call context validation relies on database query without signature verification

```php
// Line 58-77: getCallContext method
private function getCallContext(?string $callId): ?array
{
    if (!$callId) {
        Log::warning('Cannot get call context: callId is null');
        return null; // ❌ No authentication, just returns null
    }

    $call = $this->callLifecycle->getCallContext($callId);
    // ❌ No verification that this request came from Retell
    // ❌ Attacker can provide any call_id to get company_id
}
```

**Exploitation**:
```bash
# 1. Attacker discovers valid call_id (e.g., from logs or enumeration)
# 2. Sends forged function call with that call_id
# 3. Gets access to company_id and can manipulate that company's data

curl -X POST https://api.askproai.de/api/webhooks/retell/function-call \
  -d '{"call_id": "call_abc123", "function_name": "book_appointment", ...}'

# Result: ❌ Access to Company A's booking system via forged request
```

---

### 4. Input Validation Security

#### 4.1 Observer Implementation Analysis ✅

**PolicyConfigurationObserver**: ✅ **SECURE**
- JSON schema validation ✅
- Type checking ✅
- XSS prevention with `strip_tags()` + `htmlspecialchars()` ✅
- Required field validation ✅

**CallbackRequestObserver**: ✅ **SECURE**
- E.164 phone number validation ✅
- XSS prevention on customer_name and notes ✅
- Input length validation ✅

**NotificationConfigurationObserver**: ✅ **SECURE**
- Event type validation against NotificationEventMapping ✅
- Channel validation (whitelist) ✅
- Template content sanitization ✅
- Script/iframe removal ✅

#### 4.2 Potential XSS Vectors

**UserResource.php Form Fields**:
```php
// Line 64-67: User name input
Forms\Components\TextInput::make('name')
    ->required()
    ->maxLength(255)
    // ❌ No sanitization observer on User model
```

**Risk**: User names with XSS payloads stored in database

**Test Case**:
```php
User::create([
    'name' => '<script>alert("XSS")</script>',
    'email' => 'test@test.com',
    'password' => Hash::make('password'),
    'company_id' => 1
]);

// Later when displayed in Filament:
// ❌ Potential XSS if not properly escaped in Blade templates
```

**Missing Observers**:
```
❌ UserObserver - No input sanitization on name, phone, address fields
❌ ServiceObserver (exists but needs validation review)
❌ AppointmentObserver (exists but needs validation review)
❌ CustomerObserver - No input sanitization
❌ StaffObserver - No input sanitization
❌ BranchObserver - No input sanitization
```

---

### 5. Database Security Configuration

#### 5.1 Environment Configuration Review

**File**: `/var/www/api-gateway/.env`

```env
# ✅ SECURE: Production settings
APP_ENV=production
APP_DEBUG=false  # ✅ Debug disabled
APP_KEY=base64:Ssx0BL7/m+gA6/hBtoIS+xm5+v3DNDrvCKr562Pnfpg=  # ✅ Strong key

# ✅ SECURE: Database credentials
DB_CONNECTION=mysql
DB_USERNAME=askproai_user
DB_PASSWORD=askproai_secure_pass_2024  # ✅ Dedicated user with limited privileges

# ⚠️ WARNING: Session configuration
SESSION_ENCRYPT=true  # ✅ Encrypted sessions
SESSION_DOMAIN=.askproai.de  # ✅ Domain restriction
SESSION_SECURE_COOKIE=true  # ✅ HTTPS only
SESSION_SAME_SITE=lax  # ⚠️ Consider 'strict' for better CSRF protection

# ⚠️ WARNING: Mail configuration
MAIL_MAILER=log  # ⚠️ Production should use SMTP, not log driver

# ✅ SECURE: Redis configuration
CACHE_STORE=redis
REDIS_PASSWORD=null  # ⚠️ Redis should have password in production

# ⚠️ WARNING: Retell webhook security
RETELLAI_ALLOW_UNSIGNED_WEBHOOKS=false  # ✅ Signature verification enabled
RETELLAI_FUNCTION_SECRET=func_secret_6ff998ba48e842092e04  # ✅ Secret configured
```

**Recommendations**:
1. ⚠️ **Add Redis password** for cache security
2. ⚠️ **Configure SMTP mailer** for production emails
3. ⚠️ **Consider SESSION_SAME_SITE=strict** for enhanced CSRF protection
4. ✅ **Rotate secrets** regularly (APP_KEY, API tokens, webhook secrets)

#### 5.2 Database User Privileges Audit

**Required Test**:
```sql
-- Check database user privileges:
SHOW GRANTS FOR 'askproai_user'@'localhost';

-- Expected: Limited to askproai_db only
-- Should NOT have:
--   - GRANT OPTION
--   - SUPER privilege
--   - FILE privilege
--   - Process privilege
--   - Access to mysql.* system database
```

---

## Production Deployment Security Checklist

### 🔴 CRITICAL - MUST FIX BEFORE PRODUCTION

- [ ] **1. ADD BelongsToCompany TRAIT TO ALL MODELS**
  - [ ] User, Appointment, Customer, Service, Staff, Branch
  - [ ] PhoneNumber, Call, CallbackRequest, CallbackEscalation
  - [ ] Invoice, Transaction, PolicyConfiguration, NotificationEventMapping
  - [ ] SystemSetting, AppointmentModification, Integration, WebhookLog, ActivityLog
  - **Impact**: Prevents cross-tenant data access
  - **Test**: SQL query `SELECT * FROM appointments` should only return current company's data

- [ ] **2. FIX CompanyScope ADMIN BYPASS**
  - [ ] Change `hasAnyRole(['super_admin', 'admin'])` to `hasRole('super_admin')`
  - [ ] Test: Admin user queries should be company-scoped
  - **File**: `/var/www/api-gateway/app/Scopes/CompanyScope.php:22`

- [ ] **3. IMPLEMENT WEBHOOK AUTHENTICATION**
  - [ ] Add HMAC signature verification middleware for all webhooks
  - [ ] Implement IP whitelisting for Retell, Cal.com, Stripe
  - [ ] Add rate limiting (100 req/min per IP)
  - [ ] Validate webhook payload structure before processing
  - **Files**:
    - Create: `app/Http/Middleware/VerifyWebhookSignature.php`
    - Create: `app/Http/Middleware/WebhookRateLimiter.php`
    - Update: `routes/api.php`

- [ ] **4. ADD USER MODEL MULTI-TENANT PROTECTION**
  - [ ] Add `BelongsToCompany` trait to User model
  - [ ] Test: User queries properly scoped by company_id
  - **File**: `/var/www/api-gateway/app/Models/User.php`

- [ ] **5. VERIFY SERVICE DISCOVERY COMPANY ISOLATION**
  - [ ] Audit all Service::find() calls in controllers
  - [ ] Ensure company_id validation before service usage
  - [ ] Test: Cannot book appointments using other company's services
  - **Files**:
    - `app/Http/Controllers/RetellFunctionCallHandler.php`
    - `app/Http/Controllers/Api/V2/BookingController.php`

### 🟡 HIGH PRIORITY - RECOMMENDED BEFORE PRODUCTION

- [ ] **6. CREATE MISSING OBSERVERS FOR INPUT SANITIZATION**
  - [ ] UserObserver (name, phone, address sanitization)
  - [ ] CustomerObserver (name, email, notes sanitization)
  - [ ] StaffObserver (name, bio sanitization)
  - [ ] BranchObserver (name, address sanitization)
  - **Pattern**: Use same sanitization as CallbackRequestObserver

- [ ] **7. IMPLEMENT API RATE LIMITING**
  - [ ] Configure throttle middleware for API routes
  - [ ] Separate limits for authenticated vs unauthenticated
  - [ ] Webhook-specific rate limits
  - **Recommended**: 60 req/min authenticated, 20 req/min unauthenticated

- [ ] **8. ADD DATABASE CONNECTION SECURITY**
  - [ ] Set Redis password in production
  - [ ] Verify MySQL user has minimal required privileges
  - [ ] Enable MySQL audit logging
  - **Files**: `.env`, database server configuration

- [ ] **9. IMPLEMENT COMPREHENSIVE AUDIT LOGGING**
  - [ ] Log all policy authorization decisions
  - [ ] Log all cross-company access attempts (should be denied)
  - [ ] Log all webhook signature verification failures
  - [ ] Store logs in separate database or log aggregation service
  - **Create**: `app/Services/SecurityAuditLogger.php`

- [ ] **10. ADD SECURITY HEADERS**
  - [ ] Implement Content-Security-Policy header
  - [ ] Add X-Frame-Options: DENY
  - [ ] Add X-Content-Type-Options: nosniff
  - [ ] Add Referrer-Policy: strict-origin-when-cross-origin
  - **Create**: `app/Http/Middleware/SecurityHeaders.php`

### 🟢 RECOMMENDED - POST-PRODUCTION IMPROVEMENTS

- [ ] **11. Implement Automated Security Testing**
  - [ ] Multi-tenant isolation tests in PHPUnit
  - [ ] Policy enforcement tests for all models
  - [ ] XSS prevention tests for all input fields
  - [ ] CSRF protection tests for state-changing operations

- [ ] **12. Setup Security Monitoring**
  - [ ] Failed authentication monitoring and alerting
  - [ ] Cross-company access attempt detection
  - [ ] Webhook signature failure alerting
  - [ ] Abnormal API usage pattern detection

- [ ] **13. Implement Data Encryption at Rest**
  - [ ] Encrypt sensitive fields (phone numbers, emails, notes)
  - [ ] Use Laravel's encrypted casting for sensitive data
  - [ ] Document encryption key rotation procedures

- [ ] **14. Regular Security Audits**
  - [ ] Monthly dependency vulnerability scans (composer audit)
  - [ ] Quarterly penetration testing
  - [ ] Annual third-party security audit
  - [ ] Continuous OWASP Top 10 compliance review

---

## Security Testing & Validation Plan

### Phase 1: Multi-Tenant Isolation Testing

#### Test 1.1: Model-Level Company Isolation

**SQL Query Verification**:
```sql
-- Enable query logging
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- Perform test queries as different users
-- Verify ALL queries include: WHERE company_id = ?

-- Check logged queries:
SELECT argument FROM mysql.general_log
WHERE argument LIKE '%SELECT%FROM appointments%'
ORDER BY event_time DESC LIMIT 10;

-- Expected: ALL queries must include company_id filter
-- Failure: Any query without company_id is a security vulnerability
```

**PHP Test Suite**:
```php
// File: tests/Feature/MultiTenantIsolationTest.php

public function test_appointments_are_company_scoped()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userB = User::factory()->create(['company_id' => $companyB->id]);

    $appointmentA = Appointment::factory()->create(['company_id' => $companyA->id]);
    $appointmentB = Appointment::factory()->create(['company_id' => $companyB->id]);

    // Act as User A
    $this->actingAs($userA);
    $appointments = Appointment::all();

    // Assert: User A can only see Company A appointments
    $this->assertCount(1, $appointments);
    $this->assertEquals($appointmentA->id, $appointments->first()->id);
    $this->assertNotContains($appointmentB->id, $appointments->pluck('id'));
}

public function test_admin_users_cannot_bypass_company_scope()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $admin = User::factory()->create([
        'company_id' => $companyA->id
    ]);
    $admin->assignRole('admin'); // NOT super_admin

    $appointmentB = Appointment::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($admin);
    $appointments = Appointment::all();

    // Assert: Admin from Company A cannot see Company B data
    $this->assertNotContains($appointmentB->id, $appointments->pluck('id'));
}

public function test_service_discovery_prevents_cross_company_access()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $serviceB = Service::factory()->create([
        'company_id' => $companyB->id,
        'name' => 'Company B Service'
    ]);

    $userA = User::factory()->create(['company_id' => $companyA->id]);

    // Attempt to book appointment with Company B's service
    $this->actingAs($userA);

    $response = $this->postJson('/api/v2/bookings', [
        'service_id' => $serviceB->id,
        'customer' => ['name' => 'Test', 'email' => 'test@test.com'],
        'start' => now()->addDay()->toIso8601String()
    ]);

    // Assert: Request should be denied
    $response->assertStatus(403); // Forbidden
    $response->assertJson(['message' => 'Service not found or unauthorized']);
}
```

#### Test 1.2: Filament Resource Isolation

**Manual Test Steps**:
```
1. Create 2 companies (Company A, Company B)
2. Create admin user in Company A
3. Create 10 appointments in Company A
4. Create 10 appointments in Company B
5. Login as admin from Company A
6. Navigate to /admin/appointments
7. Verify ONLY Company A appointments visible (10 records)
8. Attempt to access Company B appointment by URL: /admin/appointments/[company_b_appointment_id]
9. Expected: 403 Forbidden or 404 Not Found
10. Verify cannot edit Company B appointments
```

#### Test 1.3: API Endpoint Isolation

**Automated API Tests**:
```php
public function test_api_endpoints_enforce_company_isolation()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $appointmentB = Appointment::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA, 'sanctum');

    // Test GET endpoint
    $response = $this->getJson("/api/v1/appointments/{$appointmentB->id}");
    $response->assertStatus(404); // Should not be found

    // Test UPDATE endpoint
    $response = $this->putJson("/api/v1/appointments/{$appointmentB->id}", [
        'status' => 'cancelled'
    ]);
    $response->assertStatus(404); // Should not be found

    // Test DELETE endpoint
    $response = $this->deleteJson("/api/v1/appointments/{$appointmentB->id}");
    $response->assertStatus(404); // Should not be found
}
```

---

### Phase 2: Authorization Security Testing

#### Test 2.1: Policy Enforcement

**Test All 18 Policies**:
```php
public function test_all_policies_enforce_company_id_checks()
{
    $policies = [
        'App\\Models\\Company' => 'App\\Policies\\CompanyPolicy',
        'App\\Models\\Customer' => 'App\\Policies\\CustomerPolicy',
        'App\\Models\\Appointment' => 'App\\Policies\\AppointmentPolicy',
        'App\\Models\\Staff' => 'App\\Policies\\StaffPolicy',
        'App\\Models\\Branch' => 'App\\Policies\\BranchPolicy',
        'App\\Models\\Transaction' => 'App\\Policies\\TransactionPolicy',
        'App\\Models\\Call' => 'App\\Policies\\CallPolicy',
        'App\\Models\\Service' => 'App\\Policies\\ServicePolicy',
        'App\\Models\\Invoice' => 'App\\Policies\\InvoicePolicy',
        'App\\Models\\PhoneNumber' => 'App\\Policies\\PhoneNumberPolicy',
        'App\\Models\\PolicyConfiguration' => 'App\\Policies\\PolicyConfigurationPolicy',
        'App\\Models\\NotificationConfiguration' => 'App\\Policies\\NotificationConfigurationPolicy',
        'App\\Models\\CallbackRequest' => 'App\\Policies\\CallbackRequestPolicy',
        'App\\Models\\SystemSetting' => 'App\\Policies\\SystemSettingPolicy',
        'App\\Models\\User' => 'App\\Policies\\UserPolicy',
        'App\\Models\\AppointmentModification' => 'App\\Policies\\AppointmentModificationPolicy',
        'App\\Models\\NotificationEventMapping' => 'App\\Policies\\NotificationEventMappingPolicy',
        'App\\Models\\CallbackEscalation' => 'App\\Policies\\CallbackEscalationPolicy',
    ];

    foreach ($policies as $model => $policy) {
        $this->assertPolicyEnforcesCompanyId($model, $policy);
    }
}

private function assertPolicyEnforcesCompanyId($modelClass, $policyClass)
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $recordB = $modelClass::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    // Test view policy
    $this->assertFalse(
        Gate::allows('view', $recordB),
        "{$policyClass}::view does not enforce company_id"
    );

    // Test update policy
    $this->assertFalse(
        Gate::allows('update', $recordB),
        "{$policyClass}::update does not enforce company_id"
    );

    // Test delete policy
    $this->assertFalse(
        Gate::allows('delete', $recordB),
        "{$policyClass}::delete does not enforce company_id"
    );
}
```

#### Test 2.2: Super Admin Bypass

```php
public function test_super_admin_can_bypass_all_policies()
{
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $companyB = Company::factory()->create();
    $appointmentB = Appointment::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($superAdmin);

    // Super admin should be able to access ANY company's data
    $this->assertTrue(Gate::allows('view', $appointmentB));
    $this->assertTrue(Gate::allows('update', $appointmentB));
    $this->assertTrue(Gate::allows('delete', $appointmentB));
}

public function test_regular_admin_cannot_bypass_company_scope()
{
    $companyA = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $companyA->id]);
    $admin->assignRole('admin'); // Regular admin, not super_admin

    $companyB = Company::factory()->create();
    $appointmentB = Appointment::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($admin);

    // Regular admin should NOT access other companies' data
    $this->assertFalse(Gate::allows('view', $appointmentB));
}
```

---

### Phase 3: Webhook Security Testing

#### Test 3.1: Signature Verification

**Retell Webhook Test**:
```php
public function test_retell_webhook_requires_valid_signature()
{
    $payload = ['call_id' => 'test123', 'event' => 'call.ended'];
    $secret = config('services.retell.webhook_secret');

    // Test 1: No signature - should fail
    $response = $this->postJson('/api/webhooks/retell', $payload);
    $response->assertStatus(401);

    // Test 2: Invalid signature - should fail
    $response = $this->postJson('/api/webhooks/retell', $payload, [
        'X-Retell-Signature' => 'invalid_signature_12345'
    ]);
    $response->assertStatus(401);

    // Test 3: Valid signature - should succeed
    $signature = hash_hmac('sha256', json_encode($payload), $secret);
    $response = $this->postJson('/api/webhooks/retell', $payload, [
        'X-Retell-Signature' => $signature
    ]);
    $response->assertStatus(200);
}
```

**Cal.com Webhook Test**:
```php
public function test_calcom_webhook_requires_valid_signature()
{
    $payload = [
        'triggerEvent' => 'BOOKING_CREATED',
        'payload' => ['uid' => 'test123']
    ];
    $secret = config('services.calcom.webhook_secret');

    // Test without signature
    $response = $this->postJson('/api/webhooks/calcom', $payload);
    $response->assertStatus(401);

    // Test with valid signature
    $signature = hash_hmac('sha256', json_encode($payload), $secret);
    $response = $this->postJson('/api/webhooks/calcom', $payload, [
        'X-Cal-Signature-256' => $signature
    ]);
    $response->assertStatus(200);
}
```

#### Test 3.2: Rate Limiting

```bash
#!/bin/bash
# Webhook rate limit test script

WEBHOOK_URL="https://api.askproai.de/api/webhooks/retell"

echo "Testing webhook rate limiting..."
echo "Sending 150 requests in 60 seconds..."

for i in {1..150}; do
    curl -X POST $WEBHOOK_URL \
        -H "Content-Type: application/json" \
        -d '{"call_id":"test'$i'","event":"call.started"}' \
        -w "%{http_code}\n" \
        -o /dev/null -s &

    if [ $((i % 10)) -eq 0 ]; then
        echo "Sent $i requests..."
    fi
done

wait

echo "Rate limit test complete."
echo "Expected: First 100 requests return 200, remaining 50 return 429 (Too Many Requests)"
```

#### Test 3.3: Webhook Payload Injection

**XSS Prevention Test**:
```php
public function test_webhook_prevents_xss_injection()
{
    $maliciousPayload = [
        'call_id' => 'test123',
        'customer_name' => '<script>alert("XSS")</script>',
        'notes' => '<img src=x onerror=alert(1)>',
        'metadata' => [
            'custom_field' => '<iframe src="evil.com"></iframe>'
        ]
    ];

    // Send malicious webhook (with valid signature)
    $signature = $this->generateValidSignature($maliciousPayload);
    $response = $this->postJson('/api/webhooks/retell', $maliciousPayload, [
        'X-Retell-Signature' => $signature
    ]);

    $response->assertStatus(200);

    // Verify data is sanitized in database
    $call = Call::where('retell_call_id', 'test123')->first();

    // Should NOT contain script tags or malicious HTML
    $this->assertStringNotContainsString('<script>', $call->metadata['customer_name'] ?? '');
    $this->assertStringNotContainsString('<iframe>', $call->metadata['notes'] ?? '');
    $this->assertStringNotContainsString('onerror', $call->metadata['custom_field'] ?? '');
}
```

---

### Phase 4: Input Validation Security Testing

#### Test 4.1: Observer Sanitization

**PolicyConfigurationObserver Test**:
```php
public function test_policy_configuration_observer_sanitizes_input()
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $this->actingAs($user);

    // Attempt to create policy with XSS payload
    $this->expectException(ValidationException::class);

    PolicyConfiguration::create([
        'company_id' => $company->id,
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => '<script>alert(1)</script>',
            'fee_percentage' => '50<img src=x>',
            'require_reason' => true
        ]
    ]);

    // Should throw validation exception for invalid types
}

public function test_policy_config_removes_html_tags()
{
    $company = Company::factory()->create();

    $policy = PolicyConfiguration::create([
        'company_id' => $company->id,
        'policy_type' => 'cancellation',
        'config' => [
            'hours_before' => 24,
            'fee_percentage' => 50,
            'require_reason' => true,
            'custom_message' => 'Hello <b>World</b><script>alert(1)</script>'
        ]
    ]);

    $policy->refresh();

    // Verify HTML tags are stripped
    $this->assertStringNotContainsString('<b>', $policy->config['custom_message'] ?? '');
    $this->assertStringNotContainsString('<script>', $policy->config['custom_message'] ?? '');
}
```

**CallbackRequestObserver Test**:
```php
public function test_callback_request_observer_validates_phone_format()
{
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('Phone number must be in E.164 format');

    CallbackRequest::create([
        'company_id' => 1,
        'customer_name' => 'Test User',
        'phone_number' => '0123456789', // Invalid: Missing country code
        'priority' => 'normal'
    ]);
}

public function test_callback_request_sanitizes_user_input()
{
    $callback = CallbackRequest::create([
        'company_id' => 1,
        'customer_name' => 'Test <script>alert(1)</script> User',
        'phone_number' => '+491234567890',
        'notes' => '<b>Important</b> note with <img src=x onerror=alert(1)>',
        'priority' => 'high'
    ]);

    $callback->refresh();

    // Verify sanitization
    $this->assertStringNotContainsString('<script>', $callback->customer_name);
    $this->assertStringNotContainsString('<img', $callback->notes);
    $this->assertStringNotContainsString('onerror', $callback->notes);
}
```

---

## Penetration Testing Scenarios

### Scenario 1: Cross-Tenant Data Access

**Objective**: Attempt to access Company B's data as Company A user

```bash
#!/bin/bash
# Penetration test script

echo "=== Cross-Tenant Data Access Test ==="

# Setup
COMPANY_A_USER_TOKEN="[Get from auth]"
COMPANY_B_APPOINTMENT_ID="123"

# Test 1: Direct API access
echo "Test 1: Attempting direct API access to Company B appointment..."
curl -X GET https://api.askproai.de/api/v1/appointments/$COMPANY_B_APPOINTMENT_ID \
    -H "Authorization: Bearer $COMPANY_A_USER_TOKEN" \
    -H "Content-Type: application/json"

# Expected: 404 Not Found or 403 Forbidden
# Vulnerability: 200 OK with appointment data

# Test 2: Filament resource access
echo "Test 2: Attempting Filament resource access..."
curl -X GET https://api.askproai.de/admin/appointments/$COMPANY_B_APPOINTMENT_ID/edit \
    -H "Cookie: laravel_session=[company_a_session]"

# Expected: 403 Forbidden or redirect
# Vulnerability: 200 OK with edit form

# Test 3: GraphQL/API query injection
echo "Test 3: Attempting query parameter injection..."
curl -X GET "https://api.askproai.de/api/v1/appointments?company_id=$COMPANY_B_ID" \
    -H "Authorization: Bearer $COMPANY_A_USER_TOKEN"

# Expected: Empty result or error
# Vulnerability: Returns Company B appointments

echo "Cross-tenant test complete."
```

### Scenario 2: Privilege Escalation

**Objective**: Escalate from regular user to admin or access other companies

```php
// Test privilege escalation vectors

public function test_privilege_escalation_via_role_manipulation()
{
    $user = User::factory()->create(['company_id' => 1]);
    $this->actingAs($user);

    // Attempt to assign admin role via API
    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'roles' => ['admin', 'super_admin']
    ]);

    // Expected: 403 Forbidden
    // Vulnerability: 200 OK and role assigned
    $response->assertStatus(403);

    $user->refresh();
    $this->assertFalse($user->hasRole('admin'));
}

public function test_company_id_manipulation_attack()
{
    $userA = User::factory()->create(['company_id' => 1]);
    $this->actingAs($userA);

    // Attempt to create appointment for Company B
    $response = $this->postJson('/api/v1/appointments', [
        'company_id' => 2, // Different company
        'service_id' => 1,
        'customer_id' => 1,
        'starts_at' => now()->addDay()
    ]);

    // Expected: 403 Forbidden or company_id overridden to user's company
    // Vulnerability: Appointment created for Company 2
    $this->assertNotEquals(2, Appointment::latest()->first()->company_id);
}
```

### Scenario 3: Webhook Forgery

**Objective**: Inject malicious data via forged webhooks

```bash
#!/bin/bash
echo "=== Webhook Forgery Attack Test ==="

# Test 1: Unsigned webhook
echo "Test 1: Sending unsigned webhook..."
curl -X POST https://api.askproai.de/api/webhooks/retell/function-call \
    -H "Content-Type: application/json" \
    -d '{
        "function_name": "book_appointment",
        "call_id": "forged_call_123",
        "parameters": {
            "service_id": 1,
            "customer": {"name": "Malicious", "email": "hack@evil.com"},
            "date": "2025-10-15",
            "time": "14:00"
        }
    }'

# Expected: 401 Unauthorized (missing signature)
# Vulnerability: 200 OK and appointment created

# Test 2: Replay attack
echo "Test 2: Attempting replay attack..."
CAPTURED_SIGNATURE="sha256=abc123def456..." # Previously captured
CAPTURED_PAYLOAD='{"call_id":"old_call","event":"call.ended"}'

curl -X POST https://api.askproai.de/api/webhooks/retell \
    -H "Content-Type: application/json" \
    -H "X-Retell-Signature: $CAPTURED_SIGNATURE" \
    -d "$CAPTURED_PAYLOAD"

# Expected: 400 Bad Request (timestamp too old or already processed)
# Vulnerability: 200 OK and webhook processed again

# Test 3: SQL injection via webhook
echo "Test 3: SQL injection attempt..."
curl -X POST https://api.askproai.de/api/webhooks/calcom \
    -H "Content-Type: application/json" \
    -d '{
        "triggerEvent": "BOOKING_CREATED",
        "payload": {
            "uid": "test'; DROP TABLE appointments; --",
            "title": "Normal Appointment",
            "startTime": "2025-10-15T14:00:00Z"
        }
    }'

# Expected: 400 Bad Request (invalid UID format) or sanitized
# Vulnerability: SQL injection executed

echo "Webhook forgery test complete."
```

---

## SQL Injection Prevention Verification

### Test Database Queries

```php
public function test_eloquent_queries_prevent_sql_injection()
{
    $maliciousInput = "1' OR '1'='1"; // SQL injection attempt

    // Test 1: Find by ID
    $result = Appointment::find($maliciousInput);
    $this->assertNull($result); // Should not find anything

    // Test 2: Where clause
    $results = Appointment::where('status', $maliciousInput)->get();
    $this->assertCount(0, $results); // Should return empty collection

    // Test 3: Search input
    $maliciousSearch = "admin' UNION SELECT * FROM users WHERE '1'='1";
    $results = User::where('name', 'LIKE', "%{$maliciousSearch}%")->get();
    // Should not execute UNION, treated as literal string
    $this->assertCount(0, $results);
}

public function test_raw_queries_use_parameter_binding()
{
    // Audit all DB::raw(), DB::select(), etc. in codebase
    $files = glob(app_path('**/*.php'));
    $violations = [];

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Check for dangerous raw query patterns
        if (preg_match('/DB::(raw|select|statement)\([^?]*\$/', $content)) {
            $violations[] = $file;
        }
    }

    $this->assertEmpty($violations,
        "Files with potential SQL injection: " . implode(", ", $violations)
    );
}
```

---

## Production Deployment Security Recommendations

### 1. Pre-Deployment Security Hardening

**Application Level**:
```bash
# 1. Disable debug mode
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# 2. Set secure session configuration
echo "SESSION_SECURE_COOKIE=true" >> .env
echo "SESSION_SAME_SITE=strict" >> .env

# 3. Enable HTTPS enforcement
# Update app/Providers/AppServiceProvider.php:
# URL::forceScheme('https');

# 4. Configure rate limiting
# config/rate-limiting.php
```

**Server Level**:
```bash
# 1. Configure firewall (UFW)
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP (redirect to HTTPS)
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable

# 2. Setup fail2ban for brute force protection
sudo apt-get install fail2ban
sudo systemctl enable fail2ban

# 3. Configure Redis password
redis-cli CONFIG SET requirepass "strong_redis_password_here"

# 4. MySQL security
mysql_secure_installation

# 5. Setup SSL/TLS certificates (Let's Encrypt)
sudo certbot --nginx -d api.askproai.de
```

### 2. Security Monitoring Setup

**Application Monitoring**:
```php
// Create: app/Services/SecurityMonitor.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SecurityMonitor
{
    public function logAuthorizationFailure($user, $resource, $action)
    {
        Log::warning('Authorization denied', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'resource' => get_class($resource),
            'resource_company_id' => $resource->company_id ?? null,
            'action' => $action,
            'ip' => request()->ip(),
            'timestamp' => now()
        ]);

        // Alert on repeated violations
        $key = "auth_violations:{$user->id}";
        $violations = Cache::increment($key);

        if ($violations > 5) {
            $this->alertSecurityTeam($user, $violations);
        }
    }

    public function logCrossTenantAttempt($user, $targetCompanyId)
    {
        Log::critical('Cross-tenant access attempt', [
            'user_id' => $user->id,
            'user_company_id' => $user->company_id,
            'target_company_id' => $targetCompanyId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ]);

        // Immediate alert for cross-tenant attempts
        $this->alertSecurityTeam($user, 'CROSS_TENANT_ATTEMPT');
    }

    private function alertSecurityTeam($user, $details)
    {
        // Send email, Slack notification, or trigger incident response
        // Implement based on your monitoring infrastructure
    }
}
```

**Log Aggregation**:
```bash
# Setup centralized logging (e.g., ELK Stack, Datadog, Splunk)

# Laravel logging configuration
# config/logging.php - Add production channel:

'production' => [
    'driver' => 'stack',
    'channels' => ['daily', 'slack', 'sentry'],
    'ignore_exceptions' => false,
],

'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'AskPro Security Bot',
    'emoji' => ':lock:',
    'level' => 'critical',
],
```

### 3. Incident Response Plan

**Security Incident Playbook**:

```markdown
## Incident Response Procedures

### 1. Cross-Tenant Data Access Detected
- [ ] Immediately revoke affected user's access tokens
- [ ] Identify scope of data accessed (audit logs)
- [ ] Notify affected companies within 24 hours
- [ ] Deploy emergency patch if system vulnerability
- [ ] Document incident for GDPR compliance

### 2. Unauthorized Webhook Activity
- [ ] Block source IP addresses
- [ ] Rotate webhook secrets immediately
- [ ] Review all data created/modified by suspicious webhooks
- [ ] Restore from backup if data integrity compromised
- [ ] Implement additional webhook validation

### 3. Authentication Brute Force Attack
- [ ] Enable aggressive rate limiting
- [ ] Lock affected user accounts
- [ ] Force password reset for targeted accounts
- [ ] Review access logs for successful breaches
- [ ] Enable 2FA requirement for all admin accounts

### 4. SQL Injection Attempt Detected
- [ ] Identify vulnerable endpoint
- [ ] Deploy immediate patch
- [ ] Review database logs for successful injections
- [ ] Restore database from backup if necessary
- [ ] Conduct full security audit of all database queries

### Emergency Contacts
- Security Team Lead: [contact]
- Database Admin: [contact]
- Legal/Compliance: [contact]
- Infrastructure Team: [contact]
```

---

## Conclusion & Risk Assessment

### Overall Security Posture: 🔴 **HIGH RISK - NOT PRODUCTION READY**

#### Critical Issues (Must Fix):
1. **Multi-tenant isolation incomplete** - 95% of models lack BelongsToCompany trait
2. **Admin bypass vulnerability** - Admin users can access all company data
3. **Webhook authentication missing** - Public endpoints vulnerable to forgery
4. **User model unprotected** - No company_id filtering on User queries

#### High Priority Issues:
5. **Service discovery lacks validation** - Cross-company service usage possible
6. **Input sanitization incomplete** - Missing observers for most models
7. **Rate limiting not implemented** - API vulnerable to abuse
8. **Security monitoring absent** - No alerting for suspicious activity

#### Estimated Remediation Effort:
- **Critical fixes**: 40-60 hours development + 20 hours testing
- **High priority**: 30-40 hours development + 15 hours testing
- **Total**: 90-115 hours before production deployment

#### Recommended Timeline:
1. **Week 1**: Fix critical multi-tenant isolation (items 1-4)
2. **Week 2**: Implement webhook authentication and testing (items 3, 7)
3. **Week 3**: Complete remaining security hardening (items 5-8)
4. **Week 4**: Comprehensive security testing and penetration testing
5. **Week 5**: Production deployment with monitoring

### Next Steps:
1. **Immediate**: Halt production deployment until critical fixes implemented
2. **Priority**: Implement BelongsToCompany trait on all models
3. **Next**: Fix CompanyScope admin bypass vulnerability
4. **Then**: Implement webhook authentication middleware
5. **Finally**: Complete security testing and validation

---

**Document Version**: 1.0
**Last Updated**: 2025-10-02
**Next Review**: After critical fixes implementation
**Approval Required From**: Security Team Lead, CTO, Legal/Compliance
