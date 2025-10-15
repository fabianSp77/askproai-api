# Week Picker - Quick Fix Guide
**For Immediate Implementation**
**Date:** 2025-10-14

---

## TL;DR - What's Wrong?

**Problem:** Slot buttons use direct DOM manipulation instead of Livewire, so they can't find the hidden field.

**Solution:** Change slot buttons to use `wire:click` and dispatch browser events properly.

**Time:** 30 minutes

---

## Quick Fix (Copy-Paste Ready)

### Fix #1: Slot Buttons (Desktop)
**File:** `resources/views/livewire/appointment-week-picker.blade.php`
**Line:** 174

**FIND:**
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
    class="slot-button w-full px-2 py-1.5 text-xs text-center rounded-md transition-all duration-150 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:border-primary-400 dark:hover:border-primary-600 hover:scale-105 border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400"
    title="{{ $this->getFullDayName($day) }}, {{ $slot['date'] }} - {{ $slot['time'] }} Uhr">
```

**REPLACE WITH:**
```blade
<button
    type="button"
    wire:click="selectSlot('{{ $slot['full_datetime'] }}')"
    @mouseenter="hoveredSlot = '{{ $slot['time'] }}'"
    @mouseleave="hoveredSlot = null"
    class="slot-button w-full px-2 py-1.5 text-xs text-center rounded-md transition-all duration-150 border bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:border-primary-400 dark:hover:border-primary-600 hover:scale-105 border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400"
    :class="{ 'selected-slot': '{{ $slot['full_datetime'] }}' === @entangle('selectedSlot') }"
    title="{{ $this->getFullDayName($day) }}, {{ $slot['date'] }} - {{ $slot['time'] }} Uhr">
```

**Changes:**
- Removed entire `@click` handler
- Added `wire:click="selectSlot(...)"`
- Added `:class` binding for selected state

---

### Fix #2: Slot Buttons (Mobile)
**File:** `resources/views/livewire/appointment-week-picker.blade.php`
**Line:** 267

**FIND:**
```blade
<button
    type="button"
    @click="
        const datetime = '{{ $slot['full_datetime'] }}';
        const input = document.querySelector('input[name=starts_at]');
        if (input) {
            input.value = datetime;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const debugSlot = document.getElementById('debug-slot');
        const slotStatus = document.getElementById('slot-status');
        if (debugSlot) debugSlot.textContent = datetime;
        if (slotStatus) {
            slotStatus.textContent = '✅ SLOT GESETZT: ' + datetime;
            slotStatus.className = 'text-xs text-green-700 dark:text-green-300 font-bold';
        }
        document.querySelectorAll('.slot-button').forEach(b => b.classList.remove('selected-slot'));
        $el.classList.add('selected-slot');
        console.log('✅ MOBILE CLICK - Slot set to:', datetime);
    "
    class="slot-button w-full px-4 py-3 text-sm text-left rounded-lg transition-all border bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:border-primary-400 dark:hover:border-primary-600 border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400">
```

**REPLACE WITH:**
```blade
<button
    type="button"
    wire:click="selectSlot('{{ $slot['full_datetime'] }}')"
    class="slot-button w-full px-4 py-3 text-sm text-left rounded-lg transition-all border bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:border-primary-400 dark:hover:border-primary-600 border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400"
    :class="{ 'selected-slot': '{{ $slot['full_datetime'] }}' === @entangle('selectedSlot') }">
```

---

### Fix #3: Livewire selectSlot() Method
**File:** `app/Livewire/AppointmentWeekPicker.php`
**Line:** 237

**FIND:**
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

**REPLACE WITH:**
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

    // Dispatch browser event to Alpine.js wrapper
    $this->js(<<<JS
        window.dispatchEvent(new CustomEvent('slot-selected', {
            detail: {
                datetime: '{$datetime}',
                displayTime: '{$displayTime}'
            },
            bubbles: true
        }));
    JS);

    // Success notification
    \Filament\Notifications\Notification::make()
        ->title('Slot ausgewählt')
        ->body($displayTime)
        ->success()
        ->send();
}
```

**Changes:**
- Removed Livewire `dispatch()` calls (don't work with Alpine.js)
- Added `$this->js()` to dispatch browser event
- Use Filament's Notification system

---

### Fix #4: Wrapper Event Handler
**File:** `resources/views/livewire/appointment-week-picker-wrapper.blade.php`
**Line:** 12

**FIND:**
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
```

**REPLACE WITH:**
```blade
@if($serviceId)
    <div x-data="{
        selectedSlot: @js($preselectedSlot ?? null),

        handleSlotSelected(event) {
            const datetime = event.detail.datetime;
            this.selectedSlot = datetime;

            // Find parent form reliably
            const form = this.$el.closest('form');
            if (form) {
                const startsAtInput = form.querySelector('input[name=\"starts_at\"]');
                if (startsAtInput) {
                    startsAtInput.value = datetime;
                    startsAtInput.dispatchEvent(new Event('input', { bubbles: true }));
                    startsAtInput.dispatchEvent(new Event('change', { bubbles: true }));

                    console.log('✅ Slot selected and form updated:', datetime);
                } else {
                    console.error('❌ Hidden field not found in form');
                }
            } else {
                console.error('❌ Parent form not found');
            }
        }
    }"
         x-on:slot-selected.window="handleSlotSelected($event)"
         class="week-picker-wrapper">
```

**Changes:**
- Moved logic to `handleSlotSelected()` method
- Use `$el.closest('form')` to find parent form reliably
- Simplified error handling
- Removed debug display code

---

### Fix #5: Hidden Field Reactivity
**File:** `app/Filament/Resources/AppointmentResource.php`
**Line:** 348

**FIND:**
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

**REPLACE WITH:**
```php
Forms\Components\Hidden::make('starts_at')
    ->required()
    ->reactive()
    ->live(onBlur: false)
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        if ($state) {
            // Berechne automatisch ends_at
            $duration = $get('duration_minutes') ?? 30;
            $endsAt = Carbon::parse($state)->addMinutes($duration);
            $set('ends_at', $endsAt);

            \Log::debug('[Week Picker] starts_at updated', [
                'starts_at' => $state,
                'ends_at' => $endsAt->toIso8601String(),
            ]);
        }
    }),
```

**Changes:**
- Added `->live(onBlur: false)` for immediate updates
- Added logging for debugging

---

## Testing After Fixes

### 1. Test Button First
```bash
# Open browser DevTools Console
# Click 🧪 Test button
# Should see:
# ✅ Slot selected and form updated: 2025-10-23T08:00:00+02:00
```

### 2. Test Real Slot
```bash
# Click any slot button
# Should see:
# ✅ Slot selected and form updated: [datetime]
# Green notification: "Slot ausgewählt: [time]"
```

### 3. Verify Hidden Field
```bash
# In DevTools Elements tab, find:
# <input type="hidden" name="starts_at" value="2025-10-23T08:00:00+02:00">
# Value should update when clicking slots
```

### 4. Submit Form
```bash
# Click "Speichern" button
# Should NOT see validation error for starts_at
# Form should submit successfully
```

---

## Rollback If Needed

If fixes break something, revert files:

```bash
cd /var/www/api-gateway

# Revert blade files
git checkout HEAD -- resources/views/livewire/appointment-week-picker.blade.php
git checkout HEAD -- resources/views/livewire/appointment-week-picker-wrapper.blade.php

# Revert PHP files
git checkout HEAD -- app/Livewire/AppointmentWeekPicker.php
git checkout HEAD -- app/Filament/Resources/AppointmentResource.php
```

---

## Common Issues After Fix

### Issue: "Method selectSlot not found"
**Cause:** Cache not cleared
**Solution:**
```bash
php artisan livewire:clear
php artisan view:clear
```

### Issue: "Event not firing"
**Cause:** Browser cache
**Solution:** Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

### Issue: "Hidden field still not found"
**Cause:** Form structure different than expected
**Solution:** Check browser console for error messages, verify form DOM structure

---

## Files Changed Summary

| File | Lines | Change |
|------|-------|--------|
| `appointment-week-picker.blade.php` | 174-215 | Slot button: `@click` → `wire:click` |
| `appointment-week-picker.blade.php` | 267-306 | Mobile slot: `@click` → `wire:click` |
| `AppointmentWeekPicker.php` | 237-267 | Use `$this->js()` for browser event |
| `appointment-week-picker-wrapper.blade.php` | 12-47 | Use `closest('form')` + method |
| `AppointmentResource.php` | 348-358 | Add `->live()` directive |

---

## Next Steps After Successful Fix

1. Remove test button (line 40-46 in appointment-week-picker.blade.php)
2. Remove debug display (lines 4-10 in wrapper)
3. Remove all `console.log()` statements
4. Add to git commit

---

**Estimated Time:** 30 minutes
**Risk Level:** 🟡 Low (changes are localized, easily reversible)
**Priority:** 🔴 P0 (blocking production use)

---

**Generated:** 2025-10-14
**Full Analysis:** WEEK_PICKER_COMPREHENSIVE_RCA_2025-10-14.md
