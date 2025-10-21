# Service Selection Logic Analysis - AskProAI (Company ID 15)

**Date**: 2025-10-21
**Analyst**: Backend System Architect
**Status**: 🚨 CRITICAL FINDING - Service Selection Mismatch Identified

---

## Executive Summary

### The Problem
**AskProAI has TWO active services with DIFFERENT durations, but the system ALWAYS selects Service 47 (30 min) for ALL operations, effectively IGNORING Service 32 (15 min).**

This means:
- ✅ `check_availability()` → Uses Service 47 (30 min event type 2563193)
- ✅ `book_appointment()` → Uses Service 47 (30 min event type 2563193)
- ❌ Service 32 (15 min event type 3664712) → **NEVER USED**

---

## Database State Analysis

### Company Configuration
```
Company ID: 15
Company Name: AskProAI
Cal.com Team ID: 39203
```

### Active Services (Both Active!)
```
Service 32:
  - Name: "15 Minuten Schnellberatung"
  - Cal.com Event Type: 3664712 (15 minutes)
  - is_default: NO
  - priority: 50
  - Status: ACTIVE ✅
  - Branch: NULL (company-wide)

Service 47:
  - Name: "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
  - Cal.com Event Type: 2563193 (30 minutes)
  - is_default: YES ✅
  - priority: 10 (HIGHER priority - lower number = higher priority)
  - Status: ACTIVE ✅
  - Branch: NULL (company-wide)
```

---

## Service Selection Logic Analysis

### Code Location
**File**: `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`

### Selection Algorithm (Lines 36-94)

```php
public function getDefaultService(int $companyId, ?string $branchId = null): ?Service
{
    $query = Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->whereNotNull('calcom_event_type_id');

    // Apply branch filtering (if provided)
    if ($branchId) {
        $query->where(function($q) use ($branchId) {
            $q->where('branch_id', $branchId)
              ->orWhereHas('branches', function($q2) use ($branchId) {
                  $q2->where('branches.id', $branchId);
              })
              ->orWhereNull('branch_id'); // Company-wide services
        });
    }

    // STEP 1: Try to find default service first
    $service = (clone $query)->where('is_default', true)->first();

    // STEP 2: Fallback to highest priority service
    if (!$service) {
        $service = $query
            ->orderBy('priority', 'asc')
            ->orderByRaw('CASE WHEN name LIKE "%Beratung%" THEN 0 WHEN name LIKE "%30 Minuten%" THEN 1 ELSE 2 END')
            ->first();
    }

    return $service;
}
```

### Decision Path for AskProAI

#### STEP 1: Look for `is_default = true`
```
Query: WHERE company_id = 15 AND is_active = true AND is_default = true
Result: Service 47 ✅ FOUND
Decision: RETURN Service 47 immediately
```

#### STEP 2: Fallback (Never Executed!)
Because Service 47 was found in STEP 1, the fallback logic NEVER runs.

**Result**: Service 47 (30 min) is ALWAYS selected, Service 32 (15 min) is NEVER selected.

---

## Function Call Analysis

### 1. check_availability() - Lines 200-400

**Location**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php:200-400`

```php
private function checkAvailability(array $params, ?string $callId)
{
    $serviceId = $params['service_id'] ?? null;  // ← Line 233

    // Get service with branch validation
    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
        //                                  ^^^^^^^^^^^^^^^^^ ALWAYS returns Service 47
    }

    // Use service.calcom_event_type_id for Cal.com API call
    $response = $this->calcomService->getAvailableSlots(
        $service->calcom_event_type_id,  // ← Event Type 2563193 (30 min)
        ...
    );
}
```

**Current Behavior**:
- If `service_id` is NOT provided in params → Uses `getDefaultService()` → **Service 47 (30 min)**
- If `service_id` IS provided → Uses `findServiceById()` → Could use Service 32, **but only if AI passes it**

### 2. book_appointment() - Lines 550-700

**Location**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php:550-700`

```php
private function bookAppointment(array $params, ?string $callId)
{
    $serviceId = $params['service_id'] ?? null;  // ← Line 572

    // Get service with branch validation
    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
        //                                  ^^^^^^^^^^^^^^^^^ ALWAYS returns Service 47
    }

    // Create booking via Cal.com
    $booking = $this->calcomService->createBooking([
        'eventTypeId' => $service->calcom_event_type_id,  // ← Event Type 2563193 (30 min)
        ...
    ]);
}
```

**Current Behavior**: **IDENTICAL** to `check_availability()`

### 3. getAlternatives() - Lines 467-544

**Location**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php:467-544`

```php
private function getAlternatives(array $params, ?string $callId)
{
    $serviceId = $params['service_id'] ?? null;  // ← Line 472

    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
        //                                  ^^^^^^^^^^^^^^^^^ ALWAYS returns Service 47
    }

    $alternatives = $this->alternativeFinder->findAlternatives(
        $requestedDate,
        $duration,
        $service->calcom_event_type_id,  // ← Event Type 2563193 (30 min)
        $customerId
    );
}
```

**Current Behavior**: **IDENTICAL** to other functions

---

## Critical Questions

### Q1: Are Both Services Being Considered?
**Answer**: ❌ NO

- Service 47 (30 min): ✅ ALWAYS selected (is_default = true)
- Service 32 (15 min): ❌ NEVER selected (is_default = false)

### Q2: What Service/Event Type Does check_availability() Use?
**Answer**: Service 47 with Event Type 2563193 (30 minutes)

```
check_availability() → getDefaultService() → Service 47 → Event Type 2563193
```

### Q3: Does It Use the Same Selection Logic as book_appointment()?
**Answer**: ✅ YES - IDENTICAL logic in all three functions

```
check_availability()  ────┐
book_appointment()    ────┼─→ Same serviceId extraction → Same ServiceSelector calls
getAlternatives()     ────┘
```

### Q4: Could There Be a Mismatch?
**Answer**: ❌ NO mismatch BETWEEN functions, but there IS a mismatch:

**Between Services and User Intent:**
- User wants 15 min appointment → System checks/books 30 min slot
- User wants 30 min appointment → System checks/books 30 min slot ✅

---

## Root Cause Analysis

### The Issue
The system has **NO WAY** to select Service 32 (15 min) because:

1. **Retell AI Agent** doesn't pass `service_id` parameter (it's optional)
2. **Without service_id**, all functions call `getDefaultService()`
3. **getDefaultService()** returns the service with `is_default = true`
4. **Service 47** has `is_default = true`, so it's ALWAYS returned
5. **Service 32** has `is_default = false` and priority = 50, so it's NEVER returned

### The Missing Link
**There is NO logic that determines WHICH service to use based on:**
- Customer request ("I need a quick 15 minute consultation")
- Duration parameter from AI (`duration` parameter exists but isn't used for service selection)
- Time of day
- Service availability
- Customer history

### Current Flow (Broken for 15 min requests)
```
Customer: "I need a quick 15 minute appointment"
    ↓
Retell AI: Calls check_availability(date="2025-10-22", time="10:00", duration=60)
    ↓                                                                  ^^^^^^^^^ Default
getDefaultService(company_id=15)
    ↓
Service 47 (30 min, Event Type 2563193)
    ↓
Cal.com: Check 30-minute slots starting at 10:00
    ↓
Result: Returns 30-minute availability
    ↓
Customer Books: Gets 30-minute slot (when they only needed 15!)
```

---

## Impact Assessment

### Functional Impact
- ✅ System works correctly for 30-minute appointments
- ❌ System CANNOT book 15-minute appointments (Service 32 unused)
- ❌ Customer intent for shorter appointments is ignored
- ❌ Wastes staff time (30 min slot when 15 min would suffice)
- ❌ Reduces appointment availability (30 min slots fill calendar faster)

### Business Impact
- **Revenue Loss**: Fewer available slots (30 min vs 15 min granularity)
- **Customer Dissatisfaction**: Cannot get quick consultations
- **Staff Inefficiency**: Overbooking time when not needed
- **Competitive Disadvantage**: Cannot offer quick consultations

### Technical Debt
- Service 32 configuration exists but is dead code
- No duration-based service routing logic
- AI agent doesn't understand service selection
- No way to offer service choice to customer

---

## Potential Solutions

### Option 1: Duration-Based Service Selection (Intelligent)
**Modify ServiceSelectionService to choose service based on duration:**

```php
public function getServiceByDuration(int $companyId, ?string $branchId, int $duration): ?Service
{
    $services = $this->getAvailableServices($companyId, $branchId);

    // Find service that matches duration
    return $services->first(function($service) use ($duration) {
        // Assuming services have a duration field or we infer from event type
        return $service->duration === $duration;
    });
}
```

**Pros**:
- Automatic selection based on customer need
- Uses both services intelligently
- No AI prompt changes needed

**Cons**:
- Requires service duration metadata
- Needs Cal.com event type duration lookup

### Option 2: AI Agent Service Selection (Explicit)
**Teach Retell AI agent to pass `service_id` parameter based on customer request:**

**Agent Prompt Addition**:
```
If customer mentions:
- "quick", "kurz", "15 Minuten" → Use service_id=32
- "ausführlich", "30 Minuten", "Beratung" → Use service_id=47
- Default → Use service_id=47
```

**Pros**:
- Clear customer intent → service mapping
- Uses existing infrastructure (service_id parameter works)
- No backend code changes

**Cons**:
- Relies on AI interpretation
- Fragile (what if customer doesn't mention duration?)
- Increases prompt complexity

### Option 3: Make Service 32 Default, Offer Upgrade
**Set Service 32 as default, offer Service 47 as upgrade:**

```sql
UPDATE services SET is_default = true WHERE id = 32;
UPDATE services SET is_default = false WHERE id = 47;
```

**AI Agent Logic**:
```
1. Default to 15-minute quick consultation
2. Ask: "Möchten Sie eine ausführliche 30-Minuten Beratung oder reicht eine kurze 15-Minuten Schnellberatung?"
3. Based on answer → set service_id
```

**Pros**:
- More appointments available (15 min slots)
- Customer chooses explicitly
- Better calendar utilization

**Cons**:
- Adds conversation step
- May confuse some customers

### Option 4: Dual-Check Strategy
**Check availability for BOTH services, offer customer choice:**

```
Agent: "Ich habe Verfügbarkeit am Dienstag um 10 Uhr.
        Möchten Sie eine 15-Minuten Schnellberatung oder
        eine ausführliche 30-Minuten Beratung?"

Customer: "15 Minuten reicht"

Agent: Uses service_id=32 for booking
```

**Pros**:
- Customer has full control
- Both services utilized
- Maximizes revenue (right service for right need)

**Cons**:
- Requires two Cal.com API calls (performance)
- More complex AI logic

---

## Recommended Solution

### **Hybrid Approach: Duration-Based Auto-Selection + AI Override**

**Implementation**:

1. **Backend Enhancement** (ServiceSelectionService):
```php
public function getDefaultService(int $companyId, ?string $branchId = null, ?int $duration = null): ?Service
{
    // If duration provided, try to match service by duration
    if ($duration !== null) {
        $service = $this->getServiceByDuration($companyId, $branchId, $duration);
        if ($service) return $service;
    }

    // Existing logic as fallback
    return $this->getDefaultServiceOriginal($companyId, $branchId);
}
```

2. **Function Call Updates**:
```php
// In checkAvailability, bookAppointment, getAlternatives:
$duration = $params['duration'] ?? 60;
$serviceId = $params['service_id'] ?? null;

if ($serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
} else {
    // NEW: Pass duration for intelligent selection
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId, $duration);
}
```

3. **AI Agent Awareness** (Optional enhancement):
```
If customer says "schnell", "kurz", "15 Minuten":
  → Use duration=15

If customer says "ausführlich", "30 Minuten", "Beratung":
  → Use duration=30

Default: duration=30
```

**Why This Works**:
- ✅ Uses existing `duration` parameter already extracted
- ✅ No breaking changes (fallback to original logic)
- ✅ Service 32 becomes accessible automatically
- ✅ AI can override by passing explicit service_id
- ✅ Minimal code changes
- ✅ Backward compatible

---

## Validation Queries

### Check Current Service Selection
```bash
php artisan tinker --execute="
\$service = app(\App\Services\Retell\ServiceSelectionService::class)
    ->getDefaultService(15, null);
echo 'Selected Service: ' . \$service->id . ' (' . \$service->name . ')' . PHP_EOL;
echo 'Event Type: ' . \$service->calcom_event_type_id . PHP_EOL;
"
```

**Expected Output**: Service 47 (30 min)

### Verify Both Services Active
```sql
SELECT id, name, calcom_event_type_id, is_default, priority, is_active
FROM services
WHERE company_id = 15 AND is_active = true;
```

**Expected**: 2 rows (Services 32 and 47)

---

## Conclusion

### Findings Summary
1. ✅ **Consistency**: All three functions (check_availability, book_appointment, getAlternatives) use IDENTICAL service selection logic
2. ❌ **Service Utilization**: Service 32 (15 min) is ACTIVE but NEVER SELECTED
3. ✅ **Service 47 Monopoly**: Service 47 (30 min) is ALWAYS selected due to `is_default = true`
4. ❌ **No Duration Intelligence**: The `duration` parameter is extracted but NOT used for service selection
5. ⚠️ **Business Impact**: Cannot offer quick consultations, wastes appointment slots

### Critical Path Forward
**Implement Hybrid Approach** (Duration-Based Auto-Selection):
- Low effort (modify 1 service, update 3 function calls)
- High impact (unlocks Service 32, better calendar utilization)
- No breaking changes (fallback preserves current behavior)

### Files Requiring Changes
1. `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php` (add duration parameter)
2. `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (pass duration in 3 functions)
3. Database: Add `duration` field to `services` table OR query from Cal.com event types

---

**Next Steps**: Implement recommended solution and test with both 15-min and 30-min appointment flows.
