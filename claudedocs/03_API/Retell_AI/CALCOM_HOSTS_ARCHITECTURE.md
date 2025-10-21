# Cal.com Host Architecture - Visual Overview

**Updated**: 2025-10-21

---

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    CAL.COM V2 API                               │
│  ┌──────────────────────┬──────────────────────────────────┐   │
│  │  /v2/teams/{id}      │  /v2/teams/{id}/members         │   │
│  │  /event-types        │  ✅ IMPLEMENTED                 │   │
│  │  ✅ IMPLEMENTED      │                                  │   │
│  └──────────────────────┴──────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ⬇️
                     
┌─────────────────────────────────────────────────────────────────┐
│              SERVICE LAYER (CalcomV2Service)                     │
│  ┌──────────────────────┬──────────────────────────────────┐   │
│  │ fetchTeamMembers()   │ importTeamEventTypes()           │   │
│  │ → Get all Members    │ → Get all Event Types + Hosts    │   │
│  └──────────────────────┴──────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ⬇️
        ┌─────────────────────────────────────┐
        │  HOST MAPPING SERVICE               │
        │  CalcomHostMappingService           │
        │  ┌────────────────────────────┐   │
        │  │ Matching Strategies:       │   │
        │  │ 1. Email (95% confidence) │   │
        │  │ 2. Name (75% confidence)  │   │
        │  └────────────────────────────┘   │
        └─────────────────────────────────────┘
                              ⬇️
        ┌──────────────────────────────────────────────────┐
        │           DATABASE LAYER                        │
        ├──────────────────────────────────────────────────┤
        │  ┌─ calcom_team_members                        │
        │  │  ├─ calcom_user_id (PK from Cal.com)       │
        │  │  ├─ email                                   │
        │  │  ├─ name                                    │
        │  │  ├─ role (owner, admin, member)            │
        │  │  └─ is_active                              │
        │  │                                              │
        │  ├─ team_event_type_mappings                  │
        │  │  ├─ calcom_event_type_id                   │
        │  │  ├─ event_type_name                        │
        │  │  ├─ hosts (JSON Array) ← CRITICAL!        │
        │  │  │  [                                       │
        │  │  │    {"userId": 123, "name": "..."}      │
        │  │  │    {"userId": 456, "name": "..."}      │
        │  │  │  ]                                       │
        │  │  └─ is_team_event                         │
        │  │                                              │
        │  └─ calcom_host_mappings                      │
        │     ├─ calcom_host_id                         │
        │     ├─ staff_id (Local UUID)                  │
        │     ├─ calcom_name                           │
        │     ├─ confidence_score (0-100)              │
        │     └─ mapping_source (auto_email, etc)      │
        │                                                │
        └──────────────────────────────────────────────────┘
                              ⬇️
        ┌──────────────────────────────────────────────────┐
        │              LOCAL STAFF                        │
        ├──────────────────────────────────────────────────┤
        │  ┌─ Staff (Local Database)                    │
        │  │  ├─ id (UUID)                              │
        │  │  ├─ email                                  │
        │  │  ├─ name                                   │
        │  │  ├─ calcom_user_id (Linked)               │
        │  │  └─ company_id (Tenant)                    │
        │  │                                              │
        │  └─ Services (Per Staff)                      │
        │     ├─ id (UUID)                              │
        │     ├─ calcom_event_type_id                   │
        │     ├─ name                                   │
        │     └─ duration_minutes                       │
        │                                                │
        └──────────────────────────────────────────────────┘
```

---

## Data Flow: Team Member Sync

```
COMMAND: php artisan calcom:sync-team-members
    ⬇️
    ├─ Load all Companies with calcom_team_id
    ├─ For each Company:
    │  ⬇️
    │  1. API Call: GET /v2/teams/{teamId}/members
    │  ⬇️
    │  2. Response: { "members": [...] }
    │  ⬇️
    │  3. For each member:
    │     ├─ CalcomTeamMember::updateOrCreate()
    │     │  └─ Stores: userId, email, name, role, accepted
    │     │
    │     └─ CalcomHostMappingService::linkStaffToTeamMember()
    │        ├─ Strategy 1: Find Staff by Email
    │        │  └─ If found: Update staff.calcom_user_id ✅
    │        │
    │        └─ Strategy 2: Find Staff by Name (fuzzy)
    │           └─ If found: Update staff.calcom_user_id ⚠️
    ⬇️
    Report: "X members synced, Y staff linked"
```

---

## Data Flow: Event Type Import

```
JOB: ImportTeamEventTypesJob::dispatch($company)
    ⬇️
    1. API Call: GET /v2/event-types?teamId={id}
    ⬇️
    2. Response: { "data": [{ id, title, users, ... }] }
    ⬇️
    3. For each eventType:
       ├─ Create/Update Service
       │  ├─ Service.calcom_event_type_id = id
       │  ├─ Service.name = title
       │  └─ Service.duration_minutes = length
       │
       └─ Create/Update TeamEventTypeMapping
          ├─ TeamEventTypeMapping.hosts = Extract from response
          │  (or from Team Member data)
          │
          └─ Extract hosts array:
             [
               {"userId": 123, "name": "Thomas", "email": "..."},
               {"userId": 456, "name": "Sara", "email": "..."}
             ]
    ⬇️
    4. Optionally: Sync Team Members
       └─ Same as Command: calcom:sync-team-members
    ⬇️
    Update: Company.team_sync_status = 'synced'
```

---

## Data Flow: Availability + Host Resolution

```
REQUEST: Show availability for Service X
    ⬇️
    1. Load Service + Company
    ├─ Get Service.calcom_event_type_id
    └─ Get Company.calcom_team_id
    ⬇️
    2. Check Cache: week_availability:{teamId}:{serviceId}:{date}
    ⬇️
    3. If MISS:
       └─ API Call: GET /v2/slots/available?eventTypeId=X&teamId=Y
          ⬇️
          Response: { "data": { "slots": { "2025-10-21": ["09:00", ...] } } }
    ⬇️
    4. Transform to Week Structure
       └─ Map each slot to day + time
          └─ Timezone: UTC → Europe/Berlin
    ⬇️
    5. Cache for 60 seconds
    ⬇️
    6. Return to UI:
       {
         "monday": [
           {"time": "09:00", "full_datetime": "...", ...},
           {"time": "09:30", "full_datetime": "...", ...}
         ],
         "tuesday": [...]
       }
    
    ❌ NOTE: Host info NOT returned!
       Use TeamEventTypeMapping.hosts separately
```

---

## Data Resolution: Host → Staff

```
SCENARIO: Booking received with Host X

STEP 1: Extract Host from Cal.com Response
    CalcomHostMappingService::extractHostFromBooking()
    └─ Look for:
       1. booking.organizer
       2. booking.hosts[0]
       3. booking.responses.hosts[0]
    └─ Extract: { id, email, name, ... }

STEP 2: Find or Create Mapping
    CalcomHostMapping::findOrCreate(hostId)
    ├─ Existing mapping?
    │  └─ Return staff_id ✅
    │
    └─ No mapping? Try auto-discovery:
       ├─ Strategy 1: Email matching
       │  ├─ Find Staff where email = host.email
       │  └─ Confidence: 95% ✅ Auto-link
       │
       └─ Strategy 2: Name matching (fuzzy)
          ├─ Find Staff where name LIKE host.name
          └─ Confidence: 75% ⚠️ Manual review needed

STEP 3: Result
    ├─ Created CalcomHostMapping with staff_id
    ├─ Saved confidence_score
    ├─ Logged mapping_source (auto_email, auto_name, etc)
    └─ Appointment.staff_id = staff_id ✅
```

---

## Host Info Locations

```
Host Information can be found in:

1. CalcomTeamMember Table
   ├─ calcom_user_id
   ├─ email
   ├─ name
   ├─ username
   ├─ role
   └─ accepted

2. TeamEventTypeMapping.hosts JSON
   ├─ userId
   ├─ name
   ├─ email
   ├─ username
   └─ avatarUrl

3. CalcomHostMapping
   ├─ calcom_host_id
   ├─ calcom_name
   ├─ calcom_email
   ├─ calcom_username
   ├─ calcom_timezone
   └─ confidence_score

4. Staff (After Linking)
   ├─ id (UUID)
   ├─ calcom_user_id (Linked)
   ├─ email
   ├─ name
   └─ company_id
```

---

## Query Examples

### Get all Hosts for a Service

```php
$service = Service::find($serviceId);
$mapping = TeamEventTypeMapping::where(
    'calcom_event_type_id',
    $service->calcom_event_type_id
)->first();

$hosts = $mapping->hosts ?? [];  // Array mit Hosts
```

### Get Host with Confidence Score

```php
$hostMapping = CalcomHostMapping::where('calcom_host_id', $hostId)->first();
$staff = $hostMapping->staff;  // Lokale Staff Person
$confidence = $hostMapping->confidence_score;  // 0-100
```

### Get All Team Members

```php
$members = CalcomTeamMember::where(
    'calcom_team_id',
    $company->calcom_team_id
)->get();
```

### Get Auto-Linked Staff

```php
$linkedStaff = Staff::whereNotNull('calcom_user_id')->get();
$mappedHosts = CalcomHostMapping::where(
    'confidence_score', '>=', 75
)->get();
```

---

## Confidence Score Distribution

```
Distribution after sync:
├─ 95% confidence (Email matched)
│  └─ Auto-linked immediately ✅
│  └─ ~70% of cases
│
├─ 75% confidence (Name fuzzy match)
│  └─ Auto-linked if threshold met ✅
│  └─ ~20% of cases
│
├─ 0% confidence (No match)
│  └─ Requires manual mapping ❌
│  └─ ~10% of cases
│
└─ Config:
   auto_threshold = config('booking.staff_matching.auto_threshold', 75)
```

---

## Cache Keys Pattern

```
week_availability:{teamId}:{serviceId}:{weekStartDate}
├─ Example: week_availability:123:uuid-123:2025-10-21
├─ TTL: 60 seconds
└─ Invalidated: 30 days lookhead

calcom:slots:{teamId}:{eventTypeId}:{startDate}:{endDate}
├─ Example: calcom:slots:123:456:2025-10-21:2025-10-27
├─ TTL: 60 seconds (adaptive)
└─ Invalidated: Double-layer on booking

cal_slots_{companyId}_{branchId}_{eventTypeId}_{startTime}_{endTime}
├─ Pattern: AppointmentAlternativeFinder cache
├─ TTL: Variable
└─ Invalidated: Same as Calcom slots
```

---

## Sync Frequency Recommendations

```
Optimal Schedule:

Daily (via Scheduler):
├─ php artisan calcom:sync-team-members  (Off-peak)
└─ Runs at: 00:30 UTC (= 01:30 CET)

On-Demand:
├─ When Company.calcom_team_id is set (Filament UI)
├─ When Service.calcom_event_type_id is updated
└─ Manual via: php artisan calcom:sync-team-members --company=X

Frequency: 1x per day + manual
Retry Policy: 3 attempts with backoff [60s, 300s, 900s]
Timeout: 5 minutes max
```

---

**Last Updated**: 2025-10-21  
**API Version**: Cal.com V2 with V1 fallback  
**Status**: ✅ VERIFIED & DOCUMENTED
