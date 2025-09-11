# Multi-Tier Billing System Documentation

**Version**: 1.0  
**Date**: 2025-09-10  
**Status**: ✅ Production Ready

## Executive Summary

The AskProAI platform now supports a sophisticated multi-tier billing system that enables resellers (Mandanten) to bring their own customers while maintaining transparent pricing and commission structures. This system automatically handles:

- **Multi-level pricing**: Platform → Reseller → End Customer
- **Automatic commission calculation and distribution**
- **Transparent transaction tracking across all tiers**
- **Flexible markup and pricing controls**

## Business Model Overview

### Pricing Hierarchy

```
Platform (AskProAI)
    ├── Direct Customers ─────────→ Pay standard rates (0.42€/min)
    └── Resellers (Mandanten)
        ├── Base cost ────────────→ Pay platform rates (0.30€/min)
        └── Their Customers
            └── Retail price ─────→ Pay reseller rates (0.40€/min)
                                    Reseller profit: 0.10€/min
```

### Revenue Flow Example

For a 10-minute call through a reseller:
1. **End Customer pays**: 4.00€ (0.40€/min × 10)
2. **Reseller keeps**: 1.00€ commission (25% of gross)
3. **Platform receives**: 3.00€ (0.30€/min × 10)

## Technical Architecture

### Database Schema

#### 1. Tenant Hierarchy
```sql
tenants
├── id (bigint)
├── parent_tenant_id (bigint, nullable) -- Links to reseller
├── tenant_type (enum)
│   ├── platform          -- The platform itself
│   ├── reseller          -- Mandanten who bring customers
│   ├── direct_customer   -- Direct platform customers
│   └── reseller_customer -- Customers of resellers
├── commission_rate (decimal)      -- % for resellers
├── base_cost_cents (int)          -- Platform cost
├── reseller_markup_cents (int)    -- Reseller markup
└── billing_mode (enum: direct|through_reseller)
```

#### 2. Transaction Tracking
```sql
transactions
├── tenant_id                  -- Who paid/received
├── reseller_tenant_id         -- Involved reseller
├── commission_amount_cents    -- Commission amount
├── base_cost_cents           -- Platform cost
├── reseller_revenue_cents    -- Reseller earnings
└── parent_transaction_id     -- Links related transactions
```

#### 3. Commission Management
```sql
commission_ledger
├── reseller_tenant_id        -- Reseller earning commission
├── customer_tenant_id        -- Customer who generated revenue
├── gross_amount_cents        -- Total charged
├── platform_cost_cents       -- Platform's share
├── commission_cents          -- Reseller's earnings
└── status (pending|approved|paid)
```

### Key Models and Services

#### Tenant Model Extensions
```php
class Tenant extends Model
{
    // Relationship methods
    public function parentTenant()      // Get reseller
    public function childTenants()      // Get customers
    
    // Business logic
    public function isReseller()        // Check if reseller
    public function hasReseller()       // Check if has reseller
    public function getEffectivePrice() // Calculate pricing
    public function calculateCommission() // Commission calculation
}
```

#### BillingChainService
Core service that handles multi-tier billing:
```php
class BillingChainService
{
    public function processBillingChain($customer, $serviceType, $quantity)
    {
        // 1. Calculate costs through hierarchy
        // 2. Create transactions for all parties
        // 3. Update balances atomically
        // 4. Track commissions
        // 5. Generate alerts if needed
    }
}
```

## Implementation Guide

### 1. Setting Up a Reseller

```php
$reseller = Tenant::create([
    'name' => 'Premium Solutions GmbH',
    'tenant_type' => 'reseller',
    'commission_rate' => 25.0,           // 25% commission
    'base_cost_cents' => 30,             // Pay 0.30€/min to platform
    'reseller_markup_cents' => 10,       // Add 0.10€/min markup
    'can_set_prices' => true,
    'min_markup_percent' => 10,          // Minimum 10% markup
    'max_markup_percent' => 50,          // Maximum 50% markup
]);
```

### 2. Creating Reseller Customers

```php
$customer = Tenant::create([
    'name' => 'Friseursalon Eleganz',
    'tenant_type' => 'reseller_customer',
    'parent_tenant_id' => $reseller->id,
    'billing_mode' => 'through_reseller',
    'balance_cents' => 50000,  // 500€ initial credit
]);
```

### 3. Processing Transactions

```php
use App\Services\BillingChainService;

$billingService = new BillingChainService();

// Process a 5-minute call
$transactions = $billingService->processBillingChain(
    $customer,
    'call',
    5,  // minutes
    ['metadata' => 'additional context']
);
```

### 4. Viewing Commission Reports

```php
// Get reseller's commission ledger
$commissions = $reseller->commissionLedger()
    ->whereBetween('created_at', [$start, $end])
    ->get();

// Calculate totals
$totalRevenue = $commissions->sum('gross_amount_cents');
$totalCommission = $commissions->sum('commission_cents');
$profitMargin = ($totalCommission / $totalRevenue) * 100;
```

## API Endpoints

### For Platform Administrators

#### Create Reseller
```http
POST /api/tenants
{
    "name": "New Reseller GmbH",
    "tenant_type": "reseller",
    "commission_rate": 20,
    "base_cost_cents": 30,
    "reseller_markup_cents": 10
}
```

#### View Commission Report
```http
GET /api/resellers/{id}/commissions?start=2025-09-01&end=2025-09-30
```

### For Resellers

#### List Their Customers
```http
GET /api/reseller/customers
Authorization: Bearer {reseller_api_key}
```

#### View Earnings
```http
GET /api/reseller/earnings
Authorization: Bearer {reseller_api_key}
```

## Pricing Configuration

### Standard Pricing Tiers

| Customer Type | Platform Cost | Reseller Cost | Customer Price | Commission |
|--------------|---------------|---------------|----------------|------------|
| Direct | - | - | 0.42€/min | 0% |
| Via Reseller | 0.30€/min | 0.30€/min | 0.40€/min | 25% of gross |

### Configurable Parameters

- **commission_rate**: Percentage of gross revenue for reseller
- **base_cost_cents**: What platform charges reseller
- **reseller_markup_cents**: Fixed markup amount
- **min/max_markup_percent**: Allowed markup range

## Billing Scenarios

### Scenario 1: Direct Customer
```
Customer → Platform
- Customer pays: 0.42€/min
- Platform receives: 0.42€/min
- No intermediary
```

### Scenario 2: Reseller Customer
```
Customer → Reseller → Platform
- Customer pays: 0.40€/min (to reseller)
- Reseller keeps: 0.10€/min (commission)
- Platform receives: 0.30€/min (base cost)
```

### Scenario 3: Volume Discount
```
Large Reseller (>1000 min/month)
- Special rate: 0.25€/min from platform
- Customer price: 0.38€/min
- Reseller margin: 0.13€/min
```

## Commission Payout Process

### Automatic Payouts
```php
// Configure auto-payout
$reseller->update([
    'auto_commission_payout' => true,
    'commission_payout_threshold_cents' => 5000  // 50€
]);

// System automatically creates payout when threshold reached
```

### Manual Payouts
```php
use App\Models\ResellerPayout;

$payout = ResellerPayout::create([
    'reseller_tenant_id' => $reseller->id,
    'amount_cents' => $totalCommission,
    'period_start' => $startDate,
    'period_end' => $endDate,
    'payout_method' => 'bank_transfer'
]);

$payout->process(); // Execute payout
```

## Admin Panel Integration

### Reseller Management Views

1. **Reseller List** (`/admin/tenants?type=reseller`)
   - View all resellers
   - Commission rates
   - Active customers
   - Revenue metrics

2. **Reseller Detail** (`/admin/tenants/{id}`)
   - Customer list
   - Commission history
   - Payout records
   - Performance analytics

3. **Commission Dashboard** (`/admin/billing/commissions`)
   - Real-time commission tracking
   - Payout management
   - Revenue distribution charts

## Testing

### Unit Tests
```bash
php artisan test --filter=MultiTierBillingTest
```

### Integration Test
```bash
php scripts/test-multi-tier-billing.php
```

### Manual Testing Checklist
- [ ] Create reseller account
- [ ] Add customers to reseller
- [ ] Process transactions
- [ ] Verify commission calculation
- [ ] Check balance updates
- [ ] Review transaction logs
- [ ] Test payout process

## Monitoring and Alerts

### Key Metrics to Monitor
- **Commission Accuracy**: Actual vs. expected commission rates
- **Transaction Volume**: By reseller and customer
- **Balance Health**: Low balance warnings
- **Payout Status**: Pending vs. completed payouts

### Alert Triggers
```php
// Low customer balance
if ($customer->balance_cents < 1000) {
    // Send alert to reseller
}

// High commission pending
if ($reseller->pending_commission > 10000) {
    // Trigger payout review
}
```

## Security Considerations

1. **Access Control**
   - Resellers can only see their own customers
   - Customers cannot see reseller margins
   - Platform admins have full visibility

2. **Transaction Integrity**
   - All billing operations in database transactions
   - Atomic balance updates
   - Full audit trail

3. **API Security**
   - Separate API keys per tenant
   - Rate limiting per tier
   - Encrypted sensitive data

## Migration from Single-Tier

### For Existing Customers
```sql
-- Set all existing tenants as direct customers
UPDATE tenants 
SET tenant_type = 'direct_customer' 
WHERE tenant_type IS NULL;
```

### For New Resellers
```php
// Convert direct customer to reseller
$tenant->update([
    'tenant_type' => 'reseller',
    'commission_rate' => 20,
    // ... other reseller settings
]);
```

## Troubleshooting

### Common Issues

1. **Foreign Key Constraint Errors**
   - Ensure parent_tenant_id references valid tenant
   - Check tenant_type compatibility

2. **Commission Not Calculating**
   - Verify commission_rate is set
   - Check billing_mode is 'through_reseller'
   - Ensure parent_tenant relationship exists

3. **Balance Discrepancies**
   - Review transaction logs
   - Check for failed transactions
   - Verify atomic updates

## Future Enhancements

### Planned Features
- [ ] Tiered commission rates based on volume
- [ ] White-label portal for resellers
- [ ] Automated invoicing
- [ ] Multi-currency support
- [ ] Reseller-specific pricing plans
- [ ] Advanced analytics dashboard

### API v2 Endpoints
- Bulk customer import for resellers
- Commission forecasting
- Custom billing cycles
- Webhook notifications for resellers

## Support

For technical support or questions about the multi-tier billing system:
- Technical Documentation: `/docs/api/billing`
- Admin Guide: `/admin/help/billing`
- Support Email: billing@askproai.de

---

*This document is maintained by the AskProAI Development Team*  
*Last Updated: 2025-09-10*