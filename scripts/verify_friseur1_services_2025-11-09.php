<?php
/**
 * Verify services for Friseur 1 (company_id = 1, NOT UUID)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;

$companyId = 1; // Correct company ID (integer, not UUID)
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';

echo "=== FRISEUR 1 SERVICES VERIFICATION ===\n\n";

// 1. Verify company
$company = Company::find($companyId);
echo "Company: {$company->name}\n";
echo "  ID: {$company->id}\n";
echo "  Cal.com Team ID: {$company->calcom_team_id}\n\n";

// 2. Verify branch
$branch = Branch::find($branchId);
echo "Branch: {$branch->name}\n";
echo "  ID: {$branch->id}\n";
echo "  Company ID: {$branch->company_id}\n\n";

// 3. Count services
$servicesTotal = Service::where('company_id', $companyId)->count();
$servicesActive = Service::where('company_id', $companyId)->where('is_active', true)->count();

echo "Services for Friseur 1:\n";
echo "  Total: {$servicesTotal}\n";
echo "  Active: {$servicesActive}\n\n";

// 4. Check for Herrenhaarschnitt
$herrenhaarschnitt = Service::where('company_id', $companyId)
    ->where('name', 'LIKE', '%Herrenhaarschnitt%')
    ->where('is_active', true)
    ->first();

if ($herrenhaarschnitt) {
    echo "✅ Herrenhaarschnitt found:\n";
    echo "   ID: {$herrenhaarschnitt->id}\n";
    echo "   Name: {$herrenhaarschnitt->name}\n";
    echo "   Duration: {$herrenhaarschnitt->duration_minutes} min\n";
    echo "   Price: €{$herrenhaarschnitt->price}\n";
    echo "   Cal.com Event Type ID: {$herrenhaarschnitt->calcom_event_type_id}\n";
    echo "   Active: YES\n\n";
} else {
    echo "❌ Herrenhaarschnitt NOT found or inactive\n";

    // Check if it exists but is inactive
    $inactive = Service::where('company_id', $companyId)
        ->where('name', 'LIKE', '%Herrenhaarschnitt%')
        ->first();

    if ($inactive) {
        echo "   Found but INACTIVE:\n";
        echo "   Name: {$inactive->name}\n";
        echo "   Active: " . ($inactive->is_active ? 'YES' : 'NO') . "\n";
        echo "   Sync Status: {$inactive->sync_status}\n\n";
    } else {
        echo "   Not found at all\n\n";
    }
}

// 5. List all active services
echo "All active services:\n";
$activeServices = Service::where('company_id', $companyId)
    ->where('is_active', true)
    ->orderBy('name')
    ->get();

foreach ($activeServices as $service) {
    echo "  - {$service->name} ({$service->duration_minutes} min, €{$service->price})\n";
}

if ($activeServices->count() === 0) {
    echo "  None\n\n";

    // Check why services are inactive
    echo "Checking inactive services:\n";
    $inactiveServices = Service::where('company_id', $companyId)
        ->where('is_active', false)
        ->take(10)
        ->get();

    foreach ($inactiveServices as $service) {
        echo "  - {$service->name} - Sync Status: {$service->sync_status}\n";
    }
}

echo "\n=== CORRECT COMPANY ID TO USE ===\n";
echo "Company ID: 1 (integer, NOT UUID)\n";
echo "Branch ID: 34c4d48e-4753-4715-9c30-c55843a943e8 (UUID)\n";
