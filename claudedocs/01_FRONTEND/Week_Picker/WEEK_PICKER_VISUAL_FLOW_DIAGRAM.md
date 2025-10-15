# Week Picker - Visual Flow Diagrams
**Date:** 2025-10-14

---

## Current (BROKEN) Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    Filament Form (Parent)                       │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  ViewField: week_picker                                   │ │
│  │  ┌─────────────────────────────────────────────────────┐  │ │
│  │  │  Wrapper (Alpine.js)                                │  │ │
│  │  │  x-on:slot-selected.window="..."                    │  │ │
│  │  │  │                                                   │  │ │
│  │  │  │  ┌──────────────────────────────────────────┐    │  │ │
│  │  │  │  │  Livewire: appointment-week-picker       │    │  │ │
│  │  │  │  │                                          │    │  │ │
│  │  │  │  │  ┌────────────────────────────────┐     │    │  │ │
│  │  │  │  │  │  Slot Button                   │     │    │  │ │
│  │  │  │  │  │  @click="                      │     │    │  │ │
│  │  │  │  │  │    querySelector(...)  ❌      │     │    │  │ │
│  │  │  │  │  │    input.value = datetime      │     │    │  │ │
│  │  │  │  │  │  "                              │     │    │  │ │
│  │  │  │  │  └────────────────────────────────┘     │    │  │ │
│  │  │  │  │                │                         │    │  │ │
│  │  │  │  │                │                         │    │  │ │
│  │  │  │  │  selectSlot() method EXISTS           │    │  │ │
│  │  │  │  │  but NEVER CALLED  ❌                   │    │  │ │
│  │  │  │  │                │                         │    │  │ │
│  │  │  │  │                ↓                         │    │  │ │
│  │  │  │  │  $this->dispatch('slot-selected')  ❌   │    │  │ │
│  │  │  │  │  (Livewire event, not browser event)    │    │  │ │
│  │  │  │  └──────────────────────────────────────────┘    │  │ │
│  │  │  │                                                   │  │ │
│  │  │  │  Event NEVER reaches wrapper listener  ❌        │  │ │
│  │  │  └─────────────────────────────────────────────────┘  │ │
│  │                                                           │ │
│  │  querySelector FAILS: Hidden field not in scope  ❌      │ │
│  └───────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌─────────────────────────────────────────┐                   │
│  │  Hidden Field: starts_at                │                   │
│  │  <input name="starts_at" wire:model />  │                   │
│  │  NEVER UPDATED  ❌                       │                   │
│  └─────────────────────────────────────────┘                   │
│                                                                 │
│  Form Validation FAILS  ❌                                      │
└─────────────────────────────────────────────────────────────────┘
```

**Why It Fails:**
1. ❌ Slot button uses `@click` with direct DOM manipulation
2. ❌ `querySelector('input[name=starts_at]')` searches wrong scope
3. ❌ Livewire `selectSlot()` method never called
4. ❌ Livewire event dispatched but never reaches Alpine.js
5. ❌ Hidden field never updated
6. ❌ Form validation fails on submit

---

## Fixed (WORKING) Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    Filament Form (Parent)                       │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  ViewField: week_picker                                   │ │
│  │  ┌─────────────────────────────────────────────────────┐  │ │
│  │  │  Wrapper (Alpine.js)                                │  │ │
│  │  │  x-on:slot-selected.window="handleSlotSelected()"   │  │ │
│  │  │                                                      │  │ │
│  │  │  ┌──────────────────────────────────────────┐       │  │ │
│  │  │  │  Livewire: appointment-week-picker       │       │  │ │
│  │  │  │                                          │       │  │ │
│  │  │  │  ┌────────────────────────────────┐     │       │  │ │
│  │  │  │  │  Slot Button                   │     │       │  │ │
│  │  │  │  │  wire:click="selectSlot(...)"  ✅   │       │  │ │
│  │  │  │  └────────────────────────────────┘     │       │  │ │
│  │  │  │                │                         │       │  │ │
│  │  │  │                ↓                         │       │  │ │
│  │  │  │  ┌────────────────────────────────┐     │       │  │ │
│  │  │  │  │  selectSlot(datetime)          │     │       │  │ │
│  │  │  │  │  {                              │     │       │  │ │
│  │  │  │  │    this.selectedSlot = datetime│     │       │  │ │
│  │  │  │  │                                 │     │       │  │ │
│  │  │  │  │    $this->js("                 │     │       │  │ │
│  │  │  │  │      window.dispatchEvent(     │     │       │  │ │
│  │  │  │  │        new CustomEvent(...)    │     │       │  │ │
│  │  │  │  │    ")                           │     │       │  │ │
│  │  │  │  │  }                              │     │       │  │ │
│  │  │  │  └────────────────────────────────┘     │       │  │ │
│  │  │  │                │                         │       │  │ │
│  │  │  │                ↓                         │       │  │ │
│  │  │  └────────────────┼─────────────────────────┘       │  │ │
│  │  │                   │                                 │  │ │
│  │  │                   ↓  Browser Event  ✅             │  │ │
│  │  │  ┌────────────────────────────────────────┐        │  │ │
│  │  │  │  handleSlotSelected(event)             │        │  │ │
│  │  │  │  {                                      │        │  │ │
│  │  │  │    const form = $el.closest('form')  ✅│        │  │ │
│  │  │  │    const input = form.querySelector(   │        │  │ │
│  │  │  │      'input[name=starts_at]'           │        │  │ │
│  │  │  │    )                                    │        │  │ │
│  │  │  │    input.value = datetime               │        │  │ │
│  │  │  │    input.dispatchEvent(                 │        │  │ │
│  │  │  │      new Event('input')                 │        │  │ │
│  │  │  │    )                                    │        │  │ │
│  │  │  │  }                                      │        │  │ │
│  │  │  └────────────────────────────────────────┘        │  │ │
│  │  │                   │                                 │  │ │
│  │  │                   ↓                                 │  │ │
│  │  └───────────────────┼─────────────────────────────────┘  │ │
│  │                      │                                    │ │
│  │                      ↓                                    │ │
│  │  ┌──────────────────────────────────────────┐            │ │
│  │  │  Hidden Field: starts_at                 │            │ │
│  │  │  <input name="starts_at" wire:model />   │            │ │
│  │  │  VALUE UPDATED  ✅                        │            │ │
│  │  │                                           │            │ │
│  │  │  ->afterStateUpdated() TRIGGERS  ✅      │            │ │
│  │  │  ends_at calculated  ✅                   │            │ │
│  │  └──────────────────────────────────────────┘            │ │
│                                                               │ │
│  Form Validation PASSES  ✅                                   │ │
└─────────────────────────────────────────────────────────────────┘
```

**Why It Works:**
1. ✅ Slot button uses `wire:click` to call Livewire method
2. ✅ `selectSlot()` dispatches browser event via `$this->js()`
3. ✅ Alpine.js wrapper receives event on `window`
4. ✅ Wrapper uses `closest('form')` to find parent form reliably
5. ✅ querySelector finds hidden field in correct scope
6. ✅ Hidden field updated, triggers Filament reactivity
7. ✅ Form validation passes

---

## Event Flow Comparison

### BROKEN: Livewire Event (Current)

```
PHP: AppointmentWeekPicker.php
┌────────────────────────────────┐
│ $this->dispatch('slot-selected',│
│   ['datetime' => $datetime]    │
└────────────────┬───────────────┘
                 │
                 ↓
       Livewire Event Bus
       (Server-side only)
                 │
                 ↓
            NOWHERE  ❌
     (Event never reaches browser)


Alpine.js: appointment-week-picker-wrapper.blade.php
┌──────────────────────────────────┐
│ x-on:slot-selected.window="..."  │  ← Listening on WINDOW
└──────────────────────────────────┘
                 ↑
                 │
          NEVER TRIGGERED  ❌
```

**Problem:** Livewire events stay on server, Alpine.js listens on browser.

---

### WORKING: Browser Event (Fixed)

```
PHP: AppointmentWeekPicker.php
┌────────────────────────────────────────┐
│ $this->js(<<<JS                        │
│   window.dispatchEvent(                │
│     new CustomEvent('slot-selected', {│
│       detail: { datetime: '...' }     │
│     })                                 │
│   )                                    │
│ JS)                                    │
└────────────────┬───────────────────────┘
                 │
                 ↓
       Browser JavaScript
       (Executes on client)
                 │
                 ↓
          window.dispatchEvent()
                 │
                 ↓
       CustomEvent dispatched
                 │
                 ↓
Alpine.js: appointment-week-picker-wrapper.blade.php
┌──────────────────────────────────┐
│ x-on:slot-selected.window="..."  │  ← Listening on WINDOW
└────────────────┬─────────────────┘
                 │
                 ↓
          EVENT RECEIVED  ✅
                 │
                 ↓
        handleSlotSelected()
                 │
                 ↓
      Update hidden field  ✅
```

**Solution:** Use `$this->js()` to dispatch browser event that Alpine.js can receive.

---

## DOM Structure Analysis

### WRONG: querySelector from Nested Component

```
document
├── form#filament-form
│   ├── div.week-picker-field (ViewField)
│   │   └── div.week-picker-wrapper (Alpine.js)
│   │       └── div[wire:id="livewire-component"]
│   │           └── button.slot-button
│   │               @click="
│   │                 querySelector('input[name=starts_at]')
│   │                 ❌ Searches from DOCUMENT root
│   │                 ❌ May find wrong input
│   │                 ❌ Livewire isolation blocks access
│   │               "
│   │
│   └── input[name=starts_at] (Hidden field - SIBLING)
│       ❌ Not in Livewire component scope
```

**Problem:** querySelector from nested component cannot reliably find sibling hidden field.

---

### CORRECT: closest() from Wrapper

```
document
├── form#filament-form  ← TARGET via closest('form')
│   ├── div.week-picker-field (ViewField)
│   │   └── div.week-picker-wrapper (Alpine.js)  ← START HERE
│   │       │  $el.closest('form')  ✅
│   │       │  ↓
│   │       │  Traverses UP to form
│   │       │  ↓
│   │       │  form.querySelector('input[name=starts_at]')  ✅
│   │       │
│   │       └── div[wire:id="livewire-component"]
│   │           └── button.slot-button
│   │               wire:click="selectSlot()"  ✅
│   │               (Triggers Livewire method)
│   │               ↓
│   │               $this->js() dispatches event
│   │               ↓
│   │               Wrapper receives event
│   │               ↓
│   │               Wrapper updates form
│   │
│   └── input[name=starts_at] (Hidden field)
│       ✅ Found via form.querySelector()
│       ✅ Updated reliably
```

**Solution:** Wrapper uses `$el.closest('form')` to find parent form, then querySelector within form scope.

---

## Livewire 3 Event Systems

### System 1: Livewire Events (Component-to-Component)

**Purpose:** Communication between Livewire components

**Dispatch:**
```php
$this->dispatch('event-name', ['data' => 'value']);
```

**Listen:**
```php
#[On('event-name')]
public function handleEvent($data) { }
```

**Scope:** Server-side only, between Livewire components

**NOT suitable for Alpine.js** ❌

---

### System 2: Browser Events (Component-to-Alpine.js)

**Purpose:** Communication from Livewire to Alpine.js/JavaScript

**Option A: $this->js() (Recommended)**
```php
$this->js(<<<JS
    window.dispatchEvent(new CustomEvent('event-name', {
        detail: { data: 'value' }
    }));
JS);
```

**Option B: dispatch()->to() (Livewire 3.x)**
```php
$this->dispatch('event-name', ['data' => 'value'])
    ->to(window);  // Experimental, may not work in all versions
```

**Listen in Alpine.js:**
```blade
<div x-on:event-name.window="handleEvent($event)">
```

**Scope:** Browser-side, Alpine.js can receive

**CORRECT for Alpine.js** ✅

---

## Test Button vs Slot Buttons

### Test Button (WORKS) ✅

```blade
<button
    type="button"
    @click="$wire.selectSlot('2025-10-23T08:00:00+02:00')"
    class="...">
    🧪 Test
</button>
```

**Flow:**
1. Click → `$wire.selectSlot()` called
2. Livewire method executes
3. `$this->js()` dispatches browser event
4. Alpine.js wrapper receives event
5. Wrapper updates hidden field
6. Form validation passes

**Why it works:** Uses Livewire method correctly

---

### Slot Buttons (BROKEN) ❌

```blade
<button
    type="button"
    @click="
        const input = document.querySelector('input[name=starts_at]');
        input.value = datetime;
        // ...
    "
    class="...">
    {{ $slot['time'] }}
</button>
```

**Flow:**
1. Click → Alpine.js handler executes
2. querySelector fails (wrong scope)
3. No fallback
4. Hidden field NOT updated
5. Form validation fails

**Why it fails:** Bypasses Livewire, direct DOM manipulation fails

---

## Reactivity Chain

### BROKEN Chain

```
User Click
    ↓
Alpine.js @click handler
    ↓
querySelector('input[name=starts_at]')  ❌ FAILS
    ↓
DEAD END
```

---

### WORKING Chain

```
User Click
    ↓
wire:click="selectSlot(datetime)"
    ↓
Livewire Component Method
    ↓
$this->selectedSlot = datetime (component state)
    ↓
$this->js() dispatches browser event
    ↓
Alpine.js x-on:slot-selected.window
    ↓
closest('form').querySelector('input[name=starts_at]')  ✅
    ↓
input.value = datetime
    ↓
input.dispatchEvent('input')
    ↓
Filament wire:model detects change
    ↓
afterStateUpdated() callback fires
    ↓
ends_at calculated automatically
    ↓
Form validation passes  ✅
```

---

## Summary

### Core Problems (5 Issues)

1. **Architecture:** Direct DOM manipulation instead of Livewire methods
2. **Event System:** Livewire events don't reach Alpine.js
3. **Scope:** querySelector fails due to component isolation
4. **Inconsistency:** Test button works, slot buttons don't
5. **Dead Code:** selectSlot() method unused

### Core Solutions (4 Fixes)

1. **Slot Buttons:** Change `@click` to `wire:click`
2. **Livewire Method:** Use `$this->js()` for browser events
3. **Wrapper Handler:** Use `closest('form')` for reliable querySelector
4. **Hidden Field:** Add `->live()` for immediate reactivity

### Result After Fixes

✅ Slot selection works
✅ Form validation passes
✅ Mobile and desktop identical
✅ No console errors
✅ Proper Livewire reactivity

---

**Generated:** 2025-10-14
**Reference:** WEEK_PICKER_COMPREHENSIVE_RCA_2025-10-14.md
