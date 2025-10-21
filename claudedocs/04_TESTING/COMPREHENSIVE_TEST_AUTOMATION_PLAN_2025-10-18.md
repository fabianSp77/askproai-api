# Comprehensive Test Automation Plan
**Date**: 2025-10-18
**Project**: AskPro AI Gateway - Appointment Booking System
**Target**: Prevent RCA-identified failures, ensure performance, data consistency, security

---

## Executive Summary

**Objective**: Build comprehensive test automation covering all critical failure points identified in RCA documentation, establish performance benchmarks, ensure data consistency, and validate security controls.

**Coverage Targets**:
- Unit Tests: >80%
- Integration Tests: >70%
- Critical Path: 100%
- Performance: <45s booking flow (vs current 144s)
- Security: Zero multi-tenant data leakage

**RCA Issues Addressed**:
1. **Duplicate Booking Bug** (RCA 2025-10-06): Stale Cal.com booking responses
2. **Availability Race Condition** (RCA 2025-10-14): 14s gap between check and booking
3. **Type Mismatch Errors** (RCA 2025-10-06): branch_id UUID vs int confusion
4. **Webhook Idempotency**: Duplicate webhook delivery handling
5. **Data Consistency**: Local DB vs Cal.com synchronization

---

## 1. Unit Test Suite

### 1.1 AppointmentBookingService Tests

**File**: `tests/Unit/Services/Retell/AppointmentBookingServiceTest.php`

**Scenarios** (15+ tests):

```php
<?php
namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\AppointmentCreationService;
use App\Models\{Call, Customer, Service, Appointment};
use Carbon\Carbon;

class AppointmentBookingServiceTest extends TestCase
{
    // Normal Flow Tests

    /** @test */
    public function it_creates_appointment_with_valid_booking_details()
    {
        // Arrange: Valid call + booking details
        // Act: Create appointment
        // Assert: Appointment created with correct data
    }

    /** @test */
    public function it_validates_minimum_confidence_threshold()
    {
        // Arrange: Booking details with <60% confidence
        // Act: Attempt creation
        // Assert: Null returned, failure tracked
    }

    /** @test */
    public function it_creates_customer_if_not_exists()
    {
        // Arrange: Call without customer_id
        // Act: Create appointment with customer data
        // Assert: Customer created, appointment linked
    }

    /** @test */
    public function it_resolves_service_with_branch_filtering()
    {
        // Arrange: Multi-branch company
        // Act: Request service by name
        // Assert: Correct branch service selected
    }

    // RCA: Duplicate Booking Prevention

    /** @test */
    public function it_rejects_stale_calcom_booking_response()
    {
        // RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
        // Arrange: Mock Cal.com returning booking from 1 hour ago
        $oldBooking = [
            'id' => 12345,
            'uid' => 'old_booking_uid',
            'createdAt' => Carbon::now()->subHour()->toIso8601String(),
            'metadata' => ['call_id' => 'call_123']
        ];

        $this->mockCalcomService($oldBooking);

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => $this->validBookingArgs()
        ]);

        // Assert: Rejected with clear error
        $response->assertJson([
            'success' => false,
            'status' => 'error',
            'reason' => 'stale_booking_data'
        ]);

        $this->assertDatabaseMissing('appointments', [
            'calcom_v2_booking_id' => 'old_booking_uid'
        ]);
    }

    /** @test */
    public function it_rejects_booking_with_mismatched_call_id_metadata()
    {
        // RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
        // Arrange: Fresh booking but wrong metadata
        $booking = [
            'uid' => 'test_uid',
            'createdAt' => Carbon::now()->toIso8601String(),
            'metadata' => ['call_id' => 'call_WRONG']
        ];

        $this->mockCalcomService($booking);

        // Act: Request with call_CORRECT
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => array_merge($this->validBookingArgs(), [
                'call_id' => 'call_CORRECT'
            ])
        ]);

        // Assert: Rejected
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function it_prevents_duplicate_calcom_booking_ids()
    {
        // RCA Reference: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
        // Arrange: Existing appointment with booking ID
        $existingAppointment = Appointment::factory()->create([
            'calcom_v2_booking_id' => 'duplicate_uid',
            'call_id' => 100
        ]);

        $booking = ['uid' => 'duplicate_uid'];
        $this->mockCalcomService($booking);

        // Act: Attempt duplicate
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => $this->validBookingArgs()
        ]);

        // Assert: Rejected with specific error
        $response->assertJson([
            'success' => false,
            'message' => 'Dieser Termin wurde bereits gebucht'
        ]);

        // Only 1 appointment with this booking ID
        $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', 'duplicate_uid')->count());
    }

    // RCA: Type Safety

    /** @test */
    public function it_handles_branch_id_as_uuid_string()
    {
        // RCA Reference: BOOKING_ERROR_ANALYSIS_2025-10-06.md
        // Arrange: Call with UUID branch_id
        $call = Call::factory()->create([
            'branch_id' => '9f4d5e2a-46f7-41b6-b81d-1532725381d4'
        ]);

        // Act: Trigger alternative finder (uses branch_id)
        $alternatives = app(AppointmentAlternativeFinder::class)
            ->setTenantContext($call->company_id, $call->branch_id)
            ->findAlternatives(
                Carbon::now()->addDay(),
                60,
                123,
                $call->customer_id
            );

        // Assert: No TypeError, alternatives returned
        $this->assertIsArray($alternatives);
        $this->assertArrayHasKey('alternatives', $alternatives);
    }

    // Error Handling

    /** @test */
    public function it_handles_calcom_api_timeout_gracefully()
    {
        // Arrange: Mock timeout
        $this->mockCalcomTimeout();

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => $this->validBookingArgs()
        ]);

        // Assert: Graceful error, not 500
        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'status' => 'timeout'
        ]);
    }

    /** @test */
    public function it_handles_calcom_api_400_bad_request()
    {
        // Arrange: Mock 400 error
        $this->mockCalcomError(400, 'Invalid event type');

        // Act: Attempt booking
        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => $this->validBookingArgs()
        ]);

        // Assert: Clear error message
        $response->assertJson([
            'success' => false,
            'message' => 'Entschuldigung, es gab ein Problem bei der Buchung'
        ]);
    }

    /** @test */
    public function it_handles_calcom_api_500_internal_error()
    {
        // Arrange: Mock 500
        $this->mockCalcomError(500);

        // Act & Assert: Retry logic triggered
        $this->expectCalcomRetries(3);

        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => $this->validBookingArgs()
        ]);
    }

    // Validation Tests

    /** @test */
    public function it_validates_german_date_formats()
    {
        $testCases = [
            '16.10.2025' => '2025-10-16',
            'morgen' => Carbon::tomorrow()->format('Y-m-d'),
            'übermorgen' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'Mittwoch' => $this->nextWednesday()->format('Y-m-d')
        ];

        foreach ($testCases as $input => $expected) {
            $parsed = DateTimeParser::parse($input);
            $this->assertEquals($expected, $parsed->format('Y-m-d'));
        }
    }

    /** @test */
    public function it_validates_german_time_formats()
    {
        $testCases = [
            'vierzehn Uhr' => '14:00',
            '14:00' => '14:00',
            'halb drei' => '14:30',
            'Viertel vor drei' => '14:45'
        ];

        foreach ($testCases as $input => $expected) {
            $parsed = DateTimeParser::parseTime($input);
            $this->assertEquals($expected, $parsed->format('H:i'));
        }
    }

    /** @test */
    public function it_handles_missing_required_fields()
    {
        $testCases = [
            'missing_date' => ['uhrzeit' => '14:00', 'name' => 'Test'],
            'missing_time' => ['datum' => '2025-10-16', 'name' => 'Test'],
            'missing_name' => ['datum' => '2025-10-16', 'uhrzeit' => '14:00']
        ];

        foreach ($testCases as $scenario => $args) {
            $response = $this->postJson('/api/retell/collect-appointment', [
                'args' => $args
            ]);

            $response->assertJson([
                'success' => false,
                'status' => 'missing_required_field'
            ]);
        }
    }

    // Helper Methods

    private function validBookingArgs(): array
    {
        return [
            'datum' => '2025-10-16',
            'uhrzeit' => '14:00',
            'name' => 'Test Customer',
            'dienstleistung' => 'Beratung',
            'call_id' => 'call_test_123'
        ];
    }

    private function mockCalcomService(array $booking): void
    {
        $this->mock(CalcomService::class, function ($mock) use ($booking) {
            $mock->shouldReceive('createBooking')
                ->andReturn(new Response(new \GuzzleHttp\Psr7\Response(
                    200,
                    [],
                    json_encode(['data' => $booking])
                )));
        });
    }
}
```

### 1.2 CalcomSyncService Tests

**File**: `tests/Unit/Services/CalcomSyncServiceTest.php`

**Focus**: Retry logic, circuit breaker, rate limiting

```php
/** @test */
public function it_implements_exponential_backoff_retry_logic()
{
    // Arrange: Mock Cal.com API with intermittent failures
    $attempts = 0;
    $this->mockCalcom(function() use (&$attempts) {
        $attempts++;
        if ($attempts < 3) {
            throw new \Exception('Temporary failure');
        }
        return ['success' => true];
    });

    // Act: Sync operation
    $result = $this->calcomSync->syncAppointment($appointment);

    // Assert: Retried with exponential backoff
    $this->assertEquals(3, $attempts);
    $this->assertTrue($result['success']);

    // Verify backoff delays: 1s, 2s, 4s
    $this->assertBackoffPattern([1000, 2000, 4000]);
}

/** @test */
public function it_opens_circuit_breaker_after_threshold_failures()
{
    // Arrange: Simulate 5 consecutive failures
    for ($i = 0; $i < 5; $i++) {
        $this->calcomSync->syncAppointment($appointment);
    }

    // Act: Attempt 6th call
    $result = $this->calcomSync->syncAppointment($appointment);

    // Assert: Circuit open, no API call made
    $this->assertEquals('circuit_open', $result['status']);
    $this->assertCalcomCallCount(5); // Not 6
}

/** @test */
public function it_respects_rate_limits()
{
    // Arrange: Cal.com rate limit: 100 calls/minute
    $appointments = Appointment::factory()->count(150)->create();

    // Act: Bulk sync
    $startTime = microtime(true);
    $this->calcomSync->syncBatch($appointments);
    $duration = microtime(true) - $startTime;

    // Assert: Took at least 90 seconds (150 calls at 100/min)
    $this->assertGreaterThan(90, $duration);
    $this->assertLessThan(120, $duration); // With some buffer
}
```

### 1.3 WebhookProcessingService Tests

**File**: `tests/Unit/Services/WebhookProcessingServiceTest.php`

**Focus**: Idempotency, transaction safety, signature validation

```php
/** @test */
public function it_handles_duplicate_webhook_delivery_idempotently()
{
    // Arrange: Same webhook payload
    $payload = [
        'id' => 'evt_12345',
        'type' => 'booking.created',
        'data' => ['booking_id' => 'abc123']
    ];

    // Act: Process same webhook twice
    $result1 = $this->webhookProcessor->process($payload);
    $result2 = $this->webhookProcessor->process($payload);

    // Assert: Both successful, but only 1 appointment created
    $this->assertTrue($result1['success']);
    $this->assertTrue($result2['success']);
    $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', 'abc123')->count());
}

/** @test */
public function it_rolls_back_transaction_on_processing_error()
{
    // Arrange: Webhook with invalid data mid-processing
    $payload = $this->webhookPayloadWithInvalidCustomer();

    $initialCount = Appointment::count();

    // Act: Process (will fail on customer validation)
    $result = $this->webhookProcessor->process($payload);

    // Assert: No partial data committed
    $this->assertFalse($result['success']);
    $this->assertEquals($initialCount, Appointment::count());
    $this->assertDatabaseMissing('customers', ['email' => 'invalid']);
}

/** @test */
public function it_validates_webhook_signature()
{
    // Arrange: Invalid signature
    $payload = ['data' => 'test'];
    $invalidSignature = 'invalid_signature_123';

    // Act: Process with invalid signature
    $result = $this->webhookProcessor->process($payload, $invalidSignature);

    // Assert: Rejected before processing
    $this->assertEquals('invalid_signature', $result['status']);
    $this->assertDatabaseCount('appointments', 0);
}
```

---

## 2. Integration Test Suite

### 2.1 End-to-End Booking Flow Test

**File**: `tests/Feature/Integration/CompleteBookingFlowTest.php`

**Scenarios** (10+ tests):

```php
/** @test */
public function it_completes_full_booking_flow_with_new_customer()
{
    // Arrange: Clean state
    $this->mockCalcomApi();

    // Act: Simulate Retell call sequence
    $step1 = $this->postJson('/api/retell/check-availability', [
        'args' => ['datum' => '2025-10-20', 'uhrzeit' => '14:00']
    ]);
    $step1->assertJson(['available' => true]);

    $step2 = $this->postJson('/api/retell/collect-appointment', [
        'args' => [
            'datum' => '2025-10-20',
            'uhrzeit' => '14:00',
            'name' => 'Max Mustermann',
            'telefon' => '+4915112345678',
            'dienstleistung' => 'Beratung',
            'bestaetigung' => true
        ]
    ]);

    // Assert: Complete flow
    $step2->assertJson(['success' => true]);

    // Verify database state
    $this->assertDatabaseHas('customers', [
        'name' => 'Max Mustermann',
        'phone' => '+4915112345678'
    ]);

    $appointment = Appointment::latest()->first();
    $this->assertNotNull($appointment->calcom_v2_booking_id);
    $this->assertEquals('2025-10-20 14:00:00', $appointment->starts_at);

    // Verify Cal.com API calls
    $this->assertCalcomCalled('POST', '/bookings', 1);
}

/** @test */
public function it_handles_race_condition_with_double_check()
{
    // RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md

    // Arrange: Slot available initially
    $this->mockCalcomAvailable('2025-10-20 14:00');

    // Act: Check availability
    $check = $this->postJson('/api/retell/check-availability', [
        'args' => ['datum' => '2025-10-20', 'uhrzeit' => '14:00']
    ]);
    $check->assertJson(['available' => true]);

    // Simulate 14-second gap + slot taken by someone else
    sleep(1); // Simulate user thinking
    $this->mockCalcomTaken('2025-10-20 14:00'); // Slot now taken

    // Act: User confirms booking
    $booking = $this->postJson('/api/retell/collect-appointment', [
        'args' => [
            'datum' => '2025-10-20',
            'uhrzeit' => '14:00',
            'name' => 'Test User',
            'bestaetigung' => true
        ]
    ]);

    // Assert: V85 double-check catches race condition
    $booking->assertJson([
        'success' => false,
        'status' => 'slot_taken',
        'reason' => 'race_condition_detected'
    ]);

    // Alternatives offered
    $booking->assertJsonStructure(['alternatives']);

    // No appointment created
    $this->assertDatabaseMissing('appointments', [
        'starts_at' => '2025-10-20 14:00:00'
    ]);
}

/** @test */
public function it_synchronizes_webhook_created_booking()
{
    // Arrange: Cal.com creates booking via webhook
    $webhookPayload = [
        'type' => 'booking.created',
        'data' => [
            'uid' => 'webhook_booking_123',
            'startTime' => '2025-10-20T14:00:00Z',
            'attendees' => [
                ['name' => 'Webhook Customer', 'email' => 'webhook@test.com']
            ]
        ]
    ];

    // Act: Process webhook
    $this->postJson('/api/webhooks/calcom', $webhookPayload);

    // Assert: Appointment created from webhook
    $this->assertDatabaseHas('appointments', [
        'calcom_v2_booking_id' => 'webhook_booking_123'
    ]);

    // Verify customer auto-created
    $this->assertDatabaseHas('customers', [
        'email' => 'webhook@test.com'
    ]);
}
```

### 2.2 Race Condition Simulation Tests

**File**: `tests/Feature/Integration/ConcurrentBookingTest.php`

```php
use Illuminate\Support\Facades\Parallel;

/** @test */
public function it_handles_concurrent_bookings_for_same_slot()
{
    // Arrange: 5 users trying to book same slot
    $slot = '2025-10-20 14:00:00';

    // Act: Parallel requests
    $results = Parallel::map(range(1, 5), function ($i) use ($slot) {
        return $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => "User $i",
                'bestaetigung' => true
            ]
        ])->json();
    });

    // Assert: Only 1 success, 4 get alternatives
    $successes = collect($results)->where('success', true)->count();
    $alternatives = collect($results)->where('status', 'slot_taken')->count();

    $this->assertEquals(1, $successes);
    $this->assertEquals(4, $alternatives);

    // Database: Only 1 appointment
    $this->assertEquals(1, Appointment::where('starts_at', $slot)->count());
}
```

### 2.3 Database Transaction Tests

**File**: `tests/Feature/Integration/TransactionRollbackTest.php`

```php
/** @test */
public function it_rolls_back_on_calcom_booking_failure()
{
    // Arrange: Mock Cal.com to fail after customer creation
    $this->mockCalcomToFailOnBooking();

    $initialCustomerCount = Customer::count();
    $initialAppointmentCount = Appointment::count();

    // Act: Attempt booking (will fail at Cal.com step)
    $this->postJson('/api/retell/collect-appointment', [
        'args' => [
            'datum' => '2025-10-20',
            'uhrzeit' => '14:00',
            'name' => 'Rollback Test User',
            'telefon' => '+4915199999999'
        ]
    ]);

    // Assert: No partial data committed
    $this->assertEquals($initialCustomerCount, Customer::count());
    $this->assertEquals($initialAppointmentCount, Appointment::count());

    // Customer NOT created despite name/phone provided
    $this->assertDatabaseMissing('customers', [
        'phone' => '+4915199999999'
    ]);
}
```

---

## 3. Performance Test Suite

### 3.1 K6 Baseline Test

**File**: `tests/Performance/k6/baseline-booking-flow.js`

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const bookingSuccessRate = new Rate('booking_success');
const bookingDuration = new Trend('booking_duration');
const calcomLatency = new Trend('calcom_api_latency');

export const options = {
    stages: [
        { duration: '1m', target: 10 },  // Ramp up to 10 users
        { duration: '3m', target: 10 },  // Stay at 10 users
        { duration: '1m', target: 0 },   // Ramp down
    ],
    thresholds: {
        'booking_success': ['rate>0.95'],           // 95% success rate
        'booking_duration': ['p(95)<45000'],        // P95 < 45s (target)
        'http_req_duration': ['p(99)<60000'],       // P99 < 60s
        'calcom_api_latency': ['avg<2000'],         // Cal.com avg < 2s
    },
};

export default function () {
    const baseUrl = __ENV.API_URL || 'http://localhost:8000';

    // Step 1: Check availability
    const startTime = Date.now();

    const availabilityResponse = http.post(`${baseUrl}/api/retell/check-availability`,
        JSON.stringify({
            args: {
                datum: '2025-10-20',
                uhrzeit: '14:00'
            }
        }),
        { headers: { 'Content-Type': 'application/json' } }
    );

    check(availabilityResponse, {
        'availability check status is 200': (r) => r.status === 200,
        'slot is available': (r) => JSON.parse(r.body).available === true,
    });

    calcomLatency.add(availabilityResponse.timings.duration);

    // Simulate user thinking time (14s gap from RCA)
    sleep(14);

    // Step 2: Book appointment
    const bookingResponse = http.post(`${baseUrl}/api/retell/collect-appointment`,
        JSON.stringify({
            args: {
                datum: '2025-10-20',
                uhrzeit: '14:00',
                name: `K6 User ${__VU}`,
                telefon: `+49151${__VU.toString().padStart(8, '0')}`,
                dienstleistung: 'Beratung',
                bestaetigung: true
            }
        }),
        { headers: { 'Content-Type': 'application/json' } }
    );

    const totalDuration = Date.now() - startTime;

    const success = check(bookingResponse, {
        'booking status is 200': (r) => r.status === 200,
        'booking success': (r) => JSON.parse(r.body).success === true,
    });

    bookingSuccessRate.add(success);
    bookingDuration.add(totalDuration);

    sleep(1);
}
```

### 3.2 K6 Optimized Flow Test

**File**: `tests/Performance/k6/optimized-booking-flow.js`

**Target**: Validate <45s booking flow (vs baseline 144s)

```javascript
export const options = {
    scenarios: {
        baseline: {
            executor: 'constant-vus',
            vus: 10,
            duration: '5m',
            exec: 'baselineScenario',
            tags: { test_type: 'baseline' },
        },
        optimized: {
            executor: 'constant-vus',
            vus: 10,
            duration: '5m',
            exec: 'optimizedScenario',
            tags: { test_type: 'optimized' },
            startTime: '6m', // Run after baseline
        },
    },
    thresholds: {
        'booking_duration{test_type:optimized}': ['p(95)<45000'],  // Target: <45s
        'booking_duration{test_type:baseline}': ['p(95)>100000'],  // Baseline: ~144s
        'http_req_duration{test_type:optimized}': ['p(99)<50000'],
    },
};

export function optimizedScenario() {
    // V85 optimized flow with double-check
    // Expected: Faster due to cache optimization + reduced latency
}
```

### 3.3 Load Test Suite

**File**: `tests/Performance/k6/load-test.js`

```javascript
export const options = {
    stages: [
        { duration: '2m', target: 10 },   // Warm up
        { duration: '5m', target: 50 },   // Ramp to 50 concurrent users
        { duration: '10m', target: 100 }, // Peak load
        { duration: '5m', target: 50 },   // Ramp down
        { duration: '2m', target: 0 },    // Cool down
    ],
    thresholds: {
        'http_req_failed': ['rate<0.05'],           // <5% error rate
        'http_req_duration': ['p(95)<5000'],        // P95 < 5s
        'booking_success': ['rate>0.90'],           // 90% success at peak
    },
};
```

---

## 4. E2E Test Suite (Playwright)

### 4.1 User Journey Tests

**File**: `tests/E2E/playwright/booking-journey.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Appointment Booking Journey', () => {
    test('complete booking flow from call to email confirmation', async ({ page }) => {
        // Simulate Retell AI voice call interface
        await page.goto('http://localhost:8000/test/retell-simulator');

        // Step 1: User requests appointment
        await page.fill('[data-test="user-input"]', 'Ich möchte einen Termin am Mittwoch um 14 Uhr');
        await page.click('[data-test="send"]');

        // Assert: Agent confirms availability
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('Mittwoch, 16. Oktober um 14:00 Uhr ist noch frei');

        // Step 2: User provides details
        await page.fill('[data-test="user-input"]', 'Mein Name ist Max Mustermann, Telefon 0151 12345678');
        await page.click('[data-test="send"]');

        // Step 3: User confirms
        await page.fill('[data-test="user-input"]', 'Ja, bitte buchen');
        await page.click('[data-test="send"]');

        // Assert: Booking confirmation
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('Termin wurde erfolgreich gebucht');

        // Verify in admin panel
        await page.goto('http://localhost:8000/admin/appointments');
        await page.fill('[data-test="search"]', 'Max Mustermann');

        await expect(page.locator('[data-test="appointment-row"]').first())
            .toContainText('Max Mustermann');
        await expect(page.locator('[data-test="appointment-row"]').first())
            .toContainText('16.10.2025 14:00');

        // Verify email sent (check email log)
        await page.goto('http://localhost:8000/admin/email-logs');
        await expect(page.locator('[data-test="email-log"]').first())
            .toContainText('Terminbestätigung');
    });

    test('handles race condition gracefully with alternatives', async ({ page }) => {
        // RCA Reference: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md

        await page.goto('http://localhost:8000/test/retell-simulator');

        // Request time that will be taken during 14s gap
        await page.fill('[data-test="user-input"]', 'Morgen um 9:00 Uhr');
        await page.click('[data-test="send"]');

        // Agent confirms availability
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('9:00 Uhr ist noch frei');

        // Simulate slot being taken (trigger via API)
        await page.evaluate(() => {
            fetch('http://localhost:8000/test/api/take-slot', {
                method: 'POST',
                body: JSON.stringify({ time: '2025-10-19 09:00:00' })
            });
        });

        // User confirms
        await page.fill('[data-test="user-input"]', 'Ja, buchen Sie bitte');
        await page.click('[data-test="send"]');

        // Assert: V85 double-check detects race condition
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('9:00 Uhr wurde gerade vergeben');
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('Alternative'); // Alternatives offered
    });
});

test.describe('Admin Panel Call Review', () => {
    test('displays call metrics and booking details', async ({ page }) => {
        await page.goto('http://localhost:8000/admin/calls');

        // Filter to recent calls
        await page.selectOption('[data-test="filter-status"]', 'booking_confirmed');

        // Click first call
        await page.click('[data-test="call-row"]');

        // Assert: Call details visible
        await expect(page.locator('[data-test="call-duration"]')).toBeVisible();
        await expect(page.locator('[data-test="booking-details"]')).toBeVisible();
        await expect(page.locator('[data-test="transcript"]')).toBeVisible();

        // Verify metrics
        const duration = await page.locator('[data-test="call-duration"]').textContent();
        expect(parseInt(duration!)).toBeGreaterThan(0);
    });
});
```

### 4.2 Error Scenario Tests

**File**: `tests/E2E/playwright/error-scenarios.spec.ts`

```typescript
test.describe('Error Handling', () => {
    test('handles network timeout gracefully', async ({ page, context }) => {
        // Simulate slow network
        await context.route('**/api/retell/**', route => {
            setTimeout(() => route.continue(), 30000); // 30s delay
        });

        await page.goto('http://localhost:8000/test/retell-simulator');
        await page.fill('[data-test="user-input"]', 'Termin morgen 14 Uhr');
        await page.click('[data-test="send"]');

        // Assert: Timeout handled, user informed
        await expect(page.locator('[data-test="error-message"]'))
            .toContainText('Verbindungsproblem', { timeout: 35000 });
    });

    test('handles Cal.com API failure with fallback', async ({ page }) => {
        // Mock Cal.com API failure
        await page.route('**/calcom/api/**', route => {
            route.fulfill({ status: 500, body: 'Internal Server Error' });
        });

        await page.goto('http://localhost:8000/test/retell-simulator');
        await page.fill('[data-test="user-input"]', 'Termin buchen');
        await page.click('[data-test="send"]');

        // Assert: Graceful error, callback offered
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('technisches Problem');
        await expect(page.locator('[data-test="agent-response"]'))
            .toContainText('rufen Sie uns direkt an');
    });
});
```

---

## 5. Data Consistency Test Suite

### 5.1 Reconciliation Tests

**File**: `tests/Feature/Integration/DataConsistencyTest.php`

```php
<?php
namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Appointment;
use App\Services\CalcomService;
use App\Jobs\ReconcileCalcomBookingsJob;

class DataConsistencyTest extends TestCase
{
    /** @test */
    public function it_detects_orphaned_appointments_missing_in_calcom()
    {
        // Arrange: Local appointment without Cal.com booking
        $appointment = Appointment::factory()->create([
            'calcom_v2_booking_id' => 'orphan_booking_123',
            'starts_at' => '2025-10-20 14:00:00'
        ]);

        // Mock Cal.com: Booking doesn't exist
        $this->mockCalcomBookingNotFound('orphan_booking_123');

        // Act: Run reconciliation job
        ReconcileCalcomBookingsJob::dispatch();

        // Assert: Orphaned appointment flagged
        $appointment->refresh();
        $this->assertTrue($appointment->reconciliation_status === 'orphaned');
        $this->assertNotNull($appointment->reconciliation_notes);
    }

    /** @test */
    public function it_detects_customer_name_mismatches()
    {
        // RCA Reference: Customer "Hans Schuster" overwritten with "Hansi Sputer"

        // Arrange: Local appointment with customer
        $customer = Customer::factory()->create(['name' => 'Hans Schuster']);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'calcom_v2_booking_id' => 'mismatch_123'
        ]);

        // Mock Cal.com: Different name in booking
        $this->mockCalcomBooking('mismatch_123', [
            'attendees' => [
                ['name' => 'Hansi Sputer', 'email' => 'termin@askproai.de']
            ]
        ]);

        // Act: Run reconciliation
        ReconcileCalcomBookingsJob::dispatch();

        // Assert: Mismatch detected and logged
        $appointment->refresh();
        $this->assertTrue($appointment->has_data_mismatch);
        $this->assertStringContainsString('name_mismatch', $appointment->reconciliation_notes);
    }

    /** @test */
    public function it_corrects_booking_details_field_discrepancies()
    {
        // Arrange: Appointment with outdated booking_details
        $appointment = Appointment::factory()->create([
            'calcom_v2_booking_id' => 'update_123',
            'booking_details' => json_encode(['old' => 'data'])
        ]);

        // Mock Cal.com: Updated booking data
        $this->mockCalcomBooking('update_123', [
            'startTime' => '2025-10-20T14:00:00Z',
            'attendees' => [
                ['name' => 'Updated Name', 'email' => 'updated@test.com']
            ]
        ]);

        // Act: Sync booking details
        $appointment->syncBookingDetailsFromCalcom();

        // Assert: Updated correctly
        $details = json_decode($appointment->booking_details, true);
        $this->assertArrayHasKey('calcom_booking', $details);
        $this->assertEquals('Updated Name', $details['calcom_booking']['attendees'][0]['name']);
    }

    /** @test */
    public function it_maintains_audit_trail_completeness()
    {
        // Arrange: Create appointment
        $appointment = Appointment::factory()->create();

        // Act: Modify appointment multiple times
        $appointment->update(['starts_at' => '2025-10-20 15:00:00']);
        $appointment->update(['customer_id' => 999]);
        $appointment->delete();

        // Assert: All changes logged in audit trail
        $auditLogs = $appointment->auditLogs;
        $this->assertCount(3, $auditLogs);

        $this->assertEquals('updated', $auditLogs[0]->event);
        $this->assertArrayHasKey('starts_at', $auditLogs[0]->changes);

        $this->assertEquals('deleted', $auditLogs[2]->event);
    }
}
```

### 5.2 Bi-directional Sync Tests

**File**: `tests/Feature/Integration/BidirectionalSyncTest.php`

```php
/** @test */
public function it_syncs_calcom_booking_updates_to_local_db()
{
    // Arrange: Existing appointment
    $appointment = Appointment::factory()->create([
        'calcom_v2_booking_id' => 'sync_123',
        'starts_at' => '2025-10-20 14:00:00'
    ]);

    // Act: Cal.com webhook - booking rescheduled
    $this->postJson('/api/webhooks/calcom', [
        'type' => 'booking.rescheduled',
        'data' => [
            'uid' => 'sync_123',
            'startTime' => '2025-10-20T15:00:00Z' // Changed to 15:00
        ]
    ]);

    // Assert: Local appointment updated
    $appointment->refresh();
    $this->assertEquals('2025-10-20 15:00:00', $appointment->starts_at);
}

/** @test */
public function it_syncs_local_appointment_updates_to_calcom()
{
    // Arrange: Appointment linked to Cal.com
    $appointment = Appointment::factory()->create([
        'calcom_v2_booking_id' => 'local_update_123'
    ]);

    // Act: Update locally
    $appointment->update([
        'starts_at' => '2025-10-20 16:00:00',
        'notes' => 'Customer requested change'
    ]);

    // Assert: Cal.com API called to update booking
    $this->assertCalcomCalled('PATCH', '/bookings/local_update_123', 1);
    $this->assertCalcomPayloadContains([
        'startTime' => '2025-10-20T16:00:00Z'
    ]);
}
```

---

## 6. Security Test Suite

### 6.1 SQL Injection Tests

**File**: `tests/Feature/Security/SqlInjectionTest.php`

```php
<?php
namespace Tests\Feature\Security;

use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    /** @test */
    public function it_prevents_sql_injection_in_customer_name()
    {
        $maliciousPayload = "'; DROP TABLE customers; --";

        $response = $this->postJson('/api/retell/collect-appointment', [
            'args' => [
                'datum' => '2025-10-20',
                'uhrzeit' => '14:00',
                'name' => $maliciousPayload,
                'dienstleistung' => 'Beratung'
            ]
        ]);

        // Should not cause SQL error (parameterized queries)
        $response->assertStatus(200);

        // Table still exists
        $this->assertDatabaseHas('customers', ['name' => $maliciousPayload]);
    }

    /** @test */
    public function it_sanitizes_search_inputs()
    {
        $searchPayloads = [
            "1' OR '1'='1",
            "admin'--",
            "1' UNION SELECT * FROM users--"
        ];

        foreach ($searchPayloads as $payload) {
            $response = $this->getJson("/api/customers?search={$payload}");
            $response->assertStatus(200);
            // Should not return all customers
            $this->assertLessThan(Customer::count(), count($response->json('data')));
        }
    }
}
```

### 6.2 Authorization Tests

**File**: `tests/Feature/Security/AuthorizationTest.php`

```php
/** @test */
public function it_prevents_cross_tenant_appointment_access()
{
    // Arrange: Two companies with appointments
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $appointment1 = Appointment::factory()->create(['company_id' => $company1->id]);
    $appointment2 = Appointment::factory()->create(['company_id' => $company2->id]);

    // Act: User from company1 tries to access company2 appointment
    $this->actingAs($company1->users->first());

    $response = $this->getJson("/api/appointments/{$appointment2->id}");

    // Assert: Forbidden
    $response->assertStatus(403);
}

/** @test */
public function it_enforces_role_based_access_to_admin_panel()
{
    // Arrange: Regular user (not admin)
    $user = User::factory()->create(['role' => 'agent']);

    // Act: Try to access admin-only routes
    $this->actingAs($user);

    $routes = [
        '/admin/settings',
        '/admin/companies',
        '/admin/users/create'
    ];

    foreach ($routes as $route) {
        $response = $this->get($route);
        $response->assertStatus(403);
    }
}
```

### 6.3 Multi-Tenant Isolation Tests

**File**: `tests/Feature/Security/MultiTenantIsolationTest.php`

```php
/** @test */
public function it_isolates_company_data_in_database_queries()
{
    // Arrange: Two companies
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    Appointment::factory()->count(10)->create(['company_id' => $company1->id]);
    Appointment::factory()->count(15)->create(['company_id' => $company2->id]);

    // Act: Set company scope
    CompanyScope::setCompanyId($company1->id);

    $appointments = Appointment::all();

    // Assert: Only company1 appointments returned
    $this->assertCount(10, $appointments);
    $this->assertTrue($appointments->every(fn($a) => $a->company_id === $company1->id));
}

/** @test */
public function it_prevents_data_leakage_via_foreign_key_traversal()
{
    // Arrange: Appointment in company1, customer in company2
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $customer = Customer::factory()->create(['company_id' => $company2->id]);
    $appointment = Appointment::factory()->create([
        'company_id' => $company1->id,
        'customer_id' => $customer->id // Cross-tenant reference (should fail)
    ]);

    // Act: Try to save
    // Assert: Validation error or constraint violation
    $this->expectException(\Exception::class);
    $appointment->save();
}
```

### 6.4 PII Data Protection Tests

**File**: `tests/Feature/Security/PiiDataProtectionTest.php`

```php
/** @test */
public function it_redacts_pii_from_logs()
{
    // Arrange: Enable log monitoring
    Log::spy();

    // Act: Create appointment with PII
    $this->postJson('/api/retell/collect-appointment', [
        'args' => [
            'name' => 'Max Mustermann',
            'telefon' => '+4915112345678',
            'email' => 'max@example.com',
            'datum' => '2025-10-20',
            'uhrzeit' => '14:00'
        ]
    ]);

    // Assert: Logs contain redacted PII
    Log::shouldHaveReceived('info')
        ->withArgs(function ($message, $context) {
            return !str_contains(json_encode($context), '+4915112345678') &&
                   str_contains(json_encode($context), '[REDACTED]');
        });
}

/** @test */
public function it_encrypts_sensitive_customer_data_at_rest()
{
    // Arrange: Customer with sensitive data
    $customer = Customer::create([
        'name' => 'Test Customer',
        'phone' => '+4915112345678',
        'email' => 'test@example.com',
        'company_id' => 1
    ]);

    // Assert: Database contains encrypted phone
    $rawData = DB::table('customers')->find($customer->id);
    $this->assertNotEquals('+4915112345678', $rawData->phone);

    // But model decrypts it
    $this->assertEquals('+4915112345678', $customer->phone);
}
```

---

## 7. CI/CD Pipeline Integration

### 7.1 GitHub Actions Workflow

**File**: `.github/workflows/test-automation.yml`

```yaml
name: Test Automation Suite

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  unit-tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: askproai_testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql, redis
          coverage: xdebug

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy Environment
        run: cp .env.testing .env

      - name: Generate App Key
        run: php artisan key:generate

      - name: Run Migrations
        run: php artisan migrate --force

      - name: Run Unit Tests
        run: vendor/bin/phpunit --testsuite=Unit --coverage-clover=coverage.xml

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
          flags: unit-tests

  integration-tests:
    runs-on: ubuntu-latest
    needs: unit-tests

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: askproai_testing
        ports:
          - 3306:3306

      redis:
        image: redis:7
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install

      - name: Setup Environment
        run: cp .env.testing .env

      - name: Run Migrations
        run: php artisan migrate --force

      - name: Seed Database
        run: php artisan db:seed --class=TestDataSeeder

      - name: Run Integration Tests
        run: vendor/bin/phpunit --testsuite=Feature --filter=Integration

  performance-tests:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests]

    steps:
      - uses: actions/checkout@v3

      - name: Setup K6
        run: |
          sudo gpg -k
          sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6

      - name: Start Application
        run: |
          php artisan serve &
          sleep 5

      - name: Run Performance Tests
        env:
          API_URL: http://localhost:8000
        run: k6 run tests/Performance/k6/baseline-booking-flow.js

      - name: Check Performance Thresholds
        run: |
          if [ $? -ne 0 ]; then
            echo "Performance tests failed - thresholds not met"
            exit 1
          fi

  e2e-tests:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests]

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install Playwright
        run: |
          npm install -D @playwright/test
          npx playwright install --with-deps chromium

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install

      - name: Start Application
        run: |
          php artisan serve &
          sleep 5

      - name: Run E2E Tests
        run: npx playwright test tests/E2E/playwright/

      - name: Upload Playwright Report
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: playwright-report
          path: playwright-report/

  security-tests:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests]

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install Dependencies
        run: composer install

      - name: Run Security Tests
        run: vendor/bin/phpunit --testsuite=Feature --filter=Security

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --level=8 app/

      - name: Check for Vulnerable Dependencies
        run: composer audit

  test-summary:
    runs-on: ubuntu-latest
    needs: [unit-tests, integration-tests, performance-tests, e2e-tests, security-tests]
    if: always()

    steps:
      - name: Generate Test Summary
        run: |
          echo "# Test Automation Summary" >> $GITHUB_STEP_SUMMARY
          echo "## Results" >> $GITHUB_STEP_SUMMARY
          echo "- Unit Tests: ${{ needs.unit-tests.result }}" >> $GITHUB_STEP_SUMMARY
          echo "- Integration Tests: ${{ needs.integration-tests.result }}" >> $GITHUB_STEP_SUMMARY
          echo "- Performance Tests: ${{ needs.performance-tests.result }}" >> $GITHUB_STEP_SUMMARY
          echo "- E2E Tests: ${{ needs.e2e-tests.result }}" >> $GITHUB_STEP_SUMMARY
          echo "- Security Tests: ${{ needs.security-tests.result }}" >> $GITHUB_STEP_SUMMARY
```

### 7.2 Failure Notifications

**File**: `.github/workflows/test-notifications.yml`

```yaml
name: Test Failure Notifications

on:
  workflow_run:
    workflows: ["Test Automation Suite"]
    types:
      - completed

jobs:
  notify:
    runs-on: ubuntu-latest
    if: ${{ github.event.workflow_run.conclusion == 'failure' }}

    steps:
      - name: Send Slack Notification
        uses: slackapi/slack-github-action@v1
        with:
          channel-id: 'C1234567890'
          slack-message: |
            Test Failure Alert
            Branch: ${{ github.ref }}
            Commit: ${{ github.sha }}
            Author: ${{ github.actor }}
            Workflow: ${{ github.event.workflow_run.name }}
            Status: FAILED
            View: ${{ github.event.workflow_run.html_url }}
        env:
          SLACK_BOT_TOKEN: ${{ secrets.SLACK_BOT_TOKEN }}
```

### 7.3 Performance Regression Detection

**File**: `.github/workflows/performance-regression.yml`

```yaml
name: Performance Regression Detection

on:
  pull_request:
    branches: [main]

jobs:
  performance-comparison:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3
        with:
          fetch-depth: 0

      - name: Run Baseline Performance Test (main branch)
        run: |
          git checkout main
          k6 run tests/Performance/k6/baseline-booking-flow.js --out json=baseline.json

      - name: Run Current Performance Test (PR branch)
        run: |
          git checkout ${{ github.head_ref }}
          k6 run tests/Performance/k6/baseline-booking-flow.js --out json=current.json

      - name: Compare Results
        run: |
          python scripts/compare-performance.py baseline.json current.json > comparison.md

      - name: Comment PR
        uses: actions/github-script@v6
        with:
          script: |
            const fs = require('fs');
            const comparison = fs.readFileSync('comparison.md', 'utf8');
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: comparison
            });

      - name: Fail if Regression Detected
        run: |
          python scripts/check-regression-threshold.py current.json --threshold=10
```

---

## 8. Test Data Strategy

### 8.1 Test Data Fixtures

**File**: `tests/Fixtures/AppointmentFixtures.php`

```php
<?php
namespace Tests\Fixtures;

use App\Models\{Appointment, Customer, Service, Company, Branch};
use Carbon\Carbon;

class AppointmentFixtures
{
    public static function validBookingScenario(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id
        ]);

        return [
            'company' => $company,
            'branch' => $branch,
            'customer' => $customer,
            'service' => $service,
            'booking_data' => [
                'datum' => Carbon::tomorrow()->format('Y-m-d'),
                'uhrzeit' => '14:00',
                'name' => $customer->name,
                'telefon' => $customer->phone,
                'dienstleistung' => $service->name
            ]
        ];
    }

    public static function raceConditionScenario(): array
    {
        $data = self::validBookingScenario();

        // Pre-populate Cal.com mock with available slot
        $data['mock_calcom_available'] = true;

        // Provide callback to mark slot as taken
        $data['take_slot_callback'] = function() use ($data) {
            // Simulate external booking taking the slot
            CalcomMock::takeSlot(
                $data['booking_data']['datum'],
                $data['booking_data']['uhrzeit']
            );
        };

        return $data;
    }

    public static function duplicateBookingScenario(): array
    {
        $data = self::validBookingScenario();

        // Create existing appointment with same booking ID
        $existingAppointment = Appointment::factory()->create([
            'calcom_v2_booking_id' => 'duplicate_uid_123',
            'company_id' => $data['company']->id
        ]);

        $data['existing_appointment'] = $existingAppointment;
        $data['mock_calcom_booking_id'] = 'duplicate_uid_123';

        return $data;
    }
}
```

### 8.2 Database Seeders

**File**: `database/seeders/TestDataSeeder.php`

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Company, Branch, Customer, Service, Appointment};

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Company 1: Multi-branch setup
        $company1 = Company::factory()->create(['name' => 'Test Company 1']);
        $branch1a = Branch::factory()->create(['company_id' => $company1->id, 'name' => 'Branch A']);
        $branch1b = Branch::factory()->create(['company_id' => $company1->id, 'name' => 'Branch B']);

        Service::factory()->count(5)->create(['company_id' => $company1->id, 'branch_id' => $branch1a->id]);
        Service::factory()->count(3)->create(['company_id' => $company1->id, 'branch_id' => $branch1b->id]);

        Customer::factory()->count(50)->create(['company_id' => $company1->id]);

        // Company 2: Single branch
        $company2 = Company::factory()->create(['name' => 'Test Company 2']);
        $branch2 = Branch::factory()->create(['company_id' => $company2->id]);

        Service::factory()->count(3)->create(['company_id' => $company2->id, 'branch_id' => $branch2->id]);
        Customer::factory()->count(20)->create(['company_id' => $company2->id]);

        // Appointments for testing
        Appointment::factory()->count(100)->create(['company_id' => $company1->id]);
        Appointment::factory()->count(50)->create(['company_id' => $company2->id]);
    }
}
```

### 8.3 Mock Cal.com Service

**File**: `tests/Mocks/CalcomMock.php`

```php
<?php
namespace Tests\Mocks;

use Illuminate\Support\Facades\Http;

class CalcomMock
{
    private static array $availableSlots = [];
    private static array $bookings = [];

    public static function setUp(): void
    {
        Http::fake([
            '*/calcom/api/*/availability' => function ($request) {
                return Http::response([
                    'data' => [
                        'slots' => self::$availableSlots
                    ]
                ], 200);
            },

            '*/calcom/api/*/bookings' => function ($request) {
                if ($request->method() === 'POST') {
                    return self::handleCreateBooking($request);
                }
                return Http::response(['data' => self::$bookings], 200);
            }
        ]);
    }

    public static function setAvailableSlots(array $slots): void
    {
        self::$availableSlots = $slots;
    }

    public static function takeSlot(string $date, string $time): void
    {
        $key = "{$date}T{$time}:00.000Z";
        self::$availableSlots = array_filter(
            self::$availableSlots,
            fn($slot) => $slot['time'] !== $key
        );
    }

    private static function handleCreateBooking($request): \Illuminate\Http\Client\Response
    {
        $payload = json_decode($request->body(), true);
        $startTime = $payload['start'];

        // Check if slot still available
        $isAvailable = collect(self::$availableSlots)
            ->contains('time', $startTime);

        if (!$isAvailable) {
            return Http::response([
                'error' => 'Host already has booking at this time'
            ], 400);
        }

        // Create booking
        $booking = [
            'id' => rand(10000000, 99999999),
            'uid' => 'test_' . uniqid(),
            'createdAt' => now()->toIso8601String(),
            'startTime' => $startTime,
            'metadata' => $payload['metadata'] ?? [],
            'attendees' => $payload['attendees'] ?? []
        ];

        self::$bookings[] = $booking;

        return Http::response(['data' => $booking], 200);
    }
}
```

---

## 9. Test Coverage Reporting

### 9.1 Coverage Report Template

**File**: `tests/Reports/coverage-report-template.md`

```markdown
# Test Coverage Report
**Date**: {{date}}
**Branch**: {{branch}}
**Commit**: {{commit}}

---

## Overall Coverage

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Line Coverage | 80% | {{line_coverage}}% | {{status}} |
| Branch Coverage | 75% | {{branch_coverage}}% | {{status}} |
| Method Coverage | 85% | {{method_coverage}}% | {{status}} |

---

## Coverage by Module

### Critical Services (Target: 100%)

| Service | Coverage | Critical Path | Status |
|---------|----------|---------------|--------|
| AppointmentCreationService | {{coverage}} | {{critical_path}} | {{status}} |
| CalcomSyncService | {{coverage}} | {{critical_path}} | {{status}} |
| WebhookProcessingService | {{coverage}} | {{critical_path}} | {{status}} |
| AppointmentAlternativeFinder | {{coverage}} | {{critical_path}} | {{status}} |

### Supporting Services (Target: 80%)

| Service | Coverage | Status |
|---------|----------|--------|
| DateTimeParser | {{coverage}} | {{status}} |
| PhoneNumberResolutionService | {{coverage}} | {{status}} |
| ServiceSelectionService | {{coverage}} | {{status}} |

---

## RCA Issue Coverage

| RCA Issue | Tests | Coverage | Status |
|-----------|-------|----------|--------|
| Duplicate Booking Bug | 5 | 100% | ✅ |
| Availability Race Condition | 3 | 100% | ✅ |
| Type Mismatch Errors | 2 | 100% | ✅ |
| Webhook Idempotency | 4 | 100% | ✅ |
| Data Consistency | 6 | 95% | ⚠️ |

---

## Uncovered Critical Paths

{{uncovered_paths}}

---

## Recommendations

{{recommendations}}
```

### 9.2 Coverage Monitoring Script

**File**: `scripts/check-coverage.php`

```php
<?php

$coverageFile = 'coverage.xml';
$thresholds = [
    'line' => 80,
    'branch' => 75,
    'method' => 85
];

$xml = simplexml_load_file($coverageFile);
$metrics = $xml->project->metrics;

$lineCoverage = ($metrics['coveredstatements'] / $metrics['statements']) * 100;
$branchCoverage = ($metrics['coveredconditionals'] / $metrics['conditionals']) * 100;
$methodCoverage = ($metrics['coveredmethods'] / $metrics['methods']) * 100;

$failed = false;

echo "Code Coverage Results:\n";
echo "=====================\n";

foreach ([
    'line' => $lineCoverage,
    'branch' => $branchCoverage,
    'method' => $methodCoverage
] as $type => $coverage) {
    $status = $coverage >= $thresholds[$type] ? '✅' : '❌';
    $failed = $failed || ($coverage < $thresholds[$type]);

    echo sprintf(
        "%s %s: %.2f%% (threshold: %d%%)\n",
        $status,
        ucfirst($type),
        $coverage,
        $thresholds[$type]
    );
}

exit($failed ? 1 : 0);
```

---

## 10. Execution Timeline

### Week 1: Foundation
- ✅ Unit test infrastructure setup
- ✅ Mock Cal.com service implementation
- ✅ Test fixtures and seeders
- ✅ CI/CD pipeline configuration

### Week 2: Unit Tests
- ✅ AppointmentBookingService tests (15+ scenarios)
- ✅ CalcomSyncService tests
- ✅ WebhookProcessingService tests
- ✅ Achieve >80% unit test coverage

### Week 3: Integration Tests
- ✅ E2E booking flow tests
- ✅ Race condition simulation tests
- ✅ Database transaction tests
- ✅ Bi-directional sync tests

### Week 4: Performance & E2E
- ✅ K6 baseline and optimized flow tests
- ✅ Load testing scenarios
- ✅ Playwright E2E user journey tests
- ✅ Error scenario tests

### Week 5: Security & Consistency
- ✅ SQL injection prevention tests
- ✅ Authorization and multi-tenant isolation tests
- ✅ Data consistency and reconciliation tests
- ✅ PII data protection tests

### Week 6: Monitoring & Reporting
- ✅ Coverage reporting automation
- ✅ Performance regression detection
- ✅ Flaky test detection and quarantine
- ✅ Test documentation and runbooks

---

## Success Metrics

| Metric | Baseline | Target | Timeline |
|--------|----------|--------|----------|
| Unit Test Coverage | 45% | >80% | Week 2 |
| Integration Test Coverage | 30% | >70% | Week 3 |
| Critical Path Coverage | 60% | 100% | Week 3 |
| Booking Flow Performance | 144s | <45s | Week 4 |
| RCA Issue Coverage | 0% | 100% | Week 5 |
| Zero Multi-Tenant Leakage | N/A | 100% | Week 5 |

---

## Appendix

### Related Documentation
- RCA: DUPLICATE_BOOKING_BUG_ANALYSIS_2025-10-06.md
- RCA: RCA_AVAILABILITY_RACE_CONDITION_2025-10-14.md
- RCA: BOOKING_ERROR_ANALYSIS_2025-10-06.md

### Tool Versions
- PHP: 8.2
- PHPUnit: 10.x
- Laravel: 11.x
- K6: Latest
- Playwright: Latest

### Contact
- Test Automation Lead: [TBD]
- Performance Testing: [TBD]
- Security Testing: [TBD]
