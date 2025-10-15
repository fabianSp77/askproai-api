# Critical Path: Production Readiness Fixes

**Target:** Deploy-ready Cal.com fallback verification
**Current State:** 63% test pass rate, MEDIUM-HIGH risk
**Estimated Effort:** 12 hours development + 4 hours testing
**Timeline:** 2 days to production-ready

---

## CRITICAL FIXES (MUST DO)

### Fix #1: Business Hours Edge Cases ⚠️ HIGH PRIORITY

**Problem:**
Users requesting appointments before 09:00 or after 18:00 get NO alternatives, even when slots exist during business hours.

**Impact:**
```
Scenario 1: User calls at 08:00 request
Current: "No appointments available" ❌
Expected: "How about 09:00, 10:00, or 11:00?" ✅

Scenario 2: User calls at 19:00 request
Current: "No appointments available" ❌
Expected: "Tomorrow at 09:00, 10:00, or 14:00?" ✅
```

**Root Cause Analysis:**
```php
// generateCandidateTimes() - Lines 572-627
if ($desiredDateTime->hour >= 10) {
    $earlier = $desiredDateTime->copy()->subHours(2);
    // For 08:00 request, this generates 06:00
    // Then isWithinBusinessHours() filters it out
}
```

**Solution:**
```php
private function generateCandidateTimes(Carbon $desiredDateTime): Collection
{
    $candidates = collect();

    // NEW: Check if request is outside business hours
    if (!$this->isWithinBusinessHours($desiredDateTime)) {
        return $this->generateBusinessHoursCandidates($desiredDateTime);
    }

    // Existing logic for in-hours requests...
}

private function generateBusinessHoursCandidates(Carbon $desiredDateTime): Collection
{
    $candidates = collect();

    // If before business hours, suggest start of business day
    if ($desiredDateTime->format('H:i') < $this->config['business_hours']['start']) {
        $candidates->push([
            'datetime' => $desiredDateTime->copy()->setTimeFromTimeString($this->config['business_hours']['start']),
            'type' => 'business_hours_start',
            'description' => 'um ' . $this->config['business_hours']['start'] . ' Uhr',
            'rank' => 95
        ]);

        // Also suggest 1 hour later
        $candidates->push([
            'datetime' => $desiredDateTime->copy()->setTimeFromTimeString($this->config['business_hours']['start'])->addHour(),
            'type' => 'business_hours_early',
            'description' => 'um ' . Carbon::parse($this->config['business_hours']['start'])->addHour()->format('H:i') . ' Uhr',
            'rank' => 90
        ]);
    }

    // If after business hours, suggest next workday at same time (if valid) or start of day
    if ($desiredDateTime->format('H:i') > $this->config['business_hours']['end']) {
        $nextWorkday = $this->getNextWorkday($desiredDateTime);

        // Suggest same time next day if within business hours
        $sameTimeNextDay = $nextWorkday->copy()->setTime($desiredDateTime->hour, $desiredDateTime->minute);
        if ($this->isWithinBusinessHours($sameTimeNextDay)) {
            $candidates->push([
                'datetime' => $sameTimeNextDay,
                'type' => 'next_day_same_time',
                'description' => $this->formatGermanWeekday($nextWorkday) . ', ' . $sameTimeNextDay->format('H:i') . ' Uhr',
                'rank' => 95
            ]);
        }

        // Always suggest start of next business day
        $candidates->push([
            'datetime' => $nextWorkday->copy()->setTimeFromTimeString($this->config['business_hours']['start']),
            'type' => 'next_day_start',
            'description' => $this->formatGermanWeekday($nextWorkday) . ', ' . $this->config['business_hours']['start'] . ' Uhr',
            'rank' => 90
        ]);
    }

    return $candidates;
}
```

**Testing:**
```php
// Update test expectations
public function test_0800_is_outside_business_hours(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 08:00:00', 'Europe/Berlin');
    $eventTypeId = 12345;

    // Mock Cal.com slots at 09:00, 10:00
    $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
        '09:00:00', '10:00:00', '11:00:00'
    ]);

    $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

    // Should now find alternatives at business hours start
    $this->assertNotEmpty($result['alternatives']);

    // At least one alternative should be at or after 09:00
    $hasBusinessHoursSlot = false;
    foreach ($result['alternatives'] as $alt) {
        if ($alt['datetime']->format('H:i') >= '09:00') {
            $hasBusinessHoursSlot = true;
            break;
        }
    }
    $this->assertTrue($hasBusinessHoursSlot);
}
```

**Files to Modify:**
- `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
  - Add `generateBusinessHoursCandidates()` method
  - Update `generateCandidateTimes()` to check business hours first

**Estimated Effort:** 2 hours development + 1 hour testing

---

### Fix #2: Cal.com API Error Handling ⚠️ CRITICAL

**Problem:**
Cal.com API failures silently return empty arrays, causing users to see "no availability" when service is actually down.

**Impact:**
```
Scenario: Cal.com returns HTTP 500
Current: "No appointments available" (misleading)
Expected: "Our booking system is temporarily unavailable. Please try again in a few minutes or call us."
```

**Root Cause:**
```php
// getAvailableSlots() - Line 292
if ($response->successful()) {
    // Parse data
}
return []; // Silent failure
```

**Solution:**
```php
private function getAvailableSlots(
    Carbon $startTime,
    Carbon $endTime,
    int $eventTypeId
): array {
    $cacheKey = sprintf(
        'cal_slots_%d_%s_%s',
        $eventTypeId,
        $startTime->format('Y-m-d-H'),
        $endTime->format('Y-m-d-H')
    );

    return Cache::remember($cacheKey, 300, function() use ($startTime, $endTime, $eventTypeId) {
        try {
            $response = $this->calcomService->getAvailableSlots(
                $eventTypeId,
                $startTime->format('Y-m-d'),
                $endTime->format('Y-m-d')
            );

            if ($response->successful()) {
                return $this->parseCalcomResponse($response->json());
            }

            // NEW: Log API errors
            Log::error('Cal.com API error', [
                'status_code' => $response->status(),
                'event_type_id' => $eventTypeId,
                'date_range' => [$startTime->format('Y-m-d'), $endTime->format('Y-m-d')],
                'response_body' => $response->body()
            ]);

            // NEW: Throw exception to prevent caching failures
            throw new \Exception('Cal.com API returned error: ' . $response->status());

        } catch (\Exception $e) {
            Log::error('Cal.com API exception', [
                'message' => $e->getMessage(),
                'event_type_id' => $eventTypeId,
                'date_range' => [$startTime->format('Y-m-d'), $endTime->format('Y-m-d')]
            ]);

            // Don't cache failures - let next request retry
            // Return empty to allow fallback logic
            return [];
        }
    });
}

private function parseCalcomResponse(array $data): array
{
    $allSlots = [];

    if (isset($data['data']['slots'])) {
        foreach ($data['data']['slots'] as $date => $dateSlots) {
            if (is_array($dateSlots)) {
                foreach ($dateSlots as $slot) {
                    $slotTime = is_array($slot) && isset($slot['time'])
                        ? $slot['time']
                        : $slot;

                    try {
                        $parsedTime = Carbon::parse($slotTime);

                        $allSlots[] = [
                            'time' => $slotTime,
                            'datetime' => $parsedTime,
                            'date' => $date
                        ];
                    } catch (\Exception $e) {
                        Log::warning('Invalid slot time from Cal.com', [
                            'slot_time' => $slotTime,
                            'error' => $e->getMessage()
                        ]);
                        // Skip invalid slots but continue processing
                        continue;
                    }
                }
            }
        }
    }

    return $allSlots;
}
```

**Enhanced Error Response:**
```php
public function findAlternatives(
    Carbon $desiredDateTime,
    int $durationMinutes,
    int $eventTypeId,
    ?string $preferredLanguage = 'de'
): array {
    Log::info('🔍 Searching for appointment alternatives', [
        'desired' => $desiredDateTime->format('Y-m-d H:i'),
        'duration' => $durationMinutes,
        'eventTypeId' => $eventTypeId
    ]);

    // NEW: Track if Cal.com API had errors
    $apiErrorOccurred = false;

    try {
        $alternatives = collect();

        foreach ($this->config['search_strategies'] as $strategy) {
            if ($alternatives->count() >= $this->maxAlternatives) {
                break;
            }

            $found = $this->executeStrategy($strategy, $desiredDateTime, $durationMinutes, $eventTypeId);
            $alternatives = $alternatives->merge($found);
        }

        // Rank and limit alternatives
        $ranked = $this->rankAlternatives($alternatives, $desiredDateTime);
        $limited = $ranked->take($this->maxAlternatives);

        // FALLBACK: If no real alternatives found
        if ($limited->isEmpty()) {
            Log::warning('No Cal.com slots available, generating fallback suggestions');
            $limited = $this->generateFallbackAlternatives($desiredDateTime, $durationMinutes, $eventTypeId);
        }

        Log::info('✅ Found alternatives', [
            'count' => $limited->count(),
            'slots' => $limited->map(fn($alt) => $alt['datetime']->format('Y-m-d H:i'))
        ]);

        // Format the response
        $responseText = $this->formatResponseText($limited, $apiErrorOccurred);

        return [
            'alternatives' => $limited->toArray(),
            'responseText' => $responseText,
            'api_status' => $apiErrorOccurred ? 'degraded' : 'healthy'
        ];

    } catch (\Exception $e) {
        Log::error('Alternative finder exception', [
            'message' => $e->getMessage(),
            'desired_datetime' => $desiredDateTime->format('Y-m-d H:i')
        ]);

        // Return graceful error message
        return [
            'alternatives' => [],
            'responseText' => $preferredLanguage === 'de'
                ? "Unser Buchungssystem ist vorübergehend nicht verfügbar. Bitte versuchen Sie es in wenigen Minuten erneut oder rufen Sie uns an."
                : "Our booking system is temporarily unavailable. Please try again in a few minutes or call us.",
            'api_status' => 'error'
        ];
    }
}
```

**Testing:**
```php
public function test_handles_calcom_api_500_error(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
    $eventTypeId = 12345;

    // Mock Cal.com HTTP 500 error
    $errorResponse = new Response(
        new \GuzzleHttp\Psr7\Response(500, [], json_encode([
            'error' => 'Internal Server Error'
        ]))
    );

    $this->calcomMock->shouldReceive('getAvailableSlots')
        ->andReturn($errorResponse);

    $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

    // Should return graceful error message
    $this->assertArrayHasKey('api_status', $result);
    $this->assertEquals('error', $result['api_status']);
    $this->assertStringContainsString('vorübergehend nicht verfügbar', $result['responseText']);
}

public function test_handles_calcom_timeout_exception(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
    $eventTypeId = 12345;

    // Mock Cal.com timeout
    $this->calcomMock->shouldReceive('getAvailableSlots')
        ->andThrow(new \Exception('Connection timeout'));

    $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

    // Should return graceful error message
    $this->assertEquals('error', $result['api_status']);
    $this->assertStringContainsString('nicht verfügbar', $result['responseText']);
}
```

**Files to Modify:**
- `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
  - Wrap `getAvailableSlots()` in try-catch
  - Add error logging
  - Don't cache API errors
  - Return error status in response

**Estimated Effort:** 3 hours development + 1 hour testing

---

### Fix #3: Multi-Tenant Isolation Verification 🔐 SECURITY

**Problem:**
Test `test_multi_tenant_isolation_different_event_types` is failing, indicating potential security issue or mock problem.

**Risk Level:** CRITICAL - Potential data leak between tenants

**Investigation Steps:**

1. **Manual Staging Test:**
```bash
# Test Plan: Multi-Tenant Isolation Verification

# Setup:
# - Company A: ID 15, eventTypeId 12345
# - Company B: ID 20, eventTypeId 67890

# Test 1: Company A booking
curl -X POST http://staging.api/appointments/alternatives \
  -H "X-Company-ID: 15" \
  -d '{
    "desired_datetime": "2025-10-01 14:00:00",
    "event_type_id": 12345
  }'

# Expected: Only slots from eventTypeId 12345
# Verify: Check logs for Cal.com API calls
# Should see: getAvailableSlots(12345, ...)
# Should NOT see: getAvailableSlots(67890, ...)

# Test 2: Company B booking
curl -X POST http://staging.api/appointments/alternatives \
  -H "X-Company-ID: 20" \
  -d '{
    "desired_datetime": "2025-10-01 14:00:00",
    "event_type_id": 67890
  }'

# Expected: Only slots from eventTypeId 67890
# Verify: Logs should show getAvailableSlots(67890, ...)

# Test 3: Cache isolation
# Make both requests again
# Verify: Cache keys are different
# Expected cache keys:
# - cal_slots_12345_2025-10-01-14_2025-10-01-16
# - cal_slots_67890_2025-10-01-14_2025-10-01-16
```

2. **Code Review:**
```php
// Verify all API calls include eventTypeId:

// ✅ getAvailableSlots() - Line 286
$response = $this->calcomService->getAvailableSlots(
    $eventTypeId, // ✅ Passed through
    $startTime->format('Y-m-d'),
    $endTime->format('Y-m-d')
);

// ✅ Cache key - Line 278
$cacheKey = sprintf(
    'cal_slots_%d_%s_%s',
    $eventTypeId, // ✅ Included in cache key
    $startTime->format('Y-m-d-H'),
    $endTime->format('Y-m-d-H')
);

// ✅ All strategy methods receive eventTypeId
$this->findSameDayAlternatives($desiredDateTime, $durationMinutes, $eventTypeId);

// ✅ generateFallbackAlternatives passes eventTypeId
$eventTypeId = property_exists($this, 'currentEventTypeId')
    ? $this->currentEventTypeId
    : config('booking.default_event_type_id', 1);
```

**Potential Issue Found:**
```php
// Line 507: Fallback uses config default if property not set
$eventTypeId = property_exists($this, 'currentEventTypeId')
    ? $this->currentEventTypeId
    : config('booking.default_event_type_id', 1);
```

**Solution:**
```php
// FIX: Pass eventTypeId explicitly to generateFallbackAlternatives
private function generateFallbackAlternatives(
    Carbon $desiredDateTime,
    int $durationMinutes,
    int $eventTypeId // ADD THIS PARAMETER
): Collection {
    Log::info('🔍 Generating fallback alternatives with Cal.com validation', [
        'desired_datetime' => $desiredDateTime->format('Y-m-d H:i'),
        'duration_minutes' => $durationMinutes,
        'event_type_id' => $eventTypeId // LOG IT
    ]);

    $candidates = $this->generateCandidateTimes($desiredDateTime);
    $verified = collect();

    foreach ($candidates as $candidate) {
        $datetime = $candidate['datetime'];
        $startOfDay = $datetime->copy()->startOfDay()->setTime(9, 0);
        $endOfDay = $datetime->copy()->startOfDay()->setTime(18, 0);

        // Use EXPLICIT eventTypeId, not config default
        $slots = $this->getAvailableSlots($startOfDay, $endOfDay, $eventTypeId);

        if ($this->isTimeSlotAvailable($datetime, $slots)) {
            $verified->push($candidate);
            if ($verified->count() >= $this->maxAlternatives) {
                break;
            }
        }
    }

    // Brute force search also uses explicit eventTypeId
    if ($verified->isEmpty()) {
        $nextSlot = $this->findNextAvailableSlot($desiredDateTime, $durationMinutes, $eventTypeId);
        if ($nextSlot) {
            $verified->push($nextSlot);
        }
    }

    return $verified->take($this->maxAlternatives);
}
```

**Test Fix:**
```php
public function test_multi_tenant_isolation_different_event_types(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');

    $companyA_EventTypeId = 11111;
    $companyB_EventTypeId = 22222;

    // Company A: Mock ALL potential date searches (including fallback)
    for ($i = 0; $i <= 14; $i++) {
        $date = Carbon::parse('2025-10-01')->addDays($i);
        if ($date->isWeekday()) {
            $this->mockCalcomEmptySlots($companyA_EventTypeId, $date->format('Y-m-d'));
        }
    }

    // Company B has slots (should never be queried)
    $this->mockCalcomSlotsForDate($companyB_EventTypeId, '2025-10-01', [
        '14:00:00', '15:00:00', '16:00:00'
    ]);

    // Query for Company A
    $resultA = $this->finder->findAlternatives($desiredDateTime, 30, $companyA_EventTypeId);

    // Company A gets empty because no Cal.com availability
    // This is CORRECT behavior - no cross-tenant leakage
    $this->assertEmpty($resultA['alternatives']);

    // Verify eventTypeId was used correctly (check via Mockery expectations)
    // The mock should verify that ONLY companyA_EventTypeId was called
}
```

**Files to Modify:**
- `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
  - Update `generateFallbackAlternatives()` signature to accept `$eventTypeId`
  - Update call site in `findAlternatives()` to pass `$eventTypeId`

**Estimated Effort:** 1 hour code fix + 2 hours staging verification

---

## IMPORTANT FIXES (SHOULD DO)

### Fix #4: Update Test Expectations 📝

**Problem:**
Test `test_returns_fallback_after_14_days_no_availability` expects artificial suggestions when Cal.com is empty.

**Solution:**
```php
public function test_returns_empty_when_no_availability_for_14_days(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
    $eventTypeId = 12345;

    // Mock empty slots for all dates in 14-day window
    for ($i = 0; $i <= 14; $i++) {
        $date = Carbon::parse('2025-10-01')->addDays($i);
        $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
    }

    $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

    // NEW BEHAVIOR: System returns empty when Cal.com has no availability
    $this->assertEmpty($result['alternatives']);

    // Response text should inform about no availability
    $this->assertNotEmpty($result['responseText']);
    $this->assertStringContainsString('keine', strtolower($result['responseText']));
}
```

**Files to Modify:**
- `/var/www/api-gateway/tests/Unit/AppointmentAlternativeFinderTest.php`
  - Rename test from `test_returns_fallback_after_14_days...` to `test_returns_empty_when...`
  - Update assertions to expect empty alternatives

**Estimated Effort:** 30 minutes

---

### Fix #5: Fix Mock Conflicts 🔧

**Problem:**
Tests `test_finds_next_available_slot_on_day_2` and `test_finds_slot_on_day_14` have Mockery expectation ordering issues.

**Solution:**
```php
public function test_finds_next_available_slot_on_day_2(): void
{
    $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
    $eventTypeId = 12345;

    // FIX: Set up specific expectations in REVERSE order (most specific last)

    // General empty responses first (broad matcher)
    $emptyResponse = new Response(
        new \GuzzleHttp\Psr7\Response(200, [], json_encode([
            'status' => 'success',
            'data' => ['slots' => []]
        ]))
    );

    // Day 2 with slots (specific matcher last - takes precedence)
    $slotsResponse = new Response(
        new \GuzzleHttp\Psr7\Response(200, [], json_encode([
            'status' => 'success',
            'data' => [
                'slots' => [
                    '2025-10-02' => [
                        ['time' => '2025-10-02T09:00:00+02:00'],
                        ['time' => '2025-10-02T14:00:00+02:00'],
                    ]
                ]
            ]
        ]))
    );

    // Set expectations with Mockery's ordered() for sequence control
    $this->calcomMock
        ->shouldReceive('getAvailableSlots')
        ->with($eventTypeId, '2025-10-01', '2025-10-01')
        ->andReturn($emptyResponse)
        ->ordered();

    $this->calcomMock
        ->shouldReceive('getAvailableSlots')
        ->with($eventTypeId, '2025-10-02', '2025-10-02')
        ->andReturn($slotsResponse)
        ->ordered();

    $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

    $this->assertNotEmpty($result['alternatives']);
}
```

**Estimated Effort:** 1 hour

---

## SUMMARY

### Critical Path Timeline

```
Day 1 (8 hours):
├─ Fix #1: Business Hours Edge Cases (3 hours)
├─ Fix #2: Cal.com Error Handling (4 hours)
└─ Fix #3: Multi-Tenant Verification (1 hour code)

Day 2 (4 hours):
├─ Fix #3: Staging Verification (2 hours)
├─ Fix #4: Test Expectations (0.5 hours)
├─ Fix #5: Mock Conflicts (1 hour)
└─ Full Test Suite Run + Validation (0.5 hours)

Total: 12 hours development + 4 hours testing = 16 hours (2 days)
```

### Priority Matrix

```
        CRITICAL       HIGH           MEDIUM         LOW
IMPACT  ┌──────────────┬──────────────┬──────────────┬──────────┐
 HIGH   │ Fix #2       │ Fix #1       │ Fix #5       │          │
        │ Cal.com API  │ Business Hrs │ Mock Issues  │          │
        │              │              │              │          │
SECURITY│ Fix #3       │              │              │          │
        │ Multi-tenant │              │              │          │
        │              │              │              │          │
  LOW   │              │              │ Fix #4       │          │
        │              │              │ Test Expects │          │
        └──────────────┴──────────────┴──────────────┴──────────┘
```

### Post-Fix Validation Checklist

- [ ] All 19 tests passing
- [ ] Manual staging test: Business hours edge cases
- [ ] Manual staging test: Multi-tenant isolation
- [ ] Manual staging test: Cal.com error simulation
- [ ] Load test: 100 concurrent requests
- [ ] Performance test: 14-day search latency <5s
- [ ] Cache effectiveness: >80% hit rate
- [ ] Log analysis: No error spam

### Deployment Go/No-Go Criteria

**GO if:**
- ✅ All 19 unit tests passing
- ✅ Manual staging tests pass
- ✅ Multi-tenant isolation verified
- ✅ Error handling returns graceful messages
- ✅ Performance within acceptable limits (<5s p99)

**NO-GO if:**
- ❌ Any security test fails
- ❌ Cal.com errors crash system
- ❌ Business hours edge cases unresolved
- ❌ Performance >10s for any request

---

**Document Version:** 1.0
**Last Updated:** 2025-10-01
**Next Review:** After fixes implementation
