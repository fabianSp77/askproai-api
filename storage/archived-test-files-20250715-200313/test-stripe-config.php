<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

echo "=== STRIPE CONFIGURATION TEST ===\n\n";

// Check environment
$stripeKey = config('services.stripe.publishable');
$stripeSecret = config('services.stripe.secret');
$webhookSecret = config('services.stripe.webhook_secret');

echo "1. Environment Configuration:\n";
echo "   - Public Key: " . ($stripeKey ? substr($stripeKey, 0, 20) . "..." : "NOT SET") . "\n";
echo "   - Secret Key: " . ($stripeSecret ? substr($stripeSecret, 0, 20) . "..." : "NOT SET") . "\n";
echo "   - Webhook Secret: " . ($webhookSecret ? "SET" : "NOT SET") . "\n";
echo "   - Mode: " . (strpos($stripeKey, 'pk_test_') === 0 ? "TEST" : "LIVE") . "\n\n";

if (!$stripeSecret) {
    echo "ERROR: Stripe Secret Key is not set!\n";
    exit(1);
}

// Test API connection
echo "2. Testing Stripe API Connection...\n";
try {
    Stripe::setApiKey($stripeSecret);
    
    // Try to create a simple test session
    $session = CheckoutSession::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Test Product',
                ],
                'unit_amount' => 1000, // 10€
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://api.askproai.de/success',
        'cancel_url' => 'https://api.askproai.de/cancel',
    ]);
    
    echo "   ✅ SUCCESS: Stripe API is working!\n";
    echo "   - Session ID: " . $session->id . "\n";
    echo "   - Checkout URL: " . $session->url . "\n\n";
    
    echo "3. Test Checkout URL (valid for 24 hours):\n";
    echo "   " . $session->url . "\n\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "   Full error details:\n";
    echo "   " . get_class($e) . "\n";
    if ($e instanceof \Stripe\Exception\ApiErrorException) {
        echo "   HTTP Status: " . $e->getHttpStatus() . "\n";
        echo "   Request ID: " . $e->getRequestId() . "\n";
        echo "   Stripe Error Code: " . $e->getStripeCode() . "\n";
    }
}

echo "\n=== END TEST ===\n";