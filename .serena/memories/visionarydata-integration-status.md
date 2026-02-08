# VisionaryData Integration Status

**Letzte Aktualisierung:** 2026-02-03

---

## Firmendaten

**Visionary Data GmbH**
- **Adresse:** Mozartstr. 2, 85659 Forstern
- **Handelsregister:** HRB 272172
- **Registergericht:** Amtsgericht München
- **USt-IdNr:** DE449916795
- **W-IdNr:** 114/139/00281

---
**Ansprechpartner VisionaryData:**
- Sebastian Sager (CTO) - sebastian.sager@visionarydata.de
- Thomas Stanner (GF) - thomas.stanner@visionarydata.de
- Sebastian Gesellensetter - sebastian.gesellensetter@visionarydata.de

---

## Technische Konfiguration

### Webhook
- **URL:** `https://agents-test.ascadi.ai/webhook/eb15a330-b983-4697-b4b4-d3bd61ff41f4-ticketsystem`
- **Auth:** HMAC-SHA256 Signatur
- **Status:** ✅ Funktioniert (alle Deliveries HTTP 200)

### E-Mail Backup
- **Empfänger:** `ticket-support@visionarydata.de` (seit 2026-01-13)
- **Vorher:** fabian@askproai.de + 3x VisionaryData-Adressen

### ServiceOutputConfigurations (Company 1658 - IT-Systemhaus Test GmbH)
- ID 28: Security Incident - Critical Alert
- ID 29: Infrastructure Support - High Priority
- ID 30: Application Support - Standard

---

## ⚠️ CRITICAL RULE: Always Create New Retell Agents

**Decision (2026-02-02)**: Changes to Retell agents must ALWAYS be implemented by creating a **NEW agent from scratch** via the Retell API (`create-agent`). Never modify/patch an existing agent — dashboard changes from Claude don't reliably apply, and the Retell dashboard shows no updates.

**Workflow**:
1. Use `deploy-retell-v3.sh create` or POST to `/create-conversation-flow` + `/create-agent`
2. Get new agent_id and flow_id from response
3. Update backend DB via `php artisan visionarydata:setup-agent --agent-id=<new> --flow-id=<new>`
4. Verify via test call

## Status nach Sebastian-Antwort (13.01.2026)

### Bestätigt von Sebastian
- ✅ **70 parallele Anrufe** reichen aus (nur 2 Telefonkräfte bei VisionaryData)
- ✅ **Error-Handling** ist OK ("fein für mich")
- ✅ **Ticket-ID Feldname:** `ticket_id` (Sebastian baut um)

### Unsere Code-Verbesserungen (13.01.2026)
- ✅ `extractExternalId()` gehärtet (Type-Safety, Längenvalidierung, Sanitization)
- ✅ Tests hinzugefügt (`tests/Unit/ServiceGateway/ExtractExternalIdTest.php`)
- ✅ Filament UI: external_reference in Liste sichtbar + suchbar + Filter

## Agent v3.0 - Thomas Feedback (02.02.2026)

### Implementierte Aenderungen (v3.0 Initial)
- **Consent-Wording**: Kompakter, mit `{{FIRMENNAME}}` Platzhalter
- **Intro**: Offener Einstieg ("Worum geht es?") statt strukturierte Abfrage
- **Flow-Vereinfachung**: Standort/Scope/Impact/Seit-wann NICHT mehr Pflicht-Gates
- **Triage**: Max 1 Rueckfrage pro Kategorie, dann direkt zu Kontaktdaten
- **Security-Eskalation**: Hard-Trigger bei Ransomware/Datenleck etc. → sofort Ticket, Priority=critical
- **Abschluss-Wording**: Thomas' exakte Formulierung ("Ein Techniker schaut sich das an...")
- **Fallback-Wording**: "Verbindung war kurz schlecht" (No-Blame)
- **Voice**: ElevenLabs `eEmoQJhC4SAEQpCINUov` (Thomas-Favorit)
- **Backend**: `use_case_detail` wird jetzt in `ai_metadata` gespeichert (Bug-Fix)
- **Backend**: Security-Eskalation bei `escalation=critical` → Priority CRITICAL

### v3.1 Bug-Fix (Thomas-Testanruf 02.02.2026)

**Thomas-Feedback:**
1. Langsame Erstantwort → Consent gekürzt (2 Sätze statt 4, ~5s statt ~10s TTS)
2. Keine Consent-Nachfrage → Neuer Node `node_it_consent_reask` + 3. Edge (unklar)
3. Keine Rückfrage zum Thema → Neuer Node `node_it_triage_general_v3` für "other"-Kategorie
4. Telefonnr vergessen → Summary nennt jetzt Name, Firma, Problem + Rückrufnummer
5. Kein Abschluss → Neuer `node_it_goodbye_v3` Node: Success fragt nur, Goodbye spricht Verabschiedung separat

**Agent-Config-Empfehlungen:**
- `responsiveness`: 0.8 → 0.9 (schnellere Reaktion)
- `end_call_after_silence_ms`: 30000 → 45000 (mehr Zeit vor Auto-Auflegen)

**Node-Count:** 22 → 25 (+consent_reask, +triage_general, +goodbye_v3)

### Retell Deployment
- **Agent ID (v3.0):** `agent_92cb5bd4d94f4ae5fdacc0e5b5` — ALTER Agent
- **Flow ID (v3.0):** `conversation_flow_e1777169a264` — ALTER Flow
- **Agent ID (v3.1):** `agent_769af524dac772a9b8b3234af4` — BROKEN (edges fehlen → RCA #94768)
- **Flow ID (v3.1):** `conversation_flow_cc8ac4e55c5c` — BROKEN Flow
- **Agent ID (v3.2):** `agent_b94caa0b7804477f3ac373b3ac` — VORHERIGER Agent, erstellt 03.02.2026
- **Flow ID (v3.2):** `conversation_flow_afa858be6620` — VORHERIGER Flow, 25 Nodes
- **Agent ID (v3.3):** `agent_b963b21a218e504556926ae10c` — VORHERIGER Agent, erstellt 06.02.2026 — BROKEN (classify hängt)
- **Flow ID (v3.3):** `conversation_flow_bc55e5d96752` — VORHERIGER Flow — GPT-4o-mini Klassifizierungsfehler
- **v3.3 Änderungen:** K1 neues-Problem-Edge, W4 DSGVO-Consent, W5 präzisere Extract-Descriptions, W2 boosted_keywords (20 IT-Begriffe), W3 backchannel 0.5
- **v3.3 Backend:** K2 Error-Fallback-Email, K3 use_case_detail+use_case_category in Webhook-Payload
- **Agent ID (v3.4):** `agent_0b69369919d3c91349af8b38c9` — AKTUELL, erstellt 06.02.2026
- **Flow ID (v3.4):** `conversation_flow_1e32b8300980` — AKTUELL, 27 Nodes, GPT-4o, Classify-Deadlock fix
- **v3.4 Fixes:** GPT-4o-mini→GPT-4o, use_case_category Description gehärtet, Doppel-Extraktion entfernt, Catch-All-Edge fixiert, Firma-Nachfrage-Nodes (2 neue Nodes: 27 total)
- **Status:** v3.4 deployed (06.02.2026), Backend DB konfiguriert (DB ID 181), zum Testen bereit
- **Dashboard (v3.4):** https://dashboard.retellai.com/agents/agent_0b69369919d3c91349af8b38c9

### Dateien
- Flow JSON: `claudedocs/03_API/VisionaryData/retell-agent-v3.0.json`
- Deploy Script: `claudedocs/03_API/VisionaryData/deploy-retell-v3.sh`
- Visualisierung: `public/docs/visionarydata-v3-flow.html`
- Backend: `app/Http/Controllers/ServiceDeskHandler.php` (use_case_detail + Security-Eskalation)

---

## RCA #94768 — Agent-Stille nach Namensabfrage (03.02.2026)

### Root Cause
Der Retell Conversation Flow (v3.1 Bug-Fix) hat nach Node `node_it_classify_issue_v3` eine **Sackgasse**: 
Obwohl `use_case_category="network"` korrekt extrahiert wurde, feuert keine Edge zum nächsten Node.

### Beweis-Timeline (34.3s → 45.7s = 11s Stille)
```
33.5s  → "Extract: Klassifizierung + Security-Check"
34.3s  Result: use_case_category="network"
       ——— ERWARTET: edge → node_it_triage_network_v3 ———
       ——— TATSÄCHLICH: Kein Übergang, Agent stumm ———
45.7s  User: "Hallo?"
~53s   User legt auf
```

### Wahrscheinlichste Ursache
Beim V3.1 Bug-Fix wurden Edges/Nodes versehentlich entfernt oder Conditions geändert.
V3.0 Template (`retell-agent-v3.0.json`) hat korrekte Edges für alle 7 Kategorien.

### Fix-Maßnahmen (implementiert 03.02.2026)
1. **Backend-Konfiguration**: `php artisan visionarydata:setup-agent`
   - Registriert v3.1 Agent in retell_agents
   - Setzt gateway_mode policy auf service_desk
   - Synchronisiert Company 1658 retell_agent_id
2. **Dead-End-Detection**: In `RetellWebhookController::handleCallEnded()`
   - Erkennt service_desk Calls ohne Ticket-Erstellung
   - Warnt bei: user_hangup + <90s + kein ServiceCase
3. **Flow-Health-Check**: `php artisan retell:flow-health`
   - Analysiert aktuelle Calls pro Agent
   - Erkennt Dead-End-Muster (kurze Calls ohne Tickets)
   - JSON-Output für Monitoring-Integration

### Lösung (03.02.2026)
- **NEUER Agent v3.2 erstellt** (gemäß Regel: nie bestehende Agents modifizieren)
  - Agent: `agent_b94caa0b7804477f3ac373b3ac`
  - Flow: `conversation_flow_afa858be6620` (alle 7 Classify-Edges korrekt)
  - Voice: `custom_voice_8e4c6d5a408f81563a7e5c310b` (wie v3.1)
- **Backend-DB aktualisiert**: Company 1658 → neuer Agent-ID, retell_agents registriert
- **Noch offen**: Testanruf → Agent soll nach Name Triage-Fragen stellen

### Betroffene Dateien
- `app/Console/Commands/SetupVisionaryDataAgent.php` (NEU)
- `app/Console/Commands/CheckRetellFlowHealth.php` (NEU)
- `app/Http/Controllers/RetellWebhookController.php` (Dead-End-Detection)

---

## Offene Punkte

### Warten auf VisionaryData
1. **Ticket-ID Umbau** → Sebastian baut auf `ticket_id` um (in Arbeit)
2. **Thomas-Voice** → ✅ ElevenLabs `eEmoQJhC4SAEQpCINUov` registriert und aktiv (02.02.2026)

### v3.4 Fixes (06.02.2026 - Classify-Deadlock)
- ✅ FIX-1: Model GPT-4o-mini → GPT-4o (bessere Instruktionsbefolgung + Deutsch)
- ✅ FIX-2: use_case_category Description gehärtet (exakte 7 Werte, kleingeschrieben, PFLICHTFELD)
- ✅ FIX-3: use_case_category aus node_it_extract_initial_v3 entfernt (Doppel-Extraktion)
- ✅ FIX-4: edge_classify_other als echten DEFAULT-FALLBACK umformuliert
- ✅ FIX-5: Firma-Nachfrage wenn company fehlt (2 neue Nodes: ask_company_only + extract_company)
- ✅ FIX-6: Deploy-Script Verifikation (prüft 7 Classify-Edges nach Deploy)
- ✅ DEPLOYED: Agent `agent_0b69369919d3c91349af8b38c9`, Flow `conversation_flow_1e32b8300980` (06.02.2026)
- ✅ Backend DB konfiguriert via `visionarydata:setup-agent` (DB ID 181)
- ⏳ AUSSTEHEND: Testanrufe (Netzwerk, Drucker, unklar, nur Name, Security)

### v3.4 Review (07.02.2026 — 4-Agent Team Review)
- ✅ Requirements Check: 14/15 PASS, 1 FAIL fixed (customer_company missing from ai_metadata)
- ✅ Flow JSON Inspection: 27/27 nodes, 7/7 classify-edges, 0 dead-ends, 0 orphans
- ✅ Backend Code Review: CRIT-1 fixed (customer_company in buildAiMetadata + buildPayload)
- ✅ Test Scenarios: 19 Szenarien erstellt (claudedocs/03_API/VisionaryData/testszenarien-thomas-v3.md)
- ✅ FIX applied: customer_company added to buildAiMetadata() + buildPayload() customer block
- **Status: READY FOR THOMAS TESTING** (after fix deployment)

### Erledigt (06.02.2026 - v3.3 Analyse)
- ✅ K1: Edge für "neues Problem" nach Success-Node (Flow-Template)
- ✅ K2: Error-Handler Fallback-Email bei finalize_ticket Fehler (ServiceDeskHandler)
- ✅ K3: `use_case_detail` + `use_case_category` in Webhook-Payload (WebhookOutputHandler)
- ✅ W2: Boosted Keywords für IT-Begriffe (deploy-retell-v3.sh)
- ✅ W3: Backchannel-Frequenz auf 0.5 reduziert (deploy-retell-v3.sh)
- ✅ W4: DSGVO-Consent-Wording optimiert (Flow-Template)
- ✅ W5: Extract-Descriptions in reextract-Node präzisiert (Flow-Template)

### Empfohlen (nicht zwingend)
- Dead Code: `mapPriority()` in ServiceDeskHandler kann entfernt werden (wird nie aufgerufen)
- N4: Health-Check Cron einrichten (`0 */6 * * * php artisan retell:flow-health --company=1658 --hours=6`)

### Erledigt (02.02.2026)
- ✅ Agent v3.0 deployed (unpublished, zum Testen)
- ✅ Flow-Visualisierung: `public/docs/visionarydata-v3-flow.html`
- ✅ E-Mail an Thomas gesendet (02.02.2026): `claudedocs/03_API/VisionaryData/email-thomas-v3-release.md`
- ✅ Serena Memory aktualisiert
- ✅ 9/9 Thomas-Anforderungen umgesetzt (1x manuell: Voice-Registrierung)
- ✅ v3.1 Bug-Fix: 5 Bugs aus Thomas-Testanruf behoben (02.02.2026)
- ✅ v3.1: 3 neue Nodes (consent_reask, triage_general, goodbye_v3), 3 Instruction-Updates, 3 Edge-Änderungen
- ✅ v3.1: Deploy-Script aktualisiert (responsiveness, silence timeout)
- ✅ v3.1: Flow-Visualisierung aktualisiert
- ✅ RCA #94768: Dead-End-Detection implementiert (03.02.2026)
- ✅ RCA #94768: Backend-Setup-Command erstellt (03.02.2026)
- ✅ RCA #94768: Flow-Health-Check-Command erstellt (03.02.2026)
- ✅ RCA #94768: NEUER Agent v3.2 erstellt via API (03.02.2026) — alle 7 Classify-Edges korrekt
- ✅ RCA #94768: Backend-DB konfiguriert via `visionarydata:setup-agent` (03.02.2026)

## E-Mail-Verlauf

### Antwort von Sebastian (13.01.2026)
```
Hallo Fabian,

danke für die schnelle Rückmeldung.

Ich glaube 70 parallele Anrufe sind mehr als genug, nachdem es mMn aktuell 
nur zwei Telefonkräfte geht. Wir werden damit also nicht in Engpässe geraten.

Das Error-Handling ist fein für mich. Bzgl. der Ticket-ID werden wir mit 
ticket_id gehen – ich stell das mal um.

Liebe Grüße
Sebastian
```

### E-Mail an Thomas v3.0 Release (gesendet 02.02.2026)
```
Hallo Sebastian,

perfekt, dann sind wir uns einig!

ticket_id ist auf meiner Seite bereits vorbereitet – sobald ihr
den Umbau abgeschlossen habt, können wir einen kurzen Test machen.

Bzgl. Thomas: Gib Bescheid, wenn er sich meldet.

Beste Grüße,
Fabian
```



---

## Implementierte Features

| Feature | Status | Details |
|---------|--------|---------|
| Webhook-Delivery | ✅ | HMAC-signiert, funktioniert |
| Email-Backup | ✅ | An ticket-support@visionarydata.de |
| Retry-Logik | ✅ | 3 Versuche, Backoff 1min/2min/5min |
| Admin-Alerts | ✅ | E-Mail + Slack (optional) bei permanentem Fehler |
| Ticket-ID-Extraktion | ✅ | Gehärtet am 13.01.2026 - Feldname `ticket_id` bestätigt |
| Exchange Logs | ✅ | Alle Deliveries protokolliert |
| Filament UI | ✅ | external_reference sichtbar + suchbar + filterbar |
| Unit Tests | ✅ | ExtractExternalIdTest.php mit 25+ Tests |

**Technischer Ablauf (verifiziert):**
```
HTTP POST → VisionaryData
    ↓
HTTP 2xx → SUCCESS (external_id gespeichert falls vorhanden)
    ↓
HTTP 4xx/5xx → return false → Exception → Retry nach Backoff
    ↓
Nach 3 Fehlern → failed() → Admin-Alert + Slack
```

**Wichtig:** Keine spezielle HTTP 429 Behandlung - alle HTTP-Fehler gleich behandelt |

---

## Response-Formate von VisionaryData

**Bis 07.01.2026:**
```json
{"message": "Workflow was started"}
```

**Ab 08.01.2026:**
```json
{"success": "Valid HMAC signature", "status": 200}
```

**Erwartet (sobald implementiert):**
```json
{"success": true, "ticket_id": "VD-12345"}
```

---

## Relevante Dateien

- `app/Services/ServiceGateway/OutputHandlers/WebhookOutputHandler.php` - Webhook-Delivery
- `app/Jobs/ServiceGateway/DeliverCaseOutputJob.php` - Retry-Logik
- `app/Mail/VisionaryDataBackupMail.php` - Backup-E-Mail
- `docs/service-gateway/VISIONARY_INTEGRATION_SPEC_CHECKLIST.md` - Offene Spezifikationsfragen
