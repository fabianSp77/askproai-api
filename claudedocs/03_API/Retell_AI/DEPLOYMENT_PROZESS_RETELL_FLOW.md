# Retell AI Conversation Flow - Deployment Prozess

**WICHTIG: IMMER PUBLISH AUSFÜHREN!**

## ⚠️ Kritischer Hinweis

Wenn du einen Conversation Flow updatest, **MUSS** der Agent danach published werden!

**Warum?**
- Flow Update (PATCH) aktualisiert nur die Draft-Version
- Der Agent nutzt die published Version
- **Ohne Publish bleibt der alte Flow aktiv!**

---

## Master Deployment Script

**Nutze IMMER dieses Script:**

```bash
php deploy_flow_master.php [flow_file.json] ["Beschreibung"]
```

**Beispiele:**

```bash
# Standard Deployment
php deploy_flow_master.php public/askproai_state_of_the_art_flow_2025_V12.json "V14 Telefonnummer Fix"

# Mit Default-Werten
php deploy_flow_master.php
```

---

## Was macht das Script?

### Step 1: Update Flow
```
PATCH https://api.retellai.com/update-conversation-flow/{flow_id}
```
- Updated die Flow-Konfiguration
- Nodes, Tools, Global Prompt
- Erstellt neue Draft-Version

### Step 2: Publish Agent ✅ KRITISCH!
```
POST https://api.retellai.com/publish-agent/{agent_id}
```
- Macht die Draft-Version LIVE
- Alle neuen Calls nutzen die neue Version
- Erstellt neue Draft für nächste Änderungen

---

## Manuelles Deployment (falls nötig)

### 1. Flow updaten

```bash
curl -X PATCH "https://api.retellai.com/update-conversation-flow/conversation_flow_da76e7c6f3ba" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d @flow.json
```

### 2. Agent publishen (NICHT VERGESSEN!)

```bash
curl -X POST "https://api.retellai.com/publish-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer $RETELL_TOKEN"
```

---

## Production IDs

```
Flow ID:  conversation_flow_da76e7c6f3ba
Agent ID: agent_616d645570ae613e421edb98e7
Agent Name: Conversation Flow Agent
```

**WICHTIG:** Es gibt mehrere Agents! Der RICHTIGE ist:
- **Name:** Conversation Flow Agent
- **ID:** agent_616d645570ae613e421edb98e7
- **Type:** conversation-flow (nicht retell-llm!)

**NICHT verwenden:**
- agent_9a8202a740cd3120d96fcfda1e (alter retell-llm Agent)

---

## Deployment Checklist

- [ ] Flow JSON validiert (keine Syntax-Fehler)
- [ ] Tools getestet (API Endpoints erreichbar)
- [ ] Global Prompt aktualisiert (falls nötig)
- [ ] **PATCH Flow** durchgeführt
- [ ] **POST Publish Agent** durchgeführt ✅ KRITISCH!
- [ ] Testanruf gemacht
- [ ] Deployment geloggt

---

## Häufige Fehler

### ❌ Flow updated, Agent nicht published
**Problem:** Agent nutzt alte Version
**Lösung:** `POST /publish-agent/{agent_id}` aufrufen

### ❌ 404 bei Publish
**Problem:** Falsche URL (z.B. `/v2/publish-agent/`)
**Lösung:** Richtige URL: `/publish-agent/{agent_id}` (ohne /v2)

### ❌ "Cannot POST /publish-conversation-flow"
**Problem:** Es gibt keinen publish-flow Endpoint
**Lösung:** Agent publishen, nicht den Flow!

---

## Deployment Log

Alle Deployments werden geloggt in:
```
/var/www/api-gateway/deployment_log.txt
```

Format:
```
YYYY-MM-DD HH:MM:SS - Beschreibung - Flow: xxx - Agent: xxx - Status: SUCCESS
```

---

## Quick Commands

```bash
# Deploy
php deploy_flow_master.php

# Log anzeigen
tail -20 deployment_log.txt

# Aktuellen Flow holen
curl -X GET "https://api.retellai.com/get-conversation-flow/conversation_flow_da76e7c6f3ba" \
  -H "Authorization: Bearer $RETELL_TOKEN"

# Agent Info
curl -X GET "https://api.retellai.com/get-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer $RETELL_TOKEN"
```

---

## Version History

| Version | Datum | Beschreibung | Status |
|---------|-------|--------------|--------|
| V14 | 2025-10-22 | Telefonnummer-Erkennung Final Fix | ✅ LIVE |
| V13 | 2025-10-22 | call_id Parameter hinzugefügt | ❌ Failed |
| V12 | 2025-10-22 | Global Prompt Optimierung | ✅ LIVE |

---

**Letzte Aktualisierung:** 2025-10-22
**Erstellt von:** Claude Code
**Wichtigkeit:** 🔴 KRITISCH
