# Customer Portal Security Audit Report
**Date**: 2025-10-26
**Scope**: Customer Portal Phase 1 MVP - Security Assessment
**Target**: `/portal` endpoint for company_owner, company_manager, company_staff roles
**Classification**: SECURITY CRITICAL

---

## Executive Summary

### Overall Risk Assessment: **HIGH**

**Critical Finding**: The current implementation has **ZERO security controls** in place for the Customer Portal. The `canAccessPanel()` method allows ALL authenticated users to access ALL panels, creating severe security vulnerabilities.

```php
// app/Models/User.php:103-108 - CRITICAL VULNERABILITY
public function canAccessPanel(Panel $panel): bool
{
    // For now, allow all authenticated users
    // Later we can add role/permission checks
    return true; // ⚠️ NO PANEL ISOLATION
}
```

**Impact**:
- Admin users can access customer portal
- Customer users can access admin panel
- No multi-tenant isolation at panel level
- Session hijacking risk between panels

---

## 🔴 CRITICAL Security Vulnerabilities

### VULN-PORTAL-001: Panel Access Control Bypass (CRITICAL)
**Severity**: CRITICAL
**CVSS Score**: 9.1 (Critical)
**CWE**: CWE-284 (Improper Access Control)

**Description**: `canAccessPanel()` returns `true` for all authenticated users regardless of panel ID, allowing:
- Company owners accessing `/admin` panel
- Super admins accessing `/portal` panel
- No separation of duties between panels

**Proof of Concept**:
```php
// Current implementation allows:
$companyOwner = User::find(15); // company_owner role
$companyOwner->canAccessPanel($adminPanel); // Returns TRUE ❌
$companyOwner->canAccessPanel($portalPanel); // Returns TRUE ✅

$superAdmin = User::find(1); // super_admin role
$superAdmin->canAccessPanel($portalPanel); // Returns TRUE ❌
```

**Remediation**: Implement panel-specific authorization:
```php
public function canAccessPanel(Panel $panel): bool
{
    return match($panel->getId()) {
        'admin' => $this->hasAnyRole(['super_admin', 'admin']),
        'portal' => $this->hasAnyRole(['company_owner', 'company_manager', 'company_staff']),
        default => false,
    };
}
```

---

### VULN-PORTAL-002: Missing RetellCallSessionPolicy (HIGH)
**Severity**: HIGH
**CVSS Score**: 8.2 (High)
**CWE**: CWE-862 (Missing Authorization)

**Description**: No policy exists for `RetellCallSession` model, which will be exposed in customer portal.

**Impact**:
- No authorization checks on call session viewing
- Company A could potentially view Company B's calls
- No branch-level isolation for company_manager role
- No staff-level isolation for company_staff role

**Evidence**:
```bash
$ grep -r "RetellCallSessionPolicy" app/
# No results found

$ grep "RetellCallSession" app/Providers/AuthServiceProvider.php
# Not registered in $policies array
```

**Remediation**: Create `app/Policies/RetellCallSessionPolicy.php` with:
- `viewAny()`: Allow company_owner/manager/staff
- `view()`: Check company_id match + branch_id for managers + staff_id for staff
- Register in `AuthServiceProvider::$policies`

---

### VULN-PORTAL-003: Multi-Tenant Data Leakage Risk (HIGH)
**Severity**: HIGH
**CVSS Score**: 8.1 (High)
**CWE**: CWE-639 (Authorization Bypass Through User-Controlled Key)

**Description**: `RetellCallSession` model does NOT use `BelongsToCompany` trait for automatic scoping.

**Current State**:
```php
// app/Models/RetellCallSession.php
class RetellCallSession extends Model
{
    use HasUuids;
    // ❌ NO BelongsToCompany trait
    // ❌ NO automatic CompanyScope
}
```

**Impact**:
- Eloquent queries do NOT automatically filter by `company_id`
- Developers must manually add `->where('company_id', auth()->user()->company_id)`
- High risk of accidental data leakage in new features
- Existing code may already have leakage (not audited)

**Evidence from existing models**:
```php
// Appointment.php - SECURE ✅
class Appointment extends Model
{
    use BelongsToCompany; // Automatic company_id scoping
}

// Branch.php - SECURE ✅
class Branch extends Model
{
    use BelongsToCompany; // Automatic company_id scoping
}

// RetellCallSession.php - VULNERABLE ❌
class RetellCallSession extends Model
{
    // No automatic scoping
}
```

**Remediation**: Add `BelongsToCompany` trait to `RetellCallSession`:
```php
use App\Traits\BelongsToCompany;

class RetellCallSession extends Model
{
    use HasUuids, BelongsToCompany;
}
```

---

### VULN-PORTAL-004: Branch Isolation Not Enforced (MEDIUM)
**Severity**: MEDIUM
**CVSS Score**: 6.5 (Medium)
**CWE**: CWE-285 (Improper Authorization)

**Description**: `company_manager` role should only see data for their specific branch, but no enforcement mechanism exists.

**Requirements**:
- `company_owner`: All branches in company
- `company_manager`: Only their assigned branch (`user.branch_id`)
- `company_staff`: Only their own appointments/calls

**Current State**: User model has `company_id` but NO `branch_id` field.

**Impact**:
- Company managers can see all branches (privilege escalation)
- No granular access control at branch level

**Database Schema Check**:
```sql
-- users table
SELECT column_name
FROM information_schema.columns
WHERE table_name = 'users' AND column_name = 'branch_id';
-- Result: NO branch_id column exists
```

**Remediation**:
1. Add migration: `users.branch_id` (nullable, for company_manager role)
2. Update policies to check `branch_id` for managers
3. Add branch assignment UI in admin panel

---

### VULN-PORTAL-005: Session Hijacking Risk (MEDIUM)
**Severity**: MEDIUM
**CVSS Score**: 5.9 (Medium)
**CWE**: CWE-384 (Session Fixation)

**Description**: Same session cookie is used for both `/admin` and `/portal` panels.

**Attack Scenario**:
1. Company owner logs into `/portal`
2. Session cookie is set: `laravel_session=abc123`
3. Attacker obtains session cookie (XSS, network sniffing)
4. Attacker accesses `/admin` using same cookie
5. If `canAccessPanel()` is not properly implemented → FULL ADMIN ACCESS

**Current Vulnerability**:
```php
// Both panels use same session driver
// config/session.php: 'driver' => 'database'
// Cookie name: 'laravel_session'

// AdminPanelProvider.php
->path('admin')

// CustomerPanelProvider.php (planned)
->path('portal')
// Both share the SAME session!
```

**Remediation**:
1. Implement proper `canAccessPanel()` checks (mitigates risk)
2. Consider separate session cookies per panel (defense in depth)
3. Implement session binding to panel ID

---

### VULN-PORTAL-006: Missing CSRF Protection Verification (LOW)
**Severity**: LOW
**CVSS Score**: 4.3 (Medium)
**CWE**: CWE-352 (Cross-Site Request Forgery)

**Description**: CSRF protection is enabled globally, but not verified for Filament panels.

**Current State**:
```php
// AdminPanelProvider.php:67
->middleware([
    VerifyCsrfToken::class, // ✅ Included
])
```

**Status**: CSRF protection IS enabled. This is INFORMATIONAL only.

**Recommendation**: Verify CSRF tokens are working in manual testing:
- Test form submissions in portal
- Verify 419 errors for invalid tokens
- Check AJAX requests include `X-CSRF-TOKEN` header

---

### VULN-PORTAL-007: SQL Injection Risk (ORM Protected) (INFO)
**Severity**: INFORMATIONAL
**Risk**: LOW (Eloquent ORM provides protection)

**Description**: Application uses Eloquent ORM, which provides automatic SQL injection protection through parameter binding.

**Evidence**:
```php
// Safe - Eloquent parameter binding
RetellCallSession::where('company_id', $companyId)->get();

// Safe - CompanyScope uses parameterized queries
$builder->where($model->getTable() . '.company_id', $user->company_id);
```

**Caveat**: Raw SQL queries could introduce vulnerabilities:
```php
// UNSAFE ❌
DB::select("SELECT * FROM calls WHERE company_id = {$companyId}");

// SAFE ✅
DB::select("SELECT * FROM calls WHERE company_id = ?", [$companyId]);
```

**Recommendation**: Code review for any `DB::raw()` or `DB::select()` usage.

---

## 🟡 MEDIUM Risk Findings

### FINDING-001: No RetellCallSession Policy Implementation
**Status**: Not yet created
**Risk**: Data leakage through unprotected queries

**Required Policy Methods**:
```php
class RetellCallSessionPolicy
{
    // Allow company users to view call list
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['company_owner', 'company_manager', 'company_staff']);
    }

    // Granular access control for individual calls
    public function view(User $user, RetellCallSession $session): bool
    {
        // Company isolation
        if ($user->company_id !== $session->company_id) {
            return false;
        }

        // Branch isolation for managers
        if ($user->hasRole('company_manager')) {
            return $user->branch_id === $session->branch_id;
        }

        // Staff isolation - only see own calls
        if ($user->hasRole('company_staff')) {
            // Check if staff was involved in call
            return $session->call?->staff_id === $user->staff_id;
        }

        // Company owner sees all
        return $user->hasRole('company_owner');
    }
}
```

---

### FINDING-002: AppointmentPolicy Missing Branch Isolation
**Current State**: Policy checks `company_id` but NOT `branch_id`

```php
// app/Policies/AppointmentPolicy.php:44
if ($user->company_id === $appointment->company_id) {
    return true; // ⚠️ All company users see all branches
}
```

**Required Enhancement**:
```php
public function view(User $user, Appointment $appointment): bool
{
    // Company isolation
    if ($user->company_id !== $appointment->company_id) {
        return false;
    }

    // Branch isolation for managers
    if ($user->hasRole('company_manager') && $user->branch_id) {
        return $user->branch_id === $appointment->branch_id;
    }

    // Staff isolation
    if ($user->hasRole('company_staff')) {
        return $user->id === $appointment->staff_id;
    }

    return true; // Owner/Admin see all
}
```

---

### FINDING-003: CallPolicy Has Reseller Logic But No Customer Portal Logic
**Current State**: Policy has reseller support but no customer portal role checks

```php
// app/Policies/CallPolicy.php:55-60
if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
    if ($call->company && $call->company->parent_company_id === $user->company_id) {
        return true;
    }
}
```

**Missing**: Support for `company_owner`, `company_manager`, `company_staff`

**Required Enhancement**:
```php
public function view(User $user, Call $call): bool
{
    // Existing admin/reseller logic...

    // NEW: Customer portal roles
    if ($user->hasAnyRole(['company_owner', 'company_manager', 'company_staff'])) {
        // Company isolation
        if ($user->company_id !== $call->company_id) {
            return false;
        }

        // Branch isolation for managers
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $call->branch_id;
        }

        // Staff isolation
        if ($user->hasRole('company_staff')) {
            return $call->staff_id === $user->staff_id;
        }

        return true; // Owner sees all
    }

    return false;
}
```

---

## 🟢 LOW Risk / Informational Findings

### INFO-001: CompanyScope Performance Optimization
**Status**: Already implemented ✅

```php
// app/Scopes/CompanyScope.php:16-17
private static $cachedUser = null;
private static $cachedUserId = null;
```

**Analysis**: User caching prevents memory exhaustion from repeated `Auth::user()` calls. Good security practice.

---

### INFO-002: Appointment Model Has Multi-Tenant Validation
**Status**: Already implemented ✅

```php
// app/Models/Appointment.php:83-106
static::creating(function ($appointment) {
    if (is_null($appointment->branch_id)) {
        throw new \Exception('Appointments must have a branch_id');
    }

    $branchBelongsToCompany = Branch::where('id', $appointment->branch_id)
        ->where('company_id', $appointment->company_id)
        ->exists();

    if (!$branchBelongsToCompany) {
        throw new \Exception('SECURITY VIOLATION: Branch does not belong to company');
    }
});
```

**Analysis**: Excellent defense-in-depth. Prevents tenant isolation bypass at model level.

---

## Required Security Measures Before Launch

### 🔴 CRITICAL (MUST FIX BEFORE DEPLOYMENT)

1. **Implement Panel Access Control**
   - [ ] Update `User::canAccessPanel()` with panel-specific logic
   - [ ] Test admin cannot access `/portal`
   - [ ] Test company_owner cannot access `/admin`
   - [ ] Add automated test for panel isolation

2. **Create RetellCallSessionPolicy**
   - [ ] Create policy file
   - [ ] Implement `viewAny()`, `view()`, `export()`
   - [ ] Register in `AuthServiceProvider::$policies`
   - [ ] Add branch/staff isolation logic

3. **Add BelongsToCompany Trait to RetellCallSession**
   - [ ] Add trait to model
   - [ ] Test automatic company_id scoping
   - [ ] Verify no data leakage in queries

### 🟡 HIGH PRIORITY (REQUIRED FOR PHASE 1)

4. **Add users.branch_id Column**
   - [ ] Create migration
   - [ ] Add to User model fillable
   - [ ] Update user seeder
   - [ ] Add branch assignment UI in admin

5. **Update Existing Policies**
   - [ ] AppointmentPolicy: Add branch/staff isolation
   - [ ] CallPolicy: Add customer portal role support
   - [ ] Test all policy methods

6. **Session Security**
   - [ ] Test `canAccessPanel()` enforcement
   - [ ] Verify session cookies are secure
   - [ ] Test logout from one panel doesn't affect other

### 🟢 RECOMMENDED (PHASE 2)

7. **Security Headers**
   - [ ] Verify CSP (Content Security Policy)
   - [ ] Verify X-Frame-Options
   - [ ] Verify HSTS

8. **Rate Limiting**
   - [ ] Add rate limiting to portal routes
   - [ ] Prevent brute force login attempts

9. **Audit Logging**
   - [ ] Log all portal access attempts
   - [ ] Log policy denials
   - [ ] Alert on suspicious activity

---

## Policy Implementation Checklist

### RetellCallSessionPolicy (NEW)
```php
namespace App\Policies;

use App\Models\RetellCallSession;
use App\Models\User;

class RetellCallSessionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin',
            'company_owner',
            'company_manager',
            'company_staff',
        ]);
    }

    public function view(User $user, RetellCallSession $session): bool
    {
        // Admin can view all
        if ($user->hasRole('admin')) {
            return true;
        }

        // Company isolation
        if ($user->company_id !== $session->company_id) {
            return false;
        }

        // Branch isolation for managers
        if ($user->hasRole('company_manager') && $user->branch_id) {
            return $user->branch_id === $session->branch_id;
        }

        // Staff isolation - only see calls they participated in
        if ($user->hasRole('company_staff')) {
            // Check if staff was involved
            return $session->call?->staff_id === $user->staff_id;
        }

        // Company owner sees all sessions for their company
        return $user->hasRole('company_owner');
    }

    public function export(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'company_owner', 'company_manager']);
    }
}
```

**Registration**:
```php
// app/Providers/AuthServiceProvider.php:15
protected $policies = [
    // ... existing policies
    \App\Models\RetellCallSession::class => \App\Policies\RetellCallSessionPolicy::class,
];
```

---

## Test Cases for Security Validation

### Test Suite: Panel Access Control

```php
// tests/Feature/Security/PanelAccessControlTest.php

public function test_company_owner_can_access_portal()
{
    $user = User::factory()->create();
    $user->assignRole('company_owner');

    $this->actingAs($user)
        ->get('/portal')
        ->assertOk();
}

public function test_company_owner_cannot_access_admin()
{
    $user = User::factory()->create();
    $user->assignRole('company_owner');

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
}

public function test_super_admin_can_access_admin_but_not_portal()
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk();

    $this->actingAs($user)
        ->get('/portal')
        ->assertForbidden();
}
```

### Test Suite: Multi-Tenant Isolation

```php
// tests/Feature/Security/MultiTenantIsolationTest.php

public function test_company_owner_only_sees_own_company_calls()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('company_owner');

    $sessionA = RetellCallSession::factory()->create(['company_id' => $companyA->id]);
    $sessionB = RetellCallSession::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    // Should see own company session
    $this->assertTrue($userA->can('view', $sessionA));

    // Should NOT see other company session
    $this->assertFalse($userA->can('view', $sessionB));
}

public function test_company_manager_only_sees_own_branch()
{
    $company = Company::factory()->create();
    $branchA = Branch::factory()->create(['company_id' => $company->id]);
    $branchB = Branch::factory()->create(['company_id' => $company->id]);

    $manager = User::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branchA->id,
    ]);
    $manager->assignRole('company_manager');

    $sessionA = RetellCallSession::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branchA->id,
    ]);
    $sessionB = RetellCallSession::factory()->create([
        'company_id' => $company->id,
        'branch_id' => $branchB->id,
    ]);

    $this->actingAs($manager);

    // Should see own branch
    $this->assertTrue($manager->can('view', $sessionA));

    // Should NOT see other branch
    $this->assertFalse($manager->can('view', $sessionB));
}

public function test_automatic_company_scoping_prevents_leakage()
{
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $userA->assignRole('company_owner');

    RetellCallSession::factory()->create(['company_id' => $companyA->id]);
    RetellCallSession::factory()->create(['company_id' => $companyB->id]);

    $this->actingAs($userA);

    // Should only retrieve company A sessions
    $sessions = RetellCallSession::all();
    $this->assertCount(1, $sessions);
    $this->assertEquals($companyA->id, $sessions->first()->company_id);
}
```

### Test Suite: Branch Isolation

```php
public function test_company_staff_only_sees_own_appointments()
{
    $company = Company::factory()->create();
    $staff1 = Staff::factory()->create(['company_id' => $company->id]);
    $staff2 = Staff::factory()->create(['company_id' => $company->id]);

    $user1 = User::factory()->create([
        'company_id' => $company->id,
        'staff_id' => $staff1->id,
    ]);
    $user1->assignRole('company_staff');

    $appt1 = Appointment::factory()->create([
        'company_id' => $company->id,
        'staff_id' => $staff1->id,
    ]);
    $appt2 = Appointment::factory()->create([
        'company_id' => $company->id,
        'staff_id' => $staff2->id,
    ]);

    $this->actingAs($user1);

    // Should see own appointment
    $this->assertTrue($user1->can('view', $appt1));

    // Should NOT see other staff's appointment
    $this->assertFalse($user1->can('view', $appt2));
}
```

---

## Penetration Testing Recommendations

### Phase 1: Authentication & Authorization Testing

**Tools**: Burp Suite, OWASP ZAP

1. **Panel Access Bypass**
   - Attempt to access `/admin` as company_owner
   - Attempt to access `/portal` as super_admin
   - Test direct URL access without authentication
   - Test session replay attacks

2. **Multi-Tenant Isolation**
   - Create users in Company A and Company B
   - Attempt to view Company B data while authenticated as Company A user
   - Test API endpoints for tenant isolation
   - Fuzz company_id parameters

3. **Role Escalation**
   - Test company_staff attempting to access company_owner features
   - Test company_manager accessing other branches
   - Modify role parameters in requests

### Phase 2: Session Security Testing

1. **Session Hijacking**
   - Capture session cookies
   - Test cookie reuse across panels
   - Test session fixation vulnerabilities
   - Test concurrent sessions

2. **CSRF Protection**
   - Submit forms without CSRF tokens
   - Test token reuse
   - Test token expiration

### Phase 3: Data Leakage Testing

1. **SQL Injection**
   - Fuzz all input fields
   - Test search filters
   - Test sorting/filtering parameters

2. **Information Disclosure**
   - Test error messages for sensitive info
   - Check API responses for excessive data
   - Test export functionality for data leakage

### Phase 4: Business Logic Testing

1. **Policy Bypass**
   - Test appointment viewing across companies
   - Test call recording access
   - Test branch isolation

2. **Rate Limiting**
   - Test login brute force
   - Test API endpoint abuse
   - Test resource exhaustion

---

## Security Metrics & Monitoring

### KPIs to Track

1. **Authorization Failures**
   - Policy denials per day
   - Failed panel access attempts
   - Cross-tenant access attempts

2. **Session Security**
   - Concurrent sessions per user
   - Session hijacking attempts
   - Invalid CSRF token submissions

3. **Data Access Patterns**
   - Queries bypassing CompanyScope
   - Direct model queries without policies
   - Super admin access to customer data

### Recommended Monitoring

```php
// Log all policy denials
Gate::after(function ($user, $ability, $result, $arguments) {
    if ($result === false) {
        Log::warning('Policy denied', [
            'user_id' => $user->id,
            'ability' => $ability,
            'model' => $arguments[0] ?? null,
            'ip' => request()->ip(),
        ]);
    }
});

// Log panel access attempts
Event::listen(PanelServing::class, function (PanelServing $event) {
    Log::info('Panel access', [
        'panel_id' => $event->panel->getId(),
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
    ]);
});
```

---

## Compliance Considerations

### GDPR (EU)
- ✅ Multi-tenant isolation prevents cross-company data access
- ✅ Branch isolation limits data exposure
- ⚠️ Need data export restrictions for GDPR compliance
- ⚠️ Need audit logging for data access

### SOC 2 (Security)
- ⚠️ Missing: Audit logs for all data access
- ⚠️ Missing: Automated security testing in CI/CD
- ✅ Role-based access control implemented
- ✅ Session security configured

### HIPAA (Healthcare - if applicable)
- ✅ Multi-tenant isolation
- ⚠️ Missing: Encryption at rest verification
- ⚠️ Missing: Audit logging
- ✅ Access control policies

---

## Deployment Security Checklist

### Pre-Deployment (Local/Staging)

- [ ] All CRITICAL vulnerabilities fixed
- [ ] Panel access control tested
- [ ] RetellCallSessionPolicy created and tested
- [ ] BelongsToCompany trait added to RetellCallSession
- [ ] All policies updated with customer portal roles
- [ ] Automated tests passing
- [ ] Manual penetration testing completed

### Deployment

- [ ] Feature flag `FEATURE_CUSTOMER_PORTAL=false` initially
- [ ] Deploy to production
- [ ] Verify no regressions in admin panel
- [ ] Enable for pilot customers only
- [ ] Monitor security logs for anomalies

### Post-Deployment

- [ ] Enable for 2-3 pilot customers
- [ ] Monitor authorization failures
- [ ] Review security logs daily
- [ ] Collect feedback on access issues
- [ ] Gradual rollout to all customers

---

## Conclusion

The Customer Portal implementation requires **immediate security hardening** before deployment. The current state has critical vulnerabilities that could lead to:

1. **Unauthorized admin panel access** by customers
2. **Multi-tenant data leakage** due to missing scoping
3. **Privilege escalation** due to missing branch isolation

**Recommendation**: **DO NOT DEPLOY** until all CRITICAL findings are remediated.

**Estimated Effort**:
- Critical fixes: 8-16 hours
- High priority fixes: 16-24 hours
- Testing & validation: 8 hours
- **Total**: 32-48 hours (4-6 days)

**Next Steps**:
1. Fix VULN-PORTAL-001 (panel access control) - IMMEDIATE
2. Create RetellCallSessionPolicy - IMMEDIATE
3. Add BelongsToCompany trait - IMMEDIATE
4. Add users.branch_id migration - HIGH PRIORITY
5. Update existing policies - HIGH PRIORITY
6. Comprehensive testing - REQUIRED BEFORE LAUNCH

---

**Auditor**: Claude (Security Auditor Persona)
**Report Version**: 1.0
**Classification**: INTERNAL - SECURITY CRITICAL
