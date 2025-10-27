# ROOT CAUSE ANALYSIS: Date Mismatch in Availability Check
**Call ID**: call_4fe3efe8beada329a8270b3e8a2
**Issue**: User requested "heute 15 Uhr" (today 3 PM) on Saturday 25.10.2025 → System offered alternatives for Monday 27.10.2025 (2-day shift)
**Severity**: CRITICAL
**Status**: IDENTIFIED & READY FOR FIX

---

## EXECUTIVE SUMMARY

User requested appointment for **Saturday, October 25, 2025 at 15:00 Uhr** (today). When this time wasn't available, the system correctly determined it wasn't available BUT offered alternatives for **Monday, October 27, 2025** instead of the **same day (Saturday) or next available**.

**Root Cause**: The `AppointmentAlternativeFinder::findNextWorkdayAlternatives()` method adds 2 days when the requested date falls on a weekend, because:
1. Requested date: Saturday 25.10
2. `getNextWorkday(Saturday)` is called
3. It adds days until it finds a non-weekend day
4. Result: Monday 27.10 (2 days ahead)

This is **incorrect UX** - when a user requests "today" on a weekend, we should NOT suggest 2 days later as the primary alternative.

---

## EVIDENCE

### Test Call Details
- **Date**: Saturday, 25. Oktober 2025
- **User Request**: "Herrenhaarschnitt für heute fünfzehn Uhr" (Haircut for today 3 PM)
- **Agent Extracted**: `datum = "25.10.2025"`, `time = "15:00"`
- **Transcript**: User confirmed "Ja" to the date
- **Agent Response**: "Leider ist der Termin am 25. Oktober 2025 um 15:00 Uhr nicht verfügbar..."

### Problematic Flow Chain

```
User Input: "heute 15 Uhr" (Saturday)
    ↓
DateTimeParser.parseDateTime()
    → Input params: date="25.10.2025", time="15:00"
    → Output: Carbon(2025-10-25 15:00) ← CORRECT
    ↓
checkAvailability(requestedDate=2025-10-25 15:00)
    → Cal.com API: No slots on Saturday
    → isAvailable = false
    ↓
AppointmentAlternativeFinder.findAlternatives(2025-10-25 15:00)
    ├─ Strategy 1: findSameDayAlternatives(Sat 25.10)
    │   → No slots on Saturday (salon closed weekends)
    │   → alternatives = []
    │
    ├─ Strategy 2: findNextWorkdayAlternatives(Sat 25.10)
    │   → Calls getNextWorkday(Sat 25.10)
    │   → Logic: Start with Sat+1day=Sun → not workday
    │   → Continue: Sun+1day=Mon → IS workday
    │   → Returns: Monday 27.10 ← PROBLEM: 2-DAY SHIFT
    │   → alternatives = [{datetime: Mon 27.10 15:00, ...}]
    │
    └─ Result: User sees "27. Oktober" instead of "25. Oktober" + "Samstag nicht verfügbar"
```

---

## ROOT CAUSE: CODE LOCATION & MECHANISM

### File: `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`

#### Bug Location 1: Lines 486-493 (getNextWorkday)
```php
private function getNextWorkday(Carbon $date): Carbon
{
    $next = $date->copy()->addDay();  // Add 1 day to input
    while (!$this->isWorkday($next)) {
        $next->addDay();  // Keep adding until workday found
    }
    return $next;
}
```

**Problem**: This method is **correct in isolation** - it returns the next workday. BUT it's being called inappropriately when the desired date IS a weekend.

#### Bug Location 2: Lines 254-285 (findNextWorkdayAlternatives)
```php
private function findNextWorkdayAlternatives(
    Carbon $desiredDateTime,
    int $durationMinutes,
    int $eventTypeId
): Collection {
    $alternatives = collect();
    $nextWorkday = $this->getNextWorkday($desiredDateTime);  // ← ALWAYS increments

    $sameTimeNextDay = $nextWorkday->copy()->setTime(
        $desiredDateTime->hour,
        $desiredDateTime->minute
    );
    // ... returns $sameTimeNextDay ...
}
```

**Problem**: When `$desiredDateTime` is **already a weekend** (e.g., Saturday), calling `getNextWorkday()` means:
- **Input**: Saturday (non-workday)
- **Output**: Monday (next workday)
- **Result**: 2-day jump instead of 1-day jump

#### Root Cause Chain:
1. **IMMEDIATE CAUSE**: `getNextWorkday()` adds days until non-weekend found
2. **UNDERLYING CAUSE**: Strategy doesn't check if `$desiredDateTime` IS a weekend
3. **DESIGN FLAW**: Weekend dates are treated as "search from tomorrow" instead of "inform user this day is unavailable"

---

## WHY THIS HAPPENS

### Test Case Walkthrough

**Input**: Saturday 25.10 @ 15:00 (weekend day, closed)

**Strategy Execution Order**:

1. **STRATEGY_SAME_DAY** (lines 209-249)
   - Checks Saturday 25.10 09:00-18:00
   - Cal.com returns: **NO SLOTS** (salon closed)
   - Result: `alternatives = []`

2. **STRATEGY_NEXT_WORKDAY** (lines 254-285)
   - Input: `desiredDateTime = Saturday 25.10 15:00`
   - Calls: `getNextWorkday(Saturday 25.10)`
   - Loop:
     - Check Saturday 25.10: `isWorkday('saturday') = false` → continue
     - Add day: now Sunday 26.10
     - Check Sunday 26.10: `isWorkday('sunday') = false` → continue
     - Add day: now **Monday 27.10**
     - Check Monday 27.10: `isWorkday('monday') = true` → BREAK
   - Returns: **Monday 27.10 15:00** ← 2 days later
   - Result: User sees Monday as first alternative

3. **STRATEGY_NEXT_WEEK** (lines 290-315)
   - Never reached (max_alternatives=2 already met)

---

## CORRECT BEHAVIOR (EXPECTED)

When user requests a weekend day, system should:

1. **Same Day**: Inform "Saturday is not our business day"
2. **Next Available**: Return Monday OR next business day
3. **UX**: Be explicit about why Saturday doesn't work

**Correct Response Should Be**:
```
Agent: "Leider haben wir Samstags geschlossen.
        Der nächste verfügbare Termin ist Montag, 27. Oktober um 15:00 Uhr.
        Möchten Sie diesen Termin?"
```

**Current Buggy Response**:
```
Agent: (Offers Monday 27.10 WITHOUT explaining why Saturday failed)
       User doesn't understand the 2-day jump
```

---

## IMPACT ANALYSIS

### Affected Scenarios
- ✅ **Any Friday request at end of day** (might need Monday)
  - Correct: Friday → check Fri → Saturday not workday → Monday ✓

- ❌ **Any Saturday request** (the bug)
  - Wrong: Saturday (not available) → check Sat+1 (Sunday) → check Sat+2 (Monday)
  - Should: Saturday (not available) → "We're closed Saturdays"

- ❌ **Any Sunday request**
  - Wrong: Sunday (not available) → check Sun+1 (Monday) → same as Saturday case
  - Should: Sunday (not available) → "We're closed Sundays"

- ✅ **Monday-Friday morning requests**: Work correctly
- ✅ **Monday-Friday afternoon requests**: Work correctly

### Customer Impact
- **Confusion**: "Why is the first alternative 2 days away?"
- **Lost Sales**: Customer expects faster alternative
- **Trust**: Feels like system is hiding something or malfunctioning

---

## THE FIX

### Strategy: Separate Weekend Handling from Workday Search

**File**: `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`

**Method to Modify**: `findNextWorkdayAlternatives()` at lines 254-285

**Current Code**:
```php
private function findNextWorkdayAlternatives(
    Carbon $desiredDateTime,
    int $durationMinutes,
    int $eventTypeId
): Collection {
    $alternatives = collect();
    $nextWorkday = $this->getNextWorkday($desiredDateTime);  // BUG: Always increments

    $sameTimeNextDay = $nextWorkday->copy()->setTime(
        $desiredDateTime->hour,
        $desiredDateTime->minute
    );
    // ...
}
```

**Fixed Code**:
```php
private function findNextWorkdayAlternatives(
    Carbon $desiredDateTime,
    int $durationMinutes,
    int $eventTypeId
): Collection {
    $alternatives = collect();

    // 🔧 FIX 2025-10-25: Skip NEXT_WORKDAY strategy if desired date IS already a workday
    // Bug: When user requested Saturday, getNextWorkday(Sat) returned Monday (Sat+2 days)
    // Fix: If desired date is workday, use it; if it's weekend, skip this strategy
    if ($this->isWorkday($desiredDateTime)) {
        // Desired date IS a workday - find next occurrence
        $nextWorkday = $this->getNextWorkday($desiredDateTime);
    } else {
        // Desired date is NOT a workday (weekend) - skip this strategy
        // Let other strategies handle the alternatives
        return collect();
    }

    $sameTimeNextDay = $nextWorkday->copy()->setTime(
        $desiredDateTime->hour,
        $desiredDateTime->minute
    );
    // ... rest of method unchanged ...
}
```

**Why This Works**:
1. If user requests **Monday at 15:00** and Monday isn't available:
   - `isWorkday(Monday) = true`
   - `getNextWorkday(Monday) = Tuesday` ✓ Correct (1 day)

2. If user requests **Saturday at 15:00** and Saturday isn't available:
   - `isWorkday(Saturday) = false`
   - Returns empty collection
   - Next strategy (NEXT_WEEK) will handle it ✓ Correct

---

## VERIFICATION APPROACH

### Unit Test Case

```php
public function test_next_workday_alternative_skips_weekends()
{
    $finder = new AppointmentAlternativeFinder();

    // Test 1: Request Monday (workday) → should suggest Tuesday
    $monday = Carbon::parse('2025-10-20 15:00'); // Monday
    $result = $this->invokePrivate($finder, 'findNextWorkdayAlternatives',
        [$monday, 60, 123]);
    $this->assertNotEmpty($result);
    $this->assertEquals('2025-10-21', $result->first()['datetime']->format('Y-m-d'));

    // Test 2: Request Saturday (weekend) → should return empty (skip this strategy)
    $saturday = Carbon::parse('2025-10-25 15:00'); // Saturday
    $result = $this->invokePrivate($finder, 'findNextWorkdayAlternatives',
        [$saturday, 60, 123]);
    $this->assertEmpty($result); // ← Should be empty, not return Monday

    // Test 3: Request Sunday (weekend) → should return empty (skip this strategy)
    $sunday = Carbon::parse('2025-10-26 15:00'); // Sunday
    $result = $this->invokePrivate($finder, 'findNextWorkdayAlternatives',
        [$sunday, 60, 123]);
    $this->assertEmpty($result); // ← Should be empty
}
```

### Integration Test Case

```php
public function test_availability_check_saturday_request()
{
    // Mock Saturday request
    $params = [
        'datum' => '25.10.2025',  // Saturday
        'uhrzeit' => '15:00',
        'dienstleistung' => 'Herrenhaarschnitt'
    ];

    $result = $this->handler->checkAvailability($params, 'test_call_id');

    // Verify alternatives don't show Monday (27.10) as first option
    $this->assertFalse($result['available']);

    // Check first alternative date
    if (!empty($result['alternatives'])) {
        $firstAlt = $result['alternatives'][0];
        // Should NOT be Monday 27.10
        $this->assertNotEquals('2025-10-27',
            Carbon::parse($firstAlt['datetime'])->format('Y-m-d'));
    }
}
```

### Live Test Case

**Procedure**:
1. Call system on Saturday (today 25.10 @ ~14:00)
2. Request "Termin für heute um 15:00"
3. System should say: "Leider haben wir Samstags geschlossen. ..."
4. System should suggest **Monday 27.10** but **explicitly state why Saturday failed**

---

## IMPLEMENTATION CHECKLIST

- [ ] **Step 1**: Modify `findNextWorkdayAlternatives()` in AppointmentAlternativeFinder.php
- [ ] **Step 2**: Add unit tests for weekend handling
- [ ] **Step 3**: Run full test suite
- [ ] **Step 4**: Manual integration test on actual system
- [ ] **Step 5**: Deploy with monitoring
- [ ] **Step 6**: Monitor logs for correct behavior
- [ ] **Step 7**: Verify no regressions in Monday-Friday requests

---

## PREVENTION MEASURES

1. **Add explicit comments** in strategy selection explaining weekend behavior
2. **Enhance logging** to track when strategies are skipped
3. **Add configuration** for business hours / weekend handling
4. **Review other strategies** for similar weekend-related bugs

---

## RELATED FINDINGS

### No Additional Bugs Found
- `DateTimeParser.parseDateTime()`: Correctly parses "heute 15 Uhr" → Saturday ✓
- `CalcomService.getAvailableSlots()`: Correctly returns empty for Saturday ✓
- `isTimeAvailable()`: Correctly returns false ✓
- `findSameDayAlternatives()`: Works correctly (returns empty for Saturday) ✓
- `getNextWorkday()`: Logic is correct, just misapplied ✓

---

## AFFECTED FILES

**Primary**:
- `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php` (Lines 254-285)

**Secondary** (May need verification):
- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (Line 895-902)
- `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php` (Line 193-195)

**Testing**:
- New unit tests for AppointmentAlternativeFinder
- Integration tests for checkAvailability on weekends

---

## SUMMARY

**Bug**: When user requests appointment on weekend (Sat/Sun), system jumps 2 days to next Monday instead of clearly stating "we're closed weekends" and presenting reasonable alternatives.

**Root Cause**: `findNextWorkdayAlternatives()` calls `getNextWorkday()` for ALL date inputs, including weekends. For weekend inputs, this creates a 2-day jump.

**Fix**: Skip `NEXT_WORKDAY` strategy when desired date IS a weekend. Let other strategies (SAME_DAY info + NEXT_AVAILABLE) handle weekend requests properly.

**Effort**: ~15 minutes to implement, ~30 minutes to test

**Risk**: LOW - only affects weekend date requests, very targeted fix
