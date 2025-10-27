# Customer Portal Phase 1 - Policy Unit Tests

## Overview
Comprehensive unit tests for Customer Portal Phase 1 multi-level access control policies.

## Test Coverage

### 1. BranchPolicyTest.php (12 tests)
Tests multi-level access control for Branch viewing:
- ✅ Level 1: super_admin and admin bypass (can view all branches)
- ✅ Level 2: Company isolation (CRITICAL for multi-tenancy)
- ✅ Level 3: Branch isolation (company_manager sees only assigned branch)
- ✅ Level 4: Owner/Admin access (company_owner/company_admin see all company branches)
- ✅ Phase 1 read-only (no create permissions)

**Key Tests:**
- `test_company_manager_can_view_only_assigned_branch()` - CRITICAL branch isolation test
- `test_user_without_company_cannot_view_branches()` - Multi-tenancy security
- `test_customer_portal_roles_cannot_create_branches_in_phase_1()` - Read-only verification

### 2. StaffPolicyTest.php (14 tests)
Tests multi-level access control for Staff viewing:
- ✅ Level 1: Admin bypass
- ✅ Level 2: Company isolation
- ✅ Level 3: Branch isolation (company_manager sees only branch staff)
- ✅ Level 4: Self access (company_staff sees only own profile)
- ✅ Level 5: Owner/Admin access (see all company staff)

**Key Tests:**
- `test_company_manager_can_view_only_branch_staff()` - Branch isolation for managers
- `test_company_staff_can_view_only_own_profile()` - CRITICAL staff self-access test
- `test_company_staff_cannot_view_colleagues()` - Strict staff isolation

### 3. CustomerPolicyTest.php (16 tests)
Tests multi-level access control for Customer viewing:
- ✅ Level 1: Admin bypass
- ✅ Level 2: Company isolation
- ✅ Level 3: Branch isolation (company_manager sees only branch customers)
- ✅ Level 4: Staff isolation (company_staff sees only assigned customers)
- ✅ VULN-005 fix verification (preferred_staff_id === staff.id, NOT user.id)

**Key Tests:**
- `test_company_staff_can_view_only_assigned_customers()` - Staff customer assignment
- `test_vuln_005_fix_preferred_staff_id_uses_staff_id_not_user_id()` - CRITICAL security fix
- `test_company_staff_cannot_view_unassigned_customers()` - Strict isolation

### 4. AppointmentPolicyTest.php (15 tests)
Tests multi-level access control for Appointment viewing:
- ✅ All 5 levels of access control
- ✅ Branch isolation for company_manager
- ✅ Staff isolation for company_staff
- ✅ Phase 1 read-only (no create/update/delete)

**Key Tests:**
- `test_company_manager_can_view_only_branch_appointments()` - Branch isolation
- `test_company_staff_can_view_only_their_appointments()` - Staff appointment isolation
- `test_customer_portal_roles_cannot_create_appointments_in_phase_1()` - Read-only enforcement

## Running the Tests

### Prerequisites
1. Database migrations must be working correctly
2. All factories must be available (User, Company, Branch, Staff, Customer, Service, Appointment)
3. Spatie Permissions package installed with Role model

### Execute Tests

```bash
# Run all Customer Portal tests
vendor/bin/pest tests/Unit/CustomerPortal/

# Run individual policy tests
vendor/bin/pest tests/Unit/CustomerPortal/BranchPolicyTest.php
vendor/bin/pest tests/Unit/CustomerPortal/StaffPolicyTest.php
vendor/bin/pest tests/Unit/CustomerPortal/CustomerPolicyTest.php
vendor/bin/pest tests/Unit/CustomerPortal/AppointmentPolicyTest.php

# Run with PHPUnit
vendor/bin/phpunit tests/Unit/CustomerPortal/ --testdox

# Run specific test
vendor/bin/pest tests/Unit/CustomerPortal/BranchPolicyTest.php --filter="company_manager_can_view_only_assigned_branch"
```

### Known Issues
If tests fail with migration errors, ensure:
1. Test database is properly configured
2. All migrations run successfully without foreign key constraint errors
3. RefreshDatabase trait is working correctly

## Test Structure

Each test file follows this pattern:

```php
<?php
namespace Tests\Unit\CustomerPortal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Policies\{PolicyName};
use Spatie\Permission\Models\Role;

class {PolicyName}Test extends TestCase
{
    use RefreshDatabase;

    protected {PolicyName} $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new {PolicyName}();
        $this->seedRoles();
    }

    // Test methods...
}
```

## Test Coverage Summary

| Policy | Total Tests | Positive Cases | Negative Cases | Security Tests |
|--------|-------------|----------------|----------------|----------------|
| BranchPolicy | 12 | 6 | 4 | 2 |
| StaffPolicy | 14 | 7 | 5 | 2 |
| CustomerPolicy | 16 | 8 | 6 | 2 |
| AppointmentPolicy | 15 | 8 | 5 | 2 |
| **TOTAL** | **57** | **29** | **20** | **8** |

## Security Testing Focus

### Multi-Tenancy Isolation (CRITICAL)
- ✅ Users without company_id cannot view any resources
- ✅ Users can ONLY view resources from their company
- ✅ Company isolation works across all 4 policies

### Branch Isolation
- ✅ company_manager can ONLY view resources in assigned branch
- ✅ Branch isolation works even within same company

### Staff Isolation
- ✅ company_staff can ONLY view own profile
- ✅ company_staff can ONLY view assigned customers (preferred_staff_id match)
- ✅ company_staff can ONLY view own appointments (staff_id match)

### VULN-005 Fix Verification
- ✅ Customer.preferred_staff_id correctly matches Staff.id (NOT User.id)
- ✅ Policy checks user.staff_id === customer.preferred_staff_id

## Phase 1 Read-Only Verification

All tests verify that customer portal roles have NO create/update/delete permissions:
- ✅ company_owner: Read-only
- ✅ company_admin: Read-only
- ✅ company_manager: Read-only
- ✅ company_staff: Read-only

Admin panel roles (admin, manager, staff, receptionist) maintain full permissions.

## Next Steps (Phase 2)

When implementing Phase 2 (write permissions), update tests to verify:
- company_owner can create/update/delete
- company_manager can update within branch
- company_staff remains read-only for most resources

## Files

```
tests/Unit/CustomerPortal/
├── README.md (this file)
├── BranchPolicyTest.php (12 tests)
├── StaffPolicyTest.php (14 tests)
├── CustomerPolicyTest.php (16 tests)
└── AppointmentPolicyTest.php (15 tests)
```

## Contact

For questions or issues with these tests, refer to:
- Policy implementations: `app/Policies/`
- Security audit: `claudedocs/06_SECURITY/CUSTOMER_PORTAL_SECURITY_AUDIT_2025-10-26.md`
- Deployment docs: `CUSTOMER_PORTAL_SECURITY_AUDIT_2025-10-26.md`
