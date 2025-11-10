# 🚀 V62 Import Guide - KORRIGIERT

**Problem gelöst:** "read properties of undefined (reading 'nodes')"

---

## ⚠️ WICHTIG: Zwei Import-Optionen

Das Retell Dashboard hat **ZWEI verschiedene Import-Bereiche**:

### Option 1: Conversation Flow Import (EMPFOHLEN ✅)

**Wann:** Du willst nur die Conversation Logic updaten (Nodes, Tools, Prompt)

**Datei:** `retell_agent_v62_conversation_flow_only.json`

**Schritte:**
1. Dashboard öffnen: https://dashboard.retellai.com
2. Gehe zu: **"Conversation Flows"** (NICHT "Agents"!)
3. Suche: `conversation_flow_a58405e3f67a`
4. Klicke: "Import" oder "Edit"
5. Lade hoch: `retell_agent_v62_conversation_flow_only.json`
6. Speichern als neue Version

**Download:**
```
https://api.askproai.de/docs/friseur1/agents/retell_agent_v62_conversation_flow_only.json
```

---

### Option 2: Kompletter Agent Import (Alternativ)

**Wann:** Du willst einen komplett neuen Agent erstellen

**Datei:** `retell_agent_v62.json` (komplette Version)

**Problem:** Diese Datei enthält `conversation_flow` inline, was das Dashboard manchmal nicht mag.

**Besser:** Nutze Option 1 (Conversation Flow Import)

---

## ✅ Schnellste Lösung

**1. Download:**
```
https://api.askproai.de/docs/friseur1/agents/retell_agent_v62_conversation_flow_only.json
```

**2. Dashboard öffnen:**
- **NICHT** zu "Agents" gehen
- **STATTDESSEN** zu "Conversation Flows" gehen

**3. Import:**
- Suche: `conversation_flow_a58405e3f67a`
- Import die `..._conversation_flow_only.json`

**4. Agent verlinken:**
- Der Agent `agent_45daa54928c5768b52ba3db736` sollte automatisch auf die neue Flow-Version verweisen

---

## 🔍 Was ist der Unterschied?

### `retell_agent_v62.json` (Kompletter Agent)
```json
{
  "agent_name": "...",
  "agent_id": "...",
  "conversation_flow": {
    "nodes": [...],
    "tools": [...]
  }
}
```
→ Dashboard mag diese Struktur NICHT beim Import

### `retell_agent_v62_conversation_flow_only.json` (Nur Flow)
```json
{
  "nodes": [...],
  "tools": [...],
  "global_prompt": "..."
}
```
→ Das mag das Dashboard! ✅

---

## 🎯 Zusammenfassung

**Download:** [conversation_flow_only.json](https://api.askproai.de/docs/friseur1/agents/retell_agent_v62_conversation_flow_only.json)

**Import Location:** Dashboard → Conversation Flows → conversation_flow_a58405e3f67a

**Erwartung:** Neue Version (sollte V64 werden), 32 Nodes, optimierte Timeouts

---

**Problem gelöst!** ✅
