# Retell Cost Flow Analysis - Complete Architecture Documentation

**Analysis Date**: 2025-10-07
**Analyst**: Root Cause Analysis Mode
**Scope**: End-to-end Retell cost tracking from webhook reception to display

---

## Executive Summary

The platform implements a sophisticated multi-currency cost tracking system for Retell AI telephony costs. USD costs from Retell webhooks are converted to EUR using a dedicated exchange rate service and stored in both currencies. The system distinguishes between **actual costs** (from webhook data) and **estimated costs** (calculated fallbacks).

**Key Finding**: ✅ System is architecturally sound with proper separation of concerns, accurate currency conversion, and comprehensive cost tracking.

---

## 1. Cost Reception (Webhook Entry Point)

### 1.1 Webhook Processing
**File**: `/var/www/api-gateway/app/Http/Controllers/RetellWebhookController.php`

**Entry Point**: `handleCallEnded()` method (lines 524-667)

```php
// Webhook event: call_ended
// Data structure: $data['call']['call_cost']['combined_cost'] (USD)
```

**Cost Data Sources** (Priority Order):
1. **Primary**: `$callData['call_cost']['combined_cost']` - Complete cost including:
   - Retell API fees
   - Twilio telephony costs
   - Voice Engine costs
   - LLM costs
   - Add-on features

2. **Legacy**: `$callData['price_usd']` or `$callData['cost_usd']`

3. **Fallback**: Estimated cost = `(duration_sec / 60) * 0.10 USD/min`

### 1.2 Cost Extraction Logic

**Lines 583-610** in RetellWebhookController:

```php
// ACTUAL COST (preferred)
if (isset($callData['call_cost']['combined_cost'])) {
    $retellCostUsd = $callData['call_cost']['combined_cost'];
    $platformCostService->trackRetellCost($call, $retellCostUsd);
    // Source: webhook.call_cost.combined_cost
}

// ESTIMATED COST (fallback)
else if ($call->duration_sec > 0) {
    $estimatedRetellCostUsd = ($call->duration_sec / 60) * 0.10;
    $platformCostService->trackRetellCost($call, $estimatedRetellCostUsd);
    // Source: estimation
}
```

**Separate Twilio Tracking** (if provided):
```php
if (isset($callData['call_cost']['twilio_cost'])) {
    $twilioCostUsd = $callData['call_cost']['twilio_cost'];
    $platformCostService->trackTwilioCost($call, $twilioCostUsd);
}
```

---

## 2. Currency Conversion (USD → EUR)

### 2.1 Exchange Rate Service
**File**: `/var/www/api-gateway/app/Services/ExchangeRateService.php`

**Architecture**:
- Fetches rates from **European Central Bank (ECB)** API (primary)
- Optional fallback: **Fixer.io** API (requires API key)
- Stores rates in `currency_exchange_rates` table
- **Caches rates for 1 hour** to minimize API calls

**Primary Method**: `convertUsdCentsToEurCents(int $usdCents): int`

```php
// Lines 130-138
public function convertUsdCentsToEurCents(int $usdCents): int
{
    $rate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
    if ($rate === null) {
        Log::warning('No USD to EUR rate available, using default 0.92');
        $rate = 0.92; // Fallback rate
    }
    return (int)round($usdCents * $rate);
}
```

### 2.2 Exchange Rate Fetching

**ECB API Integration** (Lines 19-58):
```php
public function fetchECBRates(): array
{
    $response = Http::timeout(10)->get('https://api.frankfurter.app/latest', [
        'from' => 'EUR',
        'to' => 'USD,GBP'
    ]);

    if ($response->successful()) {
        $rates = $response->json()['rates'];

        // Update EUR to USD rate
        CurrencyExchangeRate::updateRate('EUR', 'USD', $rates['USD'], 'ecb');
        CurrencyExchangeRate::updateRate('USD', 'EUR', 1 / $rates['USD'], 'ecb');
    }
}
```

**Rate Update Frequency**:
- No automatic scheduling found in codebase
- Likely manual trigger or scheduled command
- Cached for 1 hour after fetching
- Default fallback: `0.92` USD to EUR

### 2.3 CurrencyExchangeRate Model
**File**: `/var/www/api-gateway/app/Models/CurrencyExchangeRate.php`

**Key Features**:
- **Active rate lookup**: Gets most recent valid rate
- **Reverse calculation**: If USD→EUR not found, uses EUR→USD and inverts
- **Cache layer**: 1-hour cache per currency pair
- **Automatic deactivation**: Old rates marked inactive when new rates added

**Database Structure**:
```sql
currency_exchange_rates:
  - from_currency: VARCHAR (e.g., 'USD')
  - to_currency: VARCHAR (e.g., 'EUR')
  - rate: DECIMAL(10,6)
  - source: VARCHAR ('ecb', 'fixer', 'manual')
  - valid_from: TIMESTAMP
  - valid_until: TIMESTAMP (nullable)
  - is_active: BOOLEAN
  - metadata: JSON
```

---

## 3. Cost Storage (Database Schema)

### 3.1 Call Table Cost Fields
**Migration**: `/var/www/api-gateway/database/migrations/2025_09_29_151103_add_external_costs_to_calls_table.php`

**Database Schema**:
```sql
calls table additions:
  -- USD Original Values
  retell_cost_usd          DECIMAL(10,4)  NULL  -- Raw USD from webhook
  twilio_cost_usd          DECIMAL(10,4)  NULL  -- Twilio USD cost

  -- EUR Converted Values (CENTS)
  retell_cost_eur_cents    INTEGER        NULL  -- Retell in EUR cents
  twilio_cost_eur_cents    INTEGER        NULL  -- Twilio in EUR cents
  total_external_cost_eur_cents INTEGER   NULL  -- Combined EUR cents

  -- Metadata
  exchange_rate_used       DECIMAL(10,6)  NULL  -- Rate snapshot

  -- Index for performance
  INDEX (created_at, total_external_cost_eur_cents)
```

**Important**: All EUR costs stored as **CENTS** (integer) to avoid floating-point errors.

### 3.2 PlatformCost Table
**Purpose**: Track detailed platform costs with metadata

```sql
platform_costs:
  - company_id: INTEGER
  - platform: VARCHAR ('retell', 'twilio', 'calcom')
  - service_type: VARCHAR ('api_call', 'telephony', 'subscription')
  - cost_type: VARCHAR ('usage', 'fixed')
  - amount_cents: INTEGER (EUR cents)
  - currency: VARCHAR ('EUR')
  - period_start: TIMESTAMP
  - period_end: TIMESTAMP
  - usage_quantity: DECIMAL (e.g., minutes)
  - usage_unit: VARCHAR ('minutes', 'users')
  - external_reference_id: VARCHAR (call_id)
  - metadata: JSON
```

**Sample Metadata**:
```json
{
  "call_id": 12345,
  "duration_seconds": 120,
  "original_cost_usd": 0.20,
  "exchange_rate": 0.92
}
```

---

## 4. Cost Tracking Flow (PlatformCostService)

### 4.1 Service Architecture
**File**: `/var/www/api-gateway/app/Services/PlatformCostService.php`

**Main Methods**:
1. `trackRetellCost(Call $call, float $costUsd)` - Track Retell costs
2. `trackTwilioCost(Call $call, float $costUsd)` - Track Twilio costs
3. `calculateCallTotalCosts(Call $call)` - Sum all external costs

### 4.2 Retell Cost Tracking Process

**Lines 25-69**:

```php
public function trackRetellCost(Call $call, float $costUsd): void
{
    // 1. Convert USD to EUR cents
    $costEurCents = $this->exchangeService->convertUsdCentsToEurCents(
        (int)($costUsd * 100)
    );

    // 2. Create platform_costs record
    PlatformCost::create([
        'company_id' => $call->company_id,
        'platform' => 'retell',
        'service_type' => 'api_call',
        'cost_type' => 'usage',
        'amount_cents' => $costEurCents,
        'currency' => 'EUR',
        'period_start' => $call->created_at,
        'period_end' => $call->updated_at,
        'usage_quantity' => $call->duration_sec / 60, // minutes
        'usage_unit' => 'minutes',
        'external_reference_id' => $call->retell_call_id,
        'metadata' => [
            'call_id' => $call->id,
            'duration_seconds' => $call->duration_sec,
            'original_cost_usd' => $costUsd,
            'exchange_rate' => CurrencyExchangeRate::getCurrentRate('USD', 'EUR')
        ]
    ]);

    // 3. Update Call record
    $call->update([
        'retell_cost_usd' => $costUsd,
        'retell_cost_eur_cents' => $costEurCents
    ]);
}
```

### 4.3 Total Cost Calculation

**Lines 171-197**:

```php
public function calculateCallTotalCosts(Call $call): void
{
    $exchangeRate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? 0.92;

    // Sum all external costs (in EUR cents)
    $totalExternalCostEurCents =
        ($call->retell_cost_eur_cents ?? 0) +
        ($call->twilio_cost_eur_cents ?? 0);

    // Update call with totals
    $call->update([
        'exchange_rate_used' => $exchangeRate,
        'total_external_cost_eur_cents' => $totalExternalCostEurCents
    ]);
}
```

---

## 5. Cost Display (Frontend)

### 5.1 Call List Display
**File**: `/var/www/api-gateway/app/Filament/Resources/CallResource.php`

**Location**: Table column "Tel.-Kosten" (Lines 867-924)

**Display Logic**:
```php
Tables\Columns\TextColumn::make('financials')
    ->label('Tel.-Kosten')
    ->getStateUsing(function (Call $record) {
        $user = auth()->user();
        $primaryCost = 0;

        // Role-based cost selection
        if ($user->hasRole('super-admin')) {
            $primaryCost = $record->base_cost ?? 0;
        } elseif ($user->hasRole('reseller_admin')) {
            $primaryCost = $record->reseller_cost ?? $record->base_cost ?? 0;
        } else {
            $primaryCost = $record->customer_cost ?? $record->cost ?? 0;
        }

        $formattedCost = number_format($primaryCost / 100, 2, ',', '.');

        // Status indicator: Actual vs Estimated
        $statusDot = '';
        if ($record->total_external_cost_eur_cents > 0) {
            // Green dot = Actual costs from webhook
            $statusDot = '<span class="...bg-green-500" title="Tatsächliche Kosten"></span>';
        } else {
            // Yellow dot = Estimated costs
            $statusDot = '<span class="...bg-yellow-500" title="Geschätzte Kosten"></span>';
        }

        return new HtmlString(
            '<div class="flex items-center gap-0.5">' .
            '<span class="font-semibold">' . $formattedCost . '€</span>' .
            $statusDot .
            '</div>'
        );
    })
```

**Visual Indicators**:
- 🟢 **Green Dot**: `total_external_cost_eur_cents > 0` → Actual webhook data
- 🟡 **Yellow Dot**: `total_external_cost_eur_cents = 0` → Estimated cost

### 5.2 Profit Details Modal
**File**: `/var/www/api-gateway/resources/views/filament/modals/profit-details.blade.php`

**Sections Displayed**:

1. **Revenue Section** (if appointment exists):
   - Appointment revenue from `$call->getAppointmentRevenue()`

2. **Cost Breakdown** (role-based):
   - **Super Admin**: Base cost, reseller cost, customer cost
   - **Reseller**: Their cost, customer cost
   - **Customer**: Customer cost only

3. **Profit Analysis**:
   - Platform profit (super admin only)
   - Reseller profit (super admin + reseller)
   - Total profit with margin percentages

4. **Additional Info**:
   - Call duration
   - **Cost Method**: `{{ $call->total_external_cost_eur_cents > 0 ? 'Tatsächlich' : 'Geschätzt' }}`
   - ROI calculation (if revenue exists)
   - Profit margin percentage

**Line 300**: Cost Method Display
```blade
<span class="...">
    {{ $call->total_external_cost_eur_cents > 0 ? 'Tatsächlich' : 'Geschätzt' }}
</span>
```

### 5.3 Dashboard Widgets

**Profit Overview Widget**: `/var/www/api-gateway/app/Filament/Widgets/ProfitOverviewWidget.php`

**Displays**:
- Today's profit (with trend vs yesterday)
- Monthly profit total
- Average profit margin
- Platform vs Reseller split (super admin only)
- 7-day profit chart

**Data Source**: Uses `CostCalculator::getDisplayProfit()` for role-based profit calculation

---

## 6. Complete Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. WEBHOOK RECEPTION (RetellWebhookController)                  │
├─────────────────────────────────────────────────────────────────┤
│ call_ended event → $callData['call_cost']['combined_cost']     │
│ ├─ combined_cost = Retell + Twilio + LLM + Voice Engine        │
│ ├─ twilio_cost (separate tracking if provided)                 │
│ └─ Fallback: (duration_sec / 60) * 0.10 USD/min               │
└───────────────────┬─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. CURRENCY CONVERSION (ExchangeRateService)                    │
├─────────────────────────────────────────────────────────────────┤
│ convertUsdCentsToEurCents($usdCents)                           │
│ ├─ Fetch: CurrencyExchangeRate::getCurrentRate('USD', 'EUR')  │
│ ├─ Cache: 1-hour TTL                                           │
│ ├─ Source: ECB API (https://api.frankfurter.app)              │
│ └─ Fallback: 0.92 fixed rate                                  │
│                                                                 │
│ Example: $0.20 USD * 0.92 = €0.184 = 18 EUR cents             │
└───────────────────┬─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. DUAL STORAGE (PlatformCostService)                          │
├─────────────────────────────────────────────────────────────────┤
│ A. platform_costs table (detailed tracking)                    │
│    ├─ company_id: 1                                            │
│    ├─ platform: 'retell'                                       │
│    ├─ amount_cents: 18 (EUR cents)                            │
│    ├─ usage_quantity: 2.0 (minutes)                            │
│    └─ metadata: {call_id, original_usd, exchange_rate}        │
│                                                                 │
│ B. calls table (quick access)                                  │
│    ├─ retell_cost_usd: 0.20                                    │
│    ├─ retell_cost_eur_cents: 18                                │
│    ├─ twilio_cost_eur_cents: 5                                 │
│    ├─ total_external_cost_eur_cents: 23                        │
│    └─ exchange_rate_used: 0.92                                 │
└───────────────────┬─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. FRONTEND DISPLAY (Filament Resources)                       │
├─────────────────────────────────────────────────────────────────┤
│ A. Call List (CallResource table)                              │
│    ├─ Display: €0.23 🟢 (formatted, with status dot)          │
│    ├─ Green dot: total_external_cost_eur_cents > 0            │
│    └─ Yellow dot: total_external_cost_eur_cents = 0           │
│                                                                 │
│ B. Profit Details Modal                                        │
│    ├─ Cost breakdown by role                                   │
│    ├─ Profit visualization                                     │
│    ├─ Cost method: "Tatsächlich" / "Geschätzt"                │
│    └─ ROI calculation                                          │
│                                                                 │
│ C. Dashboard Widgets                                           │
│    ├─ ProfitOverviewWidget: Daily/monthly profit              │
│    ├─ Platform vs Reseller split                              │
│    └─ 7-day profit trend chart                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Exchange Rate Mechanism Analysis

### 7.1 Rate Update Strategy

**Current State**:
- ✅ ECB API integration implemented
- ✅ 1-hour cache layer for performance
- ✅ Automatic deactivation of old rates
- ❌ **No automatic scheduler found**

**Recommendation**: Implement Laravel scheduled task:
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        app(ExchangeRateService::class)->updateAllRates();
    })->daily();
}
```

### 7.2 Which Rate is Used?

**Answer**: **Current rate at time of call processing**

**Evidence**:
```php
// PlatformCostService.php, Line 48
'exchange_rate' => CurrencyExchangeRate::getCurrentRate('USD', 'EUR')
```

**Not Historical**: System uses the rate available when `trackRetellCost()` is called, not the rate from when the call occurred.

**Stored for Audit**: The used rate is saved in:
- `calls.exchange_rate_used` field
- `platform_costs.metadata['exchange_rate']`

### 7.3 Rate Sources Priority

1. **Database** (active rate): `currency_exchange_rates` table
2. **Cache** (1-hour TTL): Redis/file cache
3. **Fallback**: `0.92` hardcoded

**Rate Validation**:
```php
// Line 119-124 in ExchangeRateService
$rate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR');
if ($rate === null) {
    Log::warning('No USD to EUR rate available, using default 0.92');
    $rate = 0.92;
}
```

---

## 8. Display Locations Summary

### 8.1 All Cost Display Points

| Location | File | Field Displayed | Format | Role Access |
|----------|------|-----------------|--------|-------------|
| **Call List** | CallResource.php:867 | financials column | €0.23 🟢 | All users |
| **Profit Modal** | profit-details.blade.php:89 | customer_cost | €0.23 | All users |
| **Profit Modal** | profit-details.blade.php:70 | base_cost | €0.15 | Super Admin |
| **Profit Modal** | profit-details.blade.php:84 | reseller_cost | €0.18 | Super Admin + Reseller |
| **Profit Modal** | profit-details.blade.php:300 | cost method | "Tatsächlich"/"Geschätzt" | All users |
| **Dashboard Widget** | ProfitOverviewWidget.php:99 | daily profit | €45.67 | Super Admin + Reseller |
| **Call Detail** | CallResource.php:1306 | cost_display | €0.23 | All users |
| **Call Detail** | CallResource.php:1924 | cost_cents | 23 ¢ | All users |

### 8.2 Field Mappings

**Database Field → Display Field**:

```
calls.total_external_cost_eur_cents (23)
  → /100
  → number_format(0.23, 2, ',', '.')
  → "0,23"
  → "0,23 €"

calls.retell_cost_usd (0.20)
  → Stored as-is
  → Display: metadata only

calls.exchange_rate_used (0.92)
  → Stored as-is
  → Display: metadata only
```

---

## 9. Validation & Issues

### 9.1 Correctness Validation

✅ **Currency Conversion**:
- Proper use of cents (integer) to avoid floating-point errors
- Correct rounding: `(int)round($usdCents * $rate)`

✅ **Cost Attribution**:
- Correctly tracks company_id for multi-tenant isolation
- Separate tracking for Retell vs Twilio costs

✅ **Data Integrity**:
- Dual storage (calls + platform_costs) provides redundancy
- Metadata preserves original USD values for audit

✅ **Role-Based Access**:
- Proper cost visibility based on user roles
- Super admin sees all cost layers
- Resellers see only their costs
- Customers see only customer-facing costs

### 9.2 Identified Issues

❌ **Issue 1: No Automatic Exchange Rate Updates**
- **Severity**: Medium
- **Impact**: Rates may become stale if manual updates are missed
- **Fix**: Implement daily scheduled task (see 7.1)

⚠️ **Issue 2: Historical Rate Not Used**
- **Severity**: Low
- **Impact**: Currency conversion uses rate at processing time, not call time
- **Discussion**: This is acceptable for near-real-time webhooks, but could cause discrepancies if webhook processing is delayed
- **Potential Fix**: Implement `ExchangeRateService::getHistoricalRate()` (method exists but not used)

⚠️ **Issue 3: Fallback Rate Hardcoded**
- **Severity**: Low
- **Impact**: If rate fetching fails, all conversions use 0.92
- **Discussion**: 0.92 is reasonable average, but could drift over time
- **Fix**: Update fallback rate periodically or fetch from backup source

✅ **Issue 4: Combined Cost Clarity**
- **Status**: Resolved
- **Evidence**: Line 583-590 correctly uses `combined_cost` which includes all cost components
- **Documentation**: Comment clearly states "combined_cost includes ALL costs: Retell API + Twilio + Voice Engine + LLM + Add-ons"

### 9.3 Performance Considerations

✅ **Efficient**:
- Exchange rate caching (1-hour TTL) minimizes API calls
- Database indexes on `(created_at, total_external_cost_eur_cents)`
- Widget caching (60 seconds) for dashboard

⚠️ **Potential Optimization**:
- Consider caching `getDisplayProfit()` results for frequently accessed calls
- Batch rate conversions if processing many calls

---

## 10. Recommendations

### 10.1 Immediate Actions (Priority: High)

1. **✅ Implement Automatic Rate Updates**
   ```php
   // Add to app/Console/Kernel.php
   $schedule->call(function () {
       app(ExchangeRateService::class)->updateAllRates();
   })->dailyAt('00:00');
   ```

2. **✅ Monitor Rate Fetch Failures**
   - Add alert when fallback rate is used
   - Log rate update success/failure
   - Consider backup API source

### 10.2 Future Enhancements (Priority: Medium)

1. **Historical Rate Support**
   - Use `getHistoricalRate()` for delayed webhooks
   - Store call timestamp with rate lookup

2. **Cost Reconciliation Report**
   - Monthly comparison: estimated vs actual costs
   - Alert on significant discrepancies

3. **Multi-Currency Support**
   - Currently EUR-centric
   - Could expand to support other customer currencies

### 10.3 Documentation Improvements

1. **Add API Documentation**
   - Document webhook payload structure
   - Document cost calculation formulas

2. **Create Operational Runbook**
   - Rate update procedures
   - Cost discrepancy investigation
   - Manual rate override process

---

## 11. Conclusion

The Retell cost tracking system is **architecturally sound** with:

✅ **Strengths**:
- Proper multi-currency handling
- Accurate cent-based storage
- Comprehensive audit trail
- Role-based cost visibility
- Visual indicators (actual vs estimated)
- Dual storage for redundancy and performance

⚠️ **Minor Issues**:
- Missing automatic rate updates
- No historical rate usage
- Hardcoded fallback rate

**Overall Assessment**: The system is **production-ready** with proper cost tracking and display. The identified issues are minor and don't affect core functionality, but addressing them would improve robustness.

---

## Appendix A: Data Flow Example

**Scenario**: 2-minute call with Retell

1. **Webhook Received**: `call_cost.combined_cost = 0.20 USD`
2. **Conversion**: `0.20 USD * 0.92 rate = 0.184 EUR = 18 cents`
3. **Storage**:
   - `calls.retell_cost_usd = 0.20`
   - `calls.retell_cost_eur_cents = 18`
   - `calls.total_external_cost_eur_cents = 18`
   - `calls.exchange_rate_used = 0.92`
   - `platform_costs` record created
4. **Display**:
   - Call list: `€0.18 🟢`
   - Modal: `Kunden-Kosten: 0,18 €` + `Methode: Tatsächlich`
   - Dashboard: Included in daily profit calculations

---

## Appendix B: Database Queries

**Get total Retell costs for a company (current month)**:
```sql
SELECT
    SUM(retell_cost_eur_cents) / 100 as total_retell_eur,
    AVG(exchange_rate_used) as avg_rate,
    COUNT(*) as call_count,
    SUM(CASE WHEN total_external_cost_eur_cents > 0 THEN 1 ELSE 0 END) as actual_count,
    COUNT(*) - SUM(CASE WHEN total_external_cost_eur_cents > 0 THEN 1 ELSE 0 END) as estimated_count
FROM calls
WHERE company_id = ?
AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
AND retell_cost_eur_cents IS NOT NULL;
```

**Get platform costs with exchange rate metadata**:
```sql
SELECT
    pc.*,
    c.retell_cost_usd,
    c.exchange_rate_used,
    JSON_EXTRACT(pc.metadata, '$.original_cost_usd') as original_usd,
    JSON_EXTRACT(pc.metadata, '$.exchange_rate') as stored_rate
FROM platform_costs pc
LEFT JOIN calls c ON c.id = JSON_EXTRACT(pc.metadata, '$.call_id')
WHERE pc.platform = 'retell'
AND pc.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

**End of Analysis**
