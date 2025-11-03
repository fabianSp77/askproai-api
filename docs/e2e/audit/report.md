# E2E Audit Report: Friseur 1

**Datum:** 2025-11-03
**Scope:** Company ID **1** (korrigiert von 5), 1 Branch, 3 Services, 5 Staff
**Status:** ⚠️ Teilweise konfiguriert (Demo/Test-Installation)

---

## Executive Summary

| Kategorie | Status | Completion | Kritische Issues |
|-----------|--------|------------|------------------|
| **Company & Branches** | ⚠️ | 50% | Nur 1 Branch (Zentrale), keine Zweigstelle |
| **ID-Mappings** | ⚠️ | 60% | Services ohne Cal.com Event IDs |
| **Cal.com Integration** | ❌ | 0% | Keine Event IDs konfiguriert |
| **Retell.ai Integration** | ✅ | 80% | Agent mapped, Webhook URL fehlt möglicherweise |
| **Staff** | ✅ | 100% | Alle 5 Staff haben Cal.com User IDs |
| **Services** | ❌ | 20% | Nur 3 statt 16 Services, keine Event IDs |
| **Policies** | ❌ | 0% | Keine Policies konfiguriert |
| **Komponenten-Services** | ❌ | 0% | Nicht implementiert |
| **Billing** | ❌ | 0% | Keine Kostenberechnung sichtbar |
| **GESAMT** | ⚠️ | **34%** | **7 Major Gaps, 2 Blocker** |

---

## 1. Company & Branches

### Company

| Feld | Ist-Wert | Soll-Wert | Status |
|------|----------|-----------|--------|
| ID | 1 | 1 | ✅ |
| Name | Friseur 1 | Friseur 1 | ✅ |
| Slug | krueckeberg-servicegruppe | friseur-1 | ⚠️ Inkonsistent |
| Timezone | *(nicht gesetzt)* | Europe/Berlin | ❌ |
| Locale | *(nicht gesetzt)* | de | ❌ |
| Currency | *(nicht gesetzt)* | EUR | ❌ |

**Settings:**
```json
{
  "needs_appointment_booking": false,
  "service_type": "call_center",
  "business_type": "telefonie_service"
}
```

**⚠️ Problem:**
- `needs_appointment_booking: false` → Appointment-Buchung deaktiviert?
- `service_type: call_center` → Falsche Kategorie für Friseur
- `business_type: telefonie_service` → Sollte `hair_salon` sein

**✅ Soll-Settings:**
```json
{
  "needs_appointment_booking": true,
  "service_type": "appointment_booking",
  "business_type": "hair_salon",
  "calcom_team_id": 34209,
  "retell_agent_id": "agent_b36ecd3927a81834b6d56ab07b",
  "timezone": "Europe/Berlin",
  "locale": "de",
  "currency": "EUR"
}
```

---

### Branches

| Branch | UUID | Settings | Status |
|--------|------|----------|--------|
| Friseur 1 Zentrale | 34c4d48e-4753-4715-9c30-c55843a943e8 | `{"needs_appointment_booking":false,"service_hours":"24/7","service_type":"call_answering"}` | ⚠️ |
| Friseur 1 Zweigstelle | *(fehlt)* | - | ❌ FEHLT |

**⚠️ Problem:** Nur 1 Branch, aber E2E-Spezifikation erwartet 2 Filialen

**TODO:**
- Branch "Zweigstelle" anlegen (UUID: generieren)
- Settings korrigieren: `needs_appointment_booking: true`
- Cal.com Team ID hinzufügen (aktuell: NULL)

---

## 2. ID-Mappings

### 2.1 Phone ↔ Agent ↔ Branch ✅

| Phone Number | Agent ID (suffix) | Branch | Status |
|--------------|-------------------|--------|--------|
| +493033081738 | ...b6d56ab07b | Zentrale (34c4d48e...) | ✅ OK |

**Full Agent ID:** `agent_b36ecd3927a81834b6d56ab07b` (Production Agent, korrekt!)

**✅ Mapping konsistent**

---

### 2.2 Service ↔ Cal.com Event ID ❌ BLOCKER

| Service Name | Duration | Price | Event ID | Status |
|--------------|----------|-------|----------|--------|
| Premium Hair Treatment | 120 min | *(nicht gesetzt)* | **NULL** | ❌ FEHLT |
| Comprehensive Therapy Session | 150 min | *(nicht gesetzt)* | **NULL** | ❌ FEHLT |
| Medical Examination Series | 180 min | *(nicht gesetzt)* | **NULL** | ❌ FEHLT |

**❌ KRITISCH:**
- Alle 3 Services haben **keine Cal.com Event IDs**
- Keine der 16 erwarteten Friseur-Services existieren
- Service-Namen passen nicht zu "Friseur" (Hair Treatment, Therapy, Medical?)

**Soll-Zustand:**
- 16 Friseur-Services (Kinderhaarschnitt, Waschen/Schneiden/Föhnen, Färben, etc.)
- Jeder Service mit Cal.com Event Type ID (3719738-3719753)
- Preise konfiguriert (€20-€255)

**TODO:**
1. Services löschen oder umbenennen
2. 16 Friseur-Services anlegen (siehe config.sample.yaml)
3. Cal.com Event Types erstellen (API oder manuell)
4. Event IDs in `services.settings` speichern

---

### 2.3 Staff ↔ Cal.com User ID ⚠️

| Name | Email | Cal.com User ID | Status |
|------|-------|-----------------|--------|
| Emma Williams | emma.williams@demo.com | 1001 | ⚠️ Test-ID? |
| Fabian Spitzer | fabian@askproai.de | 1002 | ⚠️ Test-ID? |
| David Martinez | david.martinez@demo.com | 1003 | ⚠️ Test-ID? |
| Michael Chen | michael.chen@demo.com | 1004 | ⚠️ Test-ID? |
| Dr. Sarah Johnson | sarah.johnson@demo.com | 1005 | ⚠️ Test-ID? |

**⚠️ Problem:**
- User IDs (1001-1005) sehen wie Test-/Mock-Daten aus
- Emails: `@demo.com` statt `@friseur1.de`
- Fabian: `@askproai.de` (internes Email)

**TODO:**
1. Cal.com Team 34209 Members API abfragen
2. Prüfen ob User IDs 1001-1005 existieren
3. Falls nicht: Real Cal.com User IDs mappen
4. Emails korrigieren zu `@friseur1.de`

---

### 2.4 Branch ↔ Cal.com Team ID ❌

| Branch | Cal.com Team ID | Status |
|--------|-----------------|--------|
| Zentrale | *(NULL in settings)* | ❌ FEHLT |
| Zweigstelle | *(Branch fehlt)* | ❌ FEHLT |

**❌ KRITISCH:** Keine Cal.com Team IDs konfiguriert

**TODO:**
- Zentrale: `calcom_team_id: 34209` in Branch-Settings
- Zweigstelle: Eigenes Team anlegen oder gleiches Team (34209)?

---

## 3. Policies

### Company-Level Policies ❌

```sql
SELECT * FROM policy_configurations WHERE configurable_id = 1 AND configurable_type = 'App\Models\Company';
```

**Result:** *(Keine Policies gefunden)*

**❌ Problem:** Keine Storno-/Umbuchungs-Policies konfiguriert

**Soll-Zustand:**
```json
{
  "cancellation": {
    "enabled": true,
    "minimum_notice_hours": 24,
    "allowed_by": ["customer", "staff"],
    "fee_applies": false
  },
  "reschedule": {
    "enabled": true,
    "minimum_notice_hours": 24,
    "max_reschedules_per_appointment": 3
  },
  "no_show": {
    "grace_period_minutes": 15,
    "charge_fee": false
  }
}
```

---

## 4. Retell.ai Integration

### Agent Configuration

| Parameter | Wert | Status |
|-----------|------|--------|
| Agent ID | agent_b36ecd3927a81834b6d56ab07b | ✅ |
| Phone Number | +493033081738 | ✅ |
| Webhook URL | *(nicht verifiziert)* | ⚠️ TODO |

**TODO:**
1. Retell API abfragen: `GET /agents/{agent_id}`
2. Webhook URL prüfen: Sollte `https://api.askproai.de/api/webhooks/retell` sein
3. Function Calls prüfen: initialize_call, check_availability, book_appointment, etc.

---

## 5. Cal.com Integration

### Event Types ❌

**Status:** Keine Event Types konfiguriert (alle Service Event IDs = NULL)

**TODO:**
1. Cal.com Team 34209 prüfen: `GET /teams/34209`
2. Event Types erstellen (16 Friseur-Services)
3. Event IDs in Services-Tabelle speichern

---

## 6. Komponenten-Services ❌

**Status:** Nicht implementiert

**TODO:**
- `service_components` Tabelle erstellen (Migration)
- Scheduler-Logic für Gaps implementieren
- UI-Editor für Komponenten

**Siehe:** GAP-004 in gaps.yaml

---

## 7. Gaps-Übersicht

| ID | System | Severity | Titel | ETA |
|----|--------|----------|-------|-----|
| GAP-001 | company | major | Company Settings inkorrekt (call_center statt hair_salon) | 1h |
| GAP-002 | branches | blocker | Nur 1 Branch (Zweigstelle fehlt) | 2h |
| GAP-003 | services | blocker | Keine Cal.com Event IDs (alle NULL) | 8h |
| GAP-004 | services | major | Nur 3 statt 16 Services | 4h |
| GAP-005 | staff | minor | Staff Emails @demo.com statt @friseur1.de | 0.5h |
| GAP-006 | policies | major | Keine Policies konfiguriert | 2h |
| GAP-007 | components | major | Komponenten-Services nicht implementiert | 16h |
| GAP-008 | billing | major | Keine Kostenberechnung/Prepaid sichtbar | 12h |
| GAP-009 | calcom | minor | Cal.com Team ID in Branch Settings fehlt | 0.5h |

**Gesamt: 9 Gaps, davon 2 Blocker, 5 Major, 2 Minor**

---

## 8. Empfohlene Reihenfolge

### Sofort (Blocker beheben)

1. **GAP-002** - Branch "Zweigstelle" anlegen (2h)
2. **GAP-003** - Cal.com Event Types erstellen und IDs mappen (8h)

### Sprint 1 (Kritische Features)

3. **GAP-001** - Company Settings korrigieren (1h)
4. **GAP-004** - 16 Friseur-Services anlegen (4h)
5. **GAP-006** - Policies konfigurieren (2h)
6. **GAP-009** - Cal.com Team ID in Branch Settings (0.5h)

### Sprint 2 (Nice-to-Have)

7. **GAP-005** - Staff Emails korrigieren (0.5h)
8. **GAP-007** - Komponenten-Services Backend (16h)
9. **GAP-008** - Billing/Prepaid System (12h)

**Gesamt-Aufwand Blocker + Sprint 1:** ~17.5h
**Gesamt-Aufwand inkl. Sprint 2:** ~46h

---

## 9. GATE 0 Status

### Prüfung: `verify-ids.sh`

```bash
scripts/e2e/verify-ids.sh 1  # Company ID 1
```

**Erwartetes Ergebnis (aktuell):**

```
[1/4] Phone ↔ Agent ↔ Branch: ✅ OK
[2/4] Service ↔ Event ID:     ❌ FAILED (alle NULL)
[3/4] Staff ↔ Cal.com User:   ⚠️  WARNING (Test-IDs?)
[4/4] Branch ↔ Team ID:       ❌ FAILED (NULL)

GATE 0: ❌ FAILED
```

**Nach Fixes:**
- GAP-003 behoben → Service Event IDs gesetzt
- GAP-009 behoben → Branch Team IDs gesetzt
- Staff User IDs verifiziert gegen Cal.com API

**Dann:** `GATE 0: ✅ PASSED`

---

## 10. Next Actions

### Für DevOps Team

1. Branch "Zweigstelle" anlegen:
   ```php
   php artisan tinker --execute="
   \$company = \App\Models\Company::find(1);
   \$branch = \$company->branches()->create([
     'name' => 'Friseur 1 Zweigstelle',
     'settings' => [
       'needs_appointment_booking' => true,
       'calcom_team_id' => 34209,  // Oder eigenes Team?
       'service_type' => 'appointment_booking'
     ]
   ]);
   echo 'Branch ID: ' . \$branch->id;
   "
   ```

2. Company Settings korrigieren:
   ```php
   php artisan tinker --execute="
   \$company = \App\Models\Company::find(1);
   \$company->settings = [
     'needs_appointment_booking' => true,
     'service_type' => 'appointment_booking',
     'business_type' => 'hair_salon',
     'calcom_team_id' => 34209,
     'retell_agent_id' => 'agent_b36ecd3927a81834b6d56ab07b',
     'timezone' => 'Europe/Berlin',
     'locale' => 'de',
     'currency' => 'EUR'
   ];
   \$company->save();
   "
   ```

3. Cal.com Event Types erstellen (via API oder manuell)

4. Services anlegen (siehe `config.sample.yaml` für vollständige Liste)

---

*Report erstellt: 2025-11-03 via E2E Audit System*
*Nächste Audit: Nach Fixes (GATE 0 Re-Check)*
