# Service-Staff Assignment - Phase 2 Design

**Status:** 📋 Planned (Not Yet Implemented)
**Trigger:** When business requires service-specific staff restrictions
**Estimated Effort:** 2-3 days

---

## Business Requirements (User Input)

> ">90% der Services werden von allen Mitarbeitern gemacht, es gibt aber auch häufiger Unterscheidungen nach Herren und Damen (wer macht nur Herren oder nur Damen oder beide) und natürlich wer kann welche Dienstleistung"

### Use Cases

1. **Gender-based Restriction**
   - "Herrenhaarschnitt → nur männliche Mitarbeiter"
   - "Damenhaarschnitt → nur weibliche Mitarbeiter"
   - "Balayage → alle Mitarbeiter"

2. **Skill-based Restriction**
   - "Dauerwelle → nur Fabian & Emma (spezialisiert)"
   - "Ansatzfärbung → alle außer Tom (Lehrling)"
   - "Highlights → nur Senior-Friseure"

3. **Scheduling Type Variations**
   - "Schnellschnitt → ROUND_ROBIN (gleichmäßige Verteilung)"
   - "Komplexe Färbung → MANAGED (manuell zuweisen)"
   - "Beratung → COLLECTIVE (beide Friseure zusammen)"

---

## Database Schema Changes

### 1. New Table: `service_staff`

**Purpose:** Maps which staff members can perform which services

```sql
CREATE TABLE service_staff (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    service_id CHAR(36) NOT NULL,
    staff_id CHAR(36) NOT NULL,
    priority INTEGER DEFAULT 0,  -- For ROUND_ROBIN weighting (higher = more bookings)
    mandatory BOOLEAN DEFAULT FALSE,  -- For COLLECTIVE events (must be present)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,

    UNIQUE KEY unique_service_staff (service_id, staff_id),
    INDEX idx_service (service_id),
    INDEX idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Example Data:**
```sql
-- Herrenhaarschnitt (Service 42) → nur Fabian & Tom
INSERT INTO service_staff (service_id, staff_id, priority) VALUES
(42, 'fabian-uuid', 10),  -- Higher priority = more bookings
(42, 'tom-uuid', 5);

-- Balayage (Service 189) → alle Mitarbeiter (keine Einträge = default)
-- (Empty = all staff)
```

### 2. Extend `services` Table

```sql
ALTER TABLE services
ADD COLUMN scheduling_type VARCHAR(20) DEFAULT 'MANAGED'
    COMMENT 'MANAGED, ROUND_ROBIN, COLLECTIVE',
ADD COLUMN gender_restriction VARCHAR(10) NULL
    COMMENT 'male, female, NULL (all)',
ADD COLUMN require_senior_level BOOLEAN DEFAULT FALSE
    COMMENT 'Only staff with is_senior=true can perform this service';
```

### 3. Extend `staff` Table (if not exists)

```sql
ALTER TABLE staff
ADD COLUMN gender VARCHAR(10) NULL
    COMMENT 'male, female, other',
ADD COLUMN is_senior BOOLEAN DEFAULT FALSE
    COMMENT 'Senior-level staff member',
ADD COLUMN specializations TEXT NULL
    COMMENT 'JSON array of specialization codes';
```

**Example Staff Data:**
```json
{
  "id": "fabian-uuid",
  "name": "Fabian Spitzer",
  "gender": "male",
  "is_senior": true,
  "specializations": ["highlights", "balayage", "perm"]
}
```

---

## Application Logic

### CalcomEventTypeManager - Enhanced Host Selection

**File:** `app/Services/CalcomEventTypeManager.php`

```php
/**
 * Get hosts for a service based on restrictions
 *
 * Priority:
 * 1. service_staff table (explicit assignment)
 * 2. gender_restriction filter
 * 3. require_senior_level filter
 * 4. Default: assignAllTeamMembers
 */
private function getHostsForService(Service $service): array
{
    // Option 1: Explicit service-staff assignments
    $serviceStaff = DB::table('service_staff')
        ->where('service_id', $service->id)
        ->get();

    if ($serviceStaff->isNotEmpty()) {
        // Map to Cal.com hosts format
        return $serviceStaff->map(function ($ss) {
            $staff = Staff::find($ss->staff_id);
            return [
                'userId' => $staff->calcom_user_id,
                'mandatory' => (bool) $ss->mandatory,
                'priority' => $ss->priority > 0 ? ($ss->priority > 5 ? 'high' : 'medium') : 'lowest'
            ];
        })->toArray();
    }

    // Option 2: Gender or seniority restrictions
    $query = Staff::where('company_id', $service->company_id)
        ->where('active', true)
        ->whereNotNull('calcom_user_id');

    if ($service->gender_restriction) {
        $query->where('gender', $service->gender_restriction);
    }

    if ($service->require_senior_level) {
        $query->where('is_senior', true);
    }

    $filteredStaff = $query->get();

    if ($filteredStaff->isNotEmpty()) {
        return $filteredStaff->map(function ($staff) {
            return [
                'userId' => $staff->calcom_user_id,
                'mandatory' => false,
                'priority' => $staff->is_senior ? 'high' : 'medium'
            ];
        })->toArray();
    }

    // Option 3: Default - assign all team members
    return ['assignAllTeamMembers' => true];
}

/**
 * Create segment event types with intelligent host assignment
 */
public function createSegmentEventTypes(Service $service): array
{
    // ... existing validation ...

    foreach ($service->segments as $segment) {
        // Get hosts for this service
        $hostsConfig = $this->getHostsForService($service);

        // Create Event Type with host config
        $response = $this->calcom->createEventType([
            'name' => $this->generateEventTypeName($service, $segment),
            'description' => "Segment {$segment['name']} für {$service->name}",
            'duration' => $segment['duration'],
            'schedulingType' => $service->scheduling_type ?? 'MANAGED',

            // Host assignment (new!)
            ...$hostsConfig  // Spreads assignAllTeamMembers OR hosts array
        ]);

        // ... rest of creation logic ...
    }
}
```

---

## Filament UI Changes

### ServiceResource - Form Fields

**File:** `app/Filament/Resources/ServiceResource.php`

```php
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

public static function form(Form $form): Form
{
    return $form->schema([
        // ... existing fields ...

        Section::make('Mitarbeiter-Zuweisung')
            ->schema([
                CheckboxList::make('staff_ids')
                    ->label('Welche Mitarbeiter können diesen Service durchführen?')
                    ->options(function () {
                        return Staff::where('company_id', auth()->user()->company_id)
                            ->where('active', true)
                            ->pluck('name', 'id');
                    })
                    ->helperText('Leer lassen = alle Mitarbeiter können diesen Service durchführen')
                    ->columns(2),

                Select::make('gender_restriction')
                    ->label('Geschlechts-Einschränkung')
                    ->options([
                        'male' => 'Nur männliche Mitarbeiter',
                        'female' => 'Nur weibliche Mitarbeiter',
                    ])
                    ->nullable()
                    ->helperText('Optional: Service nur für bestimmtes Geschlecht'),

                Toggle::make('require_senior_level')
                    ->label('Nur Senior-Mitarbeiter')
                    ->helperText('Service erfordert Senior-Level Friseur'),

                Select::make('scheduling_type')
                    ->label('Scheduling Type')
                    ->options([
                        'MANAGED' => 'Managed (manuelle Zuweisung)',
                        'ROUND_ROBIN' => 'Round Robin (gleichmäßige Verteilung)',
                        'COLLECTIVE' => 'Collective (alle Mitarbeiter gemeinsam)',
                    ])
                    ->default('MANAGED')
                    ->helperText('Wie sollen Buchungen auf Mitarbeiter verteilt werden?'),
            ])
            ->collapsible()
            ->collapsed(fn ($record) => $record === null), // Collapsed for new services
    ]);
}
```

### Save Logic

**File:** `app/Filament/Resources/ServiceResource/Pages/EditService.php`

```php
protected function mutateFormDataBeforeSave(array $data): array
{
    // Extract staff_ids for separate table
    if (isset($data['staff_ids'])) {
        $this->staffIds = $data['staff_ids'];
        unset($data['staff_ids']); // Don't save to services table
    }

    return $data;
}

protected function afterSave(): void
{
    // ... existing Cal.com sync logic ...

    // Sync service_staff assignments
    if (isset($this->staffIds)) {
        DB::table('service_staff')
            ->where('service_id', $this->record->id)
            ->delete();

        foreach ($this->staffIds as $staffId) {
            DB::table('service_staff')->insert([
                'id' => Str::uuid(),
                'service_id' => $this->record->id,
                'staff_id' => $staffId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // Refresh Cal.com Event Types with new host assignments
    if ($this->record->composite && !empty($this->record->segments)) {
        $manager = new CalcomEventTypeManager($this->record->company);
        $manager->updateSegmentEventTypes($this->record);
    }
}
```

---

## Migration Path

### Step 1: Database Migrations

```bash
php artisan make:migration create_service_staff_table
php artisan make:migration add_scheduling_fields_to_services
php artisan make:migration add_staff_attributes
php artisan migrate
```

### Step 2: Seed Default Data

```php
// Optional: Mark existing senior staff
DB::table('staff')
    ->whereIn('name', ['Fabian Spitzer', 'Emma Williams'])
    ->update(['is_senior' => true]);
```

### Step 3: Update CalcomEventTypeManager

Implement `getHostsForService()` method as shown above.

### Step 4: Update ServiceResource

Add new form fields for staff assignment.

### Step 5: Test

```php
// Create test service with restrictions
$service = Service::create([
    'name' => 'Test Herrenhaarschnitt',
    'gender_restriction' => 'male',
    'scheduling_type' => 'ROUND_ROBIN',
]);

// Assign specific staff
DB::table('service_staff')->insert([
    ['service_id' => $service->id, 'staff_id' => 'fabian-uuid'],
    ['service_id' => $service->id, 'staff_id' => 'tom-uuid'],
]);

// Trigger Cal.com sync
$manager = new CalcomEventTypeManager($company);
$manager->createSegmentEventTypes($service);

// Verify Cal.com has correct hosts
```

---

## Backwards Compatibility

**Phase 1 Code (Current):**
```php
$payload['assignAllTeamMembers'] = true;  // Always true
```

**Phase 2 Code (Future):**
```php
$hostsConfig = $this->getHostsForService($service);
// Returns either:
// - ['assignAllTeamMembers' => true]  ← Default behavior (90% of services)
// - ['hosts' => [...]]                 ← Specific staff (10% of services)
```

**Impact:** Phase 1 services continue working. Phase 2 adds flexibility without breaking existing functionality.

---

## User Workflow Examples

### Example 1: Herrenhaarschnitt (nur Männer)

1. Admin öffnet "Herrenhaarschnitt" Service in Filament
2. Wählt "Geschlechts-Einschränkung: Nur männliche Mitarbeiter"
3. Speichert
4. System:
   - Filtert Staff: `WHERE gender = 'male'`
   - Findet: Fabian, Tom
   - Erstellt Cal.com Event Types mit `hosts: [Fabian, Tom]`
5. Result: Nur Fabian & Tom bekommen Buchungen für Herrenhaarschnitt

### Example 2: Dauerwelle (Spezialisierung)

1. Admin öffnet "Dauerwelle" Service
2. Wählt unter "Mitarbeiter": Nur Fabian & Emma (Haken setzen)
3. Setzt "Scheduling Type: Round Robin"
4. Speichert
5. System:
   - Erstellt `service_staff` Einträge für Fabian & Emma
   - Erstellt Cal.com Event Types mit `hosts: [Fabian, Emma]`
   - `schedulingType: ROUND_ROBIN`
6. Result: Dauerwellen-Buchungen rotieren zwischen Fabian & Emma

### Example 3: Standard-Service (alle Mitarbeiter)

1. Admin erstellt "Haarwäsche" Service
2. Lässt alle Felder leer
3. Speichert
4. System:
   - Keine `service_staff` Einträge
   - Keine gender_restriction
   - Erstellt Cal.com Event Types mit `assignAllTeamMembers: true`
5. Result: Alle Mitarbeiter können Haarwäsche durchführen

---

## Performance Considerations

### Database Queries

**Before (Phase 1):**
- 1 query: Create Event Type

**After (Phase 2):**
- 1 query: Check `service_staff` table
- 1 query: Filter staff by gender/seniority (if needed)
- 1 query: Create Event Type

**Optimization:**
- Eager load staff relationships
- Cache staff list per company
- Index on `service_id` and `staff_id`

### Cal.com API Calls

**No change** - Same number of API calls, just different payloads.

---

## Testing Strategy

### Unit Tests

```php
// tests/Unit/Services/CalcomEventTypeManagerTest.php
public function test_getHostsForService_returns_all_when_no_restrictions()
{
    $service = Service::factory()->create();

    $manager = new CalcomEventTypeManager($service->company);
    $hosts = $manager->getHostsForService($service);

    $this->assertEquals(['assignAllTeamMembers' => true], $hosts);
}

public function test_getHostsForService_filters_by_gender()
{
    $service = Service::factory()->create(['gender_restriction' => 'male']);
    $maleStaff = Staff::factory()->create(['gender' => 'male']);
    $femaleStaff = Staff::factory()->create(['gender' => 'female']);

    $manager = new CalcomEventTypeManager($service->company);
    $hosts = $manager->getHostsForService($service);

    $this->assertCount(1, $hosts);
    $this->assertEquals($maleStaff->calcom_user_id, $hosts[0]['userId']);
}
```

### Integration Tests

```php
// tests/Feature/ServiceStaffAssignmentTest.php
public function test_creating_service_with_staff_restriction_creates_correct_cal_event_types()
{
    // Test full workflow from Filament form to Cal.com API
}
```

---

## Documentation for Users

### Admin Guide

**Location:** `claudedocs/01_FRONTEND/Filament/SERVICE_STAFF_ASSIGNMENT_GUIDE.md`

**Contents:**
1. Wie man Mitarbeiter einem Service zuweist
2. Geschlechts-Einschränkungen verwenden
3. Scheduling Types verstehen (MANAGED vs ROUND_ROBIN)
4. Beispiele für verschiedene Service-Typen

### Developer Guide

**Location:** This document

---

## Summary

**Current State (Phase 1):**
- ✅ All new Event Types have `assignAllTeamMembers: true`
- ✅ Simple, works for 90% of use cases
- ✅ No UI changes needed

**Future State (Phase 2):**
- 📋 Service-specific staff assignment via `service_staff` table
- 📋 Gender restriction filtering
- 📋 Senior-level requirement
- 📋 Configurable scheduling types per service
- 📋 Filament UI for easy configuration

**Trigger for Implementation:**
User requests: "Service X soll nur von Mitarbeiter Y und Z gemacht werden"

**Estimated Effort:**
- Database migrations: 1 hour
- CalcomEventTypeManager changes: 3 hours
- Filament UI: 4 hours
- Testing: 3 hours
- Documentation: 1 hour
- **Total: 12 hours (1.5 days)**

---

**References:**
- Cal.com API Hosts Documentation: `claudedocs/09_ARCHIVE/Deprecated/calcom_api_hosts_research.md`
- Phase 1 Implementation: `HOST_ASSIGNMENT_VERIFICATION_GUIDE.md`
- CalcomV2Client: `app/Services/CalcomV2Client.php:154-183`
