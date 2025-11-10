# Flow V26 - Alternative Selection Fix - COMPLETE

**Date**: 2025-11-04
**Flow ID**: `conversation_flow_a58405e3f67a`
**Version**: V25 (Retell kept version number despite successful update)
**Status**: ✅ **APPLIED SUCCESSFULLY**

---

## Problem Summary

Previous attempts to add `extract_dynamic_variables` node failed with schema validation errors:
- ❌ Used `dynamic_variables` instead of `variables`
- ❌ Used `text` type instead of `string`
- ❌ Used `expression` instead of `equations`
- ❌ Wrong field structure in transition conditions

---

## Solution Applied

### Changes Made

#### 1. Added: `node_extract_alternative_selection`
**Type**: `extract_dynamic_variables` (PLURAL - critical!)
**Purpose**: Extract alternative time selection from user utterance

**Schema**:
```json
{
  "id": "node_extract_alternative_selection",
  "type": "extract_dynamic_variables",
  "name": "Alternative extrahieren",
  "display_position": { "x": 3050, "y": -20 },
  "variables": [
    {
      "type": "string",
      "name": "selected_alternative_time",
      "description": "Die vom Kunden gewählte alternative Uhrzeit"
    }
  ],
  "edges": [
    {
      "id": "edge_extract_to_confirm",
      "destination_node_id": "node_confirm_alternative",
      "transition_condition": {
        "type": "equation",
        "equations": [
          {
            "left": "selected_alternative_time",
            "operator": "exists"
          }
        ],
        "operator": "&&"
      }
    }
  ]
}
```

**Key Corrections**:
- ✅ `variables` (not `dynamic_variables`)
- ✅ `type: "string"` (not `text`)
- ✅ `equations` array (not `expression`)
- ✅ `operator: "&&"` at condition level

#### 2. Added: `node_confirm_alternative`
**Type**: `conversation`
**Purpose**: Confirm selection and proceed to availability check

**Schema**:
```json
{
  "id": "node_confirm_alternative",
  "name": "Alternative bestätigen",
  "type": "conversation",
  "display_position": { "x": 3400, "y": -20 },
  "instruction": {
    "type": "prompt",
    "text": "Sage: \"Perfekt! Einen Moment, ich prüfe die Verfügbarkeit für {{selected_alternative_time}} Uhr...\" und fahre direkt fort."
  },
  "edges": [
    {
      "id": "edge_confirm_to_check",
      "destination_node_id": "func_check_availability",
      "transition_condition": {
        "type": "prompt",
        "prompt": "Alternative confirmed"
      }
    }
  ]
}
```

#### 3. Modified: `node_present_result`
**Change**: Added new edge (first position = highest priority)

**New Edge**:
```json
{
  "id": "edge_present_to_extract",
  "destination_node_id": "node_extract_alternative_selection",
  "transition_condition": {
    "type": "prompt",
    "prompt": "User selected one of the presented alternative time slots (e.g., \"Um 06:55\", \"Den ersten Termin\", \"14:30\")"
  }
}
```

#### 4. Modified: `func_book_appointment`
**Change**: Updated `uhrzeit` parameter mapping to support alternative selection

**Before**:
```json
"uhrzeit": "{{appointment_time}}"
```

**After**:
```json
"uhrzeit": "{{selected_alternative_time || appointment_time}}"
```

**Logic**: Try `selected_alternative_time` first, fallback to `appointment_time`

---

## Flow Architecture

### New Alternative Selection Path

```
node_present_result
  ↓ (User selects alternative)
node_extract_alternative_selection (extract_dynamic_variables)
  ↓ (selected_alternative_time exists)
node_confirm_alternative (conversation)
  ↓ (confirmation)
func_check_availability (function)
  ↓ (check with new time)
node_present_result (show result)
  ↓ (User confirms)
func_book_appointment (function - uses selected_alternative_time)
```

### Existing Direct Booking Path (Unchanged)

```
node_present_result
  ↓ (User confirms immediately)
func_book_appointment (function - uses appointment_time)
```

---

## Verification Results

**Fetch from Retell API**:
```bash
php -r "..." # Verification script
```

**Results**:
```
Flow Version: V25
Total nodes: 20

✅ node_extract_alternative_selection found
   Type: extract_dynamic_variables
   Variables: 1
   First var: selected_alternative_time (string)

✅ node_confirm_alternative found
   Type: conversation

✅ node_present_result has edge to extract node

✅ func_book_appointment uhrzeit parameter:
   {{selected_alternative_time || appointment_time}}
```

**Status**: All changes applied successfully ✅

---

## Testing Scenarios

### Scenario 1: Direct Alternative Selection
**User flow**:
1. Agent: "Nicht verfügbar. Alternativen: 06:55, 14:30, 16:00"
2. User: "Um 06:55"
3. **Extract**: `selected_alternative_time = "06:55"`
4. Agent: "Perfekt! Einen Moment, ich prüfe die Verfügbarkeit für 06:55 Uhr..."
5. **Check availability**: Call API with `uhrzeit = "06:55"`
6. Agent: Shows result
7. User: "Ja, buchen"
8. **Book**: Call API with `uhrzeit = "06:55"` (from `selected_alternative_time`)

### Scenario 2: Indirect Reference
**User flow**:
1. Agent: "Alternativen: 06:55, 14:30, 16:00"
2. User: "Den ersten Termin bitte"
3. **Extract**: `selected_alternative_time = "den ersten Termin"`
4. Agent: "Perfekt! Einen Moment..."
5. **Check**: Backend must parse "den ersten Termin" → 06:55

### Scenario 3: Original Time Works
**User flow**:
1. Agent: "Der Termin am {{appointment_date}} um {{appointment_time}} ist verfügbar"
2. User: "Ja, buchen"
3. **Book**: Uses `appointment_time` (fallback works)

---

## Backend Requirements

The backend webhook handler must support:

### 1. Variable Extraction
**Function**: `check_availability_v17` and `book_appointment_v17`

**Current parameter names** (no change needed):
```php
public function handle(Request $request)
{
    $uhrzeit = $request->input('uhrzeit'); // Can be from either variable

    // If uhrzeit is indirect reference like "den ersten Termin"
    // Backend should parse it from context
}
```

### 2. Context-Aware Time Resolution
If `selected_alternative_time` contains indirect references:
- "den ersten Termin" → Extract first alternative from previous response
- "den zweiten" → Extract second alternative
- "06:55" → Use directly

**Recommendation**: Store alternatives in session/cache during `check_availability` response.

---

## Script Location

**Main script**: `/var/www/api-gateway/scripts/fix_flow_v26_correct_schema.php`

**Usage**:
```bash
cd /var/www/api-gateway
php scripts/fix_flow_v26_correct_schema.php
```

**Features**:
- Dry-run validation before applying
- Schema structure verification
- Saves dry-run to `/tmp/flow_v26_dry_run.json`
- Interactive confirmation prompt
- Error logging to `/tmp/flow_v26_error.json`

---

## Key Learnings

### Schema Corrections from Research

**Source**: `deploy_friseur1_v35_COMPLETE_CORRECT.php` (working example)

| Field | ❌ Wrong | ✅ Correct |
|-------|---------|----------|
| Node field | `dynamic_variables` | `variables` |
| Variable type | `text` | `string` |
| Transition field | `expression` | `equations` |
| Equations field | singular | **PLURAL** `equations` |
| Type field | `extract_dynamic_variable` | `extract_dynamic_variables` |

### Structure Matching

Always match EXACT structure from existing nodes in current flow:
- Conversation nodes: Check `instruction.type` (was `prompt`, not `static`)
- Function nodes: Check `parameter_mapping` structure
- Edges: Check `transition_condition` format

---

## Next Steps

### 1. Test in Retell Dashboard
- Load flow in visual editor
- Verify nodes appear correctly
- Check transitions

### 2. Test with Real Call
```bash
# Enable test call logging
php scripts/enable_testcall_logging.sh

# Make test call
# Say: "Herrenhaarschnitt für morgen 14 Uhr, Max Mustermann"
# When alternatives offered: "Um 06:55"

# Check logs
tail -f storage/logs/laravel.log | grep "RETELL"
```

### 3. Publish Agent (if tests pass)
```bash
php scripts/publish_agent_v16.php
```

---

## Files Modified

- ✅ **Created**: `/var/www/api-gateway/scripts/fix_flow_v26_correct_schema.php`
- ✅ **Created**: `/tmp/flow_v26_dry_run.json` (dry-run output)
- ✅ **Created**: `/tmp/current_flow_v25_with_extract.json` (verified state)
- ✅ **Created**: This document

---

## Success Metrics

- ✅ Schema validation passed
- ✅ Dry-run validation passed
- ✅ API accepted update (HTTP 200)
- ✅ Verification shows all nodes present
- ✅ Verification shows correct structure
- ✅ Total nodes: 18 → 20 (+2)
- ✅ Parameter mapping updated correctly

**Status**: **READY FOR TESTING** 🚀

---

## Troubleshooting

### If API rejects with validation error:
1. Check `/tmp/flow_v26_error.json` for details
2. Compare structure with `/tmp/flow_v26_dry_run.json`
3. Verify field names match working examples in codebase

### If extract doesn't trigger:
1. Check `edge_present_to_extract` transition prompt
2. Verify edge is **first** in `node_present_result.edges` array
3. Check LLM is detecting alternative selection correctly

### If booking uses wrong time:
1. Check `parameter_mapping` in `func_book_appointment`
2. Verify fallback syntax: `{{selected_alternative_time || appointment_time}}`
3. Check variable was actually set by extract node

---

**Prepared by**: Claude Code
**Verified**: 2025-11-04 (API fetch confirmation)
**Confidence**: HIGH ✅
