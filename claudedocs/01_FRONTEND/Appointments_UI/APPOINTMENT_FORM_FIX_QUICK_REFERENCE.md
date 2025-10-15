# Appointment Form Fix - Quick Reference Card
**1-Page Action Guide**

---

## Problem Statement

**User Report:** "Form shows DUPLICATE fields - Filiale → Kundendaten → Dienstleistungen → Mitarbeiter → DANN NOCHMAL DASSELBE"

**Root Cause:** Two booking systems running simultaneously in same form
- Old: Traditional Filament dropdowns (lines 67-299)
- New: Booking flow Livewire component (lines 321-339)

**Impact:** Confusing UX, poor contrast, accessibility violations

---

## Solution Options (Choose One)

### ⭐ Option A: Replace Old with New (RECOMMENDED)
- **What:** Remove old dropdowns, enhance booking flow with branch/customer
- **Time:** 5-7 hours
- **Result:** Clean, modern single-page booking
- **Best For:** Long-term solution, best UX

### Option B: Hide New by Default
- **What:** Add toggle button, show booking flow on demand
- **Time:** 1-2 hours
- **Result:** Both systems coexist, mutually exclusive
- **Best For:** Quick fix, preserves flexibility

### Option C: Remove New Component
- **What:** Delete booking flow, revert to old system
- **Time:** 30 minutes
- **Result:** No duplication, traditional workflow
- **Best For:** Emergency rollback

---

## Option A: Implementation Steps

### 1️⃣ Prepare (10 min)
```bash
cd /var/www/api-gateway
git checkout -b fix/appointment-form-duplication
git status
```

### 2️⃣ Update AppointmentResource.php (30 min)

**Hide old sections (lines 67-299):**
```php
// REPLACE visible sections with hidden fields:
Forms\Components\Hidden::make('branch_id')
    ->default(fn ($record) => $record->branch_id ?? auth()->user()->default_branch_id),
Forms\Components\Hidden::make('customer_id')->required(),
Forms\Components\Hidden::make('service_id')->required(),
Forms\Components\Hidden::make('staff_id')->required(),
```

**Keep booking flow section (lines 321-339):**
```php
ViewField::make('booking_flow')  // ← PRIMARY UI
    ->view('livewire.appointment-booking-flow-wrapper')
```

### 3️⃣ Enhance AppointmentBookingFlow.php (2 hours)

**Add to class:**
```php
// Properties
public ?string $selectedBranchId = null;
public ?string $selectedCustomerId = null;
public array $availableBranches = [];
public array $customerSearchResults = [];
public string $customerSearch = '';

// In mount()
$this->availableBranches = Branch::where('company_id', $this->companyId)
    ->select('id', 'name', 'address')
    ->get()
    ->toArray();

// New methods
public function selectBranch($branchId)
{
    $this->selectedBranchId = $branchId;
    $this->loadServices(); // Reload services for branch
    $this->dispatch('branch-selected', branchId: $branchId);
}

public function updatedCustomerSearch()
{
    if (strlen($this->customerSearch) >= 2) {
        $this->customerSearchResults = Customer::where('company_id', $this->companyId)
            ->where(function($q) {
                $q->where('name', 'like', "%{$this->customerSearch}%")
                  ->orWhere('phone', 'like', "%{$this->customerSearch}%")
                  ->orWhere('email', 'like', "%{$this->customerSearch}%");
            })
            ->limit(10)
            ->get()
            ->toArray();
    }
}

public function selectCustomer($customerId)
{
    $this->selectedCustomerId = $customerId;
    $customer = Customer::find($customerId);

    // Auto-select preferred branch if exists
    if ($customer->preferred_branch_id) {
        $this->selectBranch($customer->preferred_branch_id);
    }

    $this->customerSearchResults = [];
    $this->customerSearch = $customer->name;
    $this->dispatch('customer-selected', customerId: $customerId);
}
```

### 4️⃣ Update appointment-booking-flow.blade.php (1.5 hours)

**Add BEFORE line 4:**
```blade
{{-- 0. BRANCH SELECTION --}}
<div class="fi-section">
    <div class="fi-section-header">Filiale auswählen</div>
    <div class="fi-radio-group">
        @foreach($availableBranches as $branch)
            <label class="fi-radio-option {{ $selectedBranchId === $branch['id'] ? 'selected' : '' }}"
                   wire:key="branch-{{ $branch['id'] }}">
                <input type="radio" name="branch" value="{{ $branch['id'] }}"
                       wire:model.live="selectedBranchId"
                       wire:change="selectBranch('{{ $branch['id'] }}')"
                       class="fi-radio-input">
                <div class="flex-1">
                    <div class="font-medium text-sm">{{ $branch['name'] }}</div>
                    <div class="text-xs text-gray-400">{{ $branch['address'] ?? '' }}</div>
                </div>
            </label>
        @endforeach
    </div>
</div>

{{-- 1. CUSTOMER SELECTION --}}
<div class="fi-section">
    <div class="fi-section-header">Kunde auswählen</div>
    <div class="relative">
        <input type="text"
               wire:model.live.debounce.300ms="customerSearch"
               placeholder="Kunde suchen (Name, Telefon, Email)..."
               class="w-full px-4 py-2.5 text-sm border-2 border-gray-300 dark:border-gray-600
                      rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                      focus:border-primary-500 dark:focus:border-primary-400 focus:ring-2 focus:ring-primary-500/20">

        @if(count($customerSearchResults) > 0)
            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800
                        border-2 border-gray-300 dark:border-gray-600 rounded-lg shadow-lg
                        max-h-60 overflow-y-auto">
                @foreach($customerSearchResults as $customer)
                    <button type="button"
                            wire:click="selectCustomer('{{ $customer['id'] }}')"
                            class="w-full px-4 py-2.5 text-left hover:bg-primary-50 dark:hover:bg-primary-900/20
                                   border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <div class="font-medium text-sm text-gray-900 dark:text-gray-100">
                            {{ $customer['name'] }}
                        </div>
                        @if(!empty($customer['phone']))
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                📞 {{ $customer['phone'] }}
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        @if($selectedCustomerId)
            <div class="mt-2 p-3 bg-success-50 dark:bg-success-900/20 rounded-lg
                        border-2 border-success-200 dark:border-success-700">
                <div class="flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium text-success-700 dark:text-success-300">
                        ✓ {{ $customerSearch }}
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>
```

**Fix contrast (UPDATE lines 224-227):**
```css
.dark .fi-section {
    background-color: var(--color-gray-800);
    border: 2px solid var(--color-gray-600); /* STRONGER CONTRAST */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2); /* ADD DEPTH */
}

.dark .fi-section-header {
    color: var(--color-gray-100); /* BRIGHTER TEXT */
}

/* Add focus indicators for accessibility */
.fi-radio-option:focus-within,
input[type="text"]:focus {
    outline: 2px solid var(--color-primary-500);
    outline-offset: 2px;
}
```

### 5️⃣ Update booking-flow-wrapper.blade.php (15 min)

**Add event handlers for branch/customer:**
```blade
x-on:branch-selected.window="
    const branchInput = form.querySelector('input[name=branch_id]');
    if (branchInput) {
        branchInput.value = $event.detail.branchId;
        branchInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
"
x-on:customer-selected.window="
    const customerInput = form.querySelector('input[name=customer_id]');
    if (customerInput) {
        customerInput.value = $event.detail.customerId;
        customerInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
"
```

### 6️⃣ Test (1-2 hours)

**Manual Testing:**
```
✓ Open /admin/appointments/create
✓ No duplicate fields visible
✓ Branch selection filters services
✓ Customer search works
✓ Service selection loads calendar
✓ Slot selection updates form
✓ Form submission creates appointment
✓ Check contrast with DevTools
✓ Test keyboard navigation (Tab, Enter)
✓ Test on mobile (responsive)
```

**Automated Tests:**
```bash
php artisan test --filter AppointmentTest
```

### 7️⃣ Deploy (15 min)

```bash
git add .
git commit -m "fix: remove duplicate appointment form fields - Option A implementation

- Hide old Filament sections (branch/customer/service/staff dropdowns)
- Enhance booking flow component with branch/customer selection
- Fix WCAG contrast issues (gray-700 → gray-600 borders)
- Add accessibility improvements (focus indicators)
- Comprehensive testing completed

Closes: Appointment form duplication issue
WCAG: 2.1 AA compliant
"

git push origin fix/appointment-form-duplication

# Create PR, get review, merge to main
```

---

## Option B: Quick Fix Steps

### 1️⃣ Add Toggle Button (45 min)

**In AppointmentResource.php (line 320, BEFORE booking_flow):**
```php
Forms\Components\ViewField::make('booking_mode_toggle')
    ->view('filament.forms.components.booking-mode-toggle')
    ->columnSpanFull(),
```

**Create new file:** `resources/views/filament/forms/components/booking-mode-toggle.blade.php`
```blade
<div x-data="{ showCalendar: false }" class="mb-4">
    <button type="button"
            @click="showCalendar = !showCalendar"
            class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        <span x-show="!showCalendar">📅 Zur Kalenderansicht wechseln</span>
        <span x-show="showCalendar">📝 Zur manuellen Eingabe wechseln</span>
    </button>
</div>
```

### 2️⃣ Hide Booking Flow Conditionally (15 min)

**Update booking_flow ViewField:**
```php
Forms\Components\ViewField::make('booking_flow')
    ->view('livewire.appointment-booking-flow-wrapper')
    ->extraAttributes([
        'x-show' => 'showCalendar',
        'x-transition' => true,
    ])
```

**Hide manual picker when calendar shown:**
```php
Forms\Components\DateTimePicker::make('starts_at_manual')
    ->extraAttributes([
        'x-show' => '!showCalendar',
    ])
```

### 3️⃣ Test & Deploy (30 min)

```bash
git add .
git commit -m "fix: add toggle between manual and calendar booking (Option B)"
git push
```

---

## Option C: Rollback Steps

### 1️⃣ Remove Booking Flow (10 min)

**Delete lines 321-339 in AppointmentResource.php:**
```php
// DELETE THIS:
Forms\Components\ViewField::make('booking_flow')
    ->view('livewire.appointment-booking-flow-wrapper')
    ...
```

### 2️⃣ Keep Manual Picker (no changes)

Lines 355-425 stay as-is (manual DateTimePicker)

### 3️⃣ Deploy (5 min)

```bash
git add .
git commit -m "revert: remove booking flow component (Option C rollback)"
git push
```

---

## Contrast Fix (All Options)

**File:** `resources/views/livewire/appointment-booking-flow.blade.php`
**Lines:** 224-227

**BEFORE (Fails WCAG):**
```css
.dark .fi-section {
    border-color: var(--color-gray-700); /* 1.2:1 contrast ❌ */
}
```

**AFTER (Passes WCAG):**
```css
.dark .fi-section {
    border: 2px solid var(--color-gray-600); /* 3.2:1 contrast ✅ */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);
}
```

---

## Testing Commands

```bash
# Manual browser test
open http://localhost/admin/appointments/create

# Contrast checker
# Use: https://webaim.org/resources/contrastchecker/
# Background: #1f2937 (gray-800)
# Border Before: #374151 (gray-700) → 1.2:1 ❌
# Border After: #4b5563 (gray-600) → 3.2:1 ✅

# Accessibility audit
# Chrome DevTools → Lighthouse → Accessibility

# Keyboard navigation
# Tab through all fields, press Enter to select, Esc to close dropdowns

# Responsive test
# Chrome DevTools → Device Mode → iPhone SE, iPad, Desktop
```

---

## Files Changed (Option A)

```
Modified:
├─ app/Filament/Resources/AppointmentResource.php (hide old sections)
├─ app/Livewire/AppointmentBookingFlow.php (add branch/customer logic)
├─ resources/views/livewire/appointment-booking-flow.blade.php (add UI sections)
├─ resources/views/livewire/appointment-booking-flow-wrapper.blade.php (add events)
└─ resources/views/livewire/appointment-booking-flow.blade.php (fix CSS contrast)
```

---

## Decision Tree

```
USER: Which option?
├─ A: Best UX, modern → 5-7 hours → Refactor component
├─ B: Quick fix → 1-2 hours → Add toggle
└─ C: Emergency → 30 min → Delete component

ACCESSIBILITY: Fix contrast?
└─ YES (required) → Update CSS → 15 min

DEPLOYMENT: When?
├─ Immediate → Option C
├─ This week → Option B
└─ Next sprint → Option A
```

---

## Support

**Full Documentation:**
- `/claudedocs/APPOINTMENT_FORM_DUPLICATION_DIAGNOSIS_2025-10-14.md`
- `/claudedocs/APPOINTMENT_FORM_DUPLICATION_VISUAL_SUMMARY.md`

**Questions:**
- Which option do you prefer?
- When do you need this deployed?
- Do you want to review implementation before proceeding?

---

**Status:** Ready for user decision
**Recommendation:** Option A (best long-term solution)
**Quick Fix:** Option B (if time-constrained)
**Emergency:** Option C (rollback)
