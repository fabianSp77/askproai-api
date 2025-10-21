# Cal.com Event Type Mismatch - Implementation Fix Guide
**Date**: 2025-10-21
**Status**: READY FOR IMPLEMENTATION
**Estimated Duration**: 2-4 hours

---

## Overview

This guide provides the exact code changes needed to fix the service_id parameter chain breaking issue that causes 100% booking failure rate.

---

## Problem Summary

- `check_availability()` receives NO `service_id` - Falls back to DEFAULT
- `book_appointment()` receives NO `service_id` - Falls back to DEFAULT  
- `collect_appointment()` never extracts or passes `service_id`
- Result: Potential mismatch between checking and booking different event types

---

## Solution Overview

**Phase 1: Extract service_id in collectAppointment()**
- Convert service name (string) to service_id (numeric)
- Validate service exists and is accessible
- Store in call record for reference

**Phase 2: Pass service_id through checkAvailability()**
- Accept service_id as optional parameter
- Fallback to default only if not provided
- Log which service is being used

**Phase 3: Pass service_id through bookAppointment()**
- Accept service_id as optional parameter
- Fallback to default only if not provided
- Validate same service as used in check

**Phase 4: Update Retell function definition**
- Add service_id as return variable
- Pass service_id to both function calls

---

## Implementation Details

### File 1: RetellFunctionCallHandler.php

#### Change 1: Add helper method to find service by name

**Location**: After line 100 (before checkAvailability method)

```php
/**
 * Find service by name/dienstleistung string
 * Handles German service names
 */
private function findServiceByName(?string $serviceName, int $companyId, ?string $branchId = null): ?Service
{
    if (!$serviceName) {
        return null;
    }

    // Try exact match first (case-insensitive)
    $service = Service::where('company_id', $companyId)
        ->whereRaw('LOWER(name) = ?', [strtolower($serviceName)])
        ->where('is_active', true)
        ->first();

    if ($service) {
        Log::info('Found service by exact name match', [
            'service_name' => $serviceName,
            'service_id' => $service->id,
            'company_id' => $companyId
        ]);
        return $service;
    }

    // Try partial match (service name contains search string)
    $service = Service::where('company_id', $companyId)
        ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($serviceName) . '%'])
        ->where('is_active', true)
        ->orderBy('is_default', 'desc')  // Prefer default
        ->first();

    if ($service) {
        Log::info('Found service by partial name match', [
            'service_name' => $serviceName,
            'service_id' => $service->id,
            'company_id' => $companyId
        ]);
        return $service;
    }

    Log::warning('Service not found by name', [
        'service_name' => $serviceName,
        'company_id' => $companyId
    ]);

    return null;
}
```

#### Change 2: Modify checkAvailability() to accept service_id

**Location**: Line 233 (inside checkAvailability method)

**BEFORE:**
```php
$duration = $params['duration'] ?? 60;
$serviceId = $params['service_id'] ?? null;

Log::info('⏱️ checkAvailability START', [
    // ...
]);

// Get service with branch validation using ServiceSelectionService
if ($serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
} else {
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
}
```

**AFTER:**
```php
$duration = $params['duration'] ?? 60;
$serviceId = $params['service_id'] ?? null;
$serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;

Log::info('⏱️ checkAvailability START', [
    'call_id' => $callId,
    'requested_date' => $requestedDate->format('Y-m-d H:i'),
    'service_id_param' => $serviceId,
    'service_name_param' => $serviceName,
    'timestamp_ms' => round((microtime(true) - $startTime) * 1000, 2)
]);

// Get service with branch validation using ServiceSelectionService
if ($serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
} elseif ($serviceName) {
    // Try to find by name
    $service = $this->findServiceByName($serviceName, $companyId, $branchId);
    if ($service) {
        $serviceId = $service->id;
    }
} else {
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    if ($service) {
        $serviceId = $service->id;
    }
}
```

#### Change 3: Modify bookAppointment() to accept service_id

**Location**: Line 572 (inside bookAppointment method)

**BEFORE:**
```php
$appointmentTime = $this->dateTimeParser->parseDateTime($params);
$duration = $params['duration'] ?? 60;
$customerName = $params['customer_name'] ?? '';
$customerEmail = $params['customer_email'] ?? '';
$customerPhone = $params['customer_phone'] ?? '';
$serviceId = $params['service_id'] ?? null;
$notes = $params['notes'] ?? '';

// Get service with branch validation - SECURITY: No cross-branch bookings allowed
if ($serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
} else {
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
}
```

**AFTER:**
```php
$appointmentTime = $this->dateTimeParser->parseDateTime($params);
$duration = $params['duration'] ?? 60;
$customerName = $params['customer_name'] ?? '';
$customerEmail = $params['customer_email'] ?? '';
$customerPhone = $params['customer_phone'] ?? '';
$serviceId = $params['service_id'] ?? null;
$serviceName = $params['service_name'] ?? $params['dienstleistung'] ?? null;
$notes = $params['notes'] ?? '';

Log::info('🎯 bookAppointment START', [
    'call_id' => $callId,
    'service_id_param' => $serviceId,
    'service_name_param' => $serviceName,
    'appointment_time' => $appointmentTime->format('Y-m-d H:i')
]);

// Get service with branch validation - SECURITY: No cross-branch bookings allowed
if ($serviceId) {
    $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    Log::info('bookAppointment: Using service_id', [
        'service_id' => $serviceId,
        'found_service_id' => $service?->id
    ]);
} elseif ($serviceName) {
    // Try to find by name
    $service = $this->findServiceByName($serviceName, $companyId, $branchId);
    if ($service) {
        $serviceId = $service->id;
        Log::info('bookAppointment: Found service by name', [
            'service_name' => $serviceName,
            'service_id' => $serviceId
        ]);
    }
} else {
    $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    if ($service) {
        $serviceId = $service->id;
        Log::info('bookAppointment: Using default service', [
            'service_id' => $serviceId
        ]);
    }
}
```

#### Change 4: Modify collectAppointment() to extract and pass service_id

**Location**: After line 1398 (inside collectAppointment method)

**FIND THIS SECTION:**
```php
// Dynamic service selection using ServiceSelectionService
$service = null;

if ($companyId) {
    $service = $this->serviceSelector->getDefaultService($companyId);

    Log::info('📋 Dynamic service selection for company', [
        'company_id' => $companyId,
        'service_id' => $service ? $service->id : null,
        'service_name' => $service ? $service->name : null,
    ]);
}
```

**REPLACE WITH:**
```php
// Dynamic service selection using ServiceSelectionService
$service = null;
$selectedServiceId = null;

if ($companyId) {
    // STEP 1: Try to find service by dienstleistung (service name)
    if ($dienstleistung) {
        $serviceByName = $this->findServiceByName($dienstleistung, $companyId);
        if ($serviceByName) {
            $service = $serviceByName;
            $selectedServiceId = $service->id;
            
            Log::info('📋 Service found by dienstleistung name', [
                'company_id' => $companyId,
                'dienstleistung' => $dienstleistung,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id
            ]);
        }
    }

    // STEP 2: If not found by name, use default
    if (!$service) {
        $service = $this->serviceSelector->getDefaultService($companyId);
        if ($service) {
            $selectedServiceId = $service->id;
        }

        Log::info('📋 Using default service', [
            'company_id' => $companyId,
            'service_id' => $service ? $service->id : null,
            'service_name' => $service ? $service->name : null,
            'reason' => 'No service found by dienstleistung: ' . $dienstleistung
        ]);
    }
}

// Store service selection in call record for reference
if ($callId && $call && $selectedServiceId) {
    $call->update([
        'selected_service_id' => $selectedServiceId,
        'dienstleistung' => $dienstleistung
    ]);
}
```

---

### File 2: Retell Function Definition

**Location**: `/var/www/api-gateway/retell_collect_appointment_function_updated.json`

**Change**: Add response variables and ensure parameters are passed through

**FIND THIS SECTION:**
```json
{
  "response_variables": {
    "success": "$.success",
    "status": "$.status",
    "message": "$.message",
    "bestaetigung_status": "$.bestaetigung_status",
    "appointment_id": "$.appointment_id",
    "termin_referenz": "$.reference_id",
    "naechste_schritte": "$.next_steps"
  }
}
```

**ADD THIS:**
```json
{
  "response_variables": {
    "success": "$.success",
    "status": "$.status",
    "message": "$.message",
    "bestaetigung_status": "$.bestaetigung_status",
    "appointment_id": "$.appointment_id",
    "termin_referenz": "$.reference_id",
    "naechste_schritte": "$.next_steps",
    "selected_service_id": "$.selected_service_id"
  }
}
```

---

## Testing Protocol

### Test 1: Single Service Booking

**Setup**: Use only Service 47 (30-min consultation)

**Test Steps**:
```
1. Call the system
2. Request appointment for Oct 23, 2025 at 14:00
3. For service: "Beratung" (consultation)
4. Should check availability ✓
5. Confirm booking ✓
6. Should book successfully ✓
```

**Expected Result**: Booking succeeds

### Test 2: Verify Service ID Tracking

**Setup**: Enable debug logging

**Test Steps**:
```
1. Trigger check_availability() via Retell
2. Check logs for "Using service for availability check"
3. Verify service_id and event_type_id match
4. Trigger book_appointment()
5. Verify same service_id used
```

**Expected Result**: Same service_id in both checks

### Test 3: Service Name Resolution

**Setup**: Test findServiceByName() helper

**Test Steps**:
```php
$selector = app(\App\Services\Retell\ServiceSelectionService::class);

// Should find by exact name
$service = $selector->findServiceByName('15 Minuten Schnellberatung', 15);
// Result: Service 32

// Should find by partial name
$service = $selector->findServiceByName('Schnellberatung', 15);
// Result: Service 32

// Should handle German characters
$service = $selector->findServiceByName('Beratung', 15);
// Result: Service 47 (default because partial match)
```

**Expected Result**: All findServiceByName() calls work correctly

---

## Deployment Checklist

- [ ] Database has both services configured correctly
- [ ] Service 47 is marked as is_default = true
- [ ] Service 32 is marked as is_default = false (or remove if not needed)
- [ ] Cal.com Event Type 2563193 is configured in Cal.com team
- [ ] Cal.com API key is valid and has access to event types
- [ ] collectAppointment() extracts and passes service_id
- [ ] checkAvailability() logs service_id being used
- [ ] bookAppointment() logs service_id being used
- [ ] Test bookings work end-to-end
- [ ] Logs show consistent service_id usage
- [ ] No more "event type not found" errors

---

## Rollback Plan

If issues occur after deployment:

1. **Immediate**: Set Service 32 as default temporarily
   ```bash
   php artisan tinker
   > \App\Models\Service::where('id', 47)->update(['is_default' => false])
   > \App\Models\Service::where('id', 32)->update(['is_default' => true])
   ```

2. **Short-term**: Revert to previous RetellFunctionCallHandler.php from git

3. **Long-term**: Keep monitoring logs for service_id mismatch

---

## Success Metrics

- All test bookings complete successfully
- No "event type not found" errors in logs
- Consistent service_id usage in check + booking
- Customer satisfaction improves
- Call completion rate increases

