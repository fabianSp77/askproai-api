<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Models\PortalUser;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Services\PrepaidBillingService;
use Illuminate\Support\Facades\DB;

echo "=== Business Billing Debug ===\n\n";

// Check current session
$sessionId = session()->getId();
echo "Current Session ID: " . $sessionId . "\n";

// Check if portal user is authenticated
$portalUser = Auth::guard('portal')->user();
if ($portalUser) {
    echo "Portal User: " . $portalUser->email . " (ID: " . $portalUser->id . ")\n";
    echo "Company: " . $portalUser->company->name . " (ID: " . $portalUser->company_id . ")\n";
} else {
    echo "No portal user authenticated\n";
}

// Check if admin is viewing
$isAdminViewing = session('is_admin_viewing');
echo "Is Admin Viewing: " . ($isAdminViewing ? 'Yes' : 'No') . "\n";

if ($isAdminViewing) {
    $companyId = session('admin_impersonation.company_id');
    echo "Admin viewing company ID: " . $companyId . "\n";
}

// Check demo user
$demoUser = PortalUser::where('email', 'demo@example.com')->first();
if ($demoUser) {
    echo "\nDemo User Found:\n";
    echo "- Email: " . $demoUser->email . "\n";
    echo "- Company: " . $demoUser->company->name . "\n";
    echo "- Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
    
    // Check prepaid balance
    $balance = PrepaidBalance::where('company_id', $demoUser->company_id)->first();
    if ($balance) {
        echo "\nPrepaid Balance:\n";
        echo "- Current Balance: €" . number_format($balance->current_balance, 2) . "\n";
        echo "- Bonus Balance: €" . number_format($balance->bonus_balance, 2) . "\n";
    } else {
        echo "\nNo prepaid balance found for company\n";
    }
}

// Test API endpoint
echo "\n=== Testing API Endpoint ===\n";
$billingService = app(PrepaidBillingService::class);
$company = Company::find($demoUser->company_id ?? 1);

if ($company) {
    $prepaidBalance = $billingService->getOrCreateBalance($company);
    echo "Balance Retrieved: €" . number_format($prepaidBalance->current_balance, 2) . "\n";
    
    // Check billing rate
    $billingRate = $billingService->getCompanyBillingRate($company);
    echo "Billing Rate: €" . $billingRate->rate_per_minute . "/min\n";
}

echo "\n=== React Build Status ===\n";
$buildManifest = public_path('build/manifest.json');
if (file_exists($buildManifest)) {
    $manifest = json_decode(file_get_contents($buildManifest), true);
    if (isset($manifest['resources/js/Pages/Portal/Billing/IndexRefactored.jsx'])) {
        echo "IndexRefactored.jsx is in build manifest ✓\n";
    } else {
        echo "IndexRefactored.jsx NOT in build manifest ✗\n";
    }
} else {
    echo "Build manifest not found\n";
}

echo "\nDone.\n";