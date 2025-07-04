# Prepaid Billing System - Setup Guide

## 🚀 Quick Start

### 1. Stripe Configuration
```bash
# Add to your .env file:
STRIPE_SECRET=sk_test_... # Your Stripe secret key
STRIPE_PUBLISHABLE_KEY=pk_test_... # Your Stripe publishable key
STRIPE_WEBHOOK_SECRET=whsec_... # Will be generated in step 2
```

### 2. Configure Stripe Webhook
1. Log in to [Stripe Dashboard](https://dashboard.stripe.com)
2. Go to **Developers** → **Webhooks**
3. Click **Add endpoint**
4. Enter URL: `https://api.askproai.de/api/stripe/webhook`
5. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.failed`
   - `checkout.session.completed`
   - `charge.refunded`
6. Copy the **Signing secret** to `STRIPE_WEBHOOK_SECRET` in `.env`

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Set Up Company Billing
```bash
# Via Tinker
php artisan tinker

# Create billing rate for a company
$company = Company::find(1);
$rate = new BillingRate();
$rate->company_id = $company->id;
$rate->rate_per_minute = 0.42;
$rate->billing_increment = 1; // Per second billing
$rate->save();

# Initialize prepaid balance
$balance = new PrepaidBalance();
$balance->company_id = $company->id;
$balance->balance = 0.00;
$balance->reserved_balance = 0.00;
$balance->low_balance_threshold = 20.00; # 20€ threshold
$balance->save();
```

### 5. Test the System
1. Access the billing dashboard: `/business/billing`
2. Click "Guthaben aufladen" to test top-up
3. Use Stripe test card: `4242 4242 4242 4242`
4. Make a test call to verify balance deduction

## 📊 Monitoring

### Check Balance Status
```bash
php artisan billing:check-low-balances --company=1
```

### View Scheduled Jobs
```bash
php artisan schedule:list | grep billing
```

### Monitor Logs
```bash
tail -f storage/logs/prepaid-balance-monitoring.log
tail -f storage/logs/laravel.log | grep -i stripe
```

## 🔧 Configuration Options

### Adjust Low Balance Threshold
```php
// For specific company
$balance = PrepaidBalance::where('company_id', $companyId)->first();
$balance->low_balance_threshold = 50.00; // 50€
$balance->save();
```

### Change Billing Rate
```php
$rate = BillingRate::where('company_id', $companyId)->first();
$rate->rate_per_minute = 0.35; // New rate
$rate->save();
```

## 🎯 Key Features

- **Atomic Operations**: All balance changes are atomic to prevent race conditions
- **Balance Reservation**: Balance is reserved during active calls
- **Automatic Monitoring**: Checks every 30 minutes for low balances
- **Secure Payments**: PCI-compliant through Stripe
- **Detailed Reporting**: Transaction history and usage statistics

## 🚨 Troubleshooting

### Payment Not Reflecting
1. Check Stripe webhook logs in dashboard
2. Verify webhook endpoint is reachable
3. Check Laravel logs for errors

### Balance Not Deducting
1. Verify call has `duration_sec` field
2. Check if company has billing rate configured
3. Review `call_charges` table for records

### Low Balance Warning Not Sent
1. Check if balance is below threshold
2. Verify `last_warning_sent_at` is null or > 24 hours ago
3. Check email queue/logs

## 📝 API Endpoints

### Portal Endpoints
- `GET /business/billing` - Billing dashboard
- `GET /business/billing/topup` - Top-up page
- `POST /business/billing/topup` - Process top-up
- `GET /business/billing/transactions` - Transaction history
- `GET /business/billing/usage` - Usage statistics

### Webhook Endpoint
- `POST /api/stripe/webhook` - Stripe payment webhook

## 🔒 Security Notes

- Never log or store full credit card numbers
- Always verify Stripe webhook signatures
- Use HTTPS for all payment-related endpoints
- Implement rate limiting on top-up attempts
- Regular security audits of payment flow