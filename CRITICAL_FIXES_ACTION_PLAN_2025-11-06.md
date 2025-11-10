# CRITICAL FIXES - Action Plan
**Date**: 2025-11-06 18:40 CET
**Priority**: P0 - Immediate Action Required

---

## 🔴 FIX 1: Version 60 Publishen (3 Minuten)

**Problem:**
- Voice Calls nutzen alte Version
- 0 Tool Calls → Agent halluziniert

**Fix:**
```
1. Öffne Dashboard:
   https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736

2. Rechts oben: "Publish" Button

3. Dropdown: Wähle "Version 60"

4. Klicke "Publish"

5. Fertig!
```

**Test:**
```
Voice Call machen → Tools sollten gecallt werden
```

---

## 🔴 FIX 2: Cache TTL erhöhen (5 Minuten Code)

**Problem:**
- confirm_booking: "Buchungsdaten sind abgelaufen"
- User nimmt sich Zeit → Timeout

**Current Code:** (`app/Http/Controllers/RetellFunctionCallHandler.php:1739`)
```php
Cache::put($cacheKey, $bookingData, now()->addMinutes(5)); // 5 Minuten
```

**Fix:**
```php
// CHANGE LINE 1739:
Cache::put($cacheKey, $bookingData, now()->addMinutes(10)); // 10 Minuten

// CHANGE LINE 1829:
if ($validatedAt->lt(now()->subMinutes(10))) { // war: 5
```

**Reason:**
- Voice Calls sind langsam (User Feedback: "sehr langsam")
- User braucht Zeit zum Antworten
- 5 Minuten zu kurz, 10 Minuten sicherer

**Commands:**
```bash
# Edit file
nano /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php

# Line 1739: addMinutes(5) → addMinutes(10)
# Line 1829: subMinutes(5) → subMinutes(10)

# Reload PHP-FPM
sudo service php8.3-fpm reload
```

---

## 🔴 FIX 3: Flow Error Handling (15 Minuten)

**Problem:**
- confirm_booking failed
- Agent sagt trotzdem "gebucht"

**Current Flow:**
```
func_confirm_booking
  ↓ (always)
node_booking_success
```

**Fix: Add Error Edge**

Ich erstelle jetzt den Fix-Code für Flow Update...

```bash
# Flow Update Script wird erstellt...
```

---

## 🧪 TESTING NACH FIXES

### Test 1: Voice Call
```
1. Publish Version 60 ✅
2. Call +493033081738
3. Sage: "Herrenhaarschnitt morgen um 10 Uhr"
4. Erwarte:
   - check_availability wird gecallt ✅
   - Echte Zeiten, keine Halluzinationen ✅
```

### Test 2: Booking Flow
```
1. TTL auf 10 Min erhöht ✅
2. Test Call im Dashboard
3. Sage: "Herrenhaarschnitt heute 20:30"
4. Antworte langsam (30+ Sekunden)
5. Erwarte:
   - confirm_booking funktioniert ✅
   - Kein "abgelaufen" Error ✅
```

### Test 3: Error Handling
```
1. Flow Error Edge hinzugefügt ✅
2. Provoziere Fehler (z.B. Cal.com down)
3. Erwarte:
   - Agent sagt "Termin konnte nicht gebucht werden" ✅
   - NICHT "Termin ist gebucht" ❌
```

---

## 📊 EXPECTED RESULTS

**Before Fixes:**
- ❌ Voice: 0 Tool Calls, Halluzinationen
- ❌ Test: confirm_booking failed
- ❌ Agent lügt über Erfolg

**After Fixes:**
- ✅ Voice: Tools werden gecallt
- ✅ Test: confirm_booking funktioniert
- ✅ Agent ehrlich bei Fehlern

---

## ⏱️ TIME ESTIMATE

- Fix 1 (Publish): 3 Minuten
- Fix 2 (TTL): 5 Minuten
- Fix 3 (Flow): 15 Minuten
- Testing: 10 Minuten
**Total: ~35 Minuten**

---

## 🎯 PRIORITY ORDER

1. **Fix 1 FIRST** (Publish) - Stoppt Halluzinationen sofort
2. **Fix 2 SECOND** (TTL) - Verhindert Timeouts
3. **Fix 3 THIRD** (Flow) - Bessere Error Messages

**START NOW WITH FIX 1!**

Full Analysis: `/var/www/api-gateway/CRITICAL_TEST_ANALYSIS_2025-11-06_1830.md`
