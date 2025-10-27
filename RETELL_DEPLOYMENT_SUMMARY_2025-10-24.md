# 📝 RETELL DEPLOYMENT - QUICK SUMMARY
## Für schnelles Nachschlagen in Zukunft

**Erstellt**: 2025-10-24
**Letztes Update**: Nach V51 Deployment

---

## 🎯 DAS WICHTIGSTE IN KÜRZE

### 3 Schritte für fehlerfreies Deployment:

1. **Agent publishen**:
   ```bash
   curl -X POST "https://api.retellai.com/publish-agent/agent_f1ce85d06a84afb989dfbb16a9" \
     -H "Authorization: Bearer $RETELL_TOKEN"
   ```

2. **Telefonnummer auf auto-latest setzen**:
   ```bash
   curl -X PATCH "https://api.retellai.com/update-phone-number/+493033081738" \
     -H "Authorization: Bearer $RETELL_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"inbound_agent_id": "agent_f1ce85d06a84afb989dfbb16a9"}'
   ```

3. **Verifizieren**:
   ```bash
   ./scripts/retell_deploy.sh verify
   ```

---

## ⚠️ HÄUFIGSTE FEHLER

1. **Agent published, Phone nicht updated**
   - Symptom: Alte Version läuft weiter
   - Fix: Telefonnummer MUSS updated werden (Schritt 2)

2. **Telefonnummer hat fixe Version**
   - Symptom: Phone nutzt V48, Agent ist V51
   - Fix: `inbound_agent_version` entfernen (= auto-latest)

3. **Flow hat keine Function Nodes**
   - Symptom: check_availability wird nicht aufgerufen
   - Fix: Flow muss explicit function nodes haben (type="function")

---

## 🚀 QUICK DEPLOYMENT

**One-Liner** (nutze mit Vorsicht):
```bash
cd /var/www/api-gateway && ./scripts/retell_deploy.sh full
```

**Sicher mit Verification**:
```bash
# 1. Status prüfen
./scripts/retell_deploy.sh verify

# 2. Deployen
./scripts/retell_deploy.sh deploy

# 3. Test Call
# Rufe +493033081738 an
# Sage: "Termin morgen 10 Uhr Herrenhaarschnitt"

# 4. Logs prüfen
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "check_availability|book_appointment"
```

---

## 📚 VOLLSTÄNDIGE DOKUMENTATION

Für detaillierte Anleitung siehe:
- **Deployment Guide**: `claudedocs/03_API/Retell_AI/RETELL_AGENT_DEPLOYMENT_COMPLETE_GUIDE.md`
- **Root Cause Analysis**: `ROOT_CAUSE_CHECK_AVAILABILITY_COMPLETE_2025-10-24.md`
- **Deployment Success**: `DEPLOYMENT_SUCCESS_V48_CHECK_AVAILABILITY_2025-10-24.md`

---

## 🔍 VERIFICATION CHECKLIST

Nach jedem Deployment:
- [ ] Agent Version erhöht?
- [ ] Phone auf auto-latest oder korrekte Version?
- [ ] Test Call durchgeführt?
- [ ] check_availability wurde aufgerufen?
- [ ] Appointment in DB erstellt?

---

## 📞 TEST CALL ERWARTETER FLOW

```
User: "Termin morgen 10 Uhr Herrenhaarschnitt"
AI: "Und wie heißen Sie?"
User: "Max Mustermann"
AI: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
[✅ check_availability_v17 CALLED]
AI: "Morgen um 10 Uhr ist verfügbar. Soll ich das buchen?"
User: "Ja"
AI: "Einen Moment bitte, ich buche den Termin..."
[✅ book_appointment CALLED]
AI: "Ihr Termin ist gebucht!"
```

---

## 🎓 WAS ICH GELERNT HABE

1. **Agent ≠ Flow ≠ Phone Configuration**
   - Alle drei müssen synchron sein
   - Phone kann fixe Version oder auto-latest haben
   - is_published ist nur für Dashboard, nicht für API

2. **Function Nodes sind CRITICAL**
   - Nur explicit function nodes garantieren Ausführung
   - AI "calling" tools ist unreliable
   - speak_during_execution vermeidet Stille

3. **Publish allein reicht nicht**
   - Phone MUSS updated werden
   - Sonst läuft alte Version weiter
   - auto-latest ist empfohlen

---

**Quick Access**: `/var/www/api-gateway/RETELL_DEPLOYMENT_SUMMARY_2025-10-24.md`
