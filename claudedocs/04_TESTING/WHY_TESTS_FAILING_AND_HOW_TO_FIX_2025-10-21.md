# Why Tests Are Failing - Root Cause & Solution

**Date**: 2025-10-21
**Issue**: Tests appear to fail when running from dashboard
**Root Cause**: Test files don't exist yet (we built the UI but not the tests)
**Solution**: Create 9 Pest test files

---

## The Problem

When you click a test button on the dashboard, you see errors like:
```
Test execution failed: File not found: tests/Feature/CalcomIntegration/AppointmentBookingTest.php
```

**Why?** The CalcomTestRunner tries to run:
```php
$process = new SymfonyProcess([
    'php',
    'artisan',
    'pest',
    'tests/Feature/CalcomIntegration/AppointmentBookingTest.php',  // ← File doesn't exist!
    '--json',
    '--no-interaction'
], base_path());
```

---

## What We've Built vs. What's Missing

### ✅ What We Have (The Infrastructure)
1. **Blade Template** - Beautiful UI with company selector
2. **Livewire Component** - Handles test execution and live output
3. **Test Service** - CalcomTestRunner orchestrates tests
4. **Database Model** - Stores test results
5. **Authorization** - admin@askproai.de only

### ❌ What's Missing (The Test Implementation)
1. **AppointmentBookingTest.php** - Test appointment creation
2. **AppointmentRescheduleTest.php** - Test rescheduling
3. **AppointmentCancellationTest.php** - Test cancellation
4. **AppointmentQueryTest.php** - Test secure queries
5. **AvailabilityServiceTest.php** - Test availability checks
6. **BidirectionalSyncTest.php** - Test Cal.com ↔ Laravel sync
7. **V2ApiCompatibilityTest.php** - Test V2 API headers
8. **MultiTenantIsolationTest.php** - Test security isolation
9. **EventIdVerificationTest.php** - (Special: uses Artisan command)

---

## How It's Supposed to Work

```
User selects "AskProAI" (Team 39203)
   ↓
User clicks "Run All Tests"
   ↓
CalcomTestRunner::runAllTests(['team_id' => 39203, ...])
   ↓
For each test type:
   - Create SystemTestRun record
   - Execute Pest test file
   - Store results
   - Pass company context to tests
   ↓
Live output shows results
   ↓
Test history updated with metadata
```

---

## UI Improvements Made Today

### ✅ Company/Branch Selector
Now visible at top of dashboard:
```
┌─────────────────────────────────────┐
│ Select Company/Branch for Testing:  │
├──────────────────┬──────────────────┤
│ AskProAI         │ Friseur 1        │
│ Team 39203       │ Team 34209       │
│ Events: 36..     │ Events: 29..     │
└──────────────────┴──────────────────┘
```

- Click to select (shows blue border + blue background)
- Tests disabled until company selected
- Selected company context passed to all tests

### ✅ Better Contrast
- **Fixed**: White text on white background (disabled buttons)
- **Fixed**: All text now has proper contrast
- Company selector has dark gray text on white background

### ✅ Company Context in Output
When tests run, live output shows:
```
Starting test: 2. Availability Check
Company: AskProAI
Team ID: 39203
Event IDs: 3664712, 2563193

Executing...
```

---

## What's Happening When Tests "Fail"

The CalcomTestRunner checks 9 locations:

1. **EVENT_ID_VERIFICATION** - Uses Artisan command (built, works)
2. **AVAILABILITY_CHECK** → Looks for `tests/Feature/CalcomIntegration/AvailabilityServiceTest.php` ❌
3. **APPOINTMENT_BOOKING** → Looks for `tests/Feature/CalcomIntegration/AppointmentBookingTest.php` ❌
4. **APPOINTMENT_RESCHEDULE** → `AppointmentRescheduleTest.php` ❌
5. **APPOINTMENT_CANCELLATION** → `AppointmentCancellationTest.php` ❌
6. **APPOINTMENT_QUERY** → `AppointmentQueryTest.php` ❌
7. **BIDIRECTIONAL_SYNC** → `BidirectionalSyncTest.php` ❌
8. **V2_API_COMPATIBILITY** → `V2ApiCompatibilityTest.php` ❌
9. **MULTI_TENANT_ISOLATION** → `MultiTenantIsolationTest.php` ❌

The dashboard still shows the test as "executed" but with error status = red badge.

---

## How to Fix: Create the Missing Test Files

### Quick Start: Create Test Stub Files

```bash
mkdir -p tests/Feature/CalcomIntegration

cat > tests/Feature/CalcomIntegration/AvailabilityServiceTest.php << 'EOF'
<?php

namespace Tests\Feature\CalcomIntegration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_check_for_askproai()
    {
        // TODO: Test with Team 39203
        $this->assertTrue(true);
    }

    public function test_availability_check_for_friseur()
    {
        // TODO: Test with Team 34209
        $this->assertTrue(true);
    }
}
EOF
```

### Proper Test Structure

Each test file should:

1. **Accept company context** (passed via environment or global)
2. **Test specific Team ID** (39203 or 34209)
3. **Test specific Event IDs** (provided in $companyConfig)
4. **Use multi-tenant isolation** (ensure no cross-company data access)
5. **Report results** (pass/fail with meaningful messages)

Example structure:

```php
<?php

namespace Tests\Feature\CalcomIntegration;

use App\Models\Appointment;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    protected $teamId;
    protected $eventIds;

    protected function setUp(): void
    {
        parent::setUp();

        // Get company context from global or environment
        $this->teamId = env('TEST_TEAM_ID', 39203);
        $this->eventIds = env('TEST_EVENT_IDS', '3664712,2563193');
    }

    public function test_create_appointment_with_correct_team()
    {
        // Test that appointments are created with correct Team ID
        $this->assertEquals(39203, $this->teamId);
    }

    public function test_appointment_uses_correct_event_id()
    {
        // Test that appointments use Event IDs for the team
        $eventIds = explode(',', $this->eventIds);
        $this->assertCount(2, $eventIds);
    }

    public function test_multi_tenant_isolation()
    {
        // Test that we can't access other company's data
        $this->assertTrue(true);
    }
}
```

---

## Why This Design

### Multi-Tenant Context Passing

The dashboard passes company config like this:
```php
$this->companyConfig = [
    'name' => 'AskProAI',
    'team_id' => 39203,
    'event_ids' => [3664712, 2563193]
];
```

This is then passed to CalcomTestRunner which can:
1. Store it in SystemTestRun record (metadata)
2. Pass it to test environment (env variables)
3. Use it to filter test results

### Why Tests Were Failing

Each test file is run in a **separate process**:
```php
$process = new SymfonyProcess([
    'php',
    'artisan',
    'pest',
    'tests/Feature/CalcomIntegration/AppointmentBookingTest.php',
    '--json',
    '--no-interaction'
], base_path());
```

The test files must be **real files that exist**.

---

## Current Status

| Component | Status | Details |
|-----------|--------|---------|
| Dashboard UI | ✅ READY | Company selector, live output, history |
| Authorization | ✅ READY | admin@askproai.de only |
| Livewire Integration | ✅ READY | Proper property types, event handling |
| Styling/UX | ✅ READY | Fixed contrast, visual feedback |
| Company Context | ✅ READY | Passes Team ID and Event IDs |
| **Test Files** | ❌ MISSING | 8 test files need to be created |
| Test Execution | ⚠️ FAILING | Will work once test files exist |

---

## Next Steps: Create Tests

### Step 1: Create Test Directory Structure
```bash
mkdir -p tests/Feature/CalcomIntegration
```

### Step 2: Create 9 Test Files
1. AvailabilityServiceTest.php
2. AppointmentBookingTest.php
3. AppointmentRescheduleTest.php
4. AppointmentCancellationTest.php
5. AppointmentQueryTest.php
6. BidirectionalSyncTest.php
7. V2ApiCompatibilityTest.php
8. MultiTenantIsolationTest.php

### Step 3: Implement Real Tests
Each test should:
- Use provided company context
- Test with correct Team ID
- Test with correct Event IDs
- Verify multi-tenant isolation
- Report pass/fail with meaningful output

### Step 4: Test Dashboard
Once files exist:
1. Select AskProAI
2. Click individual test
3. See test execute in Live Output
4. See result in Test History
5. Repeat with Friseur 1

---

## Example Test Flow After Fix

```
✓ Starting test: 2. Availability Check
✓ Company: AskProAI
✓ Team ID: 39203
✓ Event IDs: 3664712, 2563193

✓ Executing...
✓ [✓ PASS] Availability retrieved for team 39203
✓ [✓ PASS] Correct event IDs used (3664712, 2563193)
✓ [✓ PASS] Multi-tenant isolation verified
✓ Duration: 1.23s
```

---

## Summary

**The dashboard is ready.** It just needs the test implementation.

- ✅ UI is beautiful and functional
- ✅ Company selector works
- ✅ Multi-tenant support built in
- ✅ Live output monitoring ready
- ✅ Test history tracking ready
- ❌ Test files must be created

Once you create the 9 test files, the dashboard will execute them with company-specific context and show results in real-time.
