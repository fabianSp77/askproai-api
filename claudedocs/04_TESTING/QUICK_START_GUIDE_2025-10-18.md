# Test Automation Quick Start Guide
**Date**: 2025-10-18
**Version**: 1.0

---

## Executive Summary

This guide provides immediate steps to start running the comprehensive test automation suite for the AskPro AI Gateway appointment booking system.

**Quick Stats**:
- Test Files Created: 10+
- Coverage Targets: Unit >80%, Integration >70%, Critical Path 100%
- Performance Target: <45s booking flow (vs 144s baseline)
- RCA Issues Covered: 100%

---

## Prerequisites

### Required Software
```bash
# PHP 8.2
php --version

# Composer
composer --version

# Node.js 18+
node --version

# K6 (Performance Testing)
k6 version

# Playwright (E2E Testing)
npx playwright --version
```

### Installation

```bash
# PHP dependencies
composer install

# Node dependencies (for Playwright)
npm install

# Install Playwright browsers
npx playwright install --with-deps chromium
```

---

## Running Tests

### 1. Unit Tests (PHPUnit)

**Run all unit tests:**
```bash
vendor/bin/phpunit --testsuite=Unit
```

**Run with coverage:**
```bash
vendor/bin/phpunit --testsuite=Unit --coverage-html coverage-report
```

**Run RCA Prevention tests specifically:**
```bash
vendor/bin/phpunit tests/Unit/Services/RcaPreventionTest.php --verbose
```

**Expected Output:**
```
PHPUnit 10.x
.............                                                     13 / 13 (100%)

Time: 00:02.345, Memory: 32.00 MB

OK (13 tests, 45 assertions)
```

---

### 2. Integration Tests

**Run all integration tests:**
```bash
vendor/bin/phpunit --testsuite=Feature --filter=Integration
```

**Run specific integration test:**
```bash
vendor/bin/phpunit tests/Feature/Integration/CompleteBookingFlowTest.php
```

---

### 3. Performance Tests (K6)

**Prerequisites:**
```bash
# Start application
php artisan serve

# In another terminal, set environment
export API_URL=http://localhost:8000
```

**Run baseline performance test:**
```bash
k6 run tests/Performance/k6/baseline-booking-flow.js
```

**Run load test:**
```bash
k6 run tests/Performance/k6/load-test.js
```

**Expected Output:**
```
     ✓ booking_duration.............: avg=32.5s  p(95)=42.1s p(99)=56.3s
     ✓ booking_success..............: 95.2%
     ✓ http_req_duration............: avg=1.2s   p(95)=3.1s  p(99)=5.6s
     ✓ calcom_api_latency...........: avg=1.8s

     ✓ All thresholds passed
```

**Key Metrics to Watch:**
- `booking_duration p(95)` should be < 45s (RCA target)
- `booking_success` should be > 95%
- `calcom_api_latency avg` should be < 2s

---

### 4. E2E Tests (Playwright)

**Run all E2E tests:**
```bash
npx playwright test tests/E2E/playwright/
```

**Run with UI (interactive mode):**
```bash
npx playwright test --ui
```

**Run specific test:**
```bash
npx playwright test tests/E2E/playwright/booking-journey.spec.ts
```

**Show test report:**
```bash
npx playwright show-report
```

**Expected Output:**
```
Running 12 tests using 1 worker

  ✓ booking-journey.spec.ts:8:1 › complete booking flow (15s)
  ✓ booking-journey.spec.ts:45:1 › handles race condition (12s)
  ✓ booking-journey.spec.ts:78:1 › rejects duplicate booking (18s)

  12 passed (1.5m)
```

---

### 5. Security Tests

**Run all security tests:**
```bash
vendor/bin/phpunit --testsuite=Feature --filter=Security
```

**Run multi-tenant isolation tests:**
```bash
vendor/bin/phpunit tests/Feature/Security/MultiTenantIsolationTest.php
```

**Run SQL injection prevention tests:**
```bash
vendor/bin/phpunit tests/Feature/Security/SqlInjectionTest.php
```

---

## CI/CD Pipeline

### GitHub Actions Workflow

The test automation suite runs automatically on:
- Push to `main` or `develop`
- Pull requests
- Nightly at 2 AM

**View workflow:**
```
.github/workflows/test-automation.yml
```

**Trigger manually:**
```bash
# Push to trigger
git push origin develop

# Or via GitHub Actions UI
```

**Pipeline Stages:**
1. Unit Tests (2-3 min)
2. RCA Prevention Tests (1-2 min)
3. Integration Tests (3-5 min)
4. Performance Tests (5-10 min)
5. E2E Tests (5-10 min)
6. Security Tests (2-3 min)

**Total Duration**: ~20-30 minutes

---

## Test Coverage

### Generate Coverage Report

```bash
# Generate HTML coverage report
vendor/bin/phpunit --testsuite=Unit --coverage-html coverage-report

# Open in browser
open coverage-report/index.html
```

### Check Coverage Thresholds

```bash
php scripts/check-coverage.php coverage.xml
```

**Expected Output:**
```
Code Coverage Results:
=====================
✅ Line: 82.45% (threshold: 80%)
✅ Branch: 76.32% (threshold: 75%)
✅ Method: 87.21% (threshold: 85%)
```

---

## Common Test Scenarios

### Test 1: Verify RCA Fixes

**Duplicate Booking Bug Prevention:**
```bash
vendor/bin/phpunit --filter=it_rejects_stale_calcom_booking_response
vendor/bin/phpunit --filter=it_prevents_duplicate_calcom_booking_ids
```

**Race Condition Handling:**
```bash
vendor/bin/phpunit --filter=it_implements_double_check_before_booking
vendor/bin/phpunit --filter=it_handles_calcom_booking_conflict_gracefully
```

**Type Safety:**
```bash
vendor/bin/phpunit --filter=it_handles_branch_id_as_uuid_string
```

### Test 2: Performance Validation

```bash
# Baseline test (should complete in < 45s P95)
k6 run tests/Performance/k6/baseline-booking-flow.js

# Check specific metric
k6 run tests/Performance/k6/baseline-booking-flow.js | grep "booking_duration"
```

### Test 3: End-to-End User Journey

```bash
# Complete booking flow
npx playwright test tests/E2E/playwright/booking-journey.spec.ts -g "complete booking flow"

# Race condition handling
npx playwright test tests/E2E/playwright/booking-journey.spec.ts -g "race condition"
```

---

## Troubleshooting

### Issue: Tests Fail Due to Database

**Solution:**
```bash
# Reset test database
php artisan migrate:fresh --env=testing

# Seed test data
php artisan db:seed --class=TestDataSeeder --env=testing
```

### Issue: Cal.com API Mock Not Working

**Solution:**
```bash
# Check HTTP facade is enabled in tests
# File: tests/TestCase.php
use Illuminate\Support\Facades\Http;

protected function setUp(): void
{
    parent::setUp();
    Http::fake(); // Mock all HTTP requests
}
```

### Issue: K6 Performance Tests Timeout

**Solution:**
```bash
# Increase timeout in test file
export options = {
    thresholds: {
        'http_req_duration': ['p(95)<60000'], // Increase from 45s
    }
};
```

### Issue: Playwright Tests Can't Connect

**Solution:**
```bash
# Ensure application is running
php artisan serve

# Check correct URL in playwright.config.ts
baseURL: 'http://localhost:8000'

# Verify browser installation
npx playwright install chromium
```

---

## Test Data Management

### Reset Test Database

```bash
php artisan migrate:fresh --env=testing
php artisan db:seed --class=TestDataSeeder --env=testing
```

### Generate Test Fixtures

```php
// Use in tests
use Tests\Fixtures\AppointmentFixtures;

$scenario = AppointmentFixtures::validBookingScenario();
$company = $scenario['company'];
$booking_data = $scenario['booking_data'];
```

### Mock Cal.com Responses

```php
use Tests\Mocks\CalcomMock;

CalcomMock::setUp();
CalcomMock::setAvailableSlots([
    '2025-10-20' => [
        ['time' => '2025-10-20T14:00:00.000Z']
    ]
]);
```

---

## Monitoring & Reporting

### View Test Results Dashboard

```bash
# Generate HTML report
vendor/bin/phpunit --log-junit test-results.xml
php scripts/generate-test-report.php test-results.xml > report.html
```

### Track Performance Trends

```bash
# Save K6 results
k6 run tests/Performance/k6/baseline-booking-flow.js --out json=results-$(date +%Y%m%d).json

# Compare with previous runs
python scripts/compare-performance.py results-20251017.json results-20251018.json
```

### Monitor Coverage Trends

```bash
# Generate coverage badge
php scripts/generate-coverage-badge.php coverage.xml > coverage-badge.svg
```

---

## Best Practices

### Before Committing Code

```bash
# Run all unit tests
vendor/bin/phpunit --testsuite=Unit

# Run RCA prevention tests
vendor/bin/phpunit tests/Unit/Services/RcaPreventionTest.php

# Check code style
vendor/bin/phpstan analyse app/
```

### Before Merging PR

```bash
# Full test suite
vendor/bin/phpunit

# Performance validation
k6 run tests/Performance/k6/baseline-booking-flow.js

# E2E smoke test
npx playwright test tests/E2E/playwright/booking-journey.spec.ts -g "complete booking flow"
```

### Debugging Failed Tests

```bash
# Run single test with verbose output
vendor/bin/phpunit --filter=test_name --verbose

# Run with debug logging
vendor/bin/phpunit --filter=test_name --debug

# Run Playwright in debug mode
PWDEBUG=1 npx playwright test tests/E2E/playwright/booking-journey.spec.ts
```

---

## Key Files Reference

### Test Files
```
tests/
├── Unit/
│   └── Services/
│       └── RcaPreventionTest.php          # RCA-specific tests
├── Feature/
│   └── Integration/
│       ├── CompleteBookingFlowTest.php
│       └── ConcurrentBookingTest.php
├── Performance/
│   └── k6/
│       ├── baseline-booking-flow.js       # Baseline performance
│       └── load-test.js                   # Load testing
└── E2E/
    └── playwright/
        ├── booking-journey.spec.ts        # User journey tests
        └── error-scenarios.spec.ts
```

### Configuration Files
```
.github/workflows/test-automation.yml      # CI/CD pipeline
tests/E2E/playwright.config.ts             # Playwright config
phpunit.xml                                # PHPUnit config
```

### Documentation
```
claudedocs/04_TESTING/
├── COMPREHENSIVE_TEST_AUTOMATION_PLAN_2025-10-18.md
├── QUICK_START_GUIDE_2025-10-18.md
└── RCA Coverage:
    ├── DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
    ├── RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
    └── BOOKING_ERROR_ANALYSIS_2025-10-06.md
```

---

## Success Metrics

### Daily Checks
- [ ] All unit tests passing
- [ ] Code coverage > 80%
- [ ] No security test failures

### Weekly Checks
- [ ] Performance tests meeting <45s target
- [ ] E2E tests passing in all browsers
- [ ] No test flakiness detected

### Monthly Checks
- [ ] Coverage trending upward
- [ ] Performance stable or improving
- [ ] All RCA issues remain covered

---

## Getting Help

### Documentation
- Comprehensive Plan: `claudedocs/04_TESTING/COMPREHENSIVE_TEST_AUTOMATION_PLAN_2025-10-18.md`
- RCA Documentation: `claudedocs/08_REFERENCE/RCA/`

### Common Commands
```bash
# Quick test everything
composer test

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature

# Performance check
k6 run tests/Performance/k6/baseline-booking-flow.js

# E2E check
npx playwright test
```

### Support
- Test automation issues: Check GitHub Actions logs
- Performance issues: Review K6 metrics
- E2E issues: Check Playwright report

---

**Last Updated**: 2025-10-18
**Version**: 1.0
**Status**: Ready for Use ✅
