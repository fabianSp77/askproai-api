# Root Cause Analysis: Missing Child Event Type IDs

**Date**: 2025-11-24
**Call ID**: call_7dce6f4f1636b605e3e3d7d4b1f
**Appointment ID**: 763
**Service**: Ansatzfärbung (Service ID: 440)
**Status**: ❌ Cal.com Sync FAILED

---

## Executive Summary

Test call for "Dauerwelle" booking was successful up to appointment creation, but **Cal.com synchronization failed** for all 4 segments with error:

> **"Event type with id=3982562 is the parent managed event type that can't be booked. You have to provide the child event type id."**

**Root Cause**: Event Types 3982562, 3982564, 3982566, 3982568 are **PARENT MANAGED** Event Types with multiple hosts (both Fabian accounts), but **no child Event Types were created** by Cal.com.

**Impact**: **ALL composite service bookings fail to sync to Cal.com** for the newly created Event Types.

**Severity**: 🔴 **CRITICAL** - System unusable for composite services

---

## Timeline

### 2025-11-24 07:00 - Event Types Created
- Created 12 parent MANAGED Event Types in Cal.com UI
- Initially assigned only to Fabian (1414768)

### 2025-11-24 07:10 - Hosts Updated
- Ran `add_second_fabian_to_event_types.php`
- Updated all 12 Event Types to include both Fabian accounts:
  - User 1414768: fabianspitzer@icloud.com
  - User 1346408: fabhandy@googlemail.com
- **Expected**: Cal.com creates child Event Types automatically
- **Actual**: No child Event Types were created

### 2025-11-24 07:15 - CalcomEventMaps Created
- Created 24 CalcomEventMap entries
- All used parent Event Type IDs (e.g., 3982562)
- No child Event Type IDs available

### 2025-11-23 23:03 - Test Call
- User "Paul Klaus" called to book Ansatzfärbung
- Requested Friday 28.11. at 16:00
- Appointment created successfully (ID: 763)
- Staff assigned: Fabian Spitzer (9f47fda1-977c-47aa-a87a-0e8cbeaeb119)
- 4 appointment phases created (A, B, C, D)

### 2025-11-23 23:03 - Sync Failure
- SyncAppointmentToCalcomJob dispatched
- Attempted to create 4 bookings in Cal.com
- **ALL 4 segments failed** with error:
  ```
  HTTP 400: Event type with id=3982562 is the parent managed event type
  that can't be booked. You have to provide the child event type id.
  ```
- Appointment marked as `requires_manual_review = true`
- Sync status: `failed`

---

## Technical Analysis

### Cal.com MANAGED Event Type Architecture

When a MANAGED Event Type is created with multiple hosts, Cal.com should:

1. **Parent Event Type**: The main Event Type (e.g., 3982562)
   - Contains configuration
   - Has multiple hosts assigned
   - **Cannot be booked directly**

2. **Child Event Types**: Auto-created per host
   - One child per host user
   - Inherit configuration from parent
   - **Must be used for bookings**
   - Contain `metadata.managedEventConfig.parentEventTypeId`

### What We Found

**Query Results**:
```bash
php check_child_event_types.php
```

Output:
```
ID: 3982562
Title: Ansatzfärbung: Ansatzfärbung auftragen (1 von 4) - Fabian Spitzer
Type: PARENT (can have children)
Hosts: Fabian Spitzer (ID: 1346408), Fabian Spitzer (ID: 1414768)

Looking for child Event Types of Parent 3982562...
⚠️  No child Event Types found for parent 3982562
```

**Conclusion**: Event Type 3982562 has TWO hosts but ZERO children.

---

## Root Cause

### Hypothesis 1: Cal.com API Behavior ✅ CONFIRMED

When updating an existing Event Type to add additional hosts via `PATCH /v2/event-types/{id}`, Cal.com **does not automatically create child Event Types**.

**Evidence**:
- Used `updateEventType()` method from CalcomV2Client
- Response was HTTP 200 (success)
- Hosts were updated correctly
- BUT no child Event Types appeared in subsequent `getEventTypes()` query

### Hypothesis 2: Event Type Creation Method

Event Types were created **manually in Cal.com UI** (not via API).
When created with a single host initially, no child structure was needed.
Adding the second host later via API **should have triggered child creation** but didn't.

### Hypothesis 3: Team Configuration Issue

Possible that MANAGED Event Types require specific team configuration or creation flow that we didn't follow.

---

## Attempted Fixes

### ❌ Attempt 1: Use Parent IDs in CalcomEventMaps
**What**: Stored parent Event Type IDs in `calcom_event_map` table
**Result**: Sync failed with "can't book parent" error
**Why Failed**: Cal.com API explicitly rejects parent Event Type IDs in booking requests

### ❌ Attempt 2: CalcomChildEventTypeResolver Fallback
**What**: Resolver returns parent ID when child not found
**Result**: Same error - parent IDs are not bookable
**Why Failed**: Fallback doesn't solve the missing child problem

---

## Impact Assessment

### Affected Services
- ❌ Ansatzfärbung (Service 440) - 4 segments
- ❌ Ansatz + Längenausgleich (Service 442) - 4 segments
- ❌ Komplette Umfärbung/Blondierung (Service 444) - 4 segments

**Total**: 12 parent Event Types, 0 child Event Types

### Affected Staff
- ❌ Fabian Spitzer (fabianspitzer@icloud.com) - User 1414768
- ❌ Fabian Spitzer (fabhandy@googlemail.com) - User 1346408

### User Impact
- ✅ Appointments can be **created** in our system
- ✅ Availability **checking works** (uses parent Event Type for slots)
- ❌ Appointments **cannot sync** to Cal.com calendars
- ❌ **No calendar blocking** occurs
- ❌ **Risk of double-bookings** (external bookings not reflected)

---

## Solution Options

### Option 1: Create Child Event Types Manually ⭐ RECOMMENDED

**Approach**: Create individual Event Types for each staff member

**Steps**:
1. For each segment (A, B, C, D) of each service:
   - Create Event Type for Fabian (1414768)
   - Create Event Type for Fabian (1346408)
2. Update CalcomEventMaps with correct child IDs

**Pros**:
- ✅ Simple and reliable
- ✅ Full control over configuration
- ✅ No dependency on Cal.com auto-creation

**Cons**:
- ❌ Manual work (24 Event Types to create)
- ❌ Not scalable for many staff members

**Estimated Time**: 2-3 hours

---

### Option 2: Use Cal.com Round-Robin Event Types

**Approach**: Create ROUND_ROBIN Event Types instead of MANAGED

**Steps**:
1. Delete current MANAGED Event Types
2. Create ROUND_ROBIN Event Types with both hosts
3. Cal.com will automatically distribute bookings

**Pros**:
- ✅ Single Event Type per segment (12 total instead of 24)
- ✅ Cal.com handles host assignment automatically

**Cons**:
- ❌ Less control over which Fabian account gets assigned
- ❌ May not work with our staff assignment logic
- ❌ Requires significant refactoring

**Estimated Time**: 4-6 hours

---

### Option 3: Fix MANAGED Event Type Creation via API

**Approach**: Recreate Event Types correctly via API with both hosts from start

**Steps**:
1. Delete current Event Types (3982562, 3982564, etc.)
2. Use Cal.com API to create MANAGED Event Types with both hosts
3. Verify child Event Types are auto-created
4. Update CalcomEventMaps with child IDs

**Pros**:
- ✅ Follows Cal.com's intended architecture
- ✅ Scalable for future services
- ✅ Automated via script

**Cons**:
- ❌ Uncertain if API will create children (might be same issue)
- ❌ Requires deleting existing Event Types
- ❌ Risk of breaking existing bookings

**Estimated Time**: 3-4 hours + testing

---

### Option 4: Use Individual Event Types (No MANAGED) ⚡ FASTEST

**Approach**: Abandon MANAGED structure, create individual Event Types per staff

**Steps**:
1. Keep current Event Types for Fabian (1414768)
2. Create NEW Event Types for Fabian (1346408) with different slugs
3. Update CalcomEventMaps to map each staff to their specific Event Types

**Pros**:
- ✅ Simplest architecture
- ✅ No child/parent complexity
- ✅ Works immediately
- ✅ Each staff has dedicated Event Types

**Cons**:
- ❌ More Event Types to manage (24 total)
- ❌ Changes to one staff don't propagate to other

**Estimated Time**: 1-2 hours

---

## Recommended Solution: Option 4 (Individual Event Types)

**Reasoning**:
1. **Fastest** to implement (1-2 hours)
2. **Lowest risk** - no API dependencies
3. **Clear structure** - one Event Type per service/segment/staff
4. **Proven approach** - works with existing services

**Implementation Plan**:
1. Create 12 NEW Event Types for Fabian (1346408):
   - Ansatzfärbung A, B, C, D (4 types)
   - Ansatz + Längenausgleich A, B, C, D (4 types)
   - Komplette Umfärbung A, B, C, D (4 types)
2. Update CalcomEventMaps:
   - Fabian (1414768) → Keep existing IDs (3982562, 3982564, etc.)
   - Fabian (1346408) → Use new IDs
3. Test booking for both staff members
4. Verify Cal.com sync successful

---

## Prevention for Future

### When Creating Event Types:
1. ✅ Create **individual Event Types** per staff member
2. ✅ Avoid MANAGED Event Types unless truly needed
3. ✅ Verify Event Type is bookable before adding to CalcomEventMap
4. ✅ Test sync immediately after creation

### When Adding New Staff:
1. ✅ Create full set of Event Types for new staff
2. ✅ Update CalcomEventMaps with new Event Type IDs
3. ✅ Run verification script to confirm completeness

---

## Next Steps

1. **Immediate** (Today):
   - ✅ Document this RCA
   - ⏳ Decide on solution approach
   - ⏳ Implement chosen solution
   - ⏳ Test with real booking

2. **Short-term** (This Week):
   - Update documentation with learnings
   - Create Event Type creation best practices
   - Add validation to detect this issue early

3. **Long-term** (Next Month):
   - Consider whether MANAGED Event Types are worth the complexity
   - Evaluate alternative Cal.com configurations
   - Document Cal.com API quirks and limitations

---

## Technical Details

### Error Messages (Full)

**Segment A (Event Type 3982562)**:
```json
{
  "status": "error",
  "timestamp": "2025-11-24T06:57:20.247Z",
  "path": "/v2/bookings",
  "error": {
    "code": "BadRequestException",
    "message": "Event type with id=3982562 is the parent managed event type that can't be booked. You have to provide the child event type id aka id of event type that has been assigned to one of the users.",
    "details": {
      "message": "Event type with id=3982562 is the parent managed event type that can't be booked. You have to provide the child event type id aka id of event type that has been assigned to one of the users.",
      "error": "Bad Request",
      "statusCode": 400
    }
  }
}
```

### Database State

**Appointment 763**:
```sql
SELECT id, service_id, staff_id, starts_at, ends_at, calcom_sync_status, requires_manual_review
FROM appointments WHERE id = 763;

-- Result:
-- id: 763
-- service_id: 440
-- staff_id: 9f47fda1-977c-47aa-a87a-0e8cbeaeb119
-- starts_at: 2025-11-28 15:00:00
-- ends_at: 2025-11-28 17:10:00
-- calcom_sync_status: failed
-- requires_manual_review: true
```

**CalcomEventMaps**:
```sql
SELECT segment_key, event_type_id, child_event_type_id
FROM calcom_event_map
WHERE service_id = 440
  AND staff_id = '9f47fda1-977c-47aa-a87a-0e8cbeaeb119';

-- Results:
-- A | 3982562 | 3982562 (WRONG - should be child ID)
-- B | 3982564 | 3982564 (WRONG)
-- C | 3982566 | 3982566 (WRONG)
-- D | 3982568 | 3982568 (WRONG)
```

---

## References

- Cal.com V2 API Docs: https://docs.cal.com/api-reference/v2
- Previous Session: SESSION_SUMMARY_2025-11-24.md
- CalcomEventMap Setup: CALCOM_EVENT_MAPS_SETUP_COMPLETE_2025-11-24.md
- Test Call Transcript: Call ID call_7dce6f4f1636b605e3e3d7d4b1f

---

**Prepared by**: Claude Code
**Date**: 2025-11-24 07:57 CET
**Status**: ⏳ **AWAITING DECISION ON SOLUTION**
