# 🔧 Stripe Payment System Troubleshooting Guide

## 🚨 Critical Issues (Immediate Action Required)

### 🔴 All Payments Failing

**Symptoms**:
- All payment attempts fail
- Error: "Invalid API Key" or similar
- No successful topups in last hour

**Quick Diagnosis**:
```bash
# 1. Check API key validity
curl https://api.stripe.com/v1/charges \
  -u sk_test_...: \
  -d amount=100 \
  -d currency=eur \
  -d source=tok_visa

# 2. Check environment
grep STRIPE .env

# 3. Check if in test mode when should be live
ls -la .stripe-test-mode.lock
```

**Solutions**:
1. **Wrong Environment**:
   ```bash
   # Remove test mode lock
   rm .stripe-test-mode.lock
   
   # Update .env with correct keys
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   
   # Clear cache
   php artisan config:clear
   ```

2. **Expired/Revoked Keys**:
   - Log into Stripe Dashboard
   - Generate new API keys
   - Update .env
   - Test with small transaction

### 🔴 Webhooks Not Processing

**Symptoms**:
- Payments succeed in Stripe but balance not updated
- No webhook logs in Laravel
- Stripe shows webhook failures

**Quick Fix**:
```bash
# 1. Test webhook endpoint
curl -X POST https://api.askproai.de/api/stripe/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true}'

# 2. Check webhook secret
grep STRIPE_WEBHOOK_SECRET .env

# 3. Check Laravel logs
tail -f storage/logs/laravel.log | grep -i webhook
```

**Resolution Steps**:
1. **Update Webhook Secret**:
   ```bash
   # Get secret from Stripe Dashboard
   # Update .env
   STRIPE_WEBHOOK_SECRET=whsec_new_secret_here
   php artisan config:clear
   ```

2. **Replay Failed Webhooks**:
   - Go to Stripe Dashboard → Webhooks
   - Click on failed webhook
   - Click "Resend"

---

## 🟡 Common Issues

### 1. Payment Succeeded but Balance Not Updated

**Diagnosis**:
```php
// Check if topup exists
$topup = BalanceTopup::where('stripe_payment_intent_id', 'pi_xxx')->first();

// Check status
echo $topup->status; // Should be 'succeeded'

// Check balance transaction
$transaction = BalanceTransaction::where('reference_id', $topup->id)
    ->where('reference_type', 'topup')
    ->first();
```

**Fix**:
```php
// Manually process the topup
if ($topup && $topup->status === 'processing') {
    $topup->markAsSucceeded();
}

// Or manually add balance
$company = $topup->company;
$balance = $company->prepaidBalance;
$balance->addBalance($topup->amount, 'Manual correction - webhook failed', 'topup', $topup->id);
```

### 2. Auto-Topup Not Triggering

**Check Configuration**:
```sql
SELECT 
    pb.*,
    c.name 
FROM prepaid_balances pb
JOIN companies c ON c.id = pb.company_id
WHERE pb.company_id = ?;
```

**Common Issues**:
1. **No Payment Method**:
   ```php
   // Check if payment method exists
   $balance = PrepaidBalance::find($id);
   if (!$balance->stripe_payment_method_id) {
       echo "No payment method configured";
   }
   ```

2. **Daily Limit Reached**:
   ```php
   // Check daily count
   $count = BalanceTransaction::where('company_id', $companyId)
       ->where('reference_type', 'auto_topup')
       ->whereDate('created_at', today())
       ->count();
   echo "Auto-topups today: $count";
   ```

3. **Threshold Not Met**:
   ```php
   $balance = $company->prepaidBalance;
   echo "Current: €{$balance->balance}\n";
   echo "Threshold: €{$balance->auto_topup_threshold}\n";
   echo "Should trigger: " . ($balance->balance <= $balance->auto_topup_threshold ? 'Yes' : 'No');
   ```

### 3. Invoice Not Generated

**Check Invoice Creation**:
```php
$topup = BalanceTopup::find($topupId);

// Check if invoice exists
if ($topup->invoice_id) {
    $invoice = Invoice::find($topup->invoice_id);
    echo "Invoice exists: #{$invoice->number}";
} else {
    // Generate invoice
    $service = app(StripeTopupService::class);
    $invoice = $service->createTopupInvoice($topup);
    echo "Invoice created: #{$invoice->number}";
}
```

**Email Not Sent**:
```bash
# Check mail configuration
php artisan tinker
>>> Mail::raw('Test', function ($m) { $m->to('test@example.com')->subject('Test'); });

# Check mail queue
php artisan queue:work --queue=emails --tries=1
```

### 4. Payment Method Errors

**"Payment method not found"**:
```php
// Validate payment method
$stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
try {
    $pm = $stripe->paymentMethods->retrieve($paymentMethodId);
    echo "Payment method valid: " . $pm->type;
} catch (\Exception $e) {
    echo "Invalid payment method: " . $e->getMessage();
}
```

**"Customer not found"**:
```php
// Check/create Stripe customer
$company = Company::find($companyId);
if (!$company->stripe_customer_id) {
    $service = app(StripeTopupService::class);
    $customerId = $service->getOrCreateCustomer($company);
    echo "Created customer: $customerId";
}
```

---

## 🟢 Performance Issues

### Slow Checkout Loading

**Diagnosis**:
```bash
# Check API response time
time curl https://api.stripe.com/v1/checkout/sessions \
  -u sk_test_...: \
  -d "success_url=https://example.com" \
  -d "line_items[0][price_data][currency]=eur" \
  -d "line_items[0][price_data][product_data][name]=Test" \
  -d "line_items[0][price_data][unit_amount]=1000" \
  -d "line_items[0][quantity]=1" \
  -d "mode=payment"
```

**Optimization**:
```php
// Cache customer ID
Cache::remember("stripe_customer_{$company->id}", 3600, function () use ($company) {
    return $this->getOrCreateCustomer($company);
});

// Pre-create setup intents
$setupIntent = $stripe->setupIntents->create([
    'customer' => $customerId,
    'usage' => 'off_session'
]);
```

### High Database Load from Transactions

**Identify Heavy Queries**:
```sql
-- Find slow transaction queries
SHOW PROCESSLIST;

-- Optimize transaction queries
EXPLAIN SELECT * FROM balance_transactions 
WHERE company_id = ? 
ORDER BY created_at DESC 
LIMIT 100;
```

**Add Indexes**:
```sql
-- Add composite index
ALTER TABLE balance_transactions 
ADD INDEX idx_company_created (company_id, created_at);

-- Add covering index for common query
ALTER TABLE balance_topups 
ADD INDEX idx_status_created (status, created_at);
```

---

## 🔍 Debugging Tools & Commands

### Laravel Tinker Debugging

```php
// Get payment details
$topup = BalanceTopup::latest()->first();
dump([
    'id' => $topup->id,
    'amount' => $topup->amount,
    'status' => $topup->status,
    'stripe_pi' => $topup->stripe_payment_intent_id,
    'error' => $topup->stripe_response['error'] ?? null
]);

// Check webhook processing
$webhook = WebhookEvent::where('provider', 'stripe')->latest()->first();
dump([
    'event_type' => $webhook->event_type,
    'status' => $webhook->status,
    'attempts' => $webhook->attempts,
    'error' => $webhook->error_message
]);
```

### Stripe CLI Debugging

```bash
# Listen to all events
stripe listen --print-json

# Filter specific events
stripe listen --events payment_intent.succeeded,payment_intent.payment_failed

# Forward to local with headers
stripe listen --forward-to localhost:8000/api/stripe/webhook \
  --forward-connect-timeout 60 \
  --forward-request-timeout 60
```

### Database Queries

```sql
-- Failed payments by error type
SELECT 
    JSON_EXTRACT(stripe_response, '$.error.code') as error_code,
    COUNT(*) as count,
    MIN(created_at) as first_seen,
    MAX(created_at) as last_seen
FROM balance_topups
WHERE status = 'failed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY error_code
ORDER BY count DESC;

-- Payment success rate by hour
SELECT 
    DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) as successful,
    (SUM(CASE WHEN status = 'succeeded' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as success_rate
FROM balance_topups
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY hour
ORDER BY hour DESC;
```

---

## 📊 Monitoring & Alerts

### Set Up Monitoring

```bash
# Create monitoring script
cat > monitor-stripe-health.sh << 'EOF'
#!/bin/bash

# Check failed payments
FAILED=$(mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -sN -e "
SELECT COUNT(*) FROM balance_topups 
WHERE status = 'failed' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")

if [ $FAILED -gt 5 ]; then
    echo "ALERT: $FAILED failed payments in last hour"
    # Send alert email/SMS
fi

# Check webhook failures
WEBHOOK_FAILS=$(tail -n 1000 /var/www/api-gateway/storage/logs/laravel.log | 
    grep "Stripe webhook" | grep -c "failed")

if [ $WEBHOOK_FAILS -gt 0 ]; then
    echo "ALERT: $WEBHOOK_FAILS webhook failures detected"
fi
EOF

chmod +x monitor-stripe-health.sh

# Add to crontab
*/15 * * * * /path/to/monitor-stripe-health.sh
```

### Logging Best Practices

```php
// Add detailed logging in StripeTopupService
Log::channel('stripe')->info('Creating checkout session', [
    'company_id' => $company->id,
    'amount' => $amount,
    'user_id' => $user->id,
    'correlation_id' => Str::uuid()
]);

// Log all Stripe API errors
try {
    $session = CheckoutSession::create($params);
} catch (\Stripe\Exception\ApiErrorException $e) {
    Log::channel('stripe')->error('Stripe API Error', [
        'error' => $e->getMessage(),
        'code' => $e->getStripeCode(),
        'request_id' => $e->getRequestId(),
        'http_status' => $e->getHttpStatus()
    ]);
    throw $e;
}
```

---

## 🚑 Emergency Procedures

### Complete Payment System Failure

```bash
# 1. Enable maintenance mode
php artisan down --message="Payment system maintenance" --retry=60

# 2. Switch to backup payment processor (if available)
# Update .env
PAYMENT_PROCESSOR=backup

# 3. Notify customers
php artisan customers:notify --message="Payment system temporarily unavailable"

# 4. Fix issue and test
php artisan up
```

### Data Recovery

```bash
# Restore from Stripe
php artisan stripe:sync-payments --from="2024-01-01" --to="2024-01-31"

# Rebuild balance from transactions
php artisan tinker
>>> $company = Company::find($id);
>>> $calculatedBalance = BalanceTransaction::where('company_id', $company->id)->sum('amount');
>>> $company->prepaidBalance->update(['balance' => $calculatedBalance]);
```

---

## 📝 Troubleshooting Checklist

### For Support Team

- [ ] Check payment status in Stripe Dashboard
- [ ] Verify webhook was received
- [ ] Check Laravel logs for errors
- [ ] Confirm customer has sufficient funds
- [ ] Verify payment method is valid
- [ ] Check if in test/live mode
- [ ] Review auto-topup settings
- [ ] Check daily/monthly limits
- [ ] Verify invoice was generated
- [ ] Confirm email was sent

### For Developers

- [ ] API keys correct and not expired
- [ ] Webhook secret configured
- [ ] Queue workers running
- [ ] Database indexes optimized
- [ ] Error logging enabled
- [ ] Monitoring alerts configured
- [ ] Backup payment method available
- [ ] Recovery procedures documented
- [ ] Test coverage adequate
- [ ] Performance metrics tracked

---

## 🆘 Getting Help

### Internal Resources
- Check `storage/logs/laravel.log`
- Review `storage/logs/stripe.log` (if configured)
- Database audit trail in `balance_transactions`

### External Resources
- [Stripe Status Page](https://status.stripe.com)
- [Stripe Error Codes](https://stripe.com/docs/error-codes)
- [Stripe Support](https://support.stripe.com)

### Escalation Path
1. Check documentation and logs
2. Try suggested fixes
3. Contact senior developer
4. Open Stripe support ticket
5. Implement workaround if critical

---

Remember: Most issues are configuration-related. Always check the basics first before diving deep into debugging.