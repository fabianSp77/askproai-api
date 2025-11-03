# E2E Spezifikation: Friseur 1

## Inhaltsverzeichnis
1. [Zweck & Scope](#zweck--scope)
2. [Glossar](#glossar)
3. [Funktionale Anforderungen](#funktionale-anforderungen)
4. [Nicht-funktionale Anforderungen](#nicht-funktionale-anforderungen)
5. [Konfigurations-Matrizen](#konfigurations-matrizen)
6. [Akzeptanzkriterien & Testfälle](#akzeptanzkriterien--testfälle)
7. [Go-Live-Checkliste](#go-live-checkliste)
8. [Rollout-Template](#rollout-template)

---

## Zweck & Scope

Dieses Dokument beschreibt das End-to-End-System für **Friseur 1**, ein Haar salon-Unternehmen mit 2 Filialen, das Inbound-Telefonie über Retell.ai Voice AI nutzt, um automatisch Termine zu buchen, zu verschieben und zu stornieren.

**Scope:**
- Inbound Voice AI (Retell.ai)
- Middleware (Laravel API Gateway)
- Kalendermanagement (Cal.com)
- Frontend (Filament Admin Panel)
- Abrechnung (Stripe Prepaid)
- Logging & Analytics

**Out of Scope:**
- Outbound Calls (Rückrufe)
- SMS/Email-basierte Terminbuchung
- Mobile Customer App

---

## Glossar

| Begriff | Definition |
|---------|------------|
| **Retell.ai** | Voice AI Platform für Inbound-Telefonie |
| **Agent** | Conversational AI Agent (konfiguriert mit Prompt, Functions) |
| **Function Call** | Retell.ai ruft während Gespräch Middleware-Endpunkte auf |
| **Cal.com** | Open-Source Kalendersystem für Buchungen |
| **Event Type** | Service-Definition in Cal.com (z.B. "Herrenhaarschnitt 30 Min") |
| **Team** | Cal.com Gruppierung für Filialen/Branches |
| **Booking** | Gebuchter Termin in Cal.com |
| **Appointment** | Gebuchter Termin in Middleware-DB (Laravel) |
| **SoT** | Single Source of Truth (authoritative System) |
| **Policy** | Geschäftsregeln (z.B. Storno-Cutoff: 24h) |
| **Komponenten-Service** | Service mit mehreren Segmenten (z.B. Färben mit Einwirkzeit) |
| **Staff Reuse** | Mitarbeiter darf während Gap parallel andere Termine übernehmen |

---

## Funktionale Anforderungen

### FR-1: Neu-Buchung (Book Appointment)

**Als** Kunde
**Möchte ich** telefonisch einen Termin buchen
**Damit** ich nicht online oder persönlich buchen muss

**Akzeptanzkriterien:**
- ✅ Kunde ruft Filial-Nummer an (+493033081738)
- ✅ Agent grüßt und identifiziert Kunde per Telefonnummer
- ✅ Agent präsentiert verfügbare Services
- ✅ Agent fragt nach gewünschtem Datum/Zeit
- ✅ Agent prüft Verfügbarkeit via Cal.com
- ✅ Agent bucht Termin bei Bestätigung
- ✅ Kunde erhält Email-Bestätigung mit .ics-Datei
- ✅ Termin erscheint in Filament Admin Panel

**Fehlerbehandlung:**
- Keine Verfügbarkeit → Agent schlägt Alternativen vor
- Policy-Block (z.B. zu kurzfristig) → Agent erklärt Grund
- Cal.com Timeout → Retry mit Circuit Breaker

---

### FR-2: Umbuchung (Reschedule Appointment)

**Als** Kunde
**Möchte ich** einen bestehenden Termin verschieben
**Damit** ich flexibel auf Änderungen reagieren kann

**Akzeptanzkriterien:**
- ✅ Kunde ruft an und bittet um Umbuchung
- ✅ Agent identifiziert bestehenden Termin
- ✅ Agent prüft Policy: Umbuchung >24h vor Termin erlaubt
- ✅ Agent prüft neue Verfügbarkeit
- ✅ Agent bucht um bei Bestätigung
- ✅ Alter Termin wird storniert, neuer erstellt
- ✅ Email-Benachrichtigung mit neuem .ics

**Fehlerbehandlung:**
- Umbuchung <24h vor Termin → Policy blockiert, Agent erklärt
- Keine Verfügbarkeit für Wunschzeit → Alternativen

---

### FR-3: Stornierung (Cancel Appointment)

**Als** Kunde
**Möchte ich** einen Termin stornieren
**Damit** der Slot für andere frei wird

**Akzeptanzkriterien:**
- ✅ Kunde ruft an und bittet um Storno
- ✅ Agent identifiziert Termin
- ✅ Agent prüft Policy: Storno >24h vor Termin erlaubt
- ✅ Agent storniert bei Bestätigung
- ✅ Termin Status: "cancelled" in DB und Cal.com
- ✅ Email-Benachrichtigung über Storno

**Fehlerbehandlung:**
- Storno <24h vor Termin → Policy blockiert

---

## Nicht-funktionale Anforderungen

### NFR-1: Latenz & Performance

| Metrik | SLO | Messung |
|--------|-----|---------|
| Retell Function Call Response | <300ms (P95) | Server-Logs |
| Cal.com Availability Query | <800ms (P95) | APM |
| Booking Creation | <2s (P95) | End-to-End |
| Webhook Processing | <500ms (P95) | Queue-Latency |

**Strategie:**
- Redis-Cache für Verfügbarkeiten (5 Min TTL)
- Circuit Breaker bei Cal.com Timeouts
- Asynchrone Webhook-Verarbeitung via Queue

---

### NFR-2: Zuverlässigkeit

| Metrik | SLO | Messung |
|--------|-----|---------|
| Uptime Middleware | 99.5% | Monitoring |
| Webhook Success Rate | >99% | Dead-Letter-Queue |
| Idempotenz | 100% | Audit-Logs |

**Strategie:**
- Idempotency-Keys für alle Write-Ops
- Retry mit Exponential Backoff
- Dead-Letter-Queue für Failed Jobs

---

### NFR-3: Datenschutz (DSGVO)

| Maßnahme | Details |
|----------|---------|
| PII Redaction | Nach 365 Tagen automatisch |
| Transcript Anonymization | Nach 90 Tagen |
| Audio Retention | 30 Tage (Retell.ai), dann S3 (90 Tage) |
| Kundendaten-Löschung | Auf Anfrage innerhalb 48h |

**Compliance:**
- DSGVO Art. 17 (Recht auf Löschung)
- DSGVO Art. 15 (Auskunftsrecht)

---

## Konfigurations-Matrizen

### Matrix 1: Filialen ↔ Cal.com Teams ↔ Retell.ai

| Filiale | Branch ID (UUID) | Cal.com Team ID | Retell Agent ID | Telefonnummer | Timezone |
|---------|------------------|-----------------|-----------------|---------------|----------|
| Zentrale | 34c4d48e-4753... | 34209 | agent_b36ecd...07b | +493033081738 | Europe/Berlin |
| Zweigstelle | c335705a-435b... | 34209 | agent_b36ecd...07b | +493033081739* | Europe/Berlin |

*TODO: Zweite Nummer registrieren bei Retell.ai

---

### Matrix 2: Services ↔ Cal.com Event IDs

| Service Name | Event ID | Dauer | Preis | Skill | Komponenten? |
|--------------|----------|-------|-------|-------|--------------|
| Kinderhaarschnitt | 3719738 | 30 | €20.50 | cut_children | Nein |
| Trockenschnitt | 3719739 | 30 | €25.00 | cut_men | Nein |
| Waschen & Styling | 3719740 | 45 | €40.00 | styling | Nein |
| Waschen, schneiden, föhnen | 3719741 | 60 | €45.00 | cut_women | Nein |
| Haarspende | 3719742 | 30 | €80.00 | cut_special | Nein |
| Beratung | 3719743 | 30 | €30.00 | consultation | Nein |
| Hairdetox | 3719744 | 15 | €12.50 | treatment | Nein |
| Rebuild Treatment Olaplex | 3719745 | 15 | €15.50 | treatment | Nein |
| Intensiv Pflege Maria Nila | 3719746 | 15 | €15.50 | treatment | Nein |
| Gloss | 3719747 | 30 | €45.00 | color_basic | Nein |
| Ansatzfärbung, waschen, schneiden, föhnen | 3719748 | 120 | €85.00 | color | Ja* |
| Ansatz, Längenausgleich, waschen, schneiden, föhnen | 3719749 | 120 | €85.00 | color | Ja* |
| Klassisches Strähnen-Paket | 3719750 | 120 | €125.00 | color_advanced | Ja* |
| Globale Blondierung | 3719751 | 120 | €185.00 | color_advanced | Ja* |
| Strähnentechnik Balayage | 3719752 | 180 | €255.00 | color_expert | Ja* |
| Faceframe | 3719753 | 180 | €225.00 | color_expert | Ja* |

*TODO: Komponenten-Services (GAP-004) - Aktuell nicht implementiert

---

### Matrix 3: Staff ↔ Skills ↔ Cal.com User IDs

| Name | Email | Cal.com User ID | Filiale | Skills | Arbeitszeiten |
|------|-------|-----------------|---------|--------|---------------|
| Emma Williams | emma.williams@friseur1.de | TODO* | Zentrale | cut_men, cut_women, cut_children, color, color_advanced | Mo-Fr 09:00-18:00 |
| Fabian Spitzer | fabian.spitzer@friseur1.de | TODO* | Zentrale | cut_men, cut_women, styling | Mo-Fr 10:00-19:00 |
| Dr. Sarah Johnson | sarah.johnson@friseur1.de | TODO* | Zentrale | color, color_advanced, color_expert | Di-Sa 09:00-17:00 |
| David Martinez | david.martinez@friseur1.de | TODO* | Zweigstelle | cut_men, cut_women, styling | Mo-Fr 09:00-18:00 |
| Michael Chen | michael.chen@friseur1.de | TODO* | Zweigstelle | cut_men, cut_children | Mo-Fr 10:00-19:00 |

*TODO: GAP-002 BLOCKER - Cal.com User IDs mappen

---

### Matrix 4: Policies pro Filiale

| Policy Type | Zentrale | Zweigstelle | Company Default |
|-------------|----------|-------------|-----------------|
| **Allowed Actions** | book, reschedule, cancel | book, reschedule, cancel | book, reschedule, cancel |
| **Reschedule Cutoff** | 1440 Min (24h) | 1440 Min (24h) | 1440 Min (24h) |
| **Cancel Cutoff** | 1440 Min (24h) | 1440 Min (24h) | 1440 Min (24h) |
| **No-Show Grace Period** | 15 Min | 15 Min | 15 Min |
| **No-Show Fee** | €0 | €0 | €0 |
| **Max Reschedules** | 3 | 3 | 3 |

TODO: GAP-003 - Branch-spezifische Policies konfigurieren (falls unterschiedlich)

---

## Akzeptanzkriterien & Testfälle

### Test 1: Neu-Buchung (Happy Path)

**Voraussetzungen:**
- Kunde bekannt (Telefonnummer in DB)
- Service verfügbar (Cal.com Slots vorhanden)
- Filiale Zentrale

**Schritte:**
1. Kunde ruft +493033081738 an
2. Agent grüßt: "Willkommen zurück, Emma!"
3. Kunde: "Ich hätte gerne einen Termin für Herrenhaarschnitt"
4. Agent: "Wann hätten Sie Zeit?"
5. Kunde: "Morgen 14 Uhr"
6. Agent prüft Verfügbarkeit (Cal.com API)
7. Agent: "Ja, verfügbar. Mit Emma Williams?"
8. Kunde: "Ja, bitte"
9. Agent bucht Termin
10. Kunde erhält Email

**Erwartetes Ergebnis:**
- ✅ Termin in `appointments` Tabelle
- ✅ Cal.com Booking erstellt
- ✅ Email versendet
- ✅ Call-Log mit Status "completed"
- ✅ Kosten berechnet und gespeichert

---

### Test 2: Umbuchung innerhalb Cutoff

**Voraussetzungen:**
- Bestehender Termin in 3 Tagen
- Policy: Umbuchung >24h erlaubt

**Schritte:**
1. Kunde ruft an: "Ich möchte meinen Termin verschieben"
2. Agent identifiziert Termin
3. Agent: "Kein Problem, wann passt es Ihnen besser?"
4. Kunde: "Einen Tag später, gleiche Uhrzeit"
5. Agent prüft Verfügbarkeit
6. Agent: "Verfügbar, ich buche um"
7. Alter Termin storniert, neuer erstellt

**Erwartetes Ergebnis:**
- ✅ Alter Termin Status "cancelled"
- ✅ Neuer Termin Status "scheduled"
- ✅ Email mit neuem .ics
- ✅ `appointment_modifications` Eintrag

---

### Test 3: Storno nach Cutoff (NEGATIV-TEST)

**Voraussetzungen:**
- Bestehender Termin in 12 Stunden
- Policy: Storno >24h erforderlich

**Schritte:**
1. Kunde ruft an: "Ich möchte meinen Termin stornieren"
2. Agent identifiziert Termin
3. Agent prüft Policy
4. Agent: "Leider ist eine Stornierung nur bis 24h vor dem Termin möglich. Ihr Termin ist in 12 Stunden."
5. Storno wird NICHT durchgeführt

**Erwartetes Ergebnis:**
- ❌ Storno blockiert
- ✅ Termin bleibt "scheduled"
- ✅ Call-Log mit "policy_block" Metadata
- ✅ Kunde informiert über Policy

---

### Test 4: Unbekannter Kunde mit CLIR

**Voraussetzungen:**
- Telefonnummer unterdrückt ("anonymous")

**Schritte:**
1. Kunde ruft an (Nummer unterdrückt)
2. Agent: "Guten Tag! Wie kann ich Ihnen helfen?"
3. Agent kann Kunde nicht identifizieren
4. Agent: "Darf ich Ihren Namen und Ihre Telefonnummer haben?"
5. Kunde gibt Daten an
6. Agent erstellt neuen Kundeneintrag
7. Buchung wie gewohnt

**Erwartetes Ergebnis:**
- ✅ Neuer `customers` Eintrag
- ✅ `from_number` = "anonymous" in `calls`
- ✅ Buchung erfolgreich

---

### Test 5: Cal.com Timeout → Retry

**Voraussetzungen:**
- Cal.com API simuliert Timeout (3s+)

**Schritte:**
1. Kunde ruft an und wählt Service
2. Agent fragt Verfügbarkeit ab
3. Cal.com API Timeout
4. Circuit Breaker greift → Retry
5. Zweiter Versuch erfolgreich
6. Agent präsentiert Slots

**Erwartetes Ergebnis:**
- ✅ Retry durchgeführt
- ✅ Idempotenz gewahrt (keine Duplikate)
- ✅ Latenz <5s Gesamt

---

## Go-Live-Checkliste

### Vorbereitung

- [ ] **G0: ID-Mappings vollständig**
  - [ ] Phone ↔ Agent ↔ Branch (1:1)
  - [ ] Branch ↔ Cal.com Team (1:1)
  - [ ] Service ↔ Event ID (1:1)
  - [ ] Staff ↔ Cal.com User ID (1:1)
  - [ ] `verify-ids.sh` = 0

- [ ] **ENV-Variablen gesetzt**
  - [ ] `CALCOM_API_KEY`
  - [ ] `RETELL_API_KEY`
  - [ ] `STRIPE_SECRET` (falls Prepaid)
  - [ ] `BASE_URL`
  - [ ] `EXRATE_USD_EUR`

- [ ] **Retell.ai Agent konfiguriert**
  - [ ] Prompt finalisiert
  - [ ] Function Calls registriert
  - [ ] Webhook URL erreichbar
  - [ ] Test-Call erfolgreich

- [ ] **Cal.com Team setup**
  - [ ] Event Types erstellt (16 Services)
  - [ ] Staff als Team Members hinzugefügt
  - [ ] Arbeitszeiten konfiguriert
  - [ ] Webhook eingerichtet

### Tests

- [ ] **G2: cURL-Tests bestanden** (5 Szenarien)
- [ ] **G4: Policy-Engine blockiert korrekt**
- [ ] **G5: Kostenberechnung sekundengenau**
- [ ] **G6: Webhook-Idempotenz validiert**

### Monitoring

- [ ] **Sentry/Datadog eingerichtet**
- [ ] **Alerts konfiguriert** (Error Rate >5%, Latency >1s)
- [ ] **Dashboard deployed** (Grafana/Kibana)

### Dokumentation

- [ ] **G1: index.html offline-fähig**
- [ ] **e2e.md vollständig**
- [ ] **ADRs dokumentiert** (4 Entscheidungen)
- [ ] **Runbooks erstellt** (Incident Response)

---

## Rollout-Template

### Neues Unternehmen in <60 Minuten

**Voraussetzungen:**
- Friseur 1 als Template konfiguriert
- `clone-company.sh` getestet
- Cal.com Account mit API-Zugriff
- Retell.ai Account mit Agent-Quota

**Schritte:**

1. **Company-Daten sammeln** (5 Min)
   - Name, Anzahl Filialen, Services, Staff
   - Telefonnummern (bei Retell.ai registrieren)
   - Cal.com Team erstellen

2. **Clone-Script ausführen** (10 Min)
   ```bash
   scripts/e2e/clone-company.sh "Friseur 2" --dry-run
   # Review Diff
   scripts/e2e/clone-company.sh "Friseur 2" --apply
   ```

3. **Manuelle Anpassungen** (20 Min)
   - Staff anlegen und Cal.com User IDs mappen
   - Services anpassen (falls abweichend)
   - Policies konfigurieren (falls Branch-spezifisch)
   - Telefonnummern zuordnen

4. **Retell.ai Agent Setup** (15 Min)
   - Agent klonen von Friseur 1
   - Prompt anpassen (Name, Services)
   - Webhook URL setzen
   - Test-Call durchführen

5. **Validation** (10 Min)
   ```bash
   scripts/e2e/verify-ids.sh --company=<NEW_COMPANY_ID>
   ```

6. **Go-Live** (Checkliste abarbeiten)

**Output:**
- Neue Company ID
- 2 Branches (oder mehr)
- 16 Services (oder angepasst)
- 5+ Staff (Cal.com User IDs gemappt)
- Funktionsfähiger Retell.ai Agent
- E2E-Dokumentation generiert

---

## Kostenbeispiele (Sekundengenaue Berechnung)

### 3 Minuten 5 Sekunden (185 Sekunden)

```
Duration: 185 Sekunden = 3.0833 Minuten
Retell Cost (USD): 3.0833 × $0.020/min = $0.06167
Retell Cost (EUR): $0.06167 × 0.95 = €0.0586
Platform Markup (30%): €0.0586 × 1.30 = €0.0762
Customer Cost: ceil(€0.0762 × 100) = 8 Cent
```

### 6 Minuten 0 Sekunden (360 Sekunden)

```
Duration: 360 Sekunden = 6.0 Minuten
Retell Cost (USD): 6.0 × $0.020/min = $0.12
Retell Cost (EUR): $0.12 × 0.95 = €0.114
Platform Markup (30%): €0.114 × 1.30 = €0.148
Customer Cost: ceil(€0.148 × 100) = 15 Cent
```

### 12 Minuten 30 Sekunden (750 Sekunden)

```
Duration: 750 Sekunden = 12.5 Minuten
Retell Cost (USD): 12.5 × $0.020/min = $0.25
Retell Cost (EUR): $0.25 × 0.95 = €0.2375
Platform Markup (30%): €0.2375 × 1.30 = €0.3088
Customer Cost: ceil(€0.3088 × 100) = 31 Cent
```

**Formel (PHP):**
```php
$durationSeconds = 185;
$retellCostPerMinUSD = 0.020;
$platformMarkupPercent = 30;
$exchangeRateUSDtoEUR = 0.95;

$durationMinutes = $durationSeconds / 60.0;
$retellCostUSD = $durationMinutes * $retellCostPerMinUSD;
$retellCostEUR = $retellCostUSD * $exchangeRateUSDtoEUR;
$customerCostEUR = $retellCostEUR * (1 + ($platformMarkupPercent / 100));
$customerCostCents = (int)ceil($customerCostEUR * 100);
```

---

*Letzte Aktualisierung: 2025-11-03*
*Verantwortlich: DevOps Team*
*Status: Draft → Review → Approved*
