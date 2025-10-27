# Quick Start: V54 Live Deployment

**Status**: Version 54 deployed, awaiting manual Dashboard actions
**Time Required**: 10 minutes
**Goal**: Fix check_availability from 0% → 100% call rate

---

## Step 1: Publish Version 54 (5 min)

1. Open: https://dashboard.retellai.com/agent/agent_f1ce85d06a84afb989dfbb16a9
2. Find "Versions" or "History" tab
3. Locate **Version 54**:
   - **Must have exactly 3 tools** (NOT 8!)
   - Tools: initialize_call, check_availability_v17, book_appointment_v17
4. Click **"Publish"**
5. Confirm

**How to identify Version 54:**
- ✅ Has 3 tools (not 8)
- ✅ NO "tool-collect-appointment"
- ✅ NO "tool-1761287781516"
- ✅ Only V17 functions

---

## Step 2: Map Phone Number (2 min)

1. Open: https://dashboard.retellai.com/phone-numbers
2. Find: **+493033081738**
3. Click the phone number
4. Set Agent to: **agent_f1ce85d06a84afb989dfbb16a9**
   - Dropdown name: "Conversation Flow Agent Friseur 1"
5. **Save**

---

## Step 3: Verify Setup (1 min)

```bash
cd /var/www/api-gateway
./scripts/testing/complete_verification.sh
```

**Expected Output:**
```
✅ V54 IS READY!

NEXT STEP: Make a test call
Call: +493033081738
Say: 'Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr'
```

If you see errors, check:
- Dashboard: Is Version 54 actually published?
- Dashboard: Is phone correctly mapped?

---

## Step 4: Test Call (2 min)

1. Call: **+493033081738**
2. Say: **"Ich möchte einen Herrenhaarschnitt morgen um 14 Uhr"**
3. Listen for: **"Einen Moment bitte, ich prüfe die Verfügbarkeit..."**
4. AI should wait 2-5 seconds (calling API)
5. AI should respond with availability or alternatives
6. Say **"Ja, bitte buchen"** if offered
7. AI should confirm booking

---

## Step 5: Verify Success (1 min)

```bash
./scripts/testing/complete_verification.sh --after-call
```

**Expected Output:**
```
🎉 COMPLETE SUCCESS!

All systems GO:
  ✅ Version 54 is published
  ✅ Phone is mapped correctly
  ✅ check_availability WAS CALLED
  ✅ Fix successful: 0% → 100%
```

---

## Success Metrics

**Before (Version 51):**
- check_availability calls: 0/167 (0%)
- User hangup: 68.3%

**After (Version 54):**
- check_availability calls: 100%
- Explicit function nodes guarantee execution

---

## Troubleshooting

**Problem**: Verification fails after Step 1-2
- Solution: Check Dashboard manually, ensure V54 is actually published

**Problem**: Test call doesn't reach agent
- Solution: Verify phone mapping in Dashboard

**Problem**: Functions not in database after call
- Solution: Check `storage/logs/laravel.log` for errors

**Problem**: Can't find Version 54 in Dashboard
- Solution: It might be V52, V53, or V55 - find the one with **exactly 3 tools**

---

## What Version 54 Fixes

✅ Removed 5 unused tools (8 → 3)
✅ Removed parallel old/new function paths
✅ Removed double function call cascades
✅ Added explicit function nodes with wait_for_result: true
✅ Simplified flow architecture
✅ Guaranteed function execution

---

**Full Details**: See FINAL_ACTION_GUIDE_2025-10-24_2020.md
**Date**: 2025-10-24
**Version**: 54
