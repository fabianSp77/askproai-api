# E2E Dokumentation: Friseur 1

## Schnellstart

### 1. Production URL

Die Dokumentation ist live verfügbar unter:

**🌐 https://api.askproai.de/docs/e2e/index.html**

### 2. Lokale Anzeige

Die Dokumentation ist vollständig offline-fähig (keine CDN-Abhängigkeiten):

```bash
cd docs/e2e
python3 -m http.server 8000
# Öffne: http://localhost:8000/index.html
```

**Oder direkt im Browser:**
```bash
open docs/e2e/index.html  # macOS
xdg-open docs/e2e/index.html  # Linux
start docs/e2e/index.html  # Windows
```

### 2. Konfiguration anpassen

Kopiere `config.sample.yaml` und ersetze Platzhalter:

```bash
cp config.sample.yaml config.yaml
nano config.yaml

# Ersetze:
# {{COMPANY_ID}} → Echte UUID aus DB
# {{BRANCH_*_UUID}} → Echte Branch UUIDs
# {{CALCOM_USER_*}} → Cal.com User IDs nach Mapping
```

### 3. Tests ausführen

```bash
# ID-Konsistenz prüfen (GATE 0)
scripts/e2e/verify-ids.sh

# API-Tests (GATE 2-6)
cd docs/e2e
bash curl.sh
```

---

## Verzeichnisstruktur

```
docs/e2e/
├── index.html              # Interaktives Dashboard mit 10 Mermaid-Diagrammen
├── e2e.md                  # Vollständige technische Spezifikation
├── config.sample.yaml      # Konfigurations-Template
├── curl.sh                 # API-Testskript (5 Szenarien)
├── README.md               # Diese Datei
├── assets/
│   └── mermaid.min.js     # Mermaid (offline, 2.8 MB)
├── examples/
│   ├── booking_created.json
│   ├── booking_rescheduled.json
│   ├── cancel_blocked.json
│   └── anonymous_caller.json
├── ADR/
│   ├── 001-sot-strategy.md
│   ├── 002-policy-enforcement.md
│   ├── 003-component-services.md
│   └── 004-billing.md
└── audit/
    ├── report.md           # Ist-Soll-Analyse
    ├── findings.json       # Maschinenlesbare Befunde
    └── gaps.yaml           # Priorisierte Gap-Liste

scripts/e2e/
├── verify-ids.sh           # GATE 0: ID-Konsistenz prüfen
├── generate-diagrams.js    # Mermaid-Diagramme aus YAML generieren
├── audit.sh                # Vollständiger System-Audit
├── clone-company.sh        # Template-Rollout für neues Unternehmen
└── sync-platform-calcom.sh # Bidirektionaler Sync
```

---

## Verwendung der Diagramme

### A. C4-Kontextdiagramm
Zeigt alle beteiligten Systeme und deren Kommunikation.

**Verwendung:**
- Onboarding neuer Team-Mitglieder
- System-Übersicht für Stakeholder
- Architektur-Reviews

### B. E2E Happy Path
Erfolgreicher Buchungspfad von Anruf bis Bestätigung.

**Verwendung:**
- Understanding des Hauptflows
- Debugging von Buchungsproblemen
- Integration-Tests

### C. Alternativen
Fehlerbehandlung: Storno, Policy-Blocks, No-Show, CLIR.

**Verwendung:**
- Edge-Case-Testing
- Support-Dokumentation
- Error-Handling-Reviews

### D. Conversational Decision Tree
Intent-Erkennung und NLU-Fallback.

**Verwendung:**
- Retell.ai Agent-Optimierung
- Conversation-Flow-Design
- NLU-Training

### E. Middleware-Orchestrierung
Webhooks, Idempotenz, Retry-Logic, Mappings.

**Verwendung:**
- Debugging von Integrationsproblemen
- Performance-Optimierung
- Reliability-Engineering

### F. Cal.com Sequence
API-Integration: Availability → Booking → Webhooks.

**Verwendung:**
- Cal.com API-Debugging
- Webhook-Troubleshooting
- Integration-Tests

### G. Billing Flow
Sekundengenaue Kostenberechnung.

**Verwendung:**
- Billing-Transparenz
- Pricing-Modell-Änderungen
- Customer-Support (Kosten-Anfragen)

### H. Telemetrie & Logging
Call-ID-Korrelation, Metriken, Privacy.

**Verwendung:**
- Debugging von Calls
- Analytics & Reporting
- DSGVO-Compliance-Audits

### I. Zustandsautomat
Lifecycle eines Leads/Kunden.

**Verwendung:**
- Customer-Journey-Analyse
- State-Machine-Implementierung
- Workflow-Optimierung

### J. ER-Diagramm
Datenmodell: Company → Branch → Service → Staff → Customer → Appointment.

**Verwendung:**
- Datenbankschema-Verständnis
- Migration-Planning
- API-Design

---

## Troubleshooting

### Problem: Mermaid-Diagramme rendern nicht

**Lösung 1:** Browser-Cache leeren
```bash
# Chrome DevTools: Cmd+Shift+R (macOS) oder Ctrl+Shift+R (Windows)
```

**Lösung 2:** Mermaid.js neu herunterladen
```bash
curl -L -o docs/e2e/assets/mermaid.min.js https://unpkg.com/mermaid@10.6.1/dist/mermaid.min.js
```

**Lösung 3:** Browser-Console prüfen
- Öffne DevTools (F12)
- Suche nach JavaScript-Fehlern
- Häufig: CORS-Fehler (verwende lokalen Server!)

---

### Problem: `verify-ids.sh` schlägt fehl

**Fehler:** "Staff ohne Cal.com User ID"

**Lösung:**
```bash
# 1. Cal.com Team Members API abfragen
curl -s "https://api.cal.com/v2/teams/34209/members" \
  -H "Authorization: Bearer $CALCOM_API_KEY" \
  | jq '.data[] | {name, email, id}'

# 2. Manuelle Zuordnung in DB
psql -c "UPDATE staff SET calcom_user_id = 12345 WHERE email = 'emma.williams@friseur1.de'"

# 3. Erneut prüfen
scripts/e2e/verify-ids.sh
```

---

### Problem: API-Tests schlagen fehl

**Fehler:** "401 Unauthorized"

**Lösung:**
```bash
# API_TOKEN setzen
export API_TOKEN="your_api_token_here"

# Test erneut ausführen
cd docs/e2e
bash curl.sh
```

**Fehler:** "Cal.com Timeout"

**Lösung:**
```bash
# Cal.com API-Erreichbarkeit prüfen
curl -I https://api.cal.com/v2/health

# Circuit Breaker prüfen
psql -c "SELECT * FROM circuit_breaker_state WHERE service = 'calcom'"
```

---

## Deployment

### Production Deployment

1. **Branch mergen:**
   ```bash
   git checkout develop
   git merge feature/e2e-friseur1
   git push origin develop
   ```

2. **Dokumentation deployen:**
   ```bash
   # Rsync zu Webserver
   rsync -avz docs/e2e/ deploy@server:/var/www/docs/e2e/

   # Oder via CI/CD (siehe .github/workflows/)
   ```

3. **URL prüfen:**
   ```
   https://api.askproai.de/docs/e2e/index.html
   ```

---

## Wartung

### Diagramme aktualisieren

Wenn Konfiguration ändert (z.B. neue Filiale):

```bash
# 1. config.sample.yaml anpassen
nano docs/e2e/config.sample.yaml

# 2. Diagramme neu generieren
node scripts/e2e/generate-diagrams.js

# 3. Lokal testen
open docs/e2e/index.html

# 4. Committen
git add docs/e2e/
git commit -m "docs: update E2E diagrams for new branch"
```

### Neue Services hinzufügen

```bash
# 1. Service in DB anlegen (via Filament oder Migration)
# 2. config.sample.yaml aktualisieren
# 3. verify-ids.sh ausführen
scripts/e2e/verify-ids.sh

# 4. Wenn erfolgreich: Diagramme neu generieren
node scripts/e2e/generate-diagrams.js
```

---

## Support

**Fragen zur Dokumentation:**
- GitHub Issues: https://github.com/fabianSp77/askproai-api/issues
- Slack: #team-devops

**Fragen zur Implementierung:**
- Siehe `e2e.md` für Details
- Siehe `audit/gaps.yaml` für bekannte Issues

---

*Letzte Aktualisierung: 2025-11-03*
*Verantwortlich: DevOps Team*
