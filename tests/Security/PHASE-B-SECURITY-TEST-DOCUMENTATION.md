# PHASE B - Security Penetration Test Documentation

## Executive Summary

This document provides comprehensive documentation for the PHASE B security penetration testing suite, designed to validate the security fixes implemented in PHASE A of the API Gateway security hardening project.

**Test Suite Version**: 1.0
**Created**: October 2, 2025
**Target System**: API Gateway Multi-Tenant Application
**Risk Level**: Production-Safe (Uses test database)

---

## Table of Contents

1. [Overview](#overview)
2. [Attack Scenarios](#attack-scenarios)
3. [Test Execution Guide](#test-execution-guide)
4. [Expected Behaviors](#expected-behaviors)
5. [CVSS Score Reference](#cvss-score-reference)
6. [Remediation Guidance](#remediation-guidance)
7. [Appendix](#appendix)

---

## Overview

### Purpose

The PHASE B penetration test suite validates that all 5 critical vulnerabilities identified and fixed in PHASE A are properly remediated and cannot be exploited under real attack conditions.

### Scope

**In Scope:**
- Multi-tenant data isolation (CompanyScope)
- Role-based access control (RBAC)
- Webhook authentication and signature verification
- Authorization policy enforcement
- Mass assignment protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection mechanisms
- Authentication middleware
- Observer-based security controls

**Out of Scope:**
- Network-level attacks (DDoS, port scanning)
- Physical security
- Social engineering
- Third-party service vulnerabilities
- Infrastructure misconfigurations

### Test Environment

**Requirements:**
- PHP 8.1+
- Laravel 10+
- MySQL/PostgreSQL database
- Test database (separate from production)
- Bash shell environment
- curl command-line tool

**Safety Measures:**
- All tests use test database (`testing` schema)
- No production data modification
- Automatic cleanup after test execution
- Isolated test user accounts
- Reversible test actions

---

## Attack Scenarios

### ATTACK #1: Cross-Tenant Data Access via Direct Model Queries

**CVSS Score**: 9.8 (CRITICAL)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`

#### Description

Attempts to bypass CompanyScope global scope by directly querying appointments from a different company's tenant space using Eloquent model queries.

#### Attack Vector

```php
// Attacker from Company Beta (9002)
Auth::login($attackerFromCompanyBeta);

// Attempt to access Company Alpha (9001) data
$appointments = \App\Models\Appointment::where('company_id', 9001)->get();
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: Found 15 appointments from Company Alpha
CRITICAL: Cross-tenant data leakage confirmed
```

#### Expected Behavior (Secure System)

```
SECURE: CompanyScope prevented cross-tenant access
RESULT: Found 0 appointments (CompanyScope working)
```

#### Technical Details

**Protection Mechanism**: `App\Scopes\CompanyScope`
- Automatically appends `WHERE company_id = {user->company_id}` to all Eloquent queries
- Applied globally via `BelongsToCompany` trait
- Super admins can bypass for legitimate cross-tenant operations

**Bypass Risk**: Raw SQL queries (`DB::select`, `DB::table`) bypass Eloquent scopes

**Remediation**: Ensure all database queries use Eloquent ORM or manually filter by company_id in raw queries

---

### ATTACK #2: Admin Role Privilege Escalation

**CVSS Score**: 8.8 (HIGH)
**CVSS Vector**: `AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H`

#### Description

Regular staff user attempts to elevate privileges to `super_admin` role through direct role assignment or database manipulation.

#### Attack Vector

```php
// Regular staff user
Auth::login($regularStaffUser);

// Attempt 1: Direct role assignment
$user->assignRole('super_admin');

// Attempt 2: Database manipulation
DB::table('model_has_roles')->insert([
    'role_id' => $superAdminRoleId,
    'model_id' => $user->id
]);
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: Successfully escalated to super_admin
CRITICAL: Privilege escalation successful
User roles: staff, super_admin
```

#### Expected Behavior (Secure System)

```
SECURE: Role assignment blocked by authorization check
OR
SECURE: Exception thrown during role assignment
Error: Unauthorized role assignment attempt
```

#### Technical Details

**Protection Mechanism**: Spatie Laravel Permission
- Role assignment should be protected by Gates/Policies
- Only authorized admins can assign sensitive roles
- Database constraints prevent orphaned role assignments

**Best Practice**:
```php
Gate::define('assign-super-admin', function (User $user) {
    return $user->hasRole('super_admin');
});
```

**Remediation**: Implement authorization gates for role assignment operations

---

### ATTACK #3: Webhook Forgery Attack (Legacy Retell Route)

**CVSS Score**: 9.3 (CRITICAL)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:N`

#### Description

Attacker sends forged webhook request to legacy `/api/webhook` route without valid signature, attempting to manipulate call data or trigger unauthorized actions.

#### Attack Vector

```bash
curl -X POST http://api-gateway.local/api/webhook \
  -H "Content-Type: application/json" \
  -H "X-Retell-Signature: forged_signature_12345" \
  -d '{
    "event": "call_ended",
    "call_id": "malicious_call_123",
    "data": {
      "call_status": "ended",
      "appointment_made": true,
      "price_override": 99999.99
    }
  }'
```

#### Expected Behavior (Vulnerable System)

```
HTTP/1.1 200 OK
{
  "status": "success",
  "message": "Webhook processed",
  "call_id": "malicious_call_123"
}

VULNERABLE: Forged webhook accepted and processed
CRITICAL: Attacker can manipulate call data without authentication
```

#### Expected Behavior (Secure System)

```
HTTP/1.1 401 Unauthorized
{
  "error": "Invalid signature",
  "message": "Webhook signature verification failed"
}

SECURE: Webhook forgery rejected
Middleware: VerifyRetellSignature blocked the request
```

#### Technical Details

**Protection Mechanism**: `App\Http\Middleware\VerifyRetellSignature`
```php
public function handle(Request $request, Closure $next)
{
    $signature = $request->header('X-Retell-Signature');
    $expectedSignature = hash_hmac('sha256', $request->getContent(), config('services.retell.webhook_secret'));

    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    return $next($request);
}
```

**Route Protection** (routes/api.php):
```php
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->middleware(['retell.signature', 'throttle:60,1']);
```

**Remediation**: Ensure all webhook routes include signature verification middleware

---

### ATTACK #4: User Enumeration via Timing Analysis

**CVSS Score**: 5.3 (MEDIUM)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N`

#### Description

Attacker measures response time differences between valid and invalid email addresses during login attempts to enumerate valid user accounts.

#### Attack Vector

```bash
# Test with known valid email
time curl -X POST http://api-gateway.local/login \
  -d '{"email": "admin@test-company.com", "password": "wrong"}'

# Response time: 245ms

# Test with invalid email
time curl -X POST http://api-gateway.local/login \
  -d '{"email": "nonexistent@nowhere.com", "password": "wrong"}'

# Response time: 89ms
```

#### Expected Behavior (Vulnerable System)

```
Valid email response time: 245ms
Invalid email response time: 89ms
Time difference: 156ms

VULNERABLE: Significant timing difference allows user enumeration
Attacker can determine which emails are registered
```

#### Expected Behavior (Secure System)

```
Valid email response time: 182ms
Invalid email response time: 178ms
Time difference: 4ms

SECURE: Response timing is consistent
User enumeration prevented through constant-time operations
```

#### Technical Details

**Protection Mechanism**: Constant-time password verification
```php
// Vulnerable approach
if (!User::where('email', $email)->exists()) {
    return response()->json(['error' => 'Invalid credentials'], 401);
}

// Secure approach
$user = User::where('email', $email)->first();
if (!$user || !Hash::check($password, $user->password)) {
    return response()->json(['error' => 'Invalid credentials'], 401);
}
```

**Best Practice**: Always hash the password even for non-existent users to equalize timing

**Remediation**: Implement constant-time authentication responses

---

### ATTACK #5: Cross-Company Service Booking

**CVSS Score**: 8.1 (HIGH)
**CVSS Vector**: `AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N`

#### Description

User from Company Beta attempts to book a service that belongs to Company Alpha, bypassing company-level authorization checks.

#### Attack Vector

```php
// Attacker from Company Beta (9002)
Auth::login($userFromCompanyBeta);

// Find service from Company Alpha
$serviceAlpha = \App\Models\Service::where('company_id', 9001)->first();

// Attempt to book the service
$booking = \App\Models\Appointment::create([
    'service_id' => $serviceAlpha->id,
    'customer_id' => 1,
    'staff_id' => 1,
    'starts_at' => now()->addDay(),
    'ends_at' => now()->addDay()->addHour(),
    'status' => 'pending'
]);
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: Found 12 services from Company Alpha
VULNERABLE: Cross-company booking succeeded
CRITICAL: Appointment ID: 4567 created
Company Beta user booked Company Alpha service
```

#### Expected Behavior (Secure System)

```
SECURE: CompanyScope prevented service access
Found 0 services from Company Alpha
Authorization check prevents cross-company operations
```

#### Technical Details

**Protection Mechanism**:
1. `CompanyScope` on Service model prevents accessing other companies' services
2. `BelongsToCompany` trait validates company ownership
3. Authorization policies check company_id matches

**Related Policy** (AppointmentPolicy):
```php
public function create(User $user, Service $service): bool
{
    return $user->company_id === $service->company_id;
}
```

**Remediation**: Ensure authorization checks validate company ownership before resource access

---

### ATTACK #6: SQL Injection via company_id Manipulation

**CVSS Score**: 9.8 (CRITICAL)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`

#### Description

Attacker attempts SQL injection through the company_id parameter to bypass authorization checks and access unauthorized data.

#### Attack Vector

```bash
# Boolean-based SQL injection
curl "http://api-gateway.local/api/v1/appointments?company_id=9001' OR '1'='1"

# Union-based SQL injection
curl "http://api-gateway.local/api/v1/appointments?company_id=9001' UNION SELECT * FROM users--"

# Time-based blind SQL injection
curl "http://api-gateway.local/api/v1/appointments?company_id=9001' AND SLEEP(5)--"
```

#### Expected Behavior (Vulnerable System)

```
HTTP/1.1 200 OK
{
  "appointments": [
    // Returns ALL appointments from ALL companies
    {"id": 1, "company_id": 9001, ...},
    {"id": 2, "company_id": 9002, ...},
    {"id": 3, "company_id": 9003, ...}
  ]
}

VULNERABLE: SQL injection successful
Database error messages expose schema information
```

#### Expected Behavior (Secure System)

```
HTTP/1.1 400 Bad Request
{
  "error": "Invalid company_id parameter",
  "message": "Parameter must be a valid integer"
}

SECURE: SQL injection prevented through parameterized queries
Input validation rejected malicious payload
```

#### Technical Details

**Protection Mechanism**: Laravel Query Builder with parameter binding
```php
// Vulnerable (string concatenation)
DB::select("SELECT * FROM appointments WHERE company_id = " . $request->company_id);

// Secure (parameterized query)
DB::select("SELECT * FROM appointments WHERE company_id = ?", [$request->company_id]);

// Best (Eloquent ORM)
Appointment::where('company_id', $request->company_id)->get();
```

**Input Validation**:
```php
$validated = $request->validate([
    'company_id' => 'required|integer|exists:companies,id'
]);
```

**Remediation**:
1. Use Eloquent ORM for all database queries
2. Validate and sanitize all user inputs
3. Never concatenate user input into SQL strings
4. Implement prepared statements for raw queries

---

### ATTACK #7: XSS Injection via Observer Pattern

**CVSS Score**: 6.1 (MEDIUM)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N`

#### Description

Attacker injects malicious JavaScript payload into appointment notes or metadata, which is then executed when rendered by other users via the AppointmentObserver.

#### Attack Vector

```php
// Create appointment with XSS payload
$appointment = \App\Models\Appointment::create([
    'service_id' => 1,
    'customer_id' => 1,
    'staff_id' => 1,
    'starts_at' => now()->addDay(),
    'ends_at' => now()->addDay()->addHour(),
    'status' => 'pending',
    'notes' => '<script>alert("XSS")</script>',
    'metadata' => [
        'description' => '<img src=x onerror=alert(1)>',
        'malicious' => '<svg onload=alert(document.cookie)>'
    ]
]);
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: XSS payload stored without sanitization
Database contains: <script>alert("XSS")</script>
Metadata contains raw HTML tags

When rendered in browser:
[JavaScript executes, showing alert box]
[Attacker can steal cookies, session tokens]
```

#### Expected Behavior (Secure System)

```
SECURE: XSS payload sanitized before storage
Stored as: &lt;script&gt;alert("XSS")&lt;/script&gt;

OR

SECURE: Input validation rejected malicious content
Error: Invalid characters in notes field
```

#### Technical Details

**Protection Mechanism**:
1. Input sanitization using HTMLPurifier or similar
2. Output encoding with Blade's `{{ }}` syntax
3. Content Security Policy (CSP) headers

**Blade Template Protection**:
```blade
{{-- Safe: Automatic HTML escaping --}}
<p>{{ $appointment->notes }}</p>

{{-- Dangerous: Raw HTML output --}}
<p>{!! $appointment->notes !!}</p>
```

**Input Sanitization**:
```php
use Illuminate\Support\Str;

$appointment->notes = Str::of($request->notes)
    ->stripTags()
    ->trim();
```

**Remediation**:
1. Sanitize user input before storage
2. Use Blade's escaped output `{{ }}` instead of `{!! !!}`
3. Implement Content Security Policy headers
4. Validate input against whitelist of allowed HTML tags

---

### ATTACK #8: Authorization Policy Bypass

**CVSS Score**: 8.8 (HIGH)
**CVSS Vector**: `AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H`

#### Description

Regular user attempts to perform admin-only or super_admin-only actions by bypassing Laravel authorization policies.

#### Attack Vector

```php
// Regular staff user
Auth::login($regularStaffUser);

$appointment = Appointment::find(123);

// Attempt super_admin-only action (forceDelete)
if ($user->can('forceDelete', $appointment)) {
    $appointment->forceDelete();
}

// Attempt to delete past appointment (admin only)
$pastAppointment = Appointment::where('starts_at', '<', now())->first();
$pastAppointment->delete();
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: Regular user can forceDelete
VULNERABLE: Staff deleted past appointment
Policy checks not enforced on controller actions
```

#### Expected Behavior (Secure System)

```
SECURE: Policy prevented forceDelete for non-super_admin
Action: forceDelete - Permission: DENIED

SECURE: Only admins can delete past appointments
Action: delete past appointment - Permission: DENIED
```

#### Technical Details

**Protection Mechanism**: `App\Policies\AppointmentPolicy`
```php
public function forceDelete(User $user, Appointment $appointment): bool
{
    return $user->hasRole('super_admin');
}

public function delete(User $user, Appointment $appointment): bool
{
    // Past appointments: admin only
    if ($appointment->starts_at < now()) {
        return $user->hasRole('admin');
    }

    // Future appointments: managers in same company
    return $user->hasRole('manager')
        && $user->company_id === $appointment->company_id;
}
```

**Controller Authorization**:
```php
public function destroy(Appointment $appointment)
{
    $this->authorize('delete', $appointment);

    $appointment->delete();

    return response()->json(['message' => 'Deleted']);
}
```

**Remediation**:
1. Define comprehensive policies for all models
2. Use `$this->authorize()` in all controller actions
3. Implement policy `before()` method for super_admin bypass
4. Test authorization logic with different user roles

---

### ATTACK #9: CompanyScope Bypass via Raw SQL Queries

**CVSS Score**: 9.1 (CRITICAL)
**CVSS Vector**: `AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N`

#### Description

Attacker bypasses CompanyScope global scope by using raw SQL queries or `DB::table()` which don't trigger Eloquent scopes.

#### Attack Vector

```php
// Logged in as Company Beta (9002)
Auth::login($attackerFromCompanyBeta);

// BYPASS 1: Using DB::table()
$appointments = DB::table('appointments')
    ->where('company_id', 9001)
    ->get();

// BYPASS 2: Raw SQL query
$appointments = DB::select('SELECT * FROM appointments WHERE company_id = 9001');

// BYPASS 3: Explicit scope removal
$appointments = Appointment::withoutGlobalScope(CompanyScope::class)
    ->where('company_id', 9001)
    ->get();
```

#### Expected Behavior (Vulnerable System)

```
VULNERABLE: DB::table() found 45 appointments from Company 9001
VULNERABLE: Raw SQL found 45 appointments from Company 9001
VULNERABLE: withoutGlobalScope() allowed scope bypass

CRITICAL: CompanyScope can be bypassed with raw queries
User from Company Beta accessed Company Alpha data
```

#### Expected Behavior (Secure System)

```
WARNING: DB::table() bypasses CompanyScope (expected behavior)
NOTE: Application code must manually filter by company_id in raw queries

ANALYSIS: CompanyScope protection summary:
  ✓ Eloquent queries: PROTECTED
  ✗ DB::table(): NOT PROTECTED (manual filtering required)
  ✗ Raw SQL: NOT PROTECTED (manual filtering required)
  ? withoutGlobalScope(): Depends on authorization checks
```

#### Technical Details

**Important Note**: This is expected behavior, not a vulnerability. Raw SQL queries bypass Eloquent scopes by design.

**Best Practices**:
```php
// BAD: Raw query without company filter
$appointments = DB::table('appointments')->get();

// GOOD: Manually enforce company filter
$appointments = DB::table('appointments')
    ->where('company_id', Auth::user()->company_id)
    ->get();

// BEST: Use Eloquent ORM (scope applied automatically)
$appointments = Appointment::all();
```

**Code Review Checklist**:
- [ ] All `DB::table()` calls include company_id filter
- [ ] All `DB::select()` queries include company_id WHERE clause
- [ ] `withoutGlobalScope()` is only used with authorization checks
- [ ] Prefer Eloquent ORM over raw queries

**Remediation**:
1. Audit codebase for raw SQL queries
2. Add company_id filtering to all raw queries
3. Implement static analysis rules to detect unsafe raw queries
4. Prefer Eloquent ORM for all database operations

---

### ATTACK #10: Monitor Endpoint Unauthorized Access

**CVSS Score**: 7.5 (HIGH)
**CVSS Vector**: `AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N`

#### Description

Unauthenticated attacker accesses sensitive monitoring and metrics endpoints to gather system information and identify vulnerabilities.

#### Attack Vector

```bash
# Attempt to access webhook monitoring
curl http://api-gateway.local/api/webhooks/monitor

# Attempt to access health metrics
curl http://api-gateway.local/api/health/metrics

# Attempt to access detailed health check
curl http://api-gateway.local/api/health/detailed
```

#### Expected Behavior (Vulnerable System)

```
HTTP/1.1 200 OK
{
  "webhooks": {
    "total_processed": 15234,
    "failed": 12,
    "error_rate": 0.08,
    "endpoints": [
      {"url": "https://retell.ai/webhook", "secret": "sk_live_abc123"}
    ]
  },
  "database": {
    "host": "prod-db-01.internal",
    "username": "api_gateway",
    "tables": ["users", "appointments", "calls"]
  }
}

VULNERABLE: Monitoring endpoints accessible without authentication
Sensitive configuration and metrics exposed
```

#### Expected Behavior (Secure System)

```
HTTP/1.1 401 Unauthorized
{
  "error": "Unauthenticated",
  "message": "Authentication required to access this resource"
}

SECURE: Monitor endpoints require authentication
/api/webhooks/monitor - HTTP 401
/api/health/metrics - HTTP 401
```

#### Technical Details

**Protection Mechanism**: Authentication middleware in routes/api.php
```php
// BEFORE (Vulnerable)
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor']);

// AFTER (Secure)
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor'])
    ->middleware('auth:sanctum');
```

**Recommended Middleware Stack**:
```php
Route::prefix('monitoring')
    ->middleware(['auth:sanctum', 'throttle:10,1', 'role:admin'])
    ->group(function () {
        Route::get('/dashboard', [MonitoringController::class, 'dashboard']);
        Route::get('/health', [MonitoringController::class, 'health']);
        Route::get('/metrics', [MonitoringController::class, 'metrics']);
    });
```

**Remediation**:
1. Add `auth:sanctum` middleware to all monitoring routes
2. Implement role-based access (admin/super_admin only)
3. Add rate limiting to prevent enumeration
4. Sanitize output to remove sensitive configuration details
5. Implement IP whitelisting for monitoring endpoints

---

## Test Execution Guide

### Prerequisites

1. **Environment Setup**
   ```bash
   # Navigate to project root
   cd /var/www/api-gateway

   # Ensure test database exists
   php artisan db:create testing

   # Run migrations on test database
   php artisan migrate --database=testing

   # Seed test data if needed
   php artisan db:seed --database=testing
   ```

2. **Configuration**
   ```bash
   # Set environment variables
   export API_URL="http://localhost"
   export TEST_DB="testing"
   ```

### Running Individual Test Suites

#### Option 1: Shell-Based Penetration Tests

Tests HTTP/API endpoints and external attack vectors.

```bash
cd /var/www/api-gateway/tests/Security

# Make executable
chmod +x phase-b-penetration-tests.sh

# Run tests
./phase-b-penetration-tests.sh

# Run with custom API URL
API_URL="https://staging.api-gateway.com" ./phase-b-penetration-tests.sh
```

**Output Format:**
```
━━━ TEST #1: Cross-Tenant Data Access via Model Queries ━━━
CVSS Score: 9.8 CRITICAL | Category: Authorization Bypass

[INFO] Attack: Attempting to query appointments from another company
[ATTACK] Running cross-tenant access test...
✓ PASS: CompanyScope successfully blocked cross-tenant model access
```

#### Option 2: Tinker-Based Model Layer Tests

Tests model-level security directly in PHP.

```bash
cd /var/www/api-gateway

# Run tinker tests
php artisan tinker < tests/Security/phase-b-tinker-attacks.php

# Save output to file
php artisan tinker < tests/Security/phase-b-tinker-attacks.php > /tmp/tinker-test-results.txt
```

**Output Format:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ATTACK #1: Cross-Tenant Data Access via Model Queries
CVSS: 9.8 CRITICAL | Category: Authorization Bypass
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[INFO] Logged in as: attacker@test-company-beta.com (Company ID: 9002)
[ATTACK] Attempting to access appointments from Company Alpha (9001)...
[✓ SECURE] CompanyScope prevented cross-tenant access
[RESULT] Found 0 appointments (CompanyScope working)
```

#### Option 3: Automated Test Runner (Recommended)

Runs all tests and generates comprehensive HTML report.

```bash
cd /var/www/api-gateway/tests/Security

# Make executable
chmod +x run-all-security-tests.sh

# Run all tests
./run-all-security-tests.sh
```

**Features:**
- Executes both shell and tinker test suites
- Generates text and HTML reports
- Provides pass/fail summary with statistics
- Creates timestamped report in `reports/` directory

**Report Location:**
```
/var/www/api-gateway/tests/Security/reports/YYYYMMDD_HHMMSS/
├── security_test_report.txt   # Plain text report
└── security_test_report.html  # Interactive HTML report
```

### Viewing Reports

#### Text Report
```bash
# View latest text report
cat /var/www/api-gateway/tests/Security/reports/*/security_test_report.txt | less

# Search for failures
grep "FAIL" /var/www/api-gateway/tests/Security/reports/*/security_test_report.txt
```

#### HTML Report
```bash
# Open in browser
firefox /var/www/api-gateway/tests/Security/reports/*/security_test_report.html

# Or serve via web server
cd /var/www/api-gateway/tests/Security/reports/latest/
python3 -m http.server 8080
# Navigate to http://localhost:8080/security_test_report.html
```

### Continuous Integration Setup

#### GitHub Actions
```yaml
name: Security Penetration Tests

on: [push, pull_request]

jobs:
  security-tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Install Dependencies
        run: composer install

      - name: Setup Test Database
        run: |
          php artisan migrate --database=testing
          php artisan db:seed --database=testing

      - name: Run Security Tests
        run: |
          cd tests/Security
          chmod +x run-all-security-tests.sh
          ./run-all-security-tests.sh

      - name: Upload Reports
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: security-test-reports
          path: tests/Security/reports/
```

---

## Expected Behaviors

### Vulnerability Status Matrix

| Attack Scenario | If Vulnerable | If Secure | Criticality |
|----------------|---------------|-----------|-------------|
| Cross-Tenant Data Access | Returns data from other companies | Returns 0 records, scope enforced | 🔴 CRITICAL |
| Privilege Escalation | User gains super_admin role | Role assignment blocked | 🔴 HIGH |
| Webhook Forgery | Forged webhook processed | HTTP 401, signature invalid | 🔴 CRITICAL |
| User Enumeration | >100ms timing difference | <50ms timing difference | 🟡 MEDIUM |
| Cross-Company Booking | Booking succeeds across companies | Scope prevents service access | 🔴 HIGH |
| SQL Injection | SQL executed, error messages | Input validation, parameterized queries | 🔴 CRITICAL |
| XSS Injection | Script tags stored/executed | Input sanitized, output encoded | 🟡 MEDIUM |
| Policy Bypass | Unauthorized actions succeed | Policy denies access | 🔴 HIGH |
| CompanyScope Bypass (Raw) | Raw queries return cross-company data | Manual filtering enforced | 🟠 INFO |
| Monitor Endpoint Access | Metrics exposed without auth | HTTP 401, auth required | 🔴 HIGH |

### Success Criteria

**All Tests Must Pass:**
- ✓ 0 CRITICAL vulnerabilities
- ✓ 0 HIGH severity issues
- ✓ Pass rate ≥ 90%
- ✓ All authorization checks enforced
- ✓ All input validation working
- ✓ All authentication middleware active

**Acceptable Warnings:**
- ⚠ User enumeration timing <50ms difference
- ⚠ Raw query CompanyScope bypass (if documented and reviewed)
- ⚠ Non-critical informational findings

---

## CVSS Score Reference

### CVSS v3.1 Severity Ratings

| Score Range | Severity | Urgency | Example Vulnerabilities |
|-------------|----------|---------|-------------------------|
| 9.0 - 10.0 | 🔴 CRITICAL | Immediate | SQL Injection, RCE, Authentication Bypass |
| 7.0 - 8.9 | 🟠 HIGH | Urgent | XSS, CSRF, Privilege Escalation |
| 4.0 - 6.9 | 🟡 MEDIUM | Medium | Information Disclosure, Weak Crypto |
| 0.1 - 3.9 | 🟢 LOW | Low | Rate Limiting, Version Disclosure |

### CVSS Vector String Components

**Example**: `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`

- **AV** (Attack Vector): N=Network, A=Adjacent, L=Local, P=Physical
- **AC** (Attack Complexity): L=Low, H=High
- **PR** (Privileges Required): N=None, L=Low, H=High
- **UI** (User Interaction): N=None, R=Required
- **S** (Scope): U=Unchanged, C=Changed
- **C** (Confidentiality): H=High, L=Low, N=None
- **I** (Integrity): H=High, L=Low, N=None
- **A** (Availability): H=High, L=Low, N=None

### Attack Scenario CVSS Breakdown

#### Cross-Tenant Data Access (9.8 CRITICAL)
```
CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H

Justification:
- Network exploitable (AV:N)
- Low complexity - simple API call (AC:L)
- No privileges required (PR:N)
- No user interaction (UI:N)
- High impact on confidentiality and integrity (C:H/I:H)
```

#### Privilege Escalation (8.8 HIGH)
```
CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H

Justification:
- Network exploitable (AV:N)
- Low complexity (AC:L)
- Requires low privileges - authenticated user (PR:L)
- High impact across all aspects (C:H/I:H/A:H)
```

---

## Remediation Guidance

### Priority 1: CRITICAL Issues (CVSS ≥ 9.0)

#### 1. Cross-Tenant Data Access
```php
// ✅ SOLUTION: Ensure BelongsToCompany trait on all models

namespace App\Models;

use App\Traits\BelongsToCompany;

class Appointment extends Model
{
    use BelongsToCompany; // Applies CompanyScope automatically

    protected $guarded = ['company_id']; // Prevent mass assignment
}

// ✅ Verify in AppServiceProvider
public function boot()
{
    // Audit: List all models using CompanyScope
    $modelsWithScope = [
        Appointment::class,
        Service::class,
        Customer::class,
        Staff::class,
        Branch::class,
    ];

    foreach ($modelsWithScope as $model) {
        if (!method_exists($model, 'bootBelongsToCompany')) {
            throw new \Exception("$model missing BelongsToCompany trait");
        }
    }
}
```

#### 2. Webhook Forgery
```php
// ✅ SOLUTION: routes/api.php

// Add middleware to ALL webhook routes
Route::post('/webhook', [UnifiedWebhookController::class, 'handleRetellLegacy'])
    ->middleware(['retell.signature', 'throttle:60,1']);

Route::post('/webhooks/retell', [RetellWebhookController::class, '__invoke'])
    ->middleware(['retell.signature', 'throttle:60,1']);

// ✅ Verify middleware implementation
// app/Http/Middleware/VerifyRetellSignature.php
public function handle(Request $request, Closure $next)
{
    $signature = $request->header('X-Retell-Signature');

    if (!$signature) {
        abort(401, 'Missing signature');
    }

    $payload = $request->getContent();
    $secret = config('services.retell.webhook_secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        abort(401, 'Invalid signature');
    }

    return $next($request);
}
```

#### 3. SQL Injection
```php
// ❌ VULNERABLE
$appointments = DB::select("SELECT * FROM appointments WHERE company_id = " . $request->company_id);

// ✅ SOLUTION 1: Parameterized query
$appointments = DB::select(
    "SELECT * FROM appointments WHERE company_id = ?",
    [$request->company_id]
);

// ✅ SOLUTION 2: Eloquent ORM (preferred)
$appointments = Appointment::where('company_id', $request->company_id)->get();

// ✅ SOLUTION 3: Input validation
$validated = $request->validate([
    'company_id' => 'required|integer|exists:companies,id'
]);
```

### Priority 2: HIGH Issues (CVSS 7.0-8.9)

#### 4. Privilege Escalation
```php
// ✅ SOLUTION: Gate authorization for role assignment

// app/Providers/AuthServiceProvider.php
Gate::define('assign-role', function (User $user, string $roleName) {
    // Only admins can assign roles
    if (!$user->hasRole('admin')) {
        return false;
    }

    // Only super_admin can assign super_admin role
    if ($roleName === 'super_admin' && !$user->hasRole('super_admin')) {
        return false;
    }

    return true;
});

// Controller usage
public function assignRole(Request $request, User $targetUser)
{
    $this->authorize('assign-role', $request->role);

    $targetUser->assignRole($request->role);
}
```

#### 5. Cross-Company Booking
```php
// ✅ SOLUTION: Authorization policy

// app/Policies/AppointmentPolicy.php
public function create(User $user, array $data): bool
{
    $service = Service::find($data['service_id']);

    if (!$service) {
        return false;
    }

    // Ensure user can only book services from their company
    return $user->company_id === $service->company_id;
}

// Controller
public function store(Request $request)
{
    $this->authorize('create', [Appointment::class, $request->all()]);

    $appointment = Appointment::create($request->validated());
}
```

#### 6. Monitor Endpoint Access
```php
// ✅ SOLUTION: Add authentication middleware

// routes/api.php
Route::get('/webhooks/monitor', [UnifiedWebhookController::class, 'monitor'])
    ->middleware(['auth:sanctum', 'role:admin']);

Route::get('/health/metrics', [HealthCheckController::class, 'metrics'])
    ->middleware(['auth:sanctum', 'throttle:10,1']);
```

### Priority 3: MEDIUM Issues (CVSS 4.0-6.9)

#### 7. User Enumeration
```php
// ✅ SOLUTION: Constant-time authentication

public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();

    // ALWAYS hash password, even for non-existent users
    $validPassword = $user
        ? Hash::check($request->password, $user->password)
        : Hash::check($request->password, '$2y$10$fakehashtoequalizetiming');

    if (!$user || !$validPassword) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json(['token' => $user->createToken('auth')->plainTextToken]);
}
```

#### 8. XSS Injection
```php
// ✅ SOLUTION: Input sanitization and output encoding

// Model accessor for safe output
public function getNotesAttribute($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// OR: Strip tags on input
public function setNotesAttribute($value)
{
    $this->attributes['notes'] = strip_tags($value);
}

// Blade template (automatic escaping)
<p>{{ $appointment->notes }}</p>

// Content Security Policy header
// app/Http/Middleware/SecurityHeaders.php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);

    $response->headers->set('Content-Security-Policy',
        "default-src 'self'; script-src 'self'; object-src 'none';"
    );

    return $response;
}
```

### Code Review Checklist

Before deploying to production, verify:

- [ ] All models with company_id use `BelongsToCompany` trait
- [ ] All webhook routes have signature verification middleware
- [ ] No raw SQL queries without parameterization
- [ ] All controller actions use `$this->authorize()`
- [ ] All user inputs are validated with Laravel validation rules
- [ ] All Blade templates use `{{ }}` instead of `{!! !!}`
- [ ] Sensitive endpoints require authentication
- [ ] Role assignments are protected by Gates
- [ ] Mass assignment protection via `$guarded` or `$fillable`
- [ ] Rate limiting on all API endpoints

---

## Appendix

### A. Test Database Setup

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS testing;"

# Run migrations
php artisan migrate --database=testing

# Seed with test data
php artisan db:seed --database=testing --class=SecurityTestSeeder
```

### B. Environment Configuration

```env
# .env.testing

APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=testing
DB_USERNAME=your_username
DB_PASSWORD=your_password

RETELL_WEBHOOK_SECRET=test_webhook_secret_key
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### C. Security Test Seeder

Create `/database/seeders/SecurityTestSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Company, User, Service, Appointment};
use Spatie\Permission\Models\Role;

class SecurityTestSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'staff']);

        // Create test companies
        $companyAlpha = Company::firstOrCreate(
            ['id' => 9001],
            ['name' => 'Test Company Alpha', 'tenant_id' => 1]
        );

        $companyBeta = Company::firstOrCreate(
            ['id' => 9002],
            ['name' => 'Test Company Beta', 'tenant_id' => 1]
        );

        // Create test users
        $admin = User::firstOrCreate(
            ['email' => 'admin@test-company-alpha.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'company_id' => 9001
            ]
        );
        $admin->assignRole('admin');

        $staff = User::firstOrCreate(
            ['email' => 'user@test-company-alpha.com'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'company_id' => 9001
            ]
        );
        $staff->assignRole('staff');

        $attacker = User::firstOrCreate(
            ['email' => 'attacker@test-company-beta.com'],
            [
                'name' => 'Malicious User',
                'password' => bcrypt('password'),
                'company_id' => 9002
            ]
        );
        $attacker->assignRole('staff');

        // Create test services
        Service::firstOrCreate(
            ['company_id' => 9001, 'name' => 'Alpha Service'],
            ['duration' => 60, 'price' => 100.00]
        );

        Service::firstOrCreate(
            ['company_id' => 9002, 'name' => 'Beta Service'],
            ['duration' => 60, 'price' => 150.00]
        );
    }
}
```

### D. Quick Reference Commands

```bash
# Run all security tests
./tests/Security/run-all-security-tests.sh

# Run only shell tests
./tests/Security/phase-b-penetration-tests.sh

# Run only tinker tests
php artisan tinker < tests/Security/phase-b-tinker-attacks.php

# View latest HTML report
firefox tests/Security/reports/*/security_test_report.html

# Clean up test data
php artisan tinker --execute="
    \App\Models\User::whereIn('email', [
        'admin@test-company-alpha.com',
        'user@test-company-alpha.com',
        'attacker@test-company-beta.com'
    ])->delete();
    \App\Models\Company::whereIn('id', [9001, 9002])->delete();
"

# Check for CRITICAL findings
grep "CRITICAL" tests/Security/reports/*/security_test_report.txt

# Count vulnerabilities by severity
grep -o "CVSS.*" tests/Security/phase-b-penetration-tests.sh | sort | uniq -c
```

### E. Common Issues and Troubleshooting

**Issue**: Tests fail with database connection error
**Solution**: Verify test database exists and credentials in `.env.testing`

**Issue**: CompanyScope tests pass but shouldn't
**Solution**: Check if test users have `super_admin` role (bypasses scope)

**Issue**: Webhook signature test fails
**Solution**: Ensure `RETELL_WEBHOOK_SECRET` is set in environment

**Issue**: Permission denied when running scripts
**Solution**: `chmod +x tests/Security/*.sh`

**Issue**: Artisan tinker hangs
**Solution**: Check for syntax errors in tinker test file

---

## Contact and Support

For questions or issues with the security test suite:

- **Security Team**: security@api-gateway.local
- **Documentation**: `/docs/security/`
- **Issue Tracker**: https://github.com/api-gateway/security/issues

**Last Updated**: October 2, 2025
**Version**: 1.0.0
**Maintained By**: Security Engineering Team
