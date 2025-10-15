# E2E Test: CRM Customer History - Implementation Summary

**Created:** 2025-10-11
**Test Type:** Puppeteer E2E
**Purpose:** Verify appointment history display in Filament Admin panel

---

## Overview

Comprehensive Puppeteer end-to-end test that validates appointment history functionality in the Filament Admin CRM interface.

**Test Scenario:**
1. Login to Filament Admin (https://api.askproai.de/admin)
2. Navigate to Customers section
3. Find Customer #461 (Hansi Hinterseer)
4. Open Customer Detail page
5. Verify Appointments section exists
6. Verify appointments #672 and #673 are visible
7. Click on appointment #672
8. Verify metadata is displayed (wenn vorhanden)

---

## Files Created

### 1. Main Test Script
**File:** `/var/www/api-gateway/tests/puppeteer/crm-customer-history-e2e.cjs`

**Features:**
- ✅ Filament Admin authentication handling
- ✅ Customer navigation and search
- ✅ Appointment section verification
- ✅ Appointment visibility checks
- ✅ Metadata display validation
- ✅ Comprehensive error handling
- ✅ Automatic screenshot capture on failure
- ✅ Detailed console output
- ✅ Exit codes for CI/CD integration

**Test Coverage:**
1. Admin Login (with session persistence)
2. Customer List Navigation
3. Customer Detail Page Loading
4. Appointments Section Existence
5. Appointments Data Visibility
6. Appointment Detail/Metadata Display

### 2. Test Runner Script
**File:** `/var/www/api-gateway/tests/run-customer-history-test.sh`

**Features:**
- ✅ Environment variable handling
- ✅ Automatic Puppeteer installation check
- ✅ Headless/non-headless mode support
- ✅ Verbose output option
- ✅ Screenshot directory creation
- ✅ Password prompt for interactive mode
- ✅ Clear error messages
- ✅ Exit code handling

**Usage:**
```bash
# Basic run
./tests/run-customer-history-test.sh

# Debug mode (visible browser)
./tests/run-customer-history-test.sh --no-headless

# Verbose output
./tests/run-customer-history-test.sh --verbose

# Show help
./tests/run-customer-history-test.sh --help
```

### 3. Setup Verification Script
**File:** `/var/www/api-gateway/tests/puppeteer/verify-test-setup.sh`

**Checks:**
- ✅ Node.js installation
- ✅ NPM availability
- ✅ Puppeteer installation
- ✅ Test script files
- ✅ Runner script executable
- ✅ Screenshots directory
- ✅ Environment variables
- ✅ Test data in database (Customer #461, Appointments #672, #673)
- ✅ Application accessibility

**Usage:**
```bash
./tests/puppeteer/verify-test-setup.sh
```

### 4. Comprehensive Documentation
**File:** `/var/www/api-gateway/tests/puppeteer/README-CUSTOMER-HISTORY.md`

**Sections:**
- Overview and prerequisites
- Running the test (3 different methods)
- Test steps and verification details
- Screenshot documentation
- Expected output examples
- Troubleshooting guide
- Customization instructions
- CI/CD integration examples
- Test data reference

### 5. Quick Start Guide
**File:** `/var/www/api-gateway/tests/puppeteer/QUICK-START.md`

**Quick Reference:**
- One-time setup commands
- Run test commands
- View results
- Troubleshooting
- Common issues and fixes
- Environment variables
- File locations

---

## Test Architecture

### Authentication Strategy
```javascript
async function loginToAdmin(page) {
    // Navigate to login page
    // Fill email and password
    // Submit form
    // Wait for redirect
    // Verify successful login
}
```

**Features:**
- Session cookie preservation
- Automatic redirect handling
- Login failure detection
- Manual password entry support (non-headless mode)

### Navigation Pattern
```javascript
// Direct navigation preferred
await page.goto(`${ADMIN_URL}/customers/${customerId}`)

// Fallback to search if needed
await searchInput.type(customerName)
```

**Benefits:**
- Faster execution (skip intermediate pages)
- More reliable (no dependency on UI search)
- Easier debugging (clear URL paths)

### Verification Strategy

**Multi-Level Verification:**
1. **Section Existence** - Multiple selector strategies
2. **Data Visibility** - Content and DOM verification
3. **Metadata Display** - Flexible field detection
4. **Screenshot Evidence** - Visual proof of state

**Selector Flexibility:**
```javascript
const selectors = [
    'h2:has-text("Termine")',
    'button:has-text("Termine")',
    '.fi-ta-header-heading:has-text("Termine")',
    '[aria-label*="Termine"]',
]
```

### Error Handling

**Screenshot on Every Failure:**
```javascript
async function screenshotOnError(page, testName) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `screenshots/error-${testName}-${timestamp}.png`;
    await page.screenshot({ path: filename, fullPage: true });
}
```

**Graceful Degradation:**
- Test continues even if optional steps fail
- Warnings logged for non-critical issues
- Final summary shows passed/failed counts

---

## Configuration

### Environment Variables

**Required:**
```bash
ADMIN_PASSWORD=your_password_here
```

**Optional (with defaults):**
```bash
APP_URL=https://api.askproai.de
ADMIN_EMAIL=fabian@askproai.de
HEADLESS=true
```

### Test Data

**Customer:**
- ID: 461
- Name: Hansi Hinterseer
- Email: null

**Appointments:**
- #672: starts_at=2025-10-15 08:00:00 UTC, status=scheduled
- #673: starts_at=2025-10-16 11:00:00 UTC, status=scheduled

### Customization

**Change Test Customer:**
```javascript
const TEST_CUSTOMER_ID = 461;
const TEST_CUSTOMER_NAME = 'Hansi Hinterseer';
const TEST_APPOINTMENT_IDS = [672, 673];
```

**Adjust Timeouts:**
```javascript
page.setDefaultTimeout(30000); // 30 seconds

await page.waitForSelector('table', { timeout: 45000 }); // Per-operation
```

---

## Screenshots

### Automatic Screenshot Capture

**Success Screenshots:**
- `customer-461-detail.png` - Customer detail page loaded
- `customer-461-appointments.png` - Appointments section with data
- `appointment-672-detail.png` - Appointment detail view (if opened)
- `test-complete.png` - Final state after all tests

**Error Screenshots (timestamped):**
- `error-login-failed-[timestamp].png`
- `error-customers-navigation-failed-[timestamp].png`
- `error-customer-detail-failed-[timestamp].png`
- `error-appointments-section-missing-[timestamp].png`
- `error-appointments-verification-failed-[timestamp].png`
- `error-appointment-detail-failed-[timestamp].png`

**Location:** `/var/www/api-gateway/screenshots/`

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: CRM E2E Tests

on: [push, pull_request]

jobs:
  e2e-customer-history:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      - run: npm install puppeteer
      - run: ./tests/run-customer-history-test.sh
        env:
          ADMIN_EMAIL: ${{ secrets.ADMIN_EMAIL }}
          ADMIN_PASSWORD: ${{ secrets.ADMIN_PASSWORD }}
      - uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: test-failure-screenshots
          path: screenshots/error-*.png
```

### GitLab CI Example

```yaml
e2e_customer_history:
  stage: test
  image: node:18
  before_script:
    - apt-get update && apt-get install -y chromium
    - npm install puppeteer
    - export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
  script:
    - ./tests/run-customer-history-test.sh
  artifacts:
    when: on_failure
    paths:
      - screenshots/error-*.png
    expire_in: 1 week
```

---

## Troubleshooting Guide

### Common Issues

#### 1. Login Failed
**Symptoms:** Test fails at admin login step

**Solutions:**
```bash
# Verify user exists
php artisan tinker --execute="echo \App\Models\User::where('email', 'fabian@askproai.de')->first();"

# Reset password
php artisan tinker --execute="\$user = \App\Models\User::where('email', 'fabian@askproai.de')->first(); \$user->password = bcrypt('newpass'); \$user->save();"

# Run in non-headless to debug
HEADLESS=false ./tests/run-customer-history-test.sh --no-headless
```

#### 2. Appointments Not Found
**Symptoms:** "No appointments displayed in table"

**Solutions:**
```bash
# Verify appointments exist
php artisan tinker --execute="echo \App\Models\Customer::find(461)->appointments;"

# Check appointments status
php artisan tinker --execute="echo \App\Models\Appointment::whereIn('id', [672, 673])->get(['id', 'starts_at', 'status']);"

# Check relation manager filters
# Default filter may be hiding past appointments
```

#### 3. Puppeteer Installation Issues
**Symptoms:** "Cannot find module 'puppeteer'"

**Solutions:**
```bash
# Install Puppeteer
npm install puppeteer

# Force reinstall for ARM64
npm install puppeteer --force

# Use system Chrome
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
```

#### 4. Selectors Not Found
**Symptoms:** "Appointments section not found"

**Solutions:**
```bash
# Run with visible browser to inspect
HEADLESS=false ./tests/run-customer-history-test.sh --no-headless

# Check page HTML structure
curl -s https://api.askproai.de/admin/customers/461 | grep -i "termine"

# Update selectors in test script
```

---

## Testing Workflow

### Development Workflow

```bash
# 1. Verify setup
./tests/puppeteer/verify-test-setup.sh

# 2. Run test (headless)
./tests/run-customer-history-test.sh

# 3. If failed, debug visually
HEADLESS=false ./tests/run-customer-history-test.sh --no-headless

# 4. Review screenshots
ls -lh screenshots/

# 5. Fix issues and re-test
./tests/run-customer-history-test.sh
```

### Continuous Integration Workflow

```bash
# 1. Install dependencies
npm install puppeteer

# 2. Set credentials (from secrets)
export ADMIN_EMAIL=$ADMIN_EMAIL
export ADMIN_PASSWORD=$ADMIN_PASSWORD

# 3. Run test
./tests/run-customer-history-test.sh

# 4. Upload artifacts on failure
if [ $? -ne 0 ]; then
    upload-artifacts screenshots/error-*.png
fi
```

---

## Performance Considerations

### Test Execution Time

**Expected Duration:**
- Login: ~3-5 seconds
- Navigation: ~2-3 seconds per page
- Verification: ~1-2 seconds per check
- Screenshots: ~1 second each
- **Total: ~15-25 seconds** (headless mode)

### Optimization Tips

1. **Use Direct Navigation:**
   ```javascript
   // Fast
   await page.goto(`${ADMIN_URL}/customers/461`)
   
   // Slower
   await page.goto(`${ADMIN_URL}/customers`)
   await searchAndClick('Hansi Hinterseer')
   ```

2. **Parallel Checks:**
   ```javascript
   // Check multiple selectors in parallel
   const checks = await Promise.allSettled([
       page.$('selector1'),
       page.$('selector2'),
       page.$('selector3'),
   ])
   ```

3. **Smart Waiting:**
   ```javascript
   // Wait only for essential elements
   await page.waitForSelector('table', { timeout: 10000 })
   // Don't wait for nice-to-have elements
   ```

---

## Security Considerations

### Credentials Handling

**Best Practices:**
- ✅ Use environment variables
- ✅ Never commit passwords to git
- ✅ Use CI/CD secrets for automation
- ✅ Rotate test passwords regularly

**Bad Practices:**
- ❌ Hardcoded passwords in scripts
- ❌ Plain text credentials in screenshots
- ❌ Shared production passwords

### Screenshot Safety

**Automatic Redaction:**
```javascript
// Blur sensitive data before screenshot
await page.evaluate(() => {
    document.querySelectorAll('[data-sensitive]').forEach(el => {
        el.style.filter = 'blur(5px)';
    });
});
await page.screenshot({ path: 'safe-screenshot.png' });
```

---

## Future Enhancements

### Potential Improvements

1. **Data-Driven Testing:**
   ```javascript
   const testCases = [
       { customerId: 461, appointmentIds: [672, 673] },
       { customerId: 123, appointmentIds: [456, 789] },
   ];
   
   for (const testCase of testCases) {
       await runTest(testCase);
   }
   ```

2. **Visual Regression Testing:**
   ```bash
   # Capture baseline
   ./tests/run-customer-history-test.sh --baseline
   
   # Compare against baseline
   ./tests/run-customer-history-test.sh --compare
   ```

3. **Performance Metrics:**
   ```javascript
   const metrics = await page.metrics();
   console.log(`Page load: ${metrics.TaskDuration}ms`);
   console.log(`JS heap: ${metrics.JSHeapUsedSize / 1024 / 1024}MB`);
   ```

4. **Accessibility Testing:**
   ```javascript
   // Use axe-core for a11y checks
   const results = await page.evaluate(() => {
       return axe.run();
   });
   ```

---

## Related Documentation

- **Puppeteer Login Config:** `/var/www/api-gateway/claudedocs/PUPPETEER_LOGIN_CONFIG.md`
- **Filament History Implementation:** `/var/www/api-gateway/claudedocs/FILAMENT_HISTORY_IMPLEMENTATION_SUMMARY.md`
- **Data Consistency Spec:** `/var/www/api-gateway/claudedocs/DATA_CONSISTENCY_SPECIFICATION.md`

---

## Checklist

### Pre-Test
- [ ] Node.js installed
- [ ] Puppeteer installed (`npm install puppeteer`)
- [ ] Admin credentials set (`export ADMIN_PASSWORD=...`)
- [ ] Test data verified (Customer #461, Appointments #672, #673)
- [ ] Application accessible

### Run Test
- [ ] Verify setup: `./tests/puppeteer/verify-test-setup.sh`
- [ ] Run test: `./tests/run-customer-history-test.sh`
- [ ] Review output for errors
- [ ] Check screenshots directory

### Post-Test
- [ ] Verify all tests passed (exit code 0)
- [ ] Review success screenshots
- [ ] Archive error screenshots if any
- [ ] Update test data if needed

---

**Status:** ✅ Complete and ready to run
**Test Script:** `/var/www/api-gateway/tests/puppeteer/crm-customer-history-e2e.cjs`
**Quick Start:** `/var/www/api-gateway/tests/puppeteer/QUICK-START.md`
