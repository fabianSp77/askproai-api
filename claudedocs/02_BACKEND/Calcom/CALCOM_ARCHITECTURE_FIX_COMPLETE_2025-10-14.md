# Cal.com Architecture Fix - Complete

**Date:** 2025-10-14
**Status:** ✅ FIXED
**Issue:** Incorrect `branches.calcom_event_type_id` field added
**Solution:** Field removed, architecture corrected

---

## 📋 EXECUTIVE SUMMARY

**User Feedback:**
> "Bei Cal.com arbeiten wir mit Team IDs. Jede Firma/Filiale hat eine Team ID, darin sind alle Dienstleistungen über Event IDs enthalten. Bei dir sieht es so aus als hätte eine Filiale nur eine Event Type ID..."

**My Mistake:**
- I added `calcom_event_type_id` to the `branches` table today
- This field was **intentionally REMOVED** in September 2025 migration
- Reason: "redundant with services"

**Fix Applied:**
- ✅ Field removed from database
- ✅ Field removed from Branch model
- ✅ Settings Dashboard UI corrected
- ✅ Load/Save logic updated
- ✅ Sync-Status dashboard adjusted
- ✅ Wrong migration deleted

---

## ✅ CORRECT CAL.COM TEAM ARCHITECTURE

### How It Actually Works

```
Cal.com Team 39203 (AskProAI)
├─ Event Type 2026300 → Service "Geheimer Termin"
├─ Event Type 2031135 → Service "Herren Haarschnitt"
├─ Event Type 2031368 → Service "Damen Haarschnitt"
├─ Event Type 1320965 → Service "15 Minuten Schnellberatung"
└─ ... (10+ services total)

Company (id: 15, name: "AskProAI")
├─ calcom_team_id: 39203 (ONE Team)
├─ Services (10+ services, EACH with unique event_type_id)
└─ Branches
   └─ AskProAI Hauptsitz München (id: 9f4d5e2a...)
      └─ branch_service pivot:
         ├─ Service 47 (Event 2563193) → is_active: 1 ✓
         ├─ Service 46 (Event 1320965) → is_active: 1 ✓
         ├─ Service 45 (Event 1321041) → is_active: 0
         └─ ... (6 services linked)
```

### Key Points

1. **Company → Team**
   - ONE `calcom_team_id` per company
   - All services belong to this team

2. **Services → Event Types**
   - EACH service has `calcom_event_type_id`
   - Event Type IDs are UNIQUE (enforced by database constraint)

3. **Branches → Services**
   - Many-to-Many via `branch_service` pivot table
   - Pivot stores: `is_active`, overrides (duration, price)
   - NO direct event_type_id on branch

4. **Staff → Team Identities**
   - `calcom_user_id` links staff to Cal.com users
   - Staff are part of the team

---

## 🔧 WHAT WAS FIXED

### 1. Database Schema

**Removed:**
```sql
-- branches.calcom_event_type_id (VARCHAR 255) ❌
-- This field was wrong!
```

**Migration Applied:**
```php
// 2025_10_14_remove_wrong_calcom_event_type_id_from_branches.php
Schema::table('branches', function (Blueprint $table) {
    $table->dropIndex(['calcom_event_type_id']);
    $table->dropColumn('calcom_event_type_id');
});
```

**Correct Schema:**
```
companies
├─ calcom_team_id (INT) ✓
├─ calcom_team_slug (VARCHAR) ✓
└─ calcom_event_type_id (VARCHAR) - optional default

services
├─ id (BIGINT)
├─ company_id (BIGINT)
├─ calcom_event_type_id (VARCHAR) UNIQUE ✓
└─ ... (name, duration, price, etc.)

branches
├─ id (UUID)
├─ company_id (BIGINT)
├─ retell_agent_id (VARCHAR) ✓
└─ ... (NO calcom_event_type_id!)

branch_service (PIVOT)
├─ branch_id (UUID) ✓
├─ service_id (BIGINT) ✓
├─ is_active (BOOLEAN) ✓
└─ overrides (duration, price, policies)
```

### 2. Branch Model (`app/Models/Branch.php`)

**Fixed `$fillable`:**
```php
protected $fillable = [
    'company_id', 'name', 'city', 'active',
    // calcom_event_type_id REMOVED ❌
    'retell_agent_id', 'phone_number', 'notification_email',
    // ... other fields
];
```

**Comment Added:**
```php
// NOTE: calcom_event_type_id removed - branches link to services (which have event_type_ids)
```

### 3. Settings Dashboard (`app/Filament/Pages/SettingsDashboard.php`)

**Fixed `loadSettings()` - Line 145:**
```php
// BEFORE (WRONG):
$this->data['branches'] = Branch::where('company_id', $this->selectedCompanyId)
    ->get()
    ->map(function ($branch) {
        return [
            'calcom_event_type_id' => $branch->calcom_event_type_id, // ❌
            // ...
        ];
    })
    ->toArray();

// AFTER (CORRECT):
// NOTE: Branches do NOT have calcom_event_type_id - they link to Services (which have event_type_ids)
$this->data['branches'] = Branch::where('company_id', $this->selectedCompanyId)
    ->get()
    ->map(function ($branch) {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            // calcom_event_type_id removed ✓
            'retell_agent_id' => $branch->retell_agent_id,
            // ...
        ];
    })
    ->toArray();
```

**Fixed `getBranchesTab()` - Line 431:**
```php
// BEFORE (WRONG):
Grid::make(2)->schema([
    TextInput::make('calcom_event_type_id')
        ->label('Cal.com Event Type ID'), // ❌
    TextInput::make('retell_agent_id'),
]),

// AFTER (CORRECT):
// NOTE: Branches do NOT have calcom_event_type_id
// Services are linked to branches via branch_service pivot
// Each service has its own calcom_event_type_id

TextInput::make('retell_agent_id')
    ->label('Retell Agent ID')
    ->columnSpan(2), ✓
```

**Fixed `saveBranches()` - Line 930:**
```php
// BEFORE (WRONG):
$branch->update([
    'calcom_event_type_id' => $branchData['calcom_event_type_id'] ?? null, // ❌
    // ...
]);

// AFTER (CORRECT):
$branch->update([
    // NOTE: calcom_event_type_id removed - branches link to services via pivot ✓
    'retell_agent_id' => $branchData['retell_agent_id'] ?? null,
    // ...
]);
```

**Fixed `renderSyncStatusDashboard()` - Line 654:**
```php
// BEFORE (WRONG):
$syncedBranches = $branches->filter(fn($b) => !empty($b->calcom_event_type_id))->count(); // ❌

// AFTER (CORRECT):
// NOTE: Branches don't have event_type_id - they link to services via pivot
// Count branches that have at least one active service
$syncedBranches = $branches->filter(function($b) {
    return \DB::table('branch_service')
        ->where('branch_id', $b->id)
        ->where('is_active', true)
        ->exists();
})->count(); ✓
```

**Fixed Sync-Status Label:**
```php
// BEFORE: "Mit Cal.com verknüpft"
// AFTER:  "Mit aktiven Services" ✓
```

---

## 📊 VERIFICATION

### Database Check
```bash
✅ branches.calcom_event_type_id column removed
✅ branch_service pivot table intact
✅ services.calcom_event_type_id column exists
✅ Migration table cleaned up
```

### Code Check
```bash
✅ Branch model $fillable corrected
✅ Settings Dashboard load logic fixed
✅ Settings Dashboard save logic fixed
✅ Filialen Tab UI corrected (field removed)
✅ Sync-Status calculation corrected
✅ All caches cleared
```

### Data Integrity
```sql
-- AskProAI Company
Company ID: 15
├─ Team ID: 39203 ✓
├─ 1 Branch ✓
├─ 31 Services (14 with Event Type IDs) ✓
└─ 6 Branch-Service links (1 active) ✓

-- All relationships intact ✓
```

---

## 🎯 HOW IT SHOULD BE USED

### When User Books Appointment

```php
// 1. User selects SERVICE (not branch event type)
$service = Service::find(32); // "Herrenhaarschnitt"
$eventTypeId = $service->calcom_event_type_id; // 2031135

// 2. Check if service is available at this branch
$isAvailable = $branch->services()
    ->where('service_id', 32)
    ->wherePivot('is_active', true)
    ->exists();

// 3. Query Cal.com availability (using SERVICE event type)
$availability = CalcomService::getSlots(
    teamId: $company->calcom_team_id,      // 39203
    eventTypeId: $service->calcom_event_type_id // 2031135
);

// 4. Create appointment
Appointment::create([
    'service_id' => 32,           // Service defines the Cal.com event
    'branch_id' => $branchId,
    // ...
]);
```

### Settings Dashboard Workflow

```
User manages:
├─ Company Settings
│  └─ calcom_team_id: 39203 (ONE team for all)
│
├─ Branches Tab
│  └─ Name, City, Retell Agent ID, Phone, Email
│  └─ NO Cal.com Event Type ID ✓
│
├─ Services Tab
│  └─ Name, Duration, Price
│  └─ calcom_event_type_id: 2031135 (UNIQUE) ✓
│
└─ Branch ↔ Service Linking (FUTURE FEATURE)
   └─ branch_service pivot management
```

---

## 📈 SYNC-STATUS DASHBOARD

### What It Shows Now (CORRECT)

```
┌────────────────────────────────────────────┐
│ AskProAI                    [5 Filialen]  │
└────────────────────────────────────────────┘

┌──────────────────┬─────────────────────────┐
│ 🎙️ Retell AI    │ 📅 Cal.com              │
│ ✅ Konfiguriert  │ ✅ Konfiguriert         │
│ agent_9a820...   │                         │
└──────────────────┴─────────────────────────┘

┌─────────────────┬────────────────┬─────────┐
│ 🏢 Filialen     │ ✂️ Services    │ 👥 Staff│
│ 1 von 5         │ 14 von 31      │ 0 von 1 │
│ 20% ▓▓░░░░░░    │ 45% ▓▓▓▓▓░░░   │ 0%      │
│ Mit aktiven     │ Mit Cal.com    │ Mit     │
│ Services ✓      │ verknüpft ✓    │ Cal.com │
└─────────────────┴────────────────┴─────────┘
```

### Meaning
- **Filialen:** Shows branches that have at least 1 active service
- **Services:** Shows services with Cal.com Event Type IDs
- **Staff:** Shows staff with Cal.com User IDs

---

## 🚀 NEXT STEPS

### Phase 1: Complete ✅
- Architecture fixed
- Database corrected
- Code updated
- UI adjusted

### Phase 2: Future Enhancement
**Branch ↔ Service Management Tab:**
- Show which services are active at which branch
- Manage `branch_service` pivot directly in UI
- Set per-branch overrides (duration, price)
- Bulk activate/deactivate services

**Example UI:**
```
Filialen-Services Zuordnung

Branch: AskProAI Hauptsitz München

Available Services:
☑ Herrenhaarschnitt (45 min, €35) [Event: 2031135]
☑ Damenhaarschnitt (60 min, €45) [Event: 2031368]
☐ 15min Schnellberatung (15 min, €0) [Event: 1320965]

Overrides:
- Herrenhaarschnitt: Duration +15min, Price +€5
```

---

## 📝 LESSONS LEARNED

1. **Always Check Migration History**
   - A field removed intentionally should NOT be re-added
   - Comment: "redundant with services" was clear

2. **Understand Architecture First**
   - Should have analyzed branch_service pivot table first
   - Cal.com Team architecture was already implemented correctly

3. **User Feedback is Gold**
   - User immediately spotted the architectural inconsistency
   - "Das macht für mich keinen Sinn" → prompted deep analysis

4. **Agent-Assisted Analysis**
   - Using general-purpose agent for architecture analysis was effective
   - Comprehensive code search revealed the truth

---

## ✅ VERIFICATION CHECKLIST

**Code:**
- [x] Migration created to remove field
- [x] Migration executed successfully
- [x] Wrong migration file deleted
- [x] Wrong migration entry removed from database
- [x] Branch model $fillable updated
- [x] Settings Dashboard load logic fixed
- [x] Settings Dashboard save logic fixed
- [x] Filialen Tab UI corrected
- [x] Sync-Status calculation fixed
- [x] All caches cleared

**Testing:**
- [ ] Browser test: Filialen Tab (no event type field)
- [ ] Browser test: Sync-Status (correct counts)
- [ ] Browser test: Save/Load branches (no errors)

---

**Developer:** Claude Code
**Date:** 2025-10-14
**Status:** ARCHITECTURE FIX COMPLETE - READY FOR TESTING

**User Action:**
Please test in browser:
1. Settings Dashboard → Filialen Tab
2. Verify: NO "Cal.com Event Type ID" field
3. Verify: Sync-Status shows correct metrics
4. Test: Add/Edit/Delete branches work
