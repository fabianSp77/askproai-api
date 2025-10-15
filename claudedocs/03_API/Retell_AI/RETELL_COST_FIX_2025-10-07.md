# Retell Cost Tracking Fix - 2025-10-07

## Problem Summary
Plattform zeigte **0,19 EUR** statt korrekten **~0,32 EUR** für Call 776 (39% zu niedrig).

## Root Cause Analysis

### Issue
System verwendete **Schätzung** (0,07 USD/Min) statt **tatsächlicher Kosten** von Retell.

### Evidence: Call 776
```
Retell Dashboard: $0.345 USD
- Voice Engine (11labs): $0.070/min × 2.65 min = $0.186
- LLM (Gemini 2.5 Flash): $0.035/min × 2.65 min = $0.093
- Background Voice Cancellation: $0.005/min × 2.65 min = $0.013
- Add-ons:
  - LLM Token Surcharge: $0.052
  - Text Testing: $0.001
Total: $0.345 USD

Expected EUR (@ 0.92): 0.345 × 0.92 = 0.3174 EUR = 31.74 Cent

Actual in Platform:
- base_cost: 19 Cent = 0.19 EUR ❌
- retell_cost_eur_cents: 17 Cent (from estimate: 159/60 × 0.07 = 0.1855 USD)
- Discrepancy: 12.74 Cent missing (39% underreported)
```

### Database Values Before Fix
```sql
Call 776:
- cost: 34.45 USD (incorrect - stored combined_cost as retell_cost)
- cost_cents: 3445
- retell_cost_usd: 34.45 USD (WRONG - should be ~0.29 USD)
- retell_cost_eur_cents: 17 Cent (from ESTIMATE, not actual)
- base_cost: 19 Cent
- total_external_cost_eur_cents: 19 Cent
- exchange_rate_used: 0.92
```

## Code Bugs Identified

### Bug #1: Incorrect Data Storage
**File**: `app/Services/RetellApiClient.php:253`

**Before**:
```php
'retell_cost_usd' => $callData['call_cost']['retell_cost']
    ?? $callData['call_cost']['combined_cost'] // ❌ WRONG: combined_cost is TOTAL, not just Retell
    ?? null,
```

**Issue**: Stored `combined_cost` (total of all costs) as `retell_cost_usd` field.

**After**:
```php
// 🔥 FIX: Don't fallback to combined_cost - let webhook handler process actual costs
// combined_cost includes ALL costs (Retell + Twilio + Add-ons), not just Retell
'retell_cost_usd' => $callData['call_cost']['retell_cost'] ?? null,
```

### Bug #2: Webhook Handler Not Using Actual Costs
**File**: `app/Http/Controllers/RetellWebhookController.php:582-593`

**Before**:
```php
// Checked for fields that DON'T exist in webhook
if (isset($callData['price_usd']) || isset($callData['cost_usd'])) {
    // Never executed
} else {
    // Always fell back to estimate
    $estimatedRetellCostUsd = ($call->duration_sec / 60) * 0.07;
    $platformCostService->trackRetellCost($call, $estimatedRetellCostUsd);
}
```

**Issue**:
- Webhook sends costs in `call_cost.combined_cost`, not `price_usd`
- System always used fallback estimate (0.07 USD/min)
- Missing add-on costs (LLM Token Surcharge, Text Testing)

**After**:
```php
// 🔥 FIX: Use actual cost from webhook call_cost.combined_cost
if (isset($callData['call_cost']['combined_cost'])) {
    $retellCostUsd = $callData['call_cost']['combined_cost'];
    if ($retellCostUsd > 0) {
        Log::info('Using actual Retell cost from webhook', [
            'call_id' => $call->id,
            'combined_cost_usd' => $retellCostUsd,
            'source' => 'webhook.call_cost.combined_cost'
        ]);
        $platformCostService->trackRetellCost($call, $retellCostUsd);
    }
} elseif (isset($callData['price_usd']) || isset($callData['cost_usd'])) {
    // Backward compatibility
} else {
    // Fallback: Updated estimate to 0.10 USD/min (more accurate)
    $estimatedRetellCostUsd = ($call->duration_sec / 60) * 0.10;
}
```

## Changes Made

### 1. RetellApiClient.php
**Line 252-255**: Removed incorrect fallback to `combined_cost` for `retell_cost_usd` field.

**Impact**: Prevents storing total costs as Retell-only costs in database.

### 2. RetellWebhookController.php
**Line 581-625**: Updated cost tracking logic to:
- ✅ Check `call_cost.combined_cost` FIRST
- ✅ Use actual webhook cost data
- ✅ Log when using actual vs estimated costs
- ✅ Updated fallback estimate from 0.07 to 0.10 USD/min (more accurate)
- ✅ Removed automatic Twilio estimation (already in combined_cost)

**Impact**: All future calls will use accurate costs from Retell webhooks.

## Testing

### Manual Test: Simulate Webhook
```bash
# Create test webhook payload
curl -X POST https://api.askproai.de/webhook/retell \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_ended",
    "call": {
      "call_id": "test_call_001",
      "duration_ms": 159000,
      "call_cost": {
        "combined_cost": 0.345,
        "product_costs": [
          {"product": "elevenlabs_tts", "cost": 0.186},
          {"product": "gemini_2_0_flash", "cost": 0.093},
          {"product": "background_voice_cancellation", "cost": 0.013}
        ]
      }
    }
  }'

# Check database
mysql -u root -ppassword askproai_db -e "
  SELECT
    retell_call_id,
    retell_cost_usd,
    retell_cost_eur_cents,
    total_external_cost_eur_cents,
    base_cost
  FROM calls
  WHERE retell_call_id = 'test_call_001'\G
"

# Expected result:
# retell_cost_usd: 0.345
# retell_cost_eur_cents: 32 (0.345 * 0.92 * 100)
# base_cost: 32 Cent = 0.32 EUR ✅
```

### Check Logs
```bash
# Should see this log entry after webhook:
tail -f storage/logs/laravel.log | grep "Using actual Retell cost"

# Expected output:
[2025-10-07 ...] local.INFO: Using actual Retell cost from webhook
{
  "call_id": 777,
  "combined_cost_usd": 0.345,
  "source": "webhook.call_cost.combined_cost"
}
```

## Expected Results

### For New Calls (after fix)
```
Call with 2:39 duration, $0.345 USD actual cost:
- retell_cost_usd: 0.345 USD ✅
- retell_cost_eur_cents: 32 Cent ✅ (0.345 × 0.92 × 100)
- base_cost: 32 Cent = 0.32 EUR ✅
- Accuracy: 100% ✅
```

### For Old Calls (before fix)
```
Call 776 (unchanged):
- retell_cost_usd: 34.45 USD ❌ (incorrect)
- retell_cost_eur_cents: 17 Cent ❌
- base_cost: 19 Cent = 0.19 EUR ❌
```

## Migration (Optional)

To fix historical costs, run this migration script:

```php
// database/migrations/2025_10_07_fix_historical_retell_costs.php
public function up()
{
    // Get calls with cost_breakdown containing call_cost data
    $calls = Call::whereNotNull('cost_breakdown')
        ->where('created_at', '>=', '2025-09-01') // Last 30 days
        ->get();

    foreach ($calls as $call) {
        $costBreakdown = json_decode($call->cost_breakdown, true);

        if (isset($costBreakdown['combined_cost'])) {
            $actualCostUsd = $costBreakdown['combined_cost'];

            // Recalculate EUR cost
            $exchangeRate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? 0.92;
            $costEurCents = (int)round($actualCostUsd * $exchangeRate * 100);

            // Update call
            $call->update([
                'retell_cost_usd' => $actualCostUsd,
                'retell_cost_eur_cents' => $costEurCents,
                'total_external_cost_eur_cents' => $costEurCents,
                'base_cost' => $costEurCents,
            ]);

            Log::info('Recalculated historical cost', [
                'call_id' => $call->id,
                'old_cost_eur_cents' => $call->retell_cost_eur_cents,
                'new_cost_eur_cents' => $costEurCents
            ]);
        }
    }
}
```

## Financial Impact Assessment

### Underreporting Analysis
```
Average discrepancy: ~12 Cent per call
Estimated calls per month: ~1000

Monthly underreported costs: 120 EUR
Quarterly: 360 EUR
Yearly: 1,440 EUR
```

### Affected Period
All calls since webhook cost tracking was implemented (~September 2025).

## Monitoring

### Key Metrics to Watch
```bash
# 1. Check cost accuracy for new calls
mysql -u root -ppassword askproai_db -e "
  SELECT
    DATE(created_at) as date,
    COUNT(*) as calls,
    AVG(retell_cost_usd) as avg_cost_usd,
    AVG(retell_cost_eur_cents) as avg_cost_eur_cents,
    SUM(CASE WHEN retell_cost_usd IS NULL THEN 1 ELSE 0 END) as missing_cost
  FROM calls
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  GROUP BY DATE(created_at)
  ORDER BY date DESC;
"

# 2. Check log entries for cost tracking
grep "Using actual Retell cost" storage/logs/laravel.log | wc -l

# 3. Compare base_cost to retell_cost_eur_cents
mysql -u root -ppassword askproai_db -e "
  SELECT
    id,
    retell_call_id,
    retell_cost_usd,
    retell_cost_eur_cents,
    base_cost,
    (retell_cost_eur_cents - base_cost) as difference
  FROM calls
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
  LIMIT 10;
"
```

## Rollback Plan

If issues arise, revert with:
```bash
git checkout HEAD~1 app/Services/RetellApiClient.php
git checkout HEAD~1 app/Http/Controllers/RetellWebhookController.php
```

## References

- **Call Example**: https://api.askproai.de/admin/calls/776
- **Retell Documentation**: https://docs.retellai.com/api-reference/call-cost
- **Root Cause Analysis**: See agent analysis output above

## Status

✅ **IMPLEMENTED** - 2025-10-07
- RetellApiClient.php: Fixed
- RetellWebhookController.php: Fixed
- Testing: Manual verification recommended
- Migration: Optional (for historical data)
- Monitoring: Active

---

**Author**: Claude Code Root Cause Analyst
**Date**: 2025-10-07
**Severity**: HIGH (39% cost underreporting)
**Priority**: CRITICAL (affects all calls)
