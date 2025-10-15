# Customer Data Integrity Fix - Test Execution Plan

## Executive Summary

This test plan validates the fix for the critical data integrity issue where 31/60 customers had NULL `company_id`, bypassing multi-tenant isolation (CVSS 9.1 vulnerability).

**Issue**: NULL `company_id` values in customers table
**Impact**: Customers visible across company boundaries, security breach
**Fix**: Backfill migration + NOT NULL constraint + factory safeguards
**Test Coverage**: 100+ test cases across 10 test suites

---

## Test Suite Overview

| # | Test Suite | File | Purpose | Priority |
|---|------------|------|---------|----------|
| 1 | Pre-Backfill Validation | `CustomerCompanyIdValidationTest.php` | Document current broken state | CRITICAL |
| 2 | Backfill Migration | `BackfillCustomerCompanyIdTest.php` | Validate migration logic | CRITICAL |
| 3 | Post-Backfill Validation | `CustomerCompanyIdBackfillValidationTest.php` | Verify fix completion | CRITICAL |
| 4 | Constraint Enforcement | `CustomerCompanyIdConstraintTest.php` | Prevent future NULL values | CRITICAL |
| 5 | Security Regression | `CustomerIsolationTest.php` | Ensure isolation maintained | CRITICAL |
| 6 | Integration Testing | `CustomerManagementTest.php` | End-to-end workflows | HIGH |
| 7 | Performance Testing | `CustomerCompanyIdBackfillPerformanceTest.php` | No performance degradation | HIGH |
| 8 | Monitoring & Alerting | `CustomerDataIntegrityMonitoringTest.php` | Ongoing validation | MEDIUM |

**Total Test Count**: 82 automated tests
**Estimated Execution Time**: 5-10 minutes

---

## Phase 1: Pre-Deployment Testing (Staging)

### Step 1.1: Pre-Backfill Validation

**Purpose**: Document the current state and extent of the issue

```bash
# Run pre-backfill validation tests
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdValidationTest.php

# Expected Results:
# - Identifies all customers with NULL company_id
# - Validates which have appointments (should be backfilled)
# - Identifies orphaned customers (should be soft deleted)
# - Detects any customers with appointments from multiple companies
```

**Success Criteria**:
- All tests pass
- Report shows exact count of NULL customers
- No unexpected data patterns found

### Step 1.2: Backfill Migration Testing

**Purpose**: Validate the migration logic before running in production

```bash
# Run migration tests
php artisan test tests/Unit/Migrations/BackfillCustomerCompanyIdTest.php

# Expected Results:
# - Backup table creation verified
# - Company ID inference from appointments works
# - Multiple company conflicts resolved correctly
# - Orphaned customers soft deleted
# - Rollback capability verified
```

**Success Criteria**:
- All 8 migration tests pass
- Backup mechanism validated
- Rollback tested successfully

### Step 1.3: Constraint Enforcement Testing

**Purpose**: Ensure future NULL values are prevented

```bash
# Run constraint tests
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdConstraintTest.php

# Note: Some tests are marked as skipped until constraint is added
# Enable them after migration adds NOT NULL constraint
```

**Success Criteria**:
- Factory validation works
- Trait auto-fill verified
- Mass assignment protection confirmed

---

## Phase 2: Migration Execution (Production)

### Step 2.1: Pre-Migration Validation

**Execute on Production Database**:

```bash
# Run validation command
php artisan customers:validate-integrity --detailed

# Expected Output:
# - Current count of NULL company_id customers
# - Identification of orphaned vs. backfillable customers
# - Pre-migration integrity report
```

### Step 2.2: Create Database Backup

**CRITICAL**: Full database backup before migration

```bash
# Create backup
php artisan backup:run

# Verify backup
php artisan backup:list

# Test restore procedure (on staging)
php artisan backup:restore --latest
```

### Step 2.3: Execute Migration

**Migration File**: `database/migrations/YYYY_MM_DD_backfill_customer_company_id.php`

```bash
# Dry run (staging)
php artisan migrate --pretend

# Execute migration (production)
php artisan migrate --force

# Monitor execution
tail -f storage/logs/laravel.log
```

**Expected Duration**: <30 seconds for 60 customers

### Step 2.4: Post-Migration Validation

```bash
# Run post-backfill validation tests
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdBackfillValidationTest.php

# Run validation command
php artisan customers:validate-integrity --detailed --alert-on-failure
```

**Success Criteria**:
- Zero NULL company_id values remain
- All companies valid
- Customer-appointment alignment verified
- No data loss detected

---

## Phase 3: Post-Deployment Validation

### Step 3.1: Security Regression Testing

**Purpose**: Ensure multi-tenant isolation still works

```bash
# Run security regression tests
php artisan test tests/Feature/Security/CustomerIsolationTest.php

# Expected Results:
# - Users can only see their company's customers
# - CompanyScope functioning correctly
# - Policies enforcing boundaries
# - API endpoints respecting isolation
```

**Success Criteria**:
- All 8 security tests pass
- No cross-tenant data leakage
- Super admin access preserved

### Step 3.2: Integration Testing

**Purpose**: Validate end-to-end customer operations

```bash
# Run integration tests
php artisan test tests/Feature/Integration/CustomerManagementTest.php

# Test scenarios:
# - Create customer (company_id auto-filled)
# - List customers (scoped correctly)
# - Update customer (company_id preserved)
# - Delete customer (scope respected)
# - Restore customer (company maintained)
```

**Success Criteria**:
- All CRUD operations work
- company_id automatically set
- Scope applied consistently

### Step 3.3: Performance Validation

**Purpose**: Ensure no performance degradation

```bash
# Run performance tests
php artisan test tests/Performance/CustomerCompanyIdBackfillPerformanceTest.php

# Metrics to validate:
# - Query execution time <500ms for 1000 records
# - No N+1 queries introduced
# - Database indexes functioning
```

**Success Criteria**:
- Query performance acceptable
- No performance regressions
- Indexes being utilized

---

## Phase 4: Ongoing Monitoring

### Daily Validation

**Schedule**: Run daily at 2 AM

```bash
# Add to cron or scheduler
# app/Console/Kernel.php

$schedule->command('customers:validate-integrity --alert-on-failure')
         ->dailyAt('02:00')
         ->emailOutputOnFailure('devops@company.com');
```

### Monitoring Dashboard

**Metrics to Track**:
- Total customers with company_id: 100%
- Invalid company references: 0
- Customer-appointment mismatches: 0
- CompanyScope effectiveness: 100%

### Alerting Rules

**Critical Alerts** (Page immediately):
- NULL company_id detected
- Invalid company references found
- Cross-tenant data access detected

**Warning Alerts** (Email notification):
- Customer-appointment company mismatches
- Orphaned appointments detected
- CompanyScope inconsistencies

---

## CI/CD Integration

### GitHub Actions Workflow

```yaml
# .github/workflows/data-integrity-tests.yml
name: Data Integrity Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2

      - name: Install Dependencies
        run: composer install

      - name: Run Data Integrity Tests
        run: |
          php artisan test tests/Feature/DataIntegrity/
          php artisan test tests/Feature/Security/CustomerIsolationTest.php
          php artisan test tests/Feature/Integration/CustomerManagementTest.php

      - name: Run Validation Command
        run: php artisan customers:validate-integrity
```

### Pre-Deployment Checks

**Required to Pass Before Deployment**:

```bash
# Run full test suite
php artisan test

# Run specific integrity tests
php artisan test tests/Feature/DataIntegrity/
php artisan test tests/Feature/Security/CustomerIsolationTest.php

# Run validation command
php artisan customers:validate-integrity

# Check factory safeguards
php artisan test --filter CustomerFactory
```

**All must return exit code 0**

---

## Test Execution Commands

### Run All Data Integrity Tests

```bash
# Full test suite
php artisan test tests/Feature/DataIntegrity/ tests/Feature/Security/CustomerIsolationTest.php tests/Feature/Integration/CustomerManagementTest.php

# With coverage
php artisan test --coverage --min=80
```

### Run Specific Test Suites

```bash
# Pre-backfill validation
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdValidationTest.php

# Migration tests
php artisan test tests/Unit/Migrations/BackfillCustomerCompanyIdTest.php

# Post-backfill validation
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdBackfillValidationTest.php

# Constraint enforcement
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdConstraintTest.php

# Security regression
php artisan test tests/Feature/Security/CustomerIsolationTest.php

# Integration
php artisan test tests/Feature/Integration/CustomerManagementTest.php

# Performance
php artisan test tests/Performance/CustomerCompanyIdBackfillPerformanceTest.php

# Monitoring
php artisan test tests/Feature/Monitoring/CustomerDataIntegrityMonitoringTest.php
```

### Run Individual Test Cases

```bash
# Run specific test
php artisan test --filter test_identifies_all_null_company_id_customers

# Run with detailed output
php artisan test tests/Feature/DataIntegrity/CustomerCompanyIdValidationTest.php --testdox
```

---

## Test Coverage Report

### Expected Coverage

| Component | Coverage Target | Actual |
|-----------|----------------|--------|
| Customer Model | 100% | - |
| CustomerFactory | 100% | - |
| BelongsToCompany Trait | 100% | - |
| CompanyScope | 100% | - |
| CustomerPolicy | 100% | - |
| Migration Logic | 100% | - |
| Validation Command | 90% | - |

### Generate Coverage Report

```bash
# Generate HTML coverage report
php artisan test --coverage-html coverage/

# View report
open coverage/index.html

# Check minimum coverage threshold
php artisan test --coverage --min=80
```

---

## Rollback Procedure

### If Migration Fails

**Step 1**: Stop migration immediately
```bash
# If migration is running
Ctrl+C
```

**Step 2**: Rollback migration
```bash
php artisan migrate:rollback --step=1
```

**Step 3**: Restore from backup table
```bash
# The migration should have created: customers_backup_before_company_id_backfill
# Restore script should be provided in migration
php artisan db:restore-customer-backup
```

**Step 4**: Verify rollback
```bash
php artisan customers:validate-integrity
```

### If Post-Deployment Issues Detected

**Step 1**: Assess severity
```bash
php artisan customers:validate-integrity --detailed
```

**Step 2**: If critical issues found
```bash
# Restore from backup
php artisan backup:restore --latest

# Verify restore
php artisan customers:validate-integrity
```

**Step 3**: Incident response
- Document issue
- Notify stakeholders
- Plan remediation

---

## Success Criteria Summary

### Pre-Deployment

- ✅ All pre-backfill validation tests pass
- ✅ Migration tests validated in staging
- ✅ Constraint enforcement tests pass
- ✅ Database backup created and verified
- ✅ Rollback procedure tested

### During Migration

- ✅ Migration completes in <30 seconds
- ✅ No errors in logs
- ✅ All 31 NULL customers processed
- ✅ Backup table created successfully

### Post-Deployment

- ✅ Zero NULL company_id values
- ✅ All post-backfill validation tests pass
- ✅ Security regression tests pass
- ✅ Integration tests pass
- ✅ Performance tests pass
- ✅ No data loss detected
- ✅ Multi-tenant isolation verified
- ✅ Monitoring command working

### Ongoing

- ✅ Daily validation command running
- ✅ Alerting configured
- ✅ Metrics dashboard updated
- ✅ CI/CD tests passing

---

## Contact Information

**Test Plan Owner**: Quality Engineering Team
**Database Admin**: DBA Team
**Security Lead**: Security Team
**On-Call**: DevOps Team

**Escalation Path**:
1. Engineering Lead
2. CTO
3. CEO (for critical security issues)

---

## Appendix A: Test File Locations

```
tests/
├── Feature/
│   ├── DataIntegrity/
│   │   ├── CustomerCompanyIdValidationTest.php
│   │   ├── CustomerCompanyIdBackfillValidationTest.php
│   │   └── CustomerCompanyIdConstraintTest.php
│   ├── Security/
│   │   └── CustomerIsolationTest.php
│   ├── Integration/
│   │   └── CustomerManagementTest.php
│   └── Monitoring/
│       └── CustomerDataIntegrityMonitoringTest.php
├── Unit/
│   └── Migrations/
│       └── BackfillCustomerCompanyIdTest.php
└── Performance/
    └── CustomerCompanyIdBackfillPerformanceTest.php
```

---

## Appendix B: Validation Command Usage

```bash
# Basic validation
php artisan customers:validate-integrity

# Detailed output
php artisan customers:validate-integrity --detailed

# With alerting
php artisan customers:validate-integrity --alert-on-failure

# Attempt automatic fixes (dry run)
php artisan customers:validate-integrity --fix-issues

# Full validation with all options
php artisan customers:validate-integrity --detailed --alert-on-failure
```

---

## Appendix C: Factory Safeguards

### Updated CustomerFactory

```php
// Always provides company_id
Customer::factory()->create();

// Explicit company
Customer::factory()->forCompany($company)->create();

// Test NULL state (testing only)
Customer::factory()->withNullCompany()->makeRaw();
```

### Validation Hooks

- `afterMaking`: Validates company_id before persistence
- `afterCreating`: Double-checks after creation
- `withNullCompany`: Only available in test environments

---

**Document Version**: 1.0
**Last Updated**: 2025-10-02
**Status**: READY FOR EXECUTION
