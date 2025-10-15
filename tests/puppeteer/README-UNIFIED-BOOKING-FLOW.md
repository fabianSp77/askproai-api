# Unified Booking Flow E2E Tests

## Overview

Comprehensive End-to-End test suite for the V4 Unified Booking Flow implementation.

## Test Coverage

### ✅ Test 1: Branch Selection
- Verifies branch selection UI
- Checks `branch_id` field population
- Tests Livewire → Alpine.js event flow

### ✅ Test 2: Customer Search & Selection
- Tests live search with debounce
- Verifies search results display
- Checks `customer_id` field population

### ✅ Test 3: Service Selection
- Tests service radio button selection
- Verifies `service_id` field population
- Checks calendar reload with service duration

### ✅ Test 4: Employee/Staff Preference
- Tests employee preference selection
- Verifies `staff_id` field population (optional)
- Tests "any available" vs specific employee

### ✅ Test 5: Calendar Slot Selection
- Tests calendar grid rendering
- Verifies slot button availability
- Checks `starts_at` field population

### ✅ Test 6: No Duplicate Fields (Create Mode)
- Verifies old service/staff dropdowns are HIDDEN
- Ensures no visual duplication
- Tests context-based visibility

### ✅ Test 7: Form Submission Validation
- Checks form validity state
- Verifies all required fields populated
- Tests submit button availability

### ✅ Test 8: Dark Mode Contrast (WCAG)
- Tests dark mode toggle
- Verifies border visibility
- Checks contrast ratios (visual)

## Running the Tests

### Prerequisites

```bash
# Install Puppeteer (if not already installed)
npm install puppeteer

# Ensure app is running
php artisan serve
```

### Run Test

```bash
# Standard run
node tests/puppeteer/unified-booking-flow-e2e.cjs

# With custom credentials
TEST_EMAIL=admin@askpro.ai TEST_PASSWORD=yourpass node tests/puppeteer/unified-booking-flow-e2e.cjs

# With custom base URL
APP_URL=https://app.askpro.ai node tests/puppeteer/unified-booking-flow-e2e.cjs
```

## Expected Output

```
═══════════════════════════════════════════════
   Unified Booking Flow E2E Test Suite
═══════════════════════════════════════════════

🔐 Logging in...
✓ Logged in successfully

📍 Navigating to Appointments Create page...
✓ Page loaded

[TEST 1] Branch Selection
✓ Branch selected and branch_id populated: 123

[TEST 2] Customer Search & Selection
✓ Customer selected and customer_id populated: 456

[TEST 3] Service Selection
✓ Service selected and service_id populated: 789

[TEST 4] Employee/Staff Preference
✓ Employee selected and staff_id populated: 321

[TEST 5] Calendar Slot Selection
✓ Slot selected and starts_at populated: 2025-10-15T10:00:00Z

[TEST 6] No Duplicate Fields in Create Mode
✓ Old service/staff dropdowns are hidden in create mode

[TEST 7] Form Submission (Validation)
✓ Form is valid and ready for submission

[TEST 8] Dark Mode Contrast
✓ Dark mode borders are visible (good contrast)

═══════════════════════════════════════════════
   TEST SUMMARY
═══════════════════════════════════════════════
✓ PASS test1
✓ PASS test2
✓ PASS test3
✓ PASS test4
✓ PASS test5
✓ PASS test6
✓ PASS test7
✓ PASS test8

8/8 tests passed

🎉 ALL TESTS PASSED! 🎉

📸 Screenshot saved: screenshots/unified-booking-flow-final.png
```

## Debugging

### Enable Headful Mode

Edit `unified-booking-flow-e2e.cjs`:

```javascript
const browser = await puppeteer.launch({
    headless: false,  // Changed from true
    slowMo: 100,      // Slow down by 100ms per action
    devtools: true,   // Open DevTools
    // ...
});
```

### View Browser Console

The test automatically logs `[BookingFlowWrapper]` events:

```
   Browser: [BookingFlowWrapper] Branch selected: 123
   Browser: [BookingFlowWrapper] branch_id updated: 123
```

### Screenshot Location

Screenshots are saved to:
```
tests/puppeteer/screenshots/unified-booking-flow-final.png
```

## Troubleshooting

### Test Fails on Login

Check credentials:
```bash
TEST_EMAIL=your@email.com TEST_PASSWORD=yourpass node tests/puppeteer/unified-booking-flow-e2e.cjs
```

### "No branches available"

This is expected if the test company has no branches. Not a failure.

### "No available slots"

This is expected if:
- No Cal.com integration configured
- No availability in the selected week
- Service duration doesn't fit available slots

Not a test failure.

### "Form validation failed"

Expected if `starts_at` wasn't populated (no slots available).

## Architecture

```
User Action (Livewire Component)
    ↓
selectBranch() / selectCustomer() / selectService()
    ↓
$this->dispatch('event-name', data)
    ↓
Livewire → Browser Event
    ↓
@event-name.window (Alpine.js in Wrapper)
    ↓
querySelector('select[name=field_id]')
    ↓
field.value = data
    ↓
field.dispatchEvent(new Event('change'))
    ↓
Filament Form Updated ✅
```

## CI/CD Integration

Add to GitHub Actions:

```yaml
- name: Run Unified Booking Flow E2E Tests
  run: |
    npm install puppeteer
    node tests/puppeteer/unified-booking-flow-e2e.cjs
  env:
    APP_URL: ${{ secrets.APP_URL }}
    TEST_EMAIL: ${{ secrets.TEST_EMAIL }}
    TEST_PASSWORD: ${{ secrets.TEST_PASSWORD }}
```

## Related Files

- Component: `app/Livewire/AppointmentBookingFlow.php`
- Template: `resources/views/livewire/appointment-booking-flow.blade.php`
- Wrapper: `resources/views/livewire/appointment-booking-flow-wrapper.blade.php`
- Resource: `app/Filament/Resources/AppointmentResource.php`

## Maintenance

When adding new features to the booking flow:

1. Add corresponding test case to this file
2. Update test coverage section in this README
3. Verify all existing tests still pass
4. Update expected output if needed

---

**Last Updated:** 2025-10-15
**Test Version:** 1.0
**Phase:** 6 - E2E Testing
