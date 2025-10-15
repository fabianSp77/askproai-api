# Data Consistency & History Tracking - Technical Specification

**Project**: Smart Data Relationships & Historical Tracking
**Date**: 2025-10-10
**Status**: Requirements Specification
**Priority**: HIGH

---

## 1. EXECUTIVE SUMMARY

### 1.1 Problem Statement
User requirement (German): "Die Daten müssen smart und konsistent abgelegt werden. Anruf, Termin, alle Themen die zusammengehören sollten verknüpft sein und eine Historie aufzeigen. Anrufe von einem Kunden, Anrufe zu einem Termin, in einem Termin die gesamte Historie - wann gebucht, wann verschoben. Muss in Detail-Ansichten smart und sinnvoll dargestellt werden für Super Admin UND Plattform Nutzer."

### 1.2 Translation & Interpretation
**Requirement**: Data must be stored smartly and consistently. Calls, appointments, all related items should be linked and show history. Customer calls, appointment calls, appointment history - when booked, when rescheduled. Must be displayed smartly in detail views for both Super Admin AND Platform Users.

### 1.3 Core Objectives
1. **Data Linkage**: Ensure all related entities (Call ↔ Customer ↔ Appointment) are properly connected
2. **Historical Tracking**: Capture complete timeline of events (booking, modifications, calls)
3. **Consistent Storage**: Eliminate gaps in metadata and relationship fields
4. **Smart Display**: Present interconnected data in Filament admin panels for two user types
5. **Multi-tenant Isolation**: Maintain company_id consistency across all relationships

---

## 2. CURRENT STATE ANALYSIS

### 2.1 Existing Data Models

#### Customer Model
**File**: `/var/www/api-gateway/app/Models/Customer.php`

**Relationships**:
- ✅ `hasMany(Call::class)` - Customer → Calls
- ✅ `hasMany(Appointment::class)` - Customer → Appointments
- ✅ `hasMany(AppointmentModificationStat::class)` - Modification stats
- ✅ `hasMany(CustomerNote::class)` - Notes
- ✅ `belongsTo(Company::class)` - Multi-tenant

**Statistics Fields** (calculated):
- `appointment_count`, `completed_appointments`, `cancelled_appointments`
- `no_show_count`, `call_count`
- `last_call_at`, `last_appointment_at`
- `total_spent`, `total_revenue`
- `journey_history` (array) - Status changes

**Issues**:
- ❌ Statistics may not be populated in real-time
- ❌ No direct "calls for this appointment" linkage visible in UI

#### Call Model
**File**: `/var/www/api-gateway/app/Models/Call.php`

**Relationships**:
- ✅ `belongsTo(Customer::class)` - Call → Customer
- ✅ `belongsTo(Appointment::class, 'appointment_id')` - Direct link
- ✅ `hasMany(Appointment::class, 'call_id')` - Call originated appointments
- ✅ `hasOne(Appointment::class, 'call_id')->latestOfMany()` - Latest appointment
- ✅ `belongsTo(Company::class)` - Multi-tenant
- ✅ `belongsTo(PhoneNumber::class)` - Phone number used
- ✅ `belongsTo(RetellAgent::class)` - AI agent

**Metadata Fields**:
- `metadata` (array) - General metadata
- `linking_metadata` (array) - Customer/appointment linking data
- `analysis` (array) - AI analysis results
- `raw` (array) - Raw Retell webhook data

**Issues**:
- ❌ `metadata`, `linking_metadata` may be empty/incomplete
- ⚠️ Dual relationship to Appointment (legacy `appointment_id` + new `call_id` foreign key)

#### Appointment Model
**File**: `/var/www/api-gateway/app/Models/Appointment.php`

**Relationships**:
- ✅ `belongsTo(Customer::class)` - Appointment → Customer
- ✅ `belongsTo(Call::class, 'call_id')` - Call that originated this appointment
- ✅ `belongsTo(Service::class)` - Service type
- ✅ `belongsTo(Staff::class)` - Assigned staff
- ✅ `belongsTo(Branch::class)` - Location
- ✅ `belongsTo(Company::class)` - Multi-tenant

**No History Tracking**:
- ❌ No built-in modification history on Appointment itself
- ✅ Related model: `AppointmentModification` captures changes

**Issues**:
- ❌ No direct timeline view of "when booked, when rescheduled"
- ❌ No easy "show all calls related to this appointment"

#### AppointmentModification Model
**File**: `/var/www/api-gateway/app/Models/AppointmentModification.php`

**Purpose**: Tracks cancellations and reschedules

**Fields**:
- `appointment_id` - Which appointment
- `customer_id` - Which customer
- `modification_type` - 'cancel' or 'reschedule'
- `within_policy` - Policy compliance
- `fee_charged` - Financial impact
- `modified_by_type` - Polymorphic (User|Staff|Customer|System)
- `modified_by_id` - Who made the change
- `metadata` (array) - Additional context
- `created_at` - When the modification happened

**Relationships**:
- ✅ `belongsTo(Appointment::class)`
- ✅ `belongsTo(Customer::class)`
- ✅ `morphTo('modifiedBy')` - Who made change

**Issues**:
- ✅ Good foundation for history tracking
- ❌ Not displayed in appointment detail view
- ❌ No reverse relationship defined on Appointment model

### 2.2 Current Gaps

| Gap | Impact | Severity |
|-----|--------|----------|
| Missing `Appointment::modifications()` relationship | Cannot load history in detail view | HIGH |
| Empty `Call.metadata` fields | Lost context about call-appointment connection | MEDIUM |
| No "appointment timeline" UI component | Cannot see booking history | HIGH |
| No "related calls" UI for appointments | Cannot see all calls about an appointment | HIGH |
| Statistics not updated real-time | Customer engagement data stale | MEDIUM |
| No polymorphic activity log | No unified "what happened when" view | LOW |

---

## 3. DATA MODEL REQUIREMENTS

### 3.1 Required Fields & Relationships

#### 3.1.1 Appointment Model Enhancement

**Add Missing Relationship**:
```php
// File: app/Models/Appointment.php

/**
 * All modifications (cancellations, reschedules) for this appointment
 */
public function modifications(): HasMany
{
    return $this->hasMany(AppointmentModification::class)
        ->orderBy('created_at', 'desc');
}

/**
 * Get all calls related to this appointment
 * Includes: originating call + any follow-up calls
 */
public function relatedCalls(): HasMany
{
    return $this->hasMany(Call::class, 'appointment_id');
}

/**
 * Get the originating call (first call that created this appointment)
 */
public function originatingCall(): BelongsTo
{
    return $this->belongsTo(Call::class, 'call_id');
}
```

**Required New Fields** (optional, for denormalized performance):
- `booked_at` (datetime) - When originally created
- `last_modified_at` (datetime) - Last modification timestamp
- `modification_count` (integer) - Total modifications
- `timeline_summary` (json) - Cached timeline data

**Migration**:
```php
Schema::table('appointments', function (Blueprint $table) {
    $table->timestamp('booked_at')->nullable()->after('created_at');
    $table->timestamp('last_modified_at')->nullable()->after('booked_at');
    $table->integer('modification_count')->default(0)->after('last_modified_at');
    $table->json('timeline_summary')->nullable()->after('modification_count');

    $table->index('booked_at');
    $table->index('last_modified_at');
});
```

#### 3.1.2 Call Model Enhancement

**Ensure Metadata Population**:
```php
// Required keys in linking_metadata array:
[
    'customer_id' => int,           // Customer ID if linked
    'customer_name' => string,      // Customer name from call
    'customer_phone' => string,     // Phone used in call
    'link_confidence' => float,     // 0.0-1.0 matching confidence
    'linked_at' => timestamp,       // When linkage was established
    'linked_by' => string,          // 'auto'|'manual'|'webhook'
]

// Required keys in metadata array:
[
    'appointment_details' => [
        'appointment_id' => int,        // If appointment made
        'service_name' => string,       // Service booked
        'scheduled_time' => timestamp,  // When appointment is
        'staff_name' => string,         // Staff member
        'booking_confirmed' => bool,    // Confirmation status
    ],
    'call_context' => [
        'is_reschedule' => bool,        // Was this a reschedule call?
        'is_cancellation' => bool,      // Was this a cancellation?
        'original_appointment_id' => int, // If modifying existing
    ],
]
```

**New Methods**:
```php
/**
 * Ensure linking metadata is populated
 */
public function ensureLinkingMetadata(): void
{
    if (!$this->customer_id || empty($this->linking_metadata)) {
        $this->populateLinkingMetadata();
        $this->save();
    }
}

/**
 * Get appointment context summary
 */
public function getAppointmentContextAttribute(): ?array
{
    return $this->metadata['appointment_details'] ?? null;
}
```

#### 3.1.3 Customer Model Enhancement

**Add Timeline Method**:
```php
/**
 * Get complete customer timeline (calls + appointments + modifications)
 * Ordered chronologically
 */
public function getTimelineAttribute(): Collection
{
    $timeline = collect();

    // Add all calls
    $this->calls->each(function ($call) use ($timeline) {
        $timeline->push([
            'type' => 'call',
            'timestamp' => $call->created_at,
            'entity' => $call,
            'description' => "Call: {$call->session_outcome}",
        ]);
    });

    // Add all appointments
    $this->appointments->each(function ($appointment) use ($timeline) {
        $timeline->push([
            'type' => 'appointment',
            'timestamp' => $appointment->created_at,
            'entity' => $appointment,
            'description' => "Booked: {$appointment->service->name}",
        ]);

        // Add modifications
        $appointment->modifications->each(function ($mod) use ($timeline) {
            $timeline->push([
                'type' => 'modification',
                'timestamp' => $mod->created_at,
                'entity' => $mod,
                'description' => ucfirst($mod->modification_type),
            ]);
        });
    });

    return $timeline->sortBy('timestamp');
}
```

### 3.2 Data Consistency Rules

#### 3.2.1 MUST HAVE: Referential Integrity

| Rule | Enforcement | Validation |
|------|-------------|------------|
| All `Call.customer_id` must reference valid `Customer` | Foreign key constraint | Database |
| All `Call.appointment_id` must reference valid `Appointment` or be NULL | Foreign key constraint | Database |
| All `Appointment.customer_id` must reference valid `Customer` | Foreign key constraint | Database |
| All `Appointment.call_id` must reference valid `Call` or be NULL | Foreign key constraint | Database |
| All `AppointmentModification.appointment_id` must exist | Foreign key constraint | Database |
| All entities must have matching `company_id` | Application logic | Service layer |

#### 3.2.2 MUST HAVE: Multi-tenant Isolation

```php
// When creating Call → Appointment relationship:
if ($call->company_id !== $appointment->company_id) {
    throw new DataConsistencyException("Tenant mismatch");
}

// When linking Customer → Call:
if ($customer->company_id !== $call->company_id) {
    throw new DataConsistencyException("Tenant mismatch");
}
```

#### 3.2.3 SHOULD HAVE: Metadata Population

**Trigger Points**:
1. **Call Created** → Populate `linking_metadata` if customer identified
2. **Appointment Created** → Set `booked_at`, create initial timeline entry
3. **Appointment Modified** → Create `AppointmentModification` record, update `last_modified_at`
4. **Call Linked to Appointment** → Update both `metadata` fields with cross-reference

**Implementation**: Event listeners or model observers

```php
// Example Observer
class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        $appointment->update([
            'booked_at' => now(),
            'modification_count' => 0,
        ]);
    }

    public function updated(Appointment $appointment)
    {
        if ($appointment->isDirty(['starts_at', 'service_id', 'staff_id'])) {
            $appointment->increment('modification_count');
            $appointment->update(['last_modified_at' => now()]);
        }
    }
}
```

---

## 4. USER STORIES & ACCEPTANCE CRITERIA

### 4.1 Super Admin User Stories

#### US-SA-001: View Complete Customer History
**As a** Super Admin
**I want to** see a complete timeline of all customer interactions (calls + appointments + modifications)
**So that** I can understand customer engagement and resolve support issues

**Acceptance Criteria**:
- ✅ Customer detail view shows chronological timeline
- ✅ Timeline includes: all calls, all appointments, all modifications
- ✅ Each timeline entry shows: timestamp, type, description, status
- ✅ Timeline is paginated (50 items per page)
- ✅ Clicking timeline entry opens related entity detail
- ✅ Timeline loads within 500ms for customers with <100 events

**UI Components**:
- Filament `Infolist` with Timeline component
- Icons for each event type (📞 call, 📅 appointment, ✏️ modification)
- Color coding: green (completed), yellow (scheduled), red (cancelled)

#### US-SA-002: View Appointment Modification History
**As a** Super Admin
**I want to** see all modifications made to an appointment (reschedules, cancellations)
**So that** I can track policy compliance and fee enforcement

**Acceptance Criteria**:
- ✅ Appointment detail view shows "Modification History" section
- ✅ Each modification shows: date/time, type, who made it, within policy, fee charged
- ✅ Original booking time is clearly marked
- ✅ Timeline shows progression: Booked → Rescheduled → Cancelled
- ✅ Total modification count displayed
- ✅ Policy violations highlighted in red

**UI Components**:
- Filament `InfoSection` titled "Änderungsverlauf" (Modification History)
- Table with columns: Datum, Typ, Von, Gebühr, Status
- Badge for policy compliance (green = within, red = violation)

#### US-SA-003: View All Calls Related to Appointment
**As a** Super Admin
**I want to** see all calls related to a specific appointment (booking call + follow-up calls)
**So that** I can understand the full communication context

**Acceptance Criteria**:
- ✅ Appointment detail view shows "Related Calls" section
- ✅ Originating call (call that created appointment) is clearly marked
- ✅ All calls with `appointment_id` matching this appointment are shown
- ✅ Each call shows: timestamp, duration, outcome, transcript link
- ✅ Calls ordered chronologically
- ✅ Clicking call opens call detail view

**UI Components**:
- Filament Relation Manager or Infolist section
- Table columns: Zeit, Dauer, Status, Ergebnis, Aktion
- "Ursprungsanruf" badge for originating call

#### US-SA-004: Cross-reference Navigation
**As a** Super Admin
**I want to** navigate seamlessly between related entities (Call ↔ Customer ↔ Appointment)
**So that** I can investigate issues without manual searching

**Acceptance Criteria**:
- ✅ Call detail view has clickable links to: Customer, Appointment (if linked)
- ✅ Appointment detail view has clickable links to: Customer, Originating Call, Related Calls
- ✅ Customer detail view has links to all calls and appointments
- ✅ Links open in new tab (Cmd/Ctrl+Click)
- ✅ Links show preview tooltip on hover
- ✅ Broken links display "N/A" not error

**UI Components**:
- Filament `TextEntry` with `url()` helper
- Custom components for relationship navigation

### 4.2 Platform User (Tenant) Stories

#### US-PU-001: View My Customer Interactions
**As a** Platform User (Tenant)
**I want to** see complete history for my company's customers only
**So that** I can manage my customer relationships

**Acceptance Criteria**:
- ✅ All views automatically filtered by `company_id`
- ✅ Cannot see other tenants' data
- ✅ Same timeline view as Super Admin (US-SA-001)
- ✅ Same modification history (US-SA-002)
- ✅ Same related calls view (US-SA-003)
- ✅ Performance: <500ms load time

**Security**:
- ✅ Filament global scopes enforce tenant isolation
- ✅ Policy checks prevent cross-tenant access
- ✅ All queries include `where('company_id', auth()->user()->company_id)`

#### US-PU-002: Monitor Appointment Changes
**As a** Platform User
**I want to** see when customers reschedule or cancel appointments
**So that** I can track no-show patterns and policy violations

**Acceptance Criteria**:
- ✅ Dashboard widget shows recent modifications (last 7 days)
- ✅ Widget shows: customer name, appointment time, modification type, fee charged
- ✅ Clicking widget item opens appointment detail
- ✅ Red badge for policy violations
- ✅ Widget refreshes every 5 minutes
- ✅ Limited to 10 most recent items

**UI Components**:
- Filament Widget `AppointmentModificationsWidget`
- Table widget with real-time data
- Color-coded badges

### 4.3 System Stories (Technical Requirements)

#### US-SYS-001: Automatic Metadata Population
**As a** System
**I want to** automatically populate metadata when relationships are created
**So that** no manual intervention is required

**Acceptance Criteria**:
- ✅ When Call is created with `customer_id` → populate `linking_metadata`
- ✅ When Appointment is created → set `booked_at = now()`
- ✅ When Appointment is linked to Call → update both `metadata` fields
- ✅ When AppointmentModification is created → update Appointment `last_modified_at`
- ✅ All metadata operations logged for debugging
- ✅ Failed metadata updates do not block main transaction

**Implementation**:
- Model observers for automatic triggers
- Background jobs for heavy operations
- Transaction safety with rollback

#### US-SYS-002: Data Consistency Validation
**As a** System
**I want to** validate data consistency on save
**So that** invalid relationships cannot be created

**Acceptance Criteria**:
- ✅ Validate `company_id` matches across relationships
- ✅ Validate foreign keys reference existing records
- ✅ Prevent orphaned records (soft deletes preserve relationships)
- ✅ Validation errors return clear messages
- ✅ Validation runs before save (early failure)
- ✅ Background job audits consistency daily

**Implementation**:
- Custom validation rules
- Database constraints
- Scheduled audit command

---

## 5. UI/UX REQUIREMENTS

### 5.1 Filament Resource Enhancements

#### 5.1.1 CustomerResource Detail View

**File**: `/var/www/api-gateway/app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php`

**Sections**:

1. **Customer Information** (existing)
   - Basic details, contact info, status

2. **Activity Timeline** (NEW)
   - Component: Custom Infolist component
   - Data source: `$customer->timeline` attribute
   - Display: Vertical timeline with icons
   - Pagination: 50 items, infinite scroll
   - Filters: Type (calls|appointments|modifications), Date range

3. **Appointments** (existing, enhanced)
   - Show modification count badge
   - Color code by status
   - Quick actions: View Details, View Calls

4. **Calls** (existing, enhanced)
   - Show appointment link if exists
   - Badge for "Appointment Made"
   - Click to view appointment

5. **Statistics** (existing, enhanced)
   - Add: Last modification date
   - Add: Total modifications (last 30 days)
   - Add: Policy violation count

**Code Example**:
```php
InfoSection::make('Activity Timeline')
    ->schema([
        TextEntry::make('timeline')
            ->label('')
            ->formatStateUsing(function ($record) {
                return view('filament.components.customer-timeline', [
                    'events' => $record->timeline,
                ]);
            }),
    ])
    ->collapsible()
    ->collapsed(false),
```

#### 5.1.2 AppointmentResource Detail View

**File**: `/var/www/api-gateway/app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php`

**Sections**:

1. **Appointment Details** (existing)
   - Service, Customer, Staff, Time
   - Add: Booked At, Last Modified At

2. **Booking Information** (NEW)
   - Originating Call (clickable link)
   - Booking confirmation status
   - Booking method (phone|web|manual)

3. **Modification History** (NEW)
   - Table of all AppointmentModification records
   - Columns: Date, Type, Changed By, Fee, Policy Compliance
   - Empty state: "Keine Änderungen" (No modifications)

4. **Related Calls** (NEW)
   - Table of all calls with `appointment_id` = this
   - Columns: Date, Duration, Outcome, Transcript
   - Badge on originating call
   - Empty state: "Keine Anrufe" (No calls)

5. **Customer Information** (existing, enhanced)
   - Add link to customer detail
   - Add customer statistics preview

**Code Example**:
```php
InfoSection::make('Modification History')
    ->schema([
        TextEntry::make('modifications')
            ->label('Änderungen')
            ->formatStateUsing(function ($record) {
                if ($record->modifications->isEmpty()) {
                    return 'Keine Änderungen';
                }
                return view('filament.components.modification-history', [
                    'modifications' => $record->modifications,
                ]);
            }),
    ]),

InfoSection::make('Related Calls')
    ->schema([
        TextEntry::make('relatedCalls')
            ->label('Zugehörige Anrufe')
            ->formatStateUsing(function ($record) {
                return view('filament.components.related-calls', [
                    'calls' => $record->relatedCalls,
                    'originatingCall' => $record->originatingCall,
                ]);
            }),
    ]),
```

#### 5.1.3 CallResource Detail View

**File**: `/var/www/api-gateway/app/Filament/Resources/CallResource/Pages/ViewCall.php`

**Sections**:

1. **Call Information** (existing)
   - Duration, Status, Outcome

2. **Customer Linkage** (existing, enhanced)
   - Show linkage confidence score
   - Show linkage method (auto|manual)
   - Add "Re-link Customer" action if confidence low

3. **Appointment Context** (NEW)
   - If appointment was made: Show appointment details
   - Link to appointment detail view
   - Show appointment status
   - Show if this was a reschedule/cancellation call

4. **Metadata Debug** (Super Admin only)
   - Expandable JSON view of `metadata` and `linking_metadata`
   - Highlight missing required keys
   - "Rebuild Metadata" action button

**Code Example**:
```php
InfoSection::make('Appointment Context')
    ->schema([
        TextEntry::make('appointment.service.name')
            ->label('Service'),
        TextEntry::make('appointment.starts_at')
            ->label('Scheduled Time')
            ->dateTime('d.m.Y H:i'),
        TextEntry::make('appointment.status')
            ->label('Status')
            ->badge(),
        TextEntry::make('appointment')
            ->label('View Appointment')
            ->url(fn ($record) => $record->appointment
                ? AppointmentResource::getUrl('view', ['record' => $record->appointment])
                : null
            ),
    ])
    ->visible(fn ($record) => $record->appointment_made),
```

### 5.2 Custom Blade Components

#### 5.2.1 Customer Timeline Component
**File**: `/var/www/api-gateway/resources/views/filament/components/customer-timeline.blade.php`

**Design**:
- Vertical timeline with left-side icons
- Grouped by date (collapsible)
- Color-coded by event type
- Hoverable for details
- Mobile-responsive

**Props**:
- `$events` (Collection): Timeline events
- `$limit` (int): Max events to show

**Example**:
```blade
<div class="space-y-4">
    @foreach($events->groupBy(fn($e) => $e['timestamp']->format('Y-m-d')) as $date => $dayEvents)
        <div class="border-l-2 border-gray-300 pl-4">
            <h4 class="font-semibold text-gray-700">{{ $date }}</h4>
            @foreach($dayEvents as $event)
                <div class="flex items-start gap-3 mt-2">
                    <x-filament::icon
                        :icon="$event['icon']"
                        class="w-5 h-5 {{ $event['color'] }}"
                    />
                    <div>
                        <p class="text-sm font-medium">{{ $event['description'] }}</p>
                        <p class="text-xs text-gray-500">{{ $event['timestamp']->format('H:i') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>
```

#### 5.2.2 Modification History Component
**File**: `/var/www/api-gateway/resources/views/filament/components/modification-history.blade.php`

**Design**:
- Table format
- Timeline visualization
- Policy compliance badges
- Fee display

**Props**:
- `$modifications` (Collection): AppointmentModification records

#### 5.2.3 Related Calls Component
**File**: `/var/www/api-gateway/resources/views/filament/components/related-calls.blade.php`

**Design**:
- Table format
- Originating call badge
- Quick action buttons (View, Listen)
- Empty state illustration

**Props**:
- `$calls` (Collection): Call records
- `$originatingCall` (Call|null): The booking call

### 5.3 Dashboard Widgets

#### 5.3.1 Recent Modifications Widget
**File**: `/var/www/api-gateway/app/Filament/Widgets/RecentModificationsWidget.php`

**Purpose**: Show recent appointment changes for platform users

**Display**:
- Table widget
- Last 10 modifications
- Real-time updates (5min refresh)
- Click to open appointment

**Permissions**: Platform Users (tenant-scoped)

#### 5.3.2 Data Consistency Widget (Super Admin)
**File**: `/var/www/api-gateway/app/Filament/Widgets/DataConsistencyWidget.php`

**Purpose**: Show data integrity issues

**Metrics**:
- Calls without customer linkage
- Appointments without originating call
- Empty metadata fields count
- Tenant isolation violations

**Actions**:
- "Run Audit" button
- "Fix Issues" button (runs repair jobs)

---

## 6. PERFORMANCE REQUIREMENTS

### 6.1 Response Time Targets

| Operation | Target | Max Acceptable |
|-----------|--------|----------------|
| Customer detail page load | 300ms | 500ms |
| Appointment detail page load | 250ms | 400ms |
| Timeline component render | 200ms | 350ms |
| Dashboard widget refresh | 150ms | 300ms |
| Modification history load | 100ms | 200ms |

### 6.2 Database Query Optimization

#### 6.2.1 Required Indexes

```sql
-- Appointments
CREATE INDEX idx_appointments_booked_at ON appointments(booked_at);
CREATE INDEX idx_appointments_last_modified_at ON appointments(last_modified_at);
CREATE INDEX idx_appointments_call_id ON appointments(call_id);

-- AppointmentModifications
CREATE INDEX idx_appt_mods_created_at ON appointment_modifications(created_at);
CREATE INDEX idx_appt_mods_customer_id ON appointment_modifications(customer_id);
CREATE INDEX idx_appt_mods_type ON appointment_modifications(modification_type);

-- Calls
CREATE INDEX idx_calls_customer_id_created ON calls(customer_id, created_at);
CREATE INDEX idx_calls_appointment_id ON calls(appointment_id);
```

#### 6.2.2 Eager Loading Requirements

```php
// Customer detail view - MUST eager load
$customer = Customer::with([
    'calls' => fn($q) => $q->latest()->limit(50),
    'appointments.modifications',
    'appointments.service',
    'appointments.staff',
])->findOrFail($id);

// Appointment detail view - MUST eager load
$appointment = Appointment::with([
    'modifications.modifiedBy',
    'relatedCalls',
    'originatingCall',
    'customer',
    'service',
    'staff',
])->findOrFail($id);

// Timeline generation - use chunking for large datasets
$timeline = $customer->calls()->latest()->limit(100)->get()
    ->merge($customer->appointments()->latest()->limit(100)->get());
```

### 6.3 Caching Strategy

**Cache Keys**:
- `customer.timeline.{id}` - Customer timeline (5min TTL)
- `appointment.modifications.{id}` - Modification history (10min TTL)
- `dashboard.recent_modifications` - Widget data (5min TTL)

**Cache Invalidation**:
- Clear on model update (Observer pattern)
- Clear on relationship changes
- Manual clear action for admins

**Implementation**:
```php
public function getTimelineAttribute(): Collection
{
    return Cache::remember(
        "customer.timeline.{$this->id}",
        now()->addMinutes(5),
        fn() => $this->buildTimeline()
    );
}
```

### 6.4 Database Performance Budget

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| Timeline query time | <50ms | >100ms |
| Modification history query | <30ms | >50ms |
| Related calls query | <40ms | >80ms |
| Relationship eager load | <100ms | >200ms |
| Widget data fetch | <80ms | >150ms |

---

## 7. SUCCESS METRICS

### 7.1 Data Quality Metrics

| Metric | Current | Target | Measurement |
|--------|---------|--------|-------------|
| Calls with populated `linking_metadata` | Unknown | >95% | Daily audit |
| Appointments with `booked_at` set | Unknown | 100% | Daily audit |
| Appointments with modification history | Unknown | 100% (if modified) | Daily audit |
| Orphaned relationships | Unknown | 0 | Weekly audit |
| Tenant isolation violations | 0 | 0 | Continuous monitoring |

### 7.2 User Adoption Metrics

| Metric | Target | Measurement Period |
|--------|--------|-------------------|
| Super Admins using timeline view | >80% | 30 days post-launch |
| Platform Users viewing modification history | >60% | 30 days post-launch |
| Support ticket reduction (data questions) | -30% | 90 days post-launch |
| User satisfaction score | >4.0/5.0 | 60 days post-launch |

### 7.3 Performance Metrics

| Metric | Target | Alert Condition |
|--------|--------|-----------------|
| P95 page load time | <500ms | >800ms |
| Database query count (per page) | <15 | >30 |
| Cache hit rate | >85% | <70% |
| Failed metadata population | <1% | >5% |

### 7.4 Business Impact Metrics

| Metric | Expected Impact | Measurement |
|--------|----------------|-------------|
| Time to resolve customer inquiry | -40% | Support ticket timestamps |
| Data investigation time | -50% | Admin user feedback |
| Billing dispute resolution time | -60% | Finance team feedback |
| Customer satisfaction (visibility) | +20% | Survey responses |

---

## 8. TECHNICAL CONSTRAINTS

### 8.1 Technology Stack
- **Backend**: Laravel 11.x
- **Admin Panel**: Filament 3.x
- **Database**: MySQL 8.0+ (InnoDB)
- **Cache**: Redis 7.x
- **Queue**: Redis (Laravel Horizon)

### 8.2 Multi-tenancy Requirements
- All queries MUST filter by `company_id`
- Global scopes enforce tenant isolation
- Filament policies validate tenant access
- No cross-tenant data leakage allowed

### 8.3 Security Requirements
- Super Admin sees all tenants (with explicit context indicator)
- Platform Users see only their `company_id` data
- Audit log for all data access (GDPR compliance)
- Sensitive metadata fields encrypted at rest

### 8.4 Scalability Requirements
- Support 10,000+ customers per tenant
- Support 100,000+ calls per tenant
- Timeline pagination required (not full load)
- Background jobs for heavy operations

---

## 9. IMPLEMENTATION PHASES

### Phase 1: Data Model Foundation (Priority: HIGH)
**Duration**: 3 days

**Tasks**:
1. Add missing Appointment relationships (`modifications()`, `relatedCalls()`)
2. Create migration for new Appointment fields (`booked_at`, etc.)
3. Implement model observers for automatic metadata population
4. Add validation rules for data consistency
5. Create audit command for existing data

**Deliverables**:
- ✅ Migration files
- ✅ Updated models with relationships
- ✅ Observer classes
- ✅ Validation rule classes
- ✅ Audit command

**Acceptance**:
- All tests pass
- Existing data migrated (booked_at populated)
- No broken relationships

### Phase 2: UI Components (Priority: HIGH)
**Duration**: 4 days

**Tasks**:
1. Create Customer timeline component
2. Create Modification history component
3. Create Related calls component
4. Update CustomerResource detail view
5. Update AppointmentResource detail view
6. Update CallResource detail view

**Deliverables**:
- ✅ Blade components
- ✅ Updated Filament resources
- ✅ Infolist configurations

**Acceptance**:
- All views render without errors
- Timeline shows correct data
- Links navigate correctly
- Mobile responsive

### Phase 3: Performance Optimization (Priority: MEDIUM)
**Duration**: 2 days

**Tasks**:
1. Add database indexes
2. Implement eager loading
3. Add caching layer
4. Optimize timeline queries
5. Performance testing

**Deliverables**:
- ✅ Index migration
- ✅ Cached query methods
- ✅ Performance test results

**Acceptance**:
- P95 load time <500ms
- N+1 queries eliminated
- Cache hit rate >85%

### Phase 4: Dashboard Widgets (Priority: LOW)
**Duration**: 2 days

**Tasks**:
1. Create Recent Modifications widget
2. Create Data Consistency widget
3. Add real-time updates
4. Configure permissions

**Deliverables**:
- ✅ Widget classes
- ✅ Real-time polling setup
- ✅ Permission policies

**Acceptance**:
- Widgets display correct data
- Tenant scoping works
- Refresh works

### Phase 5: Testing & Documentation (Priority: MEDIUM)
**Duration**: 2 days

**Tasks**:
1. Feature tests for all user stories
2. Performance benchmarks
3. User documentation
4. Admin training materials

**Deliverables**:
- ✅ Test suite (>80% coverage)
- ✅ User guide
- ✅ Training slides

**Acceptance**:
- All tests pass
- Documentation complete
- Training delivered

**Total Estimated Duration**: 13 days

---

## 10. TESTING REQUIREMENTS

### 10.1 Unit Tests

**Coverage Target**: >80%

**Critical Tests**:
```php
// test/Unit/Models/AppointmentTest.php
public function test_modifications_relationship_returns_correct_records()
public function test_booked_at_set_on_creation()
public function test_modification_count_increments()
public function test_timeline_summary_populates()

// test/Unit/Models/CallTest.php
public function test_linking_metadata_populates_on_customer_link()
public function test_appointment_context_returns_correct_data()

// test/Unit/Models/CustomerTest.php
public function test_timeline_includes_all_event_types()
public function test_timeline_ordered_chronologically()
```

### 10.2 Feature Tests

**User Story Coverage**: 100%

**Critical Tests**:
```php
// test/Feature/Filament/CustomerDetailTest.php
public function test_super_admin_can_view_complete_timeline()
public function test_platform_user_only_sees_own_company_data()
public function test_timeline_pagination_works()
public function test_timeline_performance_under_500ms()

// test/Feature/Filament/AppointmentDetailTest.php
public function test_modification_history_displays_all_changes()
public function test_related_calls_section_shows_correct_calls()
public function test_originating_call_marked_correctly()

// test/Feature/DataConsistencyTest.php
public function test_tenant_isolation_enforced()
public function test_metadata_populated_on_relationship_creation()
public function test_audit_command_finds_inconsistencies()
```

### 10.3 Performance Tests

**Load Scenarios**:
1. Customer with 1,000 events → Timeline load <500ms
2. Appointment with 50 modifications → History load <200ms
3. 100 concurrent users → No degradation
4. Dashboard with 10 widgets → Load <1s

**Tools**: Laravel Telescope, Clockwork, Apache Bench

### 10.4 Manual Testing Checklist

**Super Admin**:
- [ ] Can view all tenants' data
- [ ] Timeline shows all event types
- [ ] Modification history accurate
- [ ] Related calls correct
- [ ] Cross-reference navigation works
- [ ] Data consistency widget shows issues

**Platform User**:
- [ ] Only sees own company data
- [ ] Cannot access other tenants
- [ ] Same features as Super Admin (scoped)
- [ ] Dashboard widgets work
- [ ] Performance acceptable

**Edge Cases**:
- [ ] Customer with no calls
- [ ] Appointment with no modifications
- [ ] Call without customer
- [ ] Empty timeline
- [ ] Missing metadata fields
- [ ] Deleted relationships

---

## 11. ROLLOUT PLAN

### 11.1 Pre-deployment

**Data Migration**:
1. Run audit command to identify gaps
2. Backfill `booked_at` for existing appointments
3. Populate missing metadata for calls
4. Validate data integrity

**Stakeholder Communication**:
1. Notify Super Admins of new features
2. Provide user guide
3. Schedule training session
4. Set expectations for performance improvements

### 11.2 Deployment

**Strategy**: Blue-Green Deployment

**Steps**:
1. Deploy to staging environment
2. Run smoke tests
3. Performance validation
4. Deploy to production (off-peak hours)
5. Monitor error rates
6. Enable new UI sections gradually (feature flags)

**Rollback Plan**:
- Database migrations are reversible
- Feature flags can disable new UI
- Cache can be cleared if issues
- Rollback window: 2 hours

### 11.3 Post-deployment

**Monitoring** (First 48 hours):
- Error rate (target: <0.1%)
- Page load times (target: <500ms P95)
- Database query performance
- Cache hit rate
- User adoption (page views)

**Success Criteria**:
- ✅ No critical errors
- ✅ Performance targets met
- ✅ User feedback positive
- ✅ Data quality >95%

**Support**:
- Dedicated support channel (Slack)
- Hotfix team on standby
- Daily check-ins with users

---

## 12. APPENDICES

### Appendix A: Database Schema Additions

```sql
-- Migration: Add timeline fields to appointments
ALTER TABLE appointments
ADD COLUMN booked_at TIMESTAMP NULL AFTER created_at,
ADD COLUMN last_modified_at TIMESTAMP NULL,
ADD COLUMN modification_count INT DEFAULT 0,
ADD COLUMN timeline_summary JSON NULL,
ADD INDEX idx_appointments_booked_at (booked_at),
ADD INDEX idx_appointments_last_modified_at (last_modified_at);

-- Add index for performance
CREATE INDEX idx_calls_customer_created
ON calls(customer_id, created_at);

CREATE INDEX idx_appt_mods_created
ON appointment_modifications(created_at);
```

### Appendix B: Metadata Schema Examples

**Call.linking_metadata**:
```json
{
  "customer_id": 123,
  "customer_name": "Max Mustermann",
  "customer_phone": "+49123456789",
  "link_confidence": 0.95,
  "linked_at": "2025-10-10T10:30:00Z",
  "linked_by": "auto",
  "matching_criteria": ["phone", "name"]
}
```

**Call.metadata.appointment_details**:
```json
{
  "appointment_id": 456,
  "service_name": "Haarschnitt",
  "scheduled_time": "2025-10-15T14:00:00Z",
  "staff_name": "Anna Schmidt",
  "booking_confirmed": true,
  "is_reschedule": false,
  "original_appointment_id": null
}
```

**Appointment.timeline_summary** (cached):
```json
{
  "booked_at": "2025-10-10T10:30:00Z",
  "booked_via": "phone_call",
  "modifications": [
    {
      "type": "reschedule",
      "at": "2025-10-12T09:00:00Z",
      "by": "customer",
      "from": "2025-10-15T14:00:00Z",
      "to": "2025-10-16T16:00:00Z"
    }
  ],
  "total_modifications": 1,
  "last_modified_at": "2025-10-12T09:00:00Z"
}
```

### Appendix C: Technical Debt & Future Enhancements

**Known Limitations**:
1. Timeline pagination is client-side (should be server-side for very large datasets)
2. No real-time WebSocket updates (uses polling)
3. Timeline does not include customer notes (future enhancement)
4. No export functionality for timeline data

**Future Enhancements**:
1. **Unified Activity Log**: Polymorphic `activities` table for all events
2. **Real-time Updates**: WebSocket/Pusher for live timeline updates
3. **Advanced Filtering**: Filter timeline by event type, date range, staff member
4. **Export**: PDF/CSV export of customer timeline
5. **AI Insights**: Analyze timeline for patterns (e.g., "customer reschedules often on Mondays")
6. **Notifications**: Alert admins when policy violations occur

---

## DOCUMENT CONTROL

**Version**: 1.0
**Status**: Draft for Review
**Author**: Claude (AI Requirements Analyst)
**Review Required**: Product Owner, Tech Lead, UX Designer
**Next Steps**: Review → Refinement → Implementation Planning

**Change Log**:
- 2025-10-10: Initial draft created from vague requirements
