# Quick Fix Guide - Conversation Quality Issues
**TL;DR for Developers**

---

## CRITICAL ISSUE 🚨

**Problem:** AI ignores existing customer appointments
**Impact:** 13.3% of calls, customers frustrated
**Cause:** `check_customer` returns appointment data but AI doesn't use it

**Fix Location:** General prompt, customer recognition section

**Add this:**
```
When check_customer returns existing appointments, IMMEDIATELY say:
"Guten Tag, Herr [Name]! Ich sehe, Sie haben einen Termin am [date] um [time].
 Möchten Sie diesen verschieben oder einen weiteren Termin buchen?"

NEVER ignore existing appointments.
```

---

## TOP 3 HIGH PRIORITY FIXES

### 1. Stop Asking Same Question Twice (66.7% of calls affected)

**Add to prompt:**
```
CONTEXT RETENTION RULE:
Once customer provides information (name, date, time, service), store it.
NEVER ask for it again in this call.

Asking twice = conversation failure.
```

### 2. Reduce Confirmation Spam (40% of calls affected)

**Add to prompt:**
```
Avoid excessive confirmations:
❌ "Alles klar. Verstanden. Perfekt. Und Ihr Name?"
✅ "Für die Buchung benötige ich noch Ihren Namen."

Target: <25% of responses should be confirmations.
```

### 3. Use Customer Names (13.3% of calls affected)

**Add to prompt:**
```
When check_customer returns exists=true:
Greet with: "Guten Tag, Herr/Frau [Lastname]!"
Use name naturally throughout call.
```

---

## CONVERSATION STRUCTURE TEMPLATE

**Add this framework to prompt:**

```
FOLLOW THESE PHASES IN ORDER:

1. GREETING → Understand customer need
2. IDENTIFY → Check customer, acknowledge existing appointments
3. APPOINTMENT DETAILS → Get date/time/service together
4. CONFIRM → One final check
5. CLOSE → Thank and end

⚠️  NEVER return to completed phase
⚠️  NEVER ask for info from previous phase
```

---

## TESTING CHECKLIST

After fixes, verify:

- [ ] Known customer greeted by name
- [ ] Existing appointments mentioned
- [ ] No question asked twice
- [ ] <25% confirmation responses
- [ ] Smooth flow through phases

**Test calls:**
- Known customer with appointment
- Rescheduling scenario
- New customer booking

---

## CURRENT METRICS

- **Issue Rate:** 80% (12/15 calls)
- **Target:** <25%
- **Critical Issues:** 13.3%
- **Target:** 0%

---

## FILES

- Full analysis: `/var/www/api-gateway/CONVERSATION_QUALITY_ANALYSIS.md`
- Analysis script: `/tmp/analyze_mysql_transcripts.py`
- Raw data: `/tmp/transcript_analysis_20251007_061155.txt`

---

**Date:** 2025-10-07
**Next Review:** After implementing fixes (24-48 hours)
