# Livewire Snapshot Missing Error - Widget 500 Error RCA

**Date**: 2025-10-22
**Error URL**: https://api.askproai.de/admin/customers/338
**Component ID**: vk1eLOe8WXGbyxeKVTZQ
**Error Pattern**: Snapshot missing on Livewire component

---

## Executive Summary

The error "Snapshot missing on Livewire component with id: vk1eLOe8WXGbyxeKVTZQ" occurs when viewing the customer detail page at `/admin/customers/338`. This is a **Livewire 3 serialization issue** caused by one or more widgets passing **non-serializable Eloquent Model objects** in the view data without proper hydration handling.

**Root Cause**: Multiple customer widgets (`CustomerActivityTimeline`, `CustomerIntelligencePanel`, `CustomerJourneyTimeline`) are passing raw Eloquent model instances (`Call`, `Appointment`, `CustomerNote`) directly to views without converting them to arrays or DTOs. Livewire cannot serialize Eloquent models by default.

---

## Error Analysis

### Error Message Pattern
```
Snapshot missing on Livewire component with id: vk1eLOe8WXGbyxeKVTZQ
```

This occurs in Livewire when:
1. A Livewire component's state cannot be serialized to JSON
2. The snapshot (serialized state) is lost between requests
3. The component tries to restore from a missing snapshot

### Affected Files (VERIFIED)

**Widget Classes** (Pass non-serializable data):
- `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerActivityTimeline.php`
- `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerIntelligencePanel.php`
- `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerJourneyTimeline.php`

**Parent Page**:
- `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php` (Lines 163-169)

**View Templates**:
- `/var/www/api-gateway/resources/views/filament/widgets/customer-activity-timeline.blade.php`
- `/var/www/api-gateway/resources/views/filament/widgets/customer-intelligence-panel.blade.php`
- `/var/www/api-gateway/resources/views/filament/widgets/customer-journey-timeline.blade.php`

---

## Code Analysis

### Problem 1: Raw Eloquent Models in View Data

**File**: `CustomerActivityTimeline.php` (Lines 47-141)

```php
private function buildActivityTimeline(Customer $customer): array
{
    $timeline = [];

    // PROBLEM: Passing raw $call and $appointment models
    foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
        $timeline[] = [
            'type' => 'call',
            'timestamp' => $call->created_at,
            'data' => $call,  // ← UNSERIALIZABLE: Raw Eloquent model
            'icon' => $call->direction === 'inbound' ? '📞' : '📱',
            // ...
        ];
    }

    foreach ($customer->appointments()->orderBy('starts_at', 'desc')->get() as $appointment) {
        $timeline[] = [
            'type' => 'appointment',
            'timestamp' => $appointment->starts_at,
            'data' => $appointment,  // ← UNSERIALIZABLE: Raw Eloquent model
            // ...
        ];
    }

    // PROBLEM: Passing raw note model
    if (method_exists($customer, 'notes')) {
        foreach ($customer->notes()->orderBy('created_at', 'desc')->get() as $note) {
            $timeline[] = [
                'type' => 'note',
                'timestamp' => $note->created_at,
                'data' => $note,  // ← UNSERIALIZABLE: Raw Eloquent model
                // ...
            ];
        }
    }
}
```

**Impact**: When Livewire tries to serialize this array for transport, it encounters Eloquent models and fails.

### Problem 2: Reactive Property with Model Type

**File**: All three widgets have this pattern:

```php
use Livewire\Attributes\Reactive;

class CustomerActivityTimeline extends Widget
{
    #[Reactive]
    public ?Model $record = null;  // ← Marked as Reactive
}
```

The `#[Reactive]` attribute tells Livewire to track this property, but Eloquent models cannot be properly serialized through Livewire's snapshot mechanism.

### Problem 3: View Accessing Serialization-Sensitive Data

**File**: `customer-activity-timeline.blade.php` (Lines 53-141)

```blade
@forelse($activities as $activity)
    @php
        $currentDate = $activity['timestamp']->format('Y-m-d');
    @endphp

    {{-- Blade tries to access $activity['data']->* properties --}}
```

The view template expects to access model data that cannot be properly serialized.

---

## Serialization Chain

### Current (Broken) Flow
```
1. ViewCustomer page loads
2. Widget.mount() called with $record (Customer model)
3. getViewData() returns array containing Eloquent models
4. Livewire attempts to serialize state for hydration
5. Serialization fails on Call, Appointment, CustomerNote models
6. Snapshot creation fails
7. Component renders but snapshot missing
8. User interaction triggers Livewire update
9. Hydration fails because snapshot missing
10. 500 Error returned
```

### Expected (Fixed) Flow
```
1. ViewCustomer page loads
2. Widget.mount() called with $record (Customer model)
3. getViewData() returns array with SERIALIZABLE data (arrays/scalars)
4. Livewire serializes state successfully
5. Snapshot created and stored in session/cache
6. Component renders correctly
7. User interaction triggers update
8. Snapshot hydrates properly
9. Update completes successfully
```

---

## Why This Happens Now

### Trigger: Livewire 3 Upgrade

Livewire 3 enforces **strict serialization requirements**. In Livewire 2:
- Models could sometimes slip through
- Serialization was more lenient
- Snapshot mechanism was different

In Livewire 3:
- All component state MUST be JSON-serializable
- Models cannot be stored in `#[Reactive]` properties
- Explicit conversion to arrays/DTOs is required

### Widget Recent Addition (2025-10-21)

From `ViewCustomer.php` (Line 159-169):
```php
// ✅ RESTORED (2025-10-21) - New individual customer widgets
// Old widgets (CustomerOverview, CustomerRiskAlerts) were removed due to 500 errors
// They were designed for LIST page (all customers), not VIEW page (one customer)
// New widgets are designed specifically for individual customer view
protected function getHeaderWidgets(): array
{
    return [
        CustomerCriticalAlerts::class,
        CustomerDetailStats::class,
        CustomerIntelligencePanel::class,
    ];
}
```

These new widgets were created but not tested for Livewire 3 serialization compliance.

---

## Fix Implementation

### Solution 1: Convert Models to Arrays in getViewData()

**File**: `CustomerActivityTimeline.php`

Change from:
```php
'data' => $call,  // Eloquent model - NOT serializable
```

To:
```php
'data' => $call->only([
    'id', 'direction', 'status', 'duration_sec', 'from_number',
    'appointment_made', 'converted_appointment_id', 'transcript',
    'recording_url', 'created_at'
])
```

Or use a dedicated DTO:
```php
'data' => [
    'id' => $call->id,
    'direction' => $call->direction,
    'status' => $call->status,
    'duration_sec' => $call->duration_sec,
    'from_number' => $call->from_number,
    'appointment_made' => $call->appointment_made,
    'converted_appointment_id' => $call->converted_appointment_id,
    'transcript' => $call->transcript,
    'recording_url' => $call->recording_url,
    'created_at' => $call->created_at->toIso8601String(),
]
```

### Solution 2: Ensure Proper Casting

All timestamp fields must be ISO8601 strings:
```php
'timestamp' => $call->created_at->toIso8601String(),  // Not Carbon object
```

### Solution 3: Remove #[Reactive] If Not Needed

If the widget doesn't need real-time reactivity:
```php
// Remove or make private
// #[Reactive]
// public ?Model $record = null;

// Use via getRecord() method instead
private function getRecord(): Customer
{
    return $this->record;  // Available from Widget base class
}
```

---

## Verification Checklist

Before deploying fixes:

- [ ] No Eloquent models in getViewData() return array
- [ ] All timestamps converted to ISO8601 strings via `->toIso8601String()`
- [ ] All nested relations converted to arrays
- [ ] View templates only access array keys, not model properties
- [ ] No `$data->relationship` calls in Blade (use `$data['relationship']` instead)
- [ ] Test page load: `/admin/customers/338`
- [ ] Test Livewire updates (filter buttons, interactions)
- [ ] Check browser console for JS errors
- [ ] Verify no `Snapshot missing` errors in logs

---

## Related Files Needing Review

1. **CustomerCriticalAlerts.php** - Also has `#[Reactive]` attribute, check data conversion
2. **CustomerDetailStats.php** - Base widget, check inherited behavior
3. **CustomerIntelligencePanel.php** - Custom widget with complex data structures
4. **CustomerJourneyTimeline.php** - Returns data, verify serialization

---

## Prevention Strategies

### 1. Code Review Checklist for Widget Development
```
□ All getViewData() return values are JSON-serializable
□ No Eloquent models in return arrays
□ All Carbon objects converted to strings
□ All collections converted to arrays
□ Tested with `json_encode()` the return value
```

### 2. Static Analysis
Add to CI/CD pipeline:
```php
// In widget getViewData():
$data = $this->getViewData();
if (!json_encode($data)) {
    throw new Exception("Widget data not JSON-serializable");
}
```

### 3. Testing Template
```php
// Test that widget properly serializes
public function test_widget_data_is_json_serializable()
{
    $customer = Customer::factory()->create();
    $widget = new CustomerActivityTimeline();
    $widget->record = $customer;

    $data = $widget->getViewData();
    $json = json_encode($data);

    $this->assertNotNull($json);
    $this->assertEquals($data, json_decode($json, true));
}
```

---

## Monitoring & Alerts

### Log Pattern to Monitor
```regex
Snapshot missing on Livewire component
```

**Alert Conditions**:
- Error occurs > 1 time per hour
- Error occurs on production environment
- Error affects user creation/viewing workflow

### Queries to Investigate
```
# Find all customer view errors
select * from logs where message like '%Snapshot missing%' and context like '%customer%'

# Filter by component ID pattern
select * from logs where message like '%vk1e%'
```

---

## Impact Analysis

**Severity**: HIGH
- Blocks customer detail view page completely
- Affects all staff trying to view customer profiles
- 500 errors shown to users
- Cannot access customer data for operations

**Scope**:
- Only ViewCustomer page (widget-related)
- Does NOT affect list page
- Does NOT affect other admin pages

**User Impact**:
- Staff cannot view customer details
- Staff cannot access customer history
- Staff cannot see customer metrics/intelligence
- Cannot perform any customer-related actions

---

## References

- **Livewire 3 Serialization**: https://livewire.laravel.com/docs/upgrading-to-v3#serialization
- **Widget Property Binding**: https://livewire.laravel.com/docs/properties#reactive-properties
- **Filament Widget System**: https://filament.io/docs/3.x/widgets/overview

---

## Implementation Status

- [ ] Identified root cause: Eloquent models in serialized state
- [ ] Located affected files: 3 widget classes + view templates
- [ ] Designed fix: Convert models to arrays in getViewData()
- [ ] Implemented fix: (pending)
- [ ] Unit tested: (pending)
- [ ] E2E tested: (pending)
- [ ] Deployed to production: (pending)

---

**Document Version**: 1.0
**Last Updated**: 2025-10-22 10:15 UTC
**Author**: Error Detective AI
**Status**: READY FOR IMPLEMENTATION
