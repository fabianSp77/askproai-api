# Retell V110 Publishing Guide

**Für:** Production Release von V110 Agent
**Zeitbedarf:** 30-45 Minuten
**Risiko:** Mittel (Rollback verfügbar)

---

## 📋 Pre-Publishing Checklist

### 1. Backend Verifizierung ✅

```bash
# Test check_customer endpoint
curl -X POST "https://api.askproai.de/api/webhooks/retell/check-customer" \
  -H "Content-Type: application/json" \
  -d '{"call_id": "test_123", "from_number": "+4915112345678"}'

# Expected: {"found":false}
```

**Status:** ✅ PASS - Endpoint funktioniert

### 2. Agent Configuration Check ✅

```bash
# Get agent details
curl -X GET "https://api.retellai.com/get-agent/agent_b9dd70fe509b12e031f9298854" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | jq .

# Verify:
# - conversation_flow_id: conversation_flow_f119ebba25c7
# - version: 1
# - webhook_url: https://api.askproai.de/api/webhooks/retell
```

**Status:** ✅ PASS - Agent korrekt konfiguriert

### 3. Phone Number Assignment ✅

```bash
# Verify phone assignment
curl -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  jq '.[] | select(.phone_number=="+493033081738")'

# Verify:
# - inbound_agent_id: agent_b9dd70fe509b12e031f9298854
```

**Status:** ✅ PASS - Telefonnummer zugewiesen

---

## 🚀 Publishing Steps

### Step 1: Pre-Production Test (5 Minuten)

**Test Call durchführen:**

1. Rufe +493033081738 an
2. Test Szenario: "Ich möchte einen Herrenhaarschnitt buchen, morgen um 14 Uhr"
3. Verifiziere:
   - Agent antwortet korrekt
   - Verfügbarkeit wird geprüft
   - Buchung wird durchgeführt

**Laravel Logs monitoren:**
```bash
# In separatem Terminal
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Erwartete Logs:
# - "Retell get_current_context called"
# - "Retell check_customer called"
# - "Retell function call: check_availability_v17"
# - "Retell function call: start_booking"
# - "Retell function call: confirm_booking"
```

**Go/No-Go Decision:**
- ✅ GO: Alle Logs erscheinen, Buchung erfolgreich
- ❌ NO-GO: Fehler in Logs, Buchung schlägt fehl → Siehe Troubleshooting

---

### Step 2: Publish Agent (Optional)

**Aktueller Status:**
- Agent Version: 1
- is_published: false
- Funktioniert trotzdem (Phone Number assigned)

**Option A: Als Draft belassen**
```
Pro: Schnellere Iterationen möglich
Contra: Kein "production" Status in Dashboard
Empfehlung: Für initiales Testing OK
```

**Option B: Agent publishen**
```bash
curl -X POST "https://api.retellai.com/publish-agent/agent_b9dd70fe509b12e031f9298854" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Empfehlung:** Als Draft belassen für erste Testphase (1-2 Tage)

---

### Step 3: Monitoring Setup (10 Minuten)

**1. Laravel Log Monitoring:**
```bash
# Create log monitoring script
cat > /var/www/api-gateway/scripts/monitor_v110.sh << 'SCRIPT'
#!/bin/bash
echo "=== V110 Call Monitoring ==="
tail -f /var/www/api-gateway/storage/logs/laravel.log | \
  grep --line-buffered -E "(check_customer|check_availability|start_booking|confirm_booking)"
SCRIPT

chmod +x /var/www/api-gateway/scripts/monitor_v110.sh

# Run monitoring
/var/www/api-gateway/scripts/monitor_v110.sh
```

**2. Retell Dashboard Monitoring:**
- URL: https://app.retellai.com/dashboard/calls
- Filter by: agent_b9dd70fe509b12e031f9298854
- Monitor: Call success rate, average duration, error rate

---

### Step 4: Gradual Rollout (Optional)

**Wenn vorsichtige Einführung gewünscht:**

```bash
# Phase 1: Internal Testing (Day 1-2)
# - Nur Team-Mitglieder testen
# - Telefonnummer: +493033081738 → V110

# Phase 2: Beta Testing (Day 3-5)
# - Ausgewählte Kunden
# - 10-20% des Traffics

# Phase 3: Full Rollout (Day 6+)
# - Alle Kunden
# - 100% des Traffics
```

**Für sofortiges Full Rollout:**
- V110 ist bereits auf +493033081738
- Alle eingehenden Anrufe nutzen V110

---

## 📊 Success Metrics

### Key Performance Indicators (KPIs)

**1. Call Success Rate**
```
Target: >95% successful bookings
Measurement: Retell Dashboard → Calls → Success Rate
```

**2. Average Call Duration**
```
Target: <3 Minuten (V110 sollte schneller sein durch Smart Data Collection)
Measurement: Retell Dashboard → Calls → Average Duration
```

**3. Customer Recognition Rate**
```
Target: >50% (nach Full Implementation)
Current: 0% (check_customer gibt immer false zurück)
Measurement: Laravel Logs → count(found=true) / total_calls
```

**4. Near-Match Acceptance Rate**
```
Target: >60% (Alternative wird akzeptiert)
Measurement: Manual tracking in first 2 weeks
```

---

## 🔄 Rollback Plan

### Bei kritischen Problemen

**Severity 1: Keine Anrufe möglich**
```bash
# IMMEDIATE ROLLBACK zu V109
curl -X PATCH "https://api.retellai.com/update-phone-number/+493033081738" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d '{
    "inbound_agent_id": "agent_45daa54928c5768b52ba3db736",
    "nickname": "+493033081738 Friseur 1 V109 (ROLLBACK)"
  }'

# Verify
curl -X GET "https://api.retellai.com/list-phone-numbers" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  jq '.[] | select(.phone_number=="+493033081738")'
```

**Timeframe:** < 2 Minuten

**Severity 2: Hohe Fehlerrate (>10%)**
- Untersuche Logs für spezifische Fehler
- Erwäge Rollback wenn nicht innerhalb 30 Minuten lösbar

**Severity 3: Niedrige Fehlerrate (<5%)**
- Monitoring fortsetzen
- Fixes in nächster Version

---

## 🐛 Known Issues & Workarounds

### Issue 1: check_customer gibt immer found=false

**Impact:** Niedrig
**Workaround:** V110 funktioniert normal (behandelt alle als Neukunden)
**Fix Timeline:** 1-2 Wochen (Full implementation)

```php
// TODO in CheckCustomerController.php
// Add full customer recognition logic
// - Query Customer model by phone
// - Analyze appointment history
// - Predict service with confidence
// - Return predicted_service, service_confidence, preferred_staff
```

### Issue 2: Agent Status "unpublished"

**Impact:** Keine (Phone Number funktioniert)
**Workaround:** Ignorieren oder manuell publishen
**Fix Timeline:** Optional

---

## 📈 Post-Launch Activities

### Week 1: Intensive Monitoring

**Daily Tasks:**
1. Review call logs (morning & evening)
2. Check success rate in Retell Dashboard
3. Monitor Laravel error logs
4. Collect user feedback

**Weekly Review:**
- Analyze KPIs vs targets
- Identify improvement areas
- Plan fixes for Week 2

### Week 2: Optimization

**Based on Week 1 data:**
1. Adjust confidence threshold (currently 0.8)
2. Fine-tune near-match window (currently ±30 min)
3. Optimize prompts based on real conversations
4. Implement missing features (full customer recognition)

### Week 3+: Expansion

**After proven stability:**
1. Roll out to additional phone numbers
2. Enable full customer recognition
3. A/B test V110 vs V109 improvements
4. Consider V111 with advanced features

---

## 📞 Support Contacts

### Technical Issues
```
Laravel Logs: tail -f /var/www/api-gateway/storage/logs/laravel.log
Retell Dashboard: https://app.retellai.com
Server: root@api.askproai.de
```

### Emergency Rollback
```
Rollback Script: See "Rollback Plan" section above
Estimated Time: <2 minutes
```

---

## ✅ Final Publishing Checklist

**Before Going Live:**
- [ ] Backend endpoint tested and working
- [ ] Agent configuration verified
- [ ] Phone number assigned correctly
- [ ] Test call completed successfully
- [ ] Monitoring tools set up
- [ ] Team notified of deployment
- [ ] Rollback plan documented and tested

**After Going Live:**
- [ ] Monitor first 10 calls closely
- [ ] Check logs every hour (first day)
- [ ] Review success rate after 24h
- [ ] Collect initial user feedback
- [ ] Plan Week 1 review meeting

---

## 🎯 Success Criteria

**After 1 Week, V110 is successful if:**
1. ✅ Call success rate >95%
2. ✅ Average call duration <3 minutes
3. ✅ No critical bugs reported
4. ✅ User satisfaction maintained or improved
5. ✅ Zero rollback incidents

**If criteria NOT met:**
- Analyze root causes
- Implement fixes in V110.1
- Consider temporary rollback to V109

---

## 📚 Additional Resources

- **HTML Docs:** https://api.askproai.de/docs/retell/v110/index.html
- **Quick Start:** `/var/www/api-gateway/RETELL_V110_QUICK_START.md`
- **Deployment Summary:** `/var/www/api-gateway/RETELL_V110_DEPLOYMENT_SUMMARY.md`
- **Full Docs:** `/var/www/api-gateway/RETELL_V110_*.md` (7 files)

---

**Version:** V110 (Initial Release)
**Published By:** Claude Code
**Date:** 2025-11-10
**Status:** ✅ READY FOR PRODUCTION
