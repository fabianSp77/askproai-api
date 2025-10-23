# AskProAI Setup - Quick Reference Guide

**Company ID**: 15
**Cal.com Team**: 39203
**Status**: ✅ OPERATIONAL (with known limitations)

---

## 1. DATABASE VALUES - AT A GLANCE

### Company: AskProAI
```
ID: 15
Name: AskProAI
Cal.com Team ID: 39203
Active: YES
```

### Phone Number
```
Number: +493083793369
ID: 03513893-d962-4db0-858c-ea5b0e227e9a
Company: 15 (AskProAI)
Branch: 9f4d5e2a-46f7-41b6-b81d-1532725381d4
Active: YES
Primary: YES
```

### Retell Agents
```
PRIMARY (IN USE):
  Agent ID: agent_b36ecd3927a81834b6d56ab07b
  Name: "Online: Assistent für Fabian Spitzer Rechtliches/V33"
  Phone: +493083793369

SECONDARY (REFERENCED):
  Agent ID: agent_616d645570ae613e421edb98e7
  Name: "Conversational Agent"
  Status: Not assigned to production phone
```

---

## 2. SERVICES CONFIGURATION

### Service 32: 15-Minute Consultation ⚠️ NOT USED

```
ID: 32
Name: "15 Minuten Schnellberatung"
Event Type: 3664712
Duration: 15 minutes
Default: NO ❌
Priority: 50
Active: YES
Status: CONFIGURED BUT NEVER SELECTED ⚠️
```

**Why not used**: `is_default = false` and system always selects default service.

### Service 47: 30-Minute Consultation ✅ ALWAYS USED

```
ID: 47
Name: "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
Event Type: 2563193
Duration: 30 minutes
Default: YES ✅
Priority: 10
Active: YES
Status: ALWAYS SELECTED ✅
```

**Why always used**: `is_default = true` - system selects this for ALL bookings.

---

## 3. CAL.COM EVENT TYPES

### Event Type 3664712 (15-min)
```
Service: 32
Duration: 15 minutes
Team: 39203
Status: Active in Cal.com ✅
Usage: NEVER QUERIED ❌
```

### Event Type 2563193 (30-min)
```
Service: 47
Duration: 30 minutes
Team: 39203
Status: Active in Cal.com ✅
Usage: ALWAYS QUERIED ✅
```

---

## 4. BRANCHES & STAFF

### Branch: München
```
ID: 9f4d5e2a-46f7-41b6-b81d-1532725381d4
Name: "AskProAI Hauptsitz München"
Company: 15
Phone: +493083793369
Active: YES
Services: 13 (all company services)
```

### Staff
```
Total: 3 members
Branch: München (9f4d5e2a...)
Active: All YES
Cal.com IDs: Not linked (shows "NICHT VERKNÜPFT")
```

---

## 5. CURRENT BOOKING FLOW

### Step-by-Step: What Happens When Customer Calls

```
1. Customer dials: +493083793369
   ↓
2. Phone lookup: Found (03513893-d962-4db0-858c-ea5b0e227e9a)
   ↓
3. Company: 15 (AskProAI)
   ↓
4. Branch: 9f4d5e2a... (München)
   ↓
5. Retell Agent: agent_b36ecd3927a81834b6d56ab07b
   ↓
6. Service Selection: getDefaultService(15) → Service 47 (30 min)
   ↓
7. Check Availability: Event Type 2563193 (30 min slots)
   ↓
8. Booking: Always creates 30-minute appointment
```

**Problem**: Even if customer says "I need a quick 15-minute consultation", system still uses 30-minute service.

---

## 6. CRITICAL ISSUE: Service 32 Never Used

### The Problem

```
Customer: "Ich brauche nur 15 Minuten Beratung"
          ↓
Agent: Understands request
          ↓
System: Calls checkAvailability(date, time, duration=60)
          ↓
Backend: serviceId not provided → getDefaultService()
          ↓
Result: Service 47 (30 min) ← IGNORES customer request!
          ↓
Booking: 30-minute slot (wastes 15 minutes)
```

### Root Cause

**ServiceSelectionService.php:**
```php
public function getDefaultService(int $companyId): ?Service
{
    // STEP 1: Find default service
    $service = Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->where('is_default', true)  // ← ALWAYS returns Service 47
        ->first();

    return $service;  // Service 47 (30 min)
}
```

**RetellFunctionCallHandler.php:**
```php
$serviceId = $params['service_id'] ?? null;  // ← Usually null

if (!$serviceId) {
    $service = $this->serviceSelector->getDefaultService($companyId);
    // ↑ Returns Service 47 every time
}
```

---

## 7. RECOMMENDED FIX

### Solution: Duration-Based Service Selection

**Modify ServiceSelectionService.php:**
```php
public function getDefaultService(
    int $companyId,
    ?string $branchId = null,
    ?int $duration = null  // ← NEW parameter
): ?Service {
    // NEW: If duration provided, match service by duration
    if ($duration !== null) {
        $service = Service::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('duration_minutes', $duration)
            ->first();

        if ($service) {
            return $service;  // Service 32 if duration=15, Service 47 if duration=30
        }
    }

    // Fallback to default
    return Service::where('company_id', $companyId)
        ->where('is_active', true)
        ->where('is_default', true)
        ->first();
}
```

**Update RetellFunctionCallHandler.php:**
```php
$duration = $params['duration'] ?? 60;  // ← Extract duration
$serviceId = $params['service_id'] ?? null;

if (!$serviceId) {
    $service = $this->serviceSelector->getDefaultService(
        $companyId,
        $branchId,
        $duration  // ← Pass duration
    );
}
```

### Expected Result After Fix

```
Customer: "Ich brauche nur 15 Minuten Beratung"
          ↓
Agent: Understands request
          ↓
System: Calls checkAvailability(date, time, duration=15)
          ↓
Backend: getDefaultService(15, null, 15)
          ↓
Result: Service 32 (15 min) ✅ CORRECT!
          ↓
Booking: 15-minute slot
```

---

## 8. VERIFICATION QUERIES

### Check Company Setup
```sql
SELECT id, name, calcom_team_id, is_active
FROM companies
WHERE id = 15;
```

### Check Phone Number Assignment
```sql
SELECT number, company_id, branch_id, retell_agent_id, is_active
FROM phone_numbers
WHERE company_id = 15;
```

### Check Services (Most Important!)
```sql
SELECT
    id,
    name,
    calcom_event_type_id,
    duration_minutes,
    is_default,
    priority,
    is_active
FROM services
WHERE company_id = 15
ORDER BY priority ASC;

-- Expected Results:
-- 47 | "AskProAI + aus Berlin..." | 2563193 | 30 | true  | 10 | true
-- 32 | "15 Minuten Schnellberatung" | 3664712 | 15 | false | 50 | true
```

### Check Which Service Is Currently Selected
```bash
php artisan tinker --execute="
\$service = app(\App\Services\Retell\ServiceSelectionService::class)
    ->getDefaultService(15, null);
echo 'Selected Service: ' . \$service->id . ' - ' . \$service->name;
"
# Expected Output: Selected Service: 47 - AskProAI + aus Berlin...
```

### After Fix: Test Duration-Based Selection
```bash
# Test 15-minute selection
php artisan tinker --execute="
\$service = app(\App\Services\Retell\ServiceSelectionService::class)
    ->getDefaultService(15, null, 15);
echo 'Service: ' . \$service->id . ' (' . \$service->duration_minutes . ' min)';
"
# Expected Output: Service: 32 (15 min)

# Test 30-minute selection
php artisan tinker --execute="
\$service = app(\App\Services\Retell\ServiceSelectionService::class)
    ->getDefaultService(15, null, 30);
echo 'Service: ' . \$service->id . ' (' . \$service->duration_minutes . ' min)';
"
# Expected Output: Service: 47 (30 min)
```

---

## 9. FILES TO MODIFY

### File 1: ServiceSelectionService.php
```
Path: /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php
Method: getDefaultService()
Lines: 36-94
Change: Add $duration parameter and duration-matching logic
```

### File 2: RetellFunctionCallHandler.php
```
Path: /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php

Changes in 3 functions:
1. checkAvailability() - Line ~233
2. bookAppointment() - Line ~572
3. getAlternatives() - Line ~472

Change: Extract duration, pass to getDefaultService()
```

---

## 10. TESTING CHECKLIST

### Before Fix (Verify Current Broken Behavior)
- [ ] Call +493083793369
- [ ] Request "15-Minuten Beratung"
- [ ] Observe: System books 30-minute slot (WRONG)
- [ ] Check database: service_id = 47

### After Fix (Verify Corrected Behavior)
- [ ] Deploy duration-based selection code
- [ ] Call +493083793369
- [ ] Request "15-Minuten Beratung"
- [ ] Observe: System books 15-minute slot (CORRECT)
- [ ] Check database: service_id = 32

### Additional Tests
- [ ] Request "30-Minuten Beratung" → Should use Service 47
- [ ] Request without duration → Should default to Service 47
- [ ] Check Cal.com bookings:
  - [ ] 15-min bookings use Event Type 3664712
  - [ ] 30-min bookings use Event Type 2563193

---

## 11. AGENT VERIFICATION NEEDED

### Agent: agent_616d645570ae613e421edb98e7

**Status**: Referenced but not assigned to production phone

**Questions**:
1. Is this a test agent or production agent?
2. Should it replace agent_b36ecd3927a81834b6d56ab07b?
3. Does it have different function definitions?
4. Does it include `service_id` parameter in `collect_appointment_data()`?

**Verification Script**:
```bash
php /home/user/askproai-api/scripts/check_conversational_agent.php
```

**Check For**:
- [ ] Agent exists in Retell.ai
- [ ] Function `list_services` exists
- [ ] Function `collect_appointment_data` has `service_id` parameter
- [ ] Function definitions are correct

---

## 12. SUMMARY TABLE

| Component | Current Value | Status | Notes |
|-----------|---------------|--------|-------|
| **Company** |
| Company ID | 15 | ✅ | AskProAI |
| Cal.com Team | 39203 | ✅ | Active |
| **Phone** |
| Number | +493083793369 | ✅ | Primary phone |
| Company Link | 15 | ✅ | Correct |
| Branch Link | 9f4d5e2a... | ✅ | München |
| **Services** |
| Service 32 (15 min) | Event 3664712 | ⚠️ | Active but NOT USED |
| Service 47 (30 min) | Event 2563193 | ✅ | Default, ALWAYS USED |
| **Agents** |
| Primary Agent | agent_b36ecd...b07b | ✅ | Active on production phone |
| Secondary Agent | agent_616d64...98e7 | ❓ | Referenced but not assigned |
| **Staff** |
| Total Staff | 3 | ✅ | All active |
| Cal.com Linked | 0 | ⚠️ | Not linked (may be OK) |
| **Configuration** |
| Service Selection | Default only | ❌ | No duration-based logic |
| Event Type 3664712 | Active | ⚠️ | Never queried |
| Event Type 2563193 | Active | ✅ | Always queried |

---

## 13. QUICK ACTIONS

### To Fix Service Selection (PRIORITY 1)
```bash
# 1. Edit ServiceSelectionService.php
nano /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php

# 2. Edit RetellFunctionCallHandler.php
nano /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php

# 3. Test in tinker
php artisan tinker
```

### To Check Agent Configuration
```bash
# Run agent check script
php /home/user/askproai-api/scripts/check_conversational_agent.php
```

### To Monitor Recent Calls
```sql
SELECT
    c.id,
    c.from_number,
    c.company_id,
    c.duration_sec,
    a.service_id,
    s.name AS service_name,
    c.created_at
FROM calls c
LEFT JOIN appointments a ON c.id = a.call_id
LEFT JOIN services s ON a.service_id = s.id
WHERE c.company_id = 15
ORDER BY c.created_at DESC
LIMIT 10;
```

---

**Last Updated**: 2025-10-23
**Status**: Documentation Complete
**Action Required**: Implement duration-based service selection
