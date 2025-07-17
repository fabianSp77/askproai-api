# 📋 Stripe Operations Manual

## 🎯 Purpose
This manual provides step-by-step procedures for managing the Stripe payment system in production, handling common scenarios, and resolving issues.

---

## 📊 Daily Operations

### Morning Checklist (9:00 AM)

```bash
# 1. Check system health
php artisan horizon:status

# 2. Review overnight failures
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT COUNT(*) as failed_payments 
FROM balance_topups 
WHERE status = 'failed' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);"

# 3. Check low balance companies
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT c.name, pb.balance 
FROM prepaid_balances pb 
JOIN companies c ON c.id = pb.company_id 
WHERE pb.balance < pb.low_balance_threshold 
ORDER BY pb.balance ASC 
LIMIT 10;"

# 4. Verify webhook health
tail -n 100 storage/logs/laravel.log | grep -i "stripe webhook"
```

### Monitoring Dashboard URLs
- **Admin Panel**: https://api.askproai.de/admin
- **Stripe Dashboard**: https://dashboard.stripe.com
- **Horizon Queue**: https://api.askproai.de/horizon

---

## 💰 Payment Management Procedures

### 1. Processing Manual Topup

#### Via Admin Panel
1. Navigate to `/admin/prepaid-balances`
2. Find the company
3. Click "Anpassen" (Adjust)
4. Select "Aufladung (+)"
5. Enter amount and description
6. Click "Save"

#### Via Command Line
```bash
php artisan tinker
```
```php
$company = Company::where('name', 'Company Name')->first();
$balance = $company->prepaidBalance;
$balance->addBalance(100.00, 'Manual topup by admin', 'admin_adjustment');
```

### 2. Processing Refunds

#### Full Call Refund
```php
// Find the call charge
$charge = CallCharge::find($chargeId);

// Process refund
$refundService = app(CallRefundService::class);
$refund = $refundService->refundCall($charge, 'Customer complaint');

// Verify
echo "Refunded: €" . $refund->amount;
```

#### Partial Refund
```php
$refund = $refundService->refundCallPartial(
    $charge, 
    10.00, // amount to refund
    'Partial refund - billing dispute'
);
```

### 3. Failed Payment Recovery

#### Automatic Retry
Failed payments are automatically retried by Stripe based on smart retry logic.

#### Manual Retry
```bash
# Find failed topup
php artisan tinker
>>> $topup = BalanceTopup::where('status', 'failed')->find($topupId);

# Generate new payment link
>>> $service = app(StripeTopupService::class);
>>> $session = $service->createCheckoutSession(
...     $topup->company, 
...     $topup->amount, 
...     $topup->initiatedBy
... );
>>> echo $session->url;

# Send link to customer via email
```

### 4. Auto-Topup Management

#### Enable Auto-Topup
```php
$company = Company::find($companyId);
$autoService = app(AutoTopupService::class);

$autoService->configureAutoTopup(
    $company,
    true,      // enabled
    20.00,     // threshold (€20)
    100.00,    // topup amount (€100)
    $paymentMethodId
);
```

#### Disable Auto-Topup
```php
$autoService->configureAutoTopup($company, false);
```

#### Update Payment Method
```php
// List current payment methods
$stripeService = app(StripeTopupService::class);
$methods = $stripeService->listPaymentMethods($company);

// Update auto-topup payment method
$balance = $company->prepaidBalance;
$balance->update(['stripe_payment_method_id' => $newPaymentMethodId]);
```

---

## 🚨 Incident Response

### Payment Failures Spike

**Symptoms**: Multiple payment failures in short time

**Response**:
```bash
# 1. Check Stripe status
curl https://status.stripe.com/api/v2/status.json | jq '.status.indicator'

# 2. Review recent failures
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as time,
    COUNT(*) as failures,
    JSON_EXTRACT(stripe_response, '$.error.code') as error_code
FROM balance_topups 
WHERE status = 'failed' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i'), error_code
ORDER BY time DESC;"

# 3. Check for common patterns
tail -n 1000 storage/logs/laravel.log | grep -i "stripe" | grep -i "error"
```

**Actions**:
1. If Stripe issue: Wait and monitor
2. If card network issue: Notify affected customers
3. If configuration issue: Check API keys

### Webhook Processing Failures

**Symptoms**: Payments succeed but balances not updated

**Diagnosis**:
```bash
# Check webhook logs
grep "Stripe webhook" storage/logs/laravel.log | tail -50

# Check failed jobs
php artisan queue:failed | grep stripe

# Verify webhook configuration
curl https://api.askproai.de/api/stripe/webhook -X POST
```

**Resolution**:
```bash
# 1. Replay webhooks from Stripe Dashboard
# Go to: https://dashboard.stripe.com/webhooks
# Click on failed webhook → Resend

# 2. Or manually process
php artisan tinker
>>> $controller = app(StripeWebhookController::class);
>>> // Create request with webhook data
>>> $controller->handleWebhook($request);
```

### Balance Discrepancies

**Investigation**:
```sql
-- Compare expected vs actual balance
SELECT 
    c.name,
    pb.balance as current_balance,
    (
        SELECT SUM(amount) 
        FROM balance_transactions 
        WHERE company_id = c.id
    ) as calculated_balance,
    pb.balance - (
        SELECT SUM(amount) 
        FROM balance_transactions 
        WHERE company_id = c.id
    ) as difference
FROM companies c
JOIN prepaid_balances pb ON pb.company_id = c.id
HAVING difference != 0;
```

**Correction**:
```php
// Recalculate balance from transactions
$company = Company::find($companyId);
$correctBalance = BalanceTransaction::where('company_id', $company->id)
    ->sum('amount');

// Update if needed
$company->prepaidBalance->update(['balance' => $correctBalance]);

// Log the correction
Log::channel('billing')->warning('Balance correction', [
    'company_id' => $company->id,
    'old_balance' => $oldBalance,
    'new_balance' => $correctBalance,
    'corrected_by' => auth()->id()
]);
```

---

## 📈 Reporting

### Daily Revenue Report
```sql
-- Daily revenue summary
SELECT 
    DATE(created_at) as date,
    COUNT(*) as topup_count,
    SUM(amount) as gross_revenue,
    SUM(CASE WHEN invoice_id IS NOT NULL THEN amount ELSE 0 END) as invoiced_amount,
    AVG(amount) as avg_topup
FROM balance_topups
WHERE status = 'succeeded'
AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Monthly Billing Summary
```sql
-- Monthly summary by company
SELECT 
    c.name as company,
    COUNT(DISTINCT bt.id) as topup_count,
    SUM(CASE WHEN bt.type = 'topup' THEN bt.amount ELSE 0 END) as total_topups,
    SUM(CASE WHEN bt.type = 'charge' THEN ABS(bt.amount) ELSE 0 END) as total_usage,
    pb.balance as current_balance,
    CASE 
        WHEN pb.auto_topup_enabled THEN 'Yes' 
        ELSE 'No' 
    END as auto_topup
FROM companies c
JOIN prepaid_balances pb ON pb.company_id = c.id
LEFT JOIN balance_transactions bt ON bt.company_id = c.id 
    AND bt.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
WHERE c.prepaid_billing_enabled = 1
GROUP BY c.id
ORDER BY total_usage DESC;
```

### Failed Payments Analysis
```sql
-- Failed payment reasons
SELECT 
    JSON_UNQUOTE(JSON_EXTRACT(stripe_response, '$.error.code')) as error_code,
    JSON_UNQUOTE(JSON_EXTRACT(stripe_response, '$.error.decline_code')) as decline_code,
    COUNT(*) as count
FROM balance_topups
WHERE status = 'failed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY error_code, decline_code
ORDER BY count DESC;
```

---

## 🛠️ Maintenance Tasks

### Weekly Tasks

#### 1. Review Auto-Topup Performance
```sql
-- Auto-topup success rate
SELECT 
    DATE_FORMAT(created_at, '%Y-%u') as week,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as successful,
    (SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
FROM balance_topups
WHERE reference_type = 'auto_topup'
AND created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
GROUP BY week
ORDER BY week DESC;
```

#### 2. Clean Up Old Data
```bash
# Archive old webhook events
php artisan tinker
>>> WebhookEvent::where('created_at', '<', now()->subMonths(3))->delete();

# Clean up old failed topups
>>> BalanceTopup::where('status', 'failed')
...     ->where('created_at', '<', now()->subMonth())
...     ->delete();
```

### Monthly Tasks

#### 1. Invoice Reconciliation
```php
// Generate missing invoices
$topups = BalanceTopup::where('status', 'succeeded')
    ->whereNull('invoice_id')
    ->get();

foreach ($topups as $topup) {
    $service = app(StripeTopupService::class);
    $service->createTopupInvoice($topup);
}
```

#### 2. Update Exchange Rates (if multi-currency)
```bash
# Update rates from Stripe
php artisan stripe:update-exchange-rates
```

#### 3. Security Audit
```bash
# Check for unusual activity
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
-- Large topups
SELECT 
    c.name,
    bt.created_at,
    bt.amount,
    bt.description
FROM balance_topups bt
JOIN companies c ON c.id = bt.company_id
WHERE bt.amount > 1000
AND bt.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY bt.amount DESC;

-- Rapid topups
SELECT 
    c.name,
    COUNT(*) as topup_count,
    SUM(amount) as total_amount
FROM balance_topups bt
JOIN companies c ON c.id = bt.company_id
WHERE bt.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY c.id
HAVING topup_count > 5
ORDER BY topup_count DESC;"
```

---

## 📞 Customer Support Procedures

### Common Customer Issues

#### "My payment failed"
```bash
# 1. Find the failed payment
php artisan tinker
>>> $topup = BalanceTopup::where('company_id', $companyId)
...     ->where('status', 'failed')
...     ->latest()
...     ->first();
>>> $topup->stripe_response['error']['message'] ?? 'Unknown error';

# 2. Common solutions:
# - Card declined: Ask for different payment method
# - Insufficient funds: Try smaller amount
# - 3D Secure required: Complete authentication
```

#### "Balance not updated after payment"
```bash
# 1. Check payment status in Stripe
# Search by customer email in Stripe Dashboard

# 2. Check local records
>>> $topup = BalanceTopup::where('stripe_payment_intent_id', 'pi_xxx')->first();
>>> $topup->status;

# 3. If payment succeeded but balance not updated:
>>> $topup->markAsSucceeded(); // This updates balance
```

#### "I want to cancel auto-topup"
```php
$company = Company::find($companyId);
$company->prepaidBalance->update([
    'auto_topup_enabled' => false
]);

// Confirm to customer
echo "Auto-topup has been disabled for " . $company->name;
```

### Support Templates

#### Payment Failed Email
```
Subject: Payment Failed - Action Required

Dear [Customer Name],

We were unable to process your payment of €[Amount] for your AskProAI account.

Error: [Error Message]

To ensure uninterrupted service, please:
1. Update your payment method at: [Portal Link]
2. Or make a manual top-up at: [Topup Link]

Your current balance is: €[Current Balance]

Best regards,
AskProAI Support Team
```

#### Low Balance Warning
```
Subject: Low Balance Alert - AskProAI

Dear [Customer Name],

Your AskProAI account balance is running low:
Current Balance: €[Balance]
Threshold: €[Threshold]

To avoid service interruption, please top up your account:
[Topup Link]

Consider enabling auto-topup for convenience:
[Settings Link]

Best regards,
AskProAI Support Team
```

---

## 🔐 Security Procedures

### API Key Rotation

**When to rotate**:
- Every 90 days (recommended)
- After security incident
- When employee with access leaves

**Procedure**:
```bash
# 1. Generate new keys in Stripe Dashboard
# 2. Update production environment
ssh root@hosting215275.ae83d.netcup.net
cd /var/www/api-gateway
nano .env
# Update STRIPE_SECRET and STRIPE_KEY

# 3. Clear cache
php artisan config:clear

# 4. Test with small transaction
php artisan tinker
>>> $service = app(StripeTopupService::class);
>>> $intent = $service->createPaymentIntent($company, 1.00, $user);

# 5. Update webhook signing secret if rotated
```

### Suspicious Activity Response

**Indicators**:
- Multiple failed payments from same IP
- Unusual topup amounts
- Rapid succession of topups
- International cards for local business

**Response**:
```bash
# 1. Block suspicious company temporarily
>>> Company::find($companyId)->update(['is_active' => false]);

# 2. Review transaction history
>>> BalanceTopup::where('company_id', $companyId)
...     ->where('created_at', '>', now()->subDays(7))
...     ->get();

# 3. Contact customer for verification
# 4. Report to Stripe if fraud suspected
```

---

## 📚 Appendix

### Useful SQL Queries

```sql
-- Companies never topped up
SELECT c.* FROM companies c
LEFT JOIN balance_topups bt ON bt.company_id = c.id
WHERE c.prepaid_billing_enabled = 1
AND bt.id IS NULL;

-- Average customer lifetime value
SELECT 
    AVG(total_topups) as avg_ltv,
    AVG(months_active) as avg_months
FROM (
    SELECT 
        company_id,
        SUM(amount) as total_topups,
        TIMESTAMPDIFF(MONTH, MIN(created_at), MAX(created_at)) as months_active
    FROM balance_topups
    WHERE status = 'succeeded'
    GROUP BY company_id
) as company_stats;

-- Churn prediction (no topup in 60 days)
SELECT 
    c.name,
    c.email,
    pb.balance,
    MAX(bt.created_at) as last_topup
FROM companies c
JOIN prepaid_balances pb ON pb.company_id = c.id
LEFT JOIN balance_topups bt ON bt.company_id = c.id AND bt.status = 'succeeded'
GROUP BY c.id
HAVING last_topup < DATE_SUB(NOW(), INTERVAL 60 DAY)
AND pb.balance < 50;
```

### Emergency Contacts

- **Stripe Support**: Dashboard → Help → Contact Support
- **Server Issues**: Netcup Support Portal
- **Database Issues**: Check MariaDB logs first
- **Application Errors**: Check Laravel logs

### Compliance Checklist

- [ ] Monthly invoice archive backup
- [ ] PCI compliance check (via Stripe)
- [ ] Data retention policy compliance
- [ ] GDPR data export ready
- [ ] Financial audit trail complete

---

Remember: When in doubt, check the logs first, test in staging, and always backup before major changes.