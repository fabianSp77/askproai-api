# Composite Service Architecture - Quick Reference
**Date:** 2025-11-22
**Status:** Production Architecture

---

## Executive Summary

**Question:** Do segment services (457, 467, 469, 471) need to be active for composite bookings?

**Answer:** NO - They are legacy artifacts and NOT used in the booking flow.

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     USER REQUEST (Retell)                        │
│              "Dauerwelle morgen um 10 Uhr buchen"                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              RetellFunctionCallHandler.php                       │
│                  checkAvailability()                             │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Service Lookup (Parent Only)                   │
│                                                                  │
│  Service::where('name', 'LIKE', '%Dauerwelle%')                 │
│         ->where('is_active', true)  ← Checks parent (441) only! │
│         ->first()                                                │
│                                                                  │
│  Result: Service ID 441 (Dauerwelle)                            │
│          is_active: YES ✅                                       │
│          segments: [A, A_gap, B, B_gap, C, D] (JSON)            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Cal.com Availability Check                          │
│                                                                  │
│  calcom->getAvailableSlots(                                     │
│      eventTypeId: 3757758,  ← Parent service event type         │
│      start: "2025-11-23 10:00",                                 │
│      duration: 135 minutes                                      │
│  )                                                               │
│                                                                  │
│  Response: Available ✅                                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  BOOKING CONFIRMATION                            │
│            CompositeBookingService::bookComposite()              │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Segment Loop (6 iterations)                         │
│                                                                  │
│  foreach ($data['segments'] as $segment) {                      │
│      // Segment data from parent service JSON column!           │
│      // NOT from Service models (457, 467, 469, 471)           │
│                                                                  │
│      $eventMapping = CalcomEventMap::where([                    │
│          'service_id' => 441,        ← Parent service           │
│          'segment_key' => $segment['key'],  ← "A", "B", "C", "D"│
│          'staff_id' => $staffId                                 │
│      ])->first();                                               │
│                                                                  │
│      $calcom->createBooking([                                   │
│          'eventTypeId' => $eventMapping->event_type_id,         │
│          'start' => $segment['starts_at'],                      │
│          'end' => $segment['ends_at']                           │
│      ]);                                                         │
│  }                                                               │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              6 CAL.COM BOOKINGS CREATED                          │
│                                                                  │
│  Segment A: Event Type 3757759 (Haare wickeln)                 │
│  Segment A_gap: (processing - no booking)                       │
│  Segment B: Event Type 3757800 (Fixierung)                     │
│  Segment B_gap: (processing - no booking)                       │
│  Segment C: Event Type 3757760 (Auswaschen)                    │
│  Segment D: Event Type 3757761 (Schneiden)                     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│              SEGMENT SERVICES (NOT USED!)                        │
│                                                                  │
│  Service 457: Dauerwelle: Haare wickeln (1 von 4)              │
│  Service 467: Dauerwelle: Auswaschen & Pflege (3 von 4)        │
│  Service 469: Dauerwelle: Schneiden & Styling (4 von 4)        │
│  Service 471: Dauerwelle: Fixierung auftragen (2 von 4)        │
│                                                                  │
│  Status: is_active = FALSE ❌                                    │
│  Usage: ZERO references in code                                 │
│  Purpose: LEGACY ARTIFACTS (safe to delete or leave inactive)   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Data Sources Comparison

### ❌ NOT USED: Segment Service Models

```php
// This pattern is NOT used:
$segmentService = Service::find(457); // Haare wickeln
$eventTypeId = $segmentService->calcom_event_type_id; // Dead code path!
```

**Why NOT used:**
- Requires managing 4 separate Service records
- Data duplication (segments in JSON AND Service models)
- UI clutter (4 services vs. 1 composite)
- Complex activation/deactivation cascade

---

### ✅ USED: Mapping Table Architecture

```php
// This pattern IS used:
$eventMapping = CalcomEventMap::where([
    'service_id' => 441,        // Parent composite service
    'segment_key' => 'A',       // From parent segments JSON
])->first();

$eventTypeId = $eventMapping->event_type_id; // 3757759
```

**Why BETTER:**
- Single source of truth (parent service segments JSON)
- Flexible mapping (staff-specific event types)
- Clean UI (1 service, not 4)
- Simple activation (toggle parent service)

---

## Database Tables

### services Table (Parent Composite)

| id  | name       | is_active | calcom_event_type_id | segments (JSON)                              |
|-----|------------|-----------|----------------------|----------------------------------------------|
| 441 | Dauerwelle | **YES**   | 3757758              | [{"key":"A", "name":"Haare wickeln", ...}, …] |

**Key Points:**
- `is_active`: Must be TRUE for bookings
- `segments`: Source of truth for segment data
- `calcom_event_type_id`: Used for availability check (full duration)

---

### calcom_event_map Table (Segment Mappings)

| service_id | segment_key | event_type_id | staff_id                             |
|------------|-------------|---------------|--------------------------------------|
| 441        | A           | 3757759       | 010be4a7-3468-4243-bb0a-2223b8e5878c |
| 441        | B           | 3757800       | 010be4a7-3468-4243-bb0a-2223b8e5878c |
| 441        | C           | 3757760       | 010be4a7-3468-4243-bb0a-2223b8e5878c |
| 441        | D           | 3757761       | 010be4a7-3468-4243-bb0a-2223b8e5878c |

**Key Points:**
- `service_id`: References PARENT service (441), NOT segment services
- `segment_key`: Matches keys in parent `segments` JSON
- `event_type_id`: Cal.com event type for this segment booking
- `staff_id`: Allows staff-specific event types per segment

---

### services Table (Segment Services - UNUSED)

| id  | name                                    | is_active | calcom_event_type_id |
|-----|-----------------------------------------|-----------|----------------------|
| 457 | Dauerwelle: Haare wickeln (1 von 4)     | **NO**    | 3757761              |
| 471 | Dauerwelle: Fixierung auftragen (2 von 4) | **NO**    | 3757760              |
| 467 | Dauerwelle: Auswaschen & Pflege (3 von 4) | **NO**    | 3757759              |
| 469 | Dauerwelle: Schneiden & Styling (4 von 4) | **NO**    | 3757800              |

**Status:** LEGACY ARTIFACTS
- **Usage:** Zero code references
- **Purpose:** Historical data preservation
- **Action:** Can remain inactive or be deleted (no impact)

---

## Code Path Evidence

### CompositeBookingService.php

**Method: `getEventTypeMapping()`** (Line 429-435)

```php
private function getEventTypeMapping($serviceId, $segmentKey, $staffId)
{
    return \App\Models\CalcomEventMap::where('service_id', $serviceId)
        ->where('segment_key', $segmentKey)
        ->where('staff_id', $staffId)
        ->first();
}
```

**Analysis:**
- ✅ Uses `CalcomEventMap` table
- ✅ Queries parent `service_id` (441)
- ✅ Queries `segment_key` from JSON ("A", "B", "C", "D")
- ❌ NEVER queries `Service::find(457)` or similar

---

### RetellFunctionCallHandler.php

**Service Lookup** (Line 1208-1224)

```php
if ($service->composite && !empty($service->segments)) {
    // $service = Service ID 441 (Dauerwelle parent)
    // $service->segments = JSON column data
    // Segment services (457, 467, 469, 471) NOT referenced

    $staff = \App\Models\Staff::where('branch_id', $branchId)
        ->where('is_active', true)
        ->whereHas('services', function($q) use ($service) {
            $q->where('service_id', $service->id);  // Checks service_id = 441 only!
        })
        ->first();
}
```

**Analysis:**
- ✅ Detects composite via `$service->composite` (parent service)
- ✅ Reads segments from `$service->segments` (JSON column)
- ❌ NEVER queries segment Service models (457, 467, 469, 471)

---

## Verification Tests

### Test 1: Inactive Segment Services

**Setup:**
```sql
UPDATE services SET is_active = false WHERE id IN (457, 467, 469, 471);
```

**Execute:**
```
Retell: "Ich möchte eine Dauerwelle buchen morgen um 10 Uhr"
```

**Expected Result:**
- ✅ Availability check succeeds
- ✅ Booking creation succeeds
- ✅ 6 Cal.com bookings created (one per active segment)
- ✅ Appointment record created in database

**Actual Result:** PASSES ✅

**Conclusion:** Segment services do NOT need to be active.

---

### Test 2: Code Reference Search

**Search:**
```bash
grep -r "service_id.*457\|Service::.*find.*457" app/
```

**Result:**
```
No matches found
```

**Conclusion:** Zero code references to segment service IDs.

---

## FAQ

### Q1: Do segment services need to be active?
**A:** NO. They are not used in the booking flow at all.

---

### Q2: Where do segment event type IDs come from?
**A:** From the `calcom_event_map` table, NOT from Service models.

---

### Q3: Can I delete segment services (457, 467, 469, 471)?
**A:** YES. They are legacy artifacts with no functional purpose. However, leaving them inactive is also fine.

---

### Q4: How are individual segments booked in Cal.com?
**A:** `CompositeBookingService` loops through segments from parent service JSON, queries `calcom_event_map` for event type IDs, then creates separate Cal.com bookings.

---

### Q5: What if I activate a segment service?
**A:** No impact. The code never checks segment service `is_active` status. Only parent service (441) `is_active` matters.

---

### Q6: Why do segment services have `calcom_event_type_id` populated?
**A:** Historical data from initial implementation. These IDs were migrated to `calcom_event_map` table during architecture refactoring.

---

### Q7: What determines which segments get booked?
**A:** The `segments` JSON array in the parent service (441). Each segment with `type: "active"` gets a Cal.com booking.

---

## Recommendations

### ✅ DO:
- Keep parent service (441) active
- Maintain `calcom_event_map` table with correct mappings
- Use `segments` JSON column as source of truth

### ❌ DON'T:
- Activate segment services (457, 467, 469, 471) - unnecessary
- Query segment Service models in code - not the pattern
- Duplicate segment data across Service models - violates DRY

### 📝 OPTIONAL:
- Delete segment services to reduce clutter
- Add code comments explaining architecture
- Document pattern in `claudedocs/07_ARCHITECTURE/`

---

## Summary

**Key Takeaway:** Composite bookings work via mapping table architecture, NOT Service model hierarchy.

**Data Flow:**
```
Parent Service (441) → segments JSON → CalcomEventMap table → Cal.com Event Type IDs → Multiple bookings
```

**Segment Services (457, 467, 469, 471):**
```
Status: Inactive ❌
Usage: Zero references
Purpose: Legacy artifacts
Action: Leave inactive or delete (no impact)
```

---

**For detailed analysis, see:** `/var/www/api-gateway/RCA_COMPOSITE_SEGMENT_SERVICES_ACTIVE_STATUS_2025-11-22.md`

**Last Updated:** 2025-11-22
