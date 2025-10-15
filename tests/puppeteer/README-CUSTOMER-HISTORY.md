# CRM Customer History E2E Test

**Test File:** `/var/www/api-gateway/tests/puppeteer/crm-customer-history-e2e.cjs`
**Runner Script:** `/var/www/api-gateway/tests/run-customer-history-test.sh`

---

## Overview

End-to-end Puppeteer test that verifies appointment history display in the Filament Admin panel.

**Test Scenario:**
1. Login to Filament Admin (https://api.askproai.de/admin)
2. Navigate to Customers section
3. Find and open Customer #461 (Hansi Hinterseer)
4. Verify Appointments section exists
5. Verify appointments #672 and #673 are visible
6. Open appointment detail and verify metadata display

---

## Prerequisites

### 1. Node.js & Puppeteer

```bash
# Check Node.js installation
node --version  # Should be v14+ or higher

# Install Puppeteer (if not already installed)
npm install puppeteer
```

### 2. Admin Credentials

Set admin credentials as environment variables:

```bash
export ADMIN_EMAIL=fabian@askproai.de
export ADMIN_PASSWORD=your_admin_password
```

**Or** create a `.env` file in project root:

```env
ADMIN_EMAIL=fabian@askproai.de
ADMIN_PASSWORD=your_password_here
APP_URL=https://api.askproai.de
```

### 3. Test Data

Ensure test data exists in database:

```bash
# Verify customer exists
php artisan tinker --execute="echo \App\Models\Customer::find(461);"

# Verify appointments exist
php artisan tinker --execute="echo \App\Models\Appointment::whereIn('id', [672, 673])->get();"
```

---

## Running the Test

### Option 1: Using Shell Script (Recommended)

```bash
# Basic run (headless mode)
./tests/run-customer-history-test.sh

# Run with visible browser (useful for debugging)
./tests/run-customer-history-test.sh --no-headless

# Run with verbose output
./tests/run-customer-history-test.sh --verbose

# Show help
./tests/run-customer-history-test.sh --help
```

### Option 2: Direct Node.js Execution

```bash
# Set environment variables
export ADMIN_EMAIL=fabian@askproai.de
export ADMIN_PASSWORD=your_password
export APP_URL=https://api.askproai.de
export HEADLESS=true

# Run test
node tests/puppeteer/crm-customer-history-e2e.cjs
```

### Option 3: Non-Headless for Debugging

```bash
# Run with visible browser window
HEADLESS=false ./tests/run-customer-history-test.sh --no-headless
```

---

## Test Steps & Verification

### Test 1: Admin Login ✅
- Navigate to `/admin/login`
- Fill email and password fields
- Submit login form
- Wait for redirect to admin dashboard
- **Verification:** URL contains `/admin` and not `/login`

### Test 2: Navigate to Customers ✅
- Navigate to `/admin/customers`
- Wait for customer table to load
- **Verification:** Table element exists with customer data

### Test 3: Open Customer Detail ✅
- Navigate to `/admin/customers/461`
- Wait for customer detail page to load
- **Verification:** URL contains `/customers/461`
- **Screenshot:** `customer-461-detail.png`

### Test 4: Verify Appointments Section ✅
- Look for "Termine" heading/section
- If in tab, click to activate
- **Verification:** Appointments section visible on page
- **Possible Selectors:**
  - `h2:has-text("Termine")`
  - `h3:has-text("Termine")`
  - `button:has-text("Termine")` (tab)
  - `.fi-ta-header-heading:has-text("Termine")`

### Test 5: Verify Appointments Visible ✅
- Wait for appointment table/list to load
- Verify appointments #672 and #673 are displayed
- Count total appointments shown
- **Verification:**
  - Appointment rows exist in table
  - Specific appointment IDs found (if possible)
  - At least 2 appointments displayed
- **Screenshot:** `customer-461-appointments.png`

### Test 6: Appointment Detail/Metadata ✅
- Attempt to click on appointment #672
- Wait for detail view or modal
- Look for metadata display
- **Verification:**
  - Detail view/modal opened
  - Metadata fields visible (if present)
- **Screenshot:** `appointment-672-detail.png`

---

## Screenshots

All screenshots are saved to `/var/www/api-gateway/screenshots/`

### Success Screenshots
- `customer-461-detail.png` - Customer detail page
- `customer-461-appointments.png` - Appointments section with data
- `appointment-672-detail.png` - Appointment detail view (if opened)
- `test-complete.png` - Final state after all tests

### Error Screenshots
- `error-login-failed-[timestamp].png` - Login failure
- `error-customers-navigation-failed-[timestamp].png` - Navigation issue
- `error-customer-detail-failed-[timestamp].png` - Customer detail problem
- `error-appointments-section-missing-[timestamp].png` - Section not found
- `error-appointments-verification-failed-[timestamp].png` - Appointments not visible
- `error-appointment-detail-failed-[timestamp].png` - Detail view issue

---

## Expected Output

### Success Output

```
======================================
CRM Customer History E2E Test
======================================

→ Launching browser...
✅ Browser launched

[TEST 1] Admin Login
─────────────────────
→ Navigating to admin login...
→ Waiting for login form...
→ Filling credentials...
→ Submitting login form...
→ Waiting for redirect to dashboard...
✅ Login successful

[TEST 2] Navigate to Customers
─────────────────────────────────
→ Navigating to Customers...
✅ Customers page loaded

[TEST 3] Open Customer #461 Detail
─────────────────────────────────────────────────
→ Looking for customer #461 (Hansi Hinterseer)...
→ Navigating to customer #461 detail page...
✅ Customer #461 detail page loaded
→ Screenshot saved: screenshots/customer-461-detail.png

[TEST 4] Verify Appointments Section Exists
──────────────────────────────────────────────
→ Checking for Appointments section...
✅ Appointments section found with selector: h2:has-text("Termine")

[TEST 5] Verify Appointments #672 and #673 are Visible
───────────────────────────────────────────────────────
→ Verifying appointments 672, 673 are visible...
✅ Appointment #672 found in page content
✅ Appointment #673 found in page content
→ Found 2 appointment rows in table
✅ Verified 2/2 specific appointments
→ Total appointments displayed: 2
→ Screenshot saved: screenshots/customer-461-appointments.png

[TEST 6] Verify Appointment #672 Detail/Metadata
────────────────────────────────────────────────────
→ Opening appointment #672 detail...
✅ Appointment detail view contains metadata
→ Screenshot saved: screenshots/appointment-672-detail.png

====================================
Test Summary
====================================
✅ Passed: 6
❌ Failed: 0
📊 Total:  6
====================================
```

### Failure Output Example

```
[TEST 5] Verify Appointments #672 and #673 are Visible
───────────────────────────────────────────────────────
→ Verifying appointments 672, 673 are visible...
→ Found 0 appointment rows in table
❌ Appointments verification failed: No appointments displayed in table
❌ Screenshot saved: screenshots/error-appointments-verification-failed-2025-10-11T12-30-45.png

====================================
Test Summary
====================================
✅ Passed: 4
❌ Failed: 2
📊 Total:  6
====================================
```

---

## Troubleshooting

### Issue: Login Failed

**Problem:** Cannot login to admin panel

**Solutions:**
1. Verify admin credentials:
   ```bash
   php artisan tinker --execute="echo \App\Models\User::where('email', 'fabian@askproai.de')->first();"
   ```

2. Reset admin password if needed:
   ```bash
   php artisan tinker --execute="\$user = \App\Models\User::where('email', 'fabian@askproai.de')->first(); \$user->password = bcrypt('new_password'); \$user->save();"
   ```

3. Check login page selectors:
   ```bash
   # Run with visible browser to inspect
   HEADLESS=false ./tests/run-customer-history-test.sh --no-headless
   ```

### Issue: Appointments Section Not Found

**Problem:** "Appointments section not found on customer detail page"

**Solutions:**
1. Check if appointments are in a tab that needs activation
2. Verify customer has appointments relation manager enabled
3. Inspect page source for actual section structure:
   ```bash
   curl -s https://api.askproai.de/admin/customers/461 -H "Cookie: your_session_cookie" | grep -i "termine"
   ```

### Issue: Appointments Not Visible

**Problem:** "No appointments displayed in table"

**Solutions:**
1. Verify appointments exist for customer #461:
   ```bash
   php artisan tinker --execute="echo \App\Models\Customer::find(461)->appointments;"
   ```

2. Check if default filter is hiding appointments:
   - The relation manager may have a default filter for "upcoming" appointments
   - Appointments #672, #673 may be in the past

3. Modify test to clear filters before verification

### Issue: Puppeteer Not Found

**Problem:** "Cannot find module 'puppeteer'"

**Solutions:**
```bash
# Install Puppeteer
npm install puppeteer

# Or install globally
npm install -g puppeteer
```

### Issue: ARM64 Chrome Issues

**Problem:** "Failed to launch the browser process"

**Solutions:**
```bash
# Install ARM64 compatible Puppeteer
npm install puppeteer --force

# Or use system Chrome
export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
```

---

## Customization

### Test Different Customer

Edit the test constants:

```javascript
const TEST_CUSTOMER_ID = 123;
const TEST_CUSTOMER_NAME = 'John Doe';
const TEST_APPOINTMENT_IDS = [456, 789];
```

### Add More Verification Steps

```javascript
// Example: Verify appointment metadata fields
async function verifyAppointmentMetadata(page, appointmentId) {
    console.log('→ Verifying metadata fields...');

    const metadataFields = [
        'booking_source',
        'booking_type',
        'created_by',
        'created_at',
    ];

    for (const field of metadataFields) {
        const element = await page.$(`[data-field="${field}"]`);
        if (element) {
            const value = await element.textContent();
            console.log(`✅ ${field}: ${value}`);
        }
    }
}
```

### Adjust Timeouts

```javascript
// Increase timeout for slow connections
page.setDefaultTimeout(60000); // 60 seconds

// Or per-operation
await page.waitForSelector('table', { timeout: 45000 });
```

---

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: E2E Tests

on: [push, pull_request]

jobs:
  e2e:
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
          name: error-screenshots
          path: screenshots/error-*.png
```

### GitLab CI Example

```yaml
e2e_test:
  stage: test
  image: node:18
  script:
    - apt-get update && apt-get install -y chromium
    - npm install puppeteer
    - export PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
    - ./tests/run-customer-history-test.sh
  artifacts:
    when: on_failure
    paths:
      - screenshots/error-*.png
```

---

## Related Documentation

- **Puppeteer Login Config:** `/var/www/api-gateway/claudedocs/PUPPETEER_LOGIN_CONFIG.md`
- **Filament History Implementation:** `/var/www/api-gateway/claudedocs/FILAMENT_HISTORY_IMPLEMENTATION_SUMMARY.md`
- **Customer Resource:** `/var/www/api-gateway/app/Filament/Resources/CustomerResource.php`
- **Appointments Relation Manager:** `/var/www/api-gateway/app/Filament/Resources/CustomerResource/RelationManagers/AppointmentsRelationManager.php`

---

## Test Data Reference

### Customer #461
- **ID:** 461
- **Name:** Hansi Hinterseer
- **Email:** null

### Appointments
- **#672:**
  - Customer ID: 461
  - Start: 2025-10-15 08:00:00 UTC
  - Status: scheduled

- **#673:**
  - Customer ID: 461
  - Start: 2025-10-16 11:00:00 UTC
  - Status: scheduled

---

## License & Credits

**Created:** 2025-10-11
**Test Type:** E2E (Puppeteer)
**Target:** Filament Admin Panel
**Purpose:** Verify appointment history display for customers
