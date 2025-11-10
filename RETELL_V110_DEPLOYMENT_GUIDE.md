# Retell Agent V110 - Deployment Guide

**Version:** V110 Production-Ready
**Datum:** 2025-11-10
**Status:** ✅ READY FOR DEPLOYMENT

---

## 📋 Übersicht

Dieser Guide beschreibt die Schritte zum Deployment des neuen V110 Retell Conversation Flow.

**Wichtige Verbesserungen gegenüber V109:**
1. ✅ **Near-Match Logic** - Positive Formulierung bei Alternativen ±30 Min
2. ✅ **Callback Phone Collection** - Telefonnummer wird abgefragt wenn nicht vorhanden
3. ✅ **Proaktive Kundenerkennung** - check_customer() zu Beginn jedes Anrufs
4. ✅ **Silent Intent Router** - Keine ungewollte Agent-Speech bei Intent Classification
5. ✅ **Explizite Mitarbeiter-Information** - "Ich informiere unsere Mitarbeiter"

---

## 📦 Deliverables

### 1. Conversation Flow JSON
**Datei:** `conversation_flow_v110_production_ready.json`
**Größe:** 26.445 Bytes
**Nodes:** 36 (11 Function Nodes, 25 Conversation/Extract/End Nodes)
**Tools:** 11 Custom Functions

### 2. Validation Report
**Datei:** `RETELL_V110_VALIDATION_REPORT.md`
**Inhalt:** Vollständige Validierung aller Nodes, Functions, Edges und Best Practices

### 3. Deployment Guide
**Datei:** `RETELL_V110_DEPLOYMENT_GUIDE.md` (diese Datei)

---

## 🚀 Deployment Steps

### Step 1: Backend Vorbereitung

**NEU: check_customer Endpoint implementieren**

Erstelle einen neuen Endpoint unter:
```
POST /api/webhooks/retell/check-customer
```

**Request Body:**
```json
{
  "call_id": "call_abc123..."
}
```

**Expected Response:**
```json
{
  "found": true,
  "customer_name": "Max Müller",
  "customer_phone": "+491234567890",
  "customer_email": "max@example.com",
  "predicted_service": "Herrenhaarschnitt",
  "service_confidence": 0.85,
  "preferred_staff": "Maria",
  "staff_confidence": 0.90,
  "total_appointments": 12,
  "last_appointment_at": "2025-10-15"
}
```

**Logik:**
1. Extrahiere `from_number` aus Call Context via `call_id`
2. Suche Customer in DB: `Customer::where('phone', $from_number)->first()`
3. Wenn gefunden:
   - Lade letzte 10 Appointments
   - Berechne häufigsten Service (predicted_service + confidence)
   - Berechne häufigsten Staff (preferred_staff + confidence)
4. Wenn nicht gefunden: `{"found": false}`

**Implementierungszeit:** ~2-3 Stunden

---

### Step 2: Conversation Flow Upload

**Command:**
```bash
# Set API Token
export RETELL_TOKEN="key_6ff998ba48e842092e04a5455d19"

# Upload new conversation flow
curl -X POST "https://api.retellai.com/create-conversation-flow" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d @conversation_flow_v110_production_ready.json \
  | tee conversation_flow_v110_upload_response.json
```

**Expected Response:**
```json
{
  "conversation_flow_id": "conversation_flow_xyz123...",
  "version": 110,
  "created_at": "2025-11-10T12:00:00Z"
}
```

**Wichtig:** Notiere die `conversation_flow_id` aus der Response!

---

### Step 3: Agent Update

**Command:**
```bash
# Set the new flow_id from Step 2
export NEW_FLOW_ID="conversation_flow_xyz123..."
export AGENT_ID="agent_45daa54928c5768b52ba3db736"

# Update agent with new conversation flow
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "response_engine": {
      "type": "conversation-flow",
      "conversation_flow_id": "'$NEW_FLOW_ID'",
      "version": 110
    },
    "agent_name": "Friseur 1 Agent V110 - Optimal Flow"
  }' \
  | tee agent_v110_update_response.json
```

**Expected Response:**
```json
{
  "agent_id": "agent_45daa54928c5768b52ba3db736",
  "response_engine": {
    "type": "conversation-flow",
    "conversation_flow_id": "conversation_flow_xyz123...",
    "version": 110
  },
  "is_published": false
}
```

---

### Step 4: Testing (WICHTIG!)

**Testanrufe durchführen BEVOR Publishing:**

#### Test 1: Near-Match Logic
```
Szenario: Termin für 10:00 Uhr anfragen, aber nur 9:45 und 10:15 verfügbar

Expected Agent Response:
"Um 10 Uhr ist morgen schon belegt, aber ich kann Ihnen 9 Uhr 45
oder 10 Uhr 15 anbieten. Was passt Ihnen besser?"

✅ Check: "kann Ihnen anbieten" (positiv)
❌ Fehler: "ist leider nicht verfügbar" (negativ)
```

#### Test 2: Callback Phone Collection
```
Szenario: Technischer Fehler, customer_phone fehlt

Expected Agent Response:
"Es tut mir leid, es gab gerade ein technisches Problem.
Ich informiere unsere Mitarbeiter und wir rufen Sie zurück.
Unter welcher Nummer können wir Sie am besten erreichen?"

User: "0172 345 6789"

Expected Agent Response:
"Vielen Dank! Wir rufen Sie unter 0172 345 6789
innerhalb der nächsten 30 Minuten zurück."

✅ Check: Telefonnummer wurde zur Bestätigung wiederholt
✅ Check: "Ich informiere unsere Mitarbeiter" wurde gesagt
```

#### Test 3: Proaktive Kundenerkennung
```
Szenario: Anrufer ist Bestandskunde (phone in DB vorhanden)

Expected Agent Response:
"Guten Tag! Ich sehe Sie waren bereits bei uns.
Möchten Sie wieder einen [predicted_service] buchen?"

User: "Ja, morgen um 10 Uhr"

Expected Agent Response:
"Einen Moment, ich prüfe die Verfügbarkeit..."

✅ Check: Agent fragt NICHT nochmal nach Service
✅ Check: Agent verwendet predicted_service aus check_customer
```

#### Test 4: Silent Intent Router
```
Szenario: User sagt "Ich möchte einen Termin buchen"

Expected Agent Behavior:
- SILENT transition zu node_extract_booking_variables
- KEINE Antwort wie "Ich verstehe, Sie möchten..."

✅ Check: Keine ungewollte Agent-Speech
✅ Check: Direkt zu Datensammlung übergehen
```

#### Test 5: No Duplicate Questions
```
Szenario: Bestandskunde mit vollständigen Daten in check_customer

check_customer Response:
{
  "found": true,
  "customer_name": "Max Müller",
  "customer_phone": "+491234567890",
  "predicted_service": "Herrenhaarschnitt",
  "service_confidence": 0.9
}

Expected Agent Behavior:
- Fragt NICHT nach Name ✅
- Fragt NICHT nach Service ✅
- Fragt NICHT nach Telefon ✅
- Fragt NUR nach Datum/Zeit ✅
```

**Testergebnisse dokumentieren in:**
```
/var/www/api-gateway/RETELL_V110_TEST_RESULTS.md
```

---

### Step 5: Publishing

**NUR nach erfolgreichen Tests!**

```bash
# Publish agent to production
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  | tee agent_v110_publish_response.json
```

**Expected Response:**
```json
{
  "agent_id": "agent_45daa54928c5768b52ba3db736",
  "is_published": true,
  "published_at": "2025-11-10T14:00:00Z",
  "version": 110
}
```

---

## 📊 Monitoring

### Key Metrics nach Deployment

**Call Duration:**
- **Target:** <25 Sekunden durchschnittlich
- **Messung:** Retell Dashboard → Analytics → Avg Call Duration

**Booking Success Rate:**
- **Target:** >95%
- **Messung:** `SELECT COUNT(*) FROM appointments WHERE created_via = 'retell' AND created_at > NOW() - INTERVAL '24 hours'`

**Repeat Questions:**
- **Target:** 0 wiederholte Fragen nach bekannten Daten
- **Messung:** Call Transcript Analysis (manual)

**Near-Match Acceptance:**
- **Target:** >70% Kunden akzeptieren Near-Match Alternativen
- **Messung:** Transcript Analysis für Alternativen-Präsentation

**Callback Success:**
- **Target:** 100% Callbacks mit phone_number
- **Messung:** `SELECT COUNT(*) FROM callback_requests WHERE phone_number IS NULL`

---

## 🔄 Rollback Plan

**Falls Probleme auftreten:**

### Option 1: Zurück zu V109

```bash
# Get current flow ID of V109
export OLD_FLOW_ID="conversation_flow_a58405e3f67a"

# Revert agent to V109
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "response_engine": {
      "type": "conversation-flow",
      "conversation_flow_id": "'$OLD_FLOW_ID'",
      "version": 109
    }
  }'

# Publish
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN"
```

### Option 2: Hotfix erstellen

1. Identifiziere Problem-Node
2. Editiere `conversation_flow_v110_production_ready.json`
3. Upload als V110.1
4. Update Agent
5. Test
6. Publish

---

## 📝 Deployment Checklist

### Pre-Deployment
- [ ] Backend: check_customer Endpoint implementiert und getestet
- [ ] Backend: check_customer liefert korrekte Confidence Scores
- [ ] JSON: conversation_flow_v110_production_ready.json validiert
- [ ] Validation Report gelesen und verstanden

### Deployment
- [ ] Step 1: Backend ready ✅
- [ ] Step 2: Flow uploaded → flow_id notiert
- [ ] Step 3: Agent updated mit neuem flow_id
- [ ] Step 4: Alle 5 Tests erfolgreich durchgeführt
- [ ] Step 5: Agent published

### Post-Deployment
- [ ] Monitoring Dashboard überwachen (erste 2 Stunden)
- [ ] 5 Live-Testanrufe durchführen
- [ ] Call Duration Metrik prüfen (<25s Target)
- [ ] Booking Success Rate prüfen (>95% Target)
- [ ] Keine kritischen Fehler in Logs

### 24h Check
- [ ] 100+ Anrufe analysiert
- [ ] Keine Regression in Success Rate
- [ ] Near-Match Acceptance Rate gemessen
- [ ] User Feedback gesammelt
- [ ] Rollback NICHT benötigt ✅

---

## 🆘 Support & Troubleshooting

### Problem: Agent spricht beim Intent Router

**Symptom:** Agent sagt "Ich verstehe, Sie möchten..." obwohl Silent Router

**Fix:**
- Prüfe global_prompt: "KRITISCH: Du bist ein STUMMER ROUTER!" vorhanden?
- Prüfe node instruction: Explizite VERBOTEN/ERLAUBT Regeln vorhanden?

### Problem: Telefonnummer wird nicht gesammelt

**Symptom:** Callback ohne phone_number

**Fix:**
- Prüfe edge condition in node_collect_callback_phone
- Prüfe `{{customer_phone}}` Variable in node_booking_failed
- Prüfe transition zu node_collect_callback_phone vs direct zu func_request_callback

### Problem: Service wird doppelt gefragt

**Symptom:** Agent fragt nach Service obwohl predicted_service vorhanden

**Fix:**
- Prüfe check_customer Response: `service_confidence >= 0.8`?
- Prüfe node_collect_missing_booking_data instruction: "Wenn Service bekannt..." Logik vorhanden?

### Problem: Near-Match wird negativ formuliert

**Symptom:** Agent sagt "leider nicht verfügbar" statt "kann Ihnen anbieten"

**Fix:**
- Prüfe node_present_alternatives instruction
- Prüfe global_prompt: NEAR-MATCH LOGIC Sektion vorhanden?
- Backend: Prüfe ob alternatives Array `distance_minutes` Feld enthält

---

## 📞 Kontakt

Bei Fragen oder Problemen:
- **Dokumentation:** `/var/www/api-gateway/RETELL_V110_VALIDATION_REPORT.md`
- **Flow JSON:** `/var/www/api-gateway/conversation_flow_v110_production_ready.json`
- **Support:** Siehe Projekt-README

---

**Version:** V110 Production-Ready
**Erstellt:** 2025-11-10
**Deployment Status:** ⏳ PENDING DEPLOYMENT
