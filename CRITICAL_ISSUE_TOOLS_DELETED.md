# 🚨 CRITICAL ISSUE: ALL TOOLS DELETED

**Date:** 2025-10-24 06:30
**Discovered During:** User Testanruf Analysis
**Severity:** P0 - BLOCKING

---

## 🔍 ROOT CAUSE ANALYSIS

### Was ist passiert?

Beim Deployment von V35/V36 wurden **ALLE AGENT-LEVEL KONFIGURATIONEN GELÖSCHT**:

```
BEFORE (Unknown Version):
├─ Webhooks: ✅ Configured
├─ Tools: ✅ 4+ functions
└─ Flow: ✅ conversation_flow_1607b81c8f93

AFTER (V36 deployment):
├─ Webhooks: ❌ ALL DELETED
├─ Tools: ❌ ALL DELETED
└─ Flow: ✅ Still intact (but function nodes broken!)
```

### Warum ist das kritisch?

**Ohne Tools kann der Agent KEINE Functions aufrufen:**
- ❌ `initialize_call` → Customer identification broken
- ❌ `check_availability_v17` → Can't check slots
- ❌ `book_appointment_v17` → Can't book
- ❌ Alle anderen Functions broken

**Ohne Webhooks erscheinen Calls nicht im Admin Panel:**
- ❌ `call_start_webhook_url` → Call tracking broken
- ❌ `call_end_webhook_url` → Analytics broken
- ❌ `call_analyzed_webhook_url` → Transcript storage broken

### Warum hat das deploy_friseur1_v35.php das verursacht?

**Das Script hat NUR den Conversation Flow aktualisiert:**
```php
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$flowId}",
    // ...
]);
```

**Es hat NICHT die Agent-Level Konfiguration aktualisiert!**

Beim anschließenden `publish-agent` erstellt Retell eine neue Agent Version (36 → 37 → 38), ABER:
- Nimmt nur den Flow mit
- Webhooks/Tools werden NICHT kopiert (bug oder design?)

---

## ✅ WAS WURDE BEREITS GEFIXT

### Fix 1: Webhook URL ✅

```php
$agentConfig = [
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell',
    'webhook_timeout_ms' => 10000
];

curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-agent/{$agentId}");
// HTTP 200 ✅
```

**Status:** Agent Version 38 published mit Webhook

**Result:**
- ✅ Nächste Calls werden im Admin Panel erscheinen
- ✅ call_start, call_end, call_analyzed webhooks werden gefeuert

### Fix 2: Agent Published ✅

Agent Version 38 ist live mit:
- ✅ Webhook: https://api.askproai.de/api/webhooks/retell
- ✅ Flow: conversation_flow_1607b81c8f93 (V37)
- ❌ Tools: NOCH FEHLEND

---

## ❌ WAS NOCH FEHLT

### Missing Tools

**Tool IDs in Flow (gesetzt, aber Tools existieren nicht):**
```
Function Nodes mit broken tool_ids:
├─ func_00_initialize: tool-initialize-call ❌ 404
├─ func_check_availability: tool-v17-check-availability ❌ 404
├─ func_book_appointment: tool-v17-book-appointment ❌ 404
├─ func_get_appointments: tool-get-appointments ❌ 404
├─ func_08_availability_check: tool-collect-appointment ❌ 404
├─ func_09c_final_booking: tool-collect-appointment ❌ 404
├─ func_reschedule_execute: tool-reschedule-appointment ❌ 404
└─ func_cancel_execute: tool-cancel-appointment ❌ 404
```

**Alle Tools müssen neu erstellt werden!**

---

## 🔧 LÖSUNG: Tools neu erstellen

### Problem: Keine REST API für Tools

Recherche ergab:
- ❌ Keine `/create-tool` API dokumentiert
- ❌ Keine `/list-tools` API zugänglich
- ❌ Python SDK hat keine create_tool Methode
- ✅ Tools müssen über **Retell Dashboard UI** erstellt werden

### Required Tools Configuration

**Tool 1: initialize_call**
```
Name: initialize_call
Type: Custom Function
URL: https://api.askproai.de/api/webhooks/retell/function
Description: Initialize the call and identify the customer
Parameters:
{
  "type": "object",
  "properties": {
    "phone_number": {
      "type": "string",
      "description": "Customer phone number"
    }
  },
  "required": []
}
```

**Tool 2: check_availability_v17**
```
Name: check_availability_v17
Type: Custom Function
URL: https://api.askproai.de/api/webhooks/retell/function
Description: Check appointment availability for a specific date, time and service
Speak During Execution: ✅ true
Parameters:
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string",
      "description": "Customer name"
    },
    "datum": {
      "type": "string",
      "description": "Appointment date in DD.MM.YYYY format (e.g., 24.10.2025)"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Appointment time in HH:MM format (e.g., 10:00)"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Service type (e.g., Herrenhaarschnitt, Damenhaarschnitt)"
    },
    "bestaetigung": {
      "type": "boolean",
      "description": "Whether this is a confirmation (false for initial check)"
    }
  },
  "required": ["datum", "uhrzeit", "dienstleistung"]
}
```

**Tool 3: book_appointment_v17**
```
Name: book_appointment_v17
Type: Custom Function
URL: https://api.askproai.de/api/webhooks/retell/function
Description: Book a confirmed appointment
Speak During Execution: ✅ true
Parameters:
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string",
      "description": "Customer name"
    },
    "datum": {
      "type": "string",
      "description": "Appointment date in DD.MM.YYYY format"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Appointment time in HH:MM format"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Service type"
    },
    "telefonnummer": {
      "type": "string",
      "description": "Customer phone number"
    }
  },
  "required": ["name", "datum", "uhrzeit", "dienstleistung"]
}
```

**Tool 4: get_alternatives**
```
Name: get_alternatives
Type: Custom Function
URL: https://api.askproai.de/api/webhooks/retell/function
Description: Get alternative appointment slots if requested time is not available
Parameters:
{
  "type": "object",
  "properties": {
    "datum": {
      "type": "string",
      "description": "Requested date"
    },
    "uhrzeit": {
      "type": "string",
      "description": "Requested time"
    },
    "dienstleistung": {
      "type": "string",
      "description": "Service type"
    }
  },
  "required": ["datum", "dienstleistung"]
}
```

### Steps to Create Tools

1. **Login to Retell Dashboard:** https://dashboard.retellai.com
2. **Navigate to:** Settings → Tools (or Agent → Tools section)
3. **Click:** "+ Add" → "Custom Function"
4. **For each tool above:**
   - Enter Name (exact match!)
   - Enter Description
   - Set URL: `https://api.askproai.de/api/webhooks/retell/function`
   - Configure "Speak During Execution" for check_availability_v17 and book_appointment_v17
   - Copy/paste the Parameters JSON
   - Save

5. **Important:** Tool names MUST match exactly:
   - `initialize_call` (not initialize-call!)
   - `check_availability_v17` (not check_availability!)
   - `book_appointment_v17`
   - `get_alternatives`

6. **After creating all tools:**
   - Retell auto-generates tool IDs
   - Function Nodes in Flow will auto-link if names match
   - No need to update flow manually

---

## 🧪 TESTING NACH TOOL ERSTELLUNG

### Expected Flow After Fix

```
User calls +493033081738
  ↓
Webhook fires: call_start → ✅ Appears in Admin Panel
  ↓
Agent: initialize_call → ✅ Function executes
  ↓
Conversation: Greeting, Intent, Service, DateTime
  ↓
Extract DV: dienstleistung, datum, uhrzeit
  ↓
Expression: {{datum}} exists && {{uhrzeit}} exists → ✅ Deterministic!
  ↓
Agent: check_availability_v17 → ✅ Function executes
  ↓
Result: "Verfügbar" / "Nicht verfügbar"
  ↓
Agent: book_appointment_v17 (if confirmed) → ✅ Function executes
  ↓
Webhook fires: call_end → ✅ Analytics saved
```

### Verification Checklist

- [ ] Call appears in https://api.askproai.de/admin/retell-call-sessions immediately
- [ ] `initialize_call` function shows in function traces
- [ ] `check_availability_v17` function shows in traces
- [ ] `book_appointment_v17` function shows in traces (if booking confirmed)
- [ ] Appointment created in database
- [ ] Transcript available in Admin Panel

---

## 📊 CURRENT STATUS

**Agent Version:** 38 (Published)
**Flow Version:** 37
**Webhook:** ✅ CONFIGURED
**Tools:** ❌ MISSING (must be created in Dashboard)

**Next Step:** CREATE TOOLS IN DASHBOARD

---

## 🎯 WARUM DER TESTANRUF FEHLGESCHLAGEN IST

### User's reported issues:

1. **"Er hat gesagt heute kein Termin möglich"**
   - ❌ `check_availability_v17` existiert nicht
   - Agent hat HALLUZINIERT (konnte Function nicht callen)
   - Keine echte Verfügbarkeitsprüfung

2. **"Dann 10 Uhr gesagt → verfügbar"**
   - ❌ Wieder Halluzination
   - Agent hat geraten statt zu prüfen

3. **"Call taucht nicht im Admin Panel auf"**
   - ✅ JETZT GEFIXT (Webhook konfiguriert)
   - Nächster Call wird erscheinen

### Root Cause: Alle Functions broken

```
User: "Termin heute 11 Uhr"
Agent (sollte): initialize_call() → check_availability_v17(...)
Agent (tat): Halluzination! (Tool 404)
Result: Random Antworten ohne echte Prüfung
```

---

## 🚀 DEPLOYMENT CHECKLIST FÜR ZUKUNFT

### ✅ Beim Flow Update:

1. **Save Current Agent Config First:**
   ```bash
   curl https://api.retellai.com/get-agent/{agentId} > backup_v{N}.json
   ```

2. **Update Flow:**
   ```bash
   curl PATCH /update-conversation-flow/{flowId}
   ```

3. **Verify Agent Config Still Intact:**
   ```bash
   # Check webhooks still set
   # Check tools still present
   ```

4. **If Lost → Restore:**
   ```bash
   curl PATCH /update-agent/{agentId} -d @backup_v{N}.json
   ```

5. **Then Publish:**
   ```bash
   curl POST /publish-agent/{agentId}
   ```

### ⚠️ NEVER:

- ❌ Update flow and publish without checking agent config
- ❌ Assume webhooks/tools survive publish
- ❌ Deploy without backup

---

**Status:** PARTIALLY FIXED (Webhook ✅ | Tools ❌)
**Blocker:** Tools must be created in Retell Dashboard
**ETA:** 15 minutes (manual Dashboard work)
