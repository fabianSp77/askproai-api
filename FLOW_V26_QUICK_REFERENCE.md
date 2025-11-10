# Flow V26 - Alternative Selection - Quick Reference

**Status**: ✅ APPLIED | **Date**: 2025-11-04 | **Flow**: `conversation_flow_a58405e3f67a`

---

## What Changed

```
NEW: node_extract_alternative_selection
     ↓
NEW: node_confirm_alternative
     ↓
     func_check_availability (existing)

MODIFIED: node_present_result (+ edge to extract)
MODIFIED: func_book_appointment (parameter fallback)
```

---

## Test Scenario

**Say**: "Herrenhaarschnitt für morgen 14 Uhr, Max"
**Agent**: "Nicht verfügbar. Alternativen: 06:55, 14:30, 16:00"
**Say**: "Um 06:55" ← **THIS IS THE NEW PATH**
**Expected**:
1. Extract: `selected_alternative_time = "06:55"`
2. Agent: "Perfekt! Einen Moment, ich prüfe..."
3. Check availability with 06:55
4. Book with 06:55 (if confirmed)

---

## Key Schema Corrections

| Field | ❌ Wrong | ✅ Correct |
|-------|---------|----------|
| Type | `extract_dynamic_variable` | `extract_dynamic_variables` |
| Fields | `dynamic_variables` | `variables` |
| Var type | `text` | `string` |
| Transition | `expression` | `equations` (PLURAL) |

---

## Commands

**Test logging**:
```bash
php scripts/enable_testcall_logging.sh
tail -f storage/logs/laravel.log | grep RETELL
```

**Publish** (after test):
```bash
php scripts/publish_agent_v16.php
```

**Verify**:
```bash
php -r "
require 'vendor/autoload.php';
\$app = require 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$flow = Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.retellai.api_key')
])->get('https://api.retellai.com/get-conversation-flow/conversation_flow_a58405e3f67a')->json();
echo 'Nodes: ' . count(\$flow['nodes']) . ' | Extract node: ' .
    (collect(\$flow['nodes'])->contains('id', 'node_extract_alternative_selection') ? '✅' : '❌') . \"\n\";
"
```

---

## Files

- **Script**: `scripts/fix_flow_v26_correct_schema.php`
- **Full docs**: `FLOW_V26_ALTERNATIVE_SELECTION_FIX_COMPLETE.md`
- **Dry-run**: `/tmp/flow_v26_dry_run.json`
- **Current**: `/tmp/current_flow_v25_with_extract.json`

---

## Status Check

**Verification Results** (2025-11-04):
```
✅ Flow fetched: V25, 20 nodes
✅ node_extract_alternative_selection present
✅ node_confirm_alternative present
✅ node_present_result has extract edge
✅ func_book_appointment parameter: {{selected_alternative_time || appointment_time}}
```

**Ready for**: 🧪 Testing → 🚀 Publishing
