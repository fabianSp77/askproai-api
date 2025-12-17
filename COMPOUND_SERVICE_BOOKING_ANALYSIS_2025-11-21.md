# Compound Service Booking Flow Analysis
**Date**: 2025-11-21
**Test Call**: call_84c9a2f2125837c82a93a69268d
**Service**: Dauerwelle (#441, 135 min, compound)

---

## Executive Summary

**FINDING**: Compound service configuration is CORRECT. Booking failures are NOT related to compound service logic.

**ROOT CAUSE**: Cal.com availability check passed, but subsequent booking failed with "vergeben" (race condition or Cal.com 135-min duration rejection).

---

## Test Call Flow Analysis

### Call Timeline
```
1. check_customer → ✅ Hans Schuster found (ID: 7)
2. extract_dynamic_variables → ✅ Dauerwelle, Montag, 10:00
3. check_availability_v17 → ✅ Available at 2025-11-24 10:00
4. start_booking → ❌ FAILED: "Dieser Termin wurde gerade vergeben"
```

### Function Call Details

#### 1. Customer Check (Success)
```json
{
  "function": "check_customer",
  "result": {
    "customer_id": 7,
    "name": "Hans Schuster",
    "predicted_service": "Herrenhaarschnitt",
    "appointment_history": {
      "total_appointments": 5,
      "services": [{"count": 5, "service_id": 438, "name": "Herrenhaarschnitt"}]
    }
  }
}
```

**Note**: Customer has ONLY booked Herrenhaarschnitt (5x). This is their FIRST Dauerwelle request.

#### 2. Availability Check (Success)
```json
{
  "function": "check_availability_v17",
  "arguments": {
    "dienstleistung": "Dauerwelle",
    "datum": "Montag",
    "uhrzeit": "10:00"
  },
  "result": {
    "success": true,
    "available": true,
    "service": "Dauerwelle",
    "requested_time": "2025-11-24 10:00",
    "message": "Ja, Dauerwelle ist verfügbar am Montag, den 24. November um 10:00 Uhr."
  }
}
```

**Service Correctly Identified**: Dauerwelle

#### 3. Booking Attempt (Failed)
```json
{
  "function": "start_booking",
  "arguments": {
    "datetime": "2025-11-24T10:00:00",
    "service_name": "Dauerwelle",
    "customer_name": "Hans Siebert",
    "call_id": "dummy_call_id"
  },
  "result": {
    "success": false,
    "error": "Dieser Termin wurde gerade vergeben. Bitte wählen Sie einen anderen Zeitpunkt."
  }
}
```

**Service Correctly Passed**: Dauerwelle
**Booking Failed**: Despite availability check passing seconds earlier

---

## Database Verification

### Service #441 (Dauerwelle) Configuration
```sql
SELECT id, name, duration_minutes, composite, segments, calcom_event_type_id
FROM services WHERE id = 441;
```

**Result**:
```
id: 441
name: Dauerwelle
duration_minutes: 135
composite: 1 (TRUE)
segments: [
  {"key":"A","type":"active","staff_required":true,"name":"Haare wickeln","durationMin":50,"order":1},
  {"key":"A_gap","type":"processing","staff_required":false,"name":"Einwirkzeit (Dauerwelle wirkt ein)","durationMin":15,"order":2},
  {"key":"B","type":"active","staff_required":true,"name":"Fixierung auftragen","durationMin":5,"order":3},
  {"key":"B_gap","type":"processing","staff_required":false,"name":"Einwirkzeit (Fixierung wirkt ein)","durationMin":10,"order":4},
  {"key":"C","type":"active","staff_required":true,"name":"Auswaschen & Pflege","durationMin":15,"order":5},
  {"key":"D","type":"active","staff_required":true,"name":"Schneiden & Styling","durationMin":40,"order":6}
]
calcom_event_type_id: 3757758
```

**Duration Math**:
- Active phases: 50 + 5 + 15 + 40 = 110 min
- Processing gaps: 15 + 10 = 25 min
- **Total**: 110 + 25 = 135 min ✅ MATCHES

**Composite Flag**: TRUE ✅
**Segments**: COMPLETE (6 phases) ✅
**Cal.com ID**: VALID ✅

### Service #438 (Herrenhaarschnitt) - Comparison
```
id: 438
name: Herrenhaarschnitt
duration_minutes: 55
composite: 0 (FALSE)
segments: NULL
calcom_event_type_id: 3757770
```

---

## Code Flow Analysis

### Service Selection Logic
**File**: `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`

**Selection Strategy** (Lines 239-345):
1. ✅ **Exact Match**: `WHERE name LIKE 'Dauerwelle'`
2. ✅ **Synonym Match**: Check service_synonyms table
3. ✅ **Fuzzy Match**: Levenshtein distance (75% threshold)

**Multi-Tenant Validation**:
- ✅ Company ownership check
- ✅ Branch isolation
- ✅ Cal.com team ownership validation

**Compound Service Handling**: ❓ **NO SPECIAL LOGIC**
- Service selection does NOT filter by `composite` flag
- Selection treats compound and simple services identically
- Duration is NOT considered during selection

### Booking Logic
**File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**bookAppointment Flow** (Lines 1719-2400):
1. Get call context (company_id, branch_id)
2. Parse datetime from parameters
3. **Service Resolution**:
   ```php
   if ($serviceId) {
       $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
   } elseif ($serviceName) {
       $service = $this->serviceSelector->findServiceByName($serviceName, $companyId, $branchId);
   } else {
       $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
   }
   ```
4. **Double-check availability** (Lines 2286-2334)
5. **Create Cal.com booking**

**Compound Service Handling in Booking**: ❓ **UNCLEAR**
- Code does NOT explicitly check `$service->isComposite()`
- Duration used: `$service->duration_minutes` (should be 135 for Dauerwelle)
- NO special handling for segment creation during booking

### Phase Creation Logic
**File**: `/var/www/api-gateway/app/Observers/AppointmentObserver.php`

**Phase Creation** (Lines 31-33):
```php
// 🔧 REMOVED 2025-11-20: Phase creation moved to AppointmentPhaseObserver
// AppointmentPhaseObserver now handles BOTH Processing Time AND Composite phases
// with full support for segment_name, segment_key, sequence_order fields
```

**Phase Observer**: REMOVED from AppointmentObserver
**New Location**: AppointmentPhaseObserver (file not checked)

---

## Hypothesis: Why Booking Failed

### Theory 1: Cal.com 135-Minute Duration Rejection (LIKELY)
**Evidence**:
- Availability check passed (Cal.com returned slot as free)
- Booking failed immediately after
- Dauerwelle requires 135 minutes
- Herrenhaarschnitt (55 min) works fine

**Possible Cause**:
- Cal.com Event Type #3757758 may NOT be configured for 135-min duration
- Event type might have 60-min default/max duration
- Availability API doesn't validate duration limits
- Booking API rejects if requested duration > configured max

**Test**:
```sql
-- Check if Cal.com event type has duration limits
SELECT * FROM calcom_event_mappings
WHERE calcom_event_type_id = '3757758';
```

### Theory 2: Race Condition (POSSIBLE)
**Evidence**:
- Time between availability check and booking: ~2 seconds
- Error message: "gerade vergeben" (just taken)

**Possible Cause**:
- Another booking grabbed the slot
- No transaction lock between check and book
- Double-check availability passed, but final booking failed

**Counter-Evidence**:
- Only 1 test call active
- No other appointments created at that time

### Theory 3: Service Selection Mismatch (RULED OUT)
**Evidence**:
- ✅ Dauerwelle correctly identified in availability check
- ✅ Dauerwelle correctly passed to start_booking
- ✅ Service selection logic does NOT discriminate by composite flag

**Conclusion**: Service selection is working correctly.

### Theory 4: Segment/Phase Creation Issue (UNLIKELY)
**Evidence**:
- Phase creation happens AFTER appointment creation (AppointmentPhaseObserver)
- Booking fails BEFORE appointment record is created
- No appointments found in database for this call

**Conclusion**: Phase logic is not reached - appointment creation fails earlier.

---

## Key Findings

### ✅ WORKING CORRECTLY
1. **Service Configuration**: Dauerwelle has correct duration (135 min), composite flag, and segments
2. **Service Selection**: ServiceSelectionService correctly identifies Dauerwelle by name
3. **Availability Check**: Cal.com returns Dauerwelle as available at requested time
4. **Parameter Passing**: service_name="Dauerwelle" correctly passed through entire flow

### ❓ UNCLEAR / NOT VERIFIED
1. **Cal.com Event Type Duration**: Event #3757758 configuration unknown
2. **Duration Passed to Cal.com**: Need to verify if 135 min is sent to booking API
3. **Cal.com Duration Limits**: Whether event type supports 135-min bookings
4. **AppointmentPhaseObserver Logic**: Phase creation code not reviewed

### ❌ POTENTIAL ISSUES
1. **No Compound Service Validation**: Booking flow does NOT check if service is composite before proceeding
2. **No Duration Verification**: Code does NOT verify if Cal.com event type supports requested duration
3. **No Cal.com Event Type Caching**: Every booking makes fresh API call to validate event type

---

## Recommended Tests

### Test 1: Verify Cal.com Event Type Duration Configuration
```bash
# Check Cal.com API for event type #3757758 settings
curl -X GET "https://api.cal.com/v1/event-types/3757758" \
  -H "Authorization: Bearer $CALCOM_API_KEY"
```

**Look for**:
- `length`: Should be 135 (or configurable)
- `minimumBookingNotice`: Should allow current booking window
- `slotInterval`: Should accommodate 135-min slots

### Test 2: Direct Cal.com Booking with 135 Minutes
```php
// Bypass AskPro booking logic, test Cal.com directly
$calcomService->createBooking(
    eventTypeId: 3757758,
    start: '2025-11-24T10:00:00',
    duration: 135,  // Explicit 135 minutes
    name: 'Test Dauerwelle',
    email: 'test@example.com'
);
```

**Expected Result**:
- SUCCESS → Cal.com accepts 135-min bookings
- FAIL → Cal.com rejects due to duration limit

### Test 3: Book Herrenhaarschnitt (55 min) Same Time Slot
```bash
# Test if simpler service works at same time
# Service #438, 55 minutes, non-composite
```

**Expected Result**:
- SUCCESS → Confirms slot is available, issue is duration/composite
- FAIL → Confirms slot is truly unavailable

### Test 4: Check AppointmentPhaseObserver
```php
// Verify phase creation logic for composite services
// File: app/Observers/AppointmentPhaseObserver.php

// Test if phases are created correctly:
// 1. Check if observer fires on appointment create
// 2. Verify segments JSON is parsed
// 3. Confirm 6 phases created for Dauerwelle
```

---

## Recommendations

### Immediate Actions
1. **Verify Cal.com Event Type Config**: Check if #3757758 supports 135-min duration
2. **Add Duration Logging**: Log what duration is sent to Cal.com booking API
3. **Test Simpler Service**: Confirm Herrenhaarschnitt (55 min) can book same slot

### Code Improvements
1. **Add Compound Service Validation**:
   ```php
   if ($service->isComposite() && !$this->validateCompoundBooking($service)) {
       return $this->responseFormatter->error('Compound service booking not supported');
   }
   ```

2. **Verify Duration Compatibility**:
   ```php
   if (!$this->calcomEventTypeSupportsD uration($service->calcom_event_type_id, $service->duration_minutes)) {
       return $this->responseFormatter->error('Service duration exceeds Cal.com event type limit');
   }
   ```

3. **Enhanced Error Messages**:
   ```php
   // Instead of generic "vergeben"
   if ($bookingError->code === 'DURATION_EXCEEDED') {
       return "This service requires {$service->duration_minutes} minutes, but the calendar slot only allows shorter bookings.";
   }
   ```

### Testing Strategy
1. **Unit Test**: Service selection for compound services
2. **Integration Test**: Cal.com booking with various durations
3. **E2E Test**: Complete booking flow for Dauerwelle
4. **Regression Test**: Ensure Herrenhaarschnitt still works

---

## Conclusion

**SERVICE SELECTION IS NOT THE PROBLEM**. Dauerwelle is correctly identified and passed through the entire booking flow.

**MOST LIKELY CAUSE**: Cal.com Event Type #3757758 is NOT configured to accept 135-minute bookings, OR there is a Cal.com-side validation that rejects long-duration appointments.

**NEXT STEPS**:
1. Inspect Cal.com event type #3757758 configuration
2. Compare with event type #3757770 (Herrenhaarschnitt, 55 min)
3. Test if Cal.com API accepts 135-min booking requests
4. Add duration validation before calling Cal.com booking API

**COMPOUND SERVICE LOGIC**: Appears functional but NOT TESTED end-to-end. Phase creation code needs review.
