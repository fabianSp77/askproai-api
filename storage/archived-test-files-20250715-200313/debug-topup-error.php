<?php

// Debug script to test topup functionality
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\BalanceTopup;
use App\Services\StripeTopupService;
use App\Services\PrepaidBillingService;
use Illuminate\Support\Facades\Log;

echo "=== DEBUG TOPUP ERROR ===\n\n";

// 1. Check if services can be instantiated
echo "1. Checking service instantiation...\n";
try {
    $stripeService = app(StripeTopupService::class);
    echo "   ✅ StripeTopupService instantiated\n";
} catch (\Exception $e) {
    echo "   ❌ StripeTopupService error: " . $e->getMessage() . "\n";
}

try {
    $billingService = app(PrepaidBillingService::class);
    echo "   ✅ PrepaidBillingService instantiated\n";
} catch (\Exception $e) {
    echo "   ❌ PrepaidBillingService error: " . $e->getMessage() . "\n";
}

// 2. Test company and balance
echo "\n2. Testing company and balance...\n";
$company = Company::find(1);
if ($company) {
    echo "   ✅ Company found: " . $company->name . "\n";
    
    // Test bonus calculation
    try {
        $bonusCalc = $billingService->calculateBonus(100, $company);
        echo "   ✅ Bonus calculation works: " . json_encode($bonusCalc) . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Bonus calculation error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Company not found\n";
}

// 3. Test creating a BalanceTopup record
echo "\n3. Testing BalanceTopup creation...\n";
try {
    $topup = BalanceTopup::create([
        'company_id' => 1,
        'amount' => 100,
        'currency' => 'EUR',
        'status' => BalanceTopup::STATUS_PENDING,
        'initiated_by' => null,
        'metadata' => [
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test User',
            'source' => 'debug_test',
        ],
    ]);
    echo "   ✅ BalanceTopup created with ID: " . $topup->id . "\n";
    
    // Clean up
    $topup->delete();
    echo "   ✅ Test topup deleted\n";
} catch (\Exception $e) {
    echo "   ❌ BalanceTopup creation error: " . $e->getMessage() . "\n";
    echo "   Error class: " . get_class($e) . "\n";
    if (method_exists($e, 'getSql')) {
        echo "   SQL: " . $e->getSql() . "\n";
    }
}

// 4. Check recent error logs
echo "\n4. Checking recent error logs...\n";
$logFile = storage_path('logs/laravel.log');
$lines = array_slice(file($logFile), -50);
$errorFound = false;

foreach ($lines as $line) {
    if (stripos($line, 'topup') !== false && (stripos($line, 'error') !== false || stripos($line, 'exception') !== false)) {
        echo "   Found error: " . trim($line) . "\n";
        $errorFound = true;
    }
}

if (!$errorFound) {
    echo "   No recent topup errors in logs\n";
}

// 5. Test Stripe Checkout Session creation directly
echo "\n5. Testing Stripe Checkout Session creation...\n";
try {
    $stripeService = new StripeTopupService();
    
    // Create a minimal test topup
    $testTopup = BalanceTopup::create([
        'company_id' => 1,
        'amount' => 10,
        'currency' => 'EUR',
        'status' => BalanceTopup::STATUS_PENDING,
        'initiated_by' => null,
    ]);
    
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Debug Test',
                ],
                'unit_amount' => 1000,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://api.askproai.de/success',
        'cancel_url' => 'https://api.askproai.de/cancel',
        'client_reference_id' => $testTopup->id,
    ]);
    
    echo "   ✅ Stripe session created: " . $session->id . "\n";
    echo "   URL: " . $session->url . "\n";
    
    // Clean up
    $testTopup->delete();
    
} catch (\Exception $e) {
    echo "   ❌ Stripe error: " . $e->getMessage() . "\n";
    echo "   Error class: " . get_class($e) . "\n";
}

echo "\n=== END DEBUG ===\n";