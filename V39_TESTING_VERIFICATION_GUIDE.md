# 🧪 V39 Testing & Verification Guide - Nach Flow Canvas Fix

**Status:** 📋 TEST READY
**Datum:** 2025-10-24
**Priority:** 🔴 P0 CRITICAL
**Zweck:** Vollständige Verifikation nach Retell Dashboard Flow Canvas Fix

---

## 📊 ÜBERSICHT

Nach dem Fix der V39 Flow Canvas Edges in Retell Dashboard (siehe `RETELL_DASHBOARD_FIX_GUIDE_V39.md`), muss das System comprehensive getestet werden um sicherzustellen:

✅ Keine Hallucination mehr
✅ check_availability wird aufgerufen
✅ Korrekte Verfügbarkeit wird geprüft
✅ Booking Flow funktioniert end-to-end

---

## 🎯 TEST STRATEGIE

### Phase 1: Pre-Fix Baseline (AKTUELL - BROKEN)
Dokumentiere den aktuellen broken state für Vergleich

### Phase 2: Post-Fix Smoke Test
Schneller Test direkt nach Dashboard-Änderungen

### Phase 3: Comprehensive E2E Test
Vollständiger Booking Flow mit allen Szenarien

### Phase 4: 24h Monitoring
Überwachung aller Calls für 24 Stunden

---

## 📋 PHASE 1: PRE-FIX BASELINE (BROKEN STATE)

### Zweck:
Dokumentiere den aktuellen broken state um später zu vergleichen

### Test Steps:

**Terminal 1: Database Monitor**
```bash
# Monitor function traces in real-time
watch -n 2 "psql -U postgres -d api_gateway -c \"SELECT function_name, status, created_at FROM retell_function_traces WHERE call_session_id IN (SELECT id FROM retell_call_sessions ORDER BY created_at DESC LIMIT 1) ORDER BY created_at DESC LIMIT 10;\""
```

**Terminal 2: Laravel Logs**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(initialize_call|check_availability|book_appointment|Function called|🚀|✅|❌)"
```

**Test Call:**
```
1. Call: +493033081738
2. Wait for greeting
3. Say: "Termin heute um 16:00 Uhr für Herrenhaarschnitt"
4. Listen to agent response
5. Hang up after agent responds
```

**Expected Broken Behavior:**
```
❌ Agent says: "Leider ist um 16:00 Uhr kein Termin verfügbar"
❌ No pause before response (no function execution)
❌ No check_availability in logs
❌ No function traces in database
❌ User frustrated, hangs up
```

**Dokumentiere:**
```
Call ID: _______________
Duration: _______________
Agent Response: _______________
Function Calls: _______________
Database Traces: _______________
```

**Status:** ❌ BROKEN (expected)

---

## 🚀 PHASE 2: POST-FIX SMOKE TEST

### Zweck:
Schneller Test direkt nach Dashboard-Änderungen um zu verifizieren dass grundlegende Funktionalität wiederhergestellt ist

### Prerequisites:

**Verifikation VOR Test:**
```bash
# 1. Check Dashboard wurde gespeichert
# Retell Dashboard → Agent → "Last Published" timestamp sollte AKTUELL sein

# 2. Check Global Tools existieren
# Retell Dashboard → Settings → Tools → check_availability should exist

# 3. Check Flow Canvas hat Edges
# Retell Dashboard → Flow Canvas → node_03c_anonymous_customer sollte Edge haben
```

**Setup Monitoring:**

**Terminal 1: Function Traces (Real-time)**
```bash
watch -n 1 "psql -U postgres -d api_gateway -c \"SELECT function_name, status, response_data, created_at FROM retell_function_traces WHERE created_at > NOW() - INTERVAL '5 minutes' ORDER BY created_at DESC;\""
```

**Terminal 2: Laravel Logs (Filtered)**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(🚀|✅|❌|check_availability|Function called|ERROR)"
```

**Terminal 3: Cal.com API Monitor**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(Cal\.com|getSchedule|availability)"
```

### Test Execution:

**Test Call #1: Basic Availability Check**
```
📞 Call: +493033081738

Expected Flow:
0-5s:   Call connects
5-10s:  Agent: "Guten Tag! Friseur XY, wie kann ich helfen?"
10s:    User: "Termin heute 16:00 Uhr Herrenhaarschnitt"
15s:    Agent: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
        [2-3 second PAUSE - Function executing] ← CRITICAL!
20s:    Agent: "Ja, um 16:00 Uhr ist verfügbar!" (if available)
        OR
        Agent: "Leider ist 16:00 Uhr nicht verfügbar. Wie wäre 17:00 Uhr?" (if not available)
```

### Success Criteria:

**✅ IMMEDIATE (During Call):**
- [ ] Agent pauses 2-3 seconds before answering availability
- [ ] Agent gives CORRECT availability (matches Cal.com)
- [ ] No hallucination (doesn't make up answer)

**✅ LOGS (Real-time):**
```
[timestamp] 🚀 initialize_call called
[timestamp] ✅ initialize_call: Success
[timestamp] 🚀 check_availability called  ← CRITICAL!
[timestamp] ℹ️ Cal.com API: getSchedule called
[timestamp] ✅ check_availability: Success
```

**✅ DATABASE (After Call):**
```sql
SELECT function_name, status, created_at
FROM retell_function_traces
WHERE call_session_id = (
    SELECT id FROM retell_call_sessions
    ORDER BY created_at DESC LIMIT 1
);

Expected:
initialize_call     | success | [timestamp]
check_availability  | success | [timestamp]  ← CRITICAL!
```

**✅ ADMIN PANEL:**
```
URL: https://api.askproai.de/admin/retell-call-sessions
→ Latest Call
→ Function Traces Tab
→ Should show:
  ✅ initialize_call: Success
  ✅ check_availability: Success  ← CRITICAL!
```

### Failure Indicators:

**❌ KRITISCHE FAILURES:**
```
❌ Agent sagt sofort Verfügbarkeit ohne Pause
❌ Keine check_availability in Logs
❌ Keine check_availability in Database
❌ Agent sagt "nicht verfügbar" wenn CAL.COM sagt "verfügbar"
❌ 500 Error in Logs
```

**🔧 DEBUGGING BEI FAILURE:**
```bash
# 1. Check latest error
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep ERROR

# 2. Check function traces
psql -U postgres -d api_gateway -c "SELECT * FROM retell_function_traces ORDER BY created_at DESC LIMIT 5;"

# 3. Check call session
psql -U postgres -d api_gateway -c "SELECT retell_call_id, started_at, ended_at, transcript FROM retell_call_sessions ORDER BY created_at DESC LIMIT 1;"

# 4. Check Dashboard is published
# Retell Dashboard → "Last Published" timestamp

# 5. Check Flow Canvas edges
# Retell Dashboard → Flow Canvas → Verify edges exist
```

### Post-Smoke Test Action:

**IF SUCCESS (✅):**
→ Proceed to Phase 3: Comprehensive E2E Test

**IF FAILURE (❌):**
→ Analyze failure mode
→ Check RETELL_DASHBOARD_FIX_GUIDE_V39.md Troubleshooting section
→ Fix issue
→ Retry Smoke Test

---

## 🎯 PHASE 3: COMPREHENSIVE E2E TEST

### Zweck:
Vollständiger Test aller Booking Scenarios end-to-end

### Test Scenarios:

#### **Scenario 1: Available Slot - Immediate Booking**

**Setup:**
- Ensure slot available today at 16:00 in Cal.com
- Clear any conflicting appointments

**Test:**
```
📞 Call: +493033081738

User: "Termin heute 16:00 Uhr Herrenhaarschnitt"
Expected: Agent checks → Available → Books → Confirms

Monitor:
✅ check_availability called → success
✅ book_appointment called → success
✅ Cal.com booking created
✅ Database appointment created
✅ Confirmation SMS sent (if configured)
```

**Verification:**
```sql
-- Check appointment was created
SELECT * FROM appointments
WHERE scheduled_start::date = CURRENT_DATE
  AND scheduled_start::time = '16:00:00'
ORDER BY created_at DESC LIMIT 1;

-- Check Cal.com sync
SELECT * FROM calcom_bookings
WHERE appointment_id = [appointment_id_from_above]
ORDER BY created_at DESC LIMIT 1;
```

**Success Criteria:**
- [ ] Appointment in database
- [ ] Cal.com booking exists
- [ ] Status: confirmed
- [ ] Customer details captured
- [ ] Service assigned correctly

---

#### **Scenario 2: Unavailable Slot - Alternatives Offered**

**Setup:**
- Ensure 16:00 is NOT available
- Ensure 17:00 IS available

**Test:**
```
📞 Call: +493033081738

User: "Termin heute 16:00 Uhr Herrenhaarschnitt"
Expected: Agent checks → Not available → Offers 17:00 → Books

Monitor:
✅ check_availability called → not available
✅ get_alternatives called → returns 17:00
✅ Agent offers: "Leider nicht verfügbar. Wie wäre 17:00 Uhr?"
✅ User accepts
✅ book_appointment called → success
```

**Success Criteria:**
- [ ] Agent checked 16:00
- [ ] Agent offered alternatives
- [ ] Agent booked alternative time
- [ ] Booking confirmed at 17:00

---

#### **Scenario 3: Anonymous Customer (No Customer ID)**

**Setup:**
- Use phone number NOT in database

**Test:**
```
📞 Call: +493033081738 (from different number)

Expected Flow:
✅ initialize_call → customer_known: false
✅ Agent asks for name
✅ check_availability works WITHOUT customer_id
✅ book_appointment collects customer details
✅ New customer created in database
```

**Success Criteria:**
- [ ] Flow handles anonymous customer
- [ ] Name collected during call
- [ ] Appointment booked successfully
- [ ] New customer record created

---

#### **Scenario 4: Known Customer (Has Customer ID)**

**Setup:**
- Use phone number in database (e.g., +491234567890)

**Test:**
```
📞 Call: +493033081738

Expected Flow:
✅ initialize_call → customer_known: true, customer_name: "Max Mustermann"
✅ Agent greets: "Hallo Max Mustermann!"
✅ check_availability uses customer_id
✅ book_appointment pre-fills customer details
```

**Success Criteria:**
- [ ] Customer recognized by phone
- [ ] Personalized greeting
- [ ] Appointment linked to existing customer

---

#### **Scenario 5: Multiple Services**

**Test:**
```
📞 Call: +493033081738

User: "Termin heute 16:00 Uhr Herrenhaarschnitt und Bartpflege"

Expected:
✅ extract_appointment_variables → dienstleistung: ["Herrenhaarschnitt", "Bartpflege"]
✅ check_availability checks duration for BOTH services
✅ Agent confirms both services
✅ Booking includes both services
```

**Success Criteria:**
- [ ] Both services detected
- [ ] Correct total duration calculated
- [ ] Both services in booking

---

#### **Scenario 6: Edge Case - Tomorrow Booking**

**Test:**
```
📞 Call: +493033081738

User: "Termin morgen um 11:00 Uhr"

Expected:
✅ extract_appointment_variables → datum: "morgen" → parsed to tomorrow's date
✅ check_availability checks TOMORROW
✅ Agent confirms correct date
```

**Success Criteria:**
- [ ] Correct date parsed (tomorrow)
- [ ] Availability checked for correct day
- [ ] Booking created for tomorrow

---

#### **Scenario 7: Edge Case - No Service Specified**

**Test:**
```
📞 Call: +493033081738

User: "Termin heute 16:00 Uhr"  (no service mentioned)

Expected:
✅ Agent asks: "Welche Dienstleistung möchten Sie?"
✅ User responds: "Herrenhaarschnitt"
✅ check_availability called with service
```

**Success Criteria:**
- [ ] Agent requests missing information
- [ ] Flow continues after clarification
- [ ] Booking successful

---

### E2E Test Summary Template:

```markdown
## E2E Test Results - [Date] [Time]

### Scenario 1: Available Slot
- Status: ✅ / ❌
- Duration: ___ seconds
- Functions Called: ___________
- Booking ID: ___________
- Issues: ___________

### Scenario 2: Unavailable + Alternatives
- Status: ✅ / ❌
- Alternatives Offered: ___________
- Final Booking Time: ___________
- Issues: ___________

### Scenario 3: Anonymous Customer
- Status: ✅ / ❌
- Customer Created: ✅ / ❌
- Customer ID: ___________
- Issues: ___________

### Scenario 4: Known Customer
- Status: ✅ / ❌
- Customer Recognized: ✅ / ❌
- Personalization Working: ✅ / ❌
- Issues: ___________

### Scenario 5: Multiple Services
- Status: ✅ / ❌
- Services Detected: ___________
- Duration Correct: ✅ / ❌
- Issues: ___________

### Scenario 6: Tomorrow Booking
- Status: ✅ / ❌
- Date Parsed Correctly: ✅ / ❌
- Booking Date: ___________
- Issues: ___________

### Scenario 7: No Service Specified
- Status: ✅ / ❌
- Agent Requested Info: ✅ / ❌
- Flow Completed: ✅ / ❌
- Issues: ___________

## Overall Status: ✅ PASS / ❌ FAIL
## Critical Issues: ___________
## Minor Issues: ___________
## Recommendations: ___________
```

---

## 📊 PHASE 4: 24H MONITORING

### Zweck:
Kontinuierliche Überwachung nach Deployment um sicherzustellen dass fix stabil ist

### Monitoring Setup:

**1. Function Trace Dashboard Query**
```sql
-- Run every hour for 24h
SELECT
    DATE_TRUNC('hour', created_at) AS hour,
    function_name,
    COUNT(*) AS call_count,
    COUNT(*) FILTER (WHERE status = 'success') AS success_count,
    COUNT(*) FILTER (WHERE status = 'error') AS error_count,
    ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at))), 2) AS avg_duration_sec
FROM retell_function_traces
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY hour, function_name
ORDER BY hour DESC, function_name;
```

**Expected Healthy Metrics:**
```
check_availability:
  - Call count: >0 (should be called!)
  - Success rate: >95%
  - Avg duration: <3 seconds

book_appointment:
  - Success rate: >90%
  - Avg duration: <2 seconds

initialize_call:
  - Success rate: 100%
  - Avg duration: <1 second
```

**2. Hallucination Detection Query**
```sql
-- Detect potential hallucinations (calls with NO check_availability)
SELECT
    cs.id AS session_id,
    cs.retell_call_id,
    cs.started_at,
    c.transcript,
    (SELECT COUNT(*) FROM retell_function_traces ft
     WHERE ft.call_session_id = cs.id
       AND ft.function_name = 'check_availability') AS availability_checks
FROM retell_call_sessions cs
LEFT JOIN calls c ON c.retell_call_id = cs.retell_call_id
WHERE cs.started_at > NOW() - INTERVAL '24 hours'
  AND c.transcript ILIKE '%verfügbar%'  -- Mentioned availability
  AND NOT EXISTS (
      SELECT 1 FROM retell_function_traces ft
      WHERE ft.call_session_id = cs.id
        AND ft.function_name = 'check_availability'
  )
ORDER BY cs.started_at DESC;
```

**If ANY results:** 🚨 Potential hallucination detected!

**3. Error Pattern Analysis**
```sql
-- Detect recurring errors
SELECT
    function_name,
    error_message,
    COUNT(*) AS occurrence_count,
    MAX(created_at) AS last_occurrence
FROM retell_function_traces
WHERE status = 'error'
  AND created_at > NOW() - INTERVAL '24 hours'
GROUP BY function_name, error_message
ORDER BY occurrence_count DESC;
```

**4. Booking Success Rate**
```sql
-- Overall booking success rate
SELECT
    COUNT(*) AS total_calls,
    COUNT(*) FILTER (WHERE c.call_successful = TRUE) AS successful_calls,
    ROUND(100.0 * COUNT(*) FILTER (WHERE c.call_successful = TRUE) / COUNT(*), 2) AS success_rate_pct
FROM retell_call_sessions cs
JOIN calls c ON c.retell_call_id = cs.retell_call_id
WHERE cs.started_at > NOW() - INTERVAL '24 hours';
```

**Target:** >80% success rate

---

### Monitoring Schedule:

**Hour 1-6 (Critical Window):**
- Check every 30 minutes
- Review ALL calls manually
- Immediate intervention if issues

**Hour 6-12:**
- Check every 2 hours
- Sample 25% of calls
- Document patterns

**Hour 12-24:**
- Check every 4 hours
- Statistical analysis
- Trend identification

---

### Alert Thresholds:

**🚨 CRITICAL (Immediate Action Required):**
```
❌ check_availability success rate <80%
❌ ANY hallucination detected
❌ Booking success rate <50%
❌ Function call rate = 0
```

**⚠️ WARNING (Monitor Closely):**
```
⚠️ check_availability success rate 80-95%
⚠️ Booking success rate 50-80%
⚠️ Avg function duration >5 seconds
⚠️ Error rate >10%
```

**✅ HEALTHY:**
```
✅ check_availability success rate >95%
✅ Booking success rate >80%
✅ No hallucinations
✅ Avg function duration <3 seconds
```

---

## 🔍 DEBUGGING GUIDE

### Issue: check_availability Still Not Called

**Check:**
1. Retell Dashboard → Agent → "Last Published" timestamp
2. Flow Canvas → Verify edges exist
3. Global Tools → Verify check_availability exists with correct tool_id
4. Function Node → Verify tool_id matches Global Tool

**Fix:**
- Re-publish Agent in Dashboard
- Clear browser cache
- Wait 60 seconds
- Retry test call

---

### Issue: check_availability Called But Fails

**Check Logs:**
```bash
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -A 10 "check_availability"
```

**Common Errors:**
```
Error: "Cal.com API timeout"
Fix: Check Cal.com credentials in config/calcom.php

Error: "Service not found"
Fix: Check service exists in database for company

Error: "No availability"
Fix: Verify Cal.com has available slots
```

---

### Issue: Agent Still Hallucinates Sometimes

**Check Flow Canvas:**
- Verify ALL paths from conversation nodes have edges
- Check for orphaned nodes without edges
- Verify Function Nodes have `wait_for_result: true`

**Check Logs:**
```sql
-- Find calls with availability mention but no check
SELECT * FROM retell_call_sessions WHERE transcript ILIKE '%verfügbar%'
  AND NOT EXISTS (
    SELECT 1 FROM retell_function_traces
    WHERE function_name = 'check_availability'
      AND call_session_id = retell_call_sessions.id
  );
```

---

### Issue: Booking Created But Not in Cal.com

**Check:**
```sql
-- Find appointments without Cal.com booking
SELECT a.* FROM appointments a
WHERE a.created_at > NOW() - INTERVAL '1 hour'
  AND NOT EXISTS (
    SELECT 1 FROM calcom_bookings cb
    WHERE cb.appointment_id = a.id
  );
```

**Fix:**
```bash
# Manually trigger sync
php artisan queue:work --once

# Or re-sync specific appointment
php artisan app:sync-appointment-to-calcom [appointment_id]
```

---

## 📈 SUCCESS METRICS DEFINITION

### Overall Success Criteria:

**✅ PHASE 2 SMOKE TEST:**
- [ ] check_availability appears in logs
- [ ] check_availability in database function traces
- [ ] Agent pauses before answering availability
- [ ] Agent gives correct availability (matches Cal.com)

**✅ PHASE 3 E2E TEST:**
- [ ] All 7 scenarios pass
- [ ] No hallucinations detected
- [ ] Booking flow works end-to-end
- [ ] Cal.com sync works bidirectionally

**✅ PHASE 4 MONITORING (24h):**
- [ ] check_availability success rate >95%
- [ ] Booking success rate >80%
- [ ] No hallucination incidents
- [ ] Function call latency <3 seconds avg

---

## 🎯 FINAL VERIFICATION CHECKLIST

After 24h monitoring, verify:

```markdown
## V39 Fix Verification - Final Checklist

### Technical Verification:
- [ ] check_availability called in >95% of booking attempts
- [ ] check_availability success rate >95%
- [ ] No 500 errors related to function calls
- [ ] Function traces visible in Admin Panel
- [ ] Cal.com sync working bidirectionally

### User Experience Verification:
- [ ] No hallucinations detected (0 incidents)
- [ ] Agent responses match real availability
- [ ] Agent offers alternatives when unavailable
- [ ] Booking confirmation accurate
- [ ] Customer sentiment positive (>80%)

### Business Metrics:
- [ ] Booking completion rate >80%
- [ ] Call success rate >80%
- [ ] Average call duration 60-120 seconds
- [ ] Bounce rate <20% (calls <30 seconds)
- [ ] No customer complaints about wrong availability

### Code Quality:
- [ ] No new errors in Laravel logs
- [ ] Database queries performing well
- [ ] Redis cache hit rate >90%
- [ ] No memory leaks detected
- [ ] Queue jobs processing normally

## Overall Status: ✅ PRODUCTION READY / ⚠️ NEEDS IMPROVEMENT / ❌ ROLLBACK REQUIRED

## Sign-off:
- Tested by: _______________
- Date: _______________
- Approved for production: ✅ / ❌
- Notes: _______________
```

---

## 📚 RELATED DOCUMENTATION

**Pre-Fix Analysis:**
- `CRITICAL_V39_HALLUCINATION_BUG_2025-10-24.md` - Root cause analysis
- `FIX_V3_FINAL_2025-10-24.md` - Previous initialize_call fixes

**Fix Implementation:**
- `RETELL_DASHBOARD_FIX_GUIDE_V39.md` - Step-by-step Dashboard fix guide

**Testing:** (This document)
- `V39_TESTING_VERIFICATION_GUIDE.md` - Comprehensive testing guide

**Monitoring:**
- Admin Panel: https://api.askproai.de/admin/retell-call-sessions
- Laravel Logs: /var/www/api-gateway/storage/logs/laravel.log
- Database: PostgreSQL api_gateway database

---

## 🚀 QUICK START FOR TESTING

**After Dashboard Fix ist deployed:**

```bash
# Terminal 1: Start monitoring
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -E "(check_availability|🚀|✅)"

# Terminal 2: Database watch
watch -n 2 "psql -U postgres -d api_gateway -c 'SELECT function_name, status FROM retell_function_traces ORDER BY created_at DESC LIMIT 5;'"

# Terminal 3: Make test call
# Call +493033081738
# Say: "Termin heute 16:00 Uhr Herrenhaarschnitt"

# Expected:
# ✅ "check_availability" appears in Terminal 1
# ✅ "check_availability | success" appears in Terminal 2
# ✅ Agent gives CORRECT availability
```

**IF SUCCESS:** ✅ Proceed to E2E testing
**IF FAILURE:** ❌ Review RETELL_DASHBOARD_FIX_GUIDE_V39.md troubleshooting

---

**Erstellt:** 2025-10-24 10:45
**Version:** 1.0
**Status:** 📋 READY FOR USE
**Voraussetzung:** Dashboard Fix deployed (siehe RETELL_DASHBOARD_FIX_GUIDE_V39.md)
**Ziel:** Vollständige Verifikation dass V39 Hallucination Bug behoben ist
