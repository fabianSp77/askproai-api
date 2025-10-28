# Cal.com Sync - Code References & Data Flow

## Complete Data Flow

```
┌────────────────────────────────────────────────────────────────────────────┐
│                         ADMIN EDITS SERVICE                                │
│                  (Filament Resource / ServiceResource.php)                 │
│                                                                            │
│  Fields that trigger sync:                                               │
│  - name, price, duration_minutes, description, is_active, buffer_time    │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ saving()
┌────────────────────────────────────────────────────────────────────────────┐
│                      SERVICE OBSERVER TRIGGERS                             │
│           app/Observers/ServiceObserver.php: 41-68 (updating)             │
│                                                                            │
│  Code:                                                                    │
│  - Detects field changes via isDirty()                                  │
│  - Checks: ['name', 'duration_minutes', 'price', etc.]                  │
│  - Sets sync_status = 'pending'                                         │
│  - Logs: 'Service needs Cal.com sync'                                   │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ updated()
┌────────────────────────────────────────────────────────────────────────────┐
│               DISPATCH SYNC JOB TO QUEUE                                   │
│        app/Observers/ServiceObserver.php: 73-84 (updated)                 │
│                                                                            │
│  Code:                                                                    │
│  - Checks: sync_status === 'pending' && calcom_event_type_id            │
│  - Dispatches UpdateCalcomEventTypeJob::dispatch($service)              │
│  - Queue: 'calcom-sync'                                                │
│  - Logs: 'Dispatched Cal.com sync job'                                  │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ queue:work
┌────────────────────────────────────────────────────────────────────────────┐
│              JOB EXECUTES: UpdateCalcomEventTypeJob                        │
│         app/Jobs/UpdateCalcomEventTypeJob.php: 46-127 (handle)           │
│                                                                            │
│  Code:                                                                    │
│  1. Checks service has calcom_event_type_id                            │
│  2. Calls: CalcomService::updateEventType($service)                    │
│  3. Makes PATCH /event-types/{eventTypeId} request                    │
│  4. Payload includes:                                                  │
│     - title: service.name                                             │
│     - length: service.duration_minutes                                │
│     - price: service.price                                           │
│     - description: service.description                               │
│     - hidden: !service.is_active                                     │
│     - metadata: company_id, buffer_time, etc.                       │
│                                                                        │
│  On Success:                                                          │
│  - Updates: sync_status = 'synced'                                    │
│  - Updates: last_calcom_sync = now()                                 │
│  - Clears: sync_error = null                                         │
│  - Logs: Success message with event_type_id                         │
│                                                                        │
│  On Failure:                                                          │
│  - Updates: sync_status = 'error'                                    │
│  - Updates: sync_error = error message                              │
│  - Updates: last_calcom_sync = now()                                │
│  - Logs: Error details, response body                              │
│  - Does NOT throw exception (graceful)                             │
│  - Note line 100-102: Exception throwing disabled                  │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ success?
                       │
         ┌─────────────┴─────────────┐
         │ YES                       NO
         ↓                           ↓
    ✅ SYNCED               ⚠️ SYNC ERROR
    Cal.com updated         Check logs
    Service ready           Manual retry
```

---

## Reverse Flow: Cal.com → Platform (Dangerous)

```
┌────────────────────────────────────────────────────────────────────────────┐
│             SOMEONE EDITS EVENT TYPE IN CAL.COM                            │
│                    (Cal.com Web UI)                                        │
│                                                                            │
│  Example: Changes name from "Premium Cut" to "Standard Cut"              │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ Cal.com sends webhook
┌────────────────────────────────────────────────────────────────────────────┐
│           WEBHOOK EVENT_TYPE.UPDATED RECEIVED                             │
│    POST /api/calcom/webhook                                              │
│                                                                            │
│  Trigger Event:                                                          │
│  - triggerEvent: 'EVENT_TYPE.UPDATED'                                   │
│  - payload: Full updated Event Type object                              │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓
┌────────────────────────────────────────────────────────────────────────────┐
│        CALCOMWEBHOOKCONTROLLER::HANDLEEVENTTYPEUPDATED                    │
│   app/Http/Controllers/CalcomWebhookController.php: 144-160             │
│                                                                            │
│  Code:                                                                    │
│  - Checks if service exists locally                                     │
│  - Dispatches ImportEventTypeJob with payload                          │
│  - Logs event                                                           │
│                                                                            │
│  Note: Dispatches job for BOTH new and existing services               │
│  This is the problem!                                                   │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓ job dispatched
┌────────────────────────────────────────────────────────────────────────────┐
│                JOB EXECUTES: ImportEventTypeJob                            │
│          app/Jobs/ImportEventTypeJob.php: 46-138 (handle)               │
│                                                                            │
│  Code:                                                                    │
│  1. Extracts eventTypeId from payload                                   │
│  2. Finds local service: Service::where('calcom_event_type_id', id)    │
│  3. If service found:                                                    │
│     - Line 92: $service->update($serviceData) ← OVERWRITES!            │
│     - $serviceData includes:                                           │
│       - name: payload['title'] ← YOUR NAME OVERWRITTEN                │
│       - duration_minutes: payload['length'] ← YOUR DURATION           │
│       - price: payload['price'] ← YOUR PRICE OVERWRITTEN             │
│       - is_active: !payload['hidden'] ← YOUR ACTIVE STATUS           │
│       - and 10+ other fields                                          │
│     - Sets: sync_status = 'synced'                                    │
│     - Sets: sync_error = null                                         │
│     - Sets: last_calcom_sync = now()                                 │
│     - Logs: "Updated Service ID {id}"                               │
│                                                                        │
│  4. If service NOT found:                                              │
│     - Creates new service with Cal.com data                          │
│     - Sets company_id via heuristics                                 │
└──────────────────────┬─────────────────────────────────────────────────────┘
                       │
                       ↓
                   ❌ RESULT
            YOUR LOCAL DATA IS LOST!
```

---

## Critical Code Locations

### 1. Status Column Definition

**File**: `app/Filament/Resources/ServiceResource.php`

**Lines**: 722-800

**Code**:
```php
Tables\Columns\TextColumn::make('status')
    ->label('Status')
    ->html()
    ->getStateUsing(function ($record) {
        // Renders 3 icons:
        // 1. Sync icon based on $record->sync_status
        // 2. Active icon based on $record->is_active
        // 3. Online icon based on $record->is_online
    })
    ->tooltip(function ($record): string {
        // Shows 3-part tooltip explaining status
        // Line 753: $canBeBooked = $record->is_active && $record->sync_status === 'synced'
        // This determines if service can receive bookings
    })
```

### 2. Service Observer

**File**: `app/Observers/ServiceObserver.php`

**Key Methods**:

| Method | Lines | Purpose |
|--------|-------|---------|
| `updating()` | 41-68 | Detects dirty fields, marks sync as pending |
| `updated()` | 73-84 | Dispatches UpdateCalcomEventTypeJob |

**Critical Code (Lines 49-67)**:
```php
$syncableFields = ['name', 'duration_minutes', 'price', 'description', 'is_active', 'buffer_time_minutes'];

foreach ($syncableFields as $field) {
    if ($service->isDirty($field)) {
        $hasChanges = true;
        break;
    }
}

if ($hasChanges) {
    $service->sync_status = 'pending'; // Triggers sync
}
```

### 3. Update Job

**File**: `app/Jobs/UpdateCalcomEventTypeJob.php`

**Method**: `handle()` (Lines 46-127)

**What It Does**:
1. Calls `CalcomService::updateEventType()`
2. Gets response from PATCH /event-types/{id}
3. Updates sync_status and last_calcom_sync
4. Logs success/failure

**Note (Lines 95-102)**:
```php
// NOTE 2025-10-14: Do NOT throw exception here
// Reason: Job runs synchronously (SyncQueue), throwing breaks user's save
// Cal.com sync is "best effort" - if it fails, user can manually sync later

// throw new \Exception($errorMessage); // REMOVED - was causing 500 errors
return; // Exit gracefully without throwing
```

### 4. Import Job (The Dangerous One)

**File**: `app/Jobs/ImportEventTypeJob.php`

**Method**: `handle()` (Lines 46-138)

**CRITICAL CODE (Lines 90-92)**:
```php
if ($service) {
    // UPDATE EXISTING SERVICE (OVERWRITES!)
    $service->update($serviceData); // ← THIS OVERWRITES YOUR DATA!
}
```

**Data Being Overwritten (Lines 60-82)**:
```php
$serviceData = [
    'name' => $this->eventTypeData['title'] ?? 'Unnamed Service',
    'slug' => $this->eventTypeData['slug'] ?? null,
    'duration_minutes' => $this->eventTypeData['length'] ?? 30,
    'price' => $this->eventTypeData['price'] ?? 0,
    'is_active' => !($this->eventTypeData['hidden'] ?? false),
    'schedule_id' => $this->eventTypeData['scheduleId'] ?? null,
    // ... and 8 more fields
];
```

### 5. Webhook Controller

**File**: `app/Http/Controllers/CalcomWebhookController.php`

**Method**: `handleEventTypeUpdated()` (Lines 144-160)

**Code**:
```php
protected function handleEventTypeUpdated(array $payload): void
{
    $service = Service::where('calcom_event_type_id', $payload['id'] ?? null)->first();

    if ($service) {
        // BOTH new and existing services dispatch same job
        ImportEventTypeJob::dispatch($payload); // ← Problem: always overwrites
    } else {
        ImportEventTypeJob::dispatch($payload);
    }
}
```

### 6. Service Model

**File**: `app/Models/Service.php`

**Protected Fields** (Lines 82-94):
```php
/**
 * PROTECTED FIELDS (NOT in $fillable):
 * - id                    (Primary key)
 * - company_id            (Multi-tenant isolation)
 * - branch_id             (Multi-tenant isolation)
 * - last_calcom_sync      (System field - set by sync)
 * - sync_status           (System field - set by sync)
 * - sync_error            (System field - set by sync)
 */
```

**Sync Methods** (Lines 225-266):
```php
public function getFormattedSyncStatusAttribute(): string
public function needsCalcomSync(): bool
```

---

## Database Schema

**File**: `database/migrations/2025_09_23_091422_add_calcom_sync_fields_to_services_table.php`

**Fields Added** (Lines 19-64):

| Column | Type | Purpose |
|--------|------|---------|
| `sync_status` | enum('synced','pending','error','never') | Tracks sync state |
| `last_calcom_sync` | timestamp | When sync last happened |
| `sync_error` | text | Error message if sync failed |
| `calcom_event_type_id` | integer | Link to Cal.com Event Type |

**Plus these synced fields**:
- slug, schedule_id, minimum_booking_notice, before_event_buffer
- requires_confirmation, disable_guests, booking_link
- locations_json, metadata_json, booking_fields_json

---

## Sync Command

**File**: `app/Console/Commands/SyncCalcomServices.php`

**Purpose**: Periodic backup sync to catch missed webhooks

**Flow** (Lines 33-200):

1. Fetches all Event Types from Cal.com (Lines 45-69)
2. For each Event Type:
   - If exists: checks if update needed (Lines 102-122)
   - If not exists: imports it (Line 113)
3. Finds orphaned services (Lines 131-151)
4. Syncs local changes back to Cal.com (Lines 186-222)

**Critical Code (Line 118)**:
```php
if ($this->option('force') || $this->shouldSync($service, $eventType)) {
    ImportEventTypeJob::dispatch($eventType); // ← Imports/overwrites
}
```

---

## Service Fillable Array

**File**: `app/Models/Service.php` (Lines 31-79)

**Fields User Can Edit** (Fillable):
```php
protected $fillable = [
    'name',                      // ← Syncs to Cal.com
    'display_name',
    'calcom_name',
    'slug',                      // ← Syncs to Cal.com
    'description',               // ← Syncs to Cal.com
    'category',
    'is_active',                 // ← Syncs to Cal.com (as 'hidden')
    'is_default',
    'is_online',                 // ← Syncs to Cal.com
    'priority',
    'duration_minutes',          // ← Syncs to Cal.com
    'buffer_time_minutes',       // ← Syncs to Cal.com
    'minimum_booking_notice',
    'before_event_buffer',
    'price',                     // ← Syncs to Cal.com
    'composite',
    'segments',
    'min_staff_required',
    'pause_bookable_policy',
    'reminder_policy',
    'reschedule_policy',
    'requires_confirmation',     // ← Syncs to Cal.com
    'disable_guests',            // ← Syncs to Cal.com
    'calcom_event_type_id',      // ← NEVER CHANGES
    'schedule_id',               // ← Syncs to Cal.com
    'booking_link',
    'locations_json',
    'metadata_json',
    'booking_fields_json',
    'assignment_notes',
    'assignment_method',
    'assignment_confidence',
];
```

**Fields Protected from Mass Assignment**:
- company_id (multi-tenant)
- branch_id (organization)
- last_calcom_sync (system)
- sync_status (system)
- sync_error (system)
- assignment_date (system)
- assigned_by (system)

---

## CalcomService API Calls

**File**: `app/Services/CalcomService.php`

### updateEventType()

**Lines**: 540-588

**What It Does**:
1. Builds PATCH payload from service data
2. Makes request: PATCH `/event-types/{eventTypeId}`
3. Includes title, description, length, price, hidden, metadata

**Payload Fields** (Lines 547-562):
```php
$payload = [
    'title' => $service->name,
    'description' => $service->description ?? "Service: {$service->name}",
    'length' => $service->duration_minutes ?? 30,
    'currency' => 'EUR',
    'price' => $service->price ?? 0,
    'hidden' => !$service->is_active,
    'requiresConfirmation' => $service->requires_confirmation ?? false,
    'disableGuests' => $service->max_attendees <= 1,
    'metadata' => [
        'service_id' => $service->id,
        'company_id' => $service->company_id,
        'category' => $service->category,
        'buffer_time' => $service->buffer_time_minutes ?? 0,
        'updated_at' => now()->toISOString(),
    ],
];
```

---

## Call Hierarchy

```
User edits Service in Admin
    ↓
Filament ServiceResource saves
    ↓
ServiceObserver::updating() [492.php:41]
    → Detects dirty fields
    → Sets sync_status = 'pending'
    ↓
ServiceObserver::updated() [492.php:73]
    → Checks: sync_status === 'pending'
    → Dispatches UpdateCalcomEventTypeJob
    ↓
UpdateCalcomEventTypeJob::handle() [UpdateCalcomEventTypeJob.php:46]
    → Calls CalcomService::updateEventType()
    → Makes PATCH request to Cal.com
    → Updates sync_status to 'synced' or 'error'
    ↓
CalcomService::updateEventType() [CalcomService.php:540]
    → Builds payload from Service fields
    → Makes PATCH /event-types/{id} request
    → Returns response
```

---

## Summary: What Each File Does

| File | Class | Purpose | Lines |
|------|-------|---------|-------|
| ServiceResource.php | ServiceResource | Admin UI for services | 722-800 (status column) |
| ServiceObserver.php | ServiceObserver | Detects changes, triggers sync | 41-84 |
| UpdateCalcomEventTypeJob.php | UpdateCalcomEventTypeJob | Syncs to Cal.com | 46-127 |
| ImportEventTypeJob.php | ImportEventTypeJob | Imports from Cal.com ⚠️ | 56-119 |
| CalcomWebhookController.php | CalcomWebhookController | Handles Cal.com webhooks | 144-194 |
| CalcomService.php | CalcomService | Cal.com API client | 540-588 |
| SyncCalcomServices.php | SyncCalcomServices | Manual sync command | All |
| Service.php | Service | Service model | 96-118, 225-266 |

