# Task 0 - Manual Execution Checklist

**Status**: 🔴 READY TO EXECUTE
**Approach**: Option A - Documented Mix (v17 + v4)
**Time Estimate**: 5-8 minutes

---

## Pre-Flight Check

- [ ] You have Retell Dashboard access (https://app.retell.ai/)
- [ ] Agent Name: "Friseur1 Fixed V2 (parameter_mapping)"
- [ ] Screenshot tool ready (browser screenshot or Cmd+Shift+4 on Mac)
- [ ] This checklist open for reference

---

## Step 1: Configure Tools (4 tools × 1 min each)

### Tool 1: check_availability_v17

**Navigation**: Retell Dashboard → Agents → Friseur1 Fixed V2 → Tools → check_availability_v17

1. [ ] **Open** tool configuration
2. [ ] **Navigate** to "Parameters" section
3. [ ] **Add** new parameter:
   - Name: `call_id`
   - Type: `string`
   - Description: "Unique Retell call identifier for tracking and debugging"
   - Required: ✅ **YES**
4. [ ] **Navigate** to "Function Node" section
5. [ ] **Add** mapping: `call_id: {{call.call_id}}`
6. [ ] **Screenshot** entire screen (show schema + mapping)
7. [ ] **Save** screenshot as: `01_check_availability_v17.png`
8. [ ] **Save** tool configuration in Retell

---

### Tool 2: book_appointment_v17

**Navigation**: Retell Dashboard → Agents → Friseur1 Fixed V2 → Tools → book_appointment_v17

1. [ ] **Open** tool configuration
2. [ ] **Navigate** to "Parameters" section
3. [ ] **Add** new parameter:
   - Name: `call_id`
   - Type: `string`
   - Description: "Unique Retell call identifier for tracking and debugging"
   - Required: ✅ **YES**
4. [ ] **Navigate** to "Function Node" section
5. [ ] **Add** mapping: `call_id: {{call.call_id}}`
6. [ ] **Screenshot** entire screen (show schema + mapping)
7. [ ] **Save** screenshot as: `02_book_appointment_v17.png`
8. [ ] **Save** tool configuration in Retell

---

### Tool 3: cancel_appointment_v4 (v4 RETAINED)

**Navigation**: Retell Dashboard → Agents → Friseur1 Fixed V2 → Tools → cancel_appointment_v4

1. [ ] **Open** tool configuration
2. [ ] **Navigate** to "Parameters" section
3. [ ] **Add** new parameter:
   - Name: `call_id`
   - Type: `string`
   - Description: "Unique Retell call identifier for tracking and debugging"
   - Required: ✅ **YES**
4. [ ] **Navigate** to "Function Node" section
5. [ ] **Add** mapping: `call_id: {{call.call_id}}`
6. [ ] **Screenshot** entire screen (show schema + mapping)
7. [ ] **Save** screenshot as: `03_cancel_appointment_v4.png`
8. [ ] **Save** tool configuration in Retell
9. [ ] **NOTE**: Tool remains v4, endpoint stays `/api/retell/cancel-appointment-v4`

---

### Tool 4: reschedule_appointment_v4 (v4 RETAINED)

**Navigation**: Retell Dashboard → Agents → Friseur1 Fixed V2 → Tools → reschedule_appointment_v4

1. [ ] **Open** tool configuration
2. [ ] **Navigate** to "Parameters" section
3. [ ] **Add** new parameter:
   - Name: `call_id`
   - Type: `string`
   - Description: "Unique Retell call identifier for tracking and debugging"
   - Required: ✅ **YES**
4. [ ] **Navigate** to "Function Node" section
5. [ ] **Add** mapping: `call_id: {{call.call_id}}`
6. [ ] **Screenshot** entire screen (show schema + mapping)
7. [ ] **Save** screenshot as: `04_reschedule_appointment_v4.png`
8. [ ] **Save** tool configuration in Retell
9. [ ] **NOTE**: Tool remains v4, endpoint stays `/api/retell/reschedule-appointment-v4`

---

## Step 2: Publish Agent Changes

1. [ ] **Review** all 4 tools have call_id parameter
2. [ ] **Click** "Publish" or "Deploy" in agent configuration
3. [ ] **Confirm** deployment
4. [ ] **Wait** for agent to be live (~30 seconds)

---

## Step 3: Test Call Execution

### Make Test Call

1. [ ] **Call** Friseur 1 phone number: **[INSERT NUMBER HERE]**
2. [ ] **Say**: "Ich möchte einen Herrenhaarschnitt, morgen um 16 Uhr"
3. [ ] **Wait** for agent to process availability check
4. [ ] **Expected**: Agent should proceed normally (no "Fehler aufgetreten")

### Capture Evidence

1. [ ] **Navigate** to Retell Dashboard → Calls → [Select your test call]
2. [ ] **Open** "Function Calls" or "Request Logs" section
3. [ ] **Find** the `check_availability_v17` request
4. [ ] **Verify** payload contains:
   ```json
   {
     "call": {
       "call_id": "call_abc123xyz456..."
     },
     "args": {
       "call_id": "call_abc123xyz456...",  // ✅ MUST BE PRESENT & NOT EMPTY
       "service_name": "Herrenhaarschnitt",
       "date": "2025-11-04",
       "time": "16:00"
     }
   }
   ```
5. [ ] **Copy** the request payload
6. [ ] **Save** as: `test_call_log.txt`

---

## Step 4: Check Laravel Logs (Optional but Recommended)

```bash
# SSH to server
cd /var/www/api-gateway

# Check last 50 lines for CANONICAL_CALL_ID entries
tail -50 storage/logs/laravel.log | grep CANONICAL_CALL_ID
```

**Expected Output**:
```
[2025-11-03 22:30:00] ✅ CANONICAL_CALL_ID: Resolved {"call_id":"call_abc123...","source":"webhook"}
```

**NOT Expected**:
```
⚠️ CANONICAL_CALL_ID: Both sources empty  ❌ BAD - should not see this
```

---

## Step 5: Upload Artifacts

```bash
# Navigate to project root
cd /var/www/api-gateway

# Upload screenshots (4 files)
# Place in: docs/e2e/audit/retell-agent-config-verification/

# Upload test call log (1 file)
# Save as: docs/e2e/audit/retell-agent-config-verification/test_call_log.txt
```

**Required Files**:
- [ ] `01_check_availability_v17.png` (uploaded)
- [ ] `02_book_appointment_v17.png` (uploaded)
- [ ] `03_cancel_appointment_v4.png` (uploaded)
- [ ] `04_reschedule_appointment_v4.png` (uploaded)
- [ ] `test_call_log.txt` (uploaded)

---

## Step 6: Finalize Documentation

### Update README Status

Edit: `docs/e2e/audit/retell-agent-config-verification/README.md`

Change status from:
```
- [ ] 01_check_availability_v17.png uploaded
```

To:
```
- [x] 01_check_availability_v17.png uploaded ✅
```

(Repeat for all 5 artifacts)

### CHANGELOG Already Prepared

✅ CHANGELOG entry already written in: `docs/e2e/CHANGELOG.md` (lines 7-67)

---

## Step 7: Notify for Next Steps

Once all artifacts are uploaded and verified:

1. [ ] Mark Task 0 as **COMPLETED** in todo list
2. [ ] Notify: "Task 0 complete - ready for Task 3 (E2E Tests)"
3. [ ] Task 3 (E2E Tests) + Task 4 (Monitoring) can now start in parallel

---

## Acceptance Criteria (Validation)

Before marking Task 0 complete, verify:

✅ **Configuration**:
- [ ] All 4 tools have `call_id` parameter in schema
- [ ] All 4 tools have `call_id: {{call.call_id}}` in function node
- [ ] Agent published and live

✅ **Test Evidence**:
- [ ] Test call executed successfully
- [ ] `args.call_id` present in request log
- [ ] Value is NOT empty string or "None"
- [ ] Laravel logs show `✅ CANONICAL_CALL_ID: Resolved`

✅ **Documentation**:
- [ ] 4 screenshots uploaded
- [ ] test_call_log.txt uploaded
- [ ] README.md status checkboxes updated
- [ ] CHANGELOG.md entry reviewed

✅ **Monitoring**:
- [ ] 0 `empty_call_id_occurrences` in last 15 minutes
- [ ] No "Fehler aufgetreten" responses from agent

---

## Troubleshooting

### Problem: Tool doesn't show "Parameters" section

**Solution**:
- Check you're editing the correct agent
- Ensure tool type is "Custom Function" (not built-in)
- Try refreshing Retell Dashboard

### Problem: {{call.call_id}} syntax error

**Solution**:
- Verify exact syntax: `call_id: {{call.call_id}}`
- No spaces inside `{{ }}`
- Case-sensitive: `call` not `Call`

### Problem: Test call still shows empty call_id

**Solution**:
1. Verify agent was published (not just saved)
2. Check correct agent is assigned to phone number
3. Wait 1-2 minutes for deployment propagation
4. Try another test call
5. Check Retell Dashboard → Agent → Version (should show latest)

### Problem: Retell timeout > 10s

**Solution** (temporary):
- Increase timeout in tool configuration to 15s
- Add note in CHANGELOG: "Temporary 15s timeout due to [reason]"
- Investigate Cal.com API performance (Task 5)

---

## Time Tracking

**Estimated**: 5-8 minutes
**Actual**: _______ minutes

---

## Next Steps After Completion

1. ✅ Mark Task 0 as COMPLETED
2. 🚀 Start Task 3: E2E Tests (7 scenarios + staff disambiguation)
3. 🔄 Start Task 4: Monitoring + Alerts (can run in parallel)
4. 📊 Task 5: Timeout validation after first successful live call
5. 📝 Task 6: Finalize GAP-010 + sync docs to Hub
