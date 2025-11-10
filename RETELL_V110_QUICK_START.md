# Retell V110 Quick Start Guide

**Version:** V110 (Customer Recognition)
**Status:** ✅ DEPLOYED
**Phone:** +493033081738

---

## 🚀 Sofort-Test (1 Minute)

```bash
# Test 1: Rufe die Nummer an
+493033081738

# Test 2: Sage einfach
"Ich möchte einen Herrenhaarschnitt buchen für morgen um 10 Uhr"

# Erwartung:
# - Agent fragt nach deinem Namen
# - Agent prüft Verfügbarkeit
# - Agent bucht den Termin
```

---

## 📞 Test Szenarien

### Szenario 1: Schnelle Buchung (Happy Path)
```
User: "Hallo"
Agent: "Willkommen bei Friseur 1! Wie kann ich Ihnen helfen?"

User: "Ich möchte einen Herrenhaarschnitt buchen"
Agent: "Wann hätten Sie Zeit?"

User: "Morgen um 14 Uhr"
Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."
Agent: "Perfekt! Ihr Wunschtermin ist frei. Darf ich noch Ihren Namen erfragen?"

User: "Max Mustermann"
Agent: "Perfekt! Ihr Termin ist gebucht für [Datum] um 14 Uhr."
```

### Szenario 2: Alle Infos in einem Satz
```
User: "Ich möchte einen Damenhaarschnitt buchen, morgen um 10 Uhr, ich bin Lisa Müller"
Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."
Agent: "Perfekt! Ihr Termin ist gebucht..."

✅ Agent fragt NICHT nochmal nach Service, Zeit oder Name!
```

### Szenario 3: Near-Match Alternativen
```
User: "Ich möchte morgen um 14 Uhr kommen"
Agent: "Einen Moment, ich prüfe die Verfügbarkeit..."
Agent: "Um 14 Uhr ist morgen schon belegt, aber ich kann Ihnen 13:30 oder 14:30 anbieten. Was passt Ihnen besser?"

✅ POSITIV formuliert: "kann Ihnen anbieten" statt "leider nicht verfügbar"
```

---

## 🔍 Monitoring

### Laravel Logs live ansehen
```bash
# Terminal öffnen
ssh root@api.askproai.de

# Logs in Echtzeit
tail -f /var/www/api-gateway/storage/logs/laravel.log
```

### Was du sehen solltest:
```
[2025-11-10 ...] Retell get_current_context called
[2025-11-10 ...] Retell check_customer called {"call_id":"...","phone_number":"..."}
[2025-11-10 ...] Retell function call: check_availability_v17
[2025-11-10 ...] Retell function call: start_booking
[2025-11-10 ...] Retell function call: confirm_booking
```

---

## 🛠️ Wichtige Commands

### Agent Details abrufen
```bash
curl -X GET "https://api.retellai.com/get-agent/agent_b9dd70fe509b12e031f9298854" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19"
```

### Backend Endpoint testen
```bash
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test_123", "from_number": "+4915112345678"}'

# Expected: {"found":false}
```

### Telefonnummer Status prüfen
```bash
curl -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  grep -A 5 "+493033081738"
```

---

## 🔄 Rollback (falls nötig)

### Zurück zu V109 Agent
```bash
curl -X PATCH "https://api.retellai.com/update-phone-number/+493033081738" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d '{
    "inbound_agent_id": "agent_45daa54928c5768b52ba3db736",
    "nickname": "+493033081738 Friseur 1 V109 (Rollback)"
  }'
```

### Verifizieren
```bash
curl -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  jq '.[] | select(.phone_number=="+493033081738") | {phone_number, inbound_agent_id, nickname}'
```

---

## ⚠️ Troubleshooting

### Problem: Agent antwortet nicht
```bash
# 1. Prüfe ob Telefonnummer richtig zugewiesen
curl -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  grep "agent_b9dd70fe509b12e031f9298854"

# 2. Prüfe Laravel Logs
tail -n 50 /var/www/api-gateway/storage/logs/laravel.log
```

### Problem: check_customer funktioniert nicht
```bash
# Test direct
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test_123"}'

# Expected: {"found":false}
# If 404: Route not found → check routes/api.php
# If 500: Server error → check Laravel logs
```

### Problem: Buchung schlägt fehl
```bash
# Prüfe ob check_availability funktioniert
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "check_availability"

# Prüfe Retell Dashboard
https://app.retellai.com/dashboard/calls
```

---

## 📊 V110 Features

| Feature | Aktiv | Getestet |
|---------|-------|----------|
| check_customer (Kundenerkennung) | 🟡 Teilweise | ⏳ |
| Near-Match Logic (±30 Min) | ✅ Ja | ⏳ |
| Error Callback Handling | ✅ Ja | ⏳ |
| Smart Data Collection | ✅ Ja | ⏳ |
| Context Initialization | ✅ Ja | ⏳ |

---

## 📚 Vollständige Dokumentation

- **HTML:** https://api.askproai.de/docs/retell/v110/index.html
- **Deployment:** `/var/www/api-gateway/RETELL_V110_DEPLOYMENT_SUMMARY.md`
- **Markdown Docs:** `/var/www/api-gateway/RETELL_V110_*.md` (7 Dateien)

---

## 🎯 Nächste Schritte

1. **JETZT:** Test Call durchführen (+493033081738)
2. **HEUTE:** Logs während Test monitoren
3. **DIESE WOCHE:** Full customer recognition implementieren
4. **NÄCHSTE 2 WOCHEN:** A/B Testing V110 vs V109

---

**Agent ID:** `agent_b9dd70fe509b12e031f9298854`
**Flow ID:** `conversation_flow_f119ebba25c7`
**Phone:** `+493033081738`
**Version:** V110 (Initial Release)
**Status:** ✅ READY FOR TESTING
