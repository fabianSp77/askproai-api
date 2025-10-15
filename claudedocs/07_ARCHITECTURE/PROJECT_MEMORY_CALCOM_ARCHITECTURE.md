# Project Memory: Cal.com Architecture

**Last Updated:** 2025-10-14
**Status:** CRITICAL KNOWLEDGE - Always Reference
**Type:** Architecture Pattern

---

## ⚠️ CRITICAL: branches.calcom_event_type_id is WRONG

**DO NOT add `calcom_event_type_id` to branches table!**

This field was **intentionally removed** in September 2025 migration with reason: "redundant with services"

**History:**
- 2025-09-29: Migration `fix_calcom_event_ownership.php` explicitly removed this field
- 2025-10-14: Incorrectly re-added, then immediately fixed and removed again

---

## ✅ CORRECT CAL.COM TEAM ARCHITECTURE

### Data Model

```
Company
├─ id (BIGINT)
├─ calcom_team_id (INT) ✅ ONE team per company
├─ calcom_team_slug (VARCHAR)
└─ calcom_event_type_id (VARCHAR) - optional default, rarely used

Service
├─ id (BIGINT)
├─ company_id (BIGINT)
├─ calcom_event_type_id (VARCHAR) UNIQUE ✅ EACH service = ONE event type
├─ name, duration_minutes, price, description
└─ is_active

Branch
├─ id (UUID)
├─ company_id (BIGINT)
├─ name, city, active
├─ retell_agent_id (VARCHAR) ✅ Branch-specific Retell agent
├─ phone_number, notification_email
└─ ❌ NO calcom_event_type_id field!

branch_service (PIVOT) ✅ Links branches to services
├─ branch_id (UUID)
├─ service_id (BIGINT)
├─ is_active (BOOLEAN) - Is this service available at this branch?
├─ duration_override_minutes (INT) - Branch-specific duration
├─ price_override (DECIMAL) - Branch-specific pricing
└─ branch_policies (JSON) - Branch-specific policies

Staff
├─ id (UUID)
├─ company_id (BIGINT)
├─ branch_id (UUID)
├─ calcom_user_id (VARCHAR) ✅ Cal.com team member
├─ name, email, position, phone
└─ is_active
```

### How Cal.com Teams Work

```
Cal.com Team 39203 (Company: AskProAI)
│
├─ Team Members (Identities)
│  ├─ Staff 1 → calcom_user_id
│  ├─ Staff 2 → calcom_user_id
│  └─ ...
│
└─ Event Types (Services)
   ├─ Event 2031135 → Service "Herrenhaarschnitt"
   ├─ Event 2031368 → Service "Damenhaarschnitt"
   ├─ Event 1320965 → Service "15min Beratung"
   └─ ... (10+ services)

Branch "AskProAI Hauptsitz"
└─ Available Services (via branch_service pivot)
   ├─ Service 32 (Event 2031135) ✓ active
   ├─ Service 46 (Event 1320965) ✓ active
   └─ Service 45 (Event 1321041) ✗ inactive
```

### Booking Flow

```php
// 1. Customer selects SERVICE (not branch!)
$service = Service::find(32); // "Herrenhaarschnitt"
$eventTypeId = $service->calcom_event_type_id; // 2031135

// 2. Check if service available at branch
$isAvailable = $branch->services()
    ->where('service_id', 32)
    ->wherePivot('is_active', true)
    ->exists();

// 3. Get availability from Cal.com
$availability = CalcomService::getSlots(
    teamId: $company->calcom_team_id,           // 39203
    eventTypeId: $service->calcom_event_type_id // 2031135
);

// 4. Create appointment
Appointment::create([
    'service_id' => 32,         // Service defines the event
    'branch_id' => $branchId,   // Location
    // ...
]);
```

---

## 🎯 SETTINGS DASHBOARD STRUCTURE

### Tab Order (Option A - Hybrid)

```
1. Sync-Status     - Overview first
2. Filialen        - Business entities
3. Mitarbeiter     - Business entities
4. Dienstleistungen- Business entities
5. Cal.com         - Main integration
6. Retell AI       - Main integration
7. Calendar        - Settings
8. Policies        - Settings
9. OpenAI          - Advanced (rarely changed)
10. Qdrant         - Advanced (rarely changed)
```

### Filialen Tab (Branches) ✅

**Fields to Show:**
- Name
- City
- Active (Toggle)
- Retell Agent ID
- Phone Number
- Notification Email

**Fields to NEVER Show:**
- ❌ Cal.com Event Type ID (architecturally wrong!)

### Dienstleistungen Tab (Services) ✅

**Fields to Show:**
- Name
- Description
- Duration (Minutes)
- Price (€)
- **Cal.com Event Type ID** ✅ (This is correct here!)
- Is Active (Toggle)

### Sync-Status Tab ✅

**Correct Calculation:**
```php
// Branches: Count those with at least one active service
$syncedBranches = $branches->filter(function($b) {
    return \DB::table('branch_service')
        ->where('branch_id', $b->id)
        ->where('is_active', true)
        ->exists();
})->count();

// Services: Count those with Cal.com Event Type ID
$syncedServices = $services->filter(fn($s) => !empty($s->calcom_event_type_id))->count();

// Staff: Count those with Cal.com User ID
$syncedStaff = $staff->filter(fn($s) => !empty($s->calcom_user_id))->count();
```

**Display:**
- Company Header with total branch count
- API Status Cards: Retell AI (check `retell_agent_id`), Cal.com (check `calcom_api_key`)
- Entity Sync Cards: Progress bars for Branches, Services, Staff
- Color coding: 80%+ green, 50-79% yellow, 0-49% red

---

## 📋 BRANCH MODEL $FILLABLE

```php
protected $fillable = [
    'company_id', 'customer_id', 'name', 'slug', 'city',
    'phone_number', 'notification_email',
    'send_call_summaries', 'call_summary_recipients',
    'include_transcript_in_summary', 'include_csv_export',
    'summary_email_frequency', 'call_notification_overrides',
    'active', 'invoice_recipient', 'invoice_name',
    'invoice_email', 'invoice_address', 'invoice_phone',
    // NOTE: calcom_event_type_id removed - branches link to services
    'calcom_api_key', 'retell_agent_id', 'integration_status',
    'calendar_mode', 'integrations_tested_at', 'calcom_user_id',
    'retell_agent_cache', 'retell_last_sync',
    'configuration_status', 'parent_settings',
    'address', 'postal_code', 'website', 'business_hours',
    'services_override', 'country', 'uuid', 'settings',
    'coordinates', 'features', 'transport_info',
    'service_radius_km', 'accepts_walkins',
    'parking_available', 'public_transport_access', 'is_active',
];
```

---

## 🚫 NEVER DO THIS

```php
// ❌ WRONG - Do NOT add calcom_event_type_id to branches
Schema::table('branches', function (Blueprint $table) {
    $table->string('calcom_event_type_id'); // NO!
});

// ❌ WRONG - Do NOT include in $fillable
protected $fillable = [
    'calcom_event_type_id', // NO!
];

// ❌ WRONG - Do NOT show in UI
TextInput::make('calcom_event_type_id')
    ->label('Cal.com Event Type ID'); // NO!

// ❌ WRONG - Do NOT save
$branch->update([
    'calcom_event_type_id' => $data['calcom_event_type_id'], // NO!
]);
```

---

## ✅ ALWAYS DO THIS

```php
// ✅ RIGHT - Services have event_type_id
$service = Service::find($serviceId);
$eventTypeId = $service->calcom_event_type_id; // YES!

// ✅ RIGHT - Check branch-service availability via pivot
$isAvailable = $branch->services()
    ->where('service_id', $serviceId)
    ->wherePivot('is_active', true)
    ->exists();

// ✅ RIGHT - Query Cal.com with SERVICE event type
$availability = CalcomService::getSlots(
    teamId: $company->calcom_team_id,
    eventTypeId: $service->calcom_event_type_id
);
```

---

## 📚 REFERENCE FILES

**Core Models:**
- `/var/www/api-gateway/app/Models/Branch.php`
- `/var/www/api-gateway/app/Models/Service.php`
- `/var/www/api-gateway/app/Models/Company.php`

**Settings Dashboard:**
- `/var/www/api-gateway/app/Filament/Pages/SettingsDashboard.php`

**Key Migrations:**
- `2025_09_29_fix_calcom_event_ownership.php` - Removed branches.calcom_event_type_id
- `2025_10_14_remove_wrong_calcom_event_type_id_from_branches.php` - Fix migration

**Documentation:**
- `/var/www/api-gateway/claudedocs/CALCOM_ARCHITECTURE_FIX_COMPLETE_2025-10-14.md`
- `/var/www/api-gateway/claudedocs/CALCOM_TEAM_ARCHITECTURE_ANALYSIS_2025-10-14.md`

---

## 🔍 QUICK VERIFICATION

**To check if architecture is correct:**

```bash
# 1. branches table should NOT have calcom_event_type_id
mysql> DESCRIBE branches;
# Should NOT see: calcom_event_type_id

# 2. services table SHOULD have calcom_event_type_id
mysql> DESCRIBE services;
# Should see: calcom_event_type_id | varchar(255) | YES | UNI

# 3. branch_service pivot exists
mysql> SHOW TABLES LIKE 'branch_service';
# Should return: branch_service

# 4. Check actual data
mysql> SELECT s.name, s.calcom_event_type_id, bs.branch_id, bs.is_active
       FROM services s
       LEFT JOIN branch_service bs ON s.id = bs.service_id
       WHERE s.company_id = 15;
# Should show services with event_type_ids linked to branches
```

---

**Last Verified:** 2025-10-14
**Status:** PRODUCTION ARCHITECTURE - DO NOT DEVIATE
