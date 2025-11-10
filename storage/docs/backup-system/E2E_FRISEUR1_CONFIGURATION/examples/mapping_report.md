# E2E GATE 0: Mapping-Report

**Datum:** 2025-11-03 12:45 CET
**Scope:** Friseur 1 (Company ID: 1)
**Ziel:** Branch↔Team, Service↔EventType, Staff↔User Mappings herstellen

---

## 1. Branch ↔ Cal.com Team Mapping

| Platform Branch ID | Branch Name | Cal.com Team ID | Team Name | Status |
|-------------------|-------------|-----------------|-----------|--------|
| `34c4d48e-4753...` | Friseur 1 Zentrale | **34209** | Friseur | ⚠️ **Team ID fehlt in Branch Settings** |

### Befund:
- Cal.com Team 34209 "Friseur" existiert und ist aktiv
- Branch "Friseur 1 Zentrale" hat **kein** `calcom_team_id` in Settings
- **Aktion erforderlich:** `calcom_team_id: 34209` in Branch Settings setzen

---

## 2. Service ↔ Cal.com EventType Mapping

### Platform Services (3):

| Service ID | Service Name | Duration | calcom_event_type_id | Status |
|-----------|--------------|----------|----------------------|--------|
| 41 | Premium Hair Treatment | 120 min | `NULL` | ❌ Kein Mapping |
| 42 | Comprehensive Therapy Session | 150 min | `NULL` | ❌ Kein Mapping |
| 43 | Medical Examination Series | 180 min | `NULL` | ❌ Kein Mapping |

### Cal.com EventTypes (46 im Team 34209):

**Auswahl relevanter EventTypes:**

| EventType ID | Title | Duration | Typ |
|-------------|-------|----------|-----|
| 3757770 | Herrenhaarschnitt | 55 min | Atomarer Service |
| 3757808 | Trockenschnitt | 30 min | Atomarer Service |
| 3757768 | Haarspende | 30 min | Atomarer Service |
| 3757769 | Hairdetox | 15 min | Atomarer Service |
| 3757762 | Föhnen & Styling Damen | 30 min | Atomarer Service |
| 3757766 | Föhnen & Styling Herren | 20 min | Atomarer Service |
| 3757710 | Balayage/Ombré | 150 min | Komposit-Service |
| 3757785 | Komplette Umfärbung (Blondierung): Blondierung auftragen (1 von 6) | 60 min | Komponente |
| 3757755 | Balayage/Ombré: Föhnen & Styling (4 von 4) | 40 min | Komponente |
| 3757761 | Dauerwelle: Haare wickeln (1 von 4) | 50 min | Komponente |
| ... | *(gesamt 46 EventTypes)* | ... | ... |

### Befund:
- **Kritische Diskrepanz:** Die 3 Platform-Services ("Premium Hair Treatment", "Comprehensive Therapy Session", "Medical Examination Series") passen **nicht** zu einem Friseurbetrieb
- Cal.com Team 34209 enthält bereits **46 friseur-spezifische EventTypes**
- **Keine Namen-Übereinstimmungen** zwischen Platform Services und Cal.com EventTypes
- **Root Cause:** Platform Services sind Demo-Daten aus einer anderen Branche

### Mapping-Empfehlung:
**Aktuell nicht möglich.** Es gibt keine sinnvolle 1:1-Zuordnung zwischen:
- "Premium Hair Treatment" (120 min) ↔ ? (kein passendes EventType)
- "Comprehensive Therapy Session" (150 min) ↔ ? (kein passendes EventType)
- "Medical Examination Series" (180 min) ↔ ? (kein passendes EventType)

**Lösungsweg (außerhalb GATE 0 Scope):**
1. Platform Services 41-43 löschen oder ersetzen
2. 16-20 friseur-spezifische Services anlegen (siehe `config.sample.yaml`)
3. Mapping zu Cal.com EventTypes herstellen (nach Namen und Dauer)

---

## 3. Staff ↔ Cal.com User Mapping

### Platform Staff (5):

| Staff ID | Name | Email | calcom_user_id | Status |
|---------|------|-------|----------------|--------|
| `010be4a7...` | Emma Williams | emma.williams@demo.com | `NULL` | ❌ Keine User-ID |
| `9f47fda1...` | Fabian Spitzer | fabian@askproai.de | `NULL` | ❌ Keine User-ID |
| `c4a19739...` | David Martinez | david.martinez@demo.com | `NULL` | ❌ Keine User-ID |
| `ce3d932c...` | Michael Chen | michael.chen@demo.com | `NULL` | ❌ Keine User-ID |
| `f9d4d054...` | Dr. Sarah Johnson | sarah.johnson@demo.com | `NULL` | ❌ Keine User-ID |

### Cal.com Team Members (2):

| Membership ID | Cal.com User ID | Name | Email | Role |
|--------------|----------------|------|-------|------|
| 494580 | **1346408** | Fabian Spitzer | fabhandy@googlemail.com | OWNER |
| 515780 | **1414768** | Fabian Spitzer | fabianspitzer@icloud.com | OWNER |

### Befund:
- **5 Platform Staff** vs. **2 Cal.com Team Members**
- **Keine E-Mail-Übereinstimmungen:**
  - `fabian@askproai.de` ≠ `fabhandy@googlemail.com`
  - `fabian@askproai.de` ≠ `fabianspitzer@icloud.com`
  - Alle anderen Staff haben @demo.com E-Mails (Platzhalter)
- **Root Cause:** Platform Staff sind Demo-Daten; Cal.com Team hat nur den Besitzer (Fabian, 2x)

### Mapping-Empfehlung:
**Manuelles Mapping möglich für Fabian:**
- Platform Staff `9f47fda1-977c-47aa-a87a-0e8cbeaeb119` (Fabian Spitzer, fabian@askproai.de)
  → Cal.com User ID **1346408** oder **1414768** (beide valide)

**Für die anderen 4 Staff-Mitglieder:**
- Kein Mapping möglich, da sie nicht im Cal.com Team sind
- **Lösungsweg (außerhalb GATE 0 Scope):**
  1. Cal.com Team Members für Emma, David, Michael, Sarah anlegen
  2. E-Mails korrigieren (von @demo.com zu @friseur1.de oder echte E-Mails)
  3. User IDs in Platform Staff Settings hinterlegen

---

## Zusammenfassung

| Mapping | Ist-Stand | Soll-Stand | Status |
|---------|-----------|------------|--------|
| **Branch ↔ Team** | Kein `calcom_team_id` | `34209` gesetzt | ⚠️ **Fixbar in GATE 0** |
| **Service ↔ EventType** | 3 falsche Services, keine Mappings | 16-20 passende Services mit EventType IDs | ❌ **Nicht fixbar in GATE 0** (Requires Feature-Entwicklung) |
| **Staff ↔ User** | 5 Staff, keine User IDs | 5 Staff mit Cal.com User IDs | ❌ **Nicht fixbar in GATE 0** (Requires Cal.com Team Setup) |

**GATE 0 Prognose:** ❌ **WIRD SCHEITERN**

**Begründung:**
- Prüfpunkt 1 (Phone↔Agent↔Branch): ✅ Bereits konsistent
- Prüfpunkt 2 (Branch↔Team): ⚠️ Fixbar durch `calcom_team_id: 34209` setzen
- Prüfpunkt 3 (Service↔EventType): ❌ **Blockiert durch falsche Demo-Services**
- Prüfpunkt 4 (Staff↔User): ❌ **Blockiert durch fehlende Cal.com Team Members**

**Empfohlene nächste Schritte (außerhalb GATE 0 Scope):**
1. **GAP-004 beheben:** 3 Demo-Services durch 16 Friseur-Services ersetzen
2. **GAP-003 beheben:** EventType IDs zu neuen Services mappen
3. **GAP-005 beheben:** Staff E-Mails korrigieren (@demo.com → echte E-Mails)
4. **Cal.com Team erweitern:** 4 weitere Members für Emma, David, Michael, Sarah anlegen
5. **GATE 0 erneut durchführen** mit korrekten Daten
