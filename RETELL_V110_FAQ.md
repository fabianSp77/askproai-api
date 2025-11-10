# Retell Agent V110 - FAQ & Glossary

**Version:** V110 Production-Ready
**Datum:** 2025-11-10
**Zielgruppe:** Alle Stakeholders

---

## 📖 Table of Contents

1. [Frequently Asked Questions](#frequently-asked-questions)
   - [Deployment & Setup](#deployment--setup)
   - [Features & Functionality](#features--functionality)
   - [Troubleshooting & Support](#troubleshooting--support)
   - [Performance & Optimization](#performance--optimization)
2. [Technical Glossary](#technical-glossary)
3. [Common Misconceptions](#common-misconceptions)
4. [Quick Reference Card](#quick-reference-card)

---

## ❓ Frequently Asked Questions

### Deployment & Setup

#### Q1: Wie lange dauert das V110 Deployment?

**A:** Mit vorhandenem Backend: **10 Minuten**
- Flow Upload: 1 Minute
- Agent Update: 30 Sekunden
- Testing (5 Tests): 5 Minuten
- Publishing: 10 Sekunden
- Monitoring Setup: 3 Minuten

**Ohne Backend (check_customer Endpoint fehlt): 3-5 Stunden**
- Backend Implementation: 2-3 Stunden
- Testing: 30-60 Minuten
- Deployment: 10 Minuten (siehe oben)

#### Q2: Muss ich einen neuen Agent erstellen oder kann ich den bestehenden updaten?

**A:** Der **bestehende Agent** (`agent_45daa54928c5768b52ba3db736`) kann einfach **geupdatet** werden:

```bash
# Update mit neuem Flow
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "response_engine": {
      "type": "conversation-flow",
      "conversation_flow_id": "'$NEW_FLOW_ID'",
      "version": 110
    }
  }'
```

**Neuen Agent erstellen ist NICHT notwendig** (aber möglich falls gewünscht).

#### Q3: Was passiert mit dem alten V109 Flow nach Deployment?

**A:** Der alte Flow **bleibt erhalten** in Retell:
- Flow ID: `conversation_flow_a58405e3f67a`
- Status: Inactive (nicht mehr vom Agent verwendet)
- Rollback möglich durch Agent-Update auf alte Flow ID

**Best Practice:** Behalte alte Flow ID für Rollback-Szenarien.

#### Q4: Muss ich alle 11 Backend Endpoints implementieren?

**A:** **Nein**, nur **1 NEUER** Endpoint:
- ✅ **NEU in V110:** `/api/webhooks/retell/check-customer`

Die anderen 10 Endpoints existieren bereits:
- ✅ initialize-context
- ✅ collect-appointment-info
- ✅ check-availability
- ✅ present-alternatives
- ✅ start-booking
- ✅ confirm-booking
- ✅ cancel-appointment
- ✅ reschedule-appointment
- ✅ provide-info
- ✅ request-callback

**Test ob vorhanden:**
```bash
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test"}'
# 404 = noch nicht implementiert
# 200 = bereits vorhanden
```

#### Q5: Wie teste ich V110 OHNE Production zu beeinflussen?

**A:** **Option 1: Unpublished Testing**
```bash
# 1. Update Agent aber NICHT publishen
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" ...
# (SKIP curl -X POST ".../publish-agent/...")

# 2. Test via Retell Dashboard
# → Agents → agent_45d... → Test Call
# Agent nutzt V110, aber Production nutzt noch V109

# 3. Wenn Tests erfolgreich: Publish
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID" ...
```

**Option 2: Clone Agent (Preferred)**
```bash
# 1. Clone agent with new name
curl -X POST "https://api.retellai.com/create-agent" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "agent_name": "Friseur 1 Agent V110 - STAGING",
    "response_engine": {
      "type": "conversation-flow",
      "conversation_flow_id": "'$NEW_FLOW_ID'"
    },
    "voice_id": "cartesia-Lina",
    "language": "de-DE"
  }'

# 2. Assign different phone number to staging agent
# 3. Test thoroughly
# 4. If success: Update production agent
```

---

### Features & Functionality

#### Q6: Wie funktioniert die Near-Match Logic genau?

**A:** Near-Match Logic verwendet eine **±30 Minuten Schwelle**:

**Beispiel 1: Near-Match (POSITIVE Formulierung)**
```
User Request: 10:00 Uhr
Available: 09:45 (distance: -15 min)
Agent: "Um 10 Uhr ist schon belegt, aber ich kann Ihnen 9:45 anbieten."
✅ Betont was MÖGLICH ist
```

**Beispiel 2: Far-Match (NEUTRALE Formulierung)**
```
User Request: 10:00 Uhr
Available: 08:00 (distance: -120 min)
Agent: "Um 10 Uhr ist leider nicht verfügbar. Ich habe 8 Uhr oder 14 Uhr..."
⚠️ Neutral, da Alternativen weit entfernt
```

**Backend Implementation:**
```json
{
  "available": false,
  "alternatives": [
    {
      "time": "09:45",
      "distance_minutes": -15,  // Wichtig für Near-Match Logik
      "staff_name": "Maria"
    }
  ]
}
```

#### Q7: Wann wird nach Telefonnummer gefragt?

**A:** Telefonnummer wird **NUR bei Callbacks gefragt** wenn:
1. Booking fehlgeschlagen (technischer Fehler)
2. **UND** `{{customer_phone}}` Variable ist leer

**Flow:**
```
node_booking_failed
  ↓
node_collect_callback_phone
  ↓
IF {{customer_phone}} EXISTS:
  → SILENT transition zu func_request_callback

IF {{customer_phone}} FEHLT:
  → "Unter welcher Nummer können wir Sie erreichen?"
  → [User gibt Nummer]
  → "Wir rufen Sie unter [number] zurück."
  → func_request_callback
```

**Telefonnummer wird NICHT gefragt wenn:**
- check_customer hat phone zurückgegeben
- User hat phone bereits im Dialog genannt
- Booking erfolgreich (kein Callback nötig)

#### Q8: Was ist der Unterschied zwischen start_booking und confirm_booking?

**A:** **Two-Step Booking Pattern** für bessere UX:

**Step 1: start_booking (SCHNELL, <500ms)**
```json
{
  "name": "start_booking",
  "purpose": "Fast validation",
  "checks": [
    "Service exists?",
    "Time slot still available?",
    "Staff available?",
    "No scheduling conflicts?"
  ],
  "action": "RESERVE slot temporarily (5 min hold)",
  "response": "Ich buche den Termin für Sie..." (Agent spricht während Execution)
}
```

**Step 2: confirm_booking (LANGSAM, 4-5s)**
```json
{
  "name": "confirm_booking",
  "purpose": "Actual booking creation",
  "actions": [
    "Create appointment in database",
    "Sync to Cal.com",
    "Send confirmation email",
    "Send SMS",
    "Update cache"
  ],
  "response": "Fertig! Ihr Termin ist gebucht." (Agent bestätigt)
}
```

**Warum getrennt?**
- **Agent kann sprechen** während start_booking läuft (bessere UX)
- **Schnelle Validierung** reduziert Wartezeit
- **Transaktionale Sicherheit:** Rollback möglich zwischen Steps

#### Q9: Wie funktioniert Proaktive Kundenerkennung?

**A:** Proaktive Kundenerkennung nutzt **check_customer()** zu Beginn:

**Flow:**
```
Call Start
  ↓
func_initialize_context (get current_date, current_time)
  ↓
func_check_customer (check if known customer)
  ↓
node_greeting (personalized based on check_customer result)
```

**Personalisierte Begrüßung:**
```
IF found=true AND service_confidence >= 0.8:
  "Guten Tag! Ich sehe Sie waren bereits bei uns.
   Möchten Sie wieder einen [predicted_service] buchen?"

IF found=true AND service_confidence < 0.8:
  "Guten Tag! Schön dass Sie wieder anrufen.
   Wie kann ich Ihnen heute helfen?"

IF found=false:
  "Willkommen bei Friseur 1!
   Wie kann ich Ihnen helfen?"
```

**Datennutzung:**
- **customer_name:** Pre-fill, NICHT nochmal fragen
- **customer_phone:** Pre-fill, NICHT nochmal fragen
- **predicted_service (confidence >= 0.8):** Pre-fill, NICHT nochmal fragen
- **preferred_staff:** Suggest as default

#### Q10: Spricht der Agent bei JEDEM Node?

**A:** **Nein**, nur bei **Conversation Nodes**:

**Agent SPRICHT:**
- ✅ Conversation Nodes (node_greeting, node_collect_missing_booking_data, etc.)
- ✅ Function Nodes mit `speak_during_execution: true`

**Agent SCHWEIGT:**
- ❌ Function Nodes mit `speak_during_execution: false` (func_initialize_context, func_check_customer)
- ❌ Intent Router (KRITISCH: STUMMER ROUTER)
- ❌ Extract Dynamic Variables Nodes (silent data collection)

**Beispiel:**
```json
{
  "id": "func_initialize_context",
  "speak_during_execution": false,  // ← Agent schweigt
  "wait_for_result": true
}

{
  "id": "func_check_availability",
  "speak_during_execution": true,  // ← Agent sagt "Einen Moment..."
  "acknowledgement_message": "Einen Moment, ich prüfe die Verfügbarkeit."
}
```

---

### Troubleshooting & Support

#### Q11: Agent sagt "Es gab ein technisches Problem" - was nun?

**A:** **Systematische Diagnose:**

**Step 1: Welche Function failed?**
```bash
# Check Laravel logs
grep "Function call failed" /var/www/api-gateway/storage/logs/laravel.log | tail -10
```

**Step 2: Test Function direkt**
```bash
# Beispiel: check_availability failed
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-availability" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test", "datum": "2025-11-11", "uhrzeit": "10:00"}' \
  -v
```

**Häufige Ursachen:**
- **500 Error:** Backend Code Bug → Check Laravel logs
- **Timeout:** Function zu langsam (>15s) → Optimize oder increase timeout_ms
- **404 Error:** Endpoint nicht implementiert → Implement missing endpoint
- **401 Error:** Authorization failed → Check X-Retell-Signature

**Step 3: Check Retell Dashboard**
```
Retell Dashboard → Calls → [Latest Call] → Timeline
→ Siehe welche Function failed und warum
```

#### Q12: Wie rollback ich zu V109 wenn V110 Probleme hat?

**A:** **Rollback in 2 Minuten:**

```bash
# 1. Get old flow ID
export OLD_FLOW_ID="conversation_flow_a58405e3f67a"
export AGENT_ID="agent_45daa54928c5768b52ba3db736"
export RETELL_TOKEN="key_6ff998ba48e842092e04a5455d19"

# 2. Update agent to old flow
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

# 3. Publish
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN"

# 4. Verify
curl -X GET "https://api.retellai.com/get-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  | jq '{version: .response_engine.version, is_published}'
# Should show: {"version": 109, "is_published": true}
```

**Downtime:** ~10 Sekunden (während Publish)

#### Q13: Wo finde ich Call Transcripts für Debugging?

**A:** **3 Möglichkeiten:**

**Option 1: Retell Dashboard** (Preferred)
```
1. Retell Dashboard → Calls
2. Filter by date/phone number
3. Click auf Call → See full transcript + timeline
```

**Option 2: Retell API**
```bash
# Get recent calls
curl -X GET "https://api.retellai.com/list-calls?limit=10" \
  -H "Authorization: Bearer $RETELL_TOKEN"

# Get specific call
curl -X GET "https://api.retellai.com/get-call/$CALL_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  | jq '.transcript'
```

**Option 3: Backend Logs** (Limited)
```bash
# Backend logs haben nur Function Call Payloads
grep "Retell webhook" /var/www/api-gateway/storage/logs/laravel.log
```

#### Q14: Wie teste ich EINZELNE Nodes ohne ganzen Flow?

**A:** **Nicht direkt möglich**, aber Workarounds:

**Option 1: Test via Retell Dashboard**
```
1. Dashboard → Agent → Test Call
2. Sage genau was den Node triggert
3. Check Timeline welcher Node aktiviert wurde
```

**Option 2: Test Backend Functions direkt**
```bash
# Test einzelne Function
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_123",
    "from_number": "+491234567890"
  }' | jq '.'
```

**Option 3: Create Test Flow** (Nur für kritische Nodes)
```json
{
  "conversation_flow_id": "test_flow_single_node",
  "nodes": [
    {
      "id": "start",
      "type": "conversation",
      "instruction": "Sage: Test startet",
      "edges": [{"destination_node_id": "node_under_test"}]
    },
    {
      "id": "node_under_test",
      // ... Node zu testen
    }
  ]
}
```

---

### Performance & Optimization

#### Q15: Warum ist mein Call Duration noch >25s?

**A:** **Systematische Optimierung:**

**Schritt 1: Identifiziere Bottleneck**
```bash
# Check Retell Timeline in Dashboard
Retell Dashboard → Calls → [Latest] → Timeline
→ Siehe welche Function am längsten dauert
```

**Häufige Bottlenecks:**

**1. check_availability zu langsam (>5s)**
```bash
# Test direkt
time curl -X POST ".../check-availability" -d '...'

# Optimization: Redis Cache
# Siehe: RETELL_V110_ARCHITECTURE.md Section "Performance"
```

**2. Agent zu verbose (viel Text)**
```
# Update global_prompt: KURZ & PRÄGNANT sprechen
❌ "Vielen Dank für Ihre Anfrage. Lassen Sie mich..."
✅ "Einen Moment."
```

**3. Doppelte Fragen (Wiederholt bekannte Daten)**
```
# Check check_customer wird genutzt
# Wenn service_confidence >= 0.8: Service NICHT nochmal fragen
```

**Schritt 2: Measure Impact**
```bash
# Before optimization
mysql -e "SELECT AVG(duration_seconds) FROM calls WHERE created_at > NOW() - INTERVAL 1 HOUR;"

# Make changes

# After optimization (wait 1 hour for data)
mysql -e "SELECT AVG(duration_seconds) FROM calls WHERE created_at > NOW() - INTERVAL 1 HOUR;"
```

#### Q16: Wie skaliert V110 bei hohem Call-Volumen?

**A:** **Skalierungsstrategie:**

**Current Capacity:**
- **Concurrent Calls:** 50-100 (abhängig von Backend)
- **Backend Bottleneck:** Database queries + Cal.com API

**Optimizations:**

**1. Redis Caching (Quick Win)**
```php
// Cache availability for 5 minutes
$cacheKey = "availability:{$date}:{$service}";
$availability = Cache::remember($cacheKey, 300, function() {
    return $this->calcomService->getAvailability($date, $service);
});
```
**Impact:** 80% reduction in Cal.com API calls

**2. Database Connection Pooling**
```php
// config/database.php
'connections' => [
    'pgsql' => [
        'max_connections' => 100,  // Increase pool
        'pool_size' => 20
    ]
]
```

**3. Queue Workers (Async)**
```php
// Move slow operations to queue
dispatch(new SendConfirmationEmailJob($appointment));
dispatch(new SyncToCalcomJob($appointment));
```

**4. Load Balancer (Horizontal Scaling)**
```
     Load Balancer
          |
    ------+------
    |           |
Backend 1    Backend 2
```

**Projected Capacity:**
- With Optimizations: **500+ concurrent calls**
- With Load Balancer: **2000+ concurrent calls**

#### Q17: Welche Monitoring Metriken sind wichtig?

**A:** **4 Kritische Metriken:**

**1. Call Duration (Target: <25s)**
```sql
SELECT
  DATE(created_at) as date,
  AVG(duration_seconds) as avg_duration,
  MAX(duration_seconds) as max_duration
FROM calls
WHERE created_via = 'retell'
GROUP BY DATE(created_at)
ORDER BY date DESC
LIMIT 7;
```

**2. Booking Success Rate (Target: >95%)**
```sql
SELECT
  COUNT(*) as total_bookings,
  SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as successful,
  ROUND(SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM appointments
WHERE created_via = 'retell'
AND created_at > NOW() - INTERVAL 24 HOUR;
```

**3. Function Call Error Rate (Target: <1%)**
```sql
SELECT
  function_name,
  COUNT(*) as total_calls,
  SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) as errors,
  ROUND(SUM(CASE WHEN status='error' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as error_rate
FROM function_call_logs
WHERE created_at > NOW() - INTERVAL 24 HOUR
GROUP BY function_name;
```

**4. Customer Recognition Rate (Target: >80%)**
```sql
SELECT
  COUNT(*) as total_calls,
  SUM(CASE WHEN customer_found=true THEN 1 ELSE 0 END) as recognized,
  ROUND(SUM(CASE WHEN customer_found=true THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as recognition_rate
FROM calls
WHERE created_at > NOW() - INTERVAL 24 HOUR;
```

**Dashboard Setup:**
```bash
# Use Grafana/Kibana/Custom Dashboard
# Refresh: Every 5 minutes
# Alerts: If success_rate < 90% OR avg_duration > 30s
```

---

## 📚 Technical Glossary

### Retell.ai Concepts

**Agent**
> Retell.ai entity that manages voice conversations. Contains configuration, voice settings, and reference to conversation flow.

**Conversation Flow**
> Node-based graph defining conversation logic. Contains nodes, edges, tools, and global prompt.

**Node**
> Single step in conversation flow. Types: Conversation (dialog), Function (API call), Extract (data collection), End (termination).

**Edge**
> Transition between nodes. Types: prompt (user message condition), equation (variable check), always (unconditional).

**Tool / Custom Function**
> External API endpoint called by Function Nodes. Returns data used in conversation.

**Global Prompt**
> Instructions applied to ALL nodes in conversation flow. Contains rules, guidelines, personality traits.

**Parameter Mapping**
> Maps conversation variables to function parameters. Uses {{variable}} syntax.

**speak_during_execution**
> Config for Function Nodes: if true, agent speaks acknowledgement message during API call.

**wait_for_result**
> Config for Function Nodes: if true, agent waits for function result before continuing.

---

### V110 Specific Terms

**Near-Match Logic**
> Positive framing for alternatives ±30 minutes from requested time.
> Example: "Um 10 Uhr ist belegt, aber ich kann Ihnen 9:45 anbieten."

**Silent Intent Router**
> Special node that classifies user intent WITHOUT speaking. Only transitions.

**Two-Step Booking**
> Pattern: start_booking (fast validation) → confirm_booking (slow execution)

**Proaktive Kundenerkennung**
> check_customer() at call start to recognize returning customers and pre-fill data.

**Smart Service Prediction**
> Using customer history to predict most likely service (with confidence score).

**Conditional Phone Collection**
> Only ask for phone number if {{customer_phone}} variable is empty.

**Explizite Mitarbeiter-Information**
> "Ich informiere unsere Mitarbeiter" statement in callback scenarios.

---

### Backend Terms

**Laravel Route**
> URL endpoint that maps to controller method. Example: `/api/webhooks/retell/check-customer`

**Controller**
> PHP class handling HTTP requests. Example: `RetellFunctionCallHandler.php`

**Service Class**
> Business logic layer. Example: `AppointmentCreationService.php`

**Job (Queue)**
> Async background task. Example: `SyncToCalcomJob.php`

**Middleware**
> HTTP request interceptor. Example: `ValidateRetellCallId.php`

**Cache (Redis)**
> In-memory data store for fast access. Used for availability, configuration.

**Database Migration**
> Version-controlled database schema change. Example: `2025_11_10_create_customers_table.php`

---

### Metrics & KPIs

**Call Duration**
> Average time from call start to call end. Target: <25 seconds for V110.

**Booking Success Rate**
> Percentage of calls resulting in confirmed appointment. Target: >95%.

**Customer Recognition Rate**
> Percentage of calls where check_customer found existing customer. Target: >80%.

**Function Error Rate**
> Percentage of function calls returning error. Target: <1%.

**Near-Match Acceptance**
> Percentage of users accepting ±30 min alternatives. Target: >70%.

---

## ❌ Common Misconceptions

### Misconception 1: "Ich muss einen komplett neuen Agent erstellen"

**FALSCH.** Du kannst den **bestehenden Agent updaten**:
```bash
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" ...
```

**RICHTIG:** Agent kann einfach mit neuem conversation_flow_id aktualisiert werden.

---

### Misconception 2: "Silent Intent Router bedeutet agent_config.silence = true"

**FALSCH.** Es gibt **kein** `silence` Feld in Agent Config.

**RICHTIG:** "Silent" wird durch **node instruction** erzwungen:
```json
{
  "instruction": {
    "type": "prompt",
    "text": "KRITISCH: Du bist ein STUMMER ROUTER! VERBOTEN: ❌ Irgendwas antworten"
  }
}
```

---

### Misconception 3: "Near-Match funktioniert automatisch wenn Backend alternatives zurückgibt"

**FALSCH.** Near-Match benötigt:
1. **Backend:** `distance_minutes` Feld in alternatives
2. **Flow:** node_present_alternatives mit NEAR-MATCH LOGIC instruction
3. **Global Prompt:** NEAR-MATCH LOGIC Sektion

**RICHTIG:** Near-Match ist **Multi-Layer Feature** (Backend + Flow + Prompt).

---

### Misconception 4: "speak_during_execution = false bedeutet Agent spricht niemals"

**FALSCH.** `speak_during_execution` gilt **nur** während Function Execution.

**RICHTIG:**
```json
{
  "id": "func_check_availability",
  "speak_during_execution": true,  // Agent spricht WÄHREND API call
  "acknowledgement_message": "Einen Moment...",
  "edges": [
    {
      "destination_node_id": "node_present_results"  // Agent spricht NACH API call
    }
  ]
}
```

Agent spricht in `node_present_results` auch wenn `speak_during_execution = false`.

---

### Misconception 5: "V110 erfordert Database Schema Changes"

**FALSCH** (meistens). V110 nutzt **bestehende** tables:
- `customers` (für check_customer)
- `appointments` (für booking)
- `services` (für service lookup)

**RICHTIG:** Nur **1 optionales** Schema Change:
```sql
-- Optional: Track customer recognition
ALTER TABLE calls ADD COLUMN customer_found BOOLEAN DEFAULT false;
```

---

### Misconception 6: "Ich muss alle 36 Nodes verstehen um V110 zu deployen"

**FALSCH.** Für Deployment reicht Verständnis von:
- 3 kritischen Features (Near-Match, Callback Phone, Customer Recognition)
- 5 Test Cases
- Rollback Procedure

**RICHTIG:** Tiefes Verständnis nur für **Customization** nötig, nicht für Deployment.

---

### Misconception 7: "wait_for_result = true verlangsamt den Call"

**TEILWEISE RICHTIG.** Es addiert Function Latency, **ABER:**
- Notwendig wenn Result benötigt wird (z.B. current_date)
- Ohne wait_for_result: {{variable}} ist undefined
- Besser: Slow Function optimieren, nicht wait_for_result entfernen

**RICHTIG:** `wait_for_result` ist **notwendig für Dependencies**, Optimization liegt im Backend.

---

## 🎯 Quick Reference Card

### Deployment Commands

```bash
# 1. Upload Flow
curl -X POST "https://api.retellai.com/create-conversation-flow" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -H "Content-Type: application/json" \
  -d @conversation_flow_v110_production_ready.json

# 2. Update Agent
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  -d '{"response_engine": {"conversation_flow_id": "'$FLOW_ID'", "version": 110}}'

# 3. Publish
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN"
```

### Essential Verifications

```bash
# Check agent version
curl -s -X GET "https://api.retellai.com/get-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  | jq '.response_engine.version'

# Check agent published status
curl -s -X GET "https://api.retellai.com/get-agent/$AGENT_ID" \
  -H "Authorization: Bearer $RETELL_TOKEN" \
  | jq '.is_published'

# Test check_customer endpoint
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test", "from_number": "+491234567890"}'
```

### Critical Metrics

```bash
# Call duration (last 24h)
mysql -e "SELECT AVG(duration_seconds) FROM calls WHERE created_at > NOW() - INTERVAL 24 HOUR;"

# Booking success rate (last 24h)
mysql -e "SELECT ROUND(SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate FROM appointments WHERE created_at > NOW() - INTERVAL 24 HOUR;"

# Function errors (last 1h)
grep "Function call failed" /var/www/api-gateway/storage/logs/laravel.log | wc -l
```

### Rollback

```bash
# Back to V109
export OLD_FLOW_ID="conversation_flow_a58405e3f67a"
curl -X PATCH "https://api.retellai.com/update-agent/$AGENT_ID" \
  -d '{"response_engine": {"conversation_flow_id": "'$OLD_FLOW_ID'"}}'
curl -X POST "https://api.retellai.com/publish-agent/$AGENT_ID"
```

---

## 📖 Documentation Links

- **Quick Start:** `RETELL_V110_QUICK_START.md`
- **Deployment Guide:** `RETELL_V110_DEPLOYMENT_GUIDE.md`
- **API Reference:** `RETELL_V110_API_REFERENCE.md`
- **Architecture:** `RETELL_V110_ARCHITECTURE.md`
- **Troubleshooting:** `RETELL_V110_TROUBLESHOOTING.md`
- **Validation Report:** `RETELL_V110_VALIDATION_REPORT.md`
- **Executive Summary:** `RETELL_V110_EXECUTIVE_SUMMARY.md`

---

**Version:** V110 Production-Ready
**Letzte Aktualisierung:** 2025-11-10
**Maintainer:** Documentation Team
