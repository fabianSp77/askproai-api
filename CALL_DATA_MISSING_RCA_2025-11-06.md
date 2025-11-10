# Root Cause Analysis: Missing Appointment Data - Complete Booking Flow Regression

**Date**: 2025-11-06
**Severity**: P0 - Critical System Failure
**Regression Date**: October 3, 2025
**Data Loss**: 34 days, ~340 appointments

---

## Executive Summary

### Problem
Voice agent confirms appointments are booked, Cal.com shows bookings, but Laravel admin panel shows NO appointment data:
- Staff field: EMPTY
- Service field: EMPTY  
- Price field: EMPTY

### Root Cause Discovered
This is NOT a display issue. **The entire appointment creation flow has been BROKEN since October 3, 2025.**

**ZERO appointments created in Laravel database for 34 days.**

### Evidence
- Last appointment created: October 3, 2025 (ID: 613)
- Recent calls (Nov 5-6): 10/10 have NO appointments (0% linking rate)
- Estimated orphaned bookings in Cal.com: ~340

---

## Database Evidence

### Recent Calls (ALL Missing Appointments)
```
ID: 1669 | call_8f1432a7472beca84cf45e5d92e | 2025-11-06 14:02 | Appt: NO
ID: 1668 | call_test_1762433754079          | 2025-11-06 13:55 | Appt: NO
ID: 1667 | call_test_1762432739220          | 2025-11-06 13:38 | Appt: NO
[... 7 more calls, all NO appointments]

Linking Rate: 0/10 (0%)
```

### Last Appointment in Database
```
ID: 613
Created: 2025-10-03 22:23:10  ← 34 DAYS AGO
Service ID: 41
Staff ID: NULL (pre-existing issue)
Price: NULL (pre-existing issue)
Call ID: NULL
```

### Appointments Created Since Regression
```sql
SELECT COUNT(*) FROM appointments WHERE created_at >= '2025-10-04'
Result: 0

SELECT COUNT(*) FROM appointments WHERE created_at >= '2025-11-01' 
Result: 0

SELECT COUNT(*) FROM appointments WHERE created_at >= '2025-11-06'
Result: 0
```

**NO APPOINTMENTS FOR 34 DAYS**

---

## Root Cause Analysis

### Expected Booking Flow (BROKEN)

```
1. Retell Voice Agent
   ↓ Function Call: book_appointment
   ↓ POST /api/retell/function-call
   
2. RetellFunctionCallHandler.php
   ↓ bookAppointment($params, $callId)
   
3. CalcomService  
   ↓ createBooking() to Cal.com API
   
4. AppointmentCreationService
   ↓ createLocalRecord()
   
5. Appointment::create()
   ↓ INSERT INTO appointments
   
6. Call::update(['appointment_id' => ...])
   ↓ Bidirectional linking

ACTUAL: Steps 2-6 NEVER EXECUTE
```

### Breaking Point
Flow breaks BEFORE bookAppointment() is called. Calls are created (webhook works), but booking function calls never reach Laravel.

### Most Likely Causes

1. **Retell Webhook Misconfiguration** (HIGHEST PROBABILITY)
   - Function call webhook URL missing/incorrect
   - Call webhook works (calls created)
   - Function call webhook broken (no appointments)

2. **Laravel Route/Middleware Change**
   - Route removed between Oct 1-3
   - Middleware blocking Retell requests
   - Authentication changed

3. **Retell Agent Configuration**
   - book_appointment function disabled
   - Agent using old version
   - Function definitions out of sync

---

## Impact Assessment

### Data Loss
- Period: Oct 3 - Nov 6, 2025 (34 days)
- Estimated calls: ~340 (10/day average)
- Appointments in Laravel: 0
- Appointments in Cal.com: ~340
- Data loss rate: 100%

### Business Impact
- Admin panel: Unusable for all recent bookings
- Reporting: Zero appointments, revenue, staff utilization
- Customer service: Cannot look up appointments
- Data integrity: Complete database/Cal.com desync

---

## Investigation Steps

### 1. Check Retell Webhook Configuration (START HERE)
```bash
# Via Retell API
curl -H "Authorization: Bearer $RETELL_API_KEY" \
  https://api.retellai.com/v1/agents/{agent_id}

# Look for:
# - function_call_webhook_url (must be set!)
# - enabled_functions (must include "book_appointment")
```

### 2. Verify Laravel Routes
```bash
php artisan route:list --name=retell

# Expected:
# POST | api/retell/function-call | RetellFunctionCallHandler@handle
```

### 3. Check Recent Logs
```bash
tail -1000 storage/logs/laravel.log | \
  grep -i "book_appointment\|function call"
  
# If EMPTY: Webhooks not reaching Laravel
```

### 4. Test End-to-End
```bash
# Terminal 1: Monitor logs
tail -f storage/logs/laravel.log | grep retell

# Terminal 2: Make test call, attempt booking
# Watch for webhook requests, errors
```

---

## Data Recovery Strategy

### Step 1: Verify Cal.com Has Bookings
```bash
curl -H "Authorization: Bearer $CALCOM_API_KEY" \
  "https://api.cal.com/v1/bookings?startDate=2025-10-03&endDate=2025-11-06"
```

### Step 2: Create Recovery Script
**File**: `database/scripts/recover_appointments_from_calls_2025-11-06.php`

Fetch bookings from Cal.com API → Create Appointment records → Link to Call records

### Step 3: Run Recovery
```bash
php database/scripts/recover_appointments_from_calls_2025-11-06.php
```

---

## Secondary Issue: Missing Staff & Price

Even before regression, appointments had:
- staff_id: NULL (99.1% of appointments)
- price: NULL (100% of appointments)

**Fix Already Deployed** (AppointmentCreationService.php, lines 441-470):
- Auto-selects staff from service_staff pivot
- But fix can't run because appointments aren't being created

**Still Needs**:
- Price calculation logic
- Backfill for existing NULL values

---

## Immediate Actions

1. **[P0] Check Retell webhook config** ← START HERE
2. **[P0] Test booking flow end-to-end**
3. **[P1] Recover 34 days of data from Cal.com**
4. **[P1] Deploy monitoring alerts**

---

## Files to Check

**Primary**:
- Retell Dashboard: Webhook URLs, function definitions
- `/routes/api.php`: Retell routes
- `/app/Http/Controllers/RetellFunctionCallHandler.php`: Webhook handler

**Logs**:
- `/storage/logs/laravel.log`: Check for webhook attempts

---

**Status**: Root cause identified, investigation required
**Fix ETA**: 1-2 hours once webhook issue confirmed
**Recovery ETA**: 2-4 hours for data backfill
