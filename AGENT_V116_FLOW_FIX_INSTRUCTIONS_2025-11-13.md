# Agent V116 - Flow Korrektur Anleitung
**Date**: 2025-11-13
**Flow ID**: conversation_flow_ec9a4cdef77e
**Agent**: Friseur 1 Agent V116 - Direct Booking Fix

---

## Problem: Premature Booking Confirmation

Der Agent sagt **"Ihr Termin ist gebucht"** BEVOR `start_booking()` aufgerufen wird.

**Testanruf Beispiel**:
```
44.9 sec: "Perfekt! Ihr Termin ist gebucht für morgen um 11 Uhr 55." ❌
54.2 sec: "Perfekt! Soll ich den Herrenhaarschnitt buchen?" ❌ (widersprüchlich!)
61.0 sec: "Ihr Termin ist jetzt fest..." ❌
71.5 sec: start_booking() FIRST CALLED ⏰
```

---

## Fix: Instructions in 4 Nodes ändern

### 1. Node: "Zeit aktualisieren" (`node_update_time`)

**VORHER** (FALSCH):
```
Aktualisiere {{appointment_time}} mit {{selected_alternative_time}}.
Wenn {{selected_alternative_date}} vorhanden: Aktualisiere auch {{appointment_date}}.

Sage: "Perfekt! Soll ich den [service_name] für [date] um [time] Uhr buchen?"

Transition zu node_collect_final_booking_data.
```

**NACHHER** (KORREKT):
```
Aktualisiere {{appointment_time}} mit {{selected_alternative_time}}.
Wenn {{selected_alternative_date}} vorhanden: Aktualisiere auch {{appointment_date}}.

WICHTIG - NIEMALS "ist gebucht" sagen!

Sage: "Perfekt! Soll ich den [service_name] für [date] um [time] Uhr dann für Sie buchen?"

VERBOTEN:
- "ist gebucht"
- "Termin gebucht"
- "ist fest"

Transition zu node_collect_final_booking_data.
```

---

### 2. Node: "Finale Buchungsdaten sammeln" (`node_collect_final_booking_data`)

**VORHER**:
```
SAMMLE FEHLENDE PFLICHTDATEN:

Pflicht für Buchung:
- customer_name

Optional (Fallback erlaubt):
- customer_phone (Fallback: '0151123456')
- customer_email (Fallback: 'termin@askproai.de')

LOGIK:
1. Prüfe was bereits aus check_customer vorhanden:
   - {{customer_name}} gefüllt → NICHT fragen
   - {{customer_phone}} gefüllt → NICHT fragen

2. Bei Neukunde:
   "Darf ich noch Ihren Namen erfragen?"

3. Telefon/Email OPTIONAL:
   "Möchten Sie eine Telefonnummer angeben?" → nur fragen wenn explizit gewünscht

REGELN:
- KEINE wiederholten Fragen
- Sobald customer_name vorhanden → zu func_start_booking
```

**NACHHER** (KORREKT - mit Anti-Speculation):
```
SAMMLE FEHLENDE PFLICHTDATEN:

Pflicht für Buchung:
- customer_name

Optional (Fallback erlaubt):
- customer_phone (Fallback: '0151123456')
- customer_email (Fallback: 'termin@askproai.de')

LOGIK:
1. Prüfe was bereits aus check_customer vorhanden:
   - {{customer_name}} gefüllt → NICHT fragen
   - {{customer_phone}} gefüllt → NICHT fragen

2. Bei Neukunde:
   "Darf ich noch Ihren Namen erfragen?"

3. Telefon/Email OPTIONAL:
   "Möchten Sie eine Telefonnummer angeben?" → nur fragen wenn explizit gewünscht

REGELN:
- KEINE wiederholten Fragen
- Sobald customer_name vorhanden → SOFORT zu func_start_booking
- NIEMALS sagen "ist gebucht" oder "Termin fest"
- NUR sagen: "Einen Moment, ich buche das für Sie..."

KRITISCH - VERBOTEN:
- "Ihr Termin ist gebucht"
- "Termin ist fest"
- "Termin ist bestätigt"
- Jede Formulierung die impliziert die Buchung ist bereits erfolgt!

NUR ERLAUBT:
- "Ich buche jetzt für Sie"
- "Einen Moment, ich erstelle die Buchung"
- "Perfekt, ich kümmere mich darum"
```

---

### 3. Node: "Ergebnis zeigen" (`node_present_result`)

**Instruction genau prüfen**:

Dieser Node sollte NUR die Verfügbarkeit zeigen, NICHT "gebucht" sagen!

**KORREKT**:
```
Zeige das Ergebnis:

**FALL 1: Verfügbar (available:true):**
"Perfekt! Ihr Wunschtermin am {{appointment_date}} um {{appointment_time}} ist frei. Soll ich den [service_name] für Sie buchen?"

**NIEMALS in diesem Node:**
- "ist gebucht"
- "Termin ist fest"

Dieser Node fragt nur OB gebucht werden soll, sagt aber NICHT dass es gebucht IST!

**FALL 2: Nicht verfügbar mit Alternativen:**
Siehe node_present_alternatives

**FALL 3: Keine Alternativen:**
Siehe node_no_availability
```

---

### 4. Node: "Buchung erfolgreich" (`node_booking_success`)

**Diese Node ist KORREKT** - hier ist "ist gebucht" ERLAUBT:

```
Ihr Termin ist gebucht für {{appointment_date}} um {{appointment_time}} Uhr.
```

Dies ist der EINZIGE Ort wo "ist gebucht" gesagt werden darf!

---

## Retell Dashboard Anleitung

### Schritt 1: Flow Editor öffnen
1. Login: https://beta.retellai.com/dashboard
2. Navigate to: **Agents** → **Friseur 1 Agent V116 - Direct Booking Fix**
3. Click: **Edit Response Engine**
4. Flow ID: `conversation_flow_ec9a4cdef77e`

### Schritt 2: Node Instructions aktualisieren

Für jeden der 4 Nodes:

1. **Suche Node** im Flow Editor (verwende Ctrl+F oder suche visuell)
2. **Klicke auf Node** um Details zu öffnen
3. **Instruction Tab** öffnen
4. **Text ersetzen** mit der NACHHER-Version oben
5. **Save** klicken

**Nodes zum Updaten**:
- `node_update_time` → "Zeit aktualisieren"
- `node_collect_final_booking_data` → "Finale Buchungsdaten sammeln"
- `node_present_result` → "Ergebnis zeigen" (prüfen!)
- `node_booking_success` → "Buchung erfolgreich" (NUR prüfen, sollte korrekt sein)

### Schritt 3: Flow Publishen

1. Click: **Publish Flow** (oben rechts)
2. Confirm: **Yes, Publish**
3. Warte 30 Sekunden bis Agent V116 die neue Flow-Version geladen hat

---

## Validation Checklist

Nach der Änderung, **BEVOR** du den nächsten Testanruf machst:

- [ ] `node_update_time` enthält **"NIEMALS 'ist gebucht' sagen!"**
- [ ] `node_collect_final_booking_data` enthält **"VERBOTEN: ist gebucht"**
- [ ] `node_present_result` sagt **nur "ist frei"**, NICHT "ist gebucht"
- [ ] `node_booking_success` ist der EINZIGE Node mit "ist gebucht"
- [ ] Flow gepublished und Agent refreshed

---

## Test Plan

Nach den Änderungen:

1. **Warte 1 Minute** (Agent muss neue Flow laden)
2. **Testanruf machen** zu +493033081738
3. **Sage**: "Hans Müller, Herrenhaarschnitt morgen um 10 Uhr"
4. **Erwarte**:
   - Agent sagt "nicht frei, aber ich kann..." (Alternativen)
   - User wählt Alternative: "11 Uhr 55"
   - Agent sagt: "Soll ich buchen?" ✅ (NICHT "ist gebucht")
   - User bestätigt: "Ja bitte"
   - Agent sagt: "Einen Moment..." 🔄 (start_booking wird aufgerufen)
   - Agent sagt: "Ihr Termin ist gebucht..." ✅ (NACH booking success)

5. **Check Database**:
   ```bash
   php artisan tinker --execute="
   \$lastCall = \\App\\Models\\Call::orderBy('created_at', 'desc')->first();
   \$appts = \\App\\Models\\Appointment::where('call_id', \$lastCall->id)->get();
   echo 'Appointments: ' . \$appts->count();
   "
   ```
   Expected: `Appointments: 1` ✅

---

## Troubleshooting

### Problem: Agent sagt immer noch "ist gebucht" zu früh

**Check**:
1. Wurde Flow wirklich gepublished? (Rechts oben sollte "Published" stehen)
2. Warte 2 Minuten und versuche erneut
3. Clear Agent Cache:
   ```bash
   # Retell Dashboard → Agent → Advanced → Clear Cache
   ```

### Problem: Agent fragt gar nicht mehr ob er buchen soll

**Check**:
- `node_update_time` und `node_collect_final_booking_data` sollten beide FRAGEN "Soll ich buchen?"
- Edge conditions prüfen: Transitions sollten korrekt sein

### Problem: Agent stuck in loop

**Check**:
- Alle Edges haben korrekte `transition_condition`
- `node_collect_final_booking_data` → `func_start_booking` Edge hat equation:
  ```json
  {
    "left": "customer_name",
    "operator": "exists"
  }
  ```

---

## Code Fixes (BEREITS IMPLEMENTIERT) ✅

Die Backend-Fixes sind bereits deployed:

### ✅ Fix 1: Title Field
**File**: `app/Services/CalcomService.php:138-145`
```php
// Title wird jetzt direkt im payload gesetzt
if (isset($bookingDetails['title'])) {
    $payload['title'] = $bookingDetails['title'];
} elseif (isset($bookingDetails['service_name'])) {
    $payload['title'] = $bookingDetails['service_name'];
}
```

### ✅ Fix 2: Optimistic Locking
**File**: `app/Http/Controllers/RetellFunctionCallHandler.php:1324-1386`
```php
// Retry logic bei Race Condition
$maxRetries = 1;
while ($attempt <= $maxRetries && !$booking) {
    try {
        $booking = $this->calcomService->createBooking([...]);
        break;
    } catch (\App\Exceptions\CalcomApiException $e) {
        if (str_contains($e->getMessage(), 'already has booking')) {
            // Retry once
            continue;
        }
        throw $e;
    }
}
```

### ✅ Fix 3: Explicit Title in Function Call
**File**: `app/Http/Controllers/RetellFunctionCallHandler.php:1345`
```php
'title' => $service->name . ' - ' . $customerName,
```

---

## Status

- **Backend Code**: ✅ Fixed and Deployed
- **Flow Instructions**: ⏳ **AWAITING YOUR MANUAL UPDATE**
- **Testing**: ⏳ Pending after Flow fix

**Next Step**: Update die 4 Node Instructions im Retell Dashboard wie oben beschrieben!

---

**Last Updated**: 2025-11-13 15:30 CET
**Author**: Claude Code
**Related**: `AGENT_V116_TEST_CALL_RCA_2025-11-13.md`
