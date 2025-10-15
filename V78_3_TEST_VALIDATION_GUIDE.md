# V78.3 Test Validation Guide

**Deployed:** 2025-10-15 10:15
**Fix:** begin_message = null + ERSTE AKTION Prompt

---

## ✅ Deployment Status

**Agent Config:**
- `begin_message`: ✅ null (war: "Willkommen bei Ask Pro AI...")
- `last_modification`: 1760519325612
- Status: DEPLOYED

**LLM Prompt:**
- Version: ✅ V78.3 (ERSTE AKTION)
- `last_modification`: 1760519309144
- Status: DEPLOYED

---

## 🎯 Expected Behavior (V78.3)

### Ablauf bei neuem Anruf:

```
1. Call beginnt
2. Agent ruft SOFORT auf: check_customer(call_id={{call_id}})
3. Agent wartet auf Response (≤2s)
4. Agent spricht JETZT ERST:

   - Status 'found': "Willkommen bei Ask Pro AI. Schön Sie wieder zu hören, [Name]! Wie kann ich Ihnen helfen?"
   - Status 'new_customer': "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Wie kann ich Ihnen helfen?"
   - Status 'anonymous': "Willkommen bei Ask Pro AI. Für Terminbuchungen benötige ich Ihren vollständigen Namen. Wie heißen Sie?"
   - Error: "Willkommen bei Ask Pro AI. Wie kann ich Ihnen helfen?"
```

---

## 🧪 Test Scenarios

### Test 1: Anonymer Anruf (unterdrückte Nummer)

**User Action:**
1. Anrufen mit unterdrückter Nummer
2. Warten auf Agent-Begrüßung
3. Sagen: "Mein Name ist Max Mustermann, ich möchte einen Termin buchen"

**Expected:**
- Agent begrüßt mit: "Willkommen bei Ask Pro AI. Für Terminbuchungen benötige ich Ihren vollständigen Namen. Wie heißen Sie?"
- KEINE Stille >2s nach Begrüßung
- Agent reagiert sofort auf "Max Mustermann"

### Test 2: Anruf mit Telefonnummer

**User Action:**
1. Anrufen mit übertragener Telefonnummer
2. Warten auf Agent-Begrüßung
3. Sagen: "Ich möchte einen Termin buchen"

**Expected:**
- Agent begrüßt mit: "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Wie kann ich Ihnen helfen?" (wenn neue Nummer)
- ODER: "Willkommen bei Ask Pro AI. Schön Sie wieder zu hören, [Name]! Wie kann ich Ihnen helfen?" (wenn bekannte Nummer)
- KEINE Stille >2s nach Begrüßung
- Agent reagiert sofort auf Terminbuchungsanfrage

---

## 📊 Monitoring Commands

### Real-time Log Monitoring (Terminal 1):

```bash
cd /var/www/api-gateway
tail -f storage/logs/laravel.log | grep --line-buffered -E "check_customer|V78.3|ERSTE AKTION|call_"
```

### Performance Monitoring (Terminal 2):

```bash
cd /var/www/api-gateway
tail -f storage/logs/laravel.log | grep --line-buffered "latency_ms\|time_sec"
```

### Call Flow Analysis (Terminal 3):

```bash
cd /var/www/api-gateway
tail -f storage/logs/laravel.log | grep --line-buffered -E "Function:|Response:|Agent version:"
```

---

## 🔍 Success Criteria

### ✅ Fix Validated If:

1. **Kein Schweigen**: Keine Stille >2s nach Agent-Begrüßung
2. **check_customer() zuerst**: Logs zeigen check_customer() BEVOR erste Begrüßung
3. **Responsive Greetings**: Unterschiedliche Begrüßung basierend auf Customer Status
4. **Smooth Flow**: User kann direkt nach Begrüßung sprechen, Agent reagiert

### ❌ Fix Failed If:

1. Agent schweigt >2s nach Begrüßung
2. Agent spricht BEVOR check_customer() aufgerufen wird
3. Fixe "Willkommen" Begrüßung unabhängig von Status
4. User muss mehrmals sprechen bevor Agent reagiert

---

## 🔧 Rollback (Falls Test fehlschlägt)

```bash
# V78.2 wiederherstellen
curl -s -X PATCH "https://api.retellai.com/update-retell-llm/llm_f3209286ed1caf6a75906d2645b9" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d "{\"general_prompt\": $(cat /var/www/api-gateway/RETELL_PROMPT_V78_2_SILENCE_FIX.txt | jq -Rs .)}"

# begin_message wieder setzen
curl -s -X PATCH "https://api.retellai.com/update-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d '{"begin_message": "Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Wie kann ich Ihnen helfen?"}'
```

---

## 📋 Next Steps After Validation

### If Test Successful ✅:

1. **Remove DEBUG Mode**: Create V78.4 without "DEBUG VERBOSITY" section
2. **Performance Optimization**: Focus on latency reduction (target <1000ms)
3. **Function Call Optimization**: Batch calls where possible
4. **Cal.com Caching**: Improve cache hit rates

### If Test Failed ❌:

1. **Analyze Call Logs**: Get call_id from failed test
2. **Deep RCA**: Check timing of check_customer() vs greeting
3. **Alternative Approach**: Consider using `webhook_url` for initialization
4. **Retell Support**: Contact if begin_message behavior is inconsistent

---

**Test Status**: ⏳ PENDING USER VALIDATION
**Next Action**: Run Test 1 + Test 2, then report results
