# Task 0 Quick Reference - Agent Config Fix

**Status**: 🔴 BLOCKER (Manual Task)
**Priority**: Execute NOW before E2E tests

---

## ⚠️ CRITICAL DECISION REQUIRED: Version Strategy

### Current API Endpoint Status
```
✅ /api/retell/v17/check-availability      → RetellFunctionCallHandler::checkAvailabilityV17
✅ /api/retell/v17/book-appointment         → RetellFunctionCallHandler::bookAppointmentV17
❌ /api/retell/v17/cancel-appointment       → DOES NOT EXIST
❌ /api/retell/v17/reschedule-appointment   → DOES NOT EXIST

✅ /api/retell/cancel-appointment-v4        → RetellFunctionCallHandler::cancelAppointmentV4 (HAS getCanonicalCallId fix)
✅ /api/retell/reschedule-appointment-v4    → RetellFunctionCallHandler::rescheduleAppointmentV4 (HAS getCanonicalCallId fix)
```

### Your Requirement (Adjustment #2)
> "Entweder ALLE v17 vereinheitlichen ODER klar dokumentieren welcher Mix"

### 🎯 RECOMMENDED APPROACH: Documented Mix (Pragmatic)

**Rationale**:
1. ✅ v4 endpoints already work and have getCanonicalCallId fix
2. ✅ No code changes needed
3. ✅ Faster deployment
4. ✅ Same security/monitoring coverage

**Configuration**:
```
Agent Tools (Mixed v17/v4 - DOCUMENTED):
├─ check_availability_v17      → /api/retell/v17/check-availability
├─ book_appointment_v17         → /api/retell/v17/book-appointment
├─ cancel_appointment_v4        → /api/retell/cancel-appointment-v4
└─ reschedule_appointment_v4    → /api/retell/reschedule-appointment-v4

All 4 tools: Add call_id parameter + {{call.call_id}} mapping
```

**Documentation**: Add version mix rationale to CHANGELOG and GAP-010

---

### 🔄 ALTERNATIVE: Full v17 Unification (Requires Code Changes)

If you prefer ALL v17, I can create:
1. New controller methods: `cancelAppointmentV17()`, `rescheduleAppointmentV17()`
2. New routes: `/api/retell/v17/cancel-appointment`, `/api/retell/v17/reschedule-appointment`
3. Copy v4 logic + add v17-specific enhancements
4. Update agent configuration to use v17 endpoints

**Estimated effort**: +30 minutes (code + tests)

---

## 🚀 Quick Action Checklist (Recommended Approach)

### 1. Retell Dashboard Configuration (5 min)

**For ALL 4 TOOLS**, add `call_id` parameter:

#### check_availability_v17
```
Endpoint: /api/retell/v17/check-availability
Parameter: call_id (string, required)
Mapping: call_id: {{call.call_id}}
Screenshot: 01_check_availability_v17.png
```

#### book_appointment_v17
```
Endpoint: /api/retell/v17/book-appointment
Parameter: call_id (string, required)
Mapping: call_id: {{call.call_id}}
Screenshot: 02_book_appointment_v17.png
```

#### cancel_appointment_v4 (KEEP v4)
```
Endpoint: /api/retell/cancel-appointment-v4
Parameter: call_id (string, required)
Mapping: call_id: {{call.call_id}}
Screenshot: 03_cancel_appointment_v4.png
Note: v4 retained (has getCanonicalCallId fix)
```

#### reschedule_appointment_v4 (KEEP v4)
```
Endpoint: /api/retell/reschedule-appointment-v4
Parameter: call_id (string, required)
Mapping: call_id: {{call.call_id}}
Screenshot: 04_reschedule_appointment_v4.png
Note: v4 retained (has getCanonicalCallId fix)
```

### 2. Test Call (2 min)
```
Say: "Herrenhaarschnitt, morgen 16 Uhr"
Verify: check_availability_v17 logs show call_id ≠ ""
Save: test_call_log.txt
```

### 3. Documentation (1 min)
```
Update: docs/e2e/CHANGELOG.md
Add: Version mix rationale (v17 for new bookings, v4 for modifications)
```

---

## 📦 Deliverables

Upload to: `docs/e2e/audit/retell-agent-config-verification/`

- [ ] `01_check_availability_v17.png`
- [ ] `02_book_appointment_v17.png`
- [ ] `03_cancel_appointment_v4.png` (v4 retained)
- [ ] `04_reschedule_appointment_v4.png` (v4 retained)
- [ ] `test_call_log.txt`

---

## 🎯 Decision Needed

**Which approach?**

**A) Documented Mix (Recommended)**
- Faster, pragmatic, works now
- Document v17/v4 mix rationale
- All tools get call_id parameter

**B) Full v17 Unification**
- Requires new controller methods
- +30 min development + testing
- Cleaner version consistency

**Your choice?** Reply with **A** or **B** to proceed.

---

## Next Steps After Task 0

Once screenshots + test log are uploaded:

1. ✅ Mark Task 0 complete
2. 🚀 Start Task 3: E2E Tests (7 scenarios + staff disambiguation)
3. 🔄 Parallel: Task 4 (Monitoring + alerts)
4. 📊 Task 5: Timeout validation after first live call
5. 📝 Task 6: Finalize GAP-010 + sync docs
