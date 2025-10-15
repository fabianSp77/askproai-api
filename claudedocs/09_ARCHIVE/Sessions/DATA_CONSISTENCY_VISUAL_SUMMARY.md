# Data Consistency & History Tracking - Visual Summary

**Quick Navigation**: This document provides visual diagrams and examples
**Technical Spec**: `DATA_CONSISTENCY_SPECIFICATION.md`
**Quick Start**: `DATA_CONSISTENCY_QUICK_START.md`

---

## SYSTEM OVERVIEW

### Current vs Target State

```
CURRENT STATE (Problems):
┌─────────────────────────────────────────────────────────┐
│ CUSTOMER                                                │
│ - ID: 123                                               │
│ - Name: Max Mustermann                                 │
│ - Stats: appointment_count, call_count (may be stale) │
│                                                         │
│ RELATIONSHIPS:                                          │
│ ├── Calls (?? metadata often empty)                    │
│ └── Appointments (?? no modification history visible)  │
└─────────────────────────────────────────────────────────┘

Problems:
❌ Can't see "when appointment was booked, when rescheduled"
❌ Can't see "all calls related to this appointment"
❌ No unified timeline view
❌ Metadata fields empty/incomplete


TARGET STATE (After Implementation):
┌─────────────────────────────────────────────────────────┐
│ CUSTOMER                                                │
│ - ID: 123                                               │
│ - Name: Max Mustermann                                 │
│                                                         │
│ COMPLETE TIMELINE VIEW:                                │
│ ├── 📞 Call (2025-10-10 10:30) → Booking call         │
│ ├── 📅 Appointment Created (2025-10-10 10:31)         │
│ ├── 📞 Call (2025-10-12 09:00) → Reschedule call     │
│ ├── ✏️ Modification: Rescheduled (2025-10-12 09:01)  │
│ └── 📞 Call (2025-10-14 08:00) → Confirmation call   │
│                                                         │
│ RELATIONSHIPS (fully populated):                       │
│ ├── Calls (linking_metadata ✅)                       │
│ └── Appointments                                        │
│     ├── booked_at ✅                                   │
│     ├── modifications ✅                               │
│     └── relatedCalls ✅                                │
└─────────────────────────────────────────────────────────┘

Features:
✅ Complete modification history with timestamps
✅ All calls linked to appointments
✅ Automatic metadata population
✅ Chronological timeline
```

---

## DATA MODEL ARCHITECTURE

### Entity Relationship Diagram

```
┌─────────────────┐
│    COMPANY      │
│   (Tenant)      │
└────────┬────────┘
         │
         │ company_id (isolation boundary)
         │
    ┌────┴─────────────────────────────────────┐
    │                                           │
    │                                           │
┌───▼──────────┐                    ┌──────────▼───┐
│   CUSTOMER   │                    │  PHONE_NUMBER│
│              │                    │              │
│ - Statistics │                    │ - Number     │
│ - Journey    │                    │ - Company    │
└───┬──────────┘                    └──────┬───────┘
    │                                      │
    │                                      │
    │ customer_id                  phone_number_id
    │                                      │
    │         ┌────────────────────────────┘
    │         │
    │    ┌────▼────┐
    │    │  CALL   │
    │    │         │
    │    │ Fields: │
    │    │ - linking_metadata (JSON)      ← MUST POPULATE
    │    │ - metadata (JSON)              ← MUST POPULATE
    │    │ - customer_id                  │
    │    │ - appointment_id (legacy)      │
    │    │ - company_id                   │
    │    └────┬────┘
    │         │
    │         │ call_id (originating call)
    │         │ appointment_id (related calls)
    │         │
    │    ┌────▼────────┐
    ├────┤ APPOINTMENT │
    │    │             │
    │    │ NEW Fields: │
    │    │ - booked_at            ← ADD THIS
    │    │ - last_modified_at     ← ADD THIS
    │    │ - modification_count   ← ADD THIS
    │    │ - call_id              ✅ exists
    │    │                        │
    │    │ NEW Relationships:     │
    │    │ - modifications()      ← ADD THIS
    │    │ - relatedCalls()       ← ADD THIS
    │    │ - originatingCall()    ← ADD THIS
    │    └────┬──────────┘
    │         │
    │         │ appointment_id
    │         │
    │    ┌────▼────────────────────┐
    └────┤ APPOINTMENT_MODIFICATION│
         │                         │
         │ - modification_type     │
         │   (cancel|reschedule)   │
         │ - within_policy         │
         │ - fee_charged           │
         │ - modified_by_type      │
         │ - modified_by_id        │
         │ - customer_id           │
         │ - created_at            │
         └─────────────────────────┘
```

### Data Flow: Call → Appointment → Modification

```
SCENARIO: Customer calls, books appointment, then reschedules

Step 1: Initial Call (Booking)
┌────────────────────────────────────────────┐
│ CALL #1 (created)                          │
│ - customer_id: 123                         │
│ - appointment_made: true                   │
│ - linking_metadata: {                      │
│     customer_id: 123,                      │
│     customer_name: "Max Mustermann",       │
│     linked_at: "2025-10-10T10:30:00Z"      │
│   }                                        │
│ - metadata: {                              │
│     appointment_details: {                 │
│       service_name: "Haarschnitt",         │
│       scheduled_time: "2025-10-15T14:00"   │
│     }                                      │
│   }                                        │
└────────────────────────────────────────────┘
            │
            │ Creates
            ▼
┌────────────────────────────────────────────┐
│ APPOINTMENT #456 (created)                 │
│ - customer_id: 123                         │
│ - call_id: <CALL #1 ID>  ← Link to origin │
│ - starts_at: 2025-10-15 14:00             │
│ - booked_at: 2025-10-10 10:31 ← AUTO SET │
│ - modification_count: 0                    │
└────────────────────────────────────────────┘

Step 2: Reschedule Call
┌────────────────────────────────────────────┐
│ CALL #2 (created)                          │
│ - customer_id: 123                         │
│ - appointment_id: 456  ← Link to existing │
│ - metadata: {                              │
│     call_context: {                        │
│       is_reschedule: true,                 │
│       original_appointment_id: 456         │
│     }                                      │
│   }                                        │
└────────────────────────────────────────────┘
            │
            │ Triggers modification
            ▼
┌────────────────────────────────────────────┐
│ APPOINTMENT_MODIFICATION (created)         │
│ - appointment_id: 456                      │
│ - customer_id: 123                         │
│ - modification_type: "reschedule"          │
│ - within_policy: true                      │
│ - fee_charged: 0.00                        │
│ - modified_by_type: "Customer"             │
│ - created_at: 2025-10-12 09:01            │
└────────────────────────────────────────────┘
            │
            │ Updates
            ▼
┌────────────────────────────────────────────┐
│ APPOINTMENT #456 (updated)                 │
│ - starts_at: 2025-10-16 16:00 ← CHANGED  │
│ - last_modified_at: 2025-10-12 09:01 ← SET│
│ - modification_count: 1 ← INCREMENTED     │
└────────────────────────────────────────────┘
```

---

## UI MOCKUPS (Text-Based)

### Customer Detail View - Timeline Section

```
┌─────────────────────────────────────────────────────────────┐
│ Customer: Max Mustermann (#123)                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ [Basic Info] [Appointments] [Calls] [Activity Timeline] ←NEW│
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ 📊 ACTIVITY TIMELINE                              [Filter ▼]│
│ ════════════════════════════════════════════════════════════│
│                                                              │
│ 2025-10-14                                                  │
│ ├─ 📞 08:00  Call                                           │
│ │           Outcome: Confirmation successful                │
│ │           Duration: 2m 34s                    [View Call →]│
│                                                              │
│ 2025-10-12                                                  │
│ ├─ ✏️ 09:01  Appointment Rescheduled                        │
│ │           From: Oct 15, 14:00 → To: Oct 16, 16:00        │
│ │           By: Customer (Policy compliant)    [View Appt →]│
│ │                                                            │
│ ├─ 📞 09:00  Call                                           │
│ │           Outcome: Rescheduled appointment                │
│ │           Duration: 3m 12s                    [View Call →]│
│                                                              │
│ 2025-10-10                                                  │
│ ├─ 📅 10:31  Appointment Created                            │
│ │           Service: Haarschnitt                            │
│ │           Scheduled: Oct 15, 14:00           [View Appt →]│
│ │                                                            │
│ └─ 📞 10:30  Call                                           │
│             Outcome: Appointment booked                     │
│             Duration: 5m 47s                    [View Call →]│
│                                                              │
│                                        [Load More (34 older)]│
└─────────────────────────────────────────────────────────────┘
```

### Appointment Detail View - New Sections

```
┌─────────────────────────────────────────────────────────────┐
│ Appointment #456                                             │
│ Service: Haarschnitt | Customer: Max Mustermann (#123)      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ [Details] [Modification History] [Related Calls] ← NEW TABS │
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ 📅 APPOINTMENT DETAILS                                      │
│ ════════════════════════════════════════════════════════════│
│ Service:        Haarschnitt (30 min)                        │
│ Customer:       Max Mustermann                 [View →]     │
│ Staff:          Anna Schmidt                                │
│ Scheduled:      Oct 16, 2025 16:00                         │
│ Status:         🟢 Confirmed                                │
│                                                              │
│ Booked At:      Oct 10, 2025 10:31 ← NEW                   │
│ Last Modified:  Oct 12, 2025 09:01 ← NEW                   │
│ Modifications:  1 reschedule ← NEW                          │
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ ✏️ MODIFICATION HISTORY ← NEW SECTION                       │
│ ════════════════════════════════════════════════════════════│
│                                                              │
│ Date/Time         │ Type       │ By       │ Fee │ Status    │
│ ─────────────────────────────────────────────────────────── │
│ Oct 12, 09:01    │ Reschedule │ Customer │ €0  │ ✅ Policy │
│ Oct 10, 10:31    │ ⭐ Booked  │ Call     │ -   │ -         │
│                                                              │
│ Timeline:                                                    │
│ Oct 10 10:31 ─[Booked]─► Oct 12 09:01 ─[Rescheduled]─► Now │
│   14:00 slot              16:00 slot                         │
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ 📞 RELATED CALLS ← NEW SECTION                              │
│ ════════════════════════════════════════════════════════════│
│                                                              │
│ Date/Time         │ Duration │ Outcome            │ Action  │
│ ─────────────────────────────────────────────────────────── │
│ Oct 14, 08:00    │ 2m 34s   │ Confirmation      │ [View →]│
│ Oct 12, 09:00    │ 3m 12s   │ Rescheduled       │ [View →]│
│ Oct 10, 10:30 ⭐ │ 5m 47s   │ Booked appt       │ [View →]│
│                                                              │
│ ⭐ = Originating call (created this appointment)            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Call Detail View - Appointment Context Section

```
┌─────────────────────────────────────────────────────────────┐
│ Call #789                                                    │
│ Customer: Max Mustermann (#123) | Duration: 3m 12s          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ 📞 CALL DETAILS                                             │
│ ════════════════════════════════════════════════════════════│
│ Started:        Oct 12, 2025 09:00                         │
│ Duration:       3m 12s                                      │
│ Outcome:        Rescheduled appointment                     │
│ Status:         ✅ Successful                               │
│                                                              │
│ ════════════════════════════════════════════════════════════│
│ 📅 APPOINTMENT CONTEXT ← NEW SECTION                        │
│ ════════════════════════════════════════════════════════════│
│                                                              │
│ Context:        🔄 Reschedule call                          │
│                                                              │
│ Appointment:    #456 - Haarschnitt          [View Appt →]  │
│ Original Time:  Oct 15, 14:00                               │
│ New Time:       Oct 16, 16:00                               │
│ Staff:          Anna Schmidt                                │
│ Status:         🟢 Confirmed                                │
│                                                              │
│ Modification:                                               │
│ - Type:         Reschedule                                  │
│ - Within Policy: ✅ Yes                                     │
│ - Fee:          €0                                          │
│                                                              │
│ All Calls for this Appointment:           [View All (3) →] │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## USER JOURNEY FLOWS

### Journey 1: Super Admin Investigating Customer Issue

```
SCENARIO: Customer complains "I never rescheduled my appointment!"

Step 1: Navigate to Customer
┌─────────────────────────────────────┐
│ Dashboard → Customers → Search      │
│ Enter: "Max Mustermann"             │
│ Click: View Customer Details        │
└─────────────────────────────────────┘
              │
              ▼
Step 2: Check Activity Timeline
┌─────────────────────────────────────┐
│ Customer Detail → Activity Timeline │
│                                     │
│ Sees:                               │
│ ✏️ Oct 12, 09:01 - Rescheduled     │
│    By: Customer (Policy compliant) │
│    From phone: +49123456789         │
│                                     │
│ 📞 Oct 12, 09:00 - Call            │
│    Outcome: Rescheduled             │
│                                     │
│ Click: View Call                    │
└─────────────────────────────────────┘
              │
              ▼
Step 3: Review Call Details
┌─────────────────────────────────────┐
│ Call Detail → Listen to Recording   │
│                                     │
│ Evidence:                           │
│ - Transcript shows reschedule       │
│ - Customer voice confirmed          │
│ - Timestamp matches modification    │
│                                     │
│ RESOLUTION: Customer DID reschedule │
│ (they forgot)                       │
└─────────────────────────────────────┘

Total Time: <2 minutes (vs. 15 minutes manual investigation)
```

### Journey 2: Platform User Monitoring No-Shows

```
SCENARIO: Platform user wants to see which customers reschedule often

Step 1: Dashboard Widget
┌─────────────────────────────────────┐
│ Dashboard                           │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ 🔄 RECENT MODIFICATIONS         │ │
│ │ (Last 7 days)                   │ │
│ ├─────────────────────────────────┤ │
│ │ Max M. - Rescheduled (2nd time) │ │
│ │ Lisa K. - Cancelled (3rd time)  │ │
│ │ Tom B. - Rescheduled            │ │
│ └─────────────────────────────────┘ │
│                                     │
│ Click: Max M. entry                 │
└─────────────────────────────────────┘
              │
              ▼
Step 2: Customer Detail
┌─────────────────────────────────────┐
│ Customer: Max Mustermann            │
│                                     │
│ Stats:                              │
│ - Appointments: 12                  │
│ - Modifications: 5 (last 90 days)   │
│ - No-shows: 1                       │
│                                     │
│ Timeline shows pattern:             │
│ - Often reschedules Mon → Tue       │
│ - Prefers afternoon slots           │
│                                     │
│ ACTION: Set reminder for Max        │
│ "Avoid Monday bookings"             │
└─────────────────────────────────────┘

Insight: Data pattern reveals booking preferences
```

---

## TECHNICAL IMPLEMENTATION EXAMPLES

### Example 1: Populate booked_at on Creation

```php
// app/Observers/AppointmentObserver.php

class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        // Auto-set booked_at to creation time
        $appointment->update([
            'booked_at' => now(),
            'modification_count' => 0,
        ]);

        Log::info('Appointment booked_at populated', [
            'appointment_id' => $appointment->id,
            'booked_at' => $appointment->booked_at,
        ]);
    }

    public function updated(Appointment $appointment)
    {
        // Track meaningful changes
        $trackedFields = ['starts_at', 'service_id', 'staff_id', 'status'];

        if ($appointment->isDirty($trackedFields)) {
            $appointment->increment('modification_count');
            $appointment->update(['last_modified_at' => now()]);

            Log::info('Appointment modified', [
                'appointment_id' => $appointment->id,
                'changes' => $appointment->getChanges(),
            ]);
        }
    }
}
```

### Example 2: Timeline Method on Customer

```php
// app/Models/Customer.php

/**
 * Get chronological timeline of all customer interactions
 */
public function getTimelineAttribute(): Collection
{
    $events = collect();

    // Add calls
    $this->calls->each(function ($call) use ($events) {
        $events->push([
            'type' => 'call',
            'icon' => '📞',
            'color' => 'text-blue-600',
            'timestamp' => $call->created_at,
            'description' => "Call: {$call->session_outcome}",
            'entity' => $call,
            'url' => route('filament.resources.calls.view', $call),
        ]);
    });

    // Add appointments
    $this->appointments->each(function ($appointment) use ($events) {
        $events->push([
            'type' => 'appointment',
            'icon' => '📅',
            'color' => 'text-green-600',
            'timestamp' => $appointment->booked_at ?? $appointment->created_at,
            'description' => "Booked: {$appointment->service->name}",
            'entity' => $appointment,
            'url' => route('filament.resources.appointments.view', $appointment),
        ]);

        // Add modifications
        $appointment->modifications->each(function ($mod) use ($events) {
            $events->push([
                'type' => 'modification',
                'icon' => '✏️',
                'color' => $mod->within_policy ? 'text-yellow-600' : 'text-red-600',
                'timestamp' => $mod->created_at,
                'description' => ucfirst($mod->modification_type),
                'entity' => $mod,
                'url' => route('filament.resources.appointments.view', $mod->appointment_id),
            ]);
        });
    });

    return $events->sortByDesc('timestamp');
}
```

### Example 3: Filament Infolist for Timeline

```php
// app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

public function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            Section::make('Customer Information')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    // ... other fields
                ]),

            Section::make('Activity Timeline')
                ->schema([
                    TextEntry::make('timeline')
                        ->label('')
                        ->formatStateUsing(function ($record) {
                            return view('filament.components.customer-timeline', [
                                'events' => $record->timeline->take(50),
                            ]);
                        }),
                ])
                ->collapsible()
                ->collapsed(false),
        ]);
}
```

---

## PERFORMANCE OPTIMIZATION EXAMPLES

### Example 1: Eager Loading to Avoid N+1

```php
// WRONG: N+1 query problem
$customer = Customer::find($id);
foreach ($customer->calls as $call) {
    echo $call->appointment->service->name; // Each iteration = 2 queries
}
// Result: 1 + (N * 2) queries

// CORRECT: Eager load relationships
$customer = Customer::with([
    'calls.appointment.service',
    'appointments.modifications.modifiedBy',
])->find($id);

foreach ($customer->calls as $call) {
    echo $call->appointment->service->name; // Already loaded
}
// Result: 4 queries total (customer, calls, appointments, services)
```

### Example 2: Indexing for Fast Timeline Queries

```sql
-- Before: Full table scan
SELECT * FROM calls WHERE customer_id = 123 ORDER BY created_at DESC;
-- Execution time: 450ms (with 100k rows)

-- After: Add composite index
CREATE INDEX idx_calls_customer_created
ON calls(customer_id, created_at);
-- Execution time: 12ms (same data)

-- Before: Slow modification lookup
SELECT * FROM appointment_modifications
WHERE appointment_id = 456 ORDER BY created_at DESC;
-- Execution time: 280ms

-- After: Add index
CREATE INDEX idx_appt_mods_appointment_created
ON appointment_modifications(appointment_id, created_at);
-- Execution time: 8ms
```

### Example 3: Caching Timeline Data

```php
// app/Models/Customer.php

use Illuminate\Support\Facades\Cache;

public function getTimelineAttribute(): Collection
{
    return Cache::remember(
        "customer.timeline.{$this->id}",
        now()->addMinutes(5),
        function () {
            return $this->buildTimeline();
        }
    );
}

// Clear cache when data changes
public static function boot()
{
    parent::boot();

    static::updated(function ($customer) {
        Cache::forget("customer.timeline.{$customer->id}");
    });
}

// Clear cache when related models change
// In CallObserver, AppointmentObserver, etc.
public function created(Call $call)
{
    if ($call->customer_id) {
        Cache::forget("customer.timeline.{$call->customer_id}");
    }
}
```

---

## TESTING EXAMPLES

### Example 1: Unit Test for Relationships

```php
// tests/Unit/Models/AppointmentTest.php

public function test_modifications_relationship_returns_correct_records()
{
    $appointment = Appointment::factory()->create();

    $modification1 = AppointmentModification::factory()->create([
        'appointment_id' => $appointment->id,
        'modification_type' => 'reschedule',
    ]);

    $modification2 = AppointmentModification::factory()->create([
        'appointment_id' => $appointment->id,
        'modification_type' => 'cancel',
    ]);

    // Different appointment - should not be included
    AppointmentModification::factory()->create();

    $this->assertCount(2, $appointment->modifications);
    $this->assertTrue($appointment->modifications->contains($modification1));
}

public function test_booked_at_set_on_creation()
{
    $appointment = Appointment::factory()->create();

    $this->assertNotNull($appointment->booked_at);
    $this->assertTrue($appointment->booked_at->isToday());
}
```

### Example 2: Feature Test for Timeline View

```php
// tests/Feature/Filament/CustomerTimelineTest.php

public function test_super_admin_can_view_complete_timeline()
{
    $admin = User::factory()->superAdmin()->create();
    $customer = Customer::factory()->create();

    $call = Call::factory()->create(['customer_id' => $customer->id]);
    $appointment = Appointment::factory()->create(['customer_id' => $customer->id]);
    $modification = AppointmentModification::factory()->create([
        'appointment_id' => $appointment->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($admin)
        ->get(CustomerResource::getUrl('view', ['record' => $customer]))
        ->assertSuccessful()
        ->assertSee('Activity Timeline')
        ->assertSee($call->session_outcome)
        ->assertSee($appointment->service->name)
        ->assertSee($modification->modification_type);
}

public function test_timeline_loads_within_500ms()
{
    $admin = User::factory()->superAdmin()->create();
    $customer = Customer::factory()
        ->has(Call::factory()->count(50))
        ->has(Appointment::factory()->count(30))
        ->create();

    $startTime = microtime(true);

    $this->actingAs($admin)
        ->get(CustomerResource::getUrl('view', ['record' => $customer]))
        ->assertSuccessful();

    $duration = (microtime(true) - $startTime) * 1000; // Convert to ms

    $this->assertLessThan(500, $duration, "Timeline took {$duration}ms (target: <500ms)");
}
```

---

## GLOSSARY OF TERMS

| Term | Definition | Example |
|------|------------|---------|
| **Originating Call** | The first call that created an appointment | Call #123 at 10:30 → Appointment #456 |
| **Related Calls** | All calls with same `appointment_id` | Calls #123, #124, #125 all reference Appointment #456 |
| **Modification History** | Timeline of cancellations and reschedules | Booked → Rescheduled → Confirmed |
| **Activity Timeline** | Chronological view of all customer events | Calls + Appointments + Modifications |
| **Tenant Isolation** | Each company (tenant) only sees their data | Company A cannot see Company B data |
| **Eager Loading** | Pre-loading related data to avoid N+1 queries | Load customer WITH calls in one query |
| **Metadata Population** | Auto-filling JSON fields with context | linking_metadata populated on customer link |

---

**This document provides visual context for**: `DATA_CONSISTENCY_SPECIFICATION.md`
**Implementation guide**: `DATA_CONSISTENCY_QUICK_START.md`
**Status**: Ready for implementation

**Total Implementation Effort**: ~11 days across 4 phases
