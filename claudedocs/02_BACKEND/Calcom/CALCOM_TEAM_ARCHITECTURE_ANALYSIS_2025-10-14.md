# Cal.com Team Architecture Analysis

**Date**: 2025-10-14
**Status**: ARCHITECTURE DOCUMENTATION
**Priority**: CRITICAL - Affects Settings Dashboard Development

---

## Executive Summary

**VERDICT ON branches.calcom_event_type_id**: ⚠️ **WRONG** - Should be REMOVED

The migration `2025_10_14_add_calcom_event_type_id_to_branches.php` was **incorrect** and conflicts with existing architecture. Another migration (`2025_09_29_fix_calcom_event_ownership.php`) already **REMOVED** this field intentionally.

---

## Actual Cal.com Team Architecture

### 1. Core Hierarchy

```
Company (calcom_team_id: 39203)
│
├─ Team ID = 39203 (one team per company)
│  │
│  ├─ Event Types (services):
│  │  ├─ Event Type ID: 2026300 → Service: "Geheimer Termin"
│  │  ├─ Event Type ID: 2031135 → Service: "Herren: Waschen, Schneiden, Styling"
│  │  ├─ Event Type ID: 2031368 → Service: "Damen: Waschen, Schneiden, Styling"
│  │  └─ ... (multiple services = multiple event types)
│  │
│  └─ Team Members (identities/hosts):
│     ├─ Fabian Spitzer
│     ├─ Other team members
│     └─ ... (stored in calcom_team_members table)
│
└─ Branches:
   └─ AskProAI Hauptsitz München (branch_id: 9f4d5e2a...)
      │
      └─ branch_service pivot table:
         ├─ Service 31 (Event Type 2026300) → is_active: depends
         ├─ Service 32 (Event Type 2031135) → is_active: depends
         └─ Service 47 (Event Type 2563193) → is_active: 1
```

### 2. Database Structure

#### companies table:
```sql
calcom_team_id          int(11)        -- ONE team per company (e.g., 39203)
calcom_event_type_id    varchar(255)   -- LEGACY/DEFAULT event type (optional)
```

**Purpose of companies.calcom_event_type_id**:
- Legacy field from before multi-service architecture
- Can be used as a DEFAULT event type for the whole company
- NOT required for modern multi-service setup
- Example: Company 1 has "2563193" as default

#### branches table:
```sql
calcom_api_key          text           -- Branch can have own Cal.com API key
calcom_user_id          varchar(255)   -- Cal.com user for this branch
calcom_event_type_id    varchar(255)   -- ❌ WRONG - was removed in migration
```

**Current State**: branches.calcom_event_type_id EXISTS but should NOT

#### services table:
```sql
calcom_event_type_id    varchar(255)   -- UNIQUE - one event type per service
company_id              bigint         -- Service belongs to company
```

**Key Constraint**: `UNIQUE(calcom_event_type_id)` - prevents duplicates

#### branch_service pivot table:
```sql
branch_id               char(36)       -- UUID of branch
service_id              bigint         -- Service ID
is_active               tinyint        -- Whether service is active in this branch
duration_override       int            -- Branch-specific duration
price_override          decimal        -- Branch-specific price
```

**Purpose**: Links services (event types) to specific branches with overrides

#### team_event_type_mappings table:
```sql
company_id              bigint         -- Company that owns the team
calcom_team_id          int            -- Cal.com Team ID
calcom_event_type_id    int            -- Event Type ID
event_type_name         varchar(255)   -- Name of event type
is_team_event           boolean        -- Is it a team event?
hosts                   json           -- Array of host IDs
```

**Purpose**: Maps ALL event types in a team to company for validation

#### calcom_team_members table:
```sql
company_id              bigint         -- Company that owns the team
calcom_team_id          int            -- Cal.com Team ID
calcom_user_id          int            -- Cal.com User ID (host/identity)
email                   varchar(255)   -- Email of team member
name                    varchar(255)   -- Name of team member
role                    varchar(255)   -- Role (member, admin, owner)
```

**Purpose**: Stores team members (hosts/identities) for staff mapping

---

## Cal.com Team Concepts

### Team
- A **Team** is a company-level entity in Cal.com
- Each company has ONE team ID (stored in `companies.calcom_team_id`)
- Example: AskProAI has Team ID 39203

### Event Types (Services)
- A team can have MULTIPLE Event Types
- Each Event Type = one Service in our system
- Event Types are stored in `services.calcom_event_type_id`
- Event Types belong to the TEAM, not to individual branches

### Team Members (Identities/Hosts)
- A team has MULTIPLE members (staff/hosts)
- Each member can be assigned to Event Types
- Members are stored in `calcom_team_members` table
- Members are Cal.com users who can host appointments

### Branches
- Branches are an **internal concept** (not in Cal.com)
- Branches DO NOT have their own Event Types
- Branches LINK to company's services via `branch_service` pivot
- Branches can override service settings (duration, price) per location

---

## Why branches.calcom_event_type_id is WRONG

### 1. Architecture Conflict
```
❌ WRONG THINKING:
Company → Branch → calcom_event_type_id (one event type per branch)

✅ CORRECT ARCHITECTURE:
Company → calcom_team_id (one team)
         → Services (multiple event types)
         → Branches → branch_service (multiple services per branch)
```

### 2. Migration Evidence
The migration `2025_09_29_fix_calcom_event_ownership.php` specifically **REMOVES** `branches.calcom_event_type_id`:

```php
// 4. Remove Event Type ID from branches (redundant with services)
Schema::table('branches', function (Blueprint $table) {
    if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
        $table->dropColumn('calcom_event_type_id');
    }
});
```

Comment: "redundant with services"

### 3. Data Integrity Issue
- `services.calcom_event_type_id` has a **UNIQUE** constraint
- If branches also store event type IDs, it creates confusion:
  - Which is the source of truth?
  - How do multiple branches share the same service?

### 4. Real Data Pattern
Looking at AskProAI (company_id: 15):
- 1 branch: "AskProAI Hauptsitz München"
- 10+ services with different event type IDs
- `branch_service` pivot shows 6 linked services

If branch had ONE event type ID, how would it handle 6 services?

---

## Correct Data Model

### For Settings Dashboard

```typescript
interface Company {
  id: number;
  name: string;
  calcom_team_id: number;          // ONE team per company
  calcom_event_type_id?: string;   // Optional default event type
}

interface Service {
  id: number;
  company_id: number;
  name: string;
  calcom_event_type_id: string;    // UNIQUE event type
  // ... other fields
}

interface Branch {
  id: string;                      // UUID
  company_id: number;
  name: string;
  city: string;
  calcom_api_key?: string;         // Optional branch API key
  calcom_user_id?: string;         // Optional branch user
  // NO calcom_event_type_id field
}

interface BranchService {
  branch_id: string;
  service_id: number;
  is_active: boolean;
  duration_override_minutes?: number;
  price_override?: number;
  // ... other overrides
}
```

### Relationships

```
Company (1) → (N) Services
  ↓ calcom_event_type_id
  each service has ONE unique event type

Company (1) → (N) Branches
  ↓ no direct event type relationship

Branch (N) ↔ (M) Services via branch_service
  ↓ is_active, overrides
  many-to-many relationship
```

---

## Migration Fix Required

### Problem
The migration `2025_10_14_add_calcom_event_type_id_to_branches.php` added the field back, but it conflicts with the removal in `2025_09_29_fix_calcom_event_ownership.php`.

### Solution

**Option 1: Delete the bad migration** (if not yet run in production)
```bash
rm /var/www/api-gateway/database/migrations/2025_10_14_add_calcom_event_type_id_to_branches.php
```

**Option 2: Create rollback migration** (if already run)
```php
// 2025_10_14_remove_branches_calcom_event_type_id_again.php
public function up(): void
{
    Schema::table('branches', function (Blueprint $table) {
        if (Schema::hasColumn('branches', 'calcom_event_type_id')) {
            $table->dropIndex(['calcom_event_type_id']);
            $table->dropColumn('calcom_event_type_id');
        }
    });
}
```

---

## Settings Dashboard Implementation

### Company Settings Tab

**Cal.com Integration Section:**
```
┌─ Cal.com Team Integration ─────────────────┐
│ Team ID: [39203] (readonly)                │
│ Team Slug: [askproai] (readonly)           │
│ API Key: [••••••••••••••] (encrypted)      │
│                                             │
│ Default Event Type (legacy):               │
│ ├─ Event Type ID: [2563193] (optional)    │
│ └─ Used for: Fallback appointments         │
│                                             │
│ [Sync Team Event Types] button             │
│ Last sync: 2 hours ago                      │
└─────────────────────────────────────────────┘
```

### Services Management Tab

**Services List:**
```
┌─ Company Services (from Team 39203) ───────┐
│                                             │
│ Service 1: Geheimer Termin                 │
│ ├─ Event Type ID: 2026300                  │
│ ├─ Duration: 30 min                         │
│ └─ Active in: 1 branch                      │
│                                             │
│ Service 2: Herren: Waschen, Schneiden      │
│ ├─ Event Type ID: 2031135                  │
│ ├─ Duration: 45 min                         │
│ └─ Active in: 1 branch                      │
│                                             │
│ ... (10 total services)                     │
│                                             │
│ [Import Team Services] [Add Custom]        │
└─────────────────────────────────────────────┘
```

### Branch Configuration Tab

**Per-Branch Service Assignment:**
```
┌─ Branch: AskProAI Hauptsitz München ───────┐
│                                             │
│ Active Services:                            │
│ ✅ Service 47: AskProAI Beratung           │
│    ├─ Duration: 30 min (default)           │
│    ├─ Price: €0 (default)                  │
│    └─ [Edit Overrides]                     │
│                                             │
│ ❌ Service 38: 30 Min Termin               │
│    ├─ Inactive                              │
│    └─ [Activate]                            │
│                                             │
│ Available Services from Team:               │
│ [ ] Service 31: Geheimer Termin            │
│ [ ] Service 32: Herren Haarschnitt         │
│ [ ] Service 33: Damen Haarschnitt          │
│                                             │
│ [Add Services to Branch]                   │
└─────────────────────────────────────────────┘
```

---

## Data Flow for Appointment Booking

### Step 1: Customer calls → Retell AI
```
Retell: Which service would you like?
Customer: "Herren Haarschnitt"
```

### Step 2: Service Selection
```php
// App\Services\Retell\ServiceSelectionService
$service = Service::where('company_id', 15)
    ->where('name', 'LIKE', '%Herren%')
    ->whereHas('branches', function($q) use ($branchId) {
        $q->where('branch_id', $branchId)
          ->where('branch_service.is_active', true);
    })
    ->first();

// Result: Service #32 (calcom_event_type_id: 2031135)
```

### Step 3: Availability Check
```php
// App\Services\Appointments\WeeklyAvailabilityService
$availability = $calcomService->getAvailableSlots(
    teamId: 39203,              // Company team
    eventTypeId: 2031135,       // Service event type
    dateRange: $dateRange
);
```

### Step 4: Appointment Creation
```php
// App\Services\Retell\AppointmentCreationService
$appointment = Appointment::create([
    'company_id' => 15,
    'branch_id' => '9f4d5e2a...',
    'service_id' => 32,         // Service with event type 2031135
    'customer_id' => $customer->id,
    // ...
]);
```

### Step 5: Cal.com Sync
```php
// App\Jobs\SyncAppointmentToCalcomJob
$calcomBooking = $this->calcomService->createBooking([
    'eventTypeId' => 2031135,   // From service.calcom_event_type_id
    'start' => $appointment->start_time,
    'responses' => [
        'name' => $customer->name,
        'email' => $customer->email,
    ]
]);
```

**Notice**: NO branch event type involved - always service event type

---

## Validation & Security

### Team Access Validation
```php
// Company::ownsService()
public function ownsService(int $calcomEventTypeId): bool
{
    if (!$this->hasTeam()) {
        return false;
    }

    $calcomService = new CalcomV2Service($this);
    return $calcomService->validateTeamAccess(
        $this->calcom_team_id,
        $calcomEventTypeId
    );
}
```

### Process
1. Check if service's event type exists in company's team
2. Query Cal.com: `GET /teams/{teamId}/event-types`
3. Verify event type is in the response
4. Cache result for 1 hour

### Security Boundary
```
Team 39203 (AskProAI) can ONLY:
- Access event types 2026300, 2031135, 2031368, ...
- Book appointments for these event types
- Query availability for these event types

Team 34209 (Krückeberg) can ONLY:
- Access its own event types
- Cannot see or book AskProAI's event types
```

---

## Common Patterns

### 1. Multi-Branch, Single Service
```
Company: "Friseur Kette"
├─ Team ID: 40000
├─ Service: "Herrenhaarschnitt" (Event Type: 3000000)
└─ Branches:
   ├─ Branch A (München) → has service 3000000 active
   ├─ Branch B (Berlin) → has service 3000000 active
   └─ Branch C (Hamburg) → has service 3000000 inactive
```

**Cal.com sees**: ONE event type (3000000) with availability across all active branches

### 2. Multi-Branch, Multiple Services
```
Company: "Beauty Salon"
├─ Team ID: 50000
├─ Services:
│  ├─ "Basic Cut" (Event Type: 4000000)
│  └─ "Premium Color" (Event Type: 4000001)
└─ Branches:
   ├─ Branch A → has BOTH services
   └─ Branch B → has ONLY "Basic Cut"
```

**Cal.com sees**: TWO event types with different availability patterns

### 3. Branch-Specific Pricing
```
Company: "Consulting Firm"
├─ Service: "30 Min Beratung" (Event Type: 5000000)
│  └─ Base price: €50
└─ Branches:
   ├─ Branch A (Downtown) → price override: €80
   └─ Branch B (Suburb) → price override: €50 (default)
```

**Implementation**: `branch_service.price_override`

---

## Summary & Recommendations

### ✅ CORRECT Architecture

1. **Company** → `calcom_team_id` (one team per company)
2. **Services** → `calcom_event_type_id` (multiple event types per team)
3. **Branches** → NO event type field
4. **branch_service** pivot → Links services to branches with overrides

### ❌ WRONG: branches.calcom_event_type_id

**Reasons**:
- Conflicts with multi-service architecture
- Already removed in previous migration
- Creates data integrity issues
- Breaks many-to-many relationship

### 🔧 Required Actions

1. **Remove the bad migration file**:
   ```bash
   rm database/migrations/2025_10_14_add_calcom_event_type_id_to_branches.php
   ```

2. **Create rollback migration** if already run:
   ```bash
   php artisan make:migration remove_branches_calcom_event_type_id_final
   ```

3. **Update Branch model**: Remove `calcom_event_type_id` from `$fillable`

4. **Settings Dashboard**: Build around correct architecture:
   - Company has team_id
   - Services have event_type_id
   - Branches link to services via pivot

### 📊 Field Usage Summary

| Table | Field | Purpose | Required? |
|-------|-------|---------|-----------|
| companies | calcom_team_id | Team ownership | ✅ Yes |
| companies | calcom_event_type_id | Legacy/default | ⚠️ Optional |
| services | calcom_event_type_id | Service identity | ✅ Yes (unique) |
| branches | calcom_event_type_id | ❌ WRONG | ❌ Remove |
| branch_service | - | Service-branch link | ✅ Yes (pivot) |

---

## Reference Documentation

### Related Files
- `/var/www/api-gateway/app/Models/Company.php` - Company model with team methods
- `/var/www/api-gateway/app/Models/Service.php` - Service model with event type
- `/var/www/api-gateway/app/Models/Branch.php` - Branch model (no event type)
- `/var/www/api-gateway/app/Services/CalcomV2Service.php` - Team API integration
- `/var/www/api-gateway/database/migrations/2025_09_29_fix_calcom_event_ownership.php` - Removal migration
- `/var/www/api-gateway/database/migrations/2025_09_24_123318_create_branch_service_table.php` - Pivot table

### Related Tables
- `companies` - Company settings with team_id
- `services` - Services with event_type_id
- `branches` - Branch locations
- `branch_service` - Many-to-many with overrides
- `team_event_type_mappings` - Team event type registry
- `calcom_team_members` - Team members/hosts

---

**Conclusion**: The `branches.calcom_event_type_id` field is **architecturally wrong** and should be **removed immediately**. The correct model is: Company → Team → Multiple Services (Event Types) → Branches (linked via pivot).
