# CRITICAL BUG: V110.4 Testcall Analysis

**Call ID**: call_d28fc14dc436b8641573afad343
**Time**: 2025-11-10, 15:33-15:34 (73 Sekunden)
**Agent**: V110.4 (agent_c1d8dea0445f375857a55ffd61)
**Result**: ❌ Buchung fehlgeschlagen - "Service nicht verfügbar"

---

## Executive Summary

### ✅ Was funktionierte (V110.4 Improvements):

1. **customer_name wird gespeichert!** ✅
   - User: "Hans Schuster"
   - Variable: `"customer_name": "Hans Schuster"` ✅
   - **VORHER (V110.3)**: Variable blieb leer
   - **JETZT (V110.4)**: Korrekt gespeichert!

2. **Keine Verfügbarkeits-Spekulation mehr!** ✅
   - Agent sagte: "Einen Moment, ich prüfe die Verfügbarkeit..."
   - KEIN "Der Termin ist frei" mehr BEVOR check_availability
   - Direct transition funktionierte!

3. **Flow-Struktur verbessert** ✅
   - extract → DIRECT → check_availability
   - Keine unnötigen Zwischenstopps

### ❌ Was NICHT funktionierte (Critical Bug):

**KRITISCH**: `"function_name": "start_booking"` ist IMMER NOCH im parameter_mapping!

```json
{
  "tool_call_id": "tool_call_cff8bd",
  "name": "start_booking",
  "arguments": {
    "datetime": "2025-11-11 09:45",
    "service": "Herrenhaarschnitt",
    "function_name": "start_booking",  ← NOCH DA!!!
    "customer_phone": "0151123456",
    "customer_name": "Hans Schuster",
    "call_id": "1"
  },
  "result": {
    "success": false,
    "error": "Dieser Service ist leider nicht verfügbar"
  }
}
```

---

## Root Cause

### Warum mein Fix nicht funktionierte:

Ich habe in V110.4 versucht `function_name` aus start_booking zu entfernen:

```python
# Mein Code in V110.4
for node in flow['nodes']:
    if node['id'] == 'func_start_booking':
        if 'function_name' in node.get('parameter_mapping', {}):
            del node['parameter_mapping']['function_name']
```

**ABER**: Der node heißt möglicherweise anders! Ich muss den Flow durchsuchen nach `tool-start-booking`.

---

## Timeline mit Tool Calls

```
[000.0s] Agent: "Willkommen bei Friseur 1!"
[000.0s] User: "Hans Schuster, Herrenhaarschnitt morgen 10 Uhr"

[012.7s] ✅ get_current_context
[015.4s] ✅ check_customer → not found

[016.9s] Agent: "Einen Moment, ich prüfe..." ← KORREKT! Keine Spekulation!

[024.0s] ✅ extract_booking_variables
         → customer_name: "Hans Schuster" ✅
         → service_name: "Herrenhaarschnitt" ✅
         → appointment_time: "10 Uhr" ✅

[024.0s] Agent: "Einen Moment."
[028.2s] ✅ check_availability → 10:00 NICHT frei, Alternativen: 9:45, 8:50

[030.2s] Agent: "Um 10 Uhr ist belegt, aber 9:45 oder 8:50?" ← KORREKT!

[037.6s] User: "9:45 ist super. Bitte buchen."

[042.1s] ✅ select_alternative
         → selected_alternative_time: "9 Uhr 45" ✅

[043.2s] Agent: "Soll ich buchen?"
[048.1s] User: "Ja."

[051.8s] Agent: "Ihr Termin ist gebucht für morgen um 9:45" ← LÜGE!
         (Buchung ist noch NICHT durchgeführt!)

[058.5s] User gibt Telefonnummer

[065.2s] 🔧 start_booking CALLED
[066.9s] ❌ RESULT: "Dieser Service ist leider nicht verfügbar"

[068.7s] Agent: "Es tut mir leid, Service nicht buchbar"

User legt auf.
```

---

## Das Problem

### 1. Agent sagt "ist gebucht" BEVOR Buchung durchgeführt wird

**Timeline**:
- `[51.8s]` Agent: "Ihr Termin ist gebucht"
- `[65.2s]` start_booking wird aufgerufen (13 Sekunden später!)
- `[66.9s]` Fehler: "Service nicht verfügbar"

**Root Cause**:
- Der Node `node_collect_final_booking_data` hat eine Instruction die sagt "Ihr Termin ist gebucht"
- Diese wird gesprochen BEVOR der eigentliche start_booking call erfolgt
- Das ist eine LÜGE und verwirrt den User

**Fix benötigt**:
```json
// CURRENT (wrong):
{
  "id": "node_collect_final_booking_data",
  "instruction": {
    "type": "static_text",
    "text": "Ihr Termin ist gebucht für {{appointment_date}} um {{selected_alternative_time}}..."
  }
}

// SHOULD BE:
{
  "id": "node_collect_final_booking_data",
  "instruction": {
    "type": "prompt",
    "text": "SAMMLE nur Telefon/Email wenn User möchte. Sage NICHTS über 'ist gebucht'! Erst nach start_booking ist es gebucht!"
  }
}
```

### 2. "function_name" noch im parameter_mapping

**Backend Logs**:
```json
{
  "function_name": "start_booking",  ← Falsch!
  "service": "Herrenhaarschnitt",
  "datetime": "2025-11-11 09:45",
  "customer_name": "Hans Schuster",
  "call_id": "1"
}
```

**Backend Query**:
```sql
SELECT * FROM services
WHERE id = 438 AND company_id = 1 AND is_active = true
→ SUCCESS (service found!)
```

Aber dann returnt Backend: "Service nicht verfügbar"

**Warum?**

Die `function_name` Parameter verwirrt möglicherweise den Backend Code. Er sucht nach einem Service mit dem Namen "start_booking" statt "Herrenhaarschnitt".

---

## Collected Variables (Final State)

```json
{
  "customer_name": "Hans Schuster",           ← ✅ FIXED!
  "service_name": "Herrenhaarschnitt",        ← ✅ OK
  "appointment_date": "morgen",               ← ✅ OK
  "appointment_time": "10 Uhr",               ← ❌ NICHT updated! (sollte 9:45 sein)
  "selected_alternative_time": "9 Uhr 45",    ← ✅ OK
  "selected_alternative_date": "morgen",      ← ✅ OK
  "customer_phone": null,                     ← ✅ OK (User gab an)
  "customer_email": null,                     ← ✅ OK
  "current_node": "Validierungsfehler",       ← ❌ Error state
  "previous_node": "Buchung starten (Step 1)" ← ❌ Failed
}
```

**Problem**: `appointment_time` ist noch "10 Uhr" statt "9 Uhr 45"

---

## Required Fixes für V110.5

### Fix 1: Entferne "function_name" korrekt (P0)

```bash
# Search for the correct node
grep -n "tool-start-booking" conversation_flow_v110_4_fixed.json

# Manuell entfernen oder Script korrigieren
```

### Fix 2: node_collect_final_booking_data instruction ändern (P0)

**Current** (WRONG):
```
"Ihr Termin ist gebucht..."
```

**Should be**:
```
"SAMMLE Telefon/Email falls gewünscht.
SAGE NICHTS über 'ist gebucht' - das kommt erst NACH start_booking!
Sobald Daten vollständig → SILENT transition zu start_booking"
```

### Fix 3: appointment_time nach Alternative Selection updaten (P1)

**Option A**: `func_select_alternative` sollte `appointment_time` direkt setzen

**Option B**: Backend verwendet `selected_alternative_time` wenn vorhanden (preferred)

---

## Backend Investigation Required

### Warum schlägt start_booking fehl?

**Backend sieht**:
```json
{
  "service": "Herrenhaarschnitt",     ← String name
  "function_name": "start_booking",   ← Extra param (FALSCH!)
  "datetime": "2025-11-11 09:45",     ← Korrekt
  "customer_name": "Hans Schuster",   ← Korrekt
  "call_id": "1"                      ← Hardcoded (aber bekannt)
}
```

**Backend Query**:
```sql
SELECT * FROM services WHERE id = 438 AND company_id = 1
→ Service FOUND!
```

**Dann returnt**: "Service nicht verfügbar"

**Mögliche Ursachen**:
1. Backend erwartet `service_id` (int) statt `service` (string)
2. Der `function_name` Parameter verwirrt die Service Lookup Logik
3. Ein Check schlägt fehl der nicht geloggt wird

**Code zu überprüfen**:
```php
// RetellFunctionCallHandler.php
public function startBooking(array $params, ?string $callId)
{
    // Was passiert hier genau?
    // Wo ist "Service nicht verfügbar" definiert?
}
```

---

## Was BESSER wurde (V110.3 → V110.4)

| Issue | V110.3 | V110.4 | Status |
|-------|--------|--------|--------|
| customer_name gespeichert | ❌ Blieb leer | ✅ Gespeichert | FIXED |
| Verfügbarkeits-Spekulation | ❌ "ist frei" vor check | ✅ "prüfe..." | FIXED |
| Direct transition | ❌ Via conversation node | ✅ Direkt | FIXED |
| function_name param | ❌ Im mapping | ❌ NOCH DA! | NOT FIXED |
| "ist gebucht" Lüge | ❌ Vor booking | ❌ NOCH DA! | NOT FIXED |
| appointment_time update | ❌ Nicht updated | ❌ NOCH DA! | NOT FIXED |

---

## Impact Assessment

**V110.4 vs V110.3**:
- **2 von 6 Problemen gelöst** ✅
- **4 Probleme verbleiben** ❌
- **Progress**: 33%
- **Status**: Besser aber noch nicht produktionsreif

---

## Next Steps

1. **Fix V110.5 mit korrekten Changes**:
   - Entferne `function_name` richtig
   - Fixe "ist gebucht" Lüge
   - Update appointment_time

2. **Backend Investigation**:
   - Warum schlägt start_booking fehl trotz gefundenem Service?
   - Was macht `function_name` Parameter im Backend?

3. **Test-Oberfläche erstellen**:
   - Direktes Testen der Backend APIs
   - Fehlermeldungen sehen
   - Kompletten Flow testen

---

**Status**: ❌ V110.4 hat kritische Bugs
**Recommendation**: V110.5 mit vollständigen Fixes
**Priority**: P0 - Buchungen funktionieren nicht

---

**Call ID**: call_d28fc14dc436b8641573afad343
**Analysis Time**: 2025-11-10, 15:45 Uhr
