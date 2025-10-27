# V4 Quick Reference

## ✅ Current Status

```
Agent ID: agent_45daa54928c5768b52ba3db736
Agent Version: 5
Flow Version: 5 (V4 conversation flow)
Flow ID: conversation_flow_a58405e3f67a
Status: ✅ LIVE - READY FOR TESTING
```

---

## 🎯 5 New Capabilities

| Feature | Intent Keyword | Function | Status |
|---------|----------------|----------|--------|
| Book Appointment | "Termin buchen" | V17 (unchanged) | ✅ Preserved |
| Check Appointments | "Meine Termine" | get_appointments_v4 | ✅ NEW |
| Cancel Appointment | "Stornieren" | cancel_appointment_v4 | ✅ NEW |
| Reschedule | "Verschieben" | reschedule_appointment_v4 | ✅ NEW |
| Get Services | "Was bieten Sie an" | get_services_v4 | ✅ NEW |

---

## 🔧 Quick Commands

### Check Agent Status
```bash
php verify_published_agent.php
```

### Analyze Latest Test Call
```bash
php analyze_latest_call.php
```

### Watch Logs Live
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E 'V4|intent|appointment'
```

### Re-publish Agent (if needed)
```bash
php publish_agent_v4_force.php
```

### Rollback to V3 (emergency)
```bash
php deploy_flow_v3.php && php publish_agent_v4_force.php
```

---

## 🔍 Verify V4 is Active

**Expected in logs**:
```json
{
  "node_transition": {
    "new_node_id": "intent_router",
    "new_node_name": "Intent Erkennung"
  }
}
```

**If you see** `"node_collect_info"` → V3 is active, NOT V4!

---

## 📋 Test Checklist

- [ ] Intent Detection works (transitions to intent_router)
- [ ] Book Appointment (V3 path - no regression)
- [ ] Check Appointments (lists correctly)
- [ ] Cancel Appointment (syncs with Cal.com)
- [ ] Reschedule (transaction-safe)
- [ ] Get Services (shows all)

---

## 🚨 Critical Success Metrics

| Metric | V3 Baseline | V4 Target |
|--------|-------------|-----------|
| Booking Success | 95% | ≥95% (no regression) |
| Intent Accuracy | N/A | >90% |
| Reschedule Safety | N/A | 100% (transaction) |
| Latency (booking) | <3s | <3s |

---

## 📂 Key Files

### Code
- `app/Http/Controllers/RetellFunctionCallHandler.php` (Lines 4608-5023)
- `routes/api.php` (Lines 292-316)
- `friseur1_conversation_flow_v4_complete.json`

### Documentation
- `V4_DEPLOYMENT_SUCCESS_2025-10-25.md` (Full deployment report)
- `CONVERSATION_FLOW_V4_COMPLETE_2025-10-25.md` (Technical details)
- `V4_TESTANLEITUNG.md` (Test guide)
- `TESTCALL_ANALYSIS_V4_2025-10-25_1256.md` (First test analysis)

### Scripts
- `deploy_flow_v4.php` (Deploy V4 flow)
- `publish_agent_v4_force.php` (Publish agent)
- `verify_published_agent.php` (Check status)
- `check_agent_flow_config.php` (Diagnostic)

---

## 💡 Next Step

**Make a test call** 📞

Then analyze:
```bash
php analyze_latest_call.php
```

Look for: `"node_id": "intent_router"` ✅
