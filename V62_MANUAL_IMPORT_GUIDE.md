# V62 Manual Import Guide

**Agent:** Friseur 1 Agent V62 - Optimized with Conversational Flow Rules
**Datei:** `/var/www/api-gateway/retell_agent_v62_fixed.json`
**Dashboard:** https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736

---

## 📋 Schritt-für-Schritt Import

### Option A: Kompletter Agent Import (Empfohlen)

1. **Öffne Retell Dashboard:**
   - Gehe zu: https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736
   - Oder: Dashboard → Agents → "Friseur 1 Agent"

2. **Importiere JSON:**
   - Klicke auf "Import" oder "Update from JSON"
   - Lade die Datei: `/var/www/api-gateway/retell_agent_v62_fixed.json`
   - Oder: Copy-Paste den kompletten JSON-Inhalt

3. **Verifikation:**
   - Prüfe: **32 Nodes** (statt 30)
   - Prüfe: **Logic Split Node** mit Name "Anti-Loop Check"
   - Prüfe: Tool Timeouts (check_availability: 3000ms statt 15000ms)
   - Prüfe: Global Prompt startet mit "V62 (2025-11-07 OPTIMIZED)"

4. **Speichern als Draft:**
   - Speichere zunächst als Draft (nicht publishen)
   - Neue Version sollte sein: V64 oder höher

---

### Option B: Nur Conversation Flow Import (Alternative)

Falls nur Conversation Flow Import möglich:

1. **Extrahiere Conversation Flow:**
   ```bash
   jq '.conversation_flow' /var/www/api-gateway/retell_agent_v62_fixed.json > v62_conversation_flow_only.json
   ```

2. **Dashboard:**
   - Gehe zu: Conversation Flows
   - Finde: `conversation_flow_a58405e3f67a`
   - Import/Update mit `v62_conversation_flow_only.json`

---

## ✅ Verifikation Checklist

Nach dem Import prüfe:

### 1. Nodes (32 Total)
- [ ] **Logic Split Node** vorhanden: "Anti-Loop Check" (id: `logic_split_anti_loop`)
- [ ] **Anti-Loop Handler Node** vorhanden: "Anti-Loop Handler" (id: `node_anti_loop_handler`)
- [ ] Alle anderen 30 Nodes noch vorhanden

### 2. Tools (10 Total)
- [ ] check_availability: **3000ms** (war: 15000ms)
- [ ] get_alternatives: **2500ms** (war: 10000ms)
- [ ] request_callback: **1500ms** (war: 10000ms)
- [ ] get_appointments: **3000ms** (war: 15000ms)
- [ ] cancel_appointment: **3000ms** (war: 15000ms)
- [ ] reschedule_appointment: **3000ms** (war: 15000ms)
- [ ] get_services: **2000ms** (war: 15000ms)
- [ ] start_booking: **2000ms** (war: 5000ms)
- [ ] confirm_booking: **30000ms** (unverändert)
- [ ] get_current_context: **1000ms** (war: 5000ms)

### 3. Global Prompt
- [ ] Enthält: "⏰ ZEIT- UND DATUMSANSAGE STANDARD"
- [ ] Enthält: "🚨 Anti-Repetition Rules"
- [ ] Enthält: "{{alternative_attempt_count}}"
- [ ] Version-Marker: "V62 (2025-11-07 OPTIMIZED)"

### 4. Fine-tuning Examples (falls sichtbar)
- [ ] Mindestens einige Examples bei Edges vorhanden

---

## 🧪 Test Scenarios (nach Import)

### Test 1: Zeit-Ansage Standard
**User:** "Morgen um halb vier"
**Erwartung:** Agent sagt "15 Uhr 30" (NICHT "halb vier")

### Test 2: Tool Timeout
**User:** "Was ist heute frei?"
**Erwartung:** Antwort kommt innerhalb 3-4 Sekunden (statt 15-20 Sekunden)

### Test 3: Anti-Loop (nach einigen Runden)
**Scenario:** 2-3x "Passt mir nicht" bei Alternativen
**Erwartung:** Agent bietet Rückruf/Warteliste an

---

## 📊 Erwartete Verbesserungen

| Metric | V51 | V62 | Improvement |
|--------|-----|-----|-------------|
| Tool Timeouts (avg) | 12000ms | 2300ms | **81% faster** |
| Error Detection | 15s | 1.5-3s | **5-10x faster** |
| Logic Split Nodes | 0 | 1 | Anti-Loop enabled |
| Zeit-Ansagen | Inkonsistent | Standardisiert | 100% konsistent |

---

## ⚠️ Bekannte Einschränkungen

### Logic Split via API
- ⚠️ Logic Split Nodes **funktionieren NICHT über API**
- ✅ Aber: **Manueller Import im Dashboard sollte funktionieren**
- Falls Logic Split im Dashboard auch nicht geht:
  - Fallback: Conversation Node mit prompt-based branching
  - Siehe: AGENT_V62_CHANGELOG.md für Alternative

---

## 🔗 Weitere Ressourcen

- **Changelog:** `/var/www/api-gateway/AGENT_V62_CHANGELOG.md`
- **Dashboard:** https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736
- **JSON-Datei:** `/var/www/api-gateway/retell_agent_v62_fixed.json`

---

## 📞 Bei Problemen

**Falls Logic Split nicht importiert werden kann:**
```bash
# Erstelle Version OHNE logic_split (Fallback)
php /var/www/api-gateway/scripts/create_v62_without_logic_split.php
```

**Falls Import komplett fehlschlägt:**
- Prüfe Dashboard-Fehlermeldungen
- Ggf. nur Conversation Flow updaten (Option B oben)
- Kontaktiere Retell Support für Logic Split Support

---

**Version:** V62
**Erstellt:** 2025-11-07
**Status:** ✅ Ready for Manual Import
