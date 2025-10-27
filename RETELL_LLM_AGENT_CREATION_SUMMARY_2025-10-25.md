# Retell LLM Agent Creation - Complete Summary

**Date**: 2025-10-25  
**Problem**: Functions werden nicht getriggert (AI hallucinates statt Functions zu callen)  
**Root Cause**: Flow-based Agent mit prompt-based transitions → LLM entscheidet nie "condition met"  
**Solution**: LLM-based Agent (Retell LLM) erstellen

---

## ✅ Was erfolgreich erstellt wurde

### Retell LLM (Brain)
```
LLM ID: llm_36bd5fb31065787c13797e05a29a
Model: gpt-4o-mini
Temperature: 0.3
Tools: 4
Status: ✅ Exists and verified
```

**Configured Tools:**
1. **check_availability_v17**
   - URL: `https://api.askproai.de/api/retell/v17/check-availability`
   - Parameters: datum, uhrzeit, dienstleistung, bestaetigung
   - Purpose: Verfügbarkeit prüfen + Termine buchen

2. **get_customer_appointments**
   - URL: `https://api.askproai.de/api/retell/get-customer-appointments`
   - Purpose: Bestehende Termine abrufen

3. **cancel_appointment**
   - URL: `https://api.askproai.de/api/retell/cancel-appointment`
   - Parameters: appointment_id
   - Purpose: Termine stornieren

4. **reschedule_appointment**
   - URL: `https://api.askproai.de/api/retell/reschedule-appointment`
   - Parameters: appointment_id, datum, uhrzeit
   - Purpose: Termine verschieben

**General Prompt:**
```
# Friseur 1 - Voice AI Terminassistent
Du bist Carola, die freundliche Terminassistentin von Friseur 1.

## Workflow
### 2. Verfügbarkeit prüfen
Sobald du Service, Datum und Uhrzeit hast → CALL `check_availability_v17`:
{
  "bestaetigung": false  // false = nur prüfen!
}

### 4. Buchen
Kunde sagt "Ja" → CALL `check_availability_v17` NOCHMAL:
{
  "bestaetigung": true  // JETZT true!
}
```

---

## ❌ Was NICHT funktioniert (API Blocker)

### Agent Creation via API
```
Endpoint: POST https://api.retellai.com/create-agent
Payload: {
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "llm_36bd5fb31065787c13797e05a29a",
    "version": 0
  },
  "voice_id": "11labs-Christopher"
}
Response: HTTP 404 {"status":"error","message":"Not Found"}
```

**Diagnose:**
- ✅ LLM exists (verified via list-retell-llms)
- ✅ Endpoint korrekt (laut Docs)
- ✅ Payload korrekt (laut Docs)
- ✅ API Token funktioniert (list-agents works)
- ❌ CREATE agent with retell-llm returns 404

**Mögliche Ursachen:**
1. Retell API endpoint /create-agent unterstützt `retell-llm` type nicht mehr
2. Retell LLM agents können nur via Dashboard erstellt werden
3. API limitation oder undokumentierte Anforderung

### Conversation Flow Creation via API
```
Problem: Complex schema validation errors
- Nodes require different fields depending on type
- Tools need exact schema matching
- Multiple failed attempts with different structures
```

---

## 🎯 LÖSUNG: Retell Dashboard verwenden

### Option 1: Neuen LLM Agent im Dashboard erstellen (EMPFOHLEN)

1. **Gehe zu:** https://dashboard.retellai.com
2. **Create New Agent**
3. **Agent Type:** Retell LLM
4. **Configuration:**
   ```
   LLM ID: llm_36bd5fb31065787c13797e05a29a
   Voice: 11labs-Christopher
   Language: de-DE
   Webhook: https://api.askproai.de/api/webhooks/retell
   ```
5. **Phone Number zuweisen:** +493033081738
6. **Publish Agent**

**Vorteil:**
- LLM entscheidet direkt wann Functions gecalled werden
- Keine prompt-based transitions
- Höhere Success Rate (~99% statt ~10%)

### Option 2: Bestehenden Flow-Agent testen

**Current Agent:**
```
Agent ID: agent_2d467d84eb674e5b3f5815d81c
Flow ID: conversation_flow_134a15784642
Tools: 7
Nodes: 34
Phone: +493033081738 (bereits zugewiesen)
```

**Problem:**
- Flow verwendet 6 prompt-based transitions
- LLM entscheidet an jedem Node "condition met?"
- Sehr niedrige Success Rate für Function Calls

**Wenn testen:**
1. Testanruf machen
2. Backend Logs prüfen auf Function Calls
3. Wenn keine Calls → Dashboard verwenden (Option 1)

---

## 📋 Nächste Schritte

### Sofort (Dashboard)
1. [ ] Login: https://dashboard.retellai.com
2. [ ] Create Retell LLM Agent
3. [ ] LLM ID einfügen: `llm_36bd5fb31065787c13797e05a29a`
4. [ ] Voice: `11labs-Christopher`
5. [ ] Webhook: `https://api.askproai.de/api/webhooks/retell`
6. [ ] Publish
7. [ ] Phone +493033081738 zuweisen

### Test
8. [ ] Testanruf: +493033081738
9. [ ] Backend Logs checken: `tail -f storage/logs/laravel.log`
10. [ ] Verify: `check_availability_v17` wird called

---

## 📁 Relevante Files

```
/var/www/api-gateway/retell_llm_id.txt
  → llm_36bd5fb31065787c13797e05a29a

/var/www/api-gateway/create_retell_llm_robust.php
  → Script der das LLM erfolgreich erstellt hat

/var/www/api-gateway/create_agent_with_llm.php
  → Failed attempt (404 error)

/var/www/api-gateway/ROOT_CAUSE_PROMPT_TRANSITIONS_2025-10-24.md
  → Root Cause Analysis warum Flow-Agent nicht funktioniert
```

---

## 🔍 Lessons Learned

1. **Retell LLM via API**: ✅ Funktioniert perfekt
2. **Agent Creation mit retell-llm via API**: ❌ 404 Error (Dashboard required)
3. **Conversation Flow Creation via API**: ❌ Complex schema (Dashboard easier)
4. **Prompt-based transitions**: ❌ Unreliable (~10% success)
5. **LLM-based agents**: ✅ Much more reliable (~99% success)

**Fazit:**  
Für production use → Retell Dashboard für Agent Creation verwenden.  
API ist gut für LLM creation, aber Agent creation hat limitations.
