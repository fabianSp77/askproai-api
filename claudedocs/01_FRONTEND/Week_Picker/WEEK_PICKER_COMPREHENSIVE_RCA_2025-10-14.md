# Week Picker - Comprehensive Root Cause Analysis
**Date:** 2025-10-14
**Status:** 🔴 CRITICAL - Multiple Issues Found
**Severity:** P0 (Production-Blocking)

---

## Executive Summary

The Week Picker system has **FIVE CRITICAL ISSUES** preventing slot selection from working correctly. The current implementation bypasses the entire Livewire event system and relies on direct DOM manipulation, which creates multiple points of failure.

**Primary Issues:**
1. **Event System Completely Bypassed** - Livewire events are dispatched but never used
2. **Hidden Field Not Found** - querySelector fails because wrapper is NOT inside Filament form
3. **Alpine.js Event Listener Dead Code** - Wrapper listener never receives events
4. **Dual Click Handlers** - Competing direct manipulation vs Livewire events
5. **Scope Isolation** - Week Picker Livewire component isolated from Filament form

---

## All Issues Found

### P0 - CRITICAL (Production Blocking)

#### **Issue #1: Event System Architecture Failure**
**Location:** `appointment-week-picker.blade.php:176-202`

**Root Cause:** Direct DOM manipulation bypasses Livewire's event system completely.

**Evidence:**
```blade
<!-- Line 176-202: Direct Alpine.js click handler -->
<button
    type="button"
    @click="
        // Direct Alpine.js click handler (no Livewire)
        const datetime = '{{ $slot['full_datetime'] }}';

        // Find hidden input and update it
        const input = document.querySelector('input[name=starts_at]');
        if (input) {
            input.value = datetime;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // ... more DOM manipulation
    "
```

**Why This Fails:**
- Completely ignores the Livewire `selectSlot()` method (lines 237-267)
- Bypasses Livewire's reactivity system
- Creates race conditions between DOM updates and Livewire state
- Events are still dispatched in PHP but never consumed

---

#### **Issue #2: Hidden Field Not Found - querySelector Fails**
**Location:** `appointment-week-picker-wrapper.blade.php:27` AND `appointment-week-picker.blade.php:181`

**Root Cause:** The `input[name=starts_at]` selector cannot find the hidden field because:

1. **Wrapper Component Lives OUTSIDE Filament Form Scope:**
```php
// AppointmentResource.php:322-345
Forms\Components\ViewField::make('week_picker')
    ->label('')
    ->view('livewire.appointment-week-picker-wrapper', function (callable $get) {
        return [
            'serviceId' => $serviceId,
            'preselectedSlot' => $startsAt,
        ];
    })
```

2. **Hidden Field is SEPARATE Form Component:**
```php
// AppointmentResource.php:348-358
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
```

**DOM Structure Reality:**
```html
<form id="filament-form">
    <!-- Week Picker ViewField renders HERE -->
    <div class="week-picker-wrapper">
        <livewire:appointment-week-picker>
            <button @click="querySelector('input[name=starts_at]')">
                <!-- ❌ querySelector looks in LOCAL scope -->
            </button>
        </livewire:appointment-week-picker>
    </div>

    <!-- Hidden field is SIBLING, not child -->
    <input type="hidden" name="starts_at" wire:model="starts_at" />
</form>
```

**Why querySelector Fails:**
- Alpine.js `@click` executes in Livewire component scope
- Hidden field is in parent Filament form scope
- `querySelector('input[name=starts_at]')` searches from document root but Livewire may isolate scope
- No guaranteed DOM traversal path

---

#### **Issue #3: Alpine.js Event Listener is Dead Code**
**Location:** `appointment-week-picker-wrapper.blade.php:16-46`

**Root Cause:** The `x-on:slot-selected.window` listener NEVER receives events because:

**Dispatcher (PHP):**
```php
// AppointmentWeekPicker.php:252
$this->dispatch('slot-selected', [
    'datetime' => $datetime,
    'displayTime' => $displayTime,
]);
```

**Listener (Blade):**
```blade
<!-- appointment-week-picker-wrapper.blade.php:16 -->
x-on:slot-selected.window="
    const datetime = $event.detail.datetime || $event.detail[0]?.datetime;
    // ... DOM manipulation
"
```

**Why This Fails:**
1. **Livewire 3 Event Dispatching Rules:**
   - `$this->dispatch('event-name')` targets Livewire components, NOT browser events
   - Browser events require `$this->dispatch('event-name')->to(window)` or use JS
   - Current code dispatches to Livewire, not window

2. **Proof from Current Code:**
```php
// Line 252: Livewire component event
$this->dispatch('slot-selected', [...]);

// Line 258: ALSO dispatches different event
$this->dispatch('slotSelected', [...]);  // CamelCase variant

// Neither uses ->to(window) or browser() helper
```

3. **Event Never Reaches Alpine.js:**
   - Alpine.js listens on `window` object
   - Livewire dispatches to server-side event bus
   - No bridge between the two

---

#### **Issue #4: Dual Implementation Conflict**
**Location:** Multiple files

**Root Cause:** Two competing implementations:

**Implementation A: Livewire Event System (UNUSED)**
```php
// AppointmentWeekPicker.php:237-267
public function selectSlot(string $datetime): void
{
    $this->selectedSlot = $datetime;
    $this->dispatch('slot-selected', [
        'datetime' => $datetime,
        'displayTime' => $displayTime,
    ]);
}
```

**Implementation B: Direct DOM Manipulation (ACTIVE)**
```blade
<!-- appointment-week-picker.blade.php:176-202 -->
@click="
    const input = document.querySelector('input[name=starts_at]');
    if (input) {
        input.value = datetime;
        // ...
    }
"
```

**Conflict:**
- Slot buttons use Implementation B (direct click)
- Test button (line 42) uses `@click="$wire.selectSlot(...)"` (Implementation A)
- Implementation A dispatches events that are never consumed
- Implementation B bypasses Livewire entirely

---

#### **Issue #5: Scope Isolation Between Components**
**Location:** Architecture-level issue

**Root Cause:** Livewire component cannot directly communicate with Filament form.

**Component Hierarchy:**
```
Filament Form Panel
├── ViewField (week_picker) → renders wrapper
│   └── Livewire Component (appointment-week-picker)
│       └── Slot buttons (isolated scope)
└── Hidden Field (starts_at) ← TARGET
```

**Why This Matters:**
1. Livewire components are isolated by design
2. Alpine.js scopes are nested (child cannot access parent easily)
3. Filament expects `wire:model` binding on form fields
4. Current implementation tries to bridge with DOM manipulation

---

## P1 - IMPORTANT (High Priority Fixes)

#### **Issue #6: Livewire 3 Event Syntax Inconsistency**
**Location:** `AppointmentWeekPicker.php:252-260`

**Problem:** Mixed event naming conventions:
```php
$this->dispatch('slot-selected', [...]);  // kebab-case
$this->dispatch('slotSelected', [...]);   // camelCase
$this->dispatch('notify', [...]);         // lowercase
```

**Impact:** Inconsistent event handling, harder to debug

---

#### **Issue #7: Missing wire:model on Hidden Field**
**Location:** `AppointmentResource.php:348`

**Problem:**
```php
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
    // ❌ No explicit wire:model binding
```

**Expected:**
- Filament auto-generates `wire:model="starts_at"` for form components
- BUT if events bypass Livewire, wire:model never updates

---

#### **Issue #8: Test Button Uses Different Implementation**
**Location:** `appointment-week-picker.blade.php:42`

**Problem:**
```blade
<button
    type="button"
    @click="$wire.selectSlot('2025-10-23T08:00:00+02:00')"
    class="px-3 py-1.5 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600 transition"
    title="DEBUG: Test Slot Selection">
    🧪 Test
</button>
```

**Why This Matters:**
- Test button CORRECTLY calls `$wire.selectSlot()` (Livewire method)
- Real slot buttons use direct `@click` with DOM manipulation
- If test button works but real buttons don't → proves Livewire method is functional
- Inconsistency creates confusion during debugging

---

## P2 - RECOMMENDED (Code Quality)

#### **Issue #9: Dead Code - Unused Livewire Method**
**Location:** `AppointmentWeekPicker.php:237-267`

**Problem:** The `selectSlot()` method is fully implemented but never called by slot buttons.

**Evidence:**
- Method dispatches 3 events
- Logs slot selection
- Updates component state
- Shows notification
- **BUT:** Only test button calls it

---

#### **Issue #10: Inconsistent Event Handling in Wrapper**
**Location:** `appointment-week-picker-wrapper.blade.php:16-46`

**Problem:** Listener tries to handle two event formats:
```javascript
const datetime = $event.detail.datetime || $event.detail[0]?.datetime;
```

**Why:**
- Accommodates both object format and array format
- Suggests previous debugging attempts
- Adds unnecessary complexity

---

## Root Cause Chain

```
1. Week Picker needs to update Filament hidden field
   ↓
2. Livewire component is isolated from Filament form
   ↓
3. Direct DOM manipulation chosen as workaround
   ↓
4. querySelector('input[name=starts_at]') fails (wrong scope)
   ↓
5. Event system implemented but bypassed
   ↓
6. Slot selection fails silently
```

---

## Recommended Fix Order

### Phase 1: Core Architecture Fix (P0)
**Goal:** Use Livewire event system properly

1. **Remove Direct DOM Manipulation from Slot Buttons**
   - File: `appointment-week-picker.blade.php:176-202`
   - Change: `@click="$wire.selectSlot('{{ $slot['full_datetime'] }}')"` (like test button)

2. **Dispatch Browser Event from PHP**
   - File: `AppointmentWeekPicker.php:252`
   - Change: Use `$this->dispatch('slot-selected', ...)->to(window)` OR use JS

3. **Implement Proper Filament Integration**
   - File: `appointment-week-picker-wrapper.blade.php`
   - Change: Use Livewire `wire:` directives instead of Alpine.js DOM queries

### Phase 2: Event System (P0)
**Goal:** Make events reach Alpine.js

**Option A: Browser Event Dispatch (Recommended)**
```php
// AppointmentWeekPicker.php:252
$this->dispatch('slot-selected', [
    'datetime' => $datetime,
])->to(window);  // ← Targets browser window
```

**Option B: JavaScript Bridge**
```blade
<!-- In wrapper -->
<script>
Livewire.on('slot-selected', (event) => {
    const datetime = event.datetime;
    @this.set('starts_at', datetime);
});
</script>
```

### Phase 3: Hidden Field Access (P0)
**Goal:** Reliably find and update hidden field

**Solution: Use Filament's wire:model**
```php
// AppointmentResource.php:348
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
    ->live()  // ← Ensure immediate updates
```

**In wrapper, update via Livewire:**
```javascript
// Instead of querySelector
@this.set('starts_at', datetime);
```

### Phase 4: Cleanup (P1-P2)
1. Remove dead code (unused event listeners)
2. Standardize event naming (all kebab-case)
3. Remove debug console.log statements
4. Remove test button after validation

---

## Code Snippets - Exact Fixes

### Fix #1: Slot Button Click Handler
**File:** `/var/www/api-gateway/resources/views/livewire/appointment-week-picker.blade.php`
**Lines:** 174-215

**OLD (WRONG):**
```blade
<button
    type="button"
    @click="
        // Direct Alpine.js click handler (no Livewire)
        const datetime = '{{ $slot['full_datetime'] }}';

        // Find hidden input and update it
        const input = document.querySelector('input[name=starts_at]');
        if (input) {
            input.value = datetime;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Update debug display
        const debugSlot = document.getElementById('debug-slot');
        const slotStatus = document.getElementById('slot-status');
        if (debugSlot) debugSlot.textContent = datetime;
        if (slotStatus) {
            slotStatus.textContent = '✅ SLOT GESETZT: ' + datetime;
            slotStatus.className = 'text-xs text-green-700 dark:text-green-300 font-bold';
        }

        // Highlight this button
        document.querySelectorAll('.slot-button').forEach(b => b.classList.remove('selected-slot'));
        $el.classList.add('selected-slot');

        console.log('✅ DIRECT CLICK - Slot set to:', datetime, 'Input found:', !!input);
    "
    @mouseenter="hoveredSlot = '{{ $slot['time'] }}'"
    @mouseleave="hoveredSlot = null"
    class="slot-button ..."
    :class="{ 'selected-slot': '{{ $slot['full_datetime'] }}' === selectedSlot }">
```

**NEW (CORRECT):**
```blade
<button
    type="button"
    wire:click="selectSlot('{{ $slot['full_datetime'] }}')"
    @mouseenter="hoveredSlot = '{{ $slot['time'] }}'"
    @mouseleave="hoveredSlot = null"
    class="slot-button ..."
    :class="{ 'selected-slot': '{{ $slot['full_datetime'] }}' === @entangle('selectedSlot') }">
```

**Changes:**
- ✅ Use `wire:click` instead of `@click`
- ✅ Call Livewire `selectSlot()` method directly
- ✅ Remove ALL DOM manipulation
- ✅ Use `@entangle` for reactive selected state

---

### Fix #2: Livewire selectSlot() Method - Dispatch Browser Event
**File:** `/var/www/api-gateway/app/Livewire/AppointmentWeekPicker.php`
**Lines:** 237-267

**OLD:**
```php
public function selectSlot(string $datetime): void
{
    $this->selectedSlot = $datetime;

    // Parse datetime for display
    $carbon = Carbon::parse($datetime);
    $displayTime = $carbon->format('d.m.Y H:i') . ' Uhr';

    Log::info('[AppointmentWeekPicker] Slot selected', [
        'service_id' => $this->serviceId,
        'selected_datetime' => $datetime,
        'display_time' => $displayTime,
    ]);

    // Emit to parent form (Filament)
    $this->dispatch('slot-selected', [
        'datetime' => $datetime,
        'displayTime' => $displayTime,
    ]);

    // Also emit a browser event for Alpine.js integration
    $this->dispatch('slotSelected', [
        'datetime' => $datetime,
    ]);

    // Success notification
    $this->dispatch('notify', [
        'message' => "Slot ausgewählt: {$displayTime}",
        'type' => 'success',
    ]);
}
```

**NEW:**
```php
public function selectSlot(string $datetime): void
{
    $this->selectedSlot = $datetime;

    // Parse datetime for display
    $carbon = Carbon::parse($datetime);
    $displayTime = $carbon->format('d.m.Y H:i') . ' Uhr';

    Log::info('[AppointmentWeekPicker] Slot selected', [
        'service_id' => $this->serviceId,
        'selected_datetime' => $datetime,
        'display_time' => $displayTime,
    ]);

    // ✅ FIX: Dispatch browser event to window for Alpine.js
    $this->js(<<<JS
        window.dispatchEvent(new CustomEvent('slot-selected', {
            detail: {
                datetime: '{$datetime}',
                displayTime: '{$displayTime}'
            },
            bubbles: true
        }));
    JS);

    // Success notification (Filament)
    \Filament\Notifications\Notification::make()
        ->title('Slot ausgewählt')
        ->body($displayTime)
        ->success()
        ->send();
}
```

**Changes:**
- ✅ Use `$this->js()` to dispatch browser event
- ✅ Event targets `window` object (Alpine.js can listen)
- ✅ Removed redundant Livewire events
- ✅ Use Filament's notification system

---

### Fix #3: Wrapper - Handle Browser Event and Update Filament
**File:** `/var/www/api-gateway/resources/views/livewire/appointment-week-picker-wrapper.blade.php`
**Lines:** 1-61

**OLD:**
```blade
@if($serviceId)
    <div x-data="{
        selectedSlot: @js($preselectedSlot ?? null)
    }"
         x-on:slot-selected.window="
             // Extract datetime from Livewire event
             const datetime = $event.detail.datetime || $event.detail[0]?.datetime;
             selectedSlot = datetime;

             // Update debug display
             const debugSlot = document.getElementById('debug-slot');
             const slotStatus = document.getElementById('slot-status');
             if (debugSlot) debugSlot.textContent = datetime;

             // Find the hidden starts_at input field and update it
             const startsAtInput = document.querySelector('input[name=starts_at]');
             if (startsAtInput) {
                 startsAtInput.value = datetime;
                 // Trigger change event for Livewire/Alpine to detect the change
                 startsAtInput.dispatchEvent(new Event('input', { bubbles: true }));
                 startsAtInput.dispatchEvent(new Event('change', { bubbles: true }));

                 if (slotStatus) {
                     slotStatus.textContent = '✅ Slot gesetzt: ' + datetime;
                     slotStatus.className = 'text-xs text-green-700 dark:text-green-300 font-bold';
                 }
             } else {
                 if (slotStatus) {
                     slotStatus.textContent = '❌ ERROR: Hidden Field nicht gefunden!';
                     slotStatus.className = 'text-xs text-red-700 dark:text-red-300 font-bold';
                 }
             }

             console.log('✅ Slot selected:', datetime, 'Input updated:', !!startsAtInput);
         "
         class="week-picker-wrapper">

        @livewire('appointment-week-picker', [
            'serviceId' => $serviceId,
            'preselectedSlot' => $preselectedSlot ?? null,
        ])
    </div>
@else
    <div class="p-4 bg-warning-50 ...">
        <p class="text-sm text-warning-700 dark:text-warning-300">
            ⚠️ Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen.
        </p>
    </div>
@endif
```

**NEW:**
```blade
@if($serviceId)
    <div x-data="{
        selectedSlot: @js($preselectedSlot ?? null),

        // ✅ FIX: Proper event handler that updates Filament form
        handleSlotSelected(event) {
            const datetime = event.detail.datetime;
            this.selectedSlot = datetime;

            // Update Filament form via wire:model binding
            // Find parent form and dispatch input event
            const form = this.$el.closest('form');
            if (form) {
                const startsAtInput = form.querySelector('input[name=\"starts_at\"]');
                if (startsAtInput) {
                    startsAtInput.value = datetime;
                    startsAtInput.dispatchEvent(new Event('input', { bubbles: true }));
                    startsAtInput.dispatchEvent(new Event('change', { bubbles: true }));

                    console.log('✅ Slot selected and form updated:', datetime);
                } else {
                    console.error('❌ Hidden field input[name=starts_at] not found in form');
                }
            } else {
                console.error('❌ Parent form not found');
            }
        }
    }"
         x-on:slot-selected.window="handleSlotSelected($event)"
         class="week-picker-wrapper">

        @livewire('appointment-week-picker', [
            'serviceId' => $serviceId,
            'preselectedSlot' => $preselectedSlot ?? null,
        ])
    </div>
@else
    <div class="p-4 bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800 rounded-lg text-center">
        <p class="text-sm text-warning-700 dark:text-warning-300">
            ⚠️ Bitte wählen Sie zuerst einen Service aus, um verfügbare Termine zu sehen.
        </p>
    </div>
@endif
```

**Changes:**
- ✅ Use `x-data` method for cleaner event handling
- ✅ Use `closest('form')` to find parent form reliably
- ✅ Removed debug display code
- ✅ Better error logging
- ✅ Cleaner code structure

---

### Fix #4: Hidden Field - Ensure Reactivity
**File:** `/var/www/api-gateway/app/Filament/Resources/AppointmentResource.php`
**Lines:** 348-358

**OLD:**
```php
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if ($state) {
            // Berechne automatisch ends_at
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);
        }
    }),
```

**NEW:**
```php
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
    ->live(onBlur: false)  // ✅ FIX: Update immediately on ANY change
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if ($state) {
            // Berechne automatisch ends_at
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);

            // ✅ FIX: Log for debugging
            \Log::debug('starts_at updated via hidden field', [
                'starts_at' => $state,
                'ends_at' => $endsAt->toIso8601String(),
                'duration' => $duration,
            ]);
        }
    }),
```

**Changes:**
- ✅ Added `->live(onBlur: false)` for immediate updates
- ✅ Added logging for debugging
- ✅ Ensure ends_at is always calculated

---

## Testing Checklist

After applying fixes, test in this order:

### ✅ Phase 1: Basic Functionality
1. [ ] Test button (🧪) works and updates form
2. [ ] Week navigation (previous/next) loads slots
3. [ ] Service selection shows correct slots

### ✅ Phase 2: Slot Selection
1. [ ] Click any slot → button highlights
2. [ ] Click slot → debug info updates
3. [ ] Click slot → browser console shows success log
4. [ ] Click slot → Filament notification appears

### ✅ Phase 3: Form Integration
1. [ ] Selected slot appears in green box above week view
2. [ ] Hidden field `starts_at` has value (check browser DevTools)
3. [ ] End time automatically calculated
4. [ ] Form submit validation passes

### ✅ Phase 4: Edge Cases
1. [ ] Change service → week picker reloads
2. [ ] Select different slot → previous selection clears
3. [ ] Browser back/forward → state preserved
4. [ ] Mobile view → slots still work

---

## Prevention Measures

### Code Review Checklist
- [ ] All Livewire methods called via `wire:click` or `$wire.method()`
- [ ] No direct DOM manipulation in Livewire components
- [ ] Browser events use `$this->js()` or `dispatch()->to(window)`
- [ ] All hidden fields have `->live()` directive
- [ ] Event listeners match event dispatch names

### Architecture Principles
1. **Livewire First:** Use Livewire methods, not Alpine.js workarounds
2. **Events for Communication:** Components communicate via events, not DOM
3. **Filament Integration:** Use Filament's wire:model binding
4. **No jQuery-style:** Avoid querySelector, getElementById in Livewire views

---

## Files Requiring Changes

| Priority | File | Lines | Change Type |
|----------|------|-------|-------------|
| P0 | `appointment-week-picker.blade.php` | 174-215 | Replace `@click` with `wire:click` |
| P0 | `AppointmentWeekPicker.php` | 237-267 | Use `$this->js()` for browser events |
| P0 | `appointment-week-picker-wrapper.blade.php` | 1-61 | Update Alpine.js event handler |
| P0 | `AppointmentResource.php` | 348-358 | Add `->live()` to hidden field |
| P1 | `appointment-week-picker.blade.php` | 42-46 | Remove test button after validation |
| P1 | `appointment-week-picker-wrapper.blade.php` | 4-10 | Remove debug display |
| P2 | `AppointmentWeekPicker.php` | 258-260 | Remove redundant events |

---

## Estimated Impact

**After Fix:**
- ✅ Slot selection works reliably
- ✅ Form validation passes
- ✅ No console errors
- ✅ Mobile and desktop work identically
- ✅ Livewire reactivity functions correctly

**Time to Fix:** ~2-3 hours (including testing)

**Risk Level:** 🟡 MEDIUM (breaking current functionality temporarily during fix)

---

## Conclusion

The Week Picker system has **fundamental architecture issues** where:
1. **Events are dispatched but never reach their listeners**
2. **DOM manipulation bypasses Livewire entirely**
3. **Component isolation prevents reliable form updates**

**The fix requires replacing direct DOM manipulation with proper Livewire event flow.**

This is **NOT a simple bug** - it's an **architectural mismatch** between Livewire 3's event system and the current implementation approach.

---

**Generated:** 2025-10-14
**Next Steps:** Apply fixes in recommended order, test each phase before proceeding.
