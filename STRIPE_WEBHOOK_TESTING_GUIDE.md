# Stripe Webhook Testing Guide

This guide explains how to test the Stripe webhook integration for the billing system using Stripe CLI.

## Prerequisites

1. **Install Stripe CLI**
   ```bash
   # macOS
   brew install stripe/stripe-cli/stripe
   
   # Linux (Debian/Ubuntu)
   curl -s https://packages.stripe.com/api/security/keypair/stripe-cli-gpg/public | gpg --dearmor | sudo tee /usr/share/keyrings/stripe.gpg
   echo "deb [signed-by=/usr/share/keyrings/stripe.gpg] https://packages.stripe.com/stripe-cli-debian-local stable main" | sudo tee -a /etc/apt/sources.list.d/stripe.list
   sudo apt update
   sudo apt install stripe
   ```

2. **Login to Stripe CLI**
   ```bash
   stripe login
   ```

## Setting Up Webhook Forwarding

1. **Start webhook forwarding to your local environment**
   ```bash
   # For local development
   stripe listen --forward-to localhost:8000/api/webhooks/stripe
   
   # For production testing (replace with your actual domain)
   stripe listen --forward-to https://api.askproai.de/api/webhooks/stripe
   ```

2. **Copy the webhook signing secret**
   The CLI will display a webhook signing secret like `whsec_test_xxxxx`. Add this to your `.env`:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_test_xxxxx
   ```

## Testing Specific Webhook Events

### 1. Test Invoice Payment Succeeded
```bash
stripe trigger invoice.payment_succeeded
```

### 2. Test Invoice Payment Failed
```bash
stripe trigger invoice.payment_failed
```

### 3. Test Subscription Created
```bash
stripe trigger customer.subscription.created
```

### 4. Test Subscription Updated
```bash
stripe trigger customer.subscription.updated
```

### 5. Test Subscription Deleted
```bash
stripe trigger customer.subscription.deleted
```

### 6. Test Charge Succeeded (One-time payment)
```bash
stripe trigger charge.succeeded
```

### 7. Test Complete Checkout Session
```bash
stripe trigger checkout.session.completed
```

### 8. Test Trial Will End Notification
```bash
stripe trigger customer.subscription.trial_will_end
```

## Creating Test Scenarios

### Scenario 1: Complete Subscription Flow
```bash
# 1. Create a customer
stripe customers create \
  --name="Test Company GmbH" \
  --email="billing@testcompany.de" \
  --metadata="company_id=1"

# 2. Create a subscription with trial
stripe subscriptions create \
  --customer=cus_xxx \
  --items[0][price]=price_xxx \
  --trial_period_days=14 \
  --metadata="company_id=1"

# 3. End trial and charge
stripe subscriptions update sub_xxx \
  --trial_end=now
```

### Scenario 2: Failed Payment Recovery
```bash
# 1. Create a subscription with a card that will fail
stripe subscriptions create \
  --customer=cus_xxx \
  --items[0][price]=price_xxx \
  --payment_behavior=default_incomplete \
  --payment_settings[payment_method_types][0]=card \
  --default_payment_method=pm_card_chargeDeclined

# 2. Watch the invoice.payment_failed webhook fire
# 3. Update payment method to succeed
stripe payment_methods attach pm_card_visa \
  --customer=cus_xxx

stripe customers update cus_xxx \
  --invoice_settings[default_payment_method]=pm_card_visa

# 4. Retry the invoice
stripe invoices pay in_xxx
```

### Scenario 3: Usage-Based Billing
```bash
# 1. Create a metered subscription
stripe subscriptions create \
  --customer=cus_xxx \
  --items[0][price]=price_xxx_metered

# 2. Report usage
stripe subscription_items create_usage_record si_xxx \
  --quantity=1250 \
  --timestamp=$(date +%s) \
  --action=set

# 3. Trigger invoice creation
stripe invoices create \
  --customer=cus_xxx \
  --auto_advance=true
```

## Monitoring Webhooks

### 1. Check webhook status in your application
```bash
# View all webhooks
php artisan webhooks:status

# View only Stripe webhooks
php artisan webhooks:status --provider=stripe

# View failed webhooks
php artisan webhooks:status --status=failed

# Watch webhooks in real-time
php artisan webhooks:status --watch

# View statistics
php artisan webhooks:status --stats
```

### 2. Retry failed webhooks
```bash
# Retry all failed Stripe webhooks
php artisan webhooks:retry-failed --provider=stripe

# Retry specific webhook
php artisan webhooks:retry-failed --webhook-id=123

# Dry run to see what would be retried
php artisan webhooks:retry-failed --provider=stripe --dry-run

# Retry webhooks failed in last hour
php artisan webhooks:retry-failed --since="1 hour ago"
```

### 3. Monitor in Stripe Dashboard
- Go to [Stripe Dashboard > Developers > Webhooks](https://dashboard.stripe.com/test/webhooks)
- Click on your endpoint
- View webhook attempts and responses

## Troubleshooting

### Common Issues

1. **Signature Verification Failed**
   - Ensure `STRIPE_WEBHOOK_SECRET` in `.env` matches the one from Stripe CLI
   - Check that you're not modifying the raw request body

2. **Webhook Timeout**
   - Webhooks are processed asynchronously via queues
   - Ensure Horizon is running: `php artisan horizon`
   - Check queue workers: `php artisan queue:work`

3. **Company Not Found**
   - Ensure webhook payload includes `metadata.company_id`
   - Check that company has `stripe_customer_id` set

4. **Duplicate Webhooks**
   - System automatically handles duplicates using idempotency keys
   - Check logs for "Duplicate webhook detected" messages

### Debug Mode

Enable detailed logging for webhook processing:
```php
// In .env
LOG_CHANNEL=daily
LOG_LEVEL=debug
WEBHOOK_DEBUG=true
```

### View Logs
```bash
# Application logs
tail -f storage/logs/laravel.log | grep -i stripe

# Webhook-specific logs
tail -f storage/logs/webhooks.log

# Billing-specific logs
tail -f storage/logs/billing-*.log
```

## Production Webhook Configuration

1. **Add production webhook endpoint in Stripe Dashboard**
   - URL: `https://api.askproai.de/api/webhooks/stripe`
   - Events to subscribe:
     - `checkout.session.completed`
     - `customer.*`
     - `invoice.*`
     - `payment_intent.*`
     - `charge.*`
     - `price.*`
     - `product.*`

2. **Copy the production webhook secret**
   - Add to production `.env`: `STRIPE_WEBHOOK_SECRET=whsec_xxxxx`

3. **Test production webhooks**
   ```bash
   # Send test event from Stripe Dashboard
   # Go to webhook endpoint > Send test webhook
   ```

## Testing Your Implementation

### Run the test suite
```bash
# Run billing-specific tests
php artisan test --filter=Billing
php artisan test --filter=Stripe
php artisan test --filter=Webhook

# Run with coverage
php artisan test --coverage --filter=Billing
```

### Manual Integration Test
```bash
# 1. Start webhook forwarding
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# 2. Start queue workers
php artisan horizon

# 3. Monitor webhooks
php artisan webhooks:status --watch

# 4. In another terminal, trigger test events
stripe trigger invoice.payment_succeeded
stripe trigger customer.subscription.created

# 5. Verify processing
php artisan webhooks:status --stats
```

## Webhook Event Reference

### Critical Events (High Priority Queue)
- `invoice.payment_succeeded` - Payment received
- `invoice.payment_failed` - Payment failed, needs attention
- `customer.subscription.deleted` - Subscription cancelled
- `charge.failed` - One-time charge failed

### Important Events (Medium Priority Queue)
- `customer.subscription.created` - New subscription
- `customer.subscription.updated` - Subscription changed
- `invoice.finalized` - Invoice ready for payment
- `checkout.session.completed` - Checkout completed

### Informational Events (Low Priority Queue)
- `customer.created` - New customer created
- `payment_method.attached` - Payment method added
- `invoice.upcoming` - Upcoming invoice preview
- `price.created` - New price created

## Best Practices

1. **Always test webhooks in test mode first**
2. **Use metadata to link Stripe objects to your database**
3. **Implement proper error handling and retries**
4. **Log all webhook events for debugging**
5. **Monitor webhook success rates**
6. **Set up alerts for critical failures**
7. **Use idempotency keys to prevent duplicates**
8. **Process webhooks asynchronously via queues**