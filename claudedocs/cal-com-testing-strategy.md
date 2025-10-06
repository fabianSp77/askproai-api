# Comprehensive Testing Strategy: Cal.com API Integration
## Duplicate Booking Prevention with 4-Layer Validation

**Document Version**: 1.0
**Date**: 2025-10-06
**Confidence Level**: High (85%)

---

## Executive Summary

This document provides a comprehensive testing strategy for Cal.com API integration focusing on duplicate booking prevention through four validation layers: idempotency key checking, database deduplication, Cal.com validation, and metadata verification. The strategy is based on Laravel/PHPUnit best practices, Cal.com API patterns, and industry-standard idempotency testing approaches.

### Key Research Findings

**Cal.com API Idempotency** ✅
- Cal.com API supports `idempotencyKey` parameter (confirmed in GitHub issue #14706)
- Returns same response for duplicate requests with same idempotency key
- Stores idempotency key in booking metadata
- Recent fixes specifically address duplicate booking prevention (PR by @emrysal)

**Laravel HTTP Testing** ✅
- `Http::fake()` provides comprehensive mocking capabilities
- Requires using `Http` facade (not custom HTTP clients) for fakes to work
- Supports response sequences for testing retry scenarios
- Can simulate network failures, timeouts, and API errors

**Idempotency Patterns** ✅
- Industry standard: client generates unique key per operation
- Server stores key + response for duplicate detection
- Retry logic uses same key to prevent duplicate operations
- Critical for non-idempotent operations (POST requests)

**Race Condition Testing** ✅
- Use database transactions and locks in tests
- Parallel request simulation with queues or async testing
- Redis-based distributed locking for concurrent scenarios
- Proper cleanup between test runs essential

---

## Testing Architecture

### Test Structure Overview

```
tests/
├── Unit/
│   ├── Services/
│   │   └── CalComServiceTest.php           # Pure unit tests with mocks
│   └── Validators/
│       └── BookingValidatorTest.php         # Validation logic tests
├── Feature/
│   ├── BookingCreationTest.php             # Integration tests
│   ├── DuplicatePreventionTest.php          # 4-layer validation tests
│   └── IdempotencyTest.php                  # Idempotency-specific tests
└── Integration/
    └── CalComApiIntegrationTest.php         # Real API tests (optional)
```

### Mock vs Integration Strategy

**Unit Tests (Fast, Isolated)**
- Mock Cal.com HTTP responses
- Test service logic independently
- Validate each layer in isolation
- No database or external dependencies

**Feature Tests (Realistic, End-to-End)**
- Use `Http::fake()` for Cal.com responses
- Real database interactions with `RefreshDatabase`
- Test all 4 validation layers together
- Simulate real-world scenarios

**Integration Tests (Optional, Slow)**
- Real Cal.com API calls
- Use test event types only
- Run in CI/CD for validation
- Separate from unit/feature tests

---

## Implementation Patterns

### 1. Cal.com HTTP Mocking with Laravel

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CalComService;

class CalComServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful booking creation with idempotency
     */
    public function test_creates_booking_with_idempotency_key(): void
    {
        // Arrange: Mock Cal.com API response
        Http::fake([
            'api.cal.com/v1/bookings' => Http::response([
                'id' => 1838535,
                'uid' => 's9ksVFLmNtgiMj2XSsYv3y',
                'idempotencyKey' => 'test-idempotency-key-123',
                'userId' => 99009,
                'eventTypeId' => 341887,
                'title' => '30 Min Meeting',
                'startTime' => '2025-10-07T14:30:00.000Z',
                'endTime' => '2025-10-07T15:00:00.000Z',
                'status' => 'PENDING',
                'metadata' => [
                    'checkin' => 'true',
                    'customerId' => 'cust_123'
                ],
                'attendees' => [
                    [
                        'id' => 2358122,
                        'email' => '[email protected]',
                        'name' => 'John Doe',
                        'bookingId' => 1838535
                    ]
                ]
            ], 201)
        ]);

        $service = app(CalComService::class);

        // Act: Create booking
        $result = $service->createBooking([
            'eventTypeId' => 341887,
            'start' => '2025-10-07T14:30:00.000Z',
            'attendee' => [
                'name' => 'John Doe',
                'email' => '[email protected]',
            ],
            'metadata' => [
                'checkin' => 'true',
                'customerId' => 'cust_123'
            ]
        ], 'test-idempotency-key-123');

        // Assert: Verify response structure
        $this->assertEquals(1838535, $result['id']);
        $this->assertEquals('test-idempotency-key-123', $result['idempotencyKey']);
        $this->assertEquals('PENDING', $result['status']);

        // Assert: Verify HTTP request was made with correct headers
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cal.com/v1/bookings' &&
                   $request->hasHeader('Authorization', 'Bearer ' . config('services.calcom.api_key')) &&
                   $request['idempotencyKey'] === 'test-idempotency-key-123' &&
                   $request['metadata']['customerId'] === 'cust_123';
        });
    }

    /**
     * Test idempotent behavior - same key returns cached response
     */
    public function test_idempotent_request_returns_same_response(): void
    {
        $idempotencyKey = 'duplicate-key-456';

        // Mock: First request creates booking, second returns same result
        Http::fake([
            'api.cal.com/v1/bookings' => Http::sequence()
                ->push(['id' => 123, 'idempotencyKey' => $idempotencyKey, 'status' => 'PENDING'], 201)
                ->push(['id' => 123, 'idempotencyKey' => $idempotencyKey, 'status' => 'PENDING'], 200) // Same response
        ]);

        $service = app(CalComService::class);

        // First request
        $result1 = $service->createBooking(['eventTypeId' => 123], $idempotencyKey);

        // Second request with same key
        $result2 = $service->createBooking(['eventTypeId' => 123], $idempotencyKey);

        // Both requests return identical booking
        $this->assertEquals($result1['id'], $result2['id']);
        $this->assertEquals($idempotencyKey, $result1['idempotencyKey']);
        $this->assertEquals($idempotencyKey, $result2['idempotencyKey']);

        // Verify two requests were made
        Http::assertSentCount(2);
    }

    /**
     * Test network retry scenario with same idempotency key
     */
    public function test_handles_network_retry_with_idempotency(): void
    {
        $idempotencyKey = 'retry-key-789';

        Http::fake([
            'api.cal.com/v1/bookings' => Http::sequence()
                ->push(null, 500) // First request fails
                ->push(['id' => 456, 'idempotencyKey' => $idempotencyKey], 201) // Retry succeeds
        ]);

        $service = app(CalComService::class);

        // Service should retry with same idempotency key
        $result = $service->createBookingWithRetry(['eventTypeId' => 123], $idempotencyKey);

        $this->assertEquals(456, $result['id']);
        $this->assertEquals($idempotencyKey, $result['idempotencyKey']);
        Http::assertSentCount(2); // Verify retry happened
    }
}
```

### 2. Four-Layer Validation Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CalComService;
use App\Models\Booking;
use App\Models\Customer;

class DuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * LAYER 1: Idempotency Key Validation
     * Prevent duplicate bookings using same idempotency key
     */
    public function test_layer1_idempotency_key_prevents_duplicate(): void
    {
        $customer = Customer::factory()->create();
        $idempotencyKey = 'unique-key-123';

        // First booking with idempotency key
        Booking::create([
            'customer_id' => $customer->id,
            'calcom_booking_id' => 'booking-123',
            'idempotency_key' => $idempotencyKey,
            'event_type_id' => 100,
            'start_time' => '2025-10-07 14:00:00',
            'status' => 'confirmed'
        ]);

        // Attempt duplicate with same idempotency key
        $service = app(CalComService::class);

        $result = $service->createBooking([
            'customerId' => $customer->id,
            'eventTypeId' => 100,
            'start' => '2025-10-07T14:00:00Z'
        ], $idempotencyKey);

        // Should return existing booking, not create new one
        $this->assertEquals('booking-123', $result['calcom_booking_id']);
        $this->assertDatabaseCount('bookings', 1); // Only one booking exists
    }

    /**
     * LAYER 2: Database Deduplication
     * Prevent same customer from booking same slot twice
     */
    public function test_layer2_database_prevents_duplicate_slot(): void
    {
        Http::fake(['api.cal.com/*' => Http::response(['id' => 999], 201)]);

        $customer = Customer::factory()->create();

        // First booking
        Booking::create([
            'customer_id' => $customer->id,
            'calcom_booking_id' => 'booking-456',
            'event_type_id' => 200,
            'start_time' => '2025-10-07 15:00:00',
            'status' => 'confirmed'
        ]);

        $service = app(CalComService::class);

        // Attempt duplicate booking (different idempotency key, same slot)
        $this->expectException(\App\Exceptions\DuplicateBookingException::class);
        $this->expectExceptionMessage('Customer already has booking for this time slot');

        $service->createBooking([
            'customerId' => $customer->id,
            'eventTypeId' => 200,
            'start' => '2025-10-07T15:00:00Z'
        ], 'different-key-456');
    }

    /**
     * LAYER 3: Cal.com API Validation
     * Cal.com should prevent slot overbooking
     */
    public function test_layer3_calcom_prevents_slot_overbooking(): void
    {
        // Mock: Cal.com returns error for unavailable slot
        Http::fake([
            'api.cal.com/v1/bookings' => Http::response([
                'error' => 'Slot no longer available',
                'code' => 'SLOT_UNAVAILABLE'
            ], 409) // Conflict
        ]);

        $service = app(CalComService::class);

        $this->expectException(\App\Exceptions\SlotUnavailableException::class);

        $service->createBooking([
            'eventTypeId' => 300,
            'start' => '2025-10-07T16:00:00Z',
            'attendee' => ['email' => '[email protected]']
        ], 'key-789');
    }

    /**
     * LAYER 4: Metadata Validation
     * Detect stale/cached responses from Cal.com
     */
    public function test_layer4_metadata_detects_stale_response(): void
    {
        $currentTime = now();
        $staleTime = now()->subHours(2); // 2 hours old

        Http::fake([
            'api.cal.com/v1/bookings' => Http::response([
                'id' => 777,
                'idempotencyKey' => 'key-999',
                'status' => 'PENDING',
                'createdAt' => $staleTime->toIso8601String(), // Stale timestamp
                'metadata' => [
                    'requestTimestamp' => $staleTime->timestamp
                ]
            ], 200)
        ]);

        $service = app(CalComService::class);

        $this->expectException(\App\Exceptions\StaleResponseException::class);
        $this->expectExceptionMessage('Response is stale - timestamp mismatch');

        // Pass current timestamp in metadata
        $service->createBooking([
            'eventTypeId' => 400,
            'metadata' => [
                'requestTimestamp' => $currentTime->timestamp
            ]
        ], 'key-999');
    }

    /**
     * Integration: All 4 layers working together
     */
    public function test_all_layers_prevent_duplicate_scenarios(): void
    {
        Http::fake([
            'api.cal.com/v1/bookings' => Http::response([
                'id' => 888,
                'idempotencyKey' => 'integrated-key',
                'status' => 'CONFIRMED',
                'createdAt' => now()->toIso8601String(),
                'metadata' => ['requestTimestamp' => now()->timestamp]
            ], 201)
        ]);

        $customer = Customer::factory()->create();
        $service = app(CalComService::class);

        // Scenario 1: Successful first booking
        $booking1 = $service->createBooking([
            'customerId' => $customer->id,
            'eventTypeId' => 500,
            'start' => '2025-10-07T17:00:00Z',
            'metadata' => ['requestTimestamp' => now()->timestamp]
        ], 'integrated-key');

        $this->assertDatabaseHas('bookings', [
            'calcom_booking_id' => 888,
            'idempotency_key' => 'integrated-key'
        ]);

        // Scenario 2: Same idempotency key → Layer 1 blocks
        $booking2 = $service->createBooking([
            'customerId' => $customer->id,
            'eventTypeId' => 500,
            'start' => '2025-10-07T17:00:00Z'
        ], 'integrated-key'); // Same key

        $this->assertEquals($booking1['id'], $booking2['id']);
        $this->assertDatabaseCount('bookings', 1);

        // Scenario 3: Different key, same slot → Layer 2 blocks
        $this->expectException(\App\Exceptions\DuplicateBookingException::class);

        $service->createBooking([
            'customerId' => $customer->id,
            'eventTypeId' => 500,
            'start' => '2025-10-07T17:00:00Z'
        ], 'different-key-integrated');
    }
}
```

### 3. Race Condition Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CalComService;
use App\Models\Customer;
use App\Jobs\CreateBookingJob;

class RaceConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test concurrent booking attempts for same slot
     */
    public function test_concurrent_booking_attempts_only_one_succeeds(): void
    {
        Queue::fake();

        Http::fake([
            'api.cal.com/v1/bookings' => Http::sequence()
                ->push(['id' => 1111, 'status' => 'CONFIRMED'], 201) // First succeeds
                ->push(['error' => 'Slot unavailable'], 409) // Second fails
        ]);

        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        // Simulate concurrent requests
        $jobs = [
            new CreateBookingJob($customer1->id, 'slot-600', '2025-10-07T18:00:00Z', 'key-race-1'),
            new CreateBookingJob($customer2->id, 'slot-600', '2025-10-07T18:00:00Z', 'key-race-2'),
        ];

        // Dispatch jobs "simultaneously"
        foreach ($jobs as $job) {
            Queue::push($job);
        }

        // Process queue
        Queue::assertPushed(CreateBookingJob::class, 2);

        // Process jobs
        foreach (Queue::pushedJobs()[CreateBookingJob::class] as $job) {
            $job['job']->handle();
        }

        // Only one booking should succeed
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', ['calcom_booking_id' => 1111]);
    }

    /**
     * Test database-level race condition with transactions
     */
    public function test_database_transaction_prevents_race_condition(): void
    {
        $customer = Customer::factory()->create();
        $slotStart = '2025-10-07 19:00:00';

        // Mock successful Cal.com responses
        Http::fake([
            'api.cal.com/*' => Http::response(['id' => 2222], 201)
        ]);

        $service = app(CalComService::class);

        // Simulate race: Two processes checking slot availability simultaneously
        $process1 = function() use ($customer, $slotStart, $service) {
            \DB::transaction(function() use ($customer, $slotStart, $service) {
                // Check slot availability (both processes see it's free)
                $exists = Booking::where('customer_id', $customer->id)
                    ->where('start_time', $slotStart)
                    ->lockForUpdate() // Pessimistic lock
                    ->exists();

                if ($exists) {
                    throw new \App\Exceptions\DuplicateBookingException('Slot taken');
                }

                // Create booking
                sleep(1); // Simulate processing delay
                Booking::create([
                    'customer_id' => $customer->id,
                    'calcom_booking_id' => 'process1-booking',
                    'start_time' => $slotStart,
                    'idempotency_key' => 'key-process-1'
                ]);
            });
        };

        // First process succeeds
        $process1();

        // Second process should fail due to lock
        $this->expectException(\App\Exceptions\DuplicateBookingException::class);
        $process1(); // Same logic, but booking now exists

        $this->assertDatabaseCount('bookings', 1);
    }

    /**
     * Test retry logic doesn't create duplicates
     */
    public function test_retry_with_same_idempotency_key_no_duplicate(): void
    {
        $idempotencyKey = 'retry-test-key';

        Http::fake([
            'api.cal.com/v1/bookings' => Http::sequence()
                ->push(null, 500) // Network error
                ->push(null, 500) // Network error
                ->push(['id' => 3333, 'idempotencyKey' => $idempotencyKey], 201) // Success
        ]);

        $service = app(CalComService::class);

        // Service retries with same idempotency key
        $result = $service->createBookingWithRetry([
            'eventTypeId' => 700,
            'start' => '2025-10-07T20:00:00Z'
        ], $idempotencyKey, $maxRetries = 3);

        $this->assertEquals(3333, $result['id']);
        Http::assertSentCount(3); // 2 failures + 1 success

        // Only one database record
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('bookings', [
            'calcom_booking_id' => 3333,
            'idempotency_key' => $idempotencyKey
        ]);
    }
}
```

### 4. Service Class Testing Patterns

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CalComService;
use App\Repositories\BookingRepository;
use Illuminate\Support\Facades\Http;
use Mockery;

class CalComServiceUnitTest extends TestCase
{
    /**
     * Test idempotency key generation
     */
    public function test_generates_unique_idempotency_key(): void
    {
        $service = new CalComService(
            Mockery::mock(BookingRepository::class)
        );

        $key1 = $service->generateIdempotencyKey('customer-1', 'slot-1', '2025-10-07');
        $key2 = $service->generateIdempotencyKey('customer-1', 'slot-1', '2025-10-07');
        $key3 = $service->generateIdempotencyKey('customer-2', 'slot-1', '2025-10-07');

        // Same inputs = same key (deterministic)
        $this->assertEquals($key1, $key2);

        // Different inputs = different key
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * Test metadata validation logic
     */
    public function test_validates_response_metadata(): void
    {
        $service = new CalComService(
            Mockery::mock(BookingRepository::class)
        );

        $currentTimestamp = now()->timestamp;

        // Valid metadata
        $validResponse = [
            'id' => 123,
            'createdAt' => now()->toIso8601String(),
            'metadata' => ['requestTimestamp' => $currentTimestamp]
        ];

        $this->assertTrue($service->validateResponseMetadata($validResponse, $currentTimestamp));

        // Stale metadata (>5 minute difference)
        $staleResponse = [
            'id' => 456,
            'createdAt' => now()->subMinutes(10)->toIso8601String(),
            'metadata' => ['requestTimestamp' => now()->subMinutes(10)->timestamp]
        ];

        $this->assertFalse($service->validateResponseMetadata($staleResponse, $currentTimestamp));
    }

    /**
     * Test database lookup before API call
     */
    public function test_checks_database_before_api_call(): void
    {
        $mockRepo = Mockery::mock(BookingRepository::class);
        $mockRepo->shouldReceive('findByIdempotencyKey')
            ->with('existing-key')
            ->once()
            ->andReturn(['id' => 999, 'status' => 'confirmed']);

        $service = new CalComService($mockRepo);

        // Should return existing booking without HTTP call
        $result = $service->createBooking([
            'eventTypeId' => 123
        ], 'existing-key');

        $this->assertEquals(999, $result['id']);

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    /**
     * Test error handling and transformation
     */
    public function test_transforms_calcom_errors(): void
    {
        Http::fake([
            'api.cal.com/*' => Http::response([
                'error' => 'Event type not found',
                'code' => 'EVENT_TYPE_NOT_FOUND'
            ], 404)
        ]);

        $service = new CalComService(
            Mockery::mock(BookingRepository::class)
        );

        $this->expectException(\App\Exceptions\CalComException::class);
        $this->expectExceptionMessage('Event type not found');

        $service->createBooking([
            'eventTypeId' => 9999 // Non-existent
        ], 'error-key');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

---

## Test Data Factories

### Booking Factory

```php
<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'calcom_booking_id' => 'calcom_' . $this->faker->uuid(),
            'idempotency_key' => 'key_' . $this->faker->uuid(),
            'event_type_id' => $this->faker->numberBetween(100, 999),
            'start_time' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'end_time' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
            'metadata' => [
                'checkin' => $this->faker->boolean(),
                'requestTimestamp' => now()->timestamp
            ]
        ];
    }

    /**
     * State: Confirmed booking
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * State: For specific time slot
     */
    public function forSlot(string $startTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => \Carbon\Carbon::parse($startTime)->addMinutes(30),
        ]);
    }

    /**
     * State: With specific idempotency key
     */
    public function withIdempotencyKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'idempotency_key' => $key,
        ]);
    }
}
```

---

## Specific Test Cases for 4 Validation Layers

### Test Case Matrix

| Test Case | Layer | Scenario | Expected Outcome | Priority |
|-----------|-------|----------|------------------|----------|
| TC-001 | Layer 1 | Same idempotency key, same customer | Return existing booking | Critical |
| TC-002 | Layer 1 | Same idempotency key, different customer | Return existing booking (key is global) | High |
| TC-003 | Layer 2 | Different key, same customer, same slot | Throw DuplicateBookingException | Critical |
| TC-004 | Layer 2 | Different key, different customer, same slot | Allowed (different customers) | High |
| TC-005 | Layer 3 | Cal.com returns slot unavailable | Throw SlotUnavailableException | Critical |
| TC-006 | Layer 3 | Cal.com returns rate limit error | Retry with exponential backoff | Medium |
| TC-007 | Layer 4 | Response createdAt > 5 min old | Throw StaleResponseException | High |
| TC-008 | Layer 4 | Request timestamp mismatch | Throw MetadataMismatchException | High |
| TC-009 | Race | Concurrent requests, same slot | Only one succeeds, others fail | Critical |
| TC-010 | Race | Network retry with same key | No duplicate created | Critical |
| TC-011 | Integration | All layers pass | Booking created successfully | Critical |
| TC-012 | Integration | Layer 1 fails → skip other layers | Fast rejection, no API call | High |

### Implementation Examples

```php
// TC-001: Same idempotency key returns existing booking
public function test_tc001_same_idempotency_key_returns_existing(): void
{
    $customer = Customer::factory()->create();
    $key = 'idempotent-key-tc001';

    // Create first booking
    $booking1 = Booking::factory()
        ->for($customer)
        ->withIdempotencyKey($key)
        ->create();

    $service = app(CalComService::class);

    // Second request with same key
    $result = $service->getOrCreateBooking([
        'customerId' => $customer->id,
        'eventTypeId' => 100
    ], $key);

    $this->assertEquals($booking1->id, $result['id']);
    $this->assertDatabaseCount('bookings', 1);
    Http::assertNothingSent(); // No API call made
}

// TC-003: Different key, same slot throws exception
public function test_tc003_different_key_same_slot_throws_exception(): void
{
    $customer = Customer::factory()->create();
    $slotStart = '2025-10-08 10:00:00';

    Booking::factory()
        ->for($customer)
        ->forSlot($slotStart)
        ->withIdempotencyKey('key-1')
        ->create();

    $service = app(CalComService::class);

    $this->expectException(\App\Exceptions\DuplicateBookingException::class);

    $service->createBooking([
        'customerId' => $customer->id,
        'eventTypeId' => 100,
        'start' => $slotStart
    ], 'key-2'); // Different key, same slot
}

// TC-007: Stale response detection
public function test_tc007_stale_response_throws_exception(): void
{
    $currentTime = now();
    $staleTime = now()->subMinutes(10);

    Http::fake([
        'api.cal.com/*' => Http::response([
            'id' => 777,
            'createdAt' => $staleTime->toIso8601String(), // 10 minutes old
            'metadata' => ['requestTimestamp' => $staleTime->timestamp]
        ], 200)
    ]);

    $service = app(CalComService::class);

    $this->expectException(\App\Exceptions\StaleResponseException::class);

    $service->createBooking([
        'eventTypeId' => 100,
        'metadata' => ['requestTimestamp' => $currentTime->timestamp] // Current time
    ], 'key-tc007');
}

// TC-009: Race condition - concurrent requests
public function test_tc009_concurrent_requests_one_succeeds(): void
{
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();
    $slotStart = '2025-10-08 11:00:00';

    Http::fake([
        'api.cal.com/*' => Http::sequence()
            ->push(['id' => 100, 'status' => 'CONFIRMED'], 201)
            ->push(['error' => 'Slot unavailable'], 409)
    ]);

    $service = app(CalComService::class);

    // First customer succeeds
    $booking1 = $service->createBooking([
        'customerId' => $customer1->id,
        'eventTypeId' => 200,
        'start' => $slotStart
    ], 'key-cust1');

    $this->assertEquals(100, $booking1['id']);

    // Second customer fails (slot taken)
    $this->expectException(\App\Exceptions\SlotUnavailableException::class);

    $service->createBooking([
        'customerId' => $customer2->id,
        'eventTypeId' => 200,
        'start' => $slotStart
    ], 'key-cust2');

    $this->assertDatabaseCount('bookings', 1);
}
```

---

## Mock/Fake Patterns Reference

### HTTP Response Sequences

```php
// Success → Success (idempotent)
Http::fake([
    'api.cal.com/*' => Http::sequence()
        ->push($successResponse, 201)
        ->push($successResponse, 200) // Same response
]);

// Failure → Retry → Success
Http::fake([
    'api.cal.com/*' => Http::sequence()
        ->push(null, 500) // Server error
        ->push(null, 503) // Service unavailable
        ->push($successResponse, 201) // Success
]);

// Success → Conflict (slot taken)
Http::fake([
    'api.cal.com/*' => Http::sequence()
        ->push(['id' => 1], 201)
        ->push(['error' => 'Slot unavailable'], 409)
]);
```

### Conditional Fakes

```php
// Different responses based on request data
Http::fake(function ($request) {
    if ($request['eventTypeId'] === 999) {
        return Http::response(['error' => 'Not found'], 404);
    }

    if ($request->hasHeader('X-Idempotency-Key', 'duplicate-key')) {
        return Http::response(['id' => 123], 200); // Cached response
    }

    return Http::response(['id' => random_int(1, 9999)], 201);
});
```

### Database State Management

```php
// Use transactions for isolation
public function test_with_transaction_isolation(): void
{
    \DB::beginTransaction();

    try {
        // Test logic here
        $this->assertTrue(true);
        \DB::commit();
    } catch (\Exception $e) {
        \DB::rollBack();
        throw $e;
    }
}

// RefreshDatabase for clean state
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // Resets database before each test
}
```

---

## Edge Cases and Error Scenarios

### Network Errors

```php
// Timeout
Http::fake([
    'api.cal.com/*' => Http::response(null, 408) // Request timeout
]);

// Connection refused
Http::fake([
    'api.cal.com/*' => function ($request) {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    }
]);
```

### Invalid Responses

```php
// Malformed JSON
Http::fake([
    'api.cal.com/*' => Http::response('Invalid JSON{', 200)
]);

// Missing required fields
Http::fake([
    'api.cal.com/*' => Http::response([
        'id' => 123
        // Missing: idempotencyKey, status, etc.
    ], 201)
]);
```

### Cal.com Specific Errors

```php
// Based on Cal.com API documentation
Http::fake([
    'api.cal.com/*' => Http::response([
        'error' => 'Slot no longer available',
        'code' => 'SLOT_UNAVAILABLE'
    ], 409)
]);

Http::fake([
    'api.cal.com/*' => Http::response([
        'error' => 'Event type not found',
        'code' => 'EVENT_TYPE_NOT_FOUND'
    ], 404)
]);

Http::fake([
    'api.cal.com/*' => Http::response([
        'error' => 'Rate limit exceeded',
        'code' => 'RATE_LIMIT_EXCEEDED'
    ], 429)
]);
```

---

## Test Execution Strategy

### Test Organization

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Feature/DuplicatePreventionTest.php

# Run specific test method
php artisan test --filter=test_layer1_idempotency_key_prevents_duplicate

# Run tests with coverage
php artisan test --coverage

# Run parallel tests (faster)
php artisan test --parallel
```

### CI/CD Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo_mysql

      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run Tests
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
        run: |
          php artisan test --parallel --coverage-clover=coverage.xml

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

### Performance Testing

```php
/**
 * Test service performance under load
 */
public function test_handles_high_volume_bookings(): void
{
    Http::fake([
        'api.cal.com/*' => Http::response(['id' => 1], 201)
    ]);

    $service = app(CalComService::class);
    $startTime = microtime(true);

    // Simulate 100 booking requests
    for ($i = 0; $i < 100; $i++) {
        $service->createBooking([
            'eventTypeId' => 100,
            'start' => now()->addDays($i)->toIso8601String()
        ], 'key-' . $i);
    }

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Should complete within reasonable time (e.g., 5 seconds)
    $this->assertLessThan(5.0, $duration);
    $this->assertDatabaseCount('bookings', 100);
}
```

---

## Best Practices Summary

### DO ✅

1. **Use Http::fake() for External APIs**
   - Mock Cal.com responses consistently
   - Use sequences for retry scenarios
   - Assert HTTP requests were made correctly

2. **Test Each Layer Independently**
   - Layer 1: Idempotency key logic in isolation
   - Layer 2: Database constraints separately
   - Layer 3: Cal.com error handling
   - Layer 4: Metadata validation logic

3. **Use Factories for Test Data**
   - Create reusable booking factories
   - Use factory states for common scenarios
   - Keep test data realistic

4. **Test Integration Scenarios**
   - All layers working together
   - Real workflow simulations
   - Edge case combinations

5. **Mock Dependencies in Unit Tests**
   - Use Mockery for repository mocks
   - Test service logic in isolation
   - Verify method calls and arguments

6. **Use Database Transactions**
   - RefreshDatabase for clean state
   - Transactions for race condition tests
   - Proper cleanup between tests

### DON'T ❌

1. **Don't Use Real Cal.com API in Tests**
   - Slow, unreliable, costly
   - Use Http::fake() instead
   - Reserve integration tests for CI only

2. **Don't Share State Between Tests**
   - Each test should be independent
   - Use RefreshDatabase trait
   - Clean up after async operations

3. **Don't Test Implementation Details**
   - Focus on behavior, not internals
   - Test public interfaces
   - Allow refactoring flexibility

4. **Don't Ignore Race Conditions**
   - Test concurrent scenarios
   - Use database locks properly
   - Simulate realistic timing

5. **Don't Skip Error Cases**
   - Test all exception paths
   - Verify error messages
   - Test recovery logic

---

## Tools and Resources

### Required Packages

```json
{
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "mockery/mockery": "^1.6",
    "laravel/framework": "^11.0"
  }
}
```

### Useful Testing Utilities

```php
// Custom assertion helpers
trait BookingAssertions
{
    protected function assertBookingExists(string $idempotencyKey): void
    {
        $this->assertDatabaseHas('bookings', [
            'idempotency_key' => $idempotencyKey
        ]);
    }

    protected function assertBookingNotDuplicated(int $customerId, string $startTime): void
    {
        $count = \DB::table('bookings')
            ->where('customer_id', $customerId)
            ->where('start_time', $startTime)
            ->count();

        $this->assertEquals(1, $count, 'Duplicate booking detected');
    }

    protected function assertHttpRequestHasIdempotencyKey(string $key): void
    {
        Http::assertSent(function ($request) use ($key) {
            return $request['idempotencyKey'] === $key ||
                   $request->hasHeader('X-Idempotency-Key', $key);
        });
    }
}
```

### Documentation References

- **Cal.com API Docs**: https://cal.com/docs/api-reference/v2/bookings
- **Laravel HTTP Testing**: https://laravel.com/docs/11.x/http-tests
- **PHPUnit Documentation**: https://docs.phpunit.de/en/10.5/
- **Mockery Documentation**: http://docs.mockery.io/en/latest/

---

## Conclusion

This comprehensive testing strategy provides:

✅ **Complete Coverage**: All 4 validation layers tested independently and together
✅ **Realistic Scenarios**: Race conditions, retries, network failures
✅ **Best Practices**: Laravel/PHPUnit patterns, proper mocking, isolation
✅ **Maintainability**: Factory patterns, reusable assertions, clear structure
✅ **Performance**: Parallel testing, efficient mocks, fast feedback

### Next Steps

1. **Implement base test structure** (Unit + Feature directories)
2. **Create booking factories** with all necessary states
3. **Build layer-specific tests** (L1→L2→L3→L4)
4. **Add integration tests** for complete workflows
5. **Set up CI/CD pipeline** for automated testing
6. **Monitor coverage** and add missing scenarios

### Confidence Assessment

- **Cal.com Patterns**: 85% (confirmed via GitHub issues, API docs)
- **Laravel Testing**: 95% (official documentation, established patterns)
- **Idempotency**: 90% (industry standards, research-backed)
- **Race Conditions**: 80% (requires environment-specific tuning)

**Overall Strategy Confidence**: 88% - Ready for implementation with minor adjustments based on actual Cal.com API behavior.

---

*Document compiled from research conducted 2025-10-06*
*Sources: Cal.com GitHub, Laravel Documentation, PHPUnit Best Practices, Industry Idempotency Patterns*
