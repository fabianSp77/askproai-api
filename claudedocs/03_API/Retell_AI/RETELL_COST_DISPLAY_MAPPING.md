# Retell Cost Display Mapping - Complete Inventory

**Generated**: 2025-10-07
**Purpose**: Comprehensive mapping of all Retell cost displays across the platform
**Status**: Analysis Complete - No Changes Made

---

## Executive Summary

### Cost Data Sources (Database Schema)

The platform tracks costs across multiple database columns in the `calls` table:

#### External/Raw Cost Fields (from Retell API)
- `retell_cost_usd` (decimal 10,4) - Retell.ai cost in USD
- `retell_cost_eur_cents` (integer) - Retell cost converted to EUR cents
- `twilio_cost_usd` (decimal 10,4) - Twilio telephony cost in USD
- `twilio_cost_eur_cents` (integer) - Twilio cost converted to EUR cents
- `exchange_rate_used` (decimal 10,6) - USD to EUR conversion rate
- `total_external_cost_eur_cents` (integer) - Total external costs in EUR cents

#### Calculated Cost Fields (via CostCalculator service)
- `cost` (decimal 2) - Legacy cost field in euros
- `cost_cents` (integer) - Cost in cents
- `base_cost` (integer) - Platform base cost (in cents)
- `reseller_cost` (integer) - Reseller cost (in cents)
- `customer_cost` (integer) - Customer-facing cost (in cents)
- `retell_cost` (decimal 2) - Legacy Retell cost field

#### Profit Fields (calculated)
- `platform_profit` (integer) - Platform profit in cents
- `reseller_profit` (integer) - Reseller profit in cents
- `total_profit` (integer) - Total profit in cents
- `profit_margin_platform` (decimal 2) - Platform profit margin %
- `profit_margin_reseller` (decimal 2) - Reseller profit margin %
- `profit_margin_total` (decimal 2) - Total profit margin %

#### Metadata
- `cost_calculation_method` (string) - 'standard', 'reseller', or 'direct'
- `cost_breakdown` (json) - Detailed cost component breakdown
- `llm_token_usage` (json) - LLM token usage for cost calculation

---

## 1. Admin Panel - Call Resource

### Location: `/var/www/api-gateway/app/Filament/Resources/CallResource.php`

### 1.1 List View (Table Columns)

#### Column: "Tel.-Kosten" (`financials`)
- **Lines**: 867-924
- **Display Logic**:
  - **Super Admin**: Shows `base_cost`
  - **Reseller**: Shows `reseller_cost` (fallback to `base_cost`)
  - **Customer**: Shows `customer_cost` (fallback to `cost * 100`)
- **Format**: `X.XX€` (2 decimal places, comma separator)
- **Visual Indicators**:
  - Green dot: Actual costs (`total_external_cost_eur_cents > 0`)
  - Yellow dot: Estimated costs (no external costs tracked)
- **Modal Action**: Clicking opens "Finanzielle Details" modal
- **Sortable**: Yes (sorts by `customer_cost`)
- **Toggleable**: Yes (visible by default)
- **Font**: Monospace (`font-mono`)

#### Column: "Einnahmen/Gewinn" (`revenue_profit`)
- **Lines**: 927-962
- **Visibility**: Super Admin & Reseller only
- **Display Logic**:
  - Revenue: `getAppointmentRevenue()` - sum of appointment prices * 100
  - Profit: `getCallProfit()` - revenue minus base_cost
- **Format**:
  - Revenue: `X.XX€` (primary, bold)
  - Profit: `±X.XX€` (secondary, small text, colored)
- **Colors**:
  - Green: Positive profit
  - Red: Negative profit
  - Gray: No revenue (shows "-")
- **Toggleable**: Yes (visible by default)

#### Column: "Service-Preis" (`service_price`)
- **Lines**: 796-864
- **Display**: Appointment service price
- **Format**: `X.XX €` or `X,XX €/Min`
- **Tooltip**: Shows detailed service breakdown
- **Sortable**: Yes (by service price)
- **Toggleable**: Yes

### 1.2 Detail View (Infolist)

#### KPI Card: "Kosten" (`cost_display`)
- **Lines**: 1306-1318
- **Location**: Overview tab, KPI section
- **Display**: Uses `cost` field divided by 100
- **Format**: EUR with 2 decimals
- **Visual**: KPI card with icon

#### Tab: "Kosten & Profit" (`profit_display_view`)
- **Lines**: 1799-1829
- **Visibility**: Super Admin & Reseller only
- **Display**: Full profit breakdown view
- **Component**: Uses `filament.profit-display` blade template
- **Data Source**: CostCalculator service

#### Technical Details Section
- **Line**: 1924-1939
- **Field**: `cost_cents`
- **Format**: "X ¢" (cent display)
- **Badge**: Gray color
- **Visibility**: Always visible in tech details tab

### 1.3 Form (Edit View)

#### Cost Input Field
- **Lines**: 176-180
- **Label**: "Kosten (€)"
- **Type**: TextInput (disabled)
- **Prefix**: "€"
- **Format**: `number_format($state / 100, 2, ',', '.')`
- **Section**: "Technische Informationen" (collapsed)

### 1.4 CSV Export

**Location**: `/var/www/api-gateway/app/Filament/Resources/CallResource/Pages/ListCalls.php`

#### Export Field: "Kosten (€)"
- **Line**: 109
- **Field Used**: `cost` (NOT `cost_cents`)
- **Calculation**: `($call->cost ?? 0) / 100`
- **Format**: `number_format(X, 2, ',', '.')`
- **CSV Output**: German format (comma decimal, dot thousands)

**⚠️ INCONSISTENCY FOUND**: CSV export uses `cost` field while table display uses role-based cost fields (`base_cost`, `reseller_cost`, `customer_cost`).

---

## 2. Dashboards & Widgets

### 2.1 CallStatsOverview Widget

**Location**: `/var/www/api-gateway/app/Filament/Resources/CallResource/Widgets/CallStatsOverview.php`

#### Stat: "Kosten Monat"
- **Lines**: 107, 163-167
- **Visibility**: Super Admin only
- **Field**: `cost_cents`
- **Aggregation**: `SUM(COALESCE(cost_cents, 0)) / 100.0`
- **Format**: `€X.XX` (2 decimals)
- **Additional Info**: Shows platform profit in description
- **Chart**: Monthly cost trend by week

#### Stat: "Profit Marge"
- **Lines**: 110, 169-172
- **Visibility**: Super Admin only
- **Field**: `avg_profit_margin` (calculated from `profit_margin_total`)
- **Format**: `X.X%` (1 decimal)
- **Description**: Shows total profit in EUR
- **Color Coding**:
  - Green: >50%
  - Yellow: 30-50%
  - Red: <30%

#### Stat: "⌀ Kosten/Anruf"
- **Lines**: 122, 176-179
- **Visibility**: All authorized users
- **Calculation**: `monthCost / monthCount`
- **Format**: `€X.XX` (2 decimals)
- **Color Coding**:
  - Green: <€3
  - Yellow: €3-5
  - Red: >€5

#### Performance Notes
- **Caching**: 60 seconds (5-minute key granularity)
- **Query Optimization**: Single aggregated query per time period
- **Role Filtering**: Applied at query level

### 2.2 ProfitOverviewWidget

**Location**: `/var/www/api-gateway/app/Filament/Widgets/ProfitOverviewWidget.php`

#### Stats Displayed
1. **Profit Heute**:
   - Line: 99
   - Uses `CostCalculator::getDisplayProfit()`
   - Format: `X,XX €`
   - Shows trend vs yesterday

2. **Profit Monat**:
   - Line: 109
   - Aggregates all calls in month
   - Format: `X,XX €`

3. **⌀ Marge Heute**:
   - Line: 115
   - Calculates average `profit_margin_total`
   - Format: `X%`

4. **Platform vs. Mandant** (Super Admin only):
   - Line: 122-124
   - Shows both platform and reseller profit split
   - Format: `X,XX € / X,XX €`

#### Data Source
- **Service**: `CostCalculator::getDisplayProfit()`
- **Caching**: 60 seconds
- **Chart**: Last 7 days profit trend

### 2.3 ProfitChartWidget

**Location**: `/var/www/api-gateway/app/Filament/Widgets/ProfitChartWidget.php`

#### Chart Data
- **Lines**: 27-113
- **Type**: Line chart
- **Period**: Last 30 days
- **Datasets**:
  1. Gesamt-Profit (green)
  2. Platform-Profit (blue, Super Admin only)
  3. Mandanten-Profit (purple, Super Admin only)
- **Format**: EUR with 2 decimals
- **Tooltip**: German currency formatting
- **Caching**: 300 seconds (5 minutes)

---

## 3. Profit Dashboard Page

**Location**: `/var/www/api-gateway/app/Filament/Pages/ProfitDashboard.php`

### Access Control
- **Super Admin**: Full access to all profit data
- **Reseller**: Access to own profit data only
- **Customer**: No access (redirected)

### Data Displayed

#### Today's Stats (Line 82-144)
- `todayProfit`: Total profit in cents
- `todayPlatformProfit`: Platform profit (Super Admin only)
- `todayResellerProfit`: Reseller profit (Super Admin only)
- `todayCallCount`: Number of calls

#### Monthly Stats
- `monthProfit`: Monthly total profit
- `avgMargin`: Average profit margin

#### Chart Data (Line 147-194)
- 30-day profit trend
- Separate lines for platform/reseller (Super Admin only)

#### Top Performers (Line 196-234)
- Companies ranked by profit
- Super Admin: All companies by `total_profit`
- Reseller: Customer companies by `reseller_profit`

#### Profit Alerts (Line 273-325)
- Low margin calls (<20%)
- Negative profit calls
- High performers (>50% margin)

### Caching
- **Duration**: 300 seconds (5 minutes)
- **Key**: Role-based (`profit-dashboard-super-{id}` or `profit-dashboard-reseller-{id}`)

---

## 4. Blade Templates (Views)

### 4.1 Profit Details Modal

**Location**: `/var/www/api-gateway/resources/views/filament/modals/profit-details.blade.php`

#### Display Components

**Revenue Section** (Line 40-55)
- Shows: `getAppointmentRevenue()`
- Format: `X,XX €`
- Displayed only if revenue > 0

**Cost Breakdown** (Line 58-93)
- **Base Cost** (Super Admin only):
  - Field: `base_cost`
  - Format: `X,XX €`
- **Reseller Cost** (Super Admin & Reseller):
  - Field: `reseller_cost`
  - Format: `X,XX €`
- **Customer Cost** (All roles):
  - Field: `customer_cost`
  - Format: `X,XX €` (bold)

**Profit Breakdown** (Line 95-167)
- **Platform Profit** (Super Admin only):
  - Field: `platform_profit`
  - Format: `+X,XX €`
  - Shows margin percentage
- **Reseller Profit**:
  - Field: `reseller_profit`
  - Format: `+X,XX €`
  - Shows margin percentage
- **Total Profit** (Super Admin only):
  - Field: `total_profit`
  - Format: `+X,XX €`
  - Shows margin percentage

**Visual Profit Bar** (Line 169-284)
- **Super Admin**: Full distribution (base cost + platform profit + reseller profit)
- **Reseller**: Simplified (your cost + your profit)
- Percentage-based width calculation

**Additional Info** (Line 287-319)
- Duration display
- Cost method badge (Actual vs Estimated)
- ROI calculation (if revenue exists)
- Profit margin (Super Admin only)

### 4.2 Profit Display Component

**Location**: `/var/www/api-gateway/resources/views/filament/profit-display.blade.php`

#### Grid Layout (Line 3-59)
- Base Cost card (Super Admin only)
- Reseller/Customer Cost card
- Customer Price card (if reseller chain)

#### Profit Overview (Line 62-131)
- Main profit amount with color coding
- Profit margin bar with color indicators
- Profit breakdown (Super Admin only)
- Performance indicators (ROI, Efficiency, Category)

---

## 5. API Endpoints

### 5.1 Webhook Endpoints

**Location**: `/var/www/api-gateway/routes/api.php`

#### Retell Webhook
- **Route**: `/webhooks/retell` (POST)
- **Controller**: `RetellWebhookController`
- **Purpose**: Receives call completed events from Retell
- **Cost Data**: Processes `retell_cost_usd`, `twilio_cost_usd` from webhook payload
- **Security**: Retell signature verification middleware

#### Retell Function Calls
- **Routes**: Multiple endpoints for real-time functions
- **No direct cost display**: These are operational endpoints

### 5.2 Internal APIs

**No public API endpoints expose cost data directly**. All cost displays go through:
1. Admin panel (Filament)
2. Dashboard widgets
3. Blade templates

---

## 6. Display Consistency Analysis

### 6.1 Format Standards

#### Currency Display
| Location | Format | Decimals | Separator | Symbol |
|----------|--------|----------|-----------|--------|
| Table columns | `X.XX€` | 2 | `.` (dot) | € (suffix) |
| CSV export | `X,XX` | 2 | `,` (comma) | None in field |
| Modals | `X,XX €` | 2 | `,` (comma) | € (suffix with space) |
| Widgets | `€X.XX` | 2 | `.` (dot) | € (prefix) |
| Charts | `X.XX €` | 2 | `.` (dot) | € (suffix with space) |

**⚠️ INCONSISTENCY**: Mixed decimal separator usage (dot vs comma) and currency symbol placement (prefix vs suffix).

#### Cent Display
- **Admin table**: Always converts cents to euros (`/ 100`)
- **Technical details**: Shows raw cents with "¢" symbol
- **Database storage**: Always in cents (integer)

### 6.2 Field Naming Consistency

| Display Name | Database Field | Notes |
|--------------|----------------|-------|
| "Tel.-Kosten" | Role-dependent (`base_cost`, `reseller_cost`, `customer_cost`) | Consistent |
| "Kosten (€)" | `cost` (form), role-dependent (table) | **INCONSISTENT** |
| "Kosten (Cents)" | `cost_cents` | Consistent |
| "Basiskosten" | `base_cost` | Consistent |
| "Mandanten-Kosten" | `reseller_cost` | Consistent |
| "Kunden-Kosten" | `customer_cost` | Consistent |

### 6.3 Role-Based Security

**Excellent Consistency**: All display locations properly implement role-based access:

#### Super Admin
- Sees: `base_cost`, `reseller_cost`, `customer_cost`, all profit fields
- Full breakdown in modals
- All profit widgets visible

#### Reseller
- Sees: `reseller_cost`, `customer_cost` (for their customers)
- Own profit data only
- Limited profit widgets

#### Customer
- Sees: `customer_cost` only
- No profit data
- No financial widgets

**Security Implementation**: Consistent across:
- Table columns (visibility checks)
- Modals (content sections)
- Widgets (`canView()` methods)
- CSV exports (field selection)

---

## 7. Critical Issues Found

### 7.1 CSV Export Field Mismatch

**Issue**: CSV export uses `cost` field while table displays role-based costs.

**Location**: `/var/www/api-gateway/app/Filament/Resources/CallResource/Pages/ListCalls.php:109`

**Current Code**:
```php
number_format(($call->cost ?? 0) / 100, 2, ',', '.')
```

**Expected**:
```php
// Should match table display logic (role-based cost)
$primaryCost = /* role-based calculation from CallResource table column */
number_format($primaryCost / 100, 2, ',', '.')
```

**Impact**: Exported costs may differ from displayed costs in table view.

### 7.2 Currency Format Inconsistency

**Issue**: Mixed usage of comma vs dot decimal separators and currency symbol placement.

**Examples**:
- Table: `12.50€` (dot decimal, suffix)
- Modal: `12,50 €` (comma decimal, suffix with space)
- Widget: `€12.50` (dot decimal, prefix)
- Chart tooltip: `12,50 €` (comma decimal, suffix with space)

**Recommendation**: Standardize on German format throughout:
- Comma as decimal separator: `,`
- Dot as thousands separator: `.`
- Currency symbol: `€` (suffix with space)
- Example: `1.234,56 €`

### 7.3 Actual vs Estimated Cost Indication

**Good Implementation**: Visual indicators exist (green/yellow dots) but could be more prominent.

**Current**:
- Green dot: Actual costs from Retell API
- Yellow dot: Estimated costs

**Recommendation**: Consider adding text label for clarity, especially in exports.

---

## 8. Data Flow Architecture

### Cost Calculation Pipeline

```
1. Retell Webhook
   ↓ (receives USD costs)

2. PlatformCostService
   ↓ (converts to EUR, stores in retell_cost_eur_cents, twilio_cost_eur_cents)

3. CostCalculator Service
   ↓ (calculates base_cost, reseller_cost, customer_cost, profits)

4. Call Model Update
   ↓ (stores all calculated fields)

5. Display Layer
   ↓ (role-based filtering)

6. User Interface
   (table columns, widgets, modals, exports)
```

### Cost Priority Hierarchy (CostCalculator)

```
1. total_external_cost_eur_cents (HIGHEST PRIORITY - actual tracked costs)
   ↓
2. retell_cost_eur_cents + twilio_cost_eur_cents (component costs)
   ↓
3. Estimated calculation (duration-based)
   ↓
4. Default fallback (10 cents/min + 5 cents fixed)
```

---

## 9. Summary & Recommendations

### Strengths
1. **Comprehensive cost tracking**: Multiple granularity levels (external, calculated, profit)
2. **Role-based security**: Consistently implemented across all views
3. **Visual indicators**: Actual vs estimated costs clearly marked
4. **Caching strategy**: Good performance optimization
5. **Detailed breakdowns**: Excellent transparency in profit modals

### Issues Requiring Attention

#### High Priority
1. **CSV Export Field Mismatch**: Fix to use role-based costs
2. **Currency Format Standardization**: Choose and apply consistent format

#### Medium Priority
3. **Legacy `cost` field**: Consider deprecation in favor of `cost_cents` and role-specific fields
4. **Export metadata**: Add "actual/estimated" indicator to CSV exports

#### Low Priority
5. **Decimal separator**: Fully standardize on German format (comma)
6. **Documentation**: Add inline comments explaining cost field relationships

### Positive Findings
- **No security leaks**: Profit data properly restricted by role
- **Data integrity**: Cost calculations follow clear, documented logic
- **User experience**: Multiple detail levels (summary → detail → breakdown)
- **Performance**: Effective caching prevents performance issues

---

## 10. Field Reference Matrix

| Database Field | Type | Display Locations | Visibility | Format |
|----------------|------|-------------------|------------|--------|
| `retell_cost_usd` | decimal(10,4) | Technical details only | Super Admin | Raw |
| `retell_cost_eur_cents` | integer | Cost breakdown (modal) | Super Admin | Cents |
| `twilio_cost_usd` | decimal(10,4) | Technical details only | Super Admin | Raw |
| `twilio_cost_eur_cents` | integer | Cost breakdown (modal) | Super Admin | Cents |
| `total_external_cost_eur_cents` | integer | Cost breakdown (modal) | Super Admin | Cents |
| `cost` | decimal(2) | **CSV export only** | Role-based | EUR |
| `cost_cents` | integer | Tech details, Stats widget | Role-based | Cents |
| `base_cost` | integer | Table, Modal, Widgets | Super Admin | EUR |
| `reseller_cost` | integer | Table, Modal, Widgets | Super Admin, Reseller | EUR |
| `customer_cost` | integer | Table, Modal, Widgets | All roles | EUR |
| `platform_profit` | integer | Modal, Widgets, Dashboard | Super Admin | EUR |
| `reseller_profit` | integer | Modal, Widgets, Dashboard | Super Admin, Reseller | EUR |
| `total_profit` | integer | Modal, Widgets, Dashboard | Super Admin | EUR |
| `profit_margin_platform` | decimal(2) | Modal, Widgets | Super Admin | % |
| `profit_margin_reseller` | decimal(2) | Modal, Widgets | Super Admin, Reseller | % |
| `profit_margin_total` | decimal(2) | Modal, Widgets, Dashboard | Super Admin | % |

---

## Appendix: File Locations

### Core Files Analyzed
- `/var/www/api-gateway/app/Filament/Resources/CallResource.php` (table, form, infolist)
- `/var/www/api-gateway/app/Filament/Resources/CallResource/Pages/ListCalls.php` (CSV export)
- `/var/www/api-gateway/app/Filament/Resources/CallResource/Pages/ViewCall.php` (detail view)
- `/var/www/api-gateway/app/Filament/Resources/CallResource/Widgets/CallStatsOverview.php` (stats widget)
- `/var/www/api-gateway/app/Filament/Widgets/ProfitOverviewWidget.php` (profit stats)
- `/var/www/api-gateway/app/Filament/Widgets/ProfitChartWidget.php` (profit chart)
- `/var/www/api-gateway/app/Filament/Pages/ProfitDashboard.php` (dashboard page)
- `/var/www/api-gateway/app/Services/CostCalculator.php` (calculation logic)
- `/var/www/api-gateway/app/Models/Call.php` (model definitions)
- `/var/www/api-gateway/resources/views/filament/modals/profit-details.blade.php` (profit modal)
- `/var/www/api-gateway/resources/views/filament/profit-display.blade.php` (profit component)
- `/var/www/api-gateway/database/migrations/2025_09_29_151103_add_external_costs_to_calls_table.php` (schema)

### Total Files Reviewed: 12 primary files + related route/config files

---

**Analysis Complete**
**Status**: Documentation Only - No Code Changes Made
**Next Steps**: Review findings with team before implementing consistency improvements
