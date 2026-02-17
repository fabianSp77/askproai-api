# Voice Agent Naturalness & Optimization Research

> **Date**: 2026-02-17
> **Scope**: German IT Support Voice Agent (VisionaryData v3.5)
> **Confidence**: HIGH (multi-source validated)

---

## Table of Contents

1. [Phone Number Pronunciation (German)](#1-phone-number-pronunciation-german)
2. [Time/Date Pronunciation (German)](#2-timedate-pronunciation-german)
3. [Number Handling in Prompts for TTS](#3-number-handling-in-prompts-for-tts)
4. [Retell.ai Latency Optimization](#4-retellai-latency-optimization)
5. [Conversational Naturalness Prompting](#5-conversational-naturalness-prompting)
6. [Retell-Specific Configuration](#6-retell-specific-configuration)
7. [Concrete Recommendations for v3.5+](#7-concrete-recommendations-for-v35)
8. [Sources](#8-sources)

---

## 1. Phone Number Pronunciation (German)

### Current State (v3.5)

The current prompt instructs: "Wiederhole die Nummer Ziffer fuer Ziffer: 'null - eins - sieben - eins...'"

### Best Practices

**Natural German phone number grouping follows the area code + subscriber pattern:**

| Format | Example | Spoken |
|--------|---------|--------|
| Mobile | 0171 555 4321 | "null eins sieben eins ... fuenf fuenf fuenf ... vier drei zwei eins" |
| Landline | 030 123 456 78 | "null drei null ... eins zwei drei ... vier fuenf sechs ... sieben acht" |
| Digit-by-digit | 01onal | each digit separately with micro-pauses between groups |

**Key rules for natural German phone numbers:**

1. **Group in 2-4 digits** with pauses between groups (never read 11 digits straight)
2. **Zero is always "null"** (never "oh" as in English)
3. **"zwei" not "zwo"** in formal/professional context (though "zwo" is common in military/aviation to avoid confusion with "drei")
4. **Natural pacing**: pause 300-500ms between groups, read digits within groups at normal pace
5. **Confirm by repeating back** in the same grouping the caller used

### Recommended Prompt Text (REPLACE in node_it_collect_contact_v3)

```
Wenn der Anrufer eine Nummer nennt:
1. Wiederhole die Nummer in natuerlichen Zweier- oder Dreiergruppen mit kurzen Pausen:
   Beispiel: 'Ich wiederhole: null eins sieben eins ... fuenf fuenf fuenf ... vier drei zwei eins. Ist das korrekt?'
2. Passe dich dem Tempo des Anrufers an - wenn er langsam diktiert, wiederhole langsam.
3. Bei Unsicherheit: 'Entschuldigung, die letzten Ziffern habe ich nicht ganz verstanden. Ab welcher Stelle soll ich nochmal anfangen?'
```

### TTS Engine Handling

- **With speech normalization enabled**: Retell will auto-convert digit strings like "01715554321" into spoken German words. This adds ~100ms latency.
- **Without speech normalization**: The LLM should output the number pre-formatted with spaces: "null eins sieben eins fuenf fuenf fuenf vier drei zwei eins"
- **Recommendation**: Do NOT enable speech normalization. Instead, instruct the LLM in the prompt to output numbers as spoken words. This saves 100ms latency and gives more control.

---

## 2. Time/Date Pronunciation (German)

### Two Registers in German

**"News Speak" (Formal/Official)** - Used for appointments, official times:
- 14:30 = "vierzehn Uhr dreissig"
- 9:00 = "neun Uhr"
- 16:45 = "sechzehn Uhr fuenfundvierzig"

**"Street Speak" (Casual)** - More natural in conversation:
- 14:30 = "halb drei" (half to three)
- 9:00 = "um neun"
- 16:45 = "Viertel vor fuenf"

### Recommendation for IT Support Agent

**Use the 24-hour formal format ("News Speak")** for appointment confirmations and callbacks because:
- Avoids ambiguity (9 Uhr = morning, 21 Uhr = evening)
- Professional context expects it
- IT support is a business interaction with "Sie"

**But soften it in conversation:**

```
GUTE Beispiele:
- "Ein Techniker meldet sich heute Nachmittag, voraussichtlich gegen vierzehn Uhr."
- "Wir kuemmern uns darum, am besten erreichen Sie uns zwischen neun und siebzehn Uhr."

SCHLECHTE Beispiele:
- "Ein Techniker meldet sich um 14:00:00 Uhr." (zu roboterhaft)
- "Wir sind erreichbar von neun null null bis siebzehn null null." (niemand spricht so)
```

### Prompt Addition for Global Prompt

```
ZEITANGABEN:
- Uhrzeiten IMMER ausschreiben: "vierzehn Uhr dreissig" statt "14:30"
- Volle Stunden ohne "null null": "neun Uhr" nicht "neun Uhr null null"
- Natuerliche Einbettung: "gegen vierzehn Uhr" oder "so gegen halb drei"
```

---

## 3. Number Handling in Prompts for TTS

### The Core Problem

TTS engines interpret raw numbers unpredictably:
- "123" could be read as "einhundertdreiundzwanzig" or "eins zwei drei"
- "$24.12" might be read as "twenty-four point one two"
- Phone numbers as single large numbers: "01715554321" = "eine Milliarde siebenhundert..."

### Best Practices

**Rule 1: Spell out numbers in prompt instructions**

```
SCHLECHT: "Ihre Ticketnummer ist 4523."
GUT:     "Ihre Ticketnummer ist vier fuenf zwei drei."
```

**Rule 2: Use context-appropriate formatting in the LLM output**

| Context | Input | LLM Should Output |
|---------|-------|-------------------|
| Phone number | 01715554321 | "null eins sieben eins fuenf fuenf fuenf vier drei zwei eins" |
| Ticket number | #4523 | "vier fuenf zwei drei" |
| Count | 3 users affected | "drei Nutzer betroffen" |
| Time | 14:30 | "vierzehn Uhr dreissig" |

**Rule 3: Add explicit number-formatting instructions to the global prompt**

```
ZAHLEN UND NUMMERN:
- Telefonnummern IMMER als einzelne gesprochene Ziffern mit Pausen ausgeben
- Ticketnummern Ziffer fuer Ziffer vorlesen
- Mengenangaben als Zahlwoerter: "drei" nicht "3"
- Keine Abkuerzungen die TTS nicht versteht
```

**Rule 4: Avoid SSML** - Retell does not support SSML tags in conversation flow prompts. Rely on LLM instruction instead.

---

## 4. Retell.ai Latency Optimization

### Parameter Reference Table

| Parameter | Range | Default | Our v3.5 | Recommended | Impact |
|-----------|-------|---------|----------|-------------|--------|
| `responsiveness` | 0.0-1.0 | 1.0 | ? | **0.7-0.8** | Each -0.1 = +500ms wait. 1.0 is too fast (interrupts users). 0.7 gives ~1.5s natural pause. |
| `interruption_sensitivity` | 0.0-1.0 | 1.0 | ? | **0.6-0.7** | Lower = more resilient to background noise. 1.0 causes false interruptions from background speech. |
| `backchannel_frequency` | 0.0-1.0 | 0.8 | 0.5 | **0.3-0.4** | 0.8 is too chatty. For formal German IT support, 0.3-0.4 is appropriate. |
| `backchannel_words` | string[] | system default | ? | **["Ja", "Verstehe", "Mhm"]** | German-specific words. Avoid English defaults. Test each word with your voice. |
| `voice_speed` | 0.5-2.0 | 1.0 | ? | **0.95-1.0** | Slightly slower for German clarity. Speed added latency reduced to ~5ms. |
| `voice_temperature` | 0.0-2.0 | 1.0 | ? | **0.8** | Lower = more stable/consistent. Only for ElevenLabs voices. |
| `model_temperature` | 0.0-2.0 | varies | 0.3 | **0.3** | 0.3 is correct for structured flows with function calls. |
| `stt_mode` | fast/accurate/custom | fast | ? | **fast** | "fast" for latency optimization. Only use "accurate" if German transcription errors are frequent. |
| `enable_voicemail_detection` | bool | false | ? | **true** | Adds detection window but prevents wasted calls to voicemail. |
| `voicemail_detection_timeout_ms` | 5000-180000 | 30000 | ? | **15000** | Reduce from 30s to 15s. Most voicemails answer within 10-15s. |
| `enable_speech_normalization` | bool | false | ? | **false** | Adds ~100ms latency. Better to handle in prompt. |
| `ambient_sound` | enum/null | null | ? | **"call-center"** | Adds realism. Very low overhead. |
| `ambient_sound_volume` | 0.0-2.0 | 1.0 | ? | **0.3** | Very subtle. Too loud is distracting. |
| `reminder_trigger_ms` | ms | 10000 | ? | **12000** | 12s before "Sind Sie noch da?" — 10s can be too aggressive. |
| `begin_message_delay_ms` | 0-5000 | 0 | ? | **300-500** | Small delay feels more natural than instant response after pickup. |

### Latency Budget Analysis

```
Total target: < 1200ms end-to-end

Component breakdown:
  STT (speech-to-text):    100-200ms (fast mode)
  LLM processing:          400-700ms (GPT-4o with short prompt)
  TTS (text-to-speech):    100-200ms (streaming)
  Network overhead:          50-100ms
  Speech normalization:     +100ms (if enabled, avoid)
  ---
  Total:                    650-1200ms
```

### Latency Reduction Strategies (Ranked by Impact)

**HIGH IMPACT:**

1. **Keep responses under 2 sentences / 30 words** - LLM generates less, TTS processes less
2. **Disable speech normalization** - saves 100ms, handle in prompt instead
3. **Use GPT-4o (not GPT-4)** - significantly faster inference
4. **Minimize extract_dynamic_variables nodes in hot path** - each node requires LLM inference
5. **Use `stt_mode: "fast"`** - fastest transcription

**MEDIUM IMPACT:**

6. **Reduce knowledge base size** - less RAG retrieval time
7. **Set function call timeout to 10s** (currently 15s) - fail faster
8. **Use streaming responses** (default in Retell)
9. **Optimize prompt length** - shorter system prompts = faster processing

**LOW IMPACT:**

10. **Ambient sound** - negligible latency, improves perceived quality
11. **Begin message delay** - perceived latency, not actual

### Node Architecture Latency

Each node transition in a conversation flow triggers:
1. Edge condition evaluation (LLM call for prompt-type conditions, instant for equation-type)
2. Next node's LLM prompt processing
3. Variable extraction (if extract_dynamic_variables node)

**Optimization**: Use equation-type edge conditions instead of prompt-type wherever possible. Equations are evaluated without LLM calls.

**Current v3.5 has 7 classify edges**: 6 equation-type + 1 prompt-type fallback. This is already well optimized.

---

## 5. Conversational Naturalness Prompting

### 5.1 German Filler Words

**Appropriate for formal IT support (use sparingly):**

| Filler | Use Case | Example |
|--------|----------|---------|
| "Schauen wir mal..." | Transitioning to action | "Schauen wir mal, ich erstelle jetzt das Ticket." |
| "Also..." | Starting a summary | "Also, wenn ich das richtig verstehe..." |
| "Moment..." | Brief thinking pause | "Moment, ich notiere das kurz." |
| "Gut..." | Acknowledging info | "Gut, das habe ich notiert." |
| "Verstehe." | Active listening | Short confirmation |
| "In Ordnung." | Agreement | After receiving info |

**DO NOT use in formal German IT support:**

| Filler | Why Not |
|--------|---------|
| "Aehm" / "Oehm" | Sounds uncertain, unprofessional |
| "Sozusagen" | Vague, adds no value |
| "Quasi" | Too informal for B2B support |
| "Alter" / "Ey" | Obviously inappropriate |
| "Geil" / "Cool" | Too colloquial |

### 5.2 Sentence Length for TTS

**Target: 8-20 words per utterance.** Shorter sentences:
- Are generated faster by the LLM
- Are synthesized faster by TTS
- Sound more natural when spoken
- Are easier for the caller to process

```
SCHLECHT (37 words):
"Ich habe jetzt alle Informationen die ich brauche und werde ein Ticket in unserem System
erstellen damit sich ein Techniker so schnell wie moeglich bei Ihnen melden kann um das
Problem zu loesen."

GUT (split into turns):
"Danke, ich habe alles notiert."
[pause]
"Ich erstelle jetzt das Ticket. Ein Techniker meldet sich bei Ihnen."
```

### 5.3 Avoiding Robotic Repetitive Patterns

**Problem**: AI agents often repeat the same acknowledgment phrases.

**Solution: Provide variation pools in the prompt:**

```
BESTAETIGUNG VARIANTEN (wechsle ab, benutze nie dieselbe zweimal hintereinander):
- "Danke, das habe ich notiert."
- "Verstanden."
- "Gut, danke fuer die Info."
- "Alles klar."
- "In Ordnung, das hilft weiter."
- "Okay, danke."
```

**Anti-pattern instruction:**

```
WICHTIG: Wiederhole NICHT dieselbe Formulierung die du gerade verwendet hast.
Variiere deine Bestaetigung und Uebergaenge. Wenn du gerade "Danke" gesagt hast,
sage als naechstes "Verstanden" oder "Gut" statt nochmal "Danke".
```

### 5.4 Contractions and Informal Speech

**German IT B2B = Formal "Sie" register.** However, natural spoken German still uses:

| Written | Natural Spoken | Use? |
|---------|---------------|------|
| "Koennen Sie" | "Koennen Sie" | YES - keep formal |
| "Haben Sie schon einmal" | "Haben Sie schon mal" | YES - natural shortening |
| "Das ist" | "Das is" | NO - too informal |
| "Ich werde" | "Ich werd'" | NO - too casual |
| "auf jeden Fall" | "auf jeden Fall" | YES - natural emphasis |
| "Guten Tag" | "Guten Tag" | YES - standard greeting |
| "ein bisschen" | "n bisschen" | NO - too colloquial |

### 5.5 Turn-Taking Behavior

```
GESPRAECHSFUEHRUNG:
- Stelle immer nur EINE Frage pro Antwort
- Warte auf die vollstaendige Antwort bevor du weitersprichst
- Wenn der Anrufer mitten im Satz ist, unterbreche NICHT
- Nach deiner Antwort, signalisiere dass du zuhoerst mit einer kurzen Pause
- Wenn der Anrufer laenger als 3 Sekunden schweigt nach deiner Frage:
  "Nehmen Sie sich ruhig Zeit."
```

---

## 6. Retell-Specific Configuration

### 6.1 stt_mode: "fast" vs "accurate"

| Mode | Latency | Accuracy | When to Use |
|------|---------|----------|-------------|
| `fast` | ~100ms | Good | Default. Most use cases. |
| `accurate` | ~200-300ms | Better | Heavy accent callers, technical terminology |
| `custom` | Variable | Configurable | When you need specific ASR provider (Azure/Deepgram) |

**Recommendation for German IT support**: Start with `fast`. If STT errors on German technical terms (VPN, DHCP, etc.) are frequent, switch to `accurate` or use `boosted_keywords` to improve recognition.

**boosted_keywords example:**
```json
["VPN", "WLAN", "Outlook", "Teams", "SharePoint", "Exchange", "MFA",
 "Drucker", "Passwort", "Bildschirm", "Laptop", "Desktop", "Neustart",
 "Fehlermeldung", "Internet", "Netzwerk", "DHCP", "DNS"]
```

### 6.2 model_temperature

| Value | Behavior | Use Case |
|-------|----------|----------|
| 0.0-0.3 | Deterministic, consistent | **Structured flows**, function calls, classification |
| 0.4-0.6 | Balanced | General conversation |
| 0.7-1.0 | Creative, varied | Sales, casual chat |

**Current v3.5: 0.3** - Correct for our structured flow. Keep it.

**Per-node override idea**: Use 0.3 for extract/classify nodes, 0.5 for triage conversation nodes (more natural variation).

### 6.3 conversation Nodes vs extract_dynamic_variables Nodes

| Aspect | Conversation Node | Extract DV Node |
|--------|-------------------|-----------------|
| Purpose | Talk to user | Extract data silently |
| LLM calls | 1+ (ongoing conversation) | 1 (single extraction pass) |
| User interaction | Yes | No (transparent) |
| Latency contribution | Per-turn LLM call | Single LLM call for extraction |
| Best for | Questions, responses | Parsing data from conversation |

**Optimization**: Extract DV nodes run a single LLM inference to fill variables, then immediately evaluate edges. They are faster than conversation nodes for data extraction because there is no back-and-forth.

**Current v3.5 architecture is good**: Extract nodes are used purely for data capture, conversation nodes for interaction. No changes needed here.

### 6.4 Edge Transition Latency

| Edge Type | Evaluation Speed | Use When |
|-----------|-----------------|----------|
| `equation` | **Instant** (no LLM) | Variable comparisons (==, !=, contains) |
| `prompt` | **200-500ms** (LLM call) | Complex semantic conditions |

**Current v3.5**: 6 equation edges + 1 prompt fallback on classify node. This is optimal.

**Recommendation**: Convert more prompt-type edges to equation-type where possible. Example candidates:
- `edge_triage_extract_to_summary_phone_known`: Currently prompt-type, could be equation: `customer_phone != ""`
- `edge_triage_extract_to_contact`: Could be equation: `customer_phone == ""`

---

## 7. Concrete Recommendations for v3.5+

### Priority 1: Global Prompt Enhancements

**ADD to global_prompt (after existing content):**

```
NATUERLICHKEIT:
- Halte Antworten unter 2 Saetze / 25 Woerter
- Variiere deine Bestaetigung: "Danke", "Verstanden", "Gut", "Alles klar", "In Ordnung" — nie zweimal hintereinander dieselbe
- Verwende natuerliche Uebergaenge: "Schauen wir mal...", "Also...", "Gut..."
- Sprich Zahlen IMMER als Woerter aus, nie als Ziffern

TELEFONNUMMERN VORLESEN:
- Gruppiere in Zweier- oder Dreiergruppen mit kurzen Pausen
- Beispiel: "null eins sieben eins ... fuenf fuenf fuenf ... vier drei zwei eins"
- Passe dein Tempo dem Anrufer an

ZEITANGABEN:
- Uhrzeiten ausschreiben: "vierzehn Uhr dreissig" statt "14:30"
- Volle Stunden ohne Nullen: "neun Uhr" nicht "neun Uhr null null"
- Natuerlich einbetten: "gegen vierzehn Uhr" oder "im Laufe des Nachmittags"

ANTI-ROBOTER:
- Beginne Saetze NICHT immer mit "Ich" — variiere den Satzbau
- Sage nicht bei jeder Antwort "Vielen Dank" — wechsle ab
- Vermeide Formulierungen die sich wie ein Formular anhoeren
```

### Priority 2: Agent-Level Settings (API/Dashboard)

```json
{
  "responsiveness": 0.75,
  "interruption_sensitivity": 0.65,
  "enable_backchannel": true,
  "backchannel_frequency": 0.35,
  "backchannel_words": ["Ja", "Verstehe", "Mhm"],
  "voice_speed": 0.95,
  "voice_temperature": 0.8,
  "stt_mode": "fast",
  "enable_speech_normalization": false,
  "enable_voicemail_detection": true,
  "voicemail_detection_timeout_ms": 15000,
  "ambient_sound": "call-center",
  "ambient_sound_volume": 0.3,
  "reminder_trigger_ms": 12000,
  "begin_message_delay_ms": 400,
  "boosted_keywords": ["VPN", "WLAN", "Outlook", "Teams", "SharePoint", "Exchange", "MFA", "Drucker", "Passwort", "Bildschirm", "Laptop", "Desktop", "Neustart", "Fehlermeldung", "Internet", "Netzwerk", "DHCP", "DNS", "Firewall", "Backup", "Server", "Cloud", "OneDrive"]
}
```

### Priority 3: Node-Level Improvements

**node_it_intro_v3** - Make the opening more natural:
```
CURRENT: "Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem. Worum geht es?"
BETTER:  "Danke! Wie kann ich Ihnen helfen?"
```
Shorter, warmer, less formal-robotic.

**node_it_collect_contact_v3** - Natural phone number handling:
```
CURRENT: "Unter welcher Nummer koennen wir Sie fuer Rueckfragen am besten erreichen?"
BETTER:  "Unter welcher Nummer erreichen wir Sie am besten?"
```
Then for readback:
```
CURRENT: "Ich wiederhole: null - eins - sieben - eins..."
BETTER:  "Ich wiederhole... null eins sieben eins... fuenf fuenf fuenf... vier drei zwei eins. Stimmt das?"
```
Use ellipsis for natural pauses instead of dashes.

**Triage nodes** - Add variation:
```
CURRENT: Each triage node has "Stelle EINE gezielte Rueckfrage"
ADD:     "Formuliere die Frage natuerlich und gespraechtypisch, nicht wie eine Checkliste."
```

**node_it_summary_v3** - Make summary conversational:
```
CURRENT: "Ich fasse kurz zusammen: [Name] von [Firma] — [Problem in einem Satz]."
BETTER:  "Also, ich habe notiert: [Name], [Firma], Problem ist [kurz]. Wir melden uns unter [Nummer]. Passt das so?"
```

### Priority 4: Edge Optimization (Convert prompt to equation)

Replace in `node_it_extract_triage_v3`:

```json
// BEFORE (prompt-based, ~300ms LLM call)
{
  "id": "edge_triage_extract_to_summary_phone_known",
  "transition_condition": {
    "type": "prompt",
    "prompt": "customer_phone ist bereits gesetzt..."
  }
}

// AFTER (equation-based, instant)
{
  "id": "edge_triage_extract_to_summary_phone_known",
  "transition_condition": {
    "type": "equation",
    "equations": [
      {"left": "customer_phone", "operator": "!=", "right": ""}
    ],
    "operator": "&&"
  }
}
```

Similarly for `edge_triage_extract_to_contact`:
```json
{
  "id": "edge_triage_extract_to_contact",
  "transition_condition": {
    "type": "equation",
    "equations": [
      {"left": "customer_phone", "operator": "==", "right": ""}
    ],
    "operator": "&&"
  }
}
```

### Priority 5: Per-Node Model Temperature (Advanced)

For conversation/triage nodes that benefit from natural variation:
```json
{
  "custom_llm": {
    "model": "gpt-4o",
    "temperature": 0.5
  }
}
```

Keep extract and classify nodes at 0.3 (current global default).

---

## 8. Sources

### Retell AI Documentation
- [Create Voice Agent API Reference](https://docs.retellai.com/api-references/create-agent)
- [Configure Basic Settings](https://docs.retellai.com/build/single-multi-prompt/configure-basic-settings)
- [Conversation Flow Overview](https://docs.retellai.com/build/conversation-flow/overview)
- [Extract Dynamic Variable Node](https://docs.retellai.com/build/conversation-flow/extract-dv-node)
- [Node Overview](https://docs.retellai.com/build/conversation-flow/node)
- [Normalize Text for Speech](https://docs.retellai.com/build/normalize-text)
- [Prompt Engineering Guide](https://docs.retellai.com/build/prompt-engineering-guide)

### Retell AI Blog & Resources
- [How to Build A Good AI Voice Agent](https://www.retellai.com/blog/how-to-build-a-good-voice-agent)
- [What is Backchanneling?](https://www.retellai.com/blog/how-backchanneling-improves-user-experience-in-ai-powered-voice-agents)
- [5 Useful Prompts for AI Agent Builders](https://www.retellai.com/blog/5-useful-prompts-for-building-ai-voice-agents-on-retell-ai)
- [Latency Face-Off 2025](https://www.retellai.com/resources/ai-voice-agent-latency-face-off-2025)
- [Why Low Latency Matters](https://www.retellai.com/blog/why-low-latency-matters-how-retell-ai-outpaces-traditional-players)
- [Platform Changelogs](https://www.retellai.com/changelog)

### Voice AI Prompting Guides
- [Voice AI Prompting Guide - Layercode](https://layercode.com/blog/how-to-write-prompts-for-voice-ai-agents)
- [Voice AI Prompting Guide - Vapi](https://docs.vapi.ai/prompting-guide)
- [Prompting Guide - ElevenLabs](https://elevenlabs.io/docs/agents-platform/best-practices/prompting-guide)
- [How to Design Prompts for Voice Agents - Murf](https://murf.ai/blog/voice-agent-prompt-design)
- [Creating Natural AI Voice Prompts - CallFluent AI](https://helpcenter.callfluent.com/creating-natural-and-engaging-ai-voice-prompts-for-callfluent-ai/)

### Latency & Optimization
- [Mastering Voice AI Latency - Waboom AI](https://www.waboom.ai/blog/mastering-voice-ai-latency)
- [Engineering for Real-Time Voice Agent Latency - Cresta](https://cresta.com/blog/engineering-for-real-time-voice-agent-latency)
- [Voice AI Latency Optimization 2026 - Ruh AI](https://www.ruh.ai/blogs/voice-ai-latency-optimization)
- [Core Latency in AI Voice Agents - Twilio](https://www.twilio.com/en-us/blog/developers/best-practices/guide-core-latency-ai-voice-agents)
- [How to Optimize Latency - ElevenLabs](https://elevenlabs.io/blog/how-do-you-optimize-latency-for-conversational-ai)

### Number/TTS Handling
- [SSML Say-As Telephone Tag - SpeechGen](https://speechgen.io/en/node/telephone/)
- [Text to Speech Numbers - Speechify](https://speechify.com/blog/text-to-speech-numbers/)
- [SSML Reference - Google Cloud](https://cloud.google.com/text-to-speech/docs/ssml)

### German Language Resources
- [Telling Time in German - Busuu](https://www.busuu.com/en/german/telling-time)
- [Time of Day in German - YourDailyGerman](https://yourdailygerman.com/german-time-of-day/)
- [German Phone Call Etiquette - Callnovo](https://callnovo.com/german-phone-etiquette-importance-native-german-language-customer-service-outsourcing/)
- [German Customer Service Vocabulary - LangBox](https://langbox.co/german/customer-service-german-phrases/)

---

## Appendix: Quick-Apply Checklist

- [ ] Update global_prompt with NATUERLICHKEIT, TELEFONNUMMERN, ZEITANGABEN, ANTI-ROBOTER sections
- [ ] Set agent-level parameters (responsiveness, backchannel, etc.) via API or Dashboard
- [ ] Add boosted_keywords for IT terminology
- [ ] Convert 2 prompt-type edges to equation-type on node_it_extract_triage_v3
- [ ] Shorten node_it_intro_v3 greeting
- [ ] Improve phone number readback format in node_it_collect_contact_v3
- [ ] Test backchannel_words ["Ja", "Verstehe", "Mhm"] with current voice
- [ ] Test ambient_sound "call-center" at volume 0.3
- [ ] Test begin_message_delay_ms 400
- [ ] Measure end-to-end latency before/after changes
