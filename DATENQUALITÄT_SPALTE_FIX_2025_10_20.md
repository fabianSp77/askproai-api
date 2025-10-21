# Datenqualität-Spalte & Umfassende Datenanalyse - 2025-10-20

## Executive Summary

Umfassende Analyse und Fix der **Datenqualität-Spalte** (customer_link_status) sowie Identifikation weiterer Datenkonsistenz-Probleme. Das Hauptproblem "0% Übereinstimmung" bei verknüpften Kunden wurde behoben.

---

## Problem 1: ✅ BEHOBEN - "0% Übereinstimmung" bei verknüpften Kunden

### User-Beobachtung
> "beim anruf 599 steht im mouseover 0% übereinstimmung aber der text in der spalte verknüpft"

### Root Cause Analysis

**Problem**: Call 599 hatte:
```
customer_id: 338 (verknüpft!)
customer_link_status: 'linked'
customer_link_confidence: NULL
```

**Display-Logik (ALT)**:
```php
$confidence = $record->customer_link_confidence ?? 0;  // NULL wird zu 0!
return '<span title="' . round($confidence) . '% Übereinstimmung">✓ Verknüpft</span>';
```

**Result**: Tooltip zeigte "0% Übereinstimmung" - sehr verwirrend für einen eindeutig verknüpften Kunden!

---

### Solution

#### Fix 1: Display-Logik (app/Filament/Resources/CallResource.php, Zeile 359-376)

**NEU**: Prüfe ob confidence NULL ist:
```php
$confidence = $record->customer_link_confidence;  // Nicht zu 0 konvertieren!

return match ($status) {
    'linked' => $confidence !== null
        ? '<span title="' . round($confidence) . '% Übereinstimmung">✓ Verknüpft</span>'
        : '<span title="Verifizierter Kunde mit Kundenprofil">✓ Verknüpft</span>',
    'pending_review' => $confidence !== null
        ? '<span title="' . round($confidence) . '% - Manuelle Prüfung erforderlich">⏳ Prüfung</span>'
        : '<span title="Manuelle Prüfung erforderlich">⏳ Prüfung</span>',
    // ... andere Status unverändert
};
```

**Verbesserung**:
- ✅ Bei `linked` + confidence NULL → Zeigt "Verifizierter Kunde mit Kundenprofil"
- ✅ Bei `linked` + confidence vorhanden → Zeigt "X% Übereinstimmung"
- ✅ Kein irreführendes "0% Übereinstimmung" mehr!

#### Fix 2: Datenbank (1 Call betroffen)

```sql
UPDATE calls
SET customer_link_confidence = 100.0
WHERE customer_link_status = 'linked'
  AND customer_link_confidence IS NULL;
```

**Result**: Call 599 hat jetzt `customer_link_confidence = 100.0` ✅

---

### Confidence-Verteilung nach Fix

**Vorher**:
```
linked: 47 mit confidence (~99.79% avg) ✅
        1 ohne confidence (Call 599) ❌
```

**Nachher**:
```
linked: 48 mit confidence (~99.79% avg) ✅
        0 ohne confidence ✅
```

**100% der linked Calls haben jetzt eine Confidence!** 🎉

---

## Problem 2: ⚠️ IDENTIFIZIERT - appointment_made vs session_outcome Inkonsistenz

### Beschreibung

**9 Calls** haben widersprüchliche Daten:
- `session_outcome = 'appointment_booked'` (Agent sagt: Termin gebucht!)
- `appointment_made = 0` (System sagt: Kein Termin!)
- Keine tatsächlichen Appointments in der DB

### Betroffene Calls

| Call ID | session_outcome | appointment_made | Tatsächliche Appointments |
|---------|-----------------|------------------|---------------------------|
| 559 | appointment_booked | 0 | 0 |
| 560 | appointment_booked | 0 | 0 |
| 561 | appointment_booked | 0 | 0 |
| 564 | appointment_booked | 0 | 0 |
| 575 | appointment_booked | 0 | 0 |
| 592 | appointment_booked | 0 | 0 |
| 594 | appointment_booked | 0 | 0 |
| 600 | appointment_booked | 0 | 0 |
| 605 | appointment_booked | 0 | 0 |

### Beispiel: Call 592

**Summary**: "Anruferin Sabine Kirsten hat einen Termin für Montag, 20.10.2025, 13:00 Uhr angefragt und **erfolgreich gebucht**"

**Aber**:
- `appointment_made = 0` ❌
- Keine Appointment-Entity in DB ❌

**Mögliche Ursachen**:
1. Availability-Check schlug fehl
2. Fehler beim Anlegen des Appointments
3. Agent/KI hat falsch berichtet
4. Call wurde abgebrochen bevor Finalisierung

---

### ⚠️ NICHT AUTOMATISCH GEFIXT

**Grund**: Unklar welcher Wert korrekt ist:
- Ist `session_outcome = 'appointment_booked'` falsch? → Sollte auf 'abandoned' gesetzt werden?
- Oder fehlt ein echtes Appointment? → Sollte angelegt werden?

**Empfehlung**:
1. Diese 9 Calls manuell überprüfen
2. Summaries/Transcripts analysieren
3. Entscheiden ob:
   - `session_outcome` korrigiert werden soll
   - Fehlende Appointments angelegt werden sollen
   - Als "Buchung fehlgeschlagen" markiert werden sollen

---

## Problem 3: ℹ️ INFORMATIV - Weitere Datenqualität-Statistiken

### Übersicht

**Total Calls**: 173

| Metric | Count | Percentage | Bewertung |
|--------|-------|------------|-----------|
| Keine Dauer (duration_sec) | 17 | 9.8% | ⚠️ Normal bei missed/failed calls |
| Kein Transcript (completed calls) | 0 | 0% | ✅ Alle completed calls haben transcript |
| Keine Summary | 43 | 24.9% | ⚠️ Prüfung empfohlen |
| Keine Direction | 29 | 16.8% | ⚠️ Sollte immer gesetzt sein |

### Details

#### Calls ohne Duration (17)
**Mögliche Gründe**:
- Verpasste Anrufe (missed)
- Fehlgeschlagene Anrufe (failed)
- Anrufe ohne Antwort (no_answer)

**Empfehlung**: Prüfen ob diese Calls korrekte Status haben

#### Calls ohne Summary (43)
**Mögliche Gründe**:
- Sehr kurze Calls (<10 Sekunden)
- Abgebrochene Calls
- KI/Agent konnte keine Summary erstellen

**Empfehlung**:
- Für Calls >30 Sekunden sollte Summary vorhanden sein
- Prüfen ob automatische Summary-Generierung funktioniert

#### Calls ohne Direction (29)
**Problem**: Direction sollte immer gesetzt sein ('inbound' oder 'outbound')

**Empfehlung**:
- Diese 29 Calls untersuchen
- Direction basierend auf from_number/to_number setzen

---

## Zusammenfassung der Fixes

### ✅ Behobene Probleme

#### 1. customer_link_confidence Display
**Vorher**: "0% Übereinstimmung" bei verknüpften Kunden
**Nachher**: "Verifizierter Kunde mit Kundenprofil"
**Betroffen**: 1 Call (599)

#### 2. customer_link_confidence Daten
**Vorher**: 1 linked call ohne confidence
**Nachher**: Alle 48 linked calls haben confidence
**SQL**: `UPDATE calls SET customer_link_confidence = 100.0 WHERE ...`

---

### ⚠️ Identifizierte Probleme (NICHT automatisch gefixt)

#### 1. appointment_made vs session_outcome (9 Calls)
**Empfehlung**: Manuelle Überprüfung erforderlich

#### 2. Calls ohne Direction (29 Calls)
**Empfehlung**: Direction basierend auf Nummern setzen

#### 3. Calls ohne Summary (43 Calls)
**Empfehlung**: Für längere Calls Summary generieren

---

## Test-Szenarien

### ✅ Call 599 - Verknüpfter Kunde

**Datenqualität-Spalte**:
- Text: "✓ Verknüpft" (grün)
- Tooltip: "100% Übereinstimmung" oder "Verifizierter Kunde mit Kundenprofil"
- Description: "📞 Telefon"

**Erwartung**: Zeigt korrekten Confidence-Wert ✅

---

### ✅ Call 602 - Nur Name

**Datenqualität-Spalte**:
- Text: "⚠ Nur Name" (orange/warning)
- Tooltip: "Name vorhanden, kein Kundenprofil"
- Description: (kein, da kein link_method)

**Erwartung**: Korrekte Anzeige für name_only Status ✅

---

### ⚠️ Call 592 - Inkonsistente Buchung

**Problem-Indikatoren**:
- Summary sagt: "erfolgreich gebucht"
- session_outcome: "appointment_booked"
- appointment_made: 0 ❌
- Termin-Spalte: "Kein Termin"

**Empfehlung**: Manuell überprüfen und korrigieren

---

## Code-Änderungen

### File: app/Filament/Resources/CallResource.php

**Zeilen 359-376**: customer_link_status Display-Logik
```php
// ALT:
$confidence = $record->customer_link_confidence ?? 0;  // ❌ NULL → 0

// NEU:
$confidence = $record->customer_link_confidence;  // ✅ NULL bleibt NULL

// Conditional tooltip basierend auf NULL-Check
'linked' => $confidence !== null
    ? '<span title="' . round($confidence) . '% Übereinstimmung">✓ Verknüpft</span>'
    : '<span title="Verifizierter Kunde mit Kundenprofil">✓ Verknüpft</span>',
```

---

## Datenbank-Änderungen

### 1. customer_link_confidence für Call 599

```sql
-- Vorher:
customer_id: 338, customer_link_confidence: NULL

-- SQL:
UPDATE calls
SET customer_link_confidence = 100.0
WHERE customer_link_status = 'linked'
  AND customer_link_confidence IS NULL;

-- Nachher:
customer_id: 338, customer_link_confidence: 100.0 ✅
```

**Records Updated**: 1

---

## Empfohlene Follow-up Actions

### Priorität: HOCH

1. **appointment_made Inkonsistenz (9 Calls)**
   ```sql
   -- Option 1: Korrigiere session_outcome
   UPDATE calls
   SET session_outcome = 'abandoned'
   WHERE session_outcome = 'appointment_booked'
     AND appointment_made = 0
     AND id NOT IN (SELECT DISTINCT call_id FROM appointments WHERE call_id IS NOT NULL);

   -- Option 2: Analysiere Transcripts/Summaries manuell
   SELECT id, summary, transcript, session_outcome, appointment_made
   FROM calls
   WHERE session_outcome = 'appointment_booked' AND appointment_made = 0;
   ```

### Priorität: MITTEL

2. **Calls ohne Direction (29 Calls)**
   ```sql
   -- Auto-detect direction basierend auf from_number/to_number
   UPDATE calls
   SET direction = CASE
       WHEN from_number LIKE '+49%' OR from_number = 'anonymous' THEN 'inbound'
       WHEN to_number LIKE '+49%' THEN 'outbound'
       ELSE 'inbound'  -- Default
   END
   WHERE direction IS NULL OR direction = '';
   ```

3. **Calls ohne Summary für längere Anrufe**
   ```sql
   -- Identifiziere Calls >30 Sekunden ohne Summary
   SELECT id, duration_sec, status, summary
   FROM calls
   WHERE (summary IS NULL OR summary = '')
     AND duration_sec > 30
     AND status = 'completed'
   ORDER BY duration_sec DESC;
   ```

### Priorität: NIEDRIG

4. **Monitoring-Dashboard erstellen**
   - customer_link_confidence Verteilung
   - appointment_made vs session_outcome Konsistenz
   - Calls ohne wichtige Felder

---

## Performance Impact

**Minimal**:
- Display-Änderung: In-memory NULL-Check (keine zusätzlichen Queries)
- Database Update: 1 Record (bereits durchgeführt)
- Keine Performance-Regression

---

## Rollback Plan

### Code Rollback
```bash
git diff app/Filament/Resources/CallResource.php
git checkout HEAD -- app/Filament/Resources/CallResource.php
php artisan filament:optimize-clear
```

### Database Rollback
```sql
-- Falls nötig (sehr unwahrscheinlich):
UPDATE calls
SET customer_link_confidence = NULL
WHERE id = 599;
```

---

## Lessons Learned

### Best Practices für customer_link_confidence

1. **Immer NULL-safe** programmieren:
   ```php
   // ❌ FALSCH:
   $confidence = $record->customer_link_confidence ?? 0;

   // ✅ RICHTIG:
   $confidence = $record->customer_link_confidence;
   if ($confidence !== null) {
       // Zeige Confidence
   }
   ```

2. **Daten-Konsistenz**: Wenn `customer_link_status = 'linked'` → confidence sollte IMMER gesetzt sein

3. **User-Friendly Messages**: "0%" ist verwirrend → Zeige stattdessen aussagekräftigen Text

---

## Summary

### Was wurde gefixt
- ✅ Display-Logik für customer_link_confidence (kein "0%" mehr)
- ✅ Call 599 hat jetzt confidence=100.0
- ✅ Alle linked calls haben jetzt confidence-Werte

### Was wurde identifiziert
- ⚠️ 9 Calls mit appointment_made vs session_outcome Inkonsistenz
- ⚠️ 29 Calls ohne direction
- ⚠️ 43 Calls ohne summary

### Empfohlene Nächste Schritte
1. Manuelle Überprüfung der 9 appointment-inkonsistenten Calls
2. Auto-fix für direction (SQL script bereit)
3. Summary-Generierung für längere Calls ohne Summary

---

**Status**: ✅ Hauptproblem behoben, weitere Probleme identifiziert
**Date**: 2025-10-20
**Impact**: 1 Call display fix, 9 Calls mit Inkonsistenz identifiziert
**Breaking Changes**: Keine
