<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use Stripe\Stripe;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Stripe Debug Test ===\n\n";

try {
    // Test Stripe configuration
    $stripeSecret = config('services.stripe.secret');
    echo "Stripe Secret Key: " . (empty($stripeSecret) ? "❌ NICHT GESETZT" : "✅ " . substr($stripeSecret, 0, 10) . "...") . "\n";
    
    // Initialize Stripe
    Stripe::setApiKey($stripeSecret);
    
    // Test API connection
    echo "\nTeste Stripe API Verbindung...\n";
    $stripe = new \Stripe\StripeClient($stripeSecret);
    
    // Try to list products (simple test)
    $products = $stripe->products->all(['limit' => 1]);
    echo "✅ Stripe API Verbindung erfolgreich!\n";
    
    // Test Payment Link creation with minimal params
    echo "\nTeste Payment Link Erstellung...\n";
    
    try {
        $paymentLink = $stripe->paymentLinks->create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Test Guthaben-Aufladung',
                    ],
                    'unit_amount' => 10000, // €100
                ],
                'quantity' => 1,
            ]],
        ]);
        
        echo "✅ Payment Link erstellt!\n";
        echo "   URL: " . $paymentLink->url . "\n";
        echo "   ID: " . $paymentLink->id . "\n";
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "❌ Stripe API Error: " . $e->getMessage() . "\n";
        echo "   Error Code: " . $e->getStripeCode() . "\n";
        echo "   HTTP Status: " . $e->getHttpStatus() . "\n";
        
        if ($e->getStripeCode() === 'resource_missing') {
            echo "\n⚠️  Payment Links API möglicherweise nicht aktiviert.\n";
            echo "   Bitte prüfen Sie im Stripe Dashboard:\n";
            echo "   1. Gehen Sie zu https://dashboard.stripe.com/test/payment-links\n";
            echo "   2. Aktivieren Sie Payment Links falls erforderlich\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
}