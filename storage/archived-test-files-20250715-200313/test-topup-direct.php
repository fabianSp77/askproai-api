<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\PublicTopupController;
use Illuminate\Http\Request;
use App\Models\Company;

echo "=== DIRECT TOPUP TEST ===\n\n";

// Simulate a request
$controller = new PublicTopupController(
    app(\App\Services\StripeTopupService::class),
    app(\App\Services\PrepaidBillingService::class)
);

$company = Company::find(1);
echo "Testing with company: " . $company->name . "\n\n";

// Create a test request
$request = Request::create('/topup/1', 'POST', [
    'amount' => 100,
    'email' => 'test@example.com',
    'name' => 'Test User',
]);

// Add CSRF token
$request->setLaravelSession(app('session.store'));
$token = csrf_token();
$request->merge(['_token' => $token]);

echo "Request data:\n";
print_r($request->all());
echo "\n";

try {
    // Process the topup
    echo "Processing topup...\n";
    $response = $controller->processTopup($request, 1);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        $targetUrl = $response->getTargetUrl();
        echo "Response: Redirect to " . $targetUrl . "\n";
        
        if ($response->getSession()) {
            $errors = $response->getSession()->get('errors');
            if ($errors) {
                echo "Errors: " . print_r($errors, true) . "\n";
            }
            
            $error = $response->getSession()->get('error');
            if ($error) {
                echo "Error message: " . $error . "\n";
            }
        }
    } else {
        echo "Response type: " . get_class($response) . "\n";
    }
    
} catch (\Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
    echo "Exception class: " . get_class($e) . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check recent logs
echo "\n=== RECENT LOG ENTRIES ===\n";
$logFile = storage_path('logs/laravel.log');
$lines = array_slice(file($logFile), -20);
foreach ($lines as $line) {
    if (stripos($line, 'topup') !== false || stripos($line, 'stripe') !== false) {
        echo trim($line) . "\n";
    }
}