# Missing CalcomEventMaps - Setup Guide

**Date**: 2025-11-23 23:15 CET
**Status**: 6 Staff/Service combinations missing CalcomEventMaps
**Priority**: 🟡 MEDIUM - Prevents booking sync for affected combinations

---

## Executive Summary

**Found**: 6 missing CalcomEventMap configurations

**Impact**:
- Appointments CAN be created in our system ✅
- Availability checks work correctly ✅
- **BUT**: Cal.com sync will FAIL ❌
- User gets error message: "Beim Buchen ist ein Fehler aufgetreten"

**Solution**: Create CalcomEventMaps for 3 services × 2 Fabian Spitzer accounts = 6 configurations

---

## Missing Configurations

### Overview

| Service | Staff | Cal.com User ID | Email |
|---------|-------|----------------|-------|
| Ansatzfärbung | Fabian Spitzer (1) | 1414768 | fabianspitzer@icloud.com |
| Ansatzfärbung | Fabian Spitzer (2) | 1346408 | fabhandy@googlemail.com |
| Ansatz + Längenausgleich | Fabian Spitzer (1) | 1414768 | fabianspitzer@icloud.com |
| Ansatz + Längenausgleich | Fabian Spitzer (2) | 1346408 | fabhandy@googlemail.com |
| Komplette Umfärbung (Blondierung) | Fabian Spitzer (1) | 1414768 | fabianspitzer@icloud.com |
| Komplette Umfärbung (Blondierung) | Fabian Spitzer (2) | 1346408 | fabhandy@googlemail.com |

---

## Detailed Requirements

### 1. Ansatzfärbung (Service ID: 440)

**Composite Service**: YES
**Total Duration**: ~130 minutes

**Segments (Active phases requiring Cal.com Event Types)**:

```
Segment A: Ansatzfärbung auftragen
  - Staff Required: YES ✅
  - Needs: Cal.com Event Type

Segment B: Auswaschen
  - Staff Required: YES ✅
  - Needs: Cal.com Event Type

Segment C: Formschnitt
  - Staff Required: YES ✅
  - Needs: Cal.com Event Type

Segment D: Föhnen & Styling
  - Staff Required: YES ✅
  - Needs: Cal.com Event Type

Segment GAP_A: Einwirkzeit Ansatzfarbe
  - Staff Required: NO ⏸️
  - No Cal.com Event Type needed (gap phase)
```

**Required CalcomEventMaps per Staff**: 4 (one for each active segment)

---

### 2. Ansatz + Längenausgleich (Service ID: 442)

**Composite Service**: YES
**Total Duration**: ~variable

**Segments (Active phases)**:

```
Segment A: Ansatzfärbung & Längenausgleich auftragen
  - Needs: Cal.com Event Type

Segment B: Auswaschen
  - Needs: Cal.com Event Type

Segment C: Formschnitt
  - Needs: Cal.com Event Type

Segment D: Föhnen & Styling
  - Needs: Cal.com Event Type
```

**Required CalcomEventMaps per Staff**: 4

---

### 3. Komplette Umfärbung (Blondierung) (Service ID: 444)

**Composite Service**: YES
**Total Duration**: ~variable

**Segments (Active phases)**:

```
Segment A: Blondierung auftragen
  - Needs: Cal.com Event Type

Segment B: Auswaschen & Pflege
  - Needs: Cal.com Event Type

Segment C: Formschnitt
  - Needs: Cal.com Event Type

Segment D: Föhnen & Styling
  - Needs: Cal.com Event Type

Segment GAP_A: Einwirkzeit Blondierung
  - NO Cal.com Event Type needed (gap phase)
```

**Required CalcomEventMaps per Staff**: 4

---

## Total Required Event Types

**For Fabian Spitzer (fabianspitzer@icloud.com - User ID 1414768)**:
- Ansatzfärbung: 4 Event Types
- Ansatz + Längenausgleich: 4 Event Types
- Komplette Umfärbung: 4 Event Types
- **Total**: 12 Event Types

**For Fabian Spitzer (fabhandy@googlemail.com - User ID 1346408)**:
- Ansatzfärbung: 4 Event Types
- Ansatz + Längenausgleich: 4 Event Types
- Komplette Umfärbung: 4 Event Types
- **Total**: 12 Event Types

**Grand Total**: 24 Cal.com Event Types + 24 CalcomEventMap DB entries

---

## Step-by-Step Setup Guide

### Phase 1: Cal.com Event Type Creation

For EACH combination (Service × Segment × Staff):

#### 1.1 Login to Cal.com
- Login as Admin
- Navigate to Event Types

#### 1.2 Create MANAGED Event Type (Template)

**For each segment** of each service:

**Example: Ansatzfärbung - Segment A (Ansatzfärbung auftragen)**

```
Name: "Ansatzfärbung: Ansatzfärbung auftragen (1 von 4) - {Staff Name}"
Duration: [segment duration] minutes
Type: MANAGED (Team Event Type)
Team: Friseur 1
Assigned Staff: Fabian Spitzer (select correct Cal.com account)
```

**IMPORTANT**: Cal.com MANAGED Event Types create a **parent template** with a **child Event Type** for each assigned staff member.

**You need to note down**:
- Parent Event Type ID (from URL when editing the event type)
- Child Event Type ID (from API or team member's calendar)

#### 1.3 Repeat for all segments

**Ansatzfärbung** (4 Event Types per staff):
1. Ansatzfärbung auftragen (1 von 4)
2. Auswaschen (2 von 4)
3. Formschnitt (3 von 4)
4. Föhnen & Styling (4 von 4)

**Ansatz + Längenausgleich** (4 Event Types per staff):
1. Ansatzfärbung & Längenausgleich auftragen (1 von 4)
2. Auswaschen (2 von 4)
3. Formschnitt (3 von 4)
4. Föhnen & Styling (4 von 4)

**Komplette Umfärbung** (4 Event Types per staff):
1. Blondierung auftragen (1 von 4)
2. Auswaschen & Pflege (2 von 4)
3. Formschnitt (3 von 4)
4. Föhnen & Styling (4 von 4)

---

### Phase 2: CalcomEventMap Database Entries

For EACH Event Type created above, create a `calcom_event_map` entry.

#### Template SQL:

```sql
INSERT INTO calcom_event_map (
    service_id,
    segment_key,
    staff_id,
    event_type_id,
    child_event_type_id,
    created_at,
    updated_at
) VALUES (
    ?,  -- service_id (440, 442, or 444)
    ?,  -- segment_key ('A', 'B', 'C', or 'D')
    ?,  -- staff_id (Fabian's UUID)
    ?,  -- event_type_id (Parent ID from Cal.com)
    ?,  -- child_event_type_id (Child ID from Cal.com)
    NOW(),
    NOW()
);
```

---

## Detailed Mapping Tables

### For Fabian Spitzer (fabianspitzer@icloud.com)

**Staff ID**: `6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe`
**Cal.com User ID**: `1414768`

#### Ansatzfärbung (Service 440)

| Segment | Name | Parent Event Type ID | Child Event Type ID |
|---------|------|---------------------|---------------------|
| A | Ansatzfärbung auftragen | [TBD - from Cal.com] | [TBD - from Cal.com] |
| B | Auswaschen | [TBD - from Cal.com] | [TBD - from Cal.com] |
| C | Formschnitt | [TBD - from Cal.com] | [TBD - from Cal.com] |
| D | Föhnen & Styling | [TBD - from Cal.com] | [TBD - from Cal.com] |

#### Ansatz + Längenausgleich (Service 442)

| Segment | Name | Parent Event Type ID | Child Event Type ID |
|---------|------|---------------------|---------------------|
| A | Ansatzfärbung & Längenausgleich auftragen | [TBD] | [TBD] |
| B | Auswaschen | [TBD] | [TBD] |
| C | Formschnitt | [TBD] | [TBD] |
| D | Föhnen & Styling | [TBD] | [TBD] |

#### Komplette Umfärbung (Service 444)

| Segment | Name | Parent Event Type ID | Child Event Type ID |
|---------|------|---------------------|---------------------|
| A | Blondierung auftragen | [TBD] | [TBD] |
| B | Auswaschen & Pflege | [TBD] | [TBD] |
| C | Formschnitt | [TBD] | [TBD] |
| D | Föhnen & Styling | [TBD] | [TBD] |

---

### For Fabian Spitzer (fabhandy@googlemail.com)

**Staff ID**: `9f47fda1-977c-47aa-a87a-0e8cbeaeb119`
**Cal.com User ID**: `1346408`

(Same structure as above - create separate Event Types and CalcomEventMaps)

---

## How to Get Child Event Type IDs

### Method 1: Cal.com API (Recommended)

```bash
# Use CalcomChildEventTypeResolver service
php artisan tinker

$company = App\Models\Company::first();
$resolver = new App\Services\CalcomChildEventTypeResolver($company);

$parentEventTypeId = 3757749; // Example parent ID
$staffId = '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe';

$childId = $resolver->resolveChildEventTypeId($parentEventTypeId, $staffId);
echo "Child Event Type ID: {$childId}\n";
```

### Method 2: Manual Lookup

1. Go to Cal.com Team Settings
2. Find the MANAGED Event Type
3. Click on it
4. Look at the URL: `/event-types/[PARENT_ID]`
5. Check "Assigned Members" section
6. Each member has a child Event Type ID (visible in their individual calendar)

---

## Verification Query

After creating CalcomEventMaps, run this to verify:

```sql
SELECT
    s.name as service,
    st.name as staff,
    cem.segment_key,
    cem.event_type_id as parent_id,
    cem.child_event_type_id as child_id
FROM calcom_event_map cem
JOIN services s ON cem.service_id = s.id
JOIN staff st ON cem.staff_id = st.id
WHERE cem.service_id IN (440, 442, 444)
  AND cem.staff_id IN (
      '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
      '9f47fda1-977c-47aa-a87a-0e8cbeaeb119'
  )
ORDER BY s.name, st.name, cem.segment_key;
```

**Expected Result**: 24 rows (3 services × 4 segments × 2 staff)

---

## Testing After Setup

### Test Case 1: Book Ansatzfärbung with Fabian (Account 1)

```
1. Call system
2. Request: "Ansatzfärbung, Freitag 10 Uhr"
3. System assigns: Fabian Spitzer (fabianspitzer@icloud.com)
4. Expected: Booking succeeds, Cal.com sync succeeds
5. Verify: 4 Cal.com bookings created (one per segment)
```

### Test Case 2: Book Ansatzfärbung with Fabian (Account 2)

```
1. Call system
2. Request: "Ansatzfärbung, Freitag 14 Uhr"
3. System assigns: Fabian Spitzer (fabhandy@googlemail.com)
4. Expected: Booking succeeds, Cal.com sync succeeds
5. Verify: 4 Cal.com bookings created
```

---

## Alternative: Disable Services for Staff Without Event Maps

If you DON'T want Fabian to perform these services:

```sql
-- Remove Fabian from service_staff
UPDATE service_staff
SET is_active = 0
WHERE service_id IN (440, 442, 444)
  AND staff_id IN (
      '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
      '9f47fda1-977c-47aa-a87a-0e8cbeaeb119'
  );
```

**Impact**:
- Fabian will NOT be offered for these services
- Emma Williams (who has Event Maps) will be assigned instead
- No sync errors

---

## Quick Reference Script

```bash
# Find missing CalcomEventMaps
php find_missing_event_maps.php

# After creating Event Types, populate CalcomEventMaps
# (Use the SQL template above for each Event Type)
```

---

## Timeline Estimate

**Cal.com Event Type Creation**: ~30 minutes
- 12 Event Types per Fabian account × 2 accounts = 24 Event Types
- ~1-2 minutes per Event Type

**CalcomEventMap Database Entries**: ~15 minutes
- 24 INSERT statements
- Can be scripted/batched

**Testing**: ~15 minutes
- 2 test calls (one per Fabian account)
- Verify Cal.com bookings

**Total**: ~60 minutes

---

## Status Tracking

### Ansatzfärbung (Service 440)

**Fabian (fabianspitzer@icloud.com)**:
- [ ] Segment A Event Type created
- [ ] Segment A CalcomEventMap created
- [ ] Segment B Event Type created
- [ ] Segment B CalcomEventMap created
- [ ] Segment C Event Type created
- [ ] Segment C CalcomEventMap created
- [ ] Segment D Event Type created
- [ ] Segment D CalcomEventMap created

**Fabian (fabhandy@googlemail.com)**:
- [ ] Segment A Event Type created
- [ ] Segment A CalcomEventMap created
- [ ] Segment B Event Type created
- [ ] Segment B CalcomEventMap created
- [ ] Segment C Event Type created
- [ ] Segment C CalcomEventMap created
- [ ] Segment D Event Type created
- [ ] Segment D CalcomEventMap created

### Ansatz + Längenausgleich (Service 442)

(Same checklist as above)

### Komplette Umfärbung (Service 444)

(Same checklist as above)

---

**Status**: 📋 READY FOR SETUP
**Priority**: 🟡 MEDIUM
**Estimated Time**: 60 minutes
**Blocker**: None - system works for other staff/services
