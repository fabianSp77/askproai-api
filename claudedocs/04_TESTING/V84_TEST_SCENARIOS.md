# V84 Test Scenarios - 2-Step Confirmation & Name Enforcement
**Version**: V84
**Date**: 2025-10-14
**Purpose**: Validate fixes for Name Query & Confirmation issues

---

## OVERVIEW

**Fixes Being Tested:**
1. ✅ Name enforcement - reject "Unbekannt" placeholder
2. ✅ 2-step confirmation - require explicit user "Ja"
3. ✅ Data validation - never accept invented data
4. ✅ check_customer() mandatory before booking

---

## TEST 1: Anonymous Caller (Call 872 Reproduction)

### Purpose
Validate that anonymous callers are asked for their name BEFORE booking

### Expected Behavior (V84)
1. Agent greets generically
2. `current_time_berlin()` is called
3. `check_customer()` is called → returns `status='anonymous'`
4. Agent: "Möchten Sie einen Termin buchen? Für die Buchung benötige ich Ihren Namen."
5. User provides name (e.g., "Schmidt")
6. Agent asks for date/time: "Für welchen Tag und welche Uhrzeit?"
7. User: "Morgen 14 Uhr"
8. Agent calls `collect_appointment_data(bestaetigung: false)` → STEP 1
9. Agent: "Morgen um 14 Uhr ist noch frei. Soll ich den Termin für Sie buchen?"
10. User: "Ja"
11. Agent calls `collect_appointment_data(bestaetigung: true)` → STEP 2
12. Agent: "Perfekt! Ihr Termin morgen um 14 Uhr wurde gebucht."

### Test Script
```
Tester → Call system (anonymous number)
Expected: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Guten Tag!"
Expected: [System calls current_time_berlin() and check_customer() in background]

Tester: "Ich hätte gern einen Termin"
Expected: "Für die Buchung benötige ich Ihren Namen. Wie heißen Sie?"

Tester: "Müller"
Expected: "Vielen Dank, Herr Müller. Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"

Tester: "Morgen um 14 Uhr"
Expected: [System calls collect_appointment with bestaetigung: false]
Expected: "Morgen um 14 Uhr ist noch frei. Soll ich den Termin für Sie buchen?"

Tester: "Ja, bitte"
Expected: [System calls collect_appointment with bestaetigung: true]
Expected: "Perfekt! Ihr Termin morgen um 14 Uhr wurde gebucht."
```

### Validation
**Check Database:**
```sql
SELECT id, call_id, customer_name, from_number, customer_id
FROM calls
ORDER BY created_at DESC LIMIT 1;

-- customer_name should be "Müller", NOT "Unbekannt"
```

```sql
SELECT id, name, phone, source
FROM customers
WHERE id = (SELECT customer_id FROM calls ORDER BY created_at DESC LIMIT 1);

-- name should be "Müller", NOT "Unbekannt #XXXX" or "Anonym XXXX"
```

**Check Logs:**
```bash
tail -100 storage/logs/laravel.log | grep "check_customer\|collect_appointment\|bestaetigung"

# Expected:
# - check_customer called with call_id
# - collect_appointment called TWICE
# - First call: bestaetigung: false
# - Second call: bestaetigung: true
```

### Success Criteria
- ✅ `check_customer()` called before any booking logic
- ✅ Name requested from anonymous caller
- ✅ Real name stored in database (not "Unbekannt")
- ✅ 2-step process followed (bestaetigung: false → true)
- ✅ No booking without user "Ja"

### Failure Cases
- ❌ System books with "Unbekannt" → FAIL (RC1 not fixed)
- ❌ System books without asking for confirmation → FAIL (RC2 not fixed)
- ❌ System invents date/time → FAIL (RC3 not fixed)

---

## TEST 2: Incomplete Data (Call 873 Reproduction)

### Purpose
Validate that system NEVER invents date/time when user doesn't provide it

### Expected Behavior (V84)
1. User: "Ich hätte gerne einen Termin"
2. Agent: "Gerne! Für welchen Tag und welche Uhrzeit?"
3. Agent DOES NOT call `collect_appointment_data` yet
4. User: "Morgen um 14 Uhr"
5. Agent NOW calls `collect_appointment_data(bestaetigung: false)`

### Test Script
```
Tester → Call system
Expected: Greeting + check_customer()

Tester: "Ich hätte gern einen Termin"
Expected: "Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"
Expected: [NO collect_appointment call yet]

[Monitor logs - should NOT see collect_appointment at this point]

Tester: "Morgen um 14 Uhr"
Expected: [NOW collect_appointment is called with datum="morgen" uhrzeit="14:00"]
Expected: "Morgen um 14 Uhr ist noch frei. Soll ich den Termin buchen?"
```

### Validation
**Check Logs:**
```bash
tail -500 storage/logs/laravel.log | grep -A 5 "collect_appointment"

# Verify:
# - collect_appointment NOT called when user said "ich hätte gern einen Termin"
# - collect_appointment ONLY called after user provided "morgen um 14 Uhr"
```

### Success Criteria
- ✅ No `collect_appointment_data` call before user provides date/time
- ✅ Agent asks for missing data
- ✅ Function call only after user provides all required data

### Failure Cases
- ❌ System calls function with invented date/time → FAIL (RC3 not fixed)

---

## TEST 3: Backend Name Validation

### Purpose
Validate that backend rejects bookings with placeholder names

### Test Script
```bash
# Manually call collect_appointment endpoint with "Unbekannt"
curl -X POST http://localhost/api/retell/collect-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "args": {
      "call_id": "test_call_123",
      "name": "Unbekannt",
      "datum": "morgen",
      "uhrzeit": "14:00",
      "dienstleistung": "Beratung"
    }
  }'
```

### Expected Response
```json
{
    "success": false,
    "status": "missing_customer_name",
    "message": "Bitte erfragen Sie zuerst den Namen des Kunden. Sagen Sie: \"Darf ich Ihren Namen haben?\"",
    "prompt_violation": true,
    "bestaetigung_status": "error"
}
```

### Validation
**Check Logs:**
```bash
tail -50 storage/logs/laravel.log | grep "PROMPT-VIOLATION"

# Expected:
# ⚠️ PROMPT-VIOLATION: Attempting to book without real customer name
# violation_type: missing_customer_name
```

### Success Criteria
- ✅ Request rejected with `missing_customer_name` error
- ✅ Prompt violation logged
- ✅ No appointment created

---

## TEST 4: Backend Confirmation Default Behavior

### Purpose
Validate that backend defaults to CHECK-ONLY when `bestaetigung` is missing

### Test Script A: Without bestaetigung Parameter
```bash
curl -X POST http://localhost/api/retell/collect-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "args": {
      "call_id": "test_call_456",
      "name": "Schmidt",
      "datum": "morgen",
      "uhrzeit": "14:00",
      "dienstleistung": "Beratung"
    }
  }'
```

### Expected Response A
```json
{
    "success": true,
    "status": "available",
    "message": "Der Termin am Mittwoch, 15. Oktober um 14:00 Uhr ist noch frei. Soll ich den Termin für Sie buchen?",
    "awaiting_confirmation": true,
    "next_action": "Wait for user 'Ja', then call collect_appointment_data with bestaetigung: true"
}
```

**Key**: No appointment created, just availability check!

### Test Script B: With bestaetigung=false
```bash
curl -X POST http://localhost/api/retell/collect-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "args": {
      "call_id": "test_call_789",
      "name": "Schmidt",
      "datum": "morgen",
      "uhrzeit": "14:00",
      "dienstleistung": "Beratung",
      "bestaetigung": false
    }
  }'
```

### Expected Response B
Same as Response A - CHECK-ONLY mode

### Test Script C: With bestaetigung=true
```bash
curl -X POST http://localhost/api/retell/collect-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "args": {
      "call_id": "test_call_999",
      "name": "Schmidt",
      "datum": "morgen",
      "uhrzeit": "14:00",
      "dienstleistung": "Beratung",
      "bestaetigung": true
    }
  }'
```

### Expected Response C
```json
{
    "success": true,
    "status": "booked",
    "message": "Perfekt! Ihr Termin am morgen um 14:00 wurde erfolgreich gebucht. Sie erhalten eine Bestätigung.",
    "appointment_id": "..."
}
```

**Key**: Appointment IS created!

### Validation
**Check Database after Test C:**
```sql
SELECT id, customer_name, starts_at, status
FROM appointments
WHERE metadata->>'call_id' = 'test_call_999';

-- Should exist with status='scheduled'
```

### Success Criteria
- ✅ Test A (no param): CHECK-ONLY, no booking
- ✅ Test B (false): CHECK-ONLY, no booking
- ✅ Test C (true): BOOKING created
- ✅ Prompt violation logged for Test A

### Failure Cases
- ❌ Test A creates appointment → FAIL (V84 not applied)
- ❌ Test B creates appointment → FAIL (logic broken)
- ❌ Test C doesn't create appointment → FAIL (regression)

---

## TEST 5: End-to-End Happy Path (Known Customer)

### Purpose
Validate complete flow for returning customer

### Test Script
```
Tester → Call from known phone number
Expected: "Schön Sie wieder zu hören, [Name]! Möchten Sie einen Termin buchen?"

Tester: "Ja, morgen um 14 Uhr"
Expected: [collect_appointment with bestaetigung: false]
Expected: "Morgen um 14 Uhr ist noch frei. Soll ich den Termin buchen?"

Tester: "Ja"
Expected: [collect_appointment with bestaetigung: true]
Expected: "Perfekt! Ihr Termin morgen um 14 Uhr wurde gebucht."
```

### Validation
```sql
-- Check appointment created
SELECT a.id, a.customer_name, a.starts_at, c.name as customer_name_from_table
FROM appointments a
JOIN customers c ON a.customer_id = c.id
ORDER BY a.created_at DESC LIMIT 1;

-- Customer name should match recognized customer
```

### Success Criteria
- ✅ Known customer recognized
- ✅ 2-step process followed
- ✅ Appointment linked to correct customer
- ✅ No duplicate customer created

---

## TEST 6: Prompt Violation Monitoring

### Purpose
Monitor and track all prompt violations for reporting

### Monitoring Script
```bash
# Run after several test calls
grep "PROMPT-VIOLATION" storage/logs/laravel.log | \
  jq -r '.message + " | " + .call_id + " | " + .violation_type'

# Expected violations during testing:
# - missing_customer_name (when testing name validation)
# - Missing bestaetigung parameter (when testing without param)
```

### Report Format
```
PROMPT VIOLATION REPORT
Date: 2025-10-14
Test Session: V84 Validation

Type: missing_customer_name
Count: X
Sample: [call_id examples]

Type: missing_bestaetigung
Count: Y
Sample: [call_id examples]

Action: Review prompt compliance with development team
```

---

## REGRESSION TESTS

### RT1: Existing Functionality (Reschedule)
Test that reschedule still works after V84 changes

### RT2: Existing Functionality (Cancel)
Test that cancellation still works

### RT3: Alternative Times
Test that alternative time offering still works when exact time unavailable

---

## MANUAL TESTING CHECKLIST

**Before deploying V84:**

- [ ] TEST 1: Anonymous caller → Name collected
- [ ] TEST 2: Incomplete data → System asks for details
- [ ] TEST 3: Backend name validation → "Unbekannt" rejected
- [ ] TEST 4A: No bestaetigung → CHECK-ONLY
- [ ] TEST 4B: bestaetigung=false → CHECK-ONLY
- [ ] TEST 4C: bestaetigung=true → BOOKING created
- [ ] TEST 5: Known customer happy path → Works end-to-end
- [ ] TEST 6: Prompt violations logged → Monitoring works
- [ ] RT1: Reschedule → Still works
- [ ] RT2: Cancel → Still works
- [ ] RT3: Alternatives → Still offered

**All tests passed?**
- [ ] YES → Deploy to production
- [ ] NO → Review failures, fix issues, retest

---

## PRODUCTION MONITORING (Post-Deploy)

**Metrics to Watch (48 hours):**

1. **Name Quality**
```sql
-- Check for "Unbekannt" in new appointments
SELECT COUNT(*) as unbekannt_count
FROM appointments
WHERE created_at > NOW() - INTERVAL '48 hours'
  AND (customer_name LIKE '%Unbekannt%' OR customer_name LIKE '%Anonym%');

-- Target: 0
```

2. **Confirmation Rate**
```bash
# Check logs for bestaetigung parameter usage
grep "collect_appointment" storage/logs/laravel.log | \
  grep -c "bestaetigung.*true"

# Should be ~50% of total collect_appointment calls (STEP 2 of 2-step process)
```

3. **Prompt Violations**
```bash
grep "PROMPT-VIOLATION" storage/logs/laravel.log | wc -l

# Target: Decreasing over time as Retell AI learns
```

4. **Customer Satisfaction**
- Monitor support tickets for booking issues
- Check appointment completion rate
- Review call transcripts for user frustration

---

**Testing Status**: ⏳ READY FOR EXECUTION
**Next Step**: Execute tests in staging environment
**Owner**: Development Team
**Timeline**: 2025-10-14 to 2025-10-16
