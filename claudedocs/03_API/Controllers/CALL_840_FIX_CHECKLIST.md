# CALL #840 FIX CHECKLIST

**Date:** 2025-10-11
**Estimated Fix Time:** 30 minutes
**Risk Level:** 🟢 LOW (configuration change only)

---

## 🔴 CRITICAL ACTIONS (DO NOW)

### [ ] Step 1: Access Retell Dashboard
```
URL: https://app.retellai.com/
Login with: [your credentials]
Navigate to: Custom Functions
```

### [ ] Step 2: Find `current_time_berlin` Function
```
Look for: "current_time_berlin" in Custom Functions list
Current Status: Returns ONLY timestamp "2025-10-11 15:45:02"
```

### [ ] Step 3: Verify Current Configuration
**Document what you find:**
```
Function Name: _________________
Current URL: _________________
Method: _________________
Response Format: _________________
```

**Expected (BROKEN) Configuration:**
```json
{
  "name": "current_time_berlin",
  "url": "https://retellai.com/api/time",  // or similar internal URL
  "method": "GET",
  "response": "2025-10-11 15:45:02"  // timestamp only
}
```

### [ ] Step 4: Update Function URL
**Change Configuration to:**
```json
{
  "name": "current_time_berlin",
  "url": "https://api.askproai.de/api/zeitinfo",
  "method": "GET",
  "timeout": 5000,
  "headers": {}
}
```

**Expected Response Format:**
```json
{
  "date": "11.10.2025",
  "time": "15:56",
  "weekday": "Samstag",
  "iso_date": "2025-10-11",
  "week_number": "41"
}
```

### [ ] Step 5: Test Function in Retell Console
```
1. In Retell Dashboard → Test Console
2. Execute: current_time_berlin()
3. Verify response contains 'weekday' field
4. Check weekday value is correct (today: Samstag)
```

**Test Command:**
```javascript
// In Retell test console
current_time_berlin();

// Expected output:
// {
//   "date": "11.10.2025",
//   "time": "HH:MM",
//   "weekday": "Samstag",
//   ...
// }
```

### [ ] Step 6: Update Agent Prompt (Add Safety Rule)
**Navigate to:** Retell Dashboard → Your Agent → Prompt Editor

**Add this section (at top, in BOLD):**

```markdown
═══════════════════════════════════════════════
🔴 KRITISCHE REGEL: WOCHENTAG UND DATUM
═══════════════════════════════════════════════

Die current_time_berlin() Funktion gibt folgendes zurück:
{
  "date": "11.10.2025",
  "time": "15:45",
  "weekday": "Samstag",
  "iso_date": "2025-10-11"
}

REGELN:
1. IMMER current_time_berlin() aufrufen BEVOR du ein Datum nennst
2. NUR den Wochentag aus dem 'weekday' Feld nutzen (NIEMALS raten!)
3. Format: "Heute ist [weekday], der [day]. [month]"
   Beispiel: "Heute ist Samstag, der 11. Oktober"
4. Jahr NIEMALS nennen (außer bei expliziter Nachfrage)

WENN 'weekday' Feld fehlt (sollte nicht passieren):
→ Sag NUR: "Heute ist der 11. Oktober" (OHNE Wochentag)
→ NIEMALS einen Wochentag raten oder schätzen

VERBOTENE PHRASEN:
❌ "Heute ist Freitag" (wenn 'weekday' = "Samstag")
❌ "Heute ist der 11. Oktober 2025" (Jahr ohne Nachfrage)
❌ "Es gab ein technisches Problem"
❌ "Ein Fehler ist aufgetreten"

ERLAUBTE PHRASEN:
✅ "Heute ist Samstag, der 11. Oktober"
✅ "Morgen, Sonntag, den 12. Oktober"
✅ "Diese Zeit ist leider belegt. Wie wäre es mit 17:00 Uhr?"
```

### [ ] Step 7: Rollback to Agent Version 80
**Why:** Version 84 is overconfident, v80 is conservative and proven stable

**Steps:**
```
1. Retell Dashboard → Your Agent → Version History
2. Find Version 80 (used in Call #837 - successful)
3. Click "Restore this version"
4. Confirm rollback
```

**Version Comparison:**
- v80: 22s duration, appointment_booked ✅
- v84: 115s duration, abandoned ❌

---

## 🟡 VERIFICATION TESTS (AFTER FIX)

### [ ] Test 1: Basic Date Query
**Call Script:**
```
Agent: [greeting]
User: "Welcher Tag ist heute?"
Agent: "Heute ist Samstag, der 11. Oktober"  ← Verify correct weekday!
```

**Success Criteria:**
- ✅ Weekday correct ("Samstag" not "Freitag")
- ✅ No year mentioned ("2025")
- ✅ Response immediate (<3s)

### [ ] Test 2: Appointment Booking (End-to-End)
**Call Script:**
```
Agent: [greeting]
User: "Ich möchte einen Termin buchen"
Agent: "Gerne! Für welchen Tag?"
User: "Heute, 16:00 Uhr"
Agent: "Heute ist Samstag, der 11. Oktober. Ich prüfe die Verfügbarkeit für 16:00 Uhr..."
```

**Success Criteria:**
- ✅ Weekday correct
- ✅ No error messages
- ✅ Duration <40s
- ✅ Outcome: appointment_booked

### [ ] Test 3: User Challenges Date
**Call Script:**
```
Agent: "Heute ist Samstag, der 11. Oktober"
User: "Bist du sicher? Welcher Wochentag ist heute?"
Agent: "Ja, heute ist Samstag, der 11. Oktober"  ← Should NOT change!
```

**Success Criteria:**
- ✅ Agent doesn't change answer
- ✅ Agent remains confident
- ✅ No "I might be wrong" phrases

### [ ] Test 4: Function Error Handling
**Simulate:** Block `/api/zeitinfo` temporarily

**Call Script:**
```
User: "Welcher Tag ist heute?"
Agent: "Welches Datum hätten Sie gern für den Termin?"  ← Graceful fallback
```

**Success Criteria:**
- ✅ No "technical problem" message
- ✅ Graceful fallback to asking user
- ✅ No hallucinated date

---

## 📊 MONITORING (NEXT 24 HOURS)

### [ ] Check Next 10 Calls
**Monitor for:**
```sql
SELECT
    id,
    TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) as duration_sec,
    session_outcome,
    transcript LIKE '%Freitag%' as mentions_wrong_weekday,
    transcript LIKE '%2025%' as mentions_year,
    transcript LIKE '%Problem%' as mentions_problem
FROM calls
WHERE created_at > NOW()
ORDER BY id DESC
LIMIT 10;
```

**Alert Thresholds:**
- ❌ Duration >60s for appointment booking
- ❌ ANY mention of wrong weekday
- ❌ Year mentioned (unless appointment next year)
- ❌ "Problem" or "Fehler" mentioned

### [ ] Dashboard Metrics
**Track:**
- Average call duration (target: <40s)
- Abandonment rate (target: <10%)
- Date/time accuracy (target: 100%)
- Prompt compliance (target: >95%)

---

## 🔄 ROLLBACK PLAN (IF FIX FAILS)

### If problems persist after fix:

**Option 1: Remove Date Mentions Entirely**
```markdown
# Add to prompt:
NIEMALS das aktuelle Datum oder den Wochentag erwähnen.
Nutze stattdessen:
- "heute"
- "morgen"
- "nächste Woche"
```

**Option 2: Use Static Date Mapping**
```markdown
# In prompt, add:
Falls current_time_berlin() fehlschlägt:
→ Nutze NIEMALS geraten
→ Sage: "Welches Datum möchten Sie?"
→ Lass den User das Datum nennen
```

**Option 3: Emergency Prompt (Minimal Viable)**
```markdown
Du bist Terminbuchungs-Assistent.

1. Begrüßung: "Willkommen, wie kann ich helfen?"
2. Datum erfragen: "Welches Datum hätten Sie gern?"
3. Uhrzeit erfragen: "Um welche Uhrzeit?"
4. Kundendaten: "Ihr Name und E-Mail?"
5. Bestätigung: "Termin gebucht für [User-Datum] um [Uhrzeit]"

NIEMALS:
- Aktuelles Datum nennen
- Wochentag nennen
- "Problem" sagen
- Jahr nennen
```

---

## ✅ SUCCESS CRITERIA

**Fix is successful when:**
- ✅ Next 10 calls: 0 wrong weekday mentions
- ✅ Average duration: <40s
- ✅ Abandonment rate: <10%
- ✅ No "technical problem" messages
- ✅ No year mentions (unless asked)
- ✅ All test calls pass

---

## 📝 POST-FIX DOCUMENTATION

### [ ] Update Function Documentation
**File:** `/var/www/api-gateway/docs/RETELL_ZEITINFO_FUNCTION.md`

**Add section:**
```markdown
## Fix History

### 2025-10-11: Function URL Correction
**Issue:** Agent was calling wrong endpoint, receiving only timestamp
**Fix:** Updated Retell Custom Function URL to https://api.askproai.de/api/zeitinfo
**Result:** Correct weekday data now returned
**Verified by:** Call #840 root cause analysis
```

### [ ] Create Incident Report
**File:** `/var/www/api-gateway/claudedocs/INCIDENT_CALL_840_RESOLVED.md`

**Content:**
- Root cause summary
- Fix implemented
- Verification results
- Lessons learned
- Prevention measures

---

## 🎯 FINAL CHECKLIST

**Before marking this complete:**
- [ ] Function URL updated in Retell Dashboard
- [ ] Function tested and returns correct data
- [ ] Prompt updated with safety rules
- [ ] Agent rolled back to v80
- [ ] All 4 test calls successful
- [ ] Monitoring active for next 10 calls
- [ ] Documentation updated
- [ ] Incident report created

**Estimated Total Time:** 30-45 minutes
**Required Access:** Retell Dashboard admin
**Risk Level:** 🟢 LOW
**Rollback Time:** <5 minutes

---

**Start Time:** __________
**Completion Time:** __________
**Verified By:** __________
**Status:** ⏳ PENDING / ✅ COMPLETE / ❌ FAILED
