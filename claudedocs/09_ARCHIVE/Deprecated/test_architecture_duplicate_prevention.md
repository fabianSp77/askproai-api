# Test Architecture: Duplicate Booking Prevention System

**Document Version**: 1.0
**Date**: 2025-10-06
**Target**: AppointmentCreationService - 4-Layer Duplicate Prevention

---

## Executive Summary

Comprehensive test suite architecture for validating the 4-layer duplicate booking prevention system implemented in AppointmentCreationService. This system prevents duplicate appointments from Cal.com idempotency behavior through multiple validation layers.

**Validation Layers**:
1. **Layer 1**: Booking freshness validation (30-second threshold)
2. **Layer 2**: Metadata call_id validation
3. **Layer 3**: Database duplicate check before insert (application-level)
4. **Layer 4**: Database UNIQUE constraint (database-level)

---

## Test Class Structure

```
tests/
├── Unit/
│   └── Services/
│       └── Retell/
│           ├── AppointmentCreationServiceTest.php (existing - extend)
│           └── DuplicatePreventionTest.php (NEW - focused unit tests)
│
├── Feature/
│   └── Integration/
│       ├── DuplicateBookingPreventionIntegrationTest.php (NEW)
│       └── CalcomIdempotencyHandlingTest.php (NEW)
│
└── Integration/
    └── DuplicateBookingDatabaseConstraintTest.php (NEW)
```

### File Structure Rationale

**Unit Tests** (`tests/Unit/Services/Retell/DuplicatePreventionTest.php`)
- Fast execution with mocked dependencies
- Tests each validation layer in isolation
- Edge cases and boundary conditions
- Mock Cal.com responses

**Feature Tests** (`tests/Feature/Integration/`)
- Full service integration with real database
- Cal.com service mocking
- End-to-end validation flow
- Multi-layer interaction testing

**Integration Tests** (`tests/Integration/`)
- Database constraint validation
- UNIQUE constraint enforcement
- Transaction rollback behavior
- Database-level duplicate prevention

---

## Test Case Matrix

### Layer 1: Booking Freshness Validation (30-second threshold)

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|------------------|-----------|
| **L1-001** | Booking created within 30 seconds (age: 5s) | Accept booking | Unit |
| **L1-002** | Booking exactly at 30 seconds | Accept booking | Unit |
| **L1-003** | Booking older than 30 seconds (age: 31s) | Reject booking (stale) | Unit |
| **L1-004** | Booking older than 30 seconds (age: 60s) | Reject booking (stale) | Unit |
| **L1-005** | Booking with NULL createdAt | Accept (no timestamp to validate) | Unit |
| **L1-006** | Booking with invalid date format | Handle gracefully | Unit |
| **L1-007** | Booking exactly at boundary (29.999s) | Accept booking | Unit |
| **L1-008** | Booking from different timezone | Normalize and validate correctly | Unit |
| **L1-009** | Multiple calls with stale bookings | Reject all stale bookings | Integration |
| **L1-010** | Race condition: booking ages during request | Reject if crosses threshold | Integration |

### Layer 2: Metadata call_id Validation

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|------------------|-----------|
| **L2-001** | Call ID matches current request | Accept booking | Unit |
| **L2-002** | Call ID does not match current request | Reject booking | Unit |
| **L2-003** | Booking metadata missing call_id field | Accept (no call_id to validate) | Unit |
| **L2-004** | Call ID is NULL in metadata | Accept (no call_id to validate) | Unit |
| **L2-005** | Call ID is empty string | Accept (no call_id to validate) | Unit |
| **L2-006** | Call parameter is NULL (no call context) | Accept (no call to validate against) | Unit |
| **L2-007** | Call ID matches but booking is stale | Reject (Layer 1 validation fails) | Unit |
| **L2-008** | Call ID format mismatch (case sensitivity) | Reject if not exact match | Unit |
| **L2-009** | Metadata contains extra call_id fields | Use correct field path | Unit |
| **L2-010** | Nested metadata structure | Extract call_id correctly | Unit |

### Layer 3: Database Duplicate Check (Application-level)

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|------------------|-----------|
| **L3-001** | No existing appointment with booking ID | Create new appointment | Integration |
| **L3-002** | Existing appointment with same booking ID | Return existing, no duplicate | Integration |
| **L3-003** | Existing appointment from different call | Return existing, log warning | Integration |
| **L3-004** | Multiple concurrent requests with same ID | First succeeds, others return existing | Integration |
| **L3-005** | Booking ID is NULL (no Cal.com ID) | Create without duplicate check | Integration |
| **L3-006** | Existing appointment was soft-deleted | Create new appointment | Integration |
| **L3-007** | Database query timeout during check | Handle gracefully, fail safely | Integration |
| **L3-008** | Transaction rollback during check | Maintain data consistency | Integration |
| **L3-009** | Check before insert in same transaction | Ensure atomicity | Integration |
| **L3-010** | Existing appointment with different status | Return existing regardless of status | Integration |

### Layer 4: Database UNIQUE Constraint

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|------------------|-----------|
| **L4-001** | Insert with unique calcom_v2_booking_id | Insert succeeds | Integration |
| **L4-002** | Insert duplicate calcom_v2_booking_id | Database throws constraint violation | Integration |
| **L4-003** | Layer 3 bypass attempt (raw SQL) | Constraint prevents duplicate | Integration |
| **L4-004** | NULL calcom_v2_booking_id (multiple) | All inserts succeed (NULL allowed) | Integration |
| **L4-005** | Transaction rollback on constraint violation | No partial data committed | Integration |
| **L4-006** | Concurrent inserts with same booking ID | One succeeds, others fail | Integration |
| **L4-007** | Constraint exists in database schema | Verify constraint in migration | Integration |
| **L4-008** | Migration up/down reversibility | Constraint removed on rollback | Integration |
| **L4-009** | Index performance on lookup | Query uses index efficiently | Integration |
| **L4-010** | Constraint violation error handling | Application catches and logs error | Integration |

### Multi-Layer Integration Tests

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|------------------|-----------|
| **ML-001** | Fresh booking, valid call_id, no duplicate | All layers pass, appointment created | Integration |
| **ML-002** | Stale booking caught by Layer 1 | Reject before Layer 2 validation | Integration |
| **ML-003** | Invalid call_id caught by Layer 2 | Reject before Layer 3 check | Integration |
| **ML-004** | Duplicate caught by Layer 3 | Return existing before Layer 4 | Integration |
| **ML-005** | Layer 3 fails, Layer 4 catches duplicate | Constraint prevents duplicate | Integration |
| **ML-006** | Multiple validation layers fail | Reject at first failure | Integration |
| **ML-007** | Retry logic with duplicate booking | Idempotent behavior across retries | Integration |
| **ML-008** | Webhook replay attack scenario | All layers reject duplicate | Integration |
| **ML-009** | Cal.com idempotency returns same booking | System detects and rejects | Integration |
| **ML-010** | Legitimate retry after transient failure | System allows retry if booking fresh | Integration |

### Edge Cases and Boundary Conditions

| Test Case ID | Scenario | Expected Outcome | Test Type |
|--------------|----------|----------|-----------|
| **EC-001** | Booking age exactly 29.999 seconds | Accept (just under threshold) | Unit |
| **EC-002** | Booking age exactly 30.000 seconds | Accept (at threshold) | Unit |
| **EC-003** | Booking age exactly 30.001 seconds | Reject (over threshold) | Unit |
| **EC-004** | System clock skew (±5 seconds) | Handle gracefully | Integration |
| **EC-005** | Cal.com API returns 500 error | No appointment created | Integration |
| **EC-006** | Cal.com response missing required fields | Validation fails gracefully | Unit |
| **EC-007** | Database connection lost during insert | Transaction rolls back | Integration |
| **EC-008** | Very long booking ID (255 chars) | Handle within column limits | Integration |
| **EC-009** | Special characters in booking ID | Store and retrieve correctly | Integration |
| **EC-010** | Unicode characters in metadata | Handle encoding correctly | Integration |
| **EC-011** | Extremely old booking (1 year old) | Reject as stale | Unit |
| **EC-012** | Future-dated booking (clock skew) | Accept if within tolerance | Unit |
| **EC-013** | Negative time difference (clock skew) | Handle gracefully | Unit |
| **EC-014** | Multiple calls in parallel (race) | All validated independently | Integration |
| **EC-015** | Database deadlock during insert | Retry or fail gracefully | Integration |

---

## Mock/Fake Strategy

### Cal.com API Response Mocking

```php
// Mock successful Cal.com response (fresh booking)
public function mockCalcomSuccessResponse(string $bookingId, Carbon $createdAt, ?string $callId = null): array
{
    return [
        'data' => [
            'id' => $bookingId,
            'createdAt' => $createdAt->toIso8601String(),
            'metadata' => [
                'call_id' => $callId,
            ],
            'status' => 'ACCEPTED',
            'attendees' => [
                [
                    'email' => 'test@example.com',
                    'name' => 'Test Customer',
                ],
            ],
        ],
    ];
}

// Mock stale Cal.com response (old booking returned by idempotency)
public function mockCalcomStaleResponse(string $bookingId, Carbon $createdAt, string $originalCallId): array
{
    return [
        'data' => [
            'id' => $bookingId,
            'createdAt' => $createdAt->toIso8601String(), // Old timestamp
            'metadata' => [
                'call_id' => $originalCallId, // Different call_id
            ],
            'status' => 'ACCEPTED',
        ],
    ];
}

// Mock Cal.com failure response
public function mockCalcomFailureResponse(): array
{
    return [
        'error' => 'Booking failed',
        'status' => 'FAILED',
    ];
}
```

### Database Seeding Strategy

```php
// Seed existing appointment with specific booking ID
public function seedExistingAppointment(string $bookingId, ?int $callId = null): Appointment
{
    return Appointment::factory()->create([
        'calcom_v2_booking_id' => $bookingId,
        'call_id' => $callId,
        'status' => 'scheduled',
        'created_at' => now()->subMinutes(5),
    ]);
}

// Seed stale appointment (older than 30 seconds)
public function seedStaleAppointment(string $bookingId): Appointment
{
    return Appointment::factory()->create([
        'calcom_v2_booking_id' => $bookingId,
        'status' => 'scheduled',
        'created_at' => now()->subMinutes(5),
    ]);
}

// Seed multiple appointments for concurrency testing
public function seedMultipleAppointments(int $count, string $bookingIdPrefix): Collection
{
    return Appointment::factory()
        ->count($count)
        ->sequence(fn ($sequence) => [
            'calcom_v2_booking_id' => "{$bookingIdPrefix}_{$sequence->index}",
        ])
        ->create();
}
```

### Service Mocking Patterns

```php
// Mock CalcomService for unit tests
protected function mockCalcomService(array $responses): void
{
    $mock = $this->createMock(CalcomService::class);

    $mock->expects($this->once())
        ->method('createBooking')
        ->willReturn($responses);

    $this->app->instance(CalcomService::class, $mock);
}

// Mock CallLifecycleService
protected function mockCallLifecycleService(): void
{
    $mock = $this->createMock(CallLifecycleService::class);

    $mock->expects($this->any())
        ->method('trackBooking')
        ->willReturn(true);

    $this->app->instance(CallLifecycleService::class, $mock);
}
```

---

## Assertion Patterns

### Layer 1: Freshness Assertions

```php
// Assert booking accepted (fresh)
$this->assertNotNull($result);
$this->assertEquals($expectedBookingId, $result['booking_id']);

// Assert booking rejected (stale)
$this->assertNull($result);
$this->assertDatabaseMissing('appointments', [
    'calcom_v2_booking_id' => $bookingId,
]);

// Assert log entry for stale booking
Log::assertLogged('error', fn ($log) =>
    str_contains($log->message, 'DUPLICATE BOOKING PREVENTION') &&
    str_contains($log->message, 'Stale booking detected')
);
```

### Layer 2: Call ID Assertions

```php
// Assert call_id mismatch rejection
$this->assertNull($result);

// Assert log entry for call_id mismatch
Log::assertLogged('error', fn ($log) =>
    str_contains($log->message, 'Call ID mismatch') &&
    $log->context['expected_call_id'] === $expectedCallId &&
    $log->context['received_call_id'] === $receivedCallId
);

// Assert booking not created in database
$this->assertDatabaseMissing('appointments', [
    'calcom_v2_booking_id' => $bookingId,
    'call_id' => $call->id,
]);
```

### Layer 3: Database Duplicate Assertions

```php
// Assert existing appointment returned
$this->assertNotNull($result);
$this->assertEquals($existingAppointment->id, $result->id);

// Assert no duplicate created
$this->assertDatabaseCount('appointments', 1);
$this->assertEquals(1, Appointment::where('calcom_v2_booking_id', $bookingId)->count());

// Assert log entry for duplicate prevention
Log::assertLogged('error', fn ($log) =>
    str_contains($log->message, 'DUPLICATE BOOKING PREVENTION') &&
    str_contains($log->message, 'already exists')
);

// Assert existing appointment unchanged
$this->assertEquals($existingAppointment->created_at, $result->created_at);
$this->assertEquals($existingAppointment->customer_id, $result->customer_id);
```

### Layer 4: Constraint Assertions

```php
// Assert constraint violation exception
$this->expectException(QueryException::class);
$this->expectExceptionMessage('Duplicate entry');

// Raw insert should fail
DB::table('appointments')->insert([
    'calcom_v2_booking_id' => $duplicateBookingId,
    // ... other fields
]);

// Assert constraint exists in schema
$this->assertTrue(
    Schema::hasIndex('appointments', 'unique_calcom_v2_booking_id')
);

// Assert transaction rolled back
$this->assertDatabaseCount('appointments', $originalCount);
```

### Multi-Layer Assertions

```php
// Assert validation stopped at first failure
$this->assertNull($result);

// Assert only one log entry for first failed layer
$logs = Log::channel('testing')->logs;
$duplicateLogs = array_filter($logs, fn($log) =>
    str_contains($log->message, 'DUPLICATE BOOKING PREVENTION')
);
$this->assertCount(1, $duplicateLogs);

// Assert appointment created only once across multiple attempts
$this->assertEquals(1, Appointment::where('calcom_v2_booking_id', $bookingId)->count());

// Assert idempotent behavior
$result1 = $service->createFromCall($call1, $bookingDetails);
$result2 = $service->createFromCall($call2, $bookingDetails);
$this->assertEquals($result1->id, $result2->id);
```

---

## Test Execution Order and Dependencies

### Execution Strategy

```bash
# 1. Unit tests first (fast, no database)
php artisan test --testsuite=Unit --filter=DuplicatePrevention

# 2. Feature integration tests (with database)
php artisan test --testsuite=Feature --filter=DuplicateBooking

# 3. Database constraint tests (database-level)
php artisan test --testsuite=Integration --filter=DuplicateBookingDatabase

# 4. Full suite
php artisan test --filter=Duplicate
```

### Test Dependencies

```
Layer 1 Tests (Freshness)
└── Independent - No dependencies

Layer 2 Tests (Call ID)
└── Independent - No dependencies

Layer 3 Tests (Database Check)
├── Requires: Database migrations
└── Requires: Factory classes (Appointment, Call, Customer, Service)

Layer 4 Tests (Constraint)
├── Requires: Database migrations with UNIQUE constraint
└── Requires: Layer 3 tests passing (validates application-level works)

Multi-Layer Tests
├── Requires: All layer tests passing
├── Requires: CalcomService mock
└── Requires: Full database setup
```

### Setup Order

1. **Database Setup**: Run migrations including UNIQUE constraint
2. **Factory Setup**: Ensure all factories available (Appointment, Call, Customer, Service)
3. **Service Mocking**: Mock CalcomService for predictable responses
4. **Layer 1-2 Tests**: Fast unit tests with mocks
5. **Layer 3 Tests**: Integration tests with real database
6. **Layer 4 Tests**: Constraint validation tests
7. **Multi-Layer Tests**: End-to-end integration tests

---

## Test Code Skeletons

### Unit Test: DuplicatePreventionTest.php

```php
<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Mockery;

class DuplicatePreventionTest extends TestCase
{
    private AppointmentCreationService $service;
    private CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->calcomService = Mockery::mock(CalcomService::class);
        $this->service = app(AppointmentCreationService::class);

        // Enable log testing
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // LAYER 1: Booking Freshness Tests
    // ==========================================

    /** @test */
    public function layer1_accepts_fresh_booking_within_30_seconds()
    {
        // Arrange: Fresh booking created 5 seconds ago
        $bookingId = 'cal_fresh_123';
        $createdAt = now()->subSeconds(5);
        $call = Call::factory()->create(['retell_call_id' => 'call_123']);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $call->retell_call_id);

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking accepted
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
        $this->assertArrayHasKey('booking_data', $result);
    }

    /** @test */
    public function layer1_accepts_booking_exactly_at_30_seconds()
    {
        // Arrange: Booking exactly 30 seconds old
        $bookingId = 'cal_boundary_123';
        $createdAt = now()->subSeconds(30);
        $call = Call::factory()->create(['retell_call_id' => 'call_boundary']);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $call->retell_call_id);

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking accepted at boundary
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
    }

    /** @test */
    public function layer1_rejects_stale_booking_older_than_30_seconds()
    {
        // Arrange: Stale booking created 31 seconds ago
        $bookingId = 'cal_stale_123';
        $createdAt = now()->subSeconds(31);
        $call = Call::factory()->create(['retell_call_id' => 'call_stale']);

        $calcomResponse = $this->mockStaleCalcomResponse($bookingId, $createdAt, 'different_call');

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking rejected
        $this->assertNull($result);

        // Assert: Error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'DUPLICATE BOOKING PREVENTION')),
                Mockery::on(fn($ctx) =>
                    $ctx['booking_id'] === $bookingId &&
                    $ctx['age_seconds'] > 30 &&
                    str_contains($ctx['reason'], 'Stale booking')
                )
            );
    }

    /** @test */
    public function layer1_accepts_booking_with_null_created_at()
    {
        // Arrange: Cal.com response missing createdAt
        $bookingId = 'cal_no_timestamp';
        $call = Call::factory()->create(['retell_call_id' => 'call_no_ts']);

        $calcomResponse = [
            'data' => [
                'id' => $bookingId,
                // createdAt missing
                'metadata' => ['call_id' => $call->retell_call_id],
                'status' => 'ACCEPTED',
            ],
        ];

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking accepted (no timestamp to validate)
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
    }

    // ==========================================
    // LAYER 2: Call ID Validation Tests
    // ==========================================

    /** @test */
    public function layer2_accepts_booking_with_matching_call_id()
    {
        // Arrange: Call ID matches current request
        $bookingId = 'cal_match_123';
        $callId = 'call_match_123';
        $createdAt = now()->subSeconds(5);
        $call = Call::factory()->create(['retell_call_id' => $callId]);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $callId);

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking accepted
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
    }

    /** @test */
    public function layer2_rejects_booking_with_mismatched_call_id()
    {
        // Arrange: Call ID does not match current request
        $bookingId = 'cal_mismatch_123';
        $currentCallId = 'call_current_123';
        $bookingCallId = 'call_different_456';
        $createdAt = now()->subSeconds(5);

        $call = Call::factory()->create(['retell_call_id' => $currentCallId]);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $bookingCallId);

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking rejected
        $this->assertNull($result);

        // Assert: Error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'Call ID mismatch')),
                Mockery::on(fn($ctx) =>
                    $ctx['expected_call_id'] === $currentCallId &&
                    $ctx['received_call_id'] === $bookingCallId
                )
            );
    }

    /** @test */
    public function layer2_accepts_booking_with_missing_call_id_in_metadata()
    {
        // Arrange: Metadata missing call_id field
        $bookingId = 'cal_no_callid';
        $createdAt = now()->subSeconds(5);
        $call = Call::factory()->create(['retell_call_id' => 'call_123']);

        $calcomResponse = [
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => [], // No call_id
                'status' => 'ACCEPTED',
            ],
        ];

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            $call
        );

        // Assert: Booking accepted (no call_id to validate)
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
    }

    /** @test */
    public function layer2_accepts_booking_when_call_parameter_is_null()
    {
        // Arrange: No call context provided
        $bookingId = 'cal_no_call_context';
        $createdAt = now()->subSeconds(5);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, 'some_call_id');

        $this->mockCalcomService()->shouldReceive('createBooking')
            ->once()
            ->andReturn($this->mockSuccessfulHttpResponse($calcomResponse));

        // Act
        $result = $this->service->bookInCalcom(
            Customer::factory()->create(),
            Service::factory()->create(),
            now(),
            45,
            null // No call
        );

        // Assert: Booking accepted (no call to validate against)
        $this->assertNotNull($result);
        $this->assertEquals($bookingId, $result['booking_id']);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function mockFreshCalcomResponse(string $bookingId, Carbon $createdAt, string $callId): array
    {
        return [
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => [
                    'call_id' => $callId,
                ],
                'status' => 'ACCEPTED',
                'attendees' => [
                    ['email' => 'test@example.com', 'name' => 'Test Customer'],
                ],
            ],
        ];
    }

    private function mockStaleCalcomResponse(string $bookingId, Carbon $createdAt, string $callId): array
    {
        return [
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => [
                    'call_id' => $callId,
                ],
                'status' => 'ACCEPTED',
            ],
        ];
    }

    private function mockSuccessfulHttpResponse(array $data): Response
    {
        return new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($data))
        );
    }

    private function mockCalcomService(): Mockery\MockInterface
    {
        return $this->calcomService;
    }
}
```

### Feature Test: DuplicateBookingPreventionIntegrationTest.php

```php
<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Mockery;

class DuplicateBookingPreventionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;
    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Service $testService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->testService = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'calcom_event_type_id' => 12345,
        ]);

        // Setup service
        $this->service = app(AppointmentCreationService::class);

        // Enable log testing
        Log::spy();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // LAYER 3: Database Duplicate Check Tests
    // ==========================================

    /** @test */
    public function layer3_creates_new_appointment_when_no_duplicate_exists()
    {
        // Arrange
        $bookingId = 'cal_new_booking_123';
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'retell_call_id' => 'call_new_123',
        ]);

        $bookingDetails = [
            'starts_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
        ];

        // Act
        $appointment = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            $bookingId,
            $call
        );

        // Assert: New appointment created
        $this->assertNotNull($appointment);
        $this->assertEquals($bookingId, $appointment->calcom_v2_booking_id);
        $this->assertEquals($call->id, $appointment->call_id);
        $this->assertDatabaseHas('appointments', [
            'calcom_v2_booking_id' => $bookingId,
            'customer_id' => $this->customer->id,
            'call_id' => $call->id,
        ]);
        $this->assertDatabaseCount('appointments', 1);
    }

    /** @test */
    public function layer3_returns_existing_appointment_when_duplicate_detected()
    {
        // Arrange: Existing appointment
        $bookingId = 'cal_existing_123';
        $existingCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'call_original',
        ]);

        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->testService->id,
            'calcom_v2_booking_id' => $bookingId,
            'call_id' => $existingCall->id,
            'status' => 'scheduled',
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(2)->addMinutes(45),
        ]);

        // Act: Try to create duplicate
        $newCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'call_duplicate_attempt',
        ]);

        $bookingDetails = [
            'starts_at' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(3)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
        ];

        $result = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            $bookingId,
            $newCall
        );

        // Assert: Existing appointment returned
        $this->assertNotNull($result);
        $this->assertEquals($existingAppointment->id, $result->id);
        $this->assertEquals($existingAppointment->calcom_v2_booking_id, $result->calcom_v2_booking_id);
        $this->assertEquals($existingAppointment->call_id, $result->call_id);

        // Assert: No duplicate created
        $this->assertDatabaseCount('appointments', 1);

        // Assert: Error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'DUPLICATE BOOKING PREVENTION')),
                Mockery::on(fn($ctx) =>
                    $ctx['existing_appointment_id'] === $existingAppointment->id &&
                    $ctx['calcom_booking_id'] === $bookingId &&
                    str_contains($ctx['reason'], 'already exists')
                )
            );
    }

    /** @test */
    public function layer3_handles_concurrent_requests_with_same_booking_id()
    {
        // Arrange
        $bookingId = 'cal_concurrent_123';
        $bookingDetails = [
            'starts_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
        ];

        // Act: Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $call = Call::factory()->create([
                'company_id' => $this->company->id,
                'retell_call_id' => "call_concurrent_{$i}",
            ]);

            $results[] = $this->service->createLocalRecord(
                $this->customer,
                $this->testService,
                $bookingDetails,
                $bookingId,
                $call
            );
        }

        // Assert: All return same appointment
        $this->assertCount(5, $results);
        $firstAppointmentId = $results[0]->id;
        foreach ($results as $result) {
            $this->assertEquals($firstAppointmentId, $result->id);
        }

        // Assert: Only one appointment created
        $this->assertDatabaseCount('appointments', 1);
        $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', $bookingId)->count());
    }

    /** @test */
    public function layer3_creates_appointment_when_booking_id_is_null()
    {
        // Arrange: No Cal.com booking ID
        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'call_no_calcom',
        ]);

        $bookingDetails = [
            'starts_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
        ];

        // Act
        $appointment = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            null, // No booking ID
            $call
        );

        // Assert: Appointment created without duplicate check
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->calcom_v2_booking_id);
        $this->assertEquals($call->id, $appointment->call_id);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'call_id' => $call->id,
            'calcom_v2_booking_id' => null,
        ]);
    }

    // ==========================================
    // MULTI-LAYER INTEGRATION TESTS
    // ==========================================

    /** @test */
    public function multilayer_fresh_booking_passes_all_layers()
    {
        // Arrange: Fresh booking, valid call_id, no duplicate
        $bookingId = 'cal_all_layers_pass';
        $callId = 'call_all_layers_123';
        $createdAt = now()->subSeconds(5);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'retell_call_id' => $callId,
        ]);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $callId);
        $this->mockCalcomService($calcomResponse);

        $bookingDetails = [
            'starts_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
            'confidence' => 85,
        ];

        // Act: Full flow through bookInCalcom and createLocalRecord
        $bookingResult = $this->service->bookInCalcom(
            $this->customer,
            $this->testService,
            Carbon::parse($bookingDetails['starts_at']),
            45,
            $call
        );

        $appointment = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            $bookingResult['booking_id'],
            $call
        );

        // Assert: All layers passed
        $this->assertNotNull($bookingResult);
        $this->assertNotNull($appointment);
        $this->assertEquals($bookingId, $appointment->calcom_v2_booking_id);
        $this->assertDatabaseHas('appointments', [
            'calcom_v2_booking_id' => $bookingId,
            'call_id' => $call->id,
        ]);
    }

    /** @test */
    public function multilayer_stale_booking_rejected_at_layer1()
    {
        // Arrange: Stale booking (Layer 1 should reject)
        $bookingId = 'cal_stale_multilayer';
        $callId = 'call_stale_123';
        $createdAt = now()->subSeconds(35); // Stale

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => $callId,
        ]);

        $calcomResponse = $this->mockStaleCalcomResponse($bookingId, $createdAt, $callId);
        $this->mockCalcomService($calcomResponse);

        // Act
        $result = $this->service->bookInCalcom(
            $this->customer,
            $this->testService,
            now(),
            45,
            $call
        );

        // Assert: Rejected at Layer 1
        $this->assertNull($result);

        // Assert: No appointment created
        $this->assertDatabaseMissing('appointments', [
            'calcom_v2_booking_id' => $bookingId,
        ]);

        // Assert: Layer 1 error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'Stale booking detected')),
                Mockery::any()
            );
    }

    /** @test */
    public function multilayer_invalid_call_id_rejected_at_layer2()
    {
        // Arrange: Fresh booking but wrong call_id (Layer 2 should reject)
        $bookingId = 'cal_wrong_callid';
        $currentCallId = 'call_current_123';
        $bookingCallId = 'call_different_456';
        $createdAt = now()->subSeconds(5); // Fresh

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => $currentCallId,
        ]);

        $calcomResponse = $this->mockFreshCalcomResponse($bookingId, $createdAt, $bookingCallId);
        $this->mockCalcomService($calcomResponse);

        // Act
        $result = $this->service->bookInCalcom(
            $this->customer,
            $this->testService,
            now(),
            45,
            $call
        );

        // Assert: Rejected at Layer 2
        $this->assertNull($result);

        // Assert: No appointment created
        $this->assertDatabaseMissing('appointments', [
            'calcom_v2_booking_id' => $bookingId,
        ]);

        // Assert: Layer 2 error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'Call ID mismatch')),
                Mockery::any()
            );
    }

    /** @test */
    public function multilayer_duplicate_caught_at_layer3()
    {
        // Arrange: Fresh booking, valid call_id, but duplicate exists (Layer 3 should catch)
        $bookingId = 'cal_duplicate_layer3';
        $callId = 'call_original_123';
        $createdAt = now()->subSeconds(5);

        // Create existing appointment
        $existingCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => $callId,
        ]);

        $existingAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->testService->id,
            'calcom_v2_booking_id' => $bookingId,
            'call_id' => $existingCall->id,
        ]);

        // New call attempts to book same booking_id
        $newCall = Call::factory()->create([
            'company_id' => $this->company->id,
            'retell_call_id' => 'call_duplicate_456',
        ]);

        $bookingDetails = [
            'starts_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s'),
            'service' => $this->testService->name,
            'duration_minutes' => 45,
        ];

        // Act: Layer 3 check
        $result = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            $bookingId,
            $newCall
        );

        // Assert: Existing appointment returned
        $this->assertNotNull($result);
        $this->assertEquals($existingAppointment->id, $result->id);

        // Assert: No new appointment created
        $this->assertDatabaseCount('appointments', 1);

        // Assert: Layer 3 error logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with(
                Mockery::on(fn($msg) => str_contains($msg, 'DUPLICATE BOOKING PREVENTION')),
                Mockery::on(fn($ctx) => $ctx['existing_appointment_id'] === $existingAppointment->id)
            );
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    private function mockFreshCalcomResponse(string $bookingId, Carbon $createdAt, string $callId): array
    {
        return [
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => ['call_id' => $callId],
                'status' => 'ACCEPTED',
            ],
        ];
    }

    private function mockStaleCalcomResponse(string $bookingId, Carbon $createdAt, string $callId): array
    {
        return [
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => ['call_id' => $callId],
                'status' => 'ACCEPTED',
            ],
        ];
    }

    private function mockCalcomService(array $response): void
    {
        $mock = Mockery::mock(CalcomService::class);
        $mock->shouldReceive('createBooking')
            ->once()
            ->andReturn(new Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode($response))
            ));

        $this->app->instance(CalcomService::class, $mock);
    }
}
```

### Integration Test: DuplicateBookingDatabaseConstraintTest.php

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DuplicateBookingDatabaseConstraintTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    // ==========================================
    // LAYER 4: Database UNIQUE Constraint Tests
    // ==========================================

    /** @test */
    public function layer4_unique_constraint_exists_in_schema()
    {
        // Assert: Constraint exists
        $indexes = Schema::getIndexes('appointments');
        $uniqueIndexExists = collect($indexes)->contains(function ($index) {
            return $index['name'] === 'unique_calcom_v2_booking_id' && $index['unique'];
        });

        $this->assertTrue($uniqueIndexExists, 'UNIQUE constraint on calcom_v2_booking_id should exist');
    }

    /** @test */
    public function layer4_allows_insert_with_unique_booking_id()
    {
        // Arrange
        $bookingId = 'cal_unique_123';

        // Act: Insert appointment
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId,
        ]);

        // Assert: Insert succeeds
        $this->assertNotNull($appointment);
        $this->assertEquals($bookingId, $appointment->calcom_v2_booking_id);
        $this->assertDatabaseHas('appointments', [
            'calcom_v2_booking_id' => $bookingId,
        ]);
    }

    /** @test */
    public function layer4_prevents_duplicate_booking_id()
    {
        // Arrange: Existing appointment
        $bookingId = 'cal_duplicate_constraint';

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId,
        ]);

        // Act & Assert: Attempting to insert duplicate throws exception
        $this->expectException(QueryException::class);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId, // Duplicate
        ]);
    }

    /** @test */
    public function layer4_allows_multiple_null_booking_ids()
    {
        // Arrange & Act: Create multiple appointments with NULL booking_id
        $appointments = Appointment::factory()
            ->count(5)
            ->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
                'calcom_v2_booking_id' => null,
            ]);

        // Assert: All inserts succeed (NULL values allowed)
        $this->assertCount(5, $appointments);
        $this->assertEquals(5, Appointment::whereNull('calcom_v2_booking_id')->count());
    }

    /** @test */
    public function layer4_rolls_back_transaction_on_constraint_violation()
    {
        // Arrange: Existing appointment
        $bookingId = 'cal_rollback_test';

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId,
        ]);

        $originalCount = Appointment::count();

        // Act: Attempt insert in transaction
        try {
            DB::transaction(function () use ($bookingId) {
                Appointment::factory()->create([
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                    'customer_id' => $this->customer->id,
                    'service_id' => $this->service->id,
                    'calcom_v2_booking_id' => $bookingId, // Duplicate
                ]);
            });
        } catch (QueryException $e) {
            // Expected
        }

        // Assert: Transaction rolled back, count unchanged
        $this->assertEquals($originalCount, Appointment::count());
    }

    /** @test */
    public function layer4_constraint_prevents_raw_sql_bypass_attempt()
    {
        // Arrange: Existing appointment
        $bookingId = 'cal_raw_sql_bypass';

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId,
        ]);

        // Act & Assert: Raw insert should fail with constraint violation
        $this->expectException(QueryException::class);

        DB::table('appointments')->insert([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $bookingId, // Duplicate
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function layer4_handles_concurrent_inserts_correctly()
    {
        // Arrange
        $bookingId = 'cal_concurrent_constraint';

        // Act: Simulate concurrent inserts
        $exceptions = 0;
        $successful = 0;

        for ($i = 0; $i < 5; $i++) {
            try {
                Appointment::factory()->create([
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                    'customer_id' => $this->customer->id,
                    'service_id' => $this->service->id,
                    'calcom_v2_booking_id' => $bookingId,
                ]);
                $successful++;
            } catch (QueryException $e) {
                $exceptions++;
            }
        }

        // Assert: Exactly one insert succeeded, others threw exceptions
        $this->assertEquals(1, $successful);
        $this->assertEquals(4, $exceptions);
        $this->assertEquals(1, Appointment::where('calcom_v2_booking_id', $bookingId)->count());
    }

    /** @test */
    public function layer4_index_improves_query_performance()
    {
        // Arrange: Create appointments
        Appointment::factory()
            ->count(1000)
            ->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'service_id' => $this->service->id,
            ]);

        $testBookingId = 'cal_performance_test';
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'calcom_v2_booking_id' => $testBookingId,
        ]);

        // Act: Query with EXPLAIN
        $explainResult = DB::select(
            'EXPLAIN SELECT * FROM appointments WHERE calcom_v2_booking_id = ?',
            [$testBookingId]
        );

        // Assert: Query uses index (type should be 'const' or 'ref', not 'ALL')
        $this->assertNotEquals('ALL', $explainResult[0]->type, 'Query should use index, not full table scan');
    }

    /** @test */
    public function layer4_migration_rollback_removes_constraint()
    {
        // Arrange: Verify constraint exists
        $indexesBefore = Schema::getIndexes('appointments');
        $constraintExists = collect($indexesBefore)->contains(fn($idx) =>
            $idx['name'] === 'unique_calcom_v2_booking_id'
        );
        $this->assertTrue($constraintExists, 'Constraint should exist before rollback');

        // Act: Rollback migration
        $this->artisan('migrate:rollback', ['--step' => 1, '--path' => 'database/migrations/2025_10_06_115958_add_unique_constraint_to_calcom_v2_booking_id.php']);

        // Assert: Constraint removed
        $indexesAfter = Schema::getIndexes('appointments');
        $constraintRemoved = !collect($indexesAfter)->contains(fn($idx) =>
            $idx['name'] === 'unique_calcom_v2_booking_id'
        );
        $this->assertTrue($constraintRemoved, 'Constraint should be removed after rollback');

        // Re-run migration for other tests
        $this->artisan('migrate');
    }
}
```

---

## Test Data Fixtures and Factories

### AppointmentFactory Extensions

Add to `/var/www/api-gateway/database/factories/AppointmentFactory.php`:

```php
/**
 * Create appointment with specific Cal.com booking ID
 */
public function withCalcomBookingId(string $bookingId): static
{
    return $this->state(fn (array $attributes) => [
        'calcom_v2_booking_id' => $bookingId,
        'external_id' => $bookingId,
    ]);
}

/**
 * Create appointment with fresh timestamp (within 30 seconds)
 */
public function fresh(): static
{
    return $this->state(fn (array $attributes) => [
        'created_at' => now()->subSeconds(5),
        'updated_at' => now()->subSeconds(5),
    ]);
}

/**
 * Create appointment with stale timestamp (older than 30 seconds)
 */
public function stale(): static
{
    return $this->state(fn (array $attributes) => [
        'created_at' => now()->subSeconds(60),
        'updated_at' => now()->subSeconds(60),
    ]);
}
```

### CallFactory Extensions

Add to `/var/www/api-gateway/database/factories/CallFactory.php`:

```php
/**
 * Create call with specific Retell call ID
 */
public function withRetellCallId(string $callId): static
{
    return $this->state(fn (array $attributes) => [
        'retell_call_id' => $callId,
    ]);
}

/**
 * Create call with booking analysis data
 */
public function withBookingAnalysis(string $callId, string $customerName): static
{
    return $this->state(fn (array $attributes) => [
        'retell_call_id' => $callId,
        'analysis' => [
            'custom_analysis_data' => [
                'patient_full_name' => $customerName,
                'appointment_details' => [
                    'desired_time' => now()->addHours(2)->format('Y-m-d H:i'),
                    'service' => 'Haircut',
                ],
            ],
        ],
    ]);
}
```

---

## Execution Commands

```bash
# Run all duplicate prevention tests
php artisan test --filter=Duplicate

# Run specific test layers
php artisan test --filter=DuplicatePreventionTest      # Unit tests
php artisan test --filter=DuplicateBookingPreventionIntegrationTest  # Feature tests
php artisan test --filter=DuplicateBookingDatabaseConstraintTest     # Integration tests

# Run with coverage
php artisan test --filter=Duplicate --coverage

# Run specific test cases
php artisan test --filter=layer1_accepts_fresh_booking
php artisan test --filter=layer2_rejects_booking_with_mismatched_call_id
php artisan test --filter=layer3_returns_existing_appointment
php artisan test --filter=layer4_prevents_duplicate_booking_id

# Run multi-layer tests only
php artisan test --filter=multilayer_

# Run edge case tests only
php artisan test --filter=edge_case_
```

---

## Quality Gates and Success Criteria

### Test Coverage Requirements

- **Unit Tests**: >95% coverage of validation logic
- **Feature Tests**: All integration paths covered
- **Edge Cases**: All boundary conditions validated
- **Database Tests**: All constraint scenarios tested

### Success Criteria

**Layer 1 (Freshness)**:
- ✅ Accept bookings ≤ 30 seconds old
- ✅ Reject bookings > 30 seconds old
- ✅ Handle NULL timestamps gracefully

**Layer 2 (Call ID)**:
- ✅ Accept matching call_id
- ✅ Reject mismatched call_id
- ✅ Handle missing call_id gracefully

**Layer 3 (Database Check)**:
- ✅ Create new when no duplicate
- ✅ Return existing when duplicate found
- ✅ Handle concurrent requests safely

**Layer 4 (Constraint)**:
- ✅ Prevent duplicates at database level
- ✅ Allow multiple NULL values
- ✅ Rollback transactions on violation

**Multi-Layer**:
- ✅ All layers work together
- ✅ Validation stops at first failure
- ✅ Idempotent behavior across retries

---

## Implementation Checklist

- [ ] Create `/tests/Unit/Services/Retell/DuplicatePreventionTest.php`
- [ ] Create `/tests/Feature/Integration/DuplicateBookingPreventionIntegrationTest.php`
- [ ] Create `/tests/Integration/DuplicateBookingDatabaseConstraintTest.php`
- [ ] Extend `AppointmentFactory` with helper methods
- [ ] Extend `CallFactory` with helper methods
- [ ] Run all tests and verify 100% pass rate
- [ ] Review test coverage report (aim for >95%)
- [ ] Document any edge cases discovered during testing
- [ ] Add CI/CD pipeline integration for duplicate prevention tests

---

## Notes and Recommendations

### Testing Best Practices

1. **Isolation**: Each test should be independent and repeatable
2. **Clarity**: Test names should clearly describe scenario and expected outcome
3. **Coverage**: Test happy path, error paths, and edge cases
4. **Performance**: Unit tests fast (<100ms), integration tests acceptable (<1s)
5. **Maintainability**: Keep tests DRY with helper methods and factories

### Common Pitfalls to Avoid

1. **Time-dependent tests**: Use Carbon::setTestNow() for predictable time-based tests
2. **Database state leaks**: Use RefreshDatabase trait to ensure clean state
3. **Mock over-specification**: Mock only what's necessary, avoid brittle tests
4. **Assertion bloat**: Focus on key assertions, not every property
5. **Test interdependence**: Avoid tests that depend on execution order

### Future Enhancements

1. **Performance benchmarks**: Add tests to measure validation overhead
2. **Stress testing**: Test with high concurrency (100+ simultaneous requests)
3. **Chaos testing**: Introduce random failures to test resilience
4. **Monitoring integration**: Add metrics collection for duplicate prevention effectiveness
5. **Webhook replay protection**: Test against replay attack scenarios

---

**Document End**
