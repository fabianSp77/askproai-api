# Customer Portal Phase 1 - Testing Guide

**Date**: 2025-10-26
**Version**: 1.0
**Status**: Ready for Testing

---

## 🎯 Testing Overview

This guide provides step-by-step instructions for testing the Customer Portal Phase 1 implementation, including multi-level access control, policy isolation, and UI functionality.

---

## 📋 Pre-Testing Setup

### Option 1: Use Test Data Seeder (Recommended for Development)

```bash
# Create test company, branches, staff, and users
php artisan db:seed --class=CustomerPortalTestDataSeeder

# Expected output:
# ✅ Company created: Test Portal GmbH
# ✅ Created 2 branches
# ✅ Created 4 staff members
# ✅ Created 5 test users
```

**Test Users Created**:
| Email | Role | Access Level |
|-------|------|--------------|
| owner@testportal.de | company_owner | All company data |
| admin@testportal.de | company_admin | All company data |
| manager.main@testportal.de | company_manager | Hauptfiliale only |
| manager.mitte@testportal.de | company_manager | Filiale Mitte only |
| anna.schmidt@testportal.de | company_staff | Own data only |

**Password for all test users**: `password`

### Option 2: Assign Existing Users (Production/Staging)

```bash
# Execute SQL script to assign branch_id/staff_id
mysql -u root -p askproai_db < database/seeders/AssignCustomerPortalRelations.sql

# Verify assignments
mysql -u root -p askproai_db -e "
SELECT u.email, r.name AS role, b.name AS branch, s.name AS staff
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
LEFT JOIN branches b ON u.branch_id = b.id
LEFT JOIN staff s ON u.staff_id = s.id
WHERE r.name IN ('company_owner', 'company_admin', 'company_manager', 'company_staff')
ORDER BY r.name, u.email;
"
```

---

## 🧪 Test Scenarios

### Test 1: Panel Access Control

**Objective**: Verify users can only access the appropriate panel based on their roles

**Steps**:
1. Try to access `/portal` without logging in
   - ✅ Expected: Redirect to login
2. Login as `owner@testportal.de` / `password`
   - ✅ Expected: Redirected to `/portal` dashboard
3. Logout and login as an admin user (without customer portal roles)
   - ✅ Expected: Cannot access `/portal` (403 or redirect to /admin)

**Success Criteria**:
- ✅ Customer portal accessible only to users with customer portal roles
- ✅ Non-customer users cannot access `/portal`

---

### Test 2: Company Isolation

**Objective**: Verify users can only see data from their own company

**Setup**:
- Ensure you have users from at least 2 different companies
- Both companies should have appointments/calls/customers

**Steps**:
1. Login as `owner@testportal.de` (Test Portal GmbH)
2. Navigate to Appointments → List
   - ✅ Expected: See only appointments from Test Portal GmbH
3. Check URL manipulation: Try to access appointment from another company
   - Navigate to any appointment, note the UUID
   - Manually change UUID in URL to appointment from different company
   - ✅ Expected: 403 Forbidden or "Not Found"

**Success Criteria**:
- ✅ All resources show only company-specific data
- ✅ Cannot access other company data via URL manipulation

---

### Test 3: Branch Isolation (company_manager)

**Objective**: Verify company_manager users only see data from their assigned branch

**Steps**:
1. Login as `manager.main@testportal.de` (assigned to Hauptfiliale)
2. Navigate to Appointments → List
   - ✅ Expected: See only appointments from Hauptfiliale
   - ❌ Should NOT see appointments from Filiale Mitte
3. Navigate to Customers → List
   - ✅ Expected: See only customers with branch_id = Hauptfiliale
4. Navigate to Call History → List
   - ✅ Expected: See only calls from Hauptfiliale

**Verification Query**:
```sql
-- Check branch_id assignment
SELECT
    u.email,
    u.branch_id,
    b.name AS assigned_branch
FROM users u
LEFT JOIN branches b ON u.branch_id = b.id
WHERE u.email = 'manager.main@testportal.de';
```

**Success Criteria**:
- ✅ Manager sees ONLY data from assigned branch
- ✅ No data from other branches visible
- ✅ Branch filter automatically applied

---

### Test 4: Staff Isolation (company_staff)

**Objective**: Verify company_staff users only see their own data

**Steps**:
1. Login as `anna.schmidt@testportal.de` (company_staff)
2. Navigate to Appointments → List
   - ✅ Expected: See only appointments where staff_id = Anna Schmidt's staff.id
   - ❌ Should NOT see appointments assigned to other staff
3. Navigate to Customers → List
   - ✅ Expected: See only customers where preferred_staff_id = Anna Schmidt's staff.id
4. Navigate to Call History → List
   - ✅ Expected: See only calls handled by Anna Schmidt

**Verification Query**:
```sql
-- Check staff_id assignment
SELECT
    u.email,
    u.staff_id,
    s.name AS assigned_staff
FROM users u
LEFT JOIN staff s ON u.staff_id = s.id
WHERE u.email = 'anna.schmidt@testportal.de';

-- Check appointments visible to staff
SELECT
    a.id,
    a.customer_id,
    a.staff_id,
    s.name AS staff_name
FROM appointments a
LEFT JOIN staff s ON a.staff_id = s.id
WHERE a.staff_id = (
    SELECT staff_id FROM users WHERE email = 'anna.schmidt@testportal.de'
);
```

**Success Criteria**:
- ✅ Staff sees ONLY own appointments/calls/customers
- ✅ Cannot see data from other staff members
- ✅ Staff filter automatically applied

---

### Test 5: Owner/Admin Access

**Objective**: Verify company_owner and company_admin see all company data

**Steps**:
1. Login as `owner@testportal.de`
2. Navigate to Appointments → List
   - ✅ Expected: See ALL company appointments (all branches)
3. Navigate to Customers → List
   - ✅ Expected: See ALL company customers (all branches, all staff)
4. Navigate to Call History → List
   - ✅ Expected: See ALL company calls

**Comparison**:
- Compare count of appointments visible to owner vs. manager
- Owner should see MORE data than manager

**Success Criteria**:
- ✅ Owner/Admin see all company data
- ✅ No branch/staff restrictions applied
- ✅ More data visible than manager/staff

---

### Test 6: Resource Actions (Read-Only Phase 1)

**Objective**: Verify customer portal is read-only in Phase 1

**Steps**:
1. Login as any customer portal user
2. Navigate to Appointments → List
   - ✅ Expected: "View" action available
   - ❌ Expected: NO "Create" button
   - ❌ Expected: NO "Edit" action
   - ❌ Expected: NO "Delete" action
3. Try to access create/edit URLs directly:
   - `/portal/appointments/create`
   - `/portal/appointments/{id}/edit`
   - ✅ Expected: 403 Forbidden (policy denial)

**Resources to Test**:
- Appointments
- Customers
- Call History
- Callback Requests
- Customer Notes
- Invoices
- Transactions

**Success Criteria**:
- ✅ All resources are read-only
- ✅ No create/edit/delete actions available
- ✅ Direct URL access to create/edit blocked by policies

---

### Test 7: Dashboard Widgets

**Objective**: Verify dashboard widgets show filtered data

**Steps**:
1. Login as `manager.main@testportal.de`
2. View Dashboard
3. Check each widget:
   - **Recent Appointments Widget**
     - ✅ Shows only Hauptfiliale appointments
   - **Recent Calls Widget**
     - ✅ Shows only Hauptfiliale calls
   - **Balance Overview Widget**
     - ✅ Shows company balance (not branch-specific)
   - **Outstanding Invoices Widget**
     - ✅ Shows company invoices

**Success Criteria**:
- ✅ Widgets respect user's access level
- ✅ Branch-specific widgets filter correctly
- ✅ No data leakage from other branches

---

### Test 8: Policy Denial Logging

**Objective**: Verify policy denials are logged for security monitoring

**Steps**:
1. Login as `manager.main@testportal.de`
2. Try to access an appointment from Filiale Mitte (different branch)
   - Get appointment ID from other branch
   - Navigate to `/portal/appointments/{id}`
   - ✅ Expected: 403 Forbidden
3. Check logs:
```bash
grep "Policy denied" storage/logs/laravel.log
```

**Expected Log Entry**:
```
[timestamp] Policy denied: App\Policies\AppointmentPolicy::view
User: manager.main@testportal.de (ID: uuid)
Resource: Appointment ID: uuid
Reason: Branch isolation (user.branch_id != appointment.branch_id)
```

**Success Criteria**:
- ✅ Policy denials are logged
- ✅ Logs include user, resource, reason
- ✅ Can audit unauthorized access attempts

---

### Test 9: Performance Testing

**Objective**: Verify policy checks don't cause N+1 queries

**Steps**:
1. Enable query logging:
```php
// In AppServiceProvider or config
DB::listen(function($query) {
    logger()->info($query->sql, $query->bindings);
});
```

2. Login as `manager.main@testportal.de`
3. Navigate to Appointments → List
4. Count queries in log
   - ✅ Expected: ~5-10 queries total (with eager loading)
   - ❌ Should NOT see 1 query per appointment (N+1 problem)

**Check Index Usage**:
```sql
EXPLAIN SELECT * FROM appointments
WHERE branch_id = 'uuid-of-branch';

-- Expected: Uses users_branch_id_index
```

**Success Criteria**:
- ✅ No N+1 query problems
- ✅ Indexes used for branch_id/staff_id queries
- ✅ Page loads in < 500ms

---

### Test 10: Backward Compatibility

**Objective**: Verify old admin panel roles still work

**Steps**:
1. Login as admin user with 'admin' role (not 'company_owner')
2. Access `/admin` panel
   - ✅ Expected: Full access to admin panel
3. Try to access `/portal`
   - ❌ Expected: Denied (admin doesn't have customer portal role)
4. Verify policies still allow admin bypass:
   - Create/edit/delete operations work for admin
   - Admin can see all companies (not just own)

**Success Criteria**:
- ✅ Admin panel unchanged
- ✅ Old roles work as before
- ✅ No breaking changes to existing functionality

---

## 🐛 Troubleshooting

### Issue: User sees 403 on customer portal dashboard

**Possible Causes**:
1. User doesn't have customer portal role
2. User's company_id is NULL
3. Panel access method not implemented

**Debug**:
```sql
-- Check user roles and company
SELECT
    u.email,
    u.company_id,
    GROUP_CONCAT(r.name) AS roles
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'user@example.com'
GROUP BY u.id;
```

**Solution**:
```php
// Assign role
$user->assignRole('company_owner');

// Assign company
$user->update(['company_id' => $company->id]);
```

---

### Issue: company_manager sees all branches

**Possible Causes**:
1. branch_id not assigned
2. Policy not checking branch_id
3. Wrong branch_id assigned

**Debug**:
```sql
-- Check branch assignment
SELECT u.email, u.branch_id, b.name
FROM users u
LEFT JOIN branches b ON u.branch_id = b.id
WHERE u.email = 'manager@example.com';
```

**Solution**:
```sql
-- Assign correct branch
UPDATE users
SET branch_id = 'uuid-of-branch'
WHERE email = 'manager@example.com';
```

---

### Issue: company_staff sees all customers

**Possible Causes**:
1. staff_id not assigned
2. preferred_staff_id points to wrong table
3. Policy not checking staff_id

**Debug**:
```sql
-- Check staff assignment
SELECT u.email, u.staff_id, s.name
FROM users u
LEFT JOIN staff s ON u.staff_id = s.id
WHERE u.email = 'staff@example.com';

-- Check customer assignments
SELECT c.id, c.name, c.preferred_staff_id, s.name AS staff_name
FROM customers c
LEFT JOIN staff s ON c.preferred_staff_id = s.id
WHERE c.preferred_staff_id IS NOT NULL;
```

**Solution**:
```sql
-- Assign staff_id to user
UPDATE users
SET staff_id = (SELECT id FROM staff WHERE email = 'staff@example.com')
WHERE email = 'staff@example.com';
```

---

## 📊 Test Results Template

Copy this template to document your test results:

```markdown
# Customer Portal Phase 1 - Test Results

**Date**: 2025-XX-XX
**Tester**: [Your Name]
**Environment**: [Development/Staging/Production]

## Test Summary

| Test | Status | Notes |
|------|--------|-------|
| Panel Access Control | ✅ Pass | All users redirected correctly |
| Company Isolation | ✅ Pass | No cross-company data visible |
| Branch Isolation | ⚠️ Partial | Manager sees some other branch data |
| Staff Isolation | ✅ Pass | Staff sees only own data |
| Owner/Admin Access | ✅ Pass | Full company data visible |
| Read-Only Actions | ✅ Pass | No create/edit/delete available |
| Dashboard Widgets | ✅ Pass | All widgets filter correctly |
| Policy Logging | ✅ Pass | Denials logged successfully |
| Performance | ✅ Pass | No N+1 queries, <500ms load time |
| Backward Compatibility | ✅ Pass | Admin panel unchanged |

## Issues Found

1. **Branch Isolation Partial Failure**
   - Severity: Medium
   - Description: manager.main sees 3 appointments from Filiale Mitte
   - Reproduction: Login as manager.main, view appointments list
   - Root Cause: appointments.branch_id NULL for 3 records
   - Fix: Update appointments.branch_id for orphaned records

## Recommendations

1. Add automated tests for all policies
2. Create E2E tests with Puppeteer
3. Add performance benchmarks
4. Enable feature flag for pilot customers

## Next Steps

- [ ] Fix branch_id NULL issues
- [ ] Re-test after fixes
- [ ] Document final results
- [ ] Approve for pilot rollout
```

---

## 🚀 Go-Live Checklist

Before enabling feature flag:

- [ ] All test scenarios pass
- [ ] No critical issues found
- [ ] Performance meets requirements (<500ms)
- [ ] Policy denials logged correctly
- [ ] Backward compatibility verified
- [ ] Documentation complete
- [ ] Pilot customers selected
- [ ] Rollback plan ready
- [ ] Monitoring dashboard set up
- [ ] Support team trained

---

## 📞 Support

**Issues Found**:
- Create issue in GitHub/JIRA
- Include test scenario number
- Attach logs and screenshots
- Tag as `customer-portal` + `phase-1`

**Logs**:
```bash
# Application logs
tail -f storage/logs/laravel.log

# Policy denials
grep "Policy denied" storage/logs/laravel.log | tail -20

# Database queries
grep "SELECT" storage/logs/laravel.log | grep "appointments"
```

---

**Created**: 2025-10-26
**Version**: 1.0
**Status**: Ready for Testing
