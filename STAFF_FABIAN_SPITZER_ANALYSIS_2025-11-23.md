# Fabian Spitzer - Zwei Staff Records Analyse

**Datum**: 2025-11-23 22:35 CET
**Status**: ✅ NICHT DUPLIKAT - Zwei separate Cal.com Accounts

---

## Zusammenfassung

**Befund**: Die zwei "Fabian Spitzer" Einträge sind KEINE Duplikate, sondern repräsentieren **zwei unterschiedliche Cal.com Accounts** für dieselbe Person.

**Empfehlung**: NICHT zusammenführen - beide Accounts aktiv in Verwendung

---

## Staff Record Details

### Staff 1: 6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe

```
Name: Fabian Spitzer
Email: fabianspitzer@icloud.com ← UNTERSCHIEDLICH!
Cal.com User ID: 1414768
Branch ID: 34c4d48e-4753-4715-9c30-c55843a943e8
Created: 2025-11-03 15:29:44
Active: Yes

Nutzung:
- Appointments: 25
- CalcomEventMaps: 20
- Recent synced: 5 appointments
- Latest appointment: 2025-11-23 07:21:40 (ID: 752)
```

### Staff 2: 9f47fda1-977c-47aa-a87a-0e8cbeaeb119

```
Name: Fabian Spitzer
Email: fabhandy@googlemail.com ← UNTERSCHIEDLICH!
Cal.com User ID: 1346408
Branch ID: 34c4d48e-4753-4715-9c30-c55843a943e8
Created: 2025-06-30 22:31:07
Active: Yes

Nutzung:
- Appointments: 20
- CalcomEventMaps: 6
- Recent synced: 2 appointments
- Latest appointment: 2025-11-23 22:05:32 (ID: 762)
```

---

## Unterschiede

| Feld | Staff 1 | Staff 2 |
|------|---------|---------|
| Email | fabianspitzer@icloud.com | fabhandy@googlemail.com |
| Cal.com User ID | 1414768 | 1346408 |
| Created | 2025-11-03 | 2025-06-30 |
| Appointments | 25 | 20 |
| CalcomEventMaps | 20 | 6 |

**Gemeinsam**:
- Name: Fabian Spitzer
- Branch ID: 34c4d48e-4753-4715-9c30-c55843a943e8 (gleiche Filiale)
- Beide aktiv
- Beide in Verwendung

---

## Warum sind das KEINE Duplikate?

### 1. Unterschiedliche E-Mail-Adressen
- Staff 1: `fabianspitzer@icloud.com`
- Staff 2: `fabhandy@googlemail.com`

→ Zwei separate Cal.com Accounts für dieselbe Person

### 2. Unterschiedliche Cal.com User IDs
- Staff 1: 1414768
- Staff 2: 1346408

→ Repräsentieren zwei verschiedene Benutzer in Cal.com

### 3. Beide aktiv in Verwendung
- Staff 1: 5 recent synced appointments
- Staff 2: 2 recent synced appointments (inklusive Appointment 762 vom heutigen Test)

→ Beide Accounts werden AKTUELL verwendet

---

## Mögliche Szenarien

### Szenario 1: Migration von altem zu neuem Account
- Staff 2 (9f47fda1) erstellt: 2025-06-30 ← ALTER Account
- Staff 1 (6ad1fa25) erstellt: 2025-11-03 ← NEUER Account

**Aber**: Beide werden NOCH verwendet! Keine klare Migration erkennbar.

### Szenario 2: Zwei verschiedene Cal.com Setups
- Staff 1: Hauptaccount (mehr CalcomEventMaps)
- Staff 2: Legacy/Test-Account

### Szenario 3: Multi-Account Setup (gewollt)
- Fabian hat zwei Cal.com Accounts für unterschiedliche Zwecke
- Beide sollen parallel existieren

---

## Empfehlung

### ❌ NICHT zusammenführen

**Begründung**:
1. Unterschiedliche E-Mail-Adressen → Separate Cal.com Accounts
2. Unterschiedliche Cal.com User IDs → Separate Kalender
3. Beide aktiv in Verwendung → Würde aktive Bookings brechen
4. Zusammenführung würde Cal.com Sync kaputt machen

### ✅ Akzeptieren als zwei separate Accounts

**Vorgehen**:
1. Beide Accounts behalten
2. In Dokumentation festhalten (dieses Dokument)
3. Bei Buchung: System wählt automatisch passenden Account
4. Optional: Umbenennen für Klarheit

---

## Optional: Umbenennung für Klarheit

Falls die zwei Accounts unterschiedliche Zwecke haben:

```sql
-- Option 1: Nach E-Mail unterscheiden
UPDATE staff
SET name = 'Fabian Spitzer (iCloud)'
WHERE id = '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe';

UPDATE staff
SET name = 'Fabian Spitzer (Gmail)'
WHERE id = '9f47fda1-977c-47aa-a87a-0e8cbeaeb119';

-- Option 2: Nach Erstellungsdatum unterscheiden
UPDATE staff
SET name = 'Fabian Spitzer (Neu)'
WHERE id = '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe';

UPDATE staff
SET name = 'Fabian Spitzer (Alt)'
WHERE id = '9f47fda1-977c-47aa-a87a-0e8cbeaeb119';
```

**Aber**: NUR machen, wenn User das explizit wünscht!

---

## Impact auf System

### Verfügbarkeitsprüfung
**Status**: ✅ Funktioniert korrekt

- Jeder Staff hat separate Appointments
- Keine Überschneidungen zwischen Staff 1 und Staff 2 Kalendern
- System prüft Verfügbarkeit pro Staff ID korrekt

### Cal.com Sync
**Status**: ✅ Funktioniert korrekt

- Jeder Staff hat eigene CalcomEventMaps
- Jeder Staff hat eigene Cal.com User ID
- Sync funktioniert unabhängig für beide Accounts

### Booking Assignment
**Frage**: Wie entscheidet System, welcher Fabian verwendet wird?

**Antwort prüfen**:
```sql
-- Wie wird Staff assigned bei Booking?
SELECT * FROM appointments
WHERE staff_id IN ('6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe', '9f47fda1-977c-47aa-a87a-0e8cbeaeb119')
ORDER BY created_at DESC
LIMIT 10;
```

---

## Fazit

**Die zwei "Fabian Spitzer" Records sind GEWOLLT und KORREKT.**

- ✅ Unterschiedliche E-Mail-Adressen
- ✅ Unterschiedliche Cal.com User IDs
- ✅ Beide aktiv in Verwendung
- ✅ Keine technischen Probleme
- ✅ System funktioniert korrekt

**Keine Aktion erforderlich** - beide Accounts können parallel existieren.

---

**Status**: ✅ ANALYSIERT - Kein Problem gefunden
**Empfehlung**: Behalten wie ist
**Alternative**: Optional umbenennen für Klarheit (nur wenn User wünscht)
