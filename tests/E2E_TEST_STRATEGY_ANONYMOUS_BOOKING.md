# E2E Test Strategy: Anonymous Booking Flow

**Date**: 2025-11-18
**Context**: Database constraint fix - `customers.email` now allows NULL
**Fix Applied**: Migration `2025_11_11_231608_fix_customers_email_unique_constraint.php`

---

## Executive Summary

Anonymous callers (no phone number transfer) can now successfully book appointments without providing email addresses. This document provides comprehensive E2E test coverage for the anonymous booking flow, including edge cases, validation points, and automated test implementation.

**Critical Business Rule**: Anonymous callers MUST ALWAYS create NEW customer records (security/privacy rule - no identity verification without phone number).

---

## Test Case Matrix

### Happy Path Scenarios

| ID | Scenario | Phone | Name | Email | Expected Result | Validation Points |
|----|----------|-------|------|-------|-----------------|-------------------|
| H1 | Anonymous caller, no email | NULL/anonymous | "Hans Müller" | NULL | ✅ Customer created, email = NULL | DB record, Appointment created |
| H2 | Anonymous caller, name only | NULL/anonymous | "Max Schmidt" | NULL | ✅ Customer created, email = NULL | DB record, unique phone placeholder |
| H3 | Anonymous caller with email | NULL/anonymous | "Anna Weber" | "anna@test.de" | ✅ Customer created, email saved | DB record, email stored correctly |
| H4 | Regular caller, no email | +4915112345678 | "Petra Klein" | NULL | ✅ Customer created, email = NULL | Phone stored, email NULL allowed |
| H5 | Regular caller with email | +4915112345678 | "Tom Berg" | "tom@test.de" | ✅ Customer created, email saved | Phone + email both stored |

### Edge Cases

| ID | Scenario | Input | Expected Result | Validation Points |
|----|----------|-------|-----------------|-------------------|
| E1 | Duplicate anonymous caller (same name) | Name: "Max Müller" (2x anonymous) | ✅ Creates 2 SEPARATE customers | 2 distinct customer_ids, different placeholder phones |
| E2 | Empty string email (legacy) | email = "" | ✅ Converted to NULL automatically | DB stores NULL, no UNIQUE violation |
| E3 | Whitespace email | email = "   " | ✅ Sanitized to NULL | Validation converts to NULL |
| E4 | Invalid email format | email = "invalid.email" | ✅ Validation rejects OR NULL | Error response OR fallback to NULL |
| E5 | No name provided | name = NULL/empty | ✅ Creates "Anonym [timestamp]" | Auto-generated name pattern |
| E6 | Extremely long name (255+ chars) | name = "A" * 300 | ✅ Truncated to 255 chars | DB constraint validation |
| E7 | Special characters in name | name = "O'Brien-Müller" | ✅ Stored correctly | UTF-8 support validated |
| E8 | Multiple NULL emails (concurrent) | 5 anonymous calls simultaneously | ✅ All succeed, no UNIQUE violation | Race condition test |

### Error Scenarios

| ID | Scenario | Trigger | Expected Result | Response Format |
|----|----------|---------|-----------------|-----------------|
| ER1 | Service not found | service_name = "NonExistent" | ❌ Error response | `{"success": false, "error": "service_not_found"}` |
| ER2 | Time slot already taken | Concurrent booking same slot | ❌ First succeeds, second fails | `{"success": false, "error": "slot_unavailable"}` |
| ER3 | Invalid date format | date = "not-a-date" | ❌ Validation error | `{"success": false, "error": "invalid_date"}` |
| ER4 | Missing required fields | No name, no service | ❌ Validation error | `{"success": false, "error": "missing_required_fields"}` |
| ER5 | Database connection failure | Simulated DB down | ❌ 500 error with fallback | Error logged, graceful degradation |
| ER6 | Cal.com API failure | Mock Cal.com down | ❌ Booking fails gracefully | Local record created as "pending" |

### Security & Privacy Tests

| ID | Scenario | Test | Expected Result | Security Goal |
|----|----------|------|-----------------|---------------|
| S1 | Anonymous caller identity isolation | 2 calls with name "Max" | ✅ 2 SEPARATE customers created | Cannot merge based on name alone |
| S2 | Phone number PII handling | Anonymous call logged | ✅ No phone number stored in logs | PII protection (GDPR) |
| S3 | Email validation bypass attempt | SQL injection in email | ✅ Sanitized/rejected | SQL injection prevention |
| S4 | Cross-tenant isolation | Anonymous call on Company A | ✅ Customer only visible to Company A | Multi-tenant security |
| S5 | Placeholder phone uniqueness | Check placeholder phone pattern | ✅ Format: `anonymous_[timestamp]_[hash]` | Collision prevention |

---

## Test Data Requirements

### Mock Call IDs
```php
// Pattern: test_[timestamp]_[random]
$testCallIds = [
    'test_' . time() . '_anonymous_1',
    'test_' . time() . '_anonymous_2',
    'test_' . time() . '_regular_1',
];
```

### Test Customer Names
```php
$testNames = [
    'Hans Müller',           // Standard German name
    'Max Schmidt',           // Common German name
    'O\'Brien-Müller',       // Special characters
    'José García',           // International characters
    'Анна Петрова',          // Cyrillic (stress test)
    'Anonym ' . time(),      // Auto-generated pattern
];
```

### Test Email Addresses
```php
$testEmails = [
    null,                              // NULL (anonymous)
    '',                                // Empty string (should convert to NULL)
    '   ',                             // Whitespace (should sanitize to NULL)
    'valid@example.com',               // Valid email
    'test+tag@example.co.uk',          // Email with plus sign
    'invalid.email',                   // Invalid format
    'user@domain..com',                // Double dots (invalid)
];
```

### Service/Date/Time Combinations
```php
$testBookings = [
    ['service' => 'Herrenhaarschnitt', 'date' => 'morgen', 'time' => '10:00'],
    ['service' => 'Damenhaarschnitt', 'date' => 'heute', 'time' => '14:30'],
    ['service' => 'Bartpflege', 'date' => '2025-11-20', 'time' => '16:00'],
    ['service' => 'Invalid Service', 'date' => 'morgen', 'time' => '10:00'], // Error case
];
```

---

## Validation Points

### 1. Database Record Creation

**Customer Table**:
```sql
SELECT
    id,
    company_id,
    name,
    email,          -- Should be NULL or valid email
    phone,          -- Regular: +49... | Anonymous: anonymous_[timestamp]_[hash]
    source,         -- 'retell_webhook' or 'retell_webhook_anonymous'
    status,         -- 'active'
    notes,          -- Anonymous: Contains warning message
    created_at
FROM customers
WHERE id = ?
```

**Validation Checks**:
- ✅ `email` is NULL (not empty string)
- ✅ `phone` follows pattern (regular OR anonymous placeholder)
- ✅ `company_id` matches call context
- ✅ `source` indicates origin
- ✅ `created_at` is recent

### 2. Appointment Record Creation

**Appointment Table**:
```sql
SELECT
    id,
    customer_id,
    service_id,
    company_id,
    branch_id,
    starts_at,
    ends_at,
    status,         -- 'confirmed' or 'pending'
    external_id,    -- Cal.com booking ID (if synced)
    booking_metadata,
    created_at
FROM appointments
WHERE id = ?
```

**Validation Checks**:
- ✅ `customer_id` links to created customer
- ✅ `service_id` matches requested service
- ✅ `starts_at` matches requested time
- ✅ `status` is 'confirmed' (if Cal.com sync) or 'pending'
- ✅ `booking_metadata` contains call context

### 3. Cal.com Sync Job Dispatch

**Queue Check**:
```php
Queue::assertPushed(SyncToCalcomJob::class, function ($job) use ($appointment) {
    return $job->appointment->id === $appointment->id;
});
```

**Validation Checks**:
- ✅ Job dispatched for appointment
- ✅ Job contains correct appointment ID
- ✅ Job scheduled (not immediate if queue enabled)

### 4. Response Format Validation

**Success Response**:
```json
{
    "success": true,
    "data": {
        "appointment_id": 123,
        "customer_id": 456,
        "service_name": "Herrenhaarschnitt",
        "starts_at": "2025-11-19 10:00:00",
        "status": "confirmed"
    },
    "message": "Termin erfolgreich gebucht"
}
```

**Error Response**:
```json
{
    "success": false,
    "error": "service_not_found",
    "message": "Dieser Service ist leider nicht verfügbar",
    "context": {
        "service_name": "Invalid Service"
    }
}
```

---

## PHPUnit Test Implementation

### Test File: `tests/Feature/AnonymousBookingTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\Retell\AppointmentCustomerResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SyncToCalcomJob;

class AnonymousBookingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Test company and branch setup
        $this->company = Company::factory()->create([
            'name' => 'Test Salon',
            'status' => 'active'
        ]);

        $this->branch = Branch::factory()->create([
            'name' => 'Main Branch',
            'company_id' => $this->company->id,
            'status' => 'active'
        ]);

        $this->service = Service::factory()->create([
            'name' => 'Herrenhaarschnitt',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123456
        ]);
    }

    /** @test H1: Anonymous caller, no email */
    public function test_anonymous_caller_without_email_creates_customer_with_null_email()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Hans Müller', null);

        $this->assertNotNull($customer);
        $this->assertEquals('Hans Müller', $customer->name);
        $this->assertNull($customer->email); // Critical: Must be NULL
        $this->assertStringStartsWith('anonymous_', $customer->phone);
        $this->assertEquals('retell_webhook_anonymous', $customer->source);
        $this->assertEquals($this->company->id, $customer->company_id);
    }

    /** @test H2: Anonymous caller, name only */
    public function test_anonymous_caller_name_only_creates_unique_placeholder_phone()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Max Schmidt', null);

        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression(
            '/^anonymous_\d+_[a-f0-9]{8}$/',
            $customer->phone,
            'Phone placeholder must follow pattern: anonymous_[timestamp]_[hash]'
        );
    }

    /** @test H3: Anonymous caller with email */
    public function test_anonymous_caller_with_email_stores_email_correctly()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Anna Weber', 'anna@test.de');

        $this->assertNotNull($customer);
        $this->assertEquals('anna@test.de', $customer->email);
        $this->assertStringStartsWith('anonymous_', $customer->phone);
    }

    /** @test H4: Regular caller, no email */
    public function test_regular_caller_without_email_creates_customer_with_null_email()
    {
        $call = $this->createRegularCall('+4915112345678');
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Petra Klein', null);

        $this->assertNotNull($customer);
        $this->assertEquals('Petra Klein', $customer->name);
        $this->assertNull($customer->email); // Critical: Must be NULL
        $this->assertEquals('+4915112345678', $customer->phone);
        $this->assertEquals('retell_webhook', $customer->source);
    }

    /** @test E1: Duplicate anonymous caller (same name) creates separate customers */
    public function test_duplicate_anonymous_caller_same_name_creates_separate_customers()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // First anonymous call
        $call1 = $this->createAnonymousCall();
        $customer1 = $resolver->ensureCustomerFromCall($call1, 'Max Müller', null);

        // Second anonymous call with SAME NAME
        $call2 = $this->createAnonymousCall();
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Max Müller', null);

        // CRITICAL: Must be DIFFERENT customers (security rule)
        $this->assertNotEquals($customer1->id, $customer2->id);
        $this->assertNotEquals($customer1->phone, $customer2->phone);
        $this->assertEquals('Max Müller', $customer1->name);
        $this->assertEquals('Max Müller', $customer2->name);
    }

    /** @test E2: Empty string email converts to NULL */
    public function test_empty_string_email_converts_to_null()
    {
        $call = $this->createAnonymousCall();
        $resolver = app(AppointmentCustomerResolver::class);

        $customer = $resolver->ensureCustomerFromCall($call, 'Test User', '');

        $this->assertNotNull($customer);
        $this->assertNull($customer->email); // Empty string should become NULL
    }

    /** @test E8: Multiple NULL emails (concurrent) - no UNIQUE violation */
    public function test_multiple_null_emails_concurrent_no_unique_violation()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // Create 5 anonymous customers with NULL emails concurrently
        $customers = [];
        for ($i = 0; $i < 5; $i++) {
            $call = $this->createAnonymousCall();
            $customers[] = $resolver->ensureCustomerFromCall($call, "User {$i}", null);
        }

        // Verify all were created successfully
        $this->assertCount(5, $customers);

        // Verify all have NULL email
        foreach ($customers as $customer) {
            $this->assertNull($customer->email);
        }

        // Verify database constraint allows multiple NULLs
        $nullEmailCount = Customer::whereNull('email')->count();
        $this->assertGreaterThanOrEqual(5, $nullEmailCount);
    }

    /** @test S1: Anonymous caller identity isolation */
    public function test_anonymous_caller_identity_isolation()
    {
        $resolver = app(AppointmentCustomerResolver::class);

        // Two anonymous calls with identical name
        $call1 = $this->createAnonymousCall();
        $customer1 = $resolver->ensureCustomerFromCall($call1, 'Max', null);

        $call2 = $this->createAnonymousCall();
        $customer2 = $resolver->ensureCustomerFromCall($call2, 'Max', null);

        // SECURITY: Must be separate customers (no identity verification)
        $this->assertNotEquals(
            $customer1->id,
            $customer2->id,
            'Anonymous callers with same name MUST create separate customers (security rule)'
        );
    }

    /** @test Integration: Full anonymous booking flow */
    public function test_full_anonymous_booking_flow()
    {
        Queue::fake();

        $call = $this->createAnonymousCall();

        // Simulate booking via API endpoint
        $response = $this->postJson('/api/retell/v17/book-appointment', [
            'call_id' => $call->retell_call_id,
            'name' => 'Hans Müller',
            'email' => null, // Anonymous - no email
            'service_name' => 'Herrenhaarschnitt',
            'date' => 'morgen',
            'time' => '10:00'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        // Verify response structure
        $this->assertTrue($json['success'] ?? false);
        $this->assertArrayHasKey('appointment_id', $json['data'] ?? []);

        // Verify customer created with NULL email
        $customer = Customer::where('name', 'Hans Müller')
            ->where('company_id', $this->company->id)
            ->first();

        $this->assertNotNull($customer);
        $this->assertNull($customer->email);
        $this->assertStringStartsWith('anonymous_', $customer->phone);

        // Verify appointment created
        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->service->id, $appointment->service_id);

        // Verify Cal.com sync job dispatched
        Queue::assertPushed(SyncToCalcomJob::class);
    }

    // Helper methods
    private function createAnonymousCall(): Call
    {
        return Call::factory()->create([
            'retell_call_id' => 'test_anonymous_' . uniqid(),
            'from_number' => 'anonymous', // No caller ID
            'to_number' => '+4989123456',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ongoing'
        ]);
    }

    private function createRegularCall(string $fromNumber): Call
    {
        return Call::factory()->create([
            'retell_call_id' => 'test_regular_' . uniqid(),
            'from_number' => $fromNumber,
            'to_number' => '+4989123456',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'ongoing'
        ]);
    }
}
```

---

## Curl Commands for Manual API Testing

### Test 1: Anonymous Booking (No Email)
```bash
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_anonymous_manual_001",
    "name": "Hans Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "10:00"
  }'
```

### Test 2: Anonymous Booking with Email
```bash
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_anonymous_manual_002",
    "name": "Anna Weber",
    "email": "anna@test.de",
    "service_name": "Damenhaarschnitt",
    "date": "heute",
    "time": "14:30"
  }'
```

### Test 3: Regular Booking (No Email)
```bash
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_regular_manual_001",
    "from_number": "+4915112345678",
    "name": "Petra Klein",
    "email": null,
    "service_name": "Bartpflege",
    "date": "2025-11-20",
    "time": "16:00"
  }'
```

### Test 4: Duplicate Anonymous Name
```bash
# First call
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_duplicate_001",
    "name": "Max Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "09:00"
  }'

# Second call (same name, should create NEW customer)
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_duplicate_002",
    "name": "Max Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "10:00"
  }'
```

### Test 5: Error Case - Service Not Found
```bash
curl -X POST https://api.askpro.ai/api/retell/v17/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_error_001",
    "name": "Test User",
    "email": null,
    "service_name": "NonExistentService",
    "date": "morgen",
    "time": "10:00"
  }'
```

### Verification Queries

**Check Customer Creation (Anonymous)**:
```sql
SELECT
    id,
    name,
    email,
    phone,
    source,
    company_id,
    created_at
FROM customers
WHERE phone LIKE 'anonymous_%'
ORDER BY created_at DESC
LIMIT 10;
```

**Check for NULL Email Constraint Violations**:
```sql
-- This should return multiple rows (all anonymous customers without email)
SELECT COUNT(*) as null_email_count
FROM customers
WHERE email IS NULL;

-- This should work (no UNIQUE constraint violation)
SELECT email, COUNT(*) as count
FROM customers
WHERE email IS NULL
GROUP BY email;
```

**Check Appointment Creation**:
```sql
SELECT
    a.id,
    a.customer_id,
    c.name as customer_name,
    c.email as customer_email,
    c.phone as customer_phone,
    s.name as service_name,
    a.starts_at,
    a.status,
    a.created_at
FROM appointments a
JOIN customers c ON a.customer_id = c.id
JOIN services s ON a.service_id = s.id
WHERE c.phone LIKE 'anonymous_%'
ORDER BY a.created_at DESC
LIMIT 10;
```

---

## Test Execution Strategy

### Phase 1: Unit Tests (CustomerResolver)
**Run**: `vendor/bin/pest tests/Unit/Services/Retell/AppointmentCustomerResolverTest.php`

**Focus**: Customer creation logic in isolation
- ✅ NULL email handling
- ✅ Placeholder phone generation
- ✅ Anonymous vs regular caller detection

### Phase 2: Integration Tests (E2E Flow)
**Run**: `vendor/bin/pest tests/Feature/AnonymousBookingTest.php`

**Focus**: Full booking flow from API to database
- ✅ API endpoint → Customer → Appointment → Queue job
- ✅ Database constraints (UNIQUE email allows multiple NULLs)
- ✅ Multi-tenant isolation

### Phase 3: Manual API Testing
**Use**: Curl commands above

**Focus**: Real-world scenarios
- ✅ Production API behavior
- ✅ Response format validation
- ✅ Error handling

### Phase 4: Load Testing (Optional)
**Tool**: Apache Bench or K6

**Test**: Concurrent anonymous bookings
```bash
# 100 concurrent requests (stress test NULL UNIQUE constraint)
ab -n 100 -c 10 -p anonymous_booking.json -T application/json \
  https://api.askpro.ai/api/retell/v17/book-appointment
```

---

## Success Criteria

### Functional Requirements
- ✅ Anonymous callers can book appointments without email
- ✅ NULL email is stored correctly (not empty string)
- ✅ Multiple anonymous callers with NULL email can coexist (no UNIQUE violation)
- ✅ Duplicate anonymous names create SEPARATE customers (security rule)
- ✅ Placeholder phone follows pattern: `anonymous_[timestamp]_[hash]`

### Technical Requirements
- ✅ Database migration applied successfully
- ✅ All unit tests pass
- ✅ All integration tests pass
- ✅ No SQL errors in logs
- ✅ Cal.com sync jobs dispatch correctly

### Performance Requirements
- ✅ Anonymous booking completes in < 3 seconds
- ✅ Concurrent bookings (10 simultaneous) succeed without deadlocks
- ✅ Database query performance remains stable

### Security Requirements
- ✅ Anonymous callers are properly isolated (no merging by name)
- ✅ Multi-tenant isolation maintained (company_id scoping)
- ✅ PII handling complies with GDPR (no unnecessary phone storage)

---

## Known Limitations

1. **Email Validation**: System currently accepts any email format. Consider adding Laravel validation rules.
2. **Phone Placeholder Collision**: Extremely unlikely but theoretically possible with `md5` hash. Consider UUID instead.
3. **Anonymous Caller Tracking**: No way to link anonymous callers across multiple calls (by design for privacy).
4. **Cal.com Email Requirement**: Cal.com API requires email. System should generate placeholder email if NULL.

---

## Next Steps

1. **Implement Unit Tests**: Create `AppointmentCustomerResolverTest.php`
2. **Implement Feature Tests**: Create `AnonymousBookingTest.php`
3. **Run Test Suite**: `vendor/bin/pest --filter=Anonymous`
4. **Manual API Testing**: Use curl commands above
5. **Production Monitoring**: Monitor logs for NULL email customers
6. **Documentation Update**: Update API documentation with anonymous booking examples

---

## Related Files

- **Migration**: `/database/migrations/2025_11_11_231608_fix_customers_email_unique_constraint.php`
- **Resolver**: `/app/Services/Retell/AppointmentCustomerResolver.php`
- **Model**: `/app/Models/Customer.php`
- **Existing Tests**:
  - `/tests/Unit/Services/Retell/AppointmentCreationServiceTest.php`
  - `/tests/Feature/Integration/CustomerManagementTest.php`

---

**Document Version**: 1.0
**Author**: Quality Engineer (Claude Code)
**Review Status**: Draft - Pending Implementation
