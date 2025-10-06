# Retell Agent Prompt - Fallback Instruktionen

## 🎯 Problem & Lösung

**Problem:** Agent sagt "Ich kann keinen Termin anbieten" wenn Cal.com keine Slots zurückgibt

**Lösung:** Fallback-System generiert IMMER Alternativen → Agent muss diese vorlesen können

## ✅ Fixes Implementiert (2025-09-30)

### 1. **Service Duration Fix**
- Service ID 47 hatte `duration = NULL` → "Invalid event length" Fehler
- **Fix:** `UPDATE services SET duration = 30 WHERE id = 47`

### 2. **Fallback-Response Logik** (`RetellFunctionCallHandler.php:1040-1063`)
```php
// Wenn Cal.com keine Alternativen liefert:
if (empty($alternatives['alternatives'])) {
    // Generiere intelligente Fallback-Alternativen
    $fallbackAlternatives = $this->alternativeFinder->findAlternatives(...);

    // Wenn auch Fallback fehlschlägt, verwende freundliche Standard-Nachricht
    if (empty($fallbackAlternatives['alternatives'])) {
        $message = "... Ich kann Ihnen aber gerne alternative Termine vorschlagen...";
    }
}
```

### 3. **Voice-Optimierte Response-Formate** (`AppointmentAlternativeFinder.php`)
- **Vorher:** "1. Montag 10:00 Uhr\n2. Dienstag 14:00 Uhr"
- **Nachher:** "Montag 10:00 Uhr oder Dienstag 14:00 Uhr"
- Keine Zeilenumbrüche → natürlicher für Sprachausgabe

## 📝 Empfohlene Retell Agent Prompt Erweiterung

Füge diese Instruktionen zum Retell Agent Prompt hinzu:

```
## TERMINBUCHUNG - UMGANG MIT NICHT VERFÜGBAREN TERMINEN

1. **Wenn der gewünschte Termin NICHT verfügbar ist:**
   - Lies die Alternativen-Nachricht VOLLSTÄNDIG vor (enthält bereits alle Infos)
   - Die Nachricht endet IMMER mit einer Frage → warte auf Kundenantwort
   - Biete NIEMALS an "einen anderen Zeitpunkt zu finden" - die Alternativen sind BEREITS da

2. **Beispiel-Dialog:**

   Kunde: "Ich möchte Termin am 1.10. um 9 Uhr"

   System-Response: {
     "message": "Der Termin am 01.10.2025 um 09:00 ist leider nicht verfügbar.
                 Ich kann Ihnen folgende Alternativen anbieten: am gleichen Tag,
                 11:00 Uhr oder Montag, 02.10. um 09:00 Uhr.
                 Welcher Termin würde Ihnen besser passen?"
   }

   Du: [LIES DIE MESSAGE EXAKT VOR]

   Kunde: "Montag 9 Uhr passt"

   Du: "Perfekt, ich buche den Termin am Montag, 02.10. um 9 Uhr für Sie..."

3. **WICHTIG - NIEMALS sagen:**
   ❌ "Ich schaue mal nach anderen Terminen"
   ❌ "Möchten Sie einen anderen Tag?"
   ❌ "Ich kann leider keinen Termin anbieten"

   ✅ STATTDESSEN: Lies die System-Message vor (enthält bereits Alternativen)

4. **Wenn KEINE Alternativen vorhanden sind:**
   - System-Message enthält dann: "Möchten Sie es zu einem späteren Zeitpunkt versuchen?"
   - Biete an, Kundendaten aufzunehmen für Rückruf
   - Oder frage nach komplett anderem Zeitraum (z.B. nächste Woche)
```

## 🧪 Test-Szenarien

### Szenario 1: Cal.com hat Slots → Fallback Alternativen
**Input:** Kunde möchte Termin am 01.10. um 16:00
**Cal.com:** Keine Slots verfügbar
**System:** Generiert Fallback (15:00, 17:30 am gleichen Tag)
**Agent:** Liest Fallback-Alternativen vor

### Szenario 2: Auch Fallback findet nichts
**Input:** Kunde möchte Termin an Weihnachten
**Cal.com:** Keine Slots
**Fallback:** Auch keine sinnvollen Alternativen
**System:** Standard-Message "... alternative Termine vorschlagen ..."
**Agent:** Bietet flexible Lösung an (Rückruf, anderer Zeitraum)

## 🔧 Cal.com Event Type Konfiguration

**KRITISCH:** Event Type `2563193` muss in Cal.com richtig konfiguriert sein:

1. **Host zuweisen:** Mindestens ein User muss als Host assigned sein
2. **Availability setzen:** Working Hours definieren (z.B. Mo-Fr 9-18)
3. **Duration:** Muss mit Service Duration übereinstimmen (30 Min)
4. **Testen:** `GET /api/v2/slots/available?eventTypeId=2563193&startTime=2025-10-01&endTime=2025-10-01`

**Fehler ohne korrekte Konfiguration:**
- `fixed_hosts_unavailable_for_booking`
- `no_available_users_found_error`
- `Invalid event length`

## 📊 Monitoring

**Log-Einträge beachten:**
```bash
tail -f /var/www/api-gateway/storage/logs/calcom-*.log | grep "slots_count"
```

**Erwartete Logs:**
- ✅ `slots_count > 0` → Cal.com funktioniert
- ⚠️ `slots_count = 0` → Fallback wird aktiviert
- ✅ `No Cal.com alternatives found, generating fallback suggestions`

## ✅ Erfolgs-Kriterien

Der Fix ist erfolgreich wenn:
1. ✅ Agent sagt NIEMALS mehr "Ich kann keinen Termin anbieten"
2. ✅ Agent bietet IMMER Alternativen oder flexible Lösungen an
3. ✅ Keine "Invalid event length" Fehler mehr in Logs
4. ✅ Kunden bekommen immer eine hilfreiche Antwort
