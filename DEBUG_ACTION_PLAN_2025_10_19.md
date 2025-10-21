# Debug Action Plan - Slot Availability Bug

## Critical Bug: Why Are Valid Cal.com Slots Being Rejected?

### Problem Statement
```
Calendar Data: Cal.com has 32 available slots for 2025-10-20
System Request: Check if 13:00 is available
Cal.com Response: YES (slot exists in response data)
System Output: "Not available"

Result: Customer offered 10:30 (3 hours wrong direction) instead of accepting 13:00
```

---

## Investigation Path

### Step 1: Locate isTimeAvailable() Method
**Status**: ❓ Unknown if exists or where implemented

**Search Commands**:
```bash
# Find the method definition
grep -rn "function isTimeAvailable" app/

# Find all calls to isTimeAvailable
grep -rn "isTimeAvailable(" app/

# Check if it's in a trait
grep -rn "isTimeAvailable" app/Traits/

# Search for availability checking logic
grep -rn "isTimeAvailable\|time.*available\|available.*time" app/Services/
```

**Likely Locations**:
- `app/Services/AppointmentAlternativeFinder.php` (most likely)
- `app/Services/CalcomService.php` (possible)
- `app/Services/AvailabilityChecker.php` (if exists)
- A trait in `app/Traits/` (possible)

---

### Step 2: Understand Current Slot Filtering Logic

**What We Know**:
- Line 326-338 in RetellFunctionCallHandler.php flattens slots correctly
- Line 349 calls `$this->isTimeAvailable($requestedDate, $slots);`
- This returns FALSE even when requested time exists in `$slots`

**What We Need to Know**:
1. What is the exact format of elements in `$slots` array?
2. How does the time comparison work?
3. Are there timezone conversions?
4. Is there a 15-minute slot granularity requirement?

### Step 3: Add Debug Logging

**Add to RetellFunctionCallHandler.php around line 349**:

```php
// DEBUG: Log slot structure before availability check
Log::info('DEBUG: Availability Check Start', [
    'requested_date' => $requestedDate->format('Y-m-d H:i:s'),
    'requested_timezone' => $requestedDate->getTimezone()->getName(),
    'slots_count' => count($slots),
    'first_3_slots' => array_slice($slots, 0, 3),
    'slot_keys' => array_keys($slots[0] ?? []),
    'searching_for_hour' => $requestedDate->hour,
    'searching_for_minute' => $requestedDate->minute
]);

$isAvailable = $this->isTimeAvailable($requestedDate, $slots);

Log::info('DEBUG: Availability Check Result', [
    'is_available' => $isAvailable,
    'requested_time' => $requestedDate->format('Y-m-d H:i:s')
]);
```

**Add to isTimeAvailable() method** (once located):
```php
// Log what slots match the requested hour
$matchingHourSlots = array_filter($slots, function($slot) use ($requestedDate) {
    // Add your actual matching logic here for debugging
    return true; // placeholder
});

Log::debug('DEBUG: Slot Matching', [
    'total_slots' => count($slots),
    'matching_hour' => count($matchingHourSlots),
    'requested' => $requestedDate->format('H:i'),
    'matching_slots' => $matchingHourSlots
]);
```

---

### Step 4: Check Slot Format from Cal.com

**Question**: What format does Cal.com actually return?

**Possible Formats**:
```
// Format A: Simple time strings
$slots = ["13:00", "13:30", "14:00", ...]

// Format B: Objects with time property
$slots = [
    {"time": "13:00", "dateTime": "2025-10-20T13:00:00"},
    {"time": "13:30", "dateTime": "2025-10-20T13:30:00"}
]

// Format C: Full ISO timestamps
$slots = [
    "2025-10-20T13:00:00Z",
    "2025-10-20T13:30:00Z"
]

// Format D: Carbon/DateTime objects
$slots = [DateTime, DateTime, ...]
```

**To Determine**:
1. Dump `$slots` to log in RetellFunctionCallHandler line 346
2. Check `calcom-2025-10-19.log` for Cal.com API responses
3. Run: `grep -A 20 'slots_count' storage/logs/laravel.log | grep -E "slots|time|date"`

---

### Step 5: Check for Timezone Issues

**Likely Problem**: Time comparison across timezones

**Scenario**:
```
Cal.com returns: "2025-10-20T13:00:00Z" (UTC)
System expects: "2025-10-20 13:00:00" (Berlin local)
Comparison fails because: UTC 13:00 ≠ Berlin 13:00
```

**To Debug**:
```php
// In isTimeAvailable() method
Log::info('Timezone Debug', [
    'requested_date_tz' => $requestedDate->getTimezone()->getName(),
    'requested_date_utc' => $requestedDate->setTimezone('UTC')->format('Y-m-d H:i:s'),
    'slot_example' => $slots[0],
    'slot_parsed_tz' => isset($slots[0]['dateTime']) ?
        Carbon::parse($slots[0]['dateTime'])->getTimezone()->getName() : 'unknown'
]);
```

---

### Step 6: Check Slot Granularity

**Hypothesis**: Cal.com returns 15-minute slots, system expects specific times

**Example**:
- User requests 13:00
- Cal.com has slots: [13:00, 13:15, 13:30, 13:45, 14:00, ...]
- System checks: Is 13:00:00 exactly available?
- Finds 13:00:00, returns true ✓

But what if system checks: Is 13:00-13:59 fully available (60 min duration)?
- Has 13:00, 13:15, 13:30, 13:45 only
- That's 60 minutes worth but in 15-min blocks
- System might reject because it's not a contiguous block

**To Check**:
Look for duration logic in `isTimeAvailable()` or caller.

---

## Parallel Investigation: call_id = "None" Bug

### Problem
Test Call #2 sent `"None"` (string literal) as call_id.

### Fallback Location
File: `app/Http/Controllers/RetellFunctionCallHandler.php`
Lines: 73-110

### Current Logic
```php
private function getCallContext(?string $callId): ?array
{
    if (!$callId || $callId === 'None') {
        // Fallback: Find most recent active call
        $recentCall = Call::where('call_status', 'ongoing')
            ->where('start_timestamp', '>=', now()->subMinutes(5))
            ->orderBy('start_timestamp', 'desc')
            ->first();

        if ($recentCall) {
            $callId = $recentCall->retell_call_id;
        } else {
            Log::error('❌ Fallback failed: no recent active calls found');
            return null;
        }
    }
    // ...
}
```

### Why It Failed
1. **Timing Issue**: Call started at 21:31:48, check_availability at ~21:31:23 (22.873s in)
2. **Status Issue**: Did the DB have `call_status = 'ongoing'` at that time?
3. **5-minute window**: Was the call created within last 5 minutes?

### Improved Fallback Strategy

```php
private function getCallContext(?string $callId): ?array
{
    if (!$callId || $callId === 'None') {
        Log::warning('call_id is invalid, attempting smart fallback', [
            'original_call_id' => $callId,
            'current_time' => now()->toIso8601String()
        ]);

        // Strategy 1: Check Redis cache for recent call
        $cachedCallId = \Redis::get('active_call:' . now()->format('His'));
        if ($cachedCallId) {
            Log::info('✓ Fallback #1 (Redis): Found cached call_id', [
                'call_id' => $cachedCallId
            ]);
            $callId = $cachedCallId;
        } else {
            // Strategy 2: Find call by phone number + recent timestamp
            $incomingCall = Call::where('to_number', request()->input('phone') ?? null)
                ->where('start_timestamp', '>=', now()->subMinutes(1))
                ->orderBy('start_timestamp', 'desc')
                ->first();

            if ($incomingCall) {
                Log::info('✓ Fallback #2 (Phone lookup): Found call', [
                    'phone' => $incomingCall->to_number,
                    'call_id' => $incomingCall->retell_call_id
                ]);
                $callId = $incomingCall->retell_call_id;
            } else {
                // Strategy 3: Original fallback
                $recentCall = Call::where('call_status', 'ongoing')
                    ->where('start_timestamp', '>=', now()->subMinutes(5))
                    ->orderBy('start_timestamp', 'desc')
                    ->first();

                if ($recentCall) {
                    Log::info('✓ Fallback #3 (DB ongoing): Found call', [
                        'call_id' => $recentCall->retell_call_id
                    ]);
                    $callId = $recentCall->retell_call_id;
                } else {
                    Log::error('❌ All fallback strategies failed', [
                        'phone' => request()->input('phone') ?? 'unknown'
                    ]);
                    return null;
                }
            }
        }
    }
    // ... continue with normal flow
}
```

---

## Debugging Checklist

### Immediate (Today)

- [ ] Add debug logging to lines 341-350 in RetellFunctionCallHandler.php
- [ ] Deploy to staging
- [ ] Run test call with V115 agent
- [ ] Capture logs with slot structure, timezone info, comparison results
- [ ] Search for `isTimeAvailable` definition
- [ ] Check cal com service for timezone handling

### Short-term (This Sprint)

- [ ] Fix `isTimeAvailable()` logic based on findings
- [ ] Improve call_id fallback with Redis strategy
- [ ] Add unit tests for slot comparison with various formats
- [ ] Test with actual Cal.com 32-slot response
- [ ] Verify afternoon preference works end-to-end

### Medium-term (Next Sprint)

- [ ] Update Retell agent prompt to inject call_id properly
- [ ] Add monitoring for "not available" rate
- [ ] Create integration tests for availability checking
- [ ] Document slot format assumptions

---

## Log Files to Review

### Primary
- `/var/www/api-gateway/storage/logs/laravel.log` - contains function call data
- `/var/www/api-gateway/storage/logs/calcom-2025-10-19.log` - Cal.com API responses

### Commands to Extract Data
```bash
# Find Check_availability calls from test
grep -A 50 'check_availability' storage/logs/laravel.log | grep -E "slots|available|time|date"

# Find Cal.com responses
grep -E "total_slots|slots_count|2025-10-20" storage/logs/*.log

# Find timezone issues
grep -E "timezone|UTC|Europe/Berlin|+02:00" storage/logs/laravel.log

# Find isTimeAvailable execution
grep -E "isTimeAvailable|time.*available" storage/logs/laravel.log
```

---

## Test Case for Verification

### After Fixes - Run This Test

```php
// Test: Request 13:00 on Monday with full alternative finding
$testCase = [
    'date_string' => 'Montag',
    'time_string' => 'dreizehn Uhr',
    'expected_date' => '2025-10-20',
    'expected_time' => '13:00',
    'expected_available' => true, // if 13:00 in Cal.com
    'expected_direction' => 'later' // prefer afternoon times
];

// Should accept the appointment directly if available
// Should NOT offer 10:30 as first alternative
```

---

## Success Criteria

Fix is complete when:

1. ✅ Test Call #1 requests 13:00 → System confirms 13:00 available (not forced to alternatives)
2. ✅ Test Call #1 requests 14:00 → System confirms 14:00 available
3. ✅ Test Call #1 requests 11:30 → System confirms 11:30 available
4. ✅ Test Call #2 with call_id="None" → Successfully recovered from fallback, processed normally
5. ✅ Afternoon request (14:00) → Prefers 14:30, 15:00, 15:30 as alternatives (not 10:00)

---

**Start Here**: Find `isTimeAvailable()` definition and add debug logging

**Key File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (lines 341-350)

**Expected Finding**: The method is incorrectly comparing slot times against requested time
