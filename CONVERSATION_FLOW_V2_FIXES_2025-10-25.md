# Conversation Flow V2 - Root Cause Analysis & Fixes
## Date: 2025-10-25

---

## Executive Summary

Fixed critical Cal.com metadata validation issue and identified missing tool parameter definition in Conversation Flow.

**Status**: ✅ Backend Fix Deployed | ⚠️ Flow Update Required

---

## Root Cause Analysis

### Issue 1: Cal.com Metadata Validation ✅ FIXED

**Error**:
```
Cal.com API request failed: POST /bookings (HTTP 400)
"metadata property is wrong, Metadata must have at most 50 keys,
each key up to 40 characters, and string values up to 500 characters."
```

**Root Cause**:
`CalcomService.php` was only validating value length (500 chars) but NOT:
- Number of keys (max 50)
- Key length (max 40 chars)
- Null values (which Cal.com may reject)

**Fix Applied** (`app/Services/CalcomService.php` lines 102-149):
```php
// Cal.com V2 API metadata limits:
// - Max 50 keys
// - Each key max 40 characters
// - Each string value max 500 characters
$sanitizedMetadata = [];
$keyCount = 0;

foreach ($metadata as $key => $value) {
    // Skip null values - Cal.com may not accept them
    if ($value === null) {
        Log::debug('Cal.com metadata: Skipping null value', ['key' => $key]);
        continue;
    }

    // Limit: Max 50 keys
    if ($keyCount >= 50) {
        Log::warning('Cal.com metadata limit: Dropping extra keys beyond 50');
        break;
    }

    // Limit: Key max 40 characters
    if (mb_strlen($key) > 40) {
        $key = mb_substr($key, 0, 40);
        Log::warning('Cal.com metadata limit: Truncated key to 40 chars');
    }

    // Limit: String value max 500 characters
    if (is_string($value) && mb_strlen($value) > 500) {
        $sanitizedMetadata[$key] = mb_substr($value, 0, 497) . '...';
        Log::warning('Cal.com metadata limit: Truncated value to 500 chars');
    } else {
        $sanitizedMetadata[$key] = $value;
    }

    $keyCount++;
}
```

**Deployment**:
- ✅ Code updated in `app/Services/CalcomService.php`
- ✅ OPcache cleared via file touch

---

### Issue 2: Missing Tool Parameter in Conversation Flow ⚠️ REQUIRES UPDATE

**Problem**:
Deployed flow (`deployed_flow_v2_actual.json`) is missing `call_id` in tool parameter definitions.

**Current (WRONG)**:
```json
{
  "tool_id": "tool-check-availability",
  "parameters": {
    "properties": {
      "name": {...},
      "datum": {...},
      "uhrzeit": {...},
      "dienstleistung": {...}
      // ❌ MISSING: call_id
    },
    "required": ["name", "datum", "uhrzeit", "dienstleistung"]
    // ❌ call_id not in required
  }
}
```

**Should Be (from `friseur1_final_perfect_flow.json`)**:
```json
{
  "tool_id": "tool-check-availability",
  "parameters": {
    "properties": {
      "call_id": {
        "type": "string",
        "description": "Call ID from system"
      },
      "name": {...},
      "datum": {...},
      "uhrzeit": {...},
      "dienstleistung": {...}
    },
    "required": ["call_id", "name", "datum", "uhrzeit", "dienstleistung"]
  }
}
```

**Impact**:
- `call_id` being sent as `null` in function calls
- Metadata includes `call_id: null` which may trigger validation error

---

## Testing Results

### Before Fix:
```
[2025-10-25 11:04:52] ERROR: Cal.com API request failed: POST /bookings (HTTP 400)
"metadata property is wrong, Metadata must have at most 50 keys..."
```

### After Backend Fix:
- Metadata now properly validated
- Null values skipped
- Key/value length enforced

### Still Required:
- Update Conversation Flow tool definitions to include `call_id`
- Re-deploy flow to Retell
- Test booking again

---

## Action Plan

### ✅ Completed:
1. Fixed `CalcomService.php` metadata validation
2. Added null value filtering
3. Added key count limit (50)
4. Added key length limit (40 chars)
5. Added value length limit (500 chars)
6. Cleared OPcache

### 📋 Next Steps:
1. Update Conversation Flow JSON with correct tool definitions
2. Deploy updated flow to Retell
3. Publish agent
4. Test booking flow end-to-end

---

## Files Modified

### Backend Fix:
- `app/Services/CalcomService.php` (lines 102-149)
  - Enhanced metadata sanitization
  - Added comprehensive validation
  - Added warning logs for truncation

### Flows to Update:
- `friseur1_minimal_booking_v2_fixed.json` → Add `call_id` to tool parameters
- Deploy via Retell API
- Flow ID: `conversation_flow_a58405e3f67a`
- Agent ID: `agent_45daa54928c5768b52ba3db736`

---

## Logs for Verification

### Error Logs:
```bash
grep "metadata property is wrong" /var/www/api-gateway/storage/logs/laravel-2025-10-25.log
```

### After Fix:
```bash
grep "Cal.com metadata" /var/www/api-gateway/storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## Reference Files

**Working Flow Example**: `/var/www/api-gateway/friseur1_final_perfect_flow.json`
- Contains correct tool parameter definitions
- Includes `call_id` in properties and required array

**Deployed Flow (Needs Update)**: `/var/www/api-gateway/deployed_flow_v2_actual.json`
- Missing `call_id` in tool definitions
- Otherwise structure is correct

**Comparison**: `/tmp/compare_tools.md`
- Side-by-side tool definition comparison
- Highlights differences

---

## Conclusion

**Backend Fix**: ✅ Complete and deployed
- Cal.com metadata now properly validated
- Null values filtered out
- All Cal.com limits enforced

**Flow Fix**: ⚠️ Required
- Tool definitions need `call_id` parameter
- Must update and re-deploy flow
- Test after deployment

**Next Immediate Action**: Update Conversation Flow tool definitions and re-deploy
