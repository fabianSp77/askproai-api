# 🔧 RETELL DATE HALLUCINATION FIX

**Date:** 2025-12-14
**Severity:** CRITICAL
**Status:** Backend Fix Complete, Agent Prompt Update Required

---

## Problem Description

### Symptom
- User says "Herrenhaarschnitt am Montag" (expecting Dec 15, 2025)
- Agent responds "Montag, 17. Juni um 14 Uhr 55" (hallucinating June 2024!)
- System books appointment for completely wrong date

### Root Cause
Retell AI Agent had **no knowledge of current date**. When processing relative dates like "Montag", it hallucinated dates from its training data.

### Evidence (Call #89687)
```json
// User said: "am Montag" (meaning Dec 15, 2025)
// Agent parsed as: "17. Juni 2024" (WRONG!)
{
  "preferred_date": "2024-06-17",  // ← Should be 2025-12-15!
  "preferred_time": "07:00"
}
```

---

## Fix Implementation

### 1. Backend Fix (COMPLETED)

**Files Modified:**
- `app/Services/RetellService.php`
- `app/Http/Controllers/RetellWebhookController.php`

**Changes:**
Added temporal context to `dynamic_variables` returned in inbound webhook response:

```php
'dynamic_variables' => [
    'heute_datum' => '14.12.2025',           // German format
    'current_date' => '2025-12-14',          // ISO format
    'current_weekday' => 'Samstag',
    'current_year' => '2025',
    'current_month' => 'Dezember',
    'naechster_montag' => '16.12.2025',
    'morgen' => '15.12.2025',
    'uebermorgen' => '16.12.2025',
]
```

### 2. Retell Agent Prompt Update (REQUIRED)

**Status:** ⚠️ NOT YET DONE - Needs manual update in Retell Dashboard

Add to the agent's system prompt:

```markdown
=== DATUMSKONTEXT ===
WICHTIG: Heute ist {{heute_datum}} ({{current_weekday}}). Jahr: {{current_year}}.

Wenn der Kunde einen relativen Tag nennt:
- "Montag" → Nächster Montag ist {{naechster_montag}}
- "Morgen" → {{morgen}}
- "Übermorgen" → {{uebermorgen}}

⚠️ NIEMALS Daten halluzinieren! Verwende NUR die bereitgestellten Variablen.
⚠️ Das Jahr ist IMMER {{current_year}}, NIEMALS 2024 oder älter.
```

---

## Testing

### Before Fix
```
User: "Montag um 7 Uhr"
Agent: "Montag, 17. Juni um 7 Uhr" ← WRONG (June 2024)
```

### After Fix (Expected)
```
User: "Montag um 7 Uhr"
Agent: "Montag, 16. Dezember um 7 Uhr" ← CORRECT (Dec 16, 2025)
```

---

## Related Issues

1. **Timezone Bug (FIXED 2025-12-14):** Cal.com UTC → Berlin conversion
2. **Silent Slot Adjustment:** PRE-SYNC changes time without informing customer
3. **Date Parsing:** `/api/retell/datetime` endpoint exists but agent doesn't use it

---

## Commits

- `[commit-hash]` fix(retell): add temporal context to prevent date hallucination

---

**Documentation Index:** `claudedocs/03_API/Retell_AI/`
**Last Updated:** 2025-12-14
