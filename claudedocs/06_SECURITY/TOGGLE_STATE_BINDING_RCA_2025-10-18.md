# Toggle "state is not defined" - ROOT CAUSE & FIX

**Date**: 2025-10-18
**Status**: ROOT CAUSE IDENTIFIED
**Severity**: HIGH - Blocks form interactions
**Affected**: AppointmentResource send_reminder & send_confirmation Toggles

---

## Executive Summary

Toggle components inside Grid containers with `wire:key='reminder-settings-grid'` fail with:
```
ReferenceError: state is not defined
```

**Root Cause**: `wire:key` on Grid creates a Livewire component boundary that breaks Alpine.js `$wire` context for child fields.

**Solution**: Remove `wire:key` from Grid containers. Use `wire:key` only on:
- Individual form fields (when needed for unique identification)
- Dynamic containers (Repeater, Builder items)
- Livewire components (not layout containers)

---

## The Problem

### Symptoms
1. Toggle renders successfully in DOM
2. Alpine.js x-data initialization fails
3. Error: "ReferenceError: state is not defined" in browser console
4. Toggle button unclickable (x-data not initialized)
5. Button appears but has no interactive behavior

### Affected Code
**File**: `/app/Filament/Resources/AppointmentResource.php` (lines 571-584)

```php
Grid::make(2)
    ->schema([
        Forms\Components\Toggle::make('send_reminder')
            ->label('Erinnerung senden')
            ->default(true)
            ->reactive()
            ->helperText('24 Stunden vor dem Termin'),

        Forms\Components\Toggle::make('send_confirmation')
            ->label('Bestätigung senden')
            ->default(true)
            ->helperText('Sofort nach der Buchung'),
    ])
    ->extraAttributes(['wire:key' => 'reminder-settings-grid']),  // ← THE PROBLEM
```

### Rendered HTML (with wire:key)
```html
<div id="..." wire:key="reminder-settings-grid">
    <button x-data="{ state: $wire.$entangle('data.send_reminder', true) }">
        <!-- Toggle content -->
    </button>
    <button x-data="{ state: $wire.$entangle('data.send_confirmation', true) }">
        <!-- Toggle content -->
    </button>
</div>
```

---

## Root Cause Analysis

### How Toggle Works
**File**: `/vendor/filament/forms/resources/views/components/toggle.blade.php` (line 14-16)

```blade
<button
    x-data="{
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
    }"
    ...
```

The Toggle's Alpine component expects `$wire` to be available in its x-data scope.

### Wire:key Behavior in Livewire 3

From `/vendor/livewire/livewire/dist/livewire.js`:
```javascript
el2.hasAttribute(`wire:key`) ? el2.getAttribute(`wire:key`) : el2.hasAttribute(`wire:id`) ? el2.getAttribute(`wire:id`) : el2.id
```

When Livewire encounters `wire:key` on an element:
1. It treats that element as a **component lifecycle boundary**
2. Livewire marks it for diffing/tracking during morphs
3. Livewire creates an isolated context for that container
4. Child elements' Alpine x-data contexts may not have access to parent `$wire`

### Why `$wire` Becomes Unavailable

In Livewire 3's Alpine integration:
1. `$wire` is injected into Alpine's global scope by the Livewire component
2. When `wire:key` creates a boundary, it can isolate child contexts
3. Alpine's x-data inside the keyed container tries to access `$wire`
4. But `$wire` is NOT automatically injected into the isolated scope
5. Result: "state is not defined" (because `$wire` is undefined, and accessing `.state` on undefined fails)

### Official Filament Pattern

Searching Filament's own source code shows `wire:key` is used on:
- **Dynamic items**: Repeater and Builder individual items
- **Specific fields**: With unique keys like `{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}`
- **NOT on static layout containers**: Grid, Section, etc. are never keyed

Example from `/vendor/filament/forms/resources/views/components/builder.blade.php`:
```html
wire:key="{{ $this->getId() }}.{{ $item->getStatePath() }}.{{ $field::class }}.item"
```

Example from `/vendor/filament/forms/resources/views/components/select.blade.php`:
```html
wire:key="{{ $this->getId() }}.{{ $statePath }}.{{ $field::class }}.{{ ... }}"
```

**Pattern**: `wire:key` is used for FIELDS with unique identifiers, not CONTAINERS.

### Why It Works WITHOUT wire:key

**Rendered HTML (without wire:key)**:
```html
<div id="...">  <!-- No wire:key, no component boundary -->
    <button x-data="{ state: $wire.$entangle('data.send_reminder', true) }">
        <!-- Toggle content -->
    </button>
</div>
```

Without the boundary:
1. Alpine's x-data inside the button still has access to parent scope
2. `$wire` is inherited from the parent Livewire component
3. State binding works correctly ✓

---

## Evidence

### Code References

1. **Toggle Template** - expects `$wire` in x-data:
   - Path: `/vendor/filament/forms/resources/views/components/toggle.blade.php:14-16`

2. **Grid Template** - passes through extraAttributes:
   - Path: `/vendor/filament/forms/resources/views/components/grid.blade.php:7`
   - Accepts and renders any extra attributes including `wire:key`

3. **Livewire wire:key Processing**:
   - Path: `/vendor/livewire/livewire/dist/livewire.js` (minified)
   - Creates component boundaries for lifecycle management

4. **Official Usage Pattern**:
   - Path: `/vendor/filament/forms/resources/views/components/builder.blade.php` (wire:key on items)
   - Path: `/vendor/filament/forms/resources/views/components/repeater/index.blade.php` (wire:key on items)
   - Path: `/vendor/filament/forms/resources/views/components/select.blade.php` (wire:key on field)

### Error Chain
1. Blade renders Toggle button with `x-data="{ state: $wire.$entangle(...) }"`
2. Alpine initialization runs
3. Alpine tries to access `$wire` variable
4. `$wire` is undefined (because wire:key boundary isolates scope)
5. Error: "ReferenceError: state is not defined"
6. Toggle's x-data never initializes
7. Button remains unclickable

---

## Comparison: Working vs Broken

### Working: NotificationTemplateResource
```php
Forms\Components\Toggle::make('is_active')
    ->label('Aktiv')
    ->default(true),
```
- No Grid container
- No wire:key
- Works perfectly ✓

### Broken: AppointmentResource  
```php
Grid::make(2)
    ->schema([
        Forms\Components\Toggle::make('send_reminder')->reactive(),
        Forms\Components\Toggle::make('send_confirmation'),
    ])
    ->extraAttributes(['wire:key' => 'reminder-settings-grid']),
```
- Inside Grid container with wire:key
- Fails with "state is not defined" ✗

---

## Solution

### Fix: Remove wire:key from Grid

**File**: `/app/Filament/Resources/AppointmentResource.php` (line 584)

**Before**:
```php
Grid::make(2)
    ->schema([
        Forms\Components\Toggle::make('send_reminder')
            ->label('Erinnerung senden')
            ->default(true)
            ->reactive()
            ->helperText('24 Stunden vor dem Termin'),

        Forms\Components\Toggle::make('send_confirmation')
            ->label('Bestätigung senden')
            ->default(true)
            ->helperText('Sofort nach der Buchung'),
    ])
    ->extraAttributes(['wire:key' => 'reminder-settings-grid']),  // ← REMOVE THIS
```

**After**:
```php
Grid::make(2)
    ->schema([
        Forms\Components\Toggle::make('send_reminder')
            ->label('Erinnerung senden')
            ->default(true)
            ->reactive()
            ->helperText('24 Stunden vor dem Termin'),

        Forms\Components\Toggle::make('send_confirmation')
            ->label('Bestätigung senden')
            ->default(true)
            ->helperText('Sofort nach der Buchung'),
    ]),  // ← Removed wire:key and extraAttributes
```

### Why This Works
1. Grid no longer has wire:key
2. No component boundary created
3. Toggle's Alpine x-data can access `$wire` from parent scope
4. State binding initializes correctly
5. Toggle becomes interactive

### Apply Same Fix to Other Grids

The same issue may affect other Grids with `wire:key`. Search and remove:

```php
->extraAttributes(['wire:key' => '...'])
```

From Grid containers:
- Line 588: `'wire:key' => 'package-sessions-grid'`
- Line 460: `'wire:key' => 'service-staff-grid'`
- Line 485: `'wire:key' => 'manual-datetime-grid'`
- Line 510: `'wire:key' => 'duration-end-grid'`
- Line 545: `'wire:key' => 'booking-source-grid'`

---

## When wire:key IS Appropriate

DO use `wire:key` on:
1. **Repeater items** - for dynamic list management
2. **Builder block items** - for dynamic block tracking
3. **Individual fields** - when unique identification is critical
4. **Livewire components** - components with wire:id

DO NOT use `wire:key` on:
1. **Grid containers** - static layout, not dynamic
2. **Section containers** - static layout
3. **Div containers** - layout only
4. **Fieldset containers** - layout only

---

## Prevention

### Guidelines for wire:key Usage

1. **Check Official Filament Patterns**
   - Look at vendor code for how wire:key is used
   - Only copy patterns that are explicitly there
   - Never add wire:key to layout containers

2. **Test Alpine x-data Access**
   - If field has `x-data` with `$wire`, don't use wire:key on parent
   - Test that `$wire` is accessible

3. **Understand Component Boundaries**
   - wire:key creates boundaries for Livewire's lifecycle tracking
   - Not meant for static layout containers
   - Reserved for dynamic content management

---

## Testing

### Manual Verification
1. Navigate to Appointment create/edit page
2. Look for send_reminder and send_confirmation Toggles
3. Click on toggles
4. Verify they switch states (on/off)
5. No console errors
6. Form can be submitted

### JavaScript Console Check
```javascript
// In browser console on appointment page
// Before fix: ReferenceError in console when page loads
// After fix: No errors, page loads cleanly
```

### Form Submission Test
1. Create new appointment
2. Toggle send_reminder ON/OFF
3. Toggle send_confirmation ON/OFF
4. Click Save
5. Verify form submitted successfully
6. Verify toggle states persisted in database

---

## Commits Required

1. **AppointmentResource.php** - Remove wire:key from all Grid containers
2. **Test** - Verify toggle functionality works
3. **Document** - This RCA file

---

## Related Documentation

- LIVEWIRE_INDEX.md - Livewire 3 documentation hub
- LOGIN_405_FIX_FINAL_2025-10-17.md - Previous Livewire initialization fix

---

## Conclusion

**Root Cause**: Using `wire:key` on Grid containers creates component boundaries that isolate Alpine.js scope and prevent `$wire` access.

**Fix**: Remove `wire:key` from Grid containers. Use `wire:key` only for dynamic components and individual fields that specifically need lifecycle management.

**Prevention**: Follow Filament's official patterns - use `wire:key` only where Filament itself uses it (dynamic items, specific fields).

**Status**: READY TO IMPLEMENT ✓

