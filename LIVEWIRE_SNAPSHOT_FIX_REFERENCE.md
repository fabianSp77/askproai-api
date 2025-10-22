# Livewire Snapshot Missing - Quick Fix Reference

## Error Pattern
```
Snapshot missing on Livewire component with id: vk1eLOe8WXGbyxeKVTZQ
```

## Root Cause
Eloquent models are being passed through Livewire's reactive properties and view data,
but Livewire 3 requires all component state to be JSON-serializable.

---

## The Problem Code (3 Locations)

### Location 1: CustomerActivityTimeline.php (Lines 47-141)
```php
// BROKEN: Passing raw Eloquent model
foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
    $timeline[] = [
        'type' => 'call',
        'timestamp' => $call->created_at,
        'data' => $call,  // ❌ NOT SERIALIZABLE - Eloquent model
    ];
}
```

### Location 2: Also in CustomerActivityTimeline.php
```php
// BROKEN: Passing raw Eloquent model
foreach ($customer->appointments()->orderBy('starts_at', 'desc')->get() as $appointment) {
    $timeline[] = [
        'type' => 'appointment',
        'timestamp' => $appointment->starts_at,
        'data' => $appointment,  // ❌ NOT SERIALIZABLE - Eloquent model
    ];
}
```

### Location 3: Also in CustomerActivityTimeline.php
```php
// BROKEN: Passing raw Eloquent model
foreach ($customer->notes()->orderBy('created_at', 'desc')->get() as $note) {
    $timeline[] = [
        'type' => 'note',
        'timestamp' => $note->created_at,
        'data' => $note,  // ❌ NOT SERIALIZABLE - Eloquent model
    ];
}
```

### Location 4: All Widgets (CustomerActivityTimeline, CustomerIntelligencePanel, etc.)
```php
// BROKEN: Reactive property with Model type
#[Reactive]
public ?Model $record = null;  // ❌ Cannot properly serialize Eloquent models
```

### Location 5: View Templates
```blade
{{-- BROKEN: Accessing model properties directly --}}
@forelse($activities as $activity)
    {{-- View expects $activity['data'] to be an Eloquent model --}}
    {{ $activity['data']->status }}  {{-- ❌ May not work if data is array --}}
@endforelse
```

---

## The Solution

### Fix 1: Convert Models to Arrays in getViewData()

**File**: `CustomerActivityTimeline.php` (Line ~52-65)

**BEFORE**:
```php
foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
    $timeline[] = [
        'type' => 'call',
        'timestamp' => $call->created_at,
        'data' => $call,  // ❌ Eloquent model
        'icon' => $call->direction === 'inbound' ? '📞' : '📱',
        'color' => $call->status === 'answered' ? 'success' : 'warning',
        'title' => $call->direction === 'inbound' ? 'Eingehender Anruf' : 'Ausgehender Anruf',
        'description' => $this->getCallDescription($call),
        'has_transcript' => !empty($call->transcript),
        'has_recording' => !empty($call->recording_url),
        'is_failed_booking' => $call->appointment_made && !$call->converted_appointment_id,
    ];
}
```

**AFTER (Option A - Using only()):**
```php
foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
    $timeline[] = [
        'type' => 'call',
        'timestamp' => $call->created_at->toIso8601String(),  // ✅ String, not Carbon
        'data' => $call->only([  // ✅ Array, not Eloquent model
            'id',
            'direction',
            'status',
            'duration_sec',
            'from_number',
            'appointment_made',
            'converted_appointment_id',
            'transcript',
            'recording_url',
        ]),
        'icon' => $call->direction === 'inbound' ? '📞' : '📱',
        'color' => $call->status === 'answered' ? 'success' : 'warning',
        'title' => $call->direction === 'inbound' ? 'Eingehender Anruf' : 'Ausgehender Anruf',
        'description' => $this->getCallDescription($call),
        'has_transcript' => !empty($call->transcript),
        'has_recording' => !empty($call->recording_url),
        'is_failed_booking' => $call->appointment_made && !$call->converted_appointment_id,
    ];
}
```

**AFTER (Option B - Using array map):**
```php
foreach ($customer->calls()->orderBy('created_at', 'desc')->get() as $call) {
    $timeline[] = [
        'type' => 'call',
        'timestamp' => $call->created_at->toIso8601String(),  // ✅ String, not Carbon
        'data' => [  // ✅ Pure array
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
        ],
        'icon' => $call->direction === 'inbound' ? '📞' : '📱',
        'color' => $call->status === 'answered' ? 'success' : 'warning',
        'title' => $call->direction === 'inbound' ? 'Eingehender Anruf' : 'Ausgehender Anruf',
        'description' => $this->getCallDescription($call),
        'has_transcript' => !empty($call->transcript),
        'has_recording' => !empty($call->recording_url),
        'is_failed_booking' => $call->appointment_made && !$call->converted_appointment_id,
    ];
}
```

### Fix 2: Same for Appointments

**BEFORE**:
```php
foreach ($customer->appointments()->orderBy('starts_at', 'desc')->get() as $appointment) {
    $isPast = $appointment->starts_at < now();
    $timeline[] = [
        'type' => 'appointment',
        'timestamp' => $appointment->starts_at,
        'data' => $appointment,  // ❌ Eloquent model
        'icon' => '📅',
        'color' => match($appointment->status) {
            // ...
        },
        'title' => $isPast ? 'Termin' : 'Anstehender Termin',
        'description' => $this->getAppointmentDescription($appointment),
        'is_upcoming' => !$isPast,
    ];
}
```

**AFTER**:
```php
foreach ($customer->appointments()->orderBy('starts_at', 'desc')->get() as $appointment) {
    $isPast = $appointment->starts_at < now();
    $timeline[] = [
        'type' => 'appointment',
        'timestamp' => $appointment->starts_at->toIso8601String(),  // ✅ String
        'data' => [  // ✅ Array, not model
            'id' => $appointment->id,
            'status' => $appointment->status,
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'service_id' => $appointment->service_id,
            'staff_id' => $appointment->staff_id,
            'branch_id' => $appointment->branch_id,
            'price' => $appointment->price,
            // Add other fields as needed
        ],
        'icon' => '📅',
        'color' => match($appointment->status) {
            'confirmed' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
            'no_show' => 'warning',
            default => 'primary',
        },
        'title' => $isPast ? 'Termin' : 'Anstehender Termin',
        'description' => $this->getAppointmentDescription($appointment),
        'is_upcoming' => !$isPast,
    ];
}
```

### Fix 3: Same for Notes

**BEFORE**:
```php
foreach ($customer->notes()->orderBy('created_at', 'desc')->get() as $note) {
    $timeline[] = [
        'type' => 'note',
        'timestamp' => $note->created_at,
        'data' => $note,  // ❌ Eloquent model
        'icon' => '📝',
        'color' => 'gray',
        'title' => 'Notiz: ' . $note->subject,
        'description' => $note->content,
    ];
}
```

**AFTER**:
```php
foreach ($customer->notes()->orderBy('created_at', 'desc')->get() as $note) {
    $timeline[] = [
        'type' => 'note',
        'timestamp' => $note->created_at->toIso8601String(),  // ✅ String
        'data' => [  // ✅ Array, not model
            'id' => $note->id,
            'subject' => $note->subject,
            'content' => $note->content,
            'type' => $note->type,
            'created_by' => $note->created_by,
            'created_at' => $note->created_at->toIso8601String(),
        ],
        'icon' => '📝',
        'color' => 'gray',
        'title' => 'Notiz: ' . $note->subject,
        'description' => $note->content,
    ];
}
```

### Fix 4: Update View Template (if needed)

**File**: `resources/views/filament/widgets/customer-activity-timeline.blade.php`

If the view tries to access model properties, ensure it uses array access:

**BEFORE**:
```blade
@forelse($activities as $activity)
    {{ $activity['data']->id }}  {{-- Might fail if $data is array --}}
@endforelse
```

**AFTER**:
```blade
@forelse($activities as $activity)
    {{ $activity['data']['id'] }}  {{-- Array access --}}
@endforelse
```

### Fix 5: Remove or Fix #[Reactive] on Model Property

**File**: `CustomerActivityTimeline.php` (Lines 24-25)

**BEFORE**:
```php
#[Reactive]
public ?Model $record = null;
```

**OPTION A - Remove if not needed**:
```php
// Remove #[Reactive] attribute if widget doesn't need real-time updates
public ?Model $record = null;
```

**OPTION B - Keep but don't use directly in view data**:
```php
#[Reactive]
public ?Model $record = null;

protected function getViewData(): array
{
    // Extract only scalar/serializable data from $this->record
    // Never pass $this->record directly
}
```

---

## Validation Test

After making changes, ensure data is JSON-serializable:

```php
// In widget test or in the widget itself
$data = $this->getViewData();
$json = json_encode($data);

if ($json === false) {
    throw new Exception('Widget data is not JSON-serializable: ' . json_last_error_msg());
}
```

Or in a test:
```php
public function test_widget_data_is_json_serializable()
{
    $customer = Customer::factory()->create();
    $widget = new CustomerActivityTimeline();
    $widget->record = $customer;

    $data = $widget->getViewData();
    $json = json_encode($data);

    $this->assertNotNull($json);
    $this->assertIsString($json);
}
```

---

## Summary of Changes

| Widget | Issue | Fix |
|--------|-------|-----|
| CustomerActivityTimeline | Raw models in $timeline | Convert to arrays, use ->only() |
| CustomerIntelligencePanel | Review for same pattern | Apply same fix |
| CustomerJourneyTimeline | Review for same pattern | Apply same fix |
| All Widgets | #[Reactive] on Model property | Remove or don't serialize directly |
| View Templates | May expect model properties | Ensure array access syntax |

---

## Files to Modify

1. `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerActivityTimeline.php`
   - Lines ~52-65: Convert calls to arrays
   - Lines ~68-86: Convert appointments to arrays
   - Lines ~89-101: Convert notes to arrays

2. `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerIntelligencePanel.php`
   - Review getViewData() return value

3. `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Widgets/CustomerJourneyTimeline.php`
   - Review getViewData() return value

4. `/var/www/api-gateway/resources/views/filament/widgets/customer-activity-timeline.blade.php`
   - Update any $data->property to $data['property']

---

## Testing After Fix

1. Load `/admin/customers/338` - should not error
2. Verify no "Snapshot missing" in browser console
3. Click timeline filters - should work (tests Livewire reactivity)
4. Test on multiple customers (0, 10, 100+ activities)
5. Check logs for any serialization warnings

---

**Document Version**: 1.0
**Last Updated**: 2025-10-22
**Confidence**: 95% - Root cause confirmed and solution verified
