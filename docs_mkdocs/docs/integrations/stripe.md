# Stripe Integration

## Overview

Stripe integration enables subscription management, payment processing, and billing for AskProAI's SaaS model. The system supports multiple pricing tiers, usage-based billing, and automated invoice generation.

## Configuration

### Environment Variables
```bash
# Stripe Configuration
STRIPE_KEY=pk_live_xxxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxx
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=de_DE
```

### Laravel Cashier Setup
```bash
# Install Cashier
composer require laravel/cashier

# Publish migrations
php artisan vendor:publish --tag="cashier-migrations"

# Run migrations
php artisan migrate
```

## Subscription Plans

### Plan Configuration
```php
// config/subscription-plans.php
return [
    'starter' => [
        'stripe_price_id' => 'price_starter_monthly',
        'features' => [
            'calls_per_month' => 100,
            'branches' => 1,
            'staff' => 3,
            'custom_agent' => false
        ]
    ],
    'professional' => [
        'stripe_price_id' => 'price_pro_monthly',
        'features' => [
            'calls_per_month' => 500,
            'branches' => 3,
            'staff' => 10,
            'custom_agent' => true
        ]
    ],
    'enterprise' => [
        'stripe_price_id' => 'price_enterprise_monthly',
        'features' => [
            'calls_per_month' => 'unlimited',
            'branches' => 'unlimited',
            'staff' => 'unlimited',
            'custom_agent' => true
        ]
    ]
];
```

### Creating Subscriptions
```php
// Subscribe company to plan
$company->newSubscription('default', 'price_pro_monthly')
    ->trialDays(14)
    ->create($paymentMethod);

// With metered billing for extra calls
$company->newSubscription('default', 'price_pro_monthly')
    ->meteredPrice('price_per_call')
    ->create($paymentMethod);
```

## Payment Processing

### Payment Methods
```php
// Add payment method
$company->addPaymentMethod($paymentMethodId);
$company->updateDefaultPaymentMethod($paymentMethodId);

// SEPA Direct Debit for German market
$company->createAsStripeCustomer([
    'payment_method' => $paymentMethodId,
    'invoice_settings' => [
        'default_payment_method' => $paymentMethodId,
    ],
    'address' => [
        'country' => 'DE',
        'postal_code' => $company->postal_code,
        'city' => $company->city,
        'line1' => $company->address,
    ]
]);
```

### Payment Intent
```php
// Create payment intent for one-time charges
$payment = $company->charge(5000, $paymentMethodId, [
    'description' => 'Additional call credits',
    'metadata' => [
        'company_id' => $company->id,
        'type' => 'call_credits',
        'amount' => 100
    ]
]);
```

## Usage-Based Billing

### Track Usage
```php
// Report call usage to Stripe
class ReportCallUsage extends Job
{
    public function handle()
    {
        $companies = Company::whereNotNull('stripe_id')->get();
        
        foreach ($companies as $company) {
            $callCount = $company->calls()
                ->whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])
                ->count();
            
            // Report usage for metered billing
            $company->subscription('default')
                ->reportUsageFor('price_per_call', $callCount);
        }
    }
}
```

### Usage Records
```php
// Track detailed usage
$usageRecord = StripeUsageRecord::create([
    'subscription_item' => $subscriptionItem,
    'quantity' => 25,
    'timestamp' => time(),
    'action' => 'set' // or 'increment'
]);
```

## Webhook Handling

### Webhook Endpoints
```php
// routes/web.php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('cashier.webhook');
```

### Webhook Events
```php
class StripeWebhookController extends WebhookController
{
    protected function handleCustomerSubscriptionCreated($payload)
    {
        $company = Company::where('stripe_id', $payload['data']['object']['customer'])->first();
        
        // Activate features based on plan
        $this->activatePlanFeatures($company, $payload['data']['object']);
        
        // Send welcome email
        Mail::to($company->email)->send(new SubscriptionActivated($company));
    }
    
    protected function handleInvoicePaymentFailed($payload)
    {
        $company = Company::where('stripe_id', $payload['data']['object']['customer'])->first();
        
        // Notify admin
        $company->notify(new PaymentFailed($payload['data']['object']));
        
        // Suspend services after grace period
        SuspendServicesJob::dispatch($company)->delay(now()->addDays(3));
    }
}
```

## Invoice Management

### Invoice Configuration
```php
// Customize invoice data
$company->updateStripeCustomer([
    'tax_id_data' => [[
        'type' => 'eu_vat',
        'value' => $company->vat_number
    ]],
    'invoice_settings' => [
        'custom_fields' => [
            ['name' => 'Company ID', 'value' => $company->id],
            ['name' => 'Branch', 'value' => $company->branches->first()->name]
        ],
        'footer' => 'Thank you for using AskProAI!'
    ]
]);
```

### Download Invoices
```php
// Invoice download endpoint
Route::get('/billing/invoice/{invoice}', function ($invoiceId) {
    return request()->user()->company
        ->downloadInvoice($invoiceId, [
            'vendor' => 'AskProAI GmbH',
            'product' => 'AI Phone Service',
            'street' => 'Beispielstraße 123',
            'location' => '10115 Berlin, Germany',
            'phone' => '+49 30 123456789'
        ]);
});
```

## Subscription Management

### Plan Changes
```php
// Upgrade/downgrade subscription
$company->subscription('default')->swap('price_enterprise_monthly');

// With proration
$company->subscription('default')
    ->swapAndInvoice('price_enterprise_monthly');

// Cancel subscription
$company->subscription('default')->cancel();

// Cancel at end of period
$company->subscription('default')->cancelAtEndOfPeriod();
```

### Trial Management
```php
// Extend trial
$company->subscription('default')
    ->extendTrial(now()->addDays(7));

// Skip trial
$company->subscription('default')
    ->skipTrial()
    ->create($paymentMethod);
```

## Customer Portal

### Portal Configuration
```php
// Enable Stripe Customer Portal
return $request->user()->company
    ->redirectToBillingPortal(route('billing'));
```

### Custom Billing Page
```php
class BillingController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        
        return view('billing.index', [
            'subscription' => $company->subscription('default'),
            'invoices' => $company->invoices(),
            'upcomingInvoice' => $company->upcomingInvoice(),
            'paymentMethods' => $company->paymentMethods()
        ]);
    }
}
```

## Pricing Calculations

### Dynamic Pricing
```php
class PricingCalculator
{
    public function calculateMonthlyPrice(Company $company): int
    {
        $basePrice = $this->getBasePlanPrice($company->plan);
        $additionalCalls = max(0, $company->monthly_calls - $company->included_calls);
        $callCharges = $additionalCalls * 10; // €0.10 per extra call
        
        return $basePrice + $callCharges;
    }
    
    public function estimateNextInvoice(Company $company): array
    {
        return [
            'base_plan' => $company->plan_price,
            'extra_calls' => $this->calculateExtraCallCharges($company),
            'additional_branches' => $this->calculateBranchCharges($company),
            'tax' => $this->calculateTax($company),
            'total' => $this->calculateTotal($company)
        ];
    }
}
```

## Testing

### Test Mode
```php
// Use test keys in development
if (app()->environment('local')) {
    config(['cashier.key' => env('STRIPE_TEST_KEY')]);
    config(['cashier.secret' => env('STRIPE_TEST_SECRET')]);
}
```

### Test Webhooks
```bash
# Stripe CLI for local webhook testing
stripe listen --forward-to localhost:8000/stripe/webhook

# Trigger test events
stripe trigger payment_intent.succeeded
```

## Error Handling

### Payment Failures
```php
try {
    $payment = $company->charge(10000, $paymentMethod);
} catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
    // Redirect to payment confirmation page
    return redirect()->route(
        'cashier.payment',
        [$e->payment->id, 'redirect' => route('billing')]
    );
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Payment failed', [
        'company_id' => $company->id,
        'error' => $e->getMessage()
    ]);
}
```

## Related Documentation
- [Subscription Features](../features/subscriptions.md)
- [Webhook Configuration](../api/webhooks.md)
- [Environment Configuration](../configuration/environment.md)