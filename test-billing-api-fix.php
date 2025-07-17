<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\PortalUser;
use App\Models\User;
use App\Http\Controllers\Portal\Api\BillingApiController;
use Illuminate\Http\Request;

// Test scenario 1: Regular portal user
echo "Test 1: Portal User accessing topup\n";
echo "=================================\n";

$company = Company::first();
$portalUser = PortalUser::where('company_id', $company->id)->where('is_active', true)->first();

if ($portalUser) {
    // Simulate portal user auth
    auth()->guard('portal')->setUser($portalUser);
    
    $controller = app(BillingApiController::class);
    $request = Request::create('/api/business/billing/topup', 'POST', [
        'amount' => 100
    ]);
    
    try {
        $response = $controller->topup($request);
        $data = json_decode($response->getContent(), true);
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Type: " . get_class($e) . "\n";
    }
}

// Test scenario 2: Admin viewing as company
echo "\n\nTest 2: Admin viewing company's billing\n";
echo "========================================\n";

$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    // Clear portal auth
    auth()->guard('portal')->logout();
    
    // Set admin auth
    auth()->guard('web')->setUser($admin);
    
    // Set admin viewing session
    session(['is_admin_viewing' => true]);
    session(['admin_impersonation.company_id' => $company->id]);
    
    $controller = app(BillingApiController::class);
    $request = Request::create('/api/business/billing/topup', 'POST', [
        'amount' => 100
    ]);
    
    try {
        $response = $controller->topup($request);
        $data = json_decode($response->getContent(), true);
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "\nTest completed.\n";
