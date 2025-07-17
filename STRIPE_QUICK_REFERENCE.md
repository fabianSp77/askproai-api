# 🚀 Stripe Payment System Quick Reference

## 🔑 Essential Information

### Production URLs
- **Admin Panel**: https://api.askproai.de/admin
- **Webhook Endpoint**: https://api.askproai.de/api/stripe/webhook
- **Business Portal**: https://portal.askproai.de

### Test Mode
```bash
# Enable test mode
touch .stripe-test-mode.lock

# Disable test mode
rm .stripe-test-mode.lock
```

### Database Access
```bash
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
```

## 💳 Quick Commands

### Check Payment Status
```php
// Find recent topups
BalanceTopup::latest()->take(10)->get();

// Check failed payments
BalanceTopup::where('status', 'failed')
    ->where('created_at', '>=', now()->subDay())
    ->get();

// View company balance
$company = Company::find($id);
echo $company->prepaidBalance->balance;
```

### Process Manual Topup
```php
$company = Company::find($companyId);
$balance = $company->prepaidBalance;
$balance->addBalance(100.00, 'Manual topup', 'admin_adjustment');
```

### Enable Auto-Topup
```php
$autoService = app(AutoTopupService::class);
$autoService->configureAutoTopup(
    $company,
    true,      // enabled
    20.00,     // threshold
    100.00,    // amount
    $paymentMethodId
);
```

## 🚨 Common Issues & Fixes

### Payment Failed - Balance Not Updated
```php
// Find and fix
$topup = BalanceTopup::where('stripe_payment_intent_id', 'pi_xxx')->first();
if ($topup && $topup->status === 'processing') {
    $topup->markAsSucceeded();
}
```

### Webhook Signature Failed
```bash
# Update webhook secret
STRIPE_WEBHOOK_SECRET=whsec_new_secret_here
php artisan config:clear
```

### Auto-Topup Not Working
```sql
-- Check configuration
SELECT * FROM prepaid_balances WHERE company_id = ?;

-- Check daily limit
SELECT COUNT(*) FROM balance_transactions 
WHERE company_id = ? 
AND reference_type = 'auto_topup' 
AND DATE(created_at) = CURDATE();
```

## 📊 Monitoring Queries

### Daily Revenue
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as topups,
    SUM(amount) as revenue
FROM balance_topups
WHERE status = 'succeeded'
AND DATE(created_at) = CURDATE();
```

### Low Balance Companies
```sql
SELECT c.name, pb.balance 
FROM prepaid_balances pb 
JOIN companies c ON c.id = pb.company_id 
WHERE pb.balance < pb.low_balance_threshold 
ORDER BY pb.balance ASC;
```

### Failed Payment Reasons
```sql
SELECT 
    JSON_EXTRACT(stripe_response, '$.error.code') as error,
    COUNT(*) as count
FROM balance_topups
WHERE status = 'failed'
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY error;
```

## 🔧 Test Cards

- ✅ **Success**: 4242 4242 4242 4242
- ❌ **Decline**: 4000 0000 0000 9995
- 🔐 **3D Secure**: 4000 0025 0000 3155
- 💶 **SEPA**: DE89 3704 0044 0532 0130 00

## 📞 Support Templates

### Payment Failed
```
Subject: Payment Failed - Action Required

Your payment of €[Amount] failed.
Error: [Error Message]

Please update your payment method:
[Portal Link]

Current balance: €[Balance]
```

### Low Balance
```
Subject: Low Balance Alert

Current Balance: €[Balance]
Threshold: €[Threshold]

Top up now: [Topup Link]
Enable auto-topup: [Settings Link]
```

## 🛟 Emergency Procedures

### All Payments Failing
1. Check Stripe status: https://status.stripe.com
2. Verify API keys in .env
3. Check test/live mode mismatch
4. Clear config cache

### Webhook Processing Stopped
1. Check Horizon: `php artisan horizon:status`
2. Verify webhook secret
3. Check Laravel logs
4. Replay from Stripe Dashboard

### Balance Discrepancy
```php
// Recalculate from transactions
$correctBalance = BalanceTransaction::where('company_id', $companyId)
    ->sum('amount');
$company->prepaidBalance->update(['balance' => $correctBalance]);
```

## 📋 Daily Checklist

- [ ] Check failed payments count
- [ ] Review low balance companies  
- [ ] Monitor webhook health
- [ ] Check auto-topup failures
- [ ] Review large transactions

## 🔐 Security Reminders

- Never store card details
- Always verify webhooks
- Use HTTPS only
- Rotate keys quarterly
- Log all transactions
- Monitor suspicious activity

## 📚 Documentation Links

- [Full Documentation](./STRIPE_PAYMENT_SYSTEM_DOCUMENTATION.md)
- [Developer Guide](./STRIPE_DEVELOPER_SETUP_GUIDE.md)
- [Operations Manual](./STRIPE_OPERATIONS_MANUAL.md)
- [Troubleshooting](./STRIPE_TROUBLESHOOTING_GUIDE.md)
- [Security](./STRIPE_SECURITY_BEST_PRACTICES.md)

---

**Last Updated**: 2025-07-10
**Version**: 1.0