<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Models\CompanyPricing;
use App\Models\Call;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find AskProAI company
$company = Company::where('name', 'like', '%AskProAI%')->first();
if (!$company) {
    echo "AskProAI company not found.\n";
    exit(1);
}

echo "Company: {$company->name} (ID: {$company->id})\n\n";

// Set company context
app()->instance('current_company_id', $company->id);

// Check date range
$periodStart = Carbon::parse('2025-03-01');
$periodEnd = Carbon::parse('2025-06-26');

echo "Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n\n";

// Check pricing models
echo "=== PRICING MODELS ===\n";
$pricings = CompanyPricing::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->orderBy('valid_from')
    ->get();

foreach ($pricings as $pricing) {
    echo "Pricing ID: {$pricing->id}\n";
    echo "  Active: " . ($pricing->is_active ? 'Yes' : 'No') . "\n";
    echo "  Valid from: {$pricing->valid_from}\n";
    echo "  Valid until: " . ($pricing->valid_until ?? 'NULL') . "\n";
    echo "  Monthly base fee: €{$pricing->monthly_base_fee}\n";
    echo "  Included minutes: {$pricing->included_minutes}\n";
    echo "  Price per minute: €{$pricing->price_per_minute}\n";
    echo "  Overage price: €" . ($pricing->overage_price_per_minute ?? 'NULL') . "\n\n";
}

// Check active pricing for period
echo "=== ACTIVE PRICING FOR PERIOD ===\n";
$activePricing = CompanyPricing::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('is_active', true)
    ->where('valid_from', '<=', $periodStart)
    ->where(function ($q) use ($periodEnd) {
        $q->whereNull('valid_until')
          ->orWhere('valid_until', '>=', $periodEnd);
    })
    ->first();

if ($activePricing) {
    echo "Found active pricing: ID {$activePricing->id}\n";
} else {
    echo "NO ACTIVE PRICING FOUND for the period!\n";
}

// Check calls
echo "\n=== CALLS IN PERIOD ===\n";
$calls = Call::where('company_id', $company->id)
    ->whereBetween('created_at', [$periodStart, $periodEnd])
    ->orderBy('created_at')
    ->get();

echo "Total calls found: " . $calls->count() . "\n";

// Group by month
$callsByMonth = $calls->groupBy(function ($call) {
    return $call->created_at->format('Y-m');
});

foreach ($callsByMonth as $month => $monthCalls) {
    $totalMinutes = $monthCalls->sum(function ($call) {
        if ($call->duration_minutes) {
            return $call->duration_minutes;
        } elseif ($call->duration_sec) {
            return $call->duration_sec / 60;
        }
        return 0;
    });
    
    echo "\n$month:\n";
    echo "  Calls: " . $monthCalls->count() . "\n";
    echo "  Total minutes: " . round($totalMinutes, 2) . "\n";
    echo "  Successful calls: " . $monthCalls->where('call_successful', true)->count() . "\n";
}

// Check specific fields
echo "\n=== SAMPLE CALL DATA ===\n";
$sampleCall = $calls->first();
if ($sampleCall) {
    echo "Sample call ID: {$sampleCall->id}\n";
    echo "  Created at: {$sampleCall->created_at}\n";
    echo "  Duration sec: " . ($sampleCall->duration_sec ?? 'NULL') . "\n";
    echo "  Duration minutes: " . ($sampleCall->duration_minutes ?? 'NULL') . "\n";
    echo "  Call successful: " . ($sampleCall->call_successful ? 'Yes' : 'No') . "\n";
    echo "  Company ID: {$sampleCall->company_id}\n";
}

// Test the service
echo "\n=== TESTING SERVICE ===\n";
$service = new App\Services\Stripe\EnhancedStripeInvoiceService();
$stats = $service->getUsageStatistics($company, $periodStart, $periodEnd);
echo "Service result:\n";
echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";