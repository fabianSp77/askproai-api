# Customer Portal Tests - Quick Start Guide

## 🚀 Run All Tests

```bash
vendor/bin/pest tests/Unit/CustomerPortal/
```

## 📋 Individual Test Files

```bash
# Branch Policy (12 tests)
vendor/bin/pest tests/Unit/CustomerPortal/BranchPolicyTest.php

# Staff Policy (14 tests)
vendor/bin/pest tests/Unit/CustomerPortal/StaffPolicyTest.php

# Customer Policy (16 tests - includes VULN-005 fix)
vendor/bin/pest tests/Unit/CustomerPortal/CustomerPolicyTest.php

# Appointment Policy (15 tests)
vendor/bin/pest tests/Unit/CustomerPortal/AppointmentPolicyTest.php
```

## 🔍 Critical Tests

### Branch Isolation (company_manager)
```bash
vendor/bin/pest tests/Unit/CustomerPortal/BranchPolicyTest.php \
  --filter="company_manager_can_view_only_assigned_branch"
```

### Staff Self-Access (company_staff)
```bash
vendor/bin/pest tests/Unit/CustomerPortal/StaffPolicyTest.php \
  --filter="company_staff_can_view_only_own_profile"
```

### VULN-005 Security Fix
```bash
vendor/bin/pest tests/Unit/CustomerPortal/CustomerPolicyTest.php \
  --filter="vuln_005"
```

### Read-Only Phase 1
```bash
vendor/bin/pest tests/Unit/CustomerPortal/ \
  --filter="cannot_create"
```

## 📊 Test Coverage Summary

| Policy | Tests | Key Focus |
|--------|-------|-----------|
| Branch | 12 | Branch isolation for managers |
| Staff | 14 | Self-access isolation for staff |
| Customer | 16 | Staff assignment + VULN-005 fix |
| Appointment | 15 | Complete 5-level isolation |
| **Total** | **57** | **Multi-tenancy security** |

## ✅ What's Tested

- [x] 5-level access control cascade
- [x] Multi-tenancy company isolation
- [x] Branch isolation (company_manager)
- [x] Staff self-access (company_staff)
- [x] Customer assignment isolation
- [x] VULN-005 fix (preferred_staff_id)
- [x] Phase 1 read-only restrictions
- [x] Admin bypass functionality

## 🛠️ Troubleshooting

### Tests Fail with Migration Errors
```bash
# Reset database and re-run
php artisan migrate:fresh --force
vendor/bin/pest tests/Unit/CustomerPortal/
```

### Individual Test Fails
```bash
# Run with verbose output
vendor/bin/pest tests/Unit/CustomerPortal/BranchPolicyTest.php -v
```

### Check Specific Assertion
```bash
# Run single test method
vendor/bin/pest tests/Unit/CustomerPortal/ \
  --filter="test_company_manager_can_view_only_assigned_branch"
```

## 📖 Full Documentation

See `/tests/Unit/CustomerPortal/README.md` for complete documentation.
