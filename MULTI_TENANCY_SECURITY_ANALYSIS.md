# Multi-Tenancy Security Analysis Report

**Date:** June 27, 2025  
**System:** AskProAI API Gateway  
**Analysis Type:** Multi-Tenancy Security Audit

## Executive Summary

The AskProAI platform implements multi-tenancy through global scopes and company_id fields. While the core implementation follows good practices, several critical vulnerabilities exist that could allow cross-tenant data access.

## ✅ Security Strengths

### 1. **Proper Global Scope Implementation**
- `CompanyScope` and `TenantScope` are correctly implemented
- Scopes are automatically applied to models using the `BelongsToCompany` trait
- Company ID is derived from authenticated user, NOT from request headers

### 2. **Header Security**
- `ValidateCompanyContext` middleware logs and rejects `X-Company-Id` headers
- `CompanyScope` logs warnings if header-based company ID is attempted
- Headers are removed to prevent accidental usage

### 3. **Creating/Updating Protection**
- Models validate company_id on creation matches current user's company
- Updating company_id after creation is blocked
- Proper exception handling for unauthorized access attempts

### 4. **Test Coverage**
- Comprehensive `MultiTenancyIsolationTest` verifies tenant isolation
- Tests cover customers, appointments, services, calls, and cross-tenant access prevention
- Super admin access is properly tested

## 🚨 Critical Security Vulnerabilities

### 1. **BelongsToCompany Trait Falls Back to Headers** ⚠️
```php
// Line 59-61 in BelongsToCompany.php
if ($companyId = request()->header('X-Company-ID')) {
    return (int) $companyId;
}
```
**Risk:** Despite CompanyScope rejecting headers, the trait still checks for them as a fallback
**Impact:** HIGH - Could allow tenant bypass if middleware is disabled

### 2. **Webhook Processing Bypasses Tenant Scope** ⚠️
```php
// WebhookCompanyResolver.php extensively uses withoutGlobalScope
$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find($resolution['branch_id']);
```
**Risk:** Webhook processing can access any tenant's data
**Impact:** MEDIUM - Limited to webhook context but could expose cross-tenant data

### 3. **Default Company Fallback** ⚠️
```php
// Lines 88-100 in WebhookCompanyResolver.php
if (!$companyId) {
    $company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('is_active', true)
        ->first();
}
```
**Risk:** Falls back to first active company when resolution fails
**Impact:** HIGH - Could process data for wrong tenant

### 4. **Mobile API Accepts company_id Parameter** ⚠️
```php
// MobileAppController.php
if ($request->has('company_id')) {
    // Only validates with Gate, still allows query
    $query->where('company_id', $request->company_id);
}
```
**Risk:** While it has Gate authorization, accepting company_id from request is dangerous
**Impact:** MEDIUM - Protected by Gate but violates principle of least privilege

### 5. **Raw Queries Without Tenant Filtering** ⚠️
Multiple files use `whereRaw`, `DB::raw`, and direct SQL without ensuring tenant isolation:
- 124 files contain raw query usage
- Not all validate company_id is included in WHERE clause
**Impact:** HIGH - Could expose cross-tenant data

### 6. **Session-Based Company ID** ⚠️
```php
// Line 64-66 in BelongsToCompany.php
if ($companyId = session('company_id')) {
    return (int) $companyId;
}
```
**Risk:** Session can be manipulated or persist incorrect company context
**Impact:** MEDIUM - Session fixation could lead to wrong tenant context

## 🔍 Specific Vulnerable Patterns Found

### 1. **PhoneNumberResolver Without Scope**
```php
// Multiple files use PhoneNumber::withoutGlobalScope for resolution
PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('number', $cleanNumber)
```
**Files Affected:** 91 files use `withoutGlobalScope`

### 2. **System Jobs Without Tenant Context**
Jobs and commands that process data may not have proper tenant context set, leading to cross-tenant data access.

### 3. **Cache Keys Without Tenant Prefix**
```php
$cacheKey = "phone_company_map:{$cleanNumber}"; // No tenant isolation
```
**Risk:** Shared cache could leak information between tenants

## 📋 Recommendations

### Immediate Actions Required

1. **Remove Header/Session Fallbacks**
   - Remove lines 59-66 from `BelongsToCompany.php`
   - Only trust authenticated user's company_id

2. **Secure Webhook Processing**
   - Add tenant validation before processing
   - Never use first active company as fallback
   - Validate resolved company matches expected tenant

3. **Audit Raw Queries**
   - Review all 124 files with raw queries
   - Ensure company_id is always in WHERE clause
   - Use `SafeQueryHelper` for all dynamic queries

4. **Fix Mobile API**
   - Remove company_id from request parameters
   - Use only authenticated user's company context

5. **Tenant-Prefix Cache Keys**
   ```php
   $cacheKey = "tenant:{$companyId}:phone_map:{$cleanNumber}";
   ```

### Long-term Improvements

1. **Implement Query Builder Restrictions**
   - Override `whereRaw` to require company_id
   - Add query logging for audit trail
   - Implement query analysis middleware

2. **Tenant Context Service**
   ```php
   class TenantContext {
       public function getCompanyId(): int { /* only from auth */ }
       public function runAsCompany($id, $callback) { /* for system jobs */ }
   }
   ```

3. **Enhanced Testing**
   - Add tests for webhook tenant isolation
   - Test all raw query locations
   - Automated security scanning for new code

4. **Monitoring & Alerts**
   - Alert on cross-tenant access attempts
   - Log all `withoutGlobalScope` usage
   - Monitor for unusual access patterns

## 🎯 Priority Matrix

| Issue | Severity | Effort | Priority |
|-------|----------|--------|----------|
| Header/Session Fallback | HIGH | LOW | P0 - Fix Immediately |
| Webhook Default Company | HIGH | LOW | P0 - Fix Immediately |
| Raw Query Audit | HIGH | HIGH | P1 - This Week |
| Mobile API company_id | MEDIUM | LOW | P1 - This Week |
| Cache Key Isolation | MEDIUM | MEDIUM | P2 - This Month |
| Query Builder Restrictions | LOW | HIGH | P3 - Roadmap |

## Conclusion

While the multi-tenancy implementation has a solid foundation, several critical vulnerabilities exist that could lead to cross-tenant data exposure. The most critical issues (header fallback and webhook processing) should be addressed immediately as they pose the highest risk.

The system's reliance on `withoutGlobalScope` for system operations creates inherent risks that require careful management and additional security controls.

**Overall Security Score: 6/10** - Good foundation but critical gaps exist