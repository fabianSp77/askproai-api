# RCA: Gateway Mode Fehlrouting + Date Halluzination

**Date**: 2025-12-21
**Severity**: HIGH - Terminbuchung fehlgeschlagen
**Call ID**: `call_0e60dafbe710fc036821008a7eb`
**Agent**: Friseur 1 – Terminassistent v6.2.5 (Date Context + Low Latency)
**Phone**: +493033081738

---

## Executive Summary

Testanruf vom 21.12.2025 10:32:55 UTC identifizierte **zwei kritische Fehler**:

| Issue | Problem | Status |
|-------|---------|--------|
| **Gateway Mode** | `get_alternatives` → "Unknown function" | ✅ BEHOBEN |
| **Date Halluzination** | Agent sendet `2023-06-13` statt `2025-12-23` | ✅ BEHOBEN |
| **TimePreference Class** | `Class "App\ValueObjects\TimePreference" not found` | ✅ BEHOBEN |

**User Impact**: Kunde konnte keinen Termin buchen. Agent sagte "mein Kalender hängt gerade" nach jedem Versuch.

---

## Issue 1: Gateway Mode Fehlrouting

### Symptom

```
Agent: "Entschuldigung, mein Kalender hängt gerade. Ich versuche es sofort noch einmal."
```

Function Call Log:
```json
{
  "name": "check_availability_v17",
  "arguments": {"datum": "2023-06-13", "dienstleistung": "Herrenhaarschnitt"},
  "result": {"success": false, "error": "Fehler beim Parsen des Datums"}
}
```

### Root Cause

**PolicyConfiguration ID 19** für Company "Friseur 1" hatte falschen Gateway-Mode:

```sql
-- Falsche Konfiguration
SELECT id, config FROM policy_configurations WHERE id = 19;
-- Ergebnis: {"mode": "service_desk"}  ← FALSCH!
```

**Code Flow:**
```
RetellFunctionCallHandler.php:802
  → GatewayModeResolver::resolve()
    → PolicyConfiguration::getCachedPolicy()
      → config['mode'] = 'service_desk'
  → ServiceDeskHandler::handle()
    → match($functionName)
      → default: handleUnknownFunction() → 400 "Unknown function"
```

**ServiceDeskHandler** kennt nur 5 Funktionen:
- `collect_issue_details`
- `categorize_request`
- `route_ticket`
- `finalize_ticket`
- `detect_intent`

`get_alternatives` und `check_availability_v17` sind **NICHT** in ServiceDeskHandler → "Unknown function"

### Fix Applied

```php
// Lösung in Laravel Tinker
\App\Models\PolicyConfiguration::find(19)->update([
    'config' => ['mode' => 'appointment']
]);

// Cache leeren
php artisan cache:clear && php artisan config:clear
```

### Verification

```php
$resolver = app(\App\Services\Gateway\GatewayModeResolver::class);
$mode = $resolver->resolve($call);
// Ergebnis: 'appointment' ✅
```

### Status: ✅ BEHOBEN

---

## Issue 2: Date Halluzination

### Symptom

**Kunde sagte:**
> "Herrenhaarschnitt, haben Sie nächste Woche Dienstagvormittag oder Mittwochvormittag ab neun Uhr noch einen Termin frei?"

**Agent sendete:**
```json
{
  "name": "check_availability_v17",
  "arguments": {
    "datum": "2023-06-13",      // ← 2.5 Jahre in der VERGANGENHEIT!
    "dienstleistung": "Herrenhaarschnitt",
    "uhrzeit": "vormittags ab 9 Uhr"
  }
}
```

**Erwartetes Datum:** `2025-12-23` (nächster Dienstag ab 21.12.2025)

### Root Cause Chain

```
1. Phone Number (+493033081738) hat KEINEN inbound_webhook_url
   ↓
2. call_inbound Event wird NIE an Backend gesendet
   ↓
3. buildInboundResponseWithDateContext() wird NICHT aufgerufen
   ↓
4. dynamic_variables (heute_datum, morgen, etc.) werden NIE injiziert
   ↓
5. Agent-Prompt hat {{heute_datum}} Template aber Wert = UNDEFINED
   ↓
6. LLM halluziniert Daten ohne Zeitreferenz → "13. Juni 2023"
```

### Evidence

**retell_llm_dynamic_variables aus Call:**
```json
{
  "lk-call-info": "Cg4zNS4xNTYuMTkxLjEyORDEJxgB",
  "twilio-accountsid": "AC008891bccf7c7e2f363eba6ae63d3f00",
  "twilio-callsid": "CA8d5e0b99537b533a07047cc5d605088f"
}
```

**FEHLEND:** `heute_datum`, `current_date`, `current_weekday`, `morgen`, `naechster_montag`

### Agent Configuration Analysis

**global_prompt** ist KORREKT konfiguriert:
```
DATUM & ZEIT (Source of Truth): Heute {{heute_datum}} ({{current_weekday}}).
Morgen {{morgen}}. Nächster Montag {{naechster_montag}}.
```

**node_extract_booking_variables** referenziert korrekt:
```
Datum-Regeln (Source of Truth):
- „heute" => {{heute_datum}}
- „morgen" => {{morgen}}
- Wochentag (Mo–So) => nächster passender Tag ab {{heute_datum}}.
```

**Problem:** Die Template-Variablen werden nie mit Werten substituiert!

### Backend Code (vorhanden aber nicht ausgeführt)

**RetellWebhookController.php:1786-1823:**
```php
private function buildInboundResponseWithDateContext(?string $agentId, ?string $fromNumber): Response
{
    $now = Carbon::now('Europe/Berlin');

    $dynamicVariables = [
        'heute_datum' => $now->format('d.m.Y'),           // "21.12.2025"
        'current_date' => $now->format('Y-m-d'),          // "2025-12-21"
        'current_weekday' => $now->locale('de')->dayName, // "Samstag"
        'naechster_montag' => $now->copy()->next(Carbon::MONDAY)->format('d.m.Y'),
        'morgen' => $now->copy()->addDay()->format('d.m.Y'),
    ];

    return response()->json([
        'dynamic_variables' => $dynamicVariables,
        'override_agent_id' => $agentId,
    ]);
}
```

Diese Methode wird bei `call_inbound` Event aufgerufen (Zeile 239), aber das Event kommt nie!

### Fix Required (Retell Dashboard)

```
1. Login: https://dashboard.retellai.com

2. Navigate: Phone Numbers → +493033081738 → Edit

3. Configure:
   ☐ Inbound Agent ID: agent_0733f6759ff813188e68258d8c
   ☑ Inbound Webhook URL: https://api.askproai.de/api/webhooks/retell

4. Save & Test
```

### Verification Steps

Nach Dashboard-Fix:
1. Testanruf durchführen
2. Laravel Logs prüfen:
   ```bash
   grep "Building inbound response with temporal context" storage/logs/laravel.log
   ```
3. Prüfen ob `heute_datum` in Call-Daten erscheint
4. Relative Datums-Anfrage testen: "nächste Woche Montag"

### Fix Applied (Dashboard)

Inbound Webhook URL wurde im Retell Dashboard konfiguriert:
```
Phone Number: +493033081738
Inbound Webhook URL: https://api.askproai.de/api/webhooks/retell
```

### Verification (Call 12:23:43)

Nach dem Fix zeigt der Testanruf `call_a4cdc3106bf63117c98dcf0df55`:
```json
{
  "retell_llm_dynamic_variables": {
    "heute_datum": "21.12.2025",      // ✅ KORREKT INJIZIERT
    "current_date": "2025-12-21",
    "current_weekday": "Sonntag",
    "morgen": "22.12.2025",
    "naechster_montag": "22.12.2025",
    "uebermorgen": "23.12.2025"
  }
}
```

Agent interpretiert Datum korrekt:
```json
{
  "collected_dynamic_variables": {
    "appointment_date": "Dienstag, 23. Dezember",  // ✅ KORREKT
    "service_name": "Herrenhaarschnitt"
  },
  "function_arguments": {
    "datum": "2025-12-23"  // ✅ KORREKT (statt "2023-06-13")
  }
}
```

### Status: ✅ BEHOBEN

---

## Issue 3: TimePreference Class Not Found

### Symptom

Nach dem Date Halluzination Fix trat ein neuer Fehler auf:
```
Error: Class "App\ValueObjects\TimePreference" not found
File: /var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php:148
```

### Root Cause

Die `TimePreference` Value Object Klasse wurde referenziert, existierte aber nicht im Codebase.

### Fix Applied

1. **TimePreference Klasse erstellt:**
   ```
   app/ValueObjects/TimePreference.php
   ```

2. **Composer Autoload aktualisiert:**
   ```bash
   composer dump-autoload
   php artisan cache:clear && php artisan config:clear
   ```

### Verification

```bash
php artisan tinker --execute="echo \App\ValueObjects\TimePreference::vormittag()->getGermanLabel();"
# Output: vormittags  ✅
```

### Status: ✅ BEHOBEN

---

## Code References

| File | Line | Purpose |
|------|------|---------|
| `app/Services/Gateway/GatewayModeResolver.php` | 28-45 | Gateway mode resolution |
| `app/Http/Controllers/RetellFunctionCallHandler.php` | 802-867 | Function routing logic |
| `app/Http/Controllers/ServiceDeskHandler.php` | 59-75 | Service desk function matching |
| `app/Http/Controllers/RetellWebhookController.php` | 235-239 | call_inbound handling |
| `app/Http/Controllers/RetellWebhookController.php` | 1786-1823 | Date context injection |
| `app/ValueObjects/TimePreference.php` | 1-312 | Time preference value object |
| `app/Services/AppointmentAlternativeFinder.php` | 148 | Uses TimePreference for slot filtering |

---

## Prevention

### For Issue 1 (Gateway Mode)
- Add validation in PolicyConfiguration to ensure `mode` is one of: `appointment`, `service_desk`, `hybrid`
- Add logging when gateway mode changes

### For Issue 2 (Date Halluzination)
- Add health check for phone number webhook configuration
- Create alert when `dynamic_variables` are empty in call data
- Add fallback: If `{{heute_datum}}` is empty, use API-based date lookup

---

## Verification Checklist

- [x] PolicyConfiguration ID 19 mode = 'appointment'
- [x] Cache cleared after policy update
- [x] Phone Number has inbound_webhook_url configured in Retell
- [x] call_inbound events appear in Laravel logs
- [x] dynamic_variables contain heute_datum, morgen, etc.
- [x] Test call with "Dienstag" → correct date "2025-12-23" sent to API
- [x] TimePreference class created and autoloaded
- [ ] Final test call to verify complete booking flow

---

## Related Documentation

- `claudedocs/RETELL_DATE_HALLUCINATION_FIX_2025-12-14.md` - Original fix documentation
- `app/Models/PolicyConfiguration.php` - Policy model
- `config/gateway.php` - Gateway configuration

---

**Created**: 2025-12-21
**Last Updated**: 2025-12-21 15:40
**Author**: Claude Code Analysis
**Resolution Status**: 3/3 Issues RESOLVED - Ready for final test call
