# Task 0: Retell Agent Config Fix - Manual Guide

**Status**: 🔴 BLOCKER - Must complete before E2E tests
**Date**: 2025-11-03
**Issue**: P1 Incident (call_bdcc364c) - Empty call_id Resolution

---

## Problem Statement

Current Retell Agent configuration is MISSING the `call_id` parameter in all 4 function tools. This causes:
- Empty call_id values in function calls
- 100% availability check failures
- Policy engine unable to validate appointments

**Root Cause**: Agent tool schemas don't include `call_id` parameter, and function nodes don't map `{{call.call_id}}` dynamic variable.

---

## What Needs to Be Done

### 1. Access Retell Dashboard
- Navigate to: https://app.retell.ai/
- Open Agent: **"Friseur1 Fixed V2 (parameter_mapping)"**

### 2. Version Unification (v17 for ALL tools)

**Current State** (Mixed v4/v17):
```
✅ check_availability_v17
✅ book_appointment_v17
❌ cancel_appointment_v4      → UPGRADE to v17
❌ reschedule_appointment_v4  → UPGRADE to v17
```

**Target State** (All v17):
```
✅ check_availability_v17
✅ book_appointment_v17
✅ cancel_appointment_v17     → NEW
✅ reschedule_appointment_v17 → NEW
```

### 3. Parameter Configuration for EACH Tool

For **ALL 4 TOOLS**, add the `call_id` parameter:

#### Parameter Schema (JSON)
```json
{
  "type": "object",
  "properties": {
    "call_id": {
      "type": "string",
      "description": "Unique Retell call identifier for tracking and debugging"
    },
    // ... existing parameters (date, time, service_name, etc.)
  },
  "required": ["call_id"]  // Add to required fields
}
```

#### Function Node Mapping
In each tool's function node configuration:
```
call_id: {{call.call_id}}
```

---

## Step-by-Step Instructions

### Tool 1: check_availability_v17

1. Open tool configuration
2. Navigate to "Parameters" section
3. Add new parameter:
   - **Name**: `call_id`
   - **Type**: `string`
   - **Description**: "Unique Retell call identifier for tracking and debugging"
   - **Required**: ✅ Yes
4. Navigate to "Function Node" section
5. Add mapping: `call_id: {{call.call_id}}`
6. **Screenshot**: Capture entire screen showing parameter schema + function node
7. Save filename: `01_check_availability_v17.png`

### Tool 2: book_appointment_v17

1. Open tool configuration
2. Navigate to "Parameters" section
3. Add new parameter:
   - **Name**: `call_id`
   - **Type**: `string`
   - **Description**: "Unique Retell call identifier for tracking and debugging"
   - **Required**: ✅ Yes
4. Navigate to "Function Node" section
5. Add mapping: `call_id: {{call.call_id}}`
6. **Screenshot**: Capture entire screen showing parameter schema + function node
7. Save filename: `02_book_appointment_v17.png`

### Tool 3: cancel_appointment_v17 (UPGRADE from v4)

**CRITICAL**: If cancel_appointment_v4 exists, create NEW v17 version:

1. Duplicate cancel_appointment_v4 → Rename to cancel_appointment_v17
2. Update endpoint URL: `/api/retell/v17/cancel-appointment`
3. Navigate to "Parameters" section
4. Add new parameter:
   - **Name**: `call_id`
   - **Type**: `string`
   - **Description**: "Unique Retell call identifier for tracking and debugging"
   - **Required**: ✅ Yes
5. Navigate to "Function Node" section
6. Add mapping: `call_id: {{call.call_id}}`
7. **Screenshot**: Capture entire screen showing parameter schema + function node
8. Save filename: `03_cancel_appointment_v17.png`
9. **Update agent conversation flow**: Replace v4 tool call with v17

### Tool 4: reschedule_appointment_v17 (UPGRADE from v4)

**CRITICAL**: If reschedule_appointment_v4 exists, create NEW v17 version:

1. Duplicate reschedule_appointment_v4 → Rename to reschedule_appointment_v17
2. Update endpoint URL: `/api/retell/v17/reschedule-appointment`
3. Navigate to "Parameters" section
4. Add new parameter:
   - **Name**: `call_id`
   - **Type**: `string`
   - **Description**: "Unique Retell call identifier for tracking and debugging"
   - **Required**: ✅ Yes
5. Navigate to "Function Node" section
6. Add mapping: `call_id: {{call.call_id}}`
7. **Screenshot**: Capture entire screen showing parameter schema + function node
8. Save filename: `04_reschedule_appointment_v17.png`
9. **Update agent conversation flow**: Replace v4 tool call with v17

---

## Verification Test

### Test Call Script
1. Initiate test call to agent
2. Say: "Ich möchte einen Herrenhaarschnitt, morgen um 16 Uhr"
3. Wait for agent to call `check_availability_v17`
4. Capture request log from Retell dashboard
5. **Verify**: `args.call_id` is present and NOT empty string
6. Save log snippet as: `test_call_log.txt`

### Expected Log Output
```json
{
  "call": {
    "call_id": "call_abc123xyz456"
  },
  "args": {
    "call_id": "call_abc123xyz456",  // ✅ MUST BE PRESENT & NOT EMPTY
    "service_name": "Herrenhaarschnitt",
    "date": "2025-11-04",
    "time": "16:00"
  }
}
```

---

## Artifact Checklist

Save all artifacts to: `docs/e2e/audit/retell-agent-config-verification/`

- [ ] `01_check_availability_v17.png` - Screenshot showing parameter schema + function node
- [ ] `02_book_appointment_v17.png` - Screenshot showing parameter schema + function node
- [ ] `03_cancel_appointment_v17.png` - Screenshot showing parameter schema + function node (NEW v17)
- [ ] `04_reschedule_appointment_v17.png` - Screenshot showing parameter schema + function node (NEW v17)
- [ ] `test_call_log.txt` - Log showing call_id ≠ "" in request

---

## Acceptance Criteria

✅ **Configuration Complete**:
- All 4 tools have `call_id` parameter in schema
- All 4 tools have `call_id: {{call.call_id}}` mapping in function node
- All tools are v17 (no v4 tools remain)

✅ **Verification Test Passed**:
- Test call log shows `args.call_id` is present
- Value is NOT empty string or "None"
- Value matches `call.call_id` from webhook context

✅ **Documentation Updated**:
- 4 screenshots saved in verification directory
- Test call log saved
- CHANGELOG entry added (see template below)

---

## CHANGELOG Template

Add this entry to `docs/e2e/CHANGELOG.md`:

```markdown
## [2025-11-03] - Agent Configuration Fix (Task 0)

### Fixed
- **P1 BLOCKER**: Retell Agent missing call_id parameter in all function tools
- Unified all tools to v17 (removed v4 versions)
- Added `call_id` parameter to all 4 tools:
  - check_availability_v17
  - book_appointment_v17
  - cancel_appointment_v17 (upgraded from v4)
  - reschedule_appointment_v17 (upgraded from v4)

### Technical Changes
- Parameter schema: Added `call_id: string (required)`
- Function node mapping: `call_id: {{call.call_id}}`
- Verification: Test call confirms call_id propagation

### Artifacts
- 4 screenshots: `docs/e2e/audit/retell-agent-config-verification/`
- Test call log: `test_call_log.txt`

### Related
- Issue: P1 Incident (call_bdcc364c)
- Task: GAP-010 Implementation (Task 0)
- Next: E2E Tests (Task 3) now unblocked
```

---

## Notes

⚠️ **CRITICAL**: This is a MANUAL task - requires Retell Dashboard access
⚠️ **BLOCKER**: E2E tests cannot proceed until this is complete
⚠️ **VERSIONING**: Ensure v4 tools are replaced, not left alongside v17

**After Task 0 completion**, immediately notify so E2E tests (Task 3) can begin.
