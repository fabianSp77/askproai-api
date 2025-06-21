# Universeller Multi-Tenant Telefon-zu-Termin Test Guide

## Übersicht

Dieses Dokument beschreibt, wie das universelle Buchungssystem für verschiedene Branchen und Multi-Location-Szenarien getestet werden kann.

## Systemarchitektur

### Kernkomponenten

1. **PhoneNumberResolver** - Löst Telefonnummern zu Branches/Companies auf
2. **HotlineRouter** - Verwaltet Hotline-Anrufe mit Standortauswahl
3. **StaffSkillMatcher** - Findet passende Mitarbeiter basierend auf Skills
4. **AlternativeSlotFinder** - Schlägt alternative Termine vor
5. **UniversalBookingOrchestrator** - Koordiniert den gesamten Buchungsflow

### Datenmodell-Erweiterungen

- **phone_numbers** - Mapping von Telefonnummern zu Branches/Hotlines
- **staff.skills/languages/certifications** - Erweiterte Mitarbeiter-Eigenschaften
- **customers.preferred_branch_id/staff_id** - Kundenpräferenzen
- **branches.coordinates/features** - Multi-Location Features

## Test-Szenarien

### 1. Friseur (Salon Demo GmbH)

**Szenario**: Einzelstandort mit spezialisierten Mitarbeitern

**Test-Daten**:
- Company: Salon Demo GmbH
- Branch: Berlin Mitte
- Telefon: +49 30 98765432 (Direktwahl)
- Mitarbeiter:
  - Anna Schmidt: Damenhaarschnitt, Extensions, Färben (DE, EN)
  - Max Weber: Herrenhaarschnitt (DE, EN, TR)
  - Lisa Müller: Damenhaarschnitt, Färben (DE, FR)

**Test-Flow**:
```
1. Anruf an +49 30 98765432
2. "Ich möchte einen Termin für Damenhaarschnitt"
3. System prüft verfügbare Mitarbeiter (Anna oder Lisa)
4. Bei Zeitkonflikt: Alternative Zeiten oder anderer Mitarbeiter
5. Buchungsbestätigung
```

**Erwartetes Verhalten**:
- Nur Anna oder Lisa werden für Damenhaarschnitt vorgeschlagen
- Max wird nicht vorgeschlagen (bietet Service nicht an)
- Bei Sprachpräferenz Französisch wird Lisa bevorzugt

### 2. Fitness-Studio (FitNow GmbH)

**Szenario**: Multi-Location mit Hotline

**Test-Daten**:
- Company: FitNow GmbH
- Hotline: +49 40 11223344
- Branches:
  - Hamburg City: +49 40 87654321
  - Hamburg Altona: +49 40 76543210
- Services: Probetraining, Personal Training, Ernährungsberatung

**Test-Flow A - Hotline**:
```
1. Anruf an +49 40 11223344 (Hotline)
2. "Für Hamburg City drücken Sie die 1, für Altona die 2"
3. Kunde wählt 1
4. "Ich möchte ein Probetraining vereinbaren"
5. System prüft Verfügbarkeit in Hamburg City
6. Buchungsbestätigung
```

**Test-Flow B - Direktwahl**:
```
1. Anruf an +49 40 76543210 (Altona direkt)
2. "Ich suche einen Personal Trainer der Arabisch spricht"
3. System findet Ahmed Hassan (spricht AR, DE, EN)
4. Terminvorschlag mit Ahmed
5. Buchungsbestätigung
```

**Erwartetes Verhalten**:
- Hotline führt zu Standortauswahl
- Direktwahl überspringt Standortauswahl
- Skill-Matching berücksichtigt Sprachen und Spezialisierungen

### 3. AskProAI München Test

**Szenario**: Test-Filiale für Entwicklung

**Test-Daten**:
- Branch: AskProAI München Test
- Telefon: +49 89 12345678
- Services: Demo-Termin, Beratungsgespräch, Setup, Schulung

**Test-Flow**:
```
1. Anruf an +49 89 12345678
2. "Ich möchte eine Demo vereinbaren"
3. System findet Dr. Test München
4. Terminvorschlag
5. Buchungsbestätigung
```

## API Testing

### Function Call Test

```bash
# Test availability check
curl -X POST https://api.askproai.de/api/retell/function-call \
  -H "Content-Type: application/json" \
  -H "x-retell-signature: YOUR_SIGNATURE" \
  -d '{
    "function_name": "collect_appointment_data",
    "parameters": {
      "verfuegbarkeit_pruefen": true,
      "datum": "25.06.2025",
      "uhrzeit": "14:00 Uhr",
      "dienstleistung": "Damenhaarschnitt",
      "alternative_termine_gewuenscht": true
    },
    "call": {
      "to_number": "+49 30 98765432",
      "from_number": "+49 170 1234567",
      "agent_id": "agent_salon_demo_berlin"
    }
  }'
```

### Erwartete Response

```json
{
  "success": true,
  "verfuegbar": false,
  "datum_geprueft": "25.06.2025",
  "uhrzeit_geprueft": "14:00",
  "alternative_termine": "heute um 15:00 Uhr, oder morgen um 14:00 Uhr",
  "alternative_anzahl": 2,
  "nachricht": "Der gewünschte Termin ist leider nicht verfügbar. Ich hätte folgende Alternativen: heute um 15:00 Uhr, oder morgen um 14:00 Uhr"
}
```

## Debugging

### 1. Phone Number Resolution

```php
// Test phone number resolution
$resolver = app(PhoneNumberResolver::class);
$result = $resolver->resolveFromWebhook([
    'to' => '+49 30 98765432',
    'from' => '+49 170 1234567'
]);

// Result should contain:
// - branch_id
// - company_id
// - resolution_method
// - confidence score
```

### 2. Staff Skill Matching

```php
// Test staff matching
$matcher = app(StaffSkillMatcher::class);
$branch = Branch::find('branch-id');
$staff = $matcher->findEligibleStaff($branch, [
    'service_name' => 'Personal Training',
    'language' => 'en'
]);

// Should return staff sorted by match score
```

### 3. Alternative Slot Finding

```php
// Test alternative slots
$finder = app(AlternativeSlotFinder::class);
$alternatives = $finder->findAlternatives([
    'branch_id' => 'branch-id',
    'staff_id' => 'staff-id',
    'date' => '2025-06-25',
    'time' => '14:00',
    'service_name' => 'Damenhaarschnitt',
    'allow_other_branches' => true
]);

// Should return array of alternatives with scores
```

## Monitoring

### Key Metrics

1. **Phone Resolution Success Rate**
   - Target: >95%
   - Monitor: Fallback usage

2. **Staff Match Rate**
   - Target: >90% find suitable staff
   - Monitor: No-match scenarios

3. **Alternative Acceptance Rate**
   - Target: >60% accept alternatives
   - Monitor: Most accepted alternative types

### Logs to Monitor

```bash
# Phone resolution logs
grep "Branch resolved from" storage/logs/laravel.log

# Staff matching logs
grep "Staff skill matching completed" storage/logs/laravel.log

# Alternative finding logs
grep "alternatives found for slot" storage/logs/laravel.log

# Hotline routing logs
grep "route_to_branch" storage/logs/laravel.log
```

## Common Issues

### Issue: "Could not resolve company from call data"
**Ursache**: Telefonnummer nicht in phone_numbers Tabelle
**Lösung**: 
```sql
INSERT INTO phone_numbers (company_id, branch_id, number, type, active)
VALUES (company_id, branch_id, '+49...', 'direct', 1);
```

### Issue: "No eligible staff found"
**Ursache**: Keine Mitarbeiter mit passenden Services/Skills
**Lösung**: 
- Prüfen Sie staff_services Zuordnungen
- Prüfen Sie staff.active und staff.is_bookable

### Issue: "No alternatives found"
**Ursache**: Keine verfügbaren Slots in Suchzeitraum
**Lösung**:
- Erweitern Sie den Suchzeitraum
- Aktivieren Sie allow_other_branches

## Best Practices

1. **Phone Number Setup**
   - Immer mit Ländercode (+49)
   - Eindeutige Nummern pro Branch
   - Hotline für Multi-Location

2. **Staff Configuration**
   - Skills vollständig pflegen
   - Sprachen angeben
   - Experience Level setzen

3. **Service Mapping**
   - Services eindeutig benennen
   - Staff-Service-Zuordnungen aktuell halten
   - Dauer realistisch setzen

4. **Testing**
   - Verschiedene Tageszeiten testen
   - Verschiedene Sprachen testen
   - Edge Cases (keine Verfügbarkeit) testen

## Retell.ai Prompt Anpassung

Der Prompt muss folgende Variablen unterstützen:

```
{{company_name}} - Firmenname
{{branch_name}} - Filialname (bei Multi-Location)
{{services}} - Verfügbare Services
{{business_hours}} - Öffnungszeiten
```

Beispiel-Prompt-Struktur:
```
Du bist der KI-Assistent von {{company_name}}.
[Bei Multi-Location: Du nimmst Anrufe für {{branch_name}} entgegen.]

Verfügbare Services:
{{services}}

Öffnungszeiten:
{{business_hours}}

Bei der Terminbuchung:
1. Erfrage Service-Wunsch
2. Erfrage Datum und Uhrzeit
3. Prüfe Verfügbarkeit mit function call
4. Biete Alternativen bei Bedarf
5. Bestätige die Buchung
```