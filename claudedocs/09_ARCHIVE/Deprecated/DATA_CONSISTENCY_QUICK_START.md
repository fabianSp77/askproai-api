# Data Consistency Implementation - Quick Start Guide

**Read this first**: This is the TL;DR version of the full specification.
**Full Spec**: `/var/www/api-gateway/claudedocs/DATA_CONSISTENCY_SPECIFICATION.md`

---

## WHAT TO BUILD

Transform vague requirement: *"Daten smart und konsistent ablegen, Anrufe und Termine verknüpfen, Historie anzeigen"*

Into: **Complete relationship tracking + historical timeline views in Filament admin**

---

## CURRENT STATE → TARGET STATE

### Current Issues
- ❌ Appointments don't show "when booked, when rescheduled"
- ❌ Can't see "all calls related to this appointment"
- ❌ Call metadata often empty (linking_metadata, appointment context)
- ❌ No unified timeline view for customers

### After Implementation
- ✅ Complete appointment history timeline (booked → rescheduled → cancelled)
- ✅ All calls related to appointment visible in detail view
- ✅ Automatic metadata population on relationship creation
- ✅ Customer timeline showing all interactions chronologically
- ✅ Works for Super Admins (all tenants) AND Platform Users (own tenant only)

---

## 5 MINUTE ARCHITECTURE OVERVIEW

### Data Flow
```
Customer
  ├── Calls (hasMany)
  │   ├── Call.linking_metadata → Customer linkage info
  │   └── Call.metadata.appointment_details → Appointment context
  │
  └── Appointments (hasMany)
      ├── Appointment.call_id → Originating call
      ├── Appointment.modifications (hasMany) → History of changes
      │   └── AppointmentModification: cancel/reschedule records
      └── Appointment.relatedCalls (hasMany via appointment_id)
```

### Missing Relationships (TO ADD)
```php
// Appointment.php
public function modifications(): HasMany // ← ADD THIS
public function relatedCalls(): HasMany  // ← ADD THIS
public function originatingCall(): BelongsTo // ← ADD THIS
```

### Missing Fields (TO ADD)
```sql
-- appointments table
ALTER TABLE appointments
ADD COLUMN booked_at TIMESTAMP,           -- When originally created
ADD COLUMN last_modified_at TIMESTAMP,    -- Last change timestamp
ADD COLUMN modification_count INT DEFAULT 0; -- Total modifications
```

---

## PRIORITY 1: DATA MODEL (MUST HAVE)

### Step 1: Add Appointment Relationships
**File**: `app/Models/Appointment.php`

```php
/**
 * All modifications (cancellations, reschedules) for this appointment
 */
public function modifications(): HasMany
{
    return $this->hasMany(AppointmentModification::class)
        ->orderBy('created_at', 'desc');
}

/**
 * All calls related to this appointment
 */
public function relatedCalls(): HasMany
{
    return $this->hasMany(Call::class, 'appointment_id');
}

/**
 * The originating call that created this appointment
 */
public function originatingCall(): BelongsTo
{
    return $this->belongsTo(Call::class, 'call_id');
}
```

### Step 2: Create Migration
**Command**: `php artisan make:migration add_timeline_fields_to_appointments`

```php
public function up()
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->timestamp('booked_at')->nullable()->after('created_at');
        $table->timestamp('last_modified_at')->nullable();
        $table->integer('modification_count')->default(0);

        $table->index('booked_at');
        $table->index('last_modified_at');
    });
}
```

### Step 3: Create Observer for Auto-Population
**File**: `app/Observers/AppointmentObserver.php`

```php
class AppointmentObserver
{
    public function created(Appointment $appointment)
    {
        $appointment->update(['booked_at' => now()]);
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

**Register**: `app/Providers/AppServiceProvider.php`
```php
public function boot()
{
    Appointment::observe(AppointmentObserver::class);
}
```

---

## PRIORITY 2: UI VIEWS (MUST HAVE)

### Customer Detail View
**File**: `app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php`

Add "Activity Timeline" section showing:
- All calls (📞)
- All appointments (📅)
- All modifications (✏️)
- Ordered chronologically

### Appointment Detail View
**File**: `app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php`

Add 2 new sections:

1. **Modification History**
   - Show all `AppointmentModification` records
   - Table: Date | Type | Changed By | Fee | Policy Compliance

2. **Related Calls**
   - Show all calls with `appointment_id = this`
   - Badge on originating call
   - Clickable links to call detail

### Call Detail View
**File**: `app/Filament/Resources/CallResource/Pages/ViewCall.php`

Add **Appointment Context** section:
- If `appointment_made = true`:
  - Show appointment details
  - Link to appointment
  - Show appointment status

---

## USER STORIES (TESTABLE)

### US-001: View Complete Customer History
**As a** Super Admin
**I want to** see chronological timeline of all customer interactions
**So that** I can understand customer engagement

**Acceptance**:
- ✅ Timeline shows calls + appointments + modifications
- ✅ Ordered by timestamp (newest first)
- ✅ Each entry clickable to detail view
- ✅ Loads in <500ms

### US-002: View Appointment Modification History
**As a** Super Admin
**I want to** see all changes to an appointment
**So that** I can track policy compliance

**Acceptance**:
- ✅ Shows when booked, when rescheduled, when cancelled
- ✅ Shows who made each change
- ✅ Shows fees charged
- ✅ Highlights policy violations in red

### US-003: View All Calls for Appointment
**As a** Super Admin
**I want to** see all calls related to an appointment
**So that** I can understand communication context

**Acceptance**:
- ✅ Shows originating call (call that created appointment)
- ✅ Shows all follow-up calls (same appointment_id)
- ✅ Each call shows: time, duration, outcome, transcript link

### US-004: Platform User Tenant Isolation
**As a** Platform User (Tenant)
**I want to** see same views but only for my company
**So that** I can manage my customers

**Acceptance**:
- ✅ All views filtered by `company_id`
- ✅ Cannot see other tenants' data
- ✅ Same performance (<500ms)

---

## PERFORMANCE REQUIREMENTS

### Response Time Targets
| View | Target | Max |
|------|--------|-----|
| Customer detail | 300ms | 500ms |
| Appointment detail | 250ms | 400ms |
| Timeline render | 200ms | 350ms |

### Required Indexes
```sql
CREATE INDEX idx_appointments_call_id ON appointments(call_id);
CREATE INDEX idx_calls_appointment_id ON calls(appointment_id);
CREATE INDEX idx_appt_mods_created ON appointment_modifications(created_at);
CREATE INDEX idx_calls_customer_created ON calls(customer_id, created_at);
```

### Eager Loading (REQUIRED)
```php
// Customer detail - MUST eager load to avoid N+1
$customer = Customer::with([
    'calls' => fn($q) => $q->latest()->limit(50),
    'appointments.modifications',
    'appointments.service',
])->findOrFail($id);

// Appointment detail - MUST eager load
$appointment = Appointment::with([
    'modifications',
    'relatedCalls',
    'originatingCall',
    'customer',
])->findOrFail($id);
```

---

## SUCCESS METRICS

### Data Quality
- **Target**: >95% of calls have populated `linking_metadata`
- **Target**: 100% of appointments have `booked_at` set
- **Target**: 0 orphaned relationships

### Performance
- **Target**: P95 page load <500ms
- **Target**: <15 database queries per page
- **Target**: >85% cache hit rate

### User Adoption
- **Target**: 80% of Super Admins use timeline view (30 days)
- **Target**: 60% of Platform Users view modification history (30 days)
- **Target**: -30% support tickets about data questions (90 days)

---

## IMPLEMENTATION PHASES

### Phase 1: Data Model (3 days) - START HERE
1. Add Appointment relationships
2. Create migration
3. Create observer
4. Backfill `booked_at` for existing appointments
5. Write unit tests

**Command to run**:
```bash
php artisan make:migration add_timeline_fields_to_appointments
php artisan make:observer AppointmentObserver --model=Appointment
```

### Phase 2: UI Components (4 days)
1. Customer timeline component
2. Modification history component
3. Related calls component
4. Update Filament resources

### Phase 3: Performance (2 days)
1. Add indexes
2. Implement eager loading
3. Add caching
4. Performance tests

### Phase 4: Testing (2 days)
1. Feature tests for all user stories
2. Performance benchmarks
3. User documentation

**Total**: 11 days

---

## QUICK VALIDATION CHECKLIST

Before marking complete, verify:

**Data Model**:
- [ ] `Appointment::modifications()` relationship works
- [ ] `Appointment::relatedCalls()` relationship works
- [ ] `booked_at` auto-populated on creation
- [ ] Observer updates `last_modified_at` on changes

**UI Views**:
- [ ] Customer detail shows complete timeline
- [ ] Appointment detail shows modification history
- [ ] Appointment detail shows related calls
- [ ] Call detail shows appointment context
- [ ] All links navigate correctly

**Performance**:
- [ ] Customer detail loads <500ms
- [ ] No N+1 query issues
- [ ] Proper indexes in place
- [ ] Eager loading configured

**Security**:
- [ ] Platform Users only see own tenant data
- [ ] Super Admin sees all (with context)
- [ ] No cross-tenant leakage

**Testing**:
- [ ] Unit tests pass (>80% coverage)
- [ ] Feature tests for all user stories pass
- [ ] Performance tests meet targets

---

## FILES TO MODIFY

### Models
- `app/Models/Appointment.php` - Add 3 relationships
- `app/Models/Customer.php` - Add timeline helper method (optional)

### Migrations
- `database/migrations/YYYY_MM_DD_add_timeline_fields_to_appointments.php` - New fields

### Observers
- `app/Observers/AppointmentObserver.php` - Auto-populate booked_at

### Filament Resources
- `app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php` - Add timeline
- `app/Filament/Resources/AppointmentResource/Pages/ViewAppointment.php` - Add history + calls
- `app/Filament/Resources/CallResource/Pages/ViewCall.php` - Add appointment context

### Blade Components (NEW)
- `resources/views/filament/components/customer-timeline.blade.php`
- `resources/views/filament/components/modification-history.blade.php`
- `resources/views/filament/components/related-calls.blade.php`

### Tests (NEW)
- `tests/Feature/Filament/CustomerTimelineTest.php`
- `tests/Feature/Filament/AppointmentHistoryTest.php`
- `tests/Unit/Models/AppointmentRelationshipsTest.php`

---

## NEXT STEPS

1. **Review**: Share full spec with Product Owner and Tech Lead
2. **Estimate**: Validate 11-day estimate with team
3. **Prioritize**: Confirm priority (suggested: HIGH)
4. **Plan**: Schedule Phase 1 (data model) for next sprint
5. **Implement**: Start with `Appointment` model changes + migration

---

## QUESTIONS FOR STAKEHOLDERS

Before starting implementation:

1. **Scope**: Should timeline include customer notes? (Not in current spec)
2. **Performance**: Is 500ms acceptable for customers with 1000+ events?
3. **History Depth**: How far back should timeline go? (Current: all history)
4. **Export**: Do users need PDF/CSV export of timeline? (Future enhancement)
5. **Real-time**: Should modifications appear without page refresh? (Current: manual refresh)

---

**Full Specification**: `/var/www/api-gateway/claudedocs/DATA_CONSISTENCY_SPECIFICATION.md` (70+ pages)
**Status**: Ready for review and refinement
**Confidence**: HIGH - All requirements clearly defined and testable
