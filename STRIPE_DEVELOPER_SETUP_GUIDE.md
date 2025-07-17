# 👨‍💻 Stripe Developer Setup Guide

## 🚀 Quick Start

### 1. Prerequisites
- PHP 8.1+
- Composer installed
- Access to Stripe Dashboard
- `.env` file configured

### 2. Installation Steps

```bash
# 1. Install dependencies
composer install

# 2. Copy environment template
cp .env.example .env

# 3. Add Stripe credentials to .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# 4. Run migrations
php artisan migrate

# 5. Install Stripe CLI (optional but recommended)
# macOS
brew install stripe/stripe-cli/stripe

# Linux
curl -L https://github.com/stripe/stripe-cli/releases/download/v1.17.1/stripe_1.17.1_linux_x86_64.tar.gz | tar xz
sudo mv stripe /usr/local/bin
```

## 🔧 Local Development Setup

### Setting Up Test Environment

#### 1. Create Test Data
```bash
# Create test company with billing enabled
php artisan tinker
```
```php
// Create company
$company = Company::create([
    'name' => 'Test Company',
    'email' => 'test@example.com',
    'phone' => '+4912345678',
    'prepaid_billing_enabled' => true
]);

// Create prepaid balance
$balance = PrepaidBalance::create([
    'company_id' => $company->id,
    'balance' => 100.00,
    'low_balance_threshold' => 20.00
]);

// Create billing rate
$rate = BillingRate::create([
    'company_id' => $company->id,
    'rate_per_minute' => 0.42,
    'billing_increment' => 1,
    'is_active' => true
]);

// Create portal user
$user = PortalUser::create([
    'company_id' => $company->id,
    'name' => 'Test User',
    'email' => 'user@example.com',
    'password' => bcrypt('password'),
    'is_active' => true
]);
```

#### 2. Configure Stripe Test Mode
```bash
# Enable test mode
touch .stripe-test-mode.lock

# Or use the helper script
./test-stripe-billing.sh start
```

#### 3. Test Webhook Locally
```bash
# Using Stripe CLI
stripe listen --forward-to localhost:8000/api/stripe/webhook

# Copy the webhook signing secret that appears
# Update your .env with this temporary secret
STRIPE_WEBHOOK_SECRET=whsec_temporary_secret_here
```

### Local Testing Workflows

#### Test Manual Topup
```bash
# 1. Generate topup link
php artisan tinker
>>> $company = Company::first();
>>> $user = $company->portalUsers()->first();
>>> $service = app(StripeTopupService::class);
>>> $session = $service->createCheckoutSession($company, 50.00, $user);
>>> echo $session->url;

# 2. Open the URL in browser
# 3. Use test card: 4242 4242 4242 4242
# 4. Complete payment
# 5. Check webhook was received
```

#### Test Auto-Topup
```php
// Configure auto-topup
$autoService = app(AutoTopupService::class);
$autoService->configureAutoTopup(
    $company,
    true, // enabled
    10.00, // threshold
    50.00, // amount
    'pm_card_visa' // test payment method
);

// Simulate low balance
$balance = $company->prepaidBalance;
$balance->update(['balance' => 5.00]);

// Trigger auto-topup check
$autoService->checkAndExecuteAutoTopup($company);
```

#### Test Call Charging
```php
// Create test call
$call = Call::create([
    'company_id' => $company->id,
    'phone_number' => '+4912345678',
    'duration_sec' => 120, // 2 minutes
    'status' => 'completed'
]);

// Charge the call
$charge = CallCharge::chargeCall($call);

// Check balance
$balance = $company->prepaidBalance->fresh();
echo "New balance: €" . $balance->balance;
```

## 🧪 Testing Strategies

### Unit Tests

#### Test Stripe Service
```php
// tests/Unit/Services/StripeTopupServiceTest.php
public function test_creates_checkout_session()
{
    // Mock Stripe
    $this->mock(StripeClient::class, function ($mock) {
        $mock->shouldReceive('checkout->sessions->create')
            ->once()
            ->andReturn((object)['id' => 'cs_test_123', 'url' => 'https://...']);
    });

    $service = app(StripeTopupService::class);
    $session = $service->createCheckoutSession($company, 50.00, $user);
    
    $this->assertNotNull($session);
    $this->assertEquals('cs_test_123', $session->id);
}
```

#### Test Balance Operations
```php
// tests/Unit/Models/PrepaidBalanceTest.php
public function test_deducts_balance_correctly()
{
    $balance = PrepaidBalance::factory()->create(['balance' => 100.00]);
    
    $transaction = $balance->deductBalance(25.00, 'Test charge');
    
    $this->assertEquals(75.00, $balance->fresh()->balance);
    $this->assertEquals(-25.00, $transaction->amount);
}
```

### Integration Tests

#### Test Webhook Processing
```php
// tests/Feature/StripeWebhookTest.php
public function test_processes_payment_succeeded_webhook()
{
    $topup = BalanceTopup::factory()->create([
        'status' => 'processing',
        'stripe_payment_intent_id' => 'pi_test_123'
    ]);

    $payload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'amount' => 5000, // €50
                'currency' => 'eur'
            ]
        ]
    ];

    $response = $this->postJson('/api/stripe/webhook', $payload, [
        'Stripe-Signature' => $this->generateSignature($payload)
    ]);

    $response->assertOk();
    $this->assertEquals('succeeded', $topup->fresh()->status);
}
```

### End-to-End Tests

#### Full Payment Flow Test
```bash
# Create E2E test script
cat > test-payment-flow.php << 'EOF'
<?php
// 1. Create checkout session
$session = createCheckoutSession($company, 100.00);
echo "Checkout URL: " . $session->url . "\n";

// 2. Simulate payment (in real E2E, use Selenium/Puppeteer)
echo "Complete payment at the URL above...\n";
echo "Press enter when done...";
fgets(STDIN);

// 3. Check balance updated
$balance = $company->prepaidBalance->fresh();
echo "New balance: €" . $balance->balance . "\n";

// 4. Verify invoice created
$topup = BalanceTopup::where('stripe_checkout_session_id', $session->id)->first();
if ($topup->invoice_id) {
    echo "Invoice created: #" . $topup->invoice->number . "\n";
}
EOF

php test-payment-flow.php
```

## 🔍 Debugging Tools

### Stripe CLI Commands
```bash
# Listen to webhooks
stripe listen --forward-to localhost:8000/api/stripe/webhook

# Trigger test events
stripe trigger payment_intent.succeeded

# View recent events
stripe events list

# Inspect specific event
stripe events retrieve evt_1234567890
```

### Laravel Debugging
```php
// Add to webhook controller for debugging
Log::channel('stripe')->info('Webhook received', [
    'type' => $event->type,
    'data' => $event->data->object
]);

// Enable SQL query logging
DB::enableQueryLog();
// ... run code ...
dd(DB::getQueryLog());

// Debug balance calculations
$balance = $company->prepaidBalance;
dump([
    'balance' => $balance->balance,
    'reserved' => $balance->reserved_balance,
    'effective' => $balance->getEffectiveBalance(),
    'threshold' => $balance->low_balance_threshold
]);
```

### Common Development Issues

#### Issue: Webhook signature verification fails locally
```bash
# Solution 1: Use Stripe CLI
stripe listen --forward-to localhost:8000/api/stripe/webhook

# Solution 2: Disable verification in local
// In StripeWebhookController for LOCAL ONLY
if (app()->environment('local')) {
    // Skip signature verification
} else {
    $event = Webhook::constructEvent(...);
}
```

#### Issue: Payment methods not showing
```php
// Debug payment methods
$stripe = new StripeClient(config('services.stripe.secret'));
$methods = $stripe->paymentMethods->all([
    'customer' => $company->stripe_customer_id,
    'type' => 'card'
]);
dd($methods);
```

## 🛠️ Development Utilities

### Artisan Commands

Create useful commands for development:

```php
// app/Console/Commands/StripeTestCommand.php
class StripeTestCommand extends Command
{
    protected $signature = 'stripe:test {action}';
    
    public function handle()
    {
        switch ($this->argument('action')) {
            case 'customer':
                $this->createTestCustomer();
                break;
            case 'topup':
                $this->createTestTopup();
                break;
            case 'webhook':
                $this->testWebhook();
                break;
        }
    }
}
```

### Helper Scripts

#### Reset Test Data
```bash
#!/bin/bash
# scripts/reset-stripe-test-data.sh

echo "Resetting Stripe test data..."

# Clear local database
php artisan tinker <<EOF
BalanceTopup::truncate();
BalanceTransaction::truncate();
CallCharge::truncate();
PrepaidBalance::truncate();
BillingRate::truncate();
EOF

echo "Test data reset complete"
```

#### Generate Test Transactions
```php
// scripts/generate-test-transactions.php
$company = Company::first();
$faker = Faker\Factory::create();

// Generate random topups
for ($i = 0; $i < 10; $i++) {
    $amount = $faker->randomElement([25, 50, 100, 200]);
    $topup = BalanceTopup::create([
        'company_id' => $company->id,
        'amount' => $amount,
        'status' => 'succeeded',
        'paid_at' => $faker->dateTimeBetween('-30 days', 'now')
    ]);
    
    $company->prepaidBalance->addBalance($amount, 'Test topup', 'topup', $topup->id);
}

// Generate random calls
for ($i = 0; $i < 50; $i++) {
    $call = Call::create([
        'company_id' => $company->id,
        'duration_sec' => $faker->numberBetween(30, 600),
        'phone_number' => $faker->phoneNumber
    ]);
    
    CallCharge::chargeCall($call);
}
```

## 📊 Development Dashboard

Create a simple dashboard for development:

```php
// routes/dev.php (only in local)
if (app()->environment('local')) {
    Route::get('/dev/stripe', function () {
        $companies = Company::with('prepaidBalance')->get();
        $recentTopups = BalanceTopup::latest()->take(10)->get();
        $recentCharges = CallCharge::latest()->take(10)->get();
        
        return view('dev.stripe-dashboard', compact(
            'companies', 'recentTopups', 'recentCharges'
        ));
    });
}
```

## 🔗 Useful Resources

### Documentation
- [Stripe API Reference](https://stripe.com/docs/api)
- [Stripe PHP SDK](https://github.com/stripe/stripe-php)
- [Laravel Cashier](https://laravel.com/docs/billing) (not used but good reference)

### Tools
- [Stripe Dashboard](https://dashboard.stripe.com)
- [Stripe CLI](https://stripe.com/docs/stripe-cli)
- [Webhook Testing](https://webhook.site)
- [Request Bin](https://requestbin.com)

### Test Resources
- [Test Cards](https://stripe.com/docs/testing#cards)
- [Test Bank Accounts](https://stripe.com/docs/testing#sepa-direct-debit)
- [Test Webhooks](https://stripe.com/docs/webhooks/test)

### Community
- [Stripe Discord](https://discord.gg/stripe)
- [Stack Overflow - Stripe Tag](https://stackoverflow.com/questions/tagged/stripe-payments)

## 🎯 Development Checklist

Before pushing code:
- [ ] All tests passing
- [ ] Webhook handling tested
- [ ] Error cases handled
- [ ] Logging added for debugging
- [ ] Security reviewed (no keys in code)
- [ ] Documentation updated
- [ ] Migration rollback tested

## 🚨 Security Reminders

1. **Never commit API keys** - Use environment variables
2. **Always verify webhooks** - Check signatures
3. **Validate amounts** - Prevent negative topups
4. **Log sensitive data carefully** - No full card numbers
5. **Use test mode** - Never test with real money
6. **Secure payment methods** - Validate ownership
7. **Rate limit APIs** - Prevent abuse

---

Happy coding! 🎉 Remember to test thoroughly and handle edge cases.