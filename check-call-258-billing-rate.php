<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\BillingRate;

// Get Call 258 without tenant scope
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->with(['company.billingRate'])
    ->find(258);

if (!$call) {
    echo "Call 258 not found!\n";
    exit(1);
}

echo "Call 258 Analysis:\n";
echo "=================\n";
echo "Call ID: " . $call->id . "\n";
echo "Duration: " . $call->duration_sec . " seconds (" . round($call->duration_sec / 60, 2) . " minutes)\n";
echo "Company ID: " . ($call->company_id ?? 'NULL') . "\n";
echo "Company Name: " . ($call->company?->name ?? 'NO COMPANY') . "\n\n";

if ($call->company) {
    echo "Company Billing Rate:\n";
    echo "-------------------\n";
    if ($call->company->billingRate) {
        $billingRate = $call->company->billingRate;
        echo "Rate per minute: " . $billingRate->rate_per_minute . "€\n";
        echo "Billing increment: " . $billingRate->billing_increment . " seconds\n";
        echo "Minimum charge: " . $billingRate->minimum_charge . "€\n";
        echo "Is active: " . ($billingRate->is_active ? 'Yes' : 'No') . "\n\n";
        
        // Calculate revenue
        $revenue = $billingRate->calculateCharge($call->duration_sec);
        echo "Calculated revenue: " . number_format($revenue, 2) . "€\n";
        echo "Calculation: " . $call->duration_sec . " sec × " . $billingRate->rate_per_minute . "€/min = " . number_format($revenue, 2) . "€\n";
    } else {
        echo "NO BILLING RATE FOUND - Need to create one!\n\n";
        
        // Check if we should create a default billing rate
        echo "Creating default billing rate for company...\n";
        $newRate = BillingRate::createDefaultForCompany($call->company);
        echo "Created billing rate:\n";
        echo "- Rate per minute: " . $newRate->rate_per_minute . "€\n";
        echo "- Billing increment: " . $newRate->billing_increment . " seconds\n";
        
        $revenue = $newRate->calculateCharge($call->duration_sec);
        echo "\nCalculated revenue with new rate: " . number_format($revenue, 2) . "€\n";
    }
} else {
    echo "ERROR: Call has no company assigned!\n";
    echo "Cannot calculate billing without company.\n";
}

// Check webhook data for costs
echo "\nRetell Cost Data:\n";
echo "----------------\n";
if (isset($call->webhook_data['call_cost'])) {
    $callCost = $call->webhook_data['call_cost'];
    $costCents = $callCost['combined_cost'] ?? 0;
    $costUSD = $costCents / 100;
    echo "Combined cost: " . $costCents . " cents = $" . number_format($costUSD, 4) . "\n";
    
    // Assuming 0.92 EUR/USD rate
    $costEUR = $costUSD * 0.92;
    echo "Cost in EUR (0.92 rate): " . number_format($costEUR, 4) . "€\n";
} else {
    echo "No cost data found in webhook_data\n";
}