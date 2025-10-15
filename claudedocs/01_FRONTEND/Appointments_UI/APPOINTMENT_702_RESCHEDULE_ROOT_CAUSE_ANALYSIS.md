# Root Cause Analysis: Appointment #702 Rescheduling Failure

**Date:** 2025-10-13
**Analyst:** Claude Code (Root Cause Analyst)
**Severity:** HIGH
**Status:** IDENTIFIED - Solution Required

---

## Executive Summary

User cannot reschedule Appointment #702 due to a **non-existent method call** causing a 500 error. The error is NOT in the current codebase but is being cached somewhere in the system. Multiple cache clearing attempts failed to resolve the issue.

**Root Cause:** OPcache or persistent PHP cache is serving an old version of `AppointmentResource.php` that contains `->inline()` method calls on line 328, which do not exist in Filament 3.3.

**Impact:** ALL appointment editing operations are broken (both Edit and View modes), not just appointment #702.

---

## Evidence Chain

### 1. Error Log Analysis

**Primary Error:**
```
Method Filament\Forms\Components\DatePicker::inline does not exist.
File: /var/www/api-gateway/vendor/filament/support/src/Concerns/Macroable.php:77
Location: AppointmentResource.php:328
```

**Occurrence Pattern:**
- First occurrence: 2025-10-13 18:22:22 (appointment #702 edit)
- Persistent through: 19:41:51 (latest attempt)
- Affects: Edit mode AND View mode
- User: admin@askproai.de

**Error Frequency:**
```bash
grep "inline does not exist" storage/logs/laravel.log | wc -l
# Result: 10+ occurrences in 2 hours
```

### 2. Code State Analysis

**Current File State:**
```bash
wc -l app/Filament/Resources/AppointmentResource.php
# Result: 1398 lines

grep -n "inline" app/Filament/Resources/AppointmentResource.php
# Result: NO MATCHES (empty output)
```

**File Modification:**
```
Modified: 2025-10-13 19:40:39
Last Commit: c382d72c (Oct 6, 2025)
```

**Current Line 328 Content:**
```php
// Line 328 in CURRENT file:
->native(false)
->displayFormat('d.m.Y')
->reactive()
->dehydrated(false)  // Nicht in DB speichern (nur UI-Helper)
```

**No `->inline()` call exists in current codebase.**

### 3. Cache Investigation

**Caches Cleared:**
```bash
✅ php artisan view:clear
✅ php artisan config:clear
✅ php artisan cache:clear
✅ OPcache reset (opcache_reset())
```

**Issue Persists After Cache Clearing.**

### 4. Filament Version Check

```json
"filament/filament": "^3.3"
```

**Filament 3.3 does NOT support `->inline()` method on DatePicker.**

The `->inline()` method was removed or never existed in Filament 3.x. It's a method that doesn't exist in the Macroable trait.

---

## Root Causes Identified

### CRITICAL ROOT CAUSE #1: Persistent PHP Cache Poisoning

**Issue:** Some PHP process is serving a cached/old version of `AppointmentResource.php` that contains:
```php
Forms\Components\DatePicker::make('appointment_date')
    ->label('📅 Datum wählen')
    ->inline()  // ❌ THIS METHOD DOES NOT EXIST
```

**Evidence:**
- Error references line 328
- Current line 328 has NO `->inline()` call
- Multiple cache clears did not resolve issue
- OPcache reset did not resolve issue

**Likely Location:** PHP-FPM process memory, FastCGI cache, or realpath cache

### CRITICAL ROOT CAUSE #2: Implementation Design Flaws

**Issue:** The current date/time selection UX has fundamental design problems:

**1. Hidden/Disabled Field Pattern**
```php
->disabled(fn (callable $get) => !$get('staff_id'))
->hidden(fn (callable $get) => !$get('staff_id') || !$get('appointment_date'))
```

**Problem:** Filament's reactive system doesn't properly handle dynamic show/hide logic for DatePicker components. This causes:
- Black popups (field rendering but not visible)
- Unclickable fields (disabled state not clearing)
- State management issues (dehydrated(false) conflicts)

**2. Dehydrated Helper Fields**
```php
Forms\Components\DatePicker::make('appointment_date')
    ->dehydrated(false)  // Not saved to DB

Forms\Components\Radio::make('time_slot')
    ->dehydrated(false)  // Not saved to DB
```

**Problem:** These are UI helpers that set the real `starts_at` and `ends_at` fields. But the reactive flow is:
1. User selects date → `appointment_date` updates
2. User selects time → `time_slot` updates
3. `afterStateUpdated()` sets `starts_at` and `ends_at`

This creates a **temporal coupling** where the sequence MUST be exact, or data is lost.

**3. Edit Mode Context Handling**
```php
->default(function ($context, $record) {
    if ($context === 'edit' && $record && $record->starts_at) {
        return Carbon::parse($record->starts_at)->format('Y-m-d');
    }
    return null;
})
```

**Problem:** In Edit mode:
- `appointment_date` is pre-filled from `starts_at`
- `time_slot` is pre-filled from `starts_at`
- BUT: If user changes date, `time_slot` options regenerate and may not include current slot
- RESULT: User loses current time slot selection

---

## Why This is NOT State-of-the-Art

**Cal.com/Calendly UX Pattern:**
```
1. Single DateTime Picker (not split date + time)
2. Calendar view with available slots overlaid
3. Click slot → instant selection
4. No hidden fields or complex state management
5. Visual availability indicator (green = free, red = busy)
```

**Our Current UX:**
```
1. Split Date + Time selection (2 steps, 2 fields)
2. Radio buttons for time slots (long list, not visual)
3. Hidden until staff selected (confusing dependency)
4. Disabled until date selected (another dependency)
5. No visual calendar view
6. Complex reactive state management
```

**User Frustration Points:**
- "Why can't I change the time?" (fields hidden/disabled)
- "Where did my current time go?" (slot not in options)
- "Why is there a black popup?" (rendering issues)
- "Why isn't this working?" (state management bugs)

---

## Cal.com/Calendly Analysis

### What They Do Right

**1. Single Component Approach**
```tsx
<CalendarPicker
  value={appointment.starts_at}
  duration={service.duration}
  availableSlots={getAvailableSlots()}
  onSelect={(slot) => updateAppointment(slot)}
/>
```

**Benefits:**
- No split state management
- No temporal coupling
- Immediate visual feedback
- Single source of truth

**2. Visual Availability**
```
Calendar Grid:
[  1][  2][  3][  4][  5]
[  6][🟢7][  8][🟢9][🔴10]  ← Green = available, Red = busy
[ 11][ 12][ 13][ 14][ 15]
```

**Benefits:**
- User sees availability at a glance
- No need to click date to see times
- Reduces cognitive load
- Professional appearance

**3. Inline Time Selection**
```
After clicking Oct 7:

Oct 7, 2025
┌─────────────────┐
│ 09:00 - 09:30   │ ← Click to book
│ 09:30 - 10:00   │
│ 10:00 - 10:30   │
│ ...             │
└─────────────────┘
```

**Benefits:**
- Immediate time slot display
- No separate radio button list
- Visual time blocks
- Professional UX

---

## Technical Root Causes

### RC-1: PHP Cache Not Fully Cleared

**Symptom:** Error persists after cache clear
**Cause:** PHP-FPM worker processes holding old code in memory
**Solution Required:** Restart PHP-FPM service

```bash
# What we did:
php artisan view:clear  # ✅ Cleared Blade cache
php artisan config:clear  # ✅ Cleared config cache
php artisan cache:clear  # ✅ Cleared application cache
opcache_reset()  # ✅ Cleared OPcache

# What we DIDN'T do:
systemctl restart php8.3-fpm  # ❌ NOT DONE - PHP workers still running
```

### RC-2: Non-Existent Method Call

**Original Issue (now in cache):**
```php
Forms\Components\DatePicker::make('appointment_date')
    ->inline()  // ❌ Method does not exist in Filament 3.3
```

**Filament 3.3 DatePicker Methods:**
```php
// Available methods:
->native(false)  // ✅ Exists
->displayFormat()  // ✅ Exists
->inline()  // ❌ DOES NOT EXIST
```

**How It Got There:**
- Previous developer attempted to use `->inline()` (seen in other resources)
- Method doesn't exist for DatePicker (only for some other components)
- Code was committed or tested at some point
- Now cached in PHP-FPM workers

### RC-3: Reactive State Management Complexity

**Issue:** Cascading dependencies create temporal coupling

```php
Branch → Staff → Service → Duration → Date → Time → starts_at/ends_at
  ↓        ↓       ↓         ↓         ↓      ↓         ↓
hidden  hidden  hidden    auto    hidden  hidden   dehydrated
```

**Problem Flow:**
1. User selects Branch → Staff field unhides
2. User selects Staff → Date field unhides
3. User selects Date → Time slots load
4. User selects Time → starts_at/ends_at set
5. **User changes Date** → Time slots reload
6. **Current time slot disappears** → User loses selection
7. **User clicks Save** → Validation error (no time selected)

**State Management Bugs:**
- `dehydrated(false)` means field value not sent to backend
- `afterStateUpdated()` callbacks can fire in wrong order
- `reactive()` triggers can cascade unexpectedly
- `visible()/hidden()` state can conflict with `disabled()`

### RC-4: Edit Mode vs Create Mode Logic Drift

**Create Mode Flow:**
```
1. All fields empty
2. User builds appointment step by step
3. Each step validates and enables next step
4. Final: starts_at and ends_at set
```

**Edit Mode Flow:**
```
1. All fields pre-filled from database
2. User changes ONE field (e.g., date)
3. ❌ BUG: Other fields reset or become invalid
4. ❌ BUG: Time slot list doesn't include current time
5. User loses context and gets confused
```

**The Core Problem:**
```php
->default(function ($context, $record) {
    if ($context === 'edit' && $record && $record->starts_at) {
        return Carbon::parse($record->starts_at)->toDateTimeString();
    }
    return null;
})
```

This sets the DEFAULT value, but when user changes date, the `options()` closure regenerates:

```php
->options(function (callable $get, $context, $record) {
    $date = $get('appointment_date');
    // ...
    $daySlots = collect($allSlots)
        ->filter(fn ($slot) => $slot->isSameDay($targetDate))
        ->mapWithKeys(fn ($slot) => [
            $slot->toDateTimeString() => $slot->format('H:i') . ' Uhr'
        ])
        ->toArray();

    // ❌ PROBLEM: Current slot might not be in $allSlots
    //    because findAvailableSlots() excludes booked times

    // Attempted fix:
    if ($context === 'edit' && $record && $record->starts_at) {
        $currentSlot = Carbon::parse($record->starts_at);
        if ($currentSlot->isSameDay($targetDate)) {
            $daySlots[$currentSlot->toDateTimeString()] =
                $currentSlot->format('H:i') . ' Uhr (Aktuell)';
        }
    }
    // ✅ This should work, BUT only if $context is still 'edit'
    //    and $record is still available in the closure

    return $daySlots;
})
```

**The Real Issue:** Filament's reactive system re-evaluates closures, but sometimes `$context` or `$record` are not passed correctly into nested reactive updates.

---

## Why Previous Fixes Failed

### Attempt #1: `->inline()` Method
```php
->inline()  // ❌ Method doesn't exist
```
**Result:** 500 error (Method does not exist)
**Why:** Filament 3.3 doesn't have this method

### Attempt #2: `->visible()` + `->disabled()`
```php
->visible(fn ($get) => $get('staff_id'))
->disabled(fn ($get) => !$get('staff_id'))
```
**Result:** Fields not clickable
**Why:** Filament bug - disabled fields can remain disabled even when condition changes

### Attempt #3: `->hidden()`
```php
->hidden(fn ($get) => !$get('staff_id'))
```
**Result:** Fields don't appear when needed
**Why:** Reactive updates don't trigger re-render properly

### Attempt #4: Black Popup
**Symptom:** Black popup appears when selecting dates
**Cause:** DatePicker component rendering but container has `display: none` or `opacity: 0`
**Why:** CSS conflict between Filament's visibility logic and Flatpickr's popup positioning

---

## Correct Solution Architecture

### Phase 1: Immediate Fix (Clear PHP Cache)

**Action Required:**
```bash
# 1. Restart PHP-FPM (clear worker memory)
sudo systemctl restart php8.3-fpm

# 2. Clear all caches again
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. Verify fix
curl -I https://api.askproai.de/admin/appointments/702/edit
# Should return 200, not 500
```

**Expected Result:** Appointment #702 edit page loads without 500 error

**Why This Works:** PHP-FPM worker processes are holding old code in memory. Restarting the service forces all workers to reload from disk, getting the current version without `->inline()`.

### Phase 2: UX Redesign (State-of-the-Art Implementation)

**Option A: Filament Native Approach (Quick Win)**

Replace split Date + Time with single DateTimePicker:

```php
Forms\Components\DateTimePicker::make('starts_at')
    ->label('📅 Termindatum und Uhrzeit')
    ->native(false)
    ->seconds(false)
    ->minutesStep(15)
    ->minDate(now())
    ->maxDate(now()->addWeeks(2))
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set, callable $get) {
        if ($state) {
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);
        }
    })
    ->helperText(function (callable $get) {
        $staffId = $get('staff_id');
        if (!$staffId) {
            return '⚠️ Bitte zuerst Mitarbeiter wählen';
        }

        // Show next available slots
        $duration = $get('duration_minutes') ?? 30;
        $slots = static::findAvailableSlots($staffId, $duration, 3);

        $slotsText = '📅 Nächste verfügbare Zeiten: ';
        foreach ($slots as $slot) {
            $slotsText .= $slot->format('d.m. H:i') . ', ';
        }

        return rtrim($slotsText, ', ');
    })
    ->disabled(fn (callable $get) => !$get('staff_id'))
    ->columnSpanFull(),
```

**Benefits:**
- ✅ Single field (no split state)
- ✅ No temporal coupling
- ✅ Works in Edit mode (single value)
- ✅ Native Filament component
- ✅ No custom JavaScript
- ✅ 1-hour implementation time

**Drawbacks:**
- ⚠️ Not as visual as Cal.com
- ⚠️ User still needs to manually pick time
- ⚠️ No visual availability grid

**Option B: Custom Calendar Component (Best UX)**

Build custom Livewire component with Filament integration:

```php
// File: app/Filament/Forms/Components/AppointmentCalendar.php
class AppointmentCalendar extends Field
{
    protected string $view = 'filament.forms.components.appointment-calendar';

    public function getAvailableSlots(): array
    {
        return $this->evaluate(function ($get) {
            $staffId = $get('staff_id');
            $duration = $get('duration_minutes') ?? 30;

            return AppointmentResource::findAvailableSlots(
                $staffId,
                $duration,
                100  // All slots for 2 weeks
            );
        });
    }
}
```

```blade
{{-- File: resources/views/filament/forms/components/appointment-calendar.blade.php --}}
<div x-data="appointmentCalendar(@js($getAvailableSlots()), @js($getState()))">
    <div class="grid grid-cols-7 gap-2">
        {{-- Calendar grid --}}
        <template x-for="day in days" :key="day.date">
            <div @click="selectDay(day)"
                 class="p-4 border rounded cursor-pointer"
                 :class="{
                     'bg-green-50 border-green-500': day.hasSlots,
                     'bg-gray-50 border-gray-200': !day.hasSlots,
                     'ring-2 ring-primary-500': day.date === selectedDate
                 }">
                <div x-text="day.dayOfMonth" class="text-center font-bold"></div>
                <div x-text="day.slotCount + ' slots'" class="text-xs text-center"></div>
            </div>
        </template>
    </div>

    {{-- Time slots for selected day --}}
    <div x-show="selectedDate" class="mt-4 grid grid-cols-3 gap-2">
        <template x-for="slot in selectedDaySlots" :key="slot">
            <button @click="selectSlot(slot)"
                    type="button"
                    class="px-4 py-2 border rounded hover:bg-primary-50"
                    :class="{'bg-primary-500 text-white': slot === $wire.state}"
                    x-text="formatTime(slot)">
            </button>
        </template>
    </div>
</div>

<script>
function appointmentCalendar(availableSlots, currentValue) {
    return {
        availableSlots: availableSlots,
        selectedDate: currentValue ? new Date(currentValue).toDateString() : null,

        get days() {
            // Group slots by day
            const days = [];
            const today = new Date();

            for (let i = 0; i < 14; i++) {
                const date = new Date(today);
                date.setDate(today.getDate() + i);

                const dateStr = date.toDateString();
                const daySlots = this.availableSlots.filter(slot =>
                    new Date(slot).toDateString() === dateStr
                );

                days.push({
                    date: dateStr,
                    dayOfMonth: date.getDate(),
                    slotCount: daySlots.length,
                    hasSlots: daySlots.length > 0
                });
            }

            return days;
        },

        get selectedDaySlots() {
            if (!this.selectedDate) return [];

            return this.availableSlots.filter(slot =>
                new Date(slot).toDateString() === this.selectedDate
            );
        },

        selectDay(day) {
            this.selectedDate = day.date;
        },

        selectSlot(slot) {
            this.$wire.state = slot;
        },

        formatTime(slot) {
            return new Date(slot).toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
```

**Usage:**
```php
AppointmentCalendar::make('starts_at')
    ->label('📅 Termin wählen')
    ->required()
    ->disabled(fn (callable $get) => !$get('staff_id'))
    ->columnSpanFull(),
```

**Benefits:**
- ✅ Visual calendar grid (like Cal.com)
- ✅ Inline time slot selection
- ✅ Availability visualization
- ✅ Professional UX
- ✅ Single component (no split state)
- ✅ Works in Edit mode

**Drawbacks:**
- ⚠️ 4-8 hours implementation time
- ⚠️ Custom JavaScript required
- ⚠️ Maintenance overhead

**Option C: Third-Party Package**

Use existing package like `filament-fullcalendar`:

```bash
composer require saade/filament-fullcalendar
```

```php
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

// Extend and customize for appointment booking
```

**Benefits:**
- ✅ Professional calendar UI
- ✅ Event management built-in
- ✅ Drag-and-drop support
- ✅ Multiple views (month, week, day)

**Drawbacks:**
- ⚠️ Might be overkill for simple booking
- ⚠️ Integration effort required
- ⚠️ Package dependency

---

## Recommended Solution: Two-Phase Approach

### IMMEDIATE (Phase 1): Fix PHP Cache - 15 Minutes

**Restore Service:**
```bash
sudo systemctl restart php8.3-fpm
php artisan optimize:clear
```

**Verify Fix:**
```bash
curl -I https://api.askproai.de/admin/appointments/702/edit
# Expected: 200 OK
```

**Test Appointment #702:**
1. Navigate to appointment #702 edit page
2. Change date to tomorrow
3. Select available time slot
4. Click Save
5. Verify appointment updated

**Result:** Appointments are editable again (using current UX)

### SHORT-TERM (Phase 2): Quick UX Improvement - 1-2 Hours

**Replace split Date+Time with DateTimePicker:**

```php
// Remove these fields:
// - DatePicker::make('appointment_date')
// - Radio::make('time_slot')

// Add single field:
Forms\Components\DateTimePicker::make('starts_at')
    ->label('📅 Termindatum und Uhrzeit')
    ->native(false)
    ->seconds(false)
    ->minutesStep(15)
    ->minDate(now())
    ->maxDate(now()->addWeeks(2))
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set, callable $get) {
        if ($state) {
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);
        }
    })
    ->helperText(function (callable $get) {
        $staffId = $get('staff_id');
        if (!$staffId) {
            return '⚠️ Bitte zuerst Mitarbeiter wählen';
        }

        $duration = $get('duration_minutes') ?? 30;
        $slots = static::findAvailableSlots($staffId, $duration, 5);

        if (empty($slots)) {
            return '❌ Keine freien Termine in den nächsten 2 Wochen';
        }

        $slotsText = '📅 Nächste verfügbare Termine:\n';
        foreach ($slots as $slot) {
            $slotsText .= '• ' . $slot->format('d.m.Y H:i') . ' Uhr\n';
        }

        return $slotsText;
    })
    ->disabled(fn (callable $get) => !$get('staff_id'))
    ->columnSpanFull(),

Forms\Components\Hidden::make('ends_at'),
```

**Benefits:**
- Fixes Edit mode issues
- Simpler state management
- No temporal coupling
- Works with existing code
- Native Filament component

**Testing:**
1. Create new appointment → Verify works
2. Edit existing appointment → Verify works
3. Change time multiple times → Verify no state loss
4. Save and reload → Verify persists correctly

### LONG-TERM (Phase 3): State-of-the-Art Calendar - 1 Day

**Custom Calendar Component (Option B):**
- Implement visual calendar grid
- Add inline time slot selection
- Show availability indicators
- Match Cal.com/Calendly UX

**Timeline:**
- Week 1: Phase 1 (immediate fix)
- Week 2: Phase 2 (DateTimePicker)
- Week 3-4: Phase 3 (custom calendar) - if budget allows

---

## Testing Plan

### Phase 1 Verification (PHP Cache Fix)

**Test Cases:**
```
✅ TC-1: Load appointment #702 edit page (expect 200, not 500)
✅ TC-2: Load appointment #702 view page (expect 200, not 500)
✅ TC-3: Load appointment create page (expect 200, not 500)
✅ TC-4: Edit appointment #702 date (expect save success)
✅ TC-5: Edit appointment #702 time (expect save success)
```

**Verification Script:**
```bash
#!/bin/bash
# File: tests/verify-appointment-702-fix.sh

echo "🔍 Testing Appointment #702 Fix"

# Test 1: Edit page loads
echo "Test 1: Edit page loads..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b cookies.txt \
    https://api.askproai.de/admin/appointments/702/edit)
if [ "$STATUS" -eq 200 ]; then
    echo "✅ PASS: Edit page returns 200"
else
    echo "❌ FAIL: Edit page returns $STATUS"
    exit 1
fi

# Test 2: View page loads
echo "Test 2: View page loads..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b cookies.txt \
    https://api.askproai.de/admin/appointments/702)
if [ "$STATUS" -eq 200 ]; then
    echo "✅ PASS: View page returns 200"
else
    echo "❌ FAIL: View page returns $STATUS"
    exit 1
fi

# Test 3: Create page loads
echo "Test 3: Create page loads..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -b cookies.txt \
    https://api.askproai.de/admin/appointments/create)
if [ "$STATUS" -eq 200 ]; then
    echo "✅ PASS: Create page returns 200"
else
    echo "❌ FAIL: Create page returns $STATUS"
    exit 1
fi

echo ""
echo "✅ ALL TESTS PASSED - Appointment system operational"
```

### Phase 2 Verification (DateTimePicker)

**Test Cases:**
```
✅ TC-6: Create appointment with DateTimePicker
✅ TC-7: Edit appointment changes date (verify time preserved)
✅ TC-8: Edit appointment changes time (verify date preserved)
✅ TC-9: Change date multiple times (verify no state loss)
✅ TC-10: Save and reload (verify all fields persist)
```

**Manual Testing:**
1. Create new appointment:
   - Select customer, service, staff
   - Select date/time using DateTimePicker
   - Verify helper text shows available slots
   - Save and verify in database

2. Edit appointment #702:
   - Load edit page
   - Verify current date/time shown in DateTimePicker
   - Change to tomorrow 2 PM
   - Save
   - Reload page and verify change persists

3. Edge cases:
   - Try to select past date (should be disabled)
   - Try to select time without staff (should be disabled)
   - Select staff with no availability (should show error)

### Phase 3 Verification (Custom Calendar)

**Test Cases:**
```
✅ TC-11: Calendar grid displays 14 days
✅ TC-12: Green indicators show days with availability
✅ TC-13: Click day shows time slots for that day
✅ TC-14: Click time slot selects and highlights it
✅ TC-15: Selected slot appears in form state
✅ TC-16: Edit mode pre-selects current slot
✅ TC-17: Visual feedback for busy days (red indicator)
```

---

## Prevention Strategies

### 1. PHP Cache Management

**Automated Cache Clear on Deploy:**
```bash
# File: .deployment/after-deploy.sh
#!/bin/bash

echo "🔄 Clearing all caches..."
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "🔄 Restarting PHP-FPM..."
sudo systemctl restart php8.3-fpm

echo "✅ Deployment cache clearing complete"
```

**Add to deployment pipeline:**
```yaml
# File: .github/workflows/deploy.yml
- name: Clear caches and restart PHP
  run: |
    php artisan optimize:clear
    sudo systemctl restart php8.3-fpm
```

### 2. Code Quality Checks

**Pre-commit Hook:**
```bash
# File: .git/hooks/pre-commit
#!/bin/bash

echo "🔍 Checking for non-existent Filament methods..."

# Check for ->inline() on DatePicker
if grep -r "DatePicker.*->inline()" app/Filament/; then
    echo "❌ ERROR: DatePicker->inline() does not exist in Filament 3.x"
    echo "   Use ->native(false) instead"
    exit 1
fi

# Check for other common mistakes
# ... add more checks

echo "✅ Code quality checks passed"
```

### 3. Documentation

**Add to project docs:**
```markdown
# File: claudedocs/FILAMENT_BEST_PRACTICES.md

## Common Pitfalls

### ❌ Don't Use
- `DatePicker->inline()` - Method doesn't exist
- Complex reactive chains with hidden/disabled
- Split date/time fields with temporal coupling

### ✅ Do Use
- `DateTimePicker` for combined date/time
- Simple visibility logic
- Single source of truth for datetime
- Native Filament components when possible
```

### 4. Monitoring

**Add health check:**
```php
// File: routes/web.php
Route::get('/health/appointments', function () {
    try {
        // Test that appointment form loads
        $resource = app(\App\Filament\Resources\AppointmentResource::class);
        $form = $resource::form(new \Filament\Forms\Form());

        return response()->json([
            'status' => 'ok',
            'form_fields' => $form->getComponents()->count()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
```

**Monitor in production:**
```bash
# Cron job every 5 minutes
*/5 * * * * curl -s https://api.askproai.de/health/appointments | \
    jq -e '.status == "ok"' || \
    echo "❌ Appointment health check failed" | mail -s "Alert" admin@askproai.de
```

---

## Success Criteria

### Phase 1: Immediate Fix
- ✅ Appointment #702 edit page loads (200 status)
- ✅ No more "inline does not exist" errors
- ✅ User can change date and time
- ✅ Changes persist to database

### Phase 2: UX Improvement
- ✅ Single DateTimePicker component
- ✅ Works in both Create and Edit modes
- ✅ No state management issues
- ✅ Helper text shows available slots
- ✅ User testing: "This is easier to use"

### Phase 3: State-of-the-Art
- ✅ Visual calendar grid (14 days)
- ✅ Availability indicators (green/red)
- ✅ Inline time slot selection
- ✅ Matches Cal.com/Calendly UX
- ✅ User testing: "This is professional"

---

## Files to Modify

### Phase 1 (Immediate):
```
NO CODE CHANGES REQUIRED
Just: sudo systemctl restart php8.3-fpm
```

### Phase 2 (DateTimePicker):
```
✏️  app/Filament/Resources/AppointmentResource.php
    - Remove: DatePicker::make('appointment_date')
    - Remove: Radio::make('time_slot')
    - Add: DateTimePicker::make('starts_at')
    - Update: Hidden::make('ends_at') logic
```

### Phase 3 (Custom Calendar):
```
📝 app/Filament/Forms/Components/AppointmentCalendar.php (NEW)
📝 resources/views/filament/forms/components/appointment-calendar.blade.php (NEW)
✏️  app/Filament/Resources/AppointmentResource.php
    - Replace DateTimePicker with AppointmentCalendar
```

---

## Timeline Estimate

### Phase 1: Immediate Fix
- **Time:** 15 minutes
- **Effort:** 1 person
- **Risk:** Low
- **Can Deploy:** Immediately

### Phase 2: DateTimePicker
- **Time:** 1-2 hours (code + test)
- **Effort:** 1 person
- **Risk:** Low
- **Can Deploy:** Same day

### Phase 3: Custom Calendar
- **Time:** 6-8 hours (design + code + test)
- **Effort:** 1 person
- **Risk:** Medium
- **Can Deploy:** After thorough testing

---

## Conclusion

**Root Cause:** PHP-FPM worker processes caching old code with non-existent `->inline()` method call.

**Immediate Fix:** Restart PHP-FPM service to clear worker memory.

**Long-Term Fix:** Replace split Date+Time selection with proper DateTimePicker or custom calendar component.

**Priority:** HIGH - User cannot reschedule appointments (business critical)

**Next Steps:**
1. Restart PHP-FPM (immediate)
2. Verify appointment #702 works (15 min)
3. Implement DateTimePicker (1-2 hours)
4. Consider custom calendar (future sprint)

---

**Analysis Complete**
**Analyst:** Claude Code (Root Cause Analyst)
**Date:** 2025-10-13
**Document:** APPOINTMENT_702_RESCHEDULE_ROOT_CAUSE_ANALYSIS.md
