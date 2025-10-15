# TAG 14: E2E Integration Tests + Performance Benchmarks - Implementation Plan

**Date**: 2025-10-02
**Status**: Analysis Complete - Ready for Implementation

---

## Journey Analysis & Dependencies

### Journey 1: Stornierung innerhalb Frist (Cancellation Within Policy)

**Flow**: Retell → Policy Check → DB Update → Notification → Cal.com Cancel

**Dependencies**:
- `RetellFunctionCallHandler::handleCancellationAttempt()` → Entry point
- `AppointmentPolicyEngine::canCancel()` → Policy validation
- `PolicyConfigurationService::resolvePolicy()` → Config resolution (Staff → Service → Branch → Company hierarchy)
- `AppointmentModification` model → Track cancellation
- `AppointmentModificationStat` model → Quota tracking (materialized view)
- Cal.com API client → Booking cancellation

**Test Data Required**:
```php
// Factory Setup
$company = Company::factory()->create();
$branch = Branch::factory()->create(['company_id' => $company->id]);
$service = Service::factory()->create([
    'company_id' => $company->id,
    'calcom_event_type_id' => 123
]);
$customer = Customer::factory()->create(['company_id' => $company->id]);
$staff = Staff::factory()->create([
    'company_id' => $company->id,
    'branch_id' => $branch->id
]);
$phoneNumber = PhoneNumber::factory()->create([
    'company_id' => $company->id,
    'branch_id' => $branch->id
]);
$call = Call::factory()->create([
    'company_id' => $company->id,
    'customer_id' => $customer->id,
    'phone_number_id' => $phoneNumber->id,
    'retell_call_id' => 'test_call_' . uniqid()
]);
$appointment = Appointment::factory()->create([
    'company_id' => $company->id,
    'branch_id' => $branch->id,
    'service_id' => $service->id,
    'customer_id' => $customer->id,
    'staff_id' => $staff->id,
    'call_id' => $call->id,
    'starts_at' => Carbon::now()->addHours(50), // Beyond 48h policy
    'ends_at' => Carbon::now()->addHours(51),
    'status' => 'booked',
    'calcom_v2_booking_id' => 'cal_v2_' . uniqid()
]);

// Policy Configuration
PolicyConfiguration::factory()->forCompany($company)->create([
    'policy_type' => 'cancellation',
    'config' => [
        'hours_before' => 24,
        'max_cancellations_per_month' => 3,
        'fee_tiers' => [
            ['min_hours' => 48, 'fee' => 0.0],
            ['min_hours' => 24, 'fee' => 10.0],
            ['min_hours' => 0, 'fee' => 15.0]
        ]
    ]
]);
```

**Assertions**:
```php
// Response validation
$response->assertOk();
$response->assertJson([
    'success' => true,
    'status' => 'cancelled',
    'fee' => 0.0,
    'details' => [
        'hours_notice' => 50,
        'within_policy' => true
    ]
]);

// Database state
$this->assertDatabaseHas('appointments', [
    'id' => $appointment->id,
    'status' => 'cancelled'
]);
$this->assertDatabaseHas('appointment_modifications', [
    'appointment_id' => $appointment->id,
    'customer_id' => $customer->id,
    'modification_type' => 'cancel',
    'within_policy' => true,
    'fee_charged' => 0.0
]);

// Cal.com mock verification
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.cal.com/v2/bookings/cal_v2_xxx/cancel';
});
```

---

### Journey 2: Stornierung außerhalb Frist (Cancellation Outside Policy - With Fee)

**Flow**: Same as Journey 1 but with different timing

**Test Data Variation**:
```php
$appointment = Appointment::factory()->create([
    // ... same as Journey 1
    'starts_at' => Carbon::now()->addHours(20), // Less than 24h
    'ends_at' => Carbon::now()->addHours(21)
]);
```

**Assertions**:
```php
$response->assertJson([
    'success' => true,
    'status' => 'cancelled',
    'fee' => 10.0, // Fee applied due to <24h notice
    'details' => [
        'hours_notice' => 20,
        'within_policy' => false,
        'fee_tier_applied' => '24h-48h'
    ]
]);

$this->assertDatabaseHas('appointment_modifications', [
    'appointment_id' => $appointment->id,
    'within_policy' => false,
    'fee_charged' => 10.0
]);
```

---

### Journey 3: Stornierung mit Kontingentüberschreitung (Quota Exceeded)

**Flow**: Same entry but policy engine denies

**Test Data Variation**:
```php
// Create 3 prior cancellations
for ($i = 0; $i < 3; $i++) {
    $pastAppointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'company_id' => $company->id
    ]);

    AppointmentModification::factory()->create([
        'appointment_id' => $pastAppointment->id,
        'customer_id' => $customer->id,
        'modification_type' => 'cancel',
        'within_policy' => true,
        'fee_charged' => 0,
        'created_at' => Carbon::now()->subDays(rand(1, 25))
    ]);
}

// Optionally create materialized stat (simulating hourly job)
AppointmentModificationStat::create([
    'customer_id' => $customer->id,
    'stat_type' => 'cancellation_count',
    'period_start' => Carbon::now()->subDays(30),
    'period_end' => Carbon::now(),
    'count' => 3,
    'calculated_at' => Carbon::now()
]);
```

**Assertions**:
```php
$response->assertJson([
    'success' => false,
    'status' => 'denied',
    'reason' => 'quota_exceeded',
    'details' => [
        'quota_used' => 3,
        'quota_max' => 3
    ]
]);

// Appointment NOT cancelled
$this->assertDatabaseHas('appointments', [
    'id' => $appointment->id,
    'status' => 'booked' // Still booked
]);

// No new modification record
$this->assertEquals(3, AppointmentModification::where('customer_id', $customer->id)->count());
```

---

### Journey 4: Callback-Anfrage bei fehlender Verfügbarkeit

**Flow**: Retell → SmartAppointmentFinder → No slots → CallbackManagementService

**Dependencies**:
- `RetellFunctionCallHandler::handleCallbackRequest()`
- `SmartAppointmentFinder::findNextAvailable()` → Cal.com API
- `CallbackManagementService::createRequest()` → Create callback
- Auto-assignment logic → `findBestStaff()`

**Test Data Required**:
```php
// Service with Cal.com event type
$service = Service::factory()->create([
    'company_id' => $company->id,
    'calcom_event_type_id' => 456,
    'is_active' => true
]);

// Staff for auto-assignment
$staff1 = Staff::factory()->create([
    'branch_id' => $branch->id,
    'is_active' => true
]);
$staff2 = Staff::factory()->create([
    'branch_id' => $branch->id,
    'is_active' => true
]);

// Mock Cal.com response - NO SLOTS
Http::fake([
    'api.cal.com/v2/slots*' => Http::response([
        'status' => 'success',
        'data' => ['slots' => []] // Empty slots
    ], 200)
]);
```

**Assertions**:
```php
$response->assertJson([
    'success' => true,
    'callback_created' => true,
    'message' => 'Keine Termine verfügbar. Wir rufen Sie zurück.',
    'callback_id' => $callbackId
]);

$this->assertDatabaseHas('callback_requests', [
    'customer_id' => $customer->id,
    'branch_id' => $branch->id,
    'service_id' => $service->id,
    'phone_number' => $customer->phone,
    'priority' => CallbackRequest::PRIORITY_NORMAL,
    'status' => CallbackRequest::STATUS_ASSIGNED // Auto-assigned
]);

// Verify auto-assignment
$callback = CallbackRequest::latest()->first();
$this->assertNotNull($callback->assigned_to);
$this->assertContains($callback->assigned_to, [$staff1->id, $staff2->id]);
```

---

### Journey 5: Rückruf-Eskalation bei Überschreitung

**Flow**: Callback expires → Job detects → CallbackManagementService::escalate()

**Dependencies**:
- `EscalateOverdueCallbacksJob` → Cron job
- `CallbackManagementService::getOverdueCallbacks()`
- `CallbackManagementService::escalate()`
- `CallbackEscalation` model

**Test Data Required**:
```php
$callback = CallbackRequest::factory()->create([
    'branch_id' => $branch->id,
    'customer_id' => $customer->id,
    'priority' => CallbackRequest::PRIORITY_HIGH,
    'status' => CallbackRequest::STATUS_ASSIGNED,
    'assigned_to' => $staff1->id,
    'expires_at' => Carbon::now()->subHours(2), // Overdue
    'assigned_at' => Carbon::now()->subHours(6)
]);
```

**Assertions**:
```php
// Dispatch job
$this->artisan('app:escalate-overdue-callbacks');

// Verify escalation created
$this->assertDatabaseHas('callback_escalations', [
    'callback_request_id' => $callback->id,
    'escalated_from' => $staff1->id,
    'escalation_reason' => 'auto'
]);

// Verify reassignment
$callback->refresh();
$this->assertNotEquals($staff1->id, $callback->assigned_to);
$this->assertEquals($staff2->id, $callback->assigned_to);
```

---

### Journey 6: Nächster Termin mit Policy-Prüfung

**Flow**: Retell → SmartAppointmentFinder → PolicyEngine validation → Return slot

**Dependencies**:
- `RetellFunctionCallHandler::handleFindNextAvailable()`
- `SmartAppointmentFinder::findNextAvailable()` → Queries Cal.com
- `AppointmentPolicyEngine` → Validate slot against recurring policy (optional)

**Test Data Required**:
```php
// Mock Cal.com with available slots
Http::fake([
    'api.cal.com/v2/slots*' => Http::response([
        'status' => 'success',
        'data' => [
            'slots' => [
                ['time' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String()],
                ['time' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String()],
                ['time' => Carbon::tomorrow()->addDays(2)->setTime(9, 0)->toIso8601String()]
            ]
        ]
    ], 200)
]);

// Optional: Recurring policy to check slot interval
PolicyConfiguration::factory()->forService($service)->create([
    'policy_type' => 'recurring',
    'config' => [
        'min_interval_days' => 7,
        'allowed_frequencies' => ['weekly', 'biweekly']
    ]
]);
```

**Assertions**:
```php
$response->assertJson([
    'success' => true,
    'next_available' => Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i'),
    'alternative_slots' => [
        Carbon::tomorrow()->setTime(14, 0)->format('Y-m-d H:i'),
        Carbon::tomorrow()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i')
    ]
]);

// Cache verification
$this->assertTrue(
    Cache::has('appointment_finder:next_available:service_' . $service->id)
);
```

---

## Performance Bottleneck Analysis

### 1. Config Resolution Performance

**Current Implementation**:
```php
// PolicyConfigurationService::resolvePolicy()
// Hierarchy: Staff → Service → Branch → Company
// Each level = 1 DB query if cache miss
```

**Bottleneck**: 4 sequential queries in worst case (no cache)

**Optimization Opportunities**:
- ✅ Already implements caching (5min TTL)
- ✅ Batch resolution with `resolveBatch()` for multiple entities
- ⚠️ Could add eager loading: `$appointment->load('staff.branch.company', 'service')`

**Performance Test**:
```php
// Measure config resolution time
$start = microtime(true);
$policy = $this->policyService->resolvePolicy($appointment, 'cancellation');
$duration = (microtime(true) - $start) * 1000; // ms

$this->assertLessThan(50, $duration, 'Config resolution should be <50ms with cache');
```

---

### 2. Policy Validation Logic

**Current Implementation**:
```php
// AppointmentPolicyEngine::canCancel()
// 1. Resolve policy (cache hit: O(1), miss: O(4))
// 2. Calculate hours notice (O(1))
// 3. Check deadline (O(1))
// 4. Query modification count (O(1) with materialized stats, O(n) without)
```

**Bottleneck**: `getModificationCount()` without materialized stats = COUNT query on `appointment_modifications`

**Optimization**:
- ✅ Uses `AppointmentModificationStat` materialized view for O(1) lookup
- ⚠️ Fallback to real-time query if stat missing (hourly job dependency)

**Performance Test**:
```php
// Test with materialized stat
$start = microtime(true);
$result = $this->policyEngine->canCancel($appointment);
$durationWithStat = (microtime(true) - $start) * 1000;

// Test without stat (force fallback)
AppointmentModificationStat::where('customer_id', $customer->id)->delete();
Cache::flush();

$start = microtime(true);
$result = $this->policyEngine->canCancel($appointment);
$durationWithoutStat = (microtime(true) - $start) * 1000;

$this->assertLessThan(20, $durationWithStat, 'With stat should be <20ms');
$this->assertLessThan(100, $durationWithoutStat, 'Fallback should be <100ms');
```

---

### 3. Stats Aggregation Queries

**Current Implementation**:
```php
// AppointmentPolicyEngine::getModificationCount()
// Materialized view query (O(1)):
AppointmentModificationStat::where('customer_id', $customerId)
    ->where('stat_type', $statType)
    ->where('period_end', '>=', Carbon::now()->toDateString())
    ->first();

// Fallback real-time query (O(n)):
AppointmentModification::where('customer_id', $customerId)
    ->where('modification_type', $type)
    ->where('created_at', '>=', Carbon::now()->subDays($days))
    ->count();
```

**Bottleneck**: Real-time count on large datasets

**Optimization**:
- ✅ Materialized view eliminates most queries
- ⚠️ Hourly job must run reliably
- ⚠️ Edge case: New cancellation before stat refresh

**Performance Test**:
```php
// Create 100 modifications for customer
AppointmentModification::factory()->count(100)->create([
    'customer_id' => $customer->id,
    'modification_type' => 'cancel',
    'created_at' => Carbon::now()->subDays(rand(1, 29))
]);

// Benchmark real-time count
$start = microtime(true);
$count = AppointmentModification::where('customer_id', $customer->id)
    ->where('modification_type', 'cancel')
    ->where('created_at', '>=', Carbon::now()->subDays(30))
    ->count();
$realTimeDuration = (microtime(true) - $start) * 1000;

$this->assertLessThan(200, $realTimeDuration, 'Real-time count on 100 records <200ms');
```

---

### 4. Cal.com API Mock Overhead

**Current Implementation**:
```php
// SmartAppointmentFinder uses HTTP client
Http::fake([
    'api.cal.com/v2/slots*' => Http::response($mockData, 200)
]);
```

**Bottleneck**: HTTP mocking adds ~10-50ms overhead per request

**Optimization**:
- ✅ Caching reduces Cal.com calls (45s TTL)
- ✅ Test suite should use consistent mocks
- ⚠️ Consider custom test double for zero-latency tests

**Performance Test**:
```php
// Benchmark mock overhead
Http::fake([
    'api.cal.com/v2/slots*' => Http::response(['data' => ['slots' => []]], 200)
]);

$start = microtime(true);
$slots = $this->finder->findNextAvailable($service, Carbon::now());
$mockDuration = (microtime(true) - $start) * 1000;

$this->assertLessThan(100, $mockDuration, 'Mocked Cal.com call <100ms');
```

---

### 5. Notification Send (Async Considerations)

**Dependencies**: Event system for notifications
- `CallbackRequested` event → Notification
- `AppointmentCancelled` event → Email/SMS

**Bottleneck**: Synchronous event dispatch can delay response

**Optimization**:
- ✅ Use queued listeners for notifications
- ⚠️ Tests should use `Event::fake()` to avoid queue delays

---

## Implementation Plan

### Phase 1: Base Test Infrastructure (2-3 hours)

**File**: `/var/www/api-gateway/tests/Feature/E2E/AppointmentE2ETest.php`

```php
<?php

namespace Tests\Feature\E2E;

use App\Models\{Appointment, AppointmentModification, AppointmentModificationStat};
use App\Models\{Branch, Call, CallbackRequest, Company, Customer, PhoneNumber, Service, Staff};
use App\Models\PolicyConfiguration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Cache, Event, Http};
use Tests\TestCase;

class AppointmentE2ETest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Customer $customer;
    protected Staff $staff1;
    protected Staff $staff2;
    protected PhoneNumber $phoneNumber;
    protected Call $call;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->setupTestData();
        $this->mockCalcomApi();
        Event::fake(); // Prevent notification delays
    }

    private function setupTestData(): void
    {
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 123,
            'is_active' => true
        ]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->staff1 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true
        ]);
        $this->staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'is_active' => true
        ]);
        $this->phoneNumber = PhoneNumber::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
        $this->call = Call::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'phone_number_id' => $this->phoneNumber->id,
            'retell_call_id' => 'test_call_' . uniqid()
        ]);
    }

    private function mockCalcomApi(): void
    {
        Http::fake([
            'api.cal.com/v2/bookings/*/cancel' => Http::response(['status' => 'success'], 200),
            'api.cal.com/v2/slots*' => Http::response([
                'status' => 'success',
                'data' => ['slots' => []]
            ], 200)
        ]);
    }

    // Test methods follow...
}
```

---

### Phase 2: Journeys 1-3 (Cancellation Flows) (3-4 hours)

**Tests**:
1. `test_journey_1_cancellation_within_policy_window()`
2. `test_journey_2_cancellation_outside_policy_with_fee()`
3. `test_journey_3_cancellation_quota_exceeded_denial()`

**Key Implementations**:
- Policy configuration setup
- Retell webhook simulation
- Multi-assertion validation (response, DB, events)

---

### Phase 3: Journeys 4-6 (Callback + Slot Finding) (3-4 hours)

**Tests**:
1. `test_journey_4_callback_request_on_unavailability()`
2. `test_journey_5_callback_escalation_on_expiry()`
3. `test_journey_6_find_next_available_with_policy()`

**Key Implementations**:
- Cal.com mock variations (no slots, multiple slots)
- Callback auto-assignment logic
- Job dispatch testing

---

### Phase 4: Performance Benchmark Base (2-3 hours)

**File**: `/var/www/api-gateway/tests/Performance/PolicyEngineBenchmark.php`

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;

class PolicyEngineBenchmark extends TestCase
{
    use DatabaseTransactions;

    private const BENCHMARK_ITERATIONS = 100;
    private const MAX_ACCEPTABLE_MS = [
        'config_resolution_cached' => 10,
        'config_resolution_cold' => 50,
        'policy_validation_with_stat' => 20,
        'policy_validation_without_stat' => 100,
        'stats_query_100_records' => 200,
        'calcom_mock_call' => 100
    ];

    // Benchmark methods...
}
```

---

### Phase 5: 5 Performance Benchmarks (3-4 hours)

**Tests**:
1. `benchmark_config_resolution_performance()`
2. `benchmark_policy_validation_with_vs_without_stats()`
3. `benchmark_stats_aggregation_scaling()`
4. `benchmark_calcom_api_mock_overhead()`
5. `benchmark_full_cancellation_e2e_latency()`

**Metrics to Capture**:
- Average execution time (ms)
- P95/P99 latency
- DB query count
- Cache hit rate

---

## Expected Outcomes

### Test Coverage Improvements
- **E2E Coverage**: 6 critical user journeys fully validated
- **Integration Points**: Retell → Policy → DB → Cal.com flow tested
- **Edge Cases**: Quota limits, policy hierarchies, auto-assignment

### Performance Insights
- **Baseline Metrics**: Document current performance characteristics
- **Bottleneck Identification**: Quantify materialized stats impact
- **Optimization Validation**: Measure cache effectiveness

### Documentation
- **Test Report**: Automated benchmark results
- **Performance Baselines**: Reference for future optimization
- **Regression Detection**: Alerts if metrics degrade

---

## Success Criteria

✅ All 6 E2E journeys pass with realistic data
✅ Performance benchmarks establish baselines <100ms for critical paths
✅ Test suite runs in <5 minutes (with DB transactions)
✅ Zero flaky tests (deterministic mocking)
✅ Documentation generated from benchmark output

---

## Notes & Considerations

### Mock Strategy
- **Cal.com**: HTTP::fake() with consistent responses
- **Notifications**: Event::fake() to avoid queue delays
- **Time**: Carbon::setTestNow() for deterministic dates

### Database Strategy
- **Transactions**: Use `DatabaseTransactions` trait for isolation
- **Factory States**: Leverage existing factory methods
- **Materialized Stats**: Manually seed when testing fallback logic

### Future Enhancements
- Load testing with 1000+ concurrent cancellations
- Real Cal.com integration tests (tagged `@integration`)
- Performance regression CI pipeline
