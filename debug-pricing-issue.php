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

echo "=== DEBUGGING PRICING ISSUE ===\n";
echo "Company: {$company->name} (ID: {$company->id})\n\n";

// Set company context
app()->instance('current_company_id', $company->id);

// Check ALL pricing models
echo "=== ALL PRICING MODELS (without scope) ===\n";
$allPricings = CompanyPricing::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->orderBy('valid_from', 'desc')
    ->get();

foreach ($allPricings as $pricing) {
    echo "\nPricing ID: {$pricing->id}\n";
    echo "  Active: " . ($pricing->is_active ? 'YES' : 'NO') . "\n";
    echo "  Valid from: {$pricing->valid_from}\n";
    echo "  Valid until: " . ($pricing->valid_until ?? 'OPEN END') . "\n";
    echo "  Base fee: €{$pricing->monthly_base_fee}\n";
    echo "  Included minutes: {$pricing->included_minutes}\n";
    echo "  Price/minute: €{$pricing->price_per_minute}\n";
    echo "  Overage price: €" . ($pricing->overage_price_per_minute ?? 'same as regular') . "\n";
}

// Test different date ranges
$testRanges = [
    ['2025-03-01', '2025-06-26', '1. März - 26. Juni (Ihre Anfrage)'],
    ['2025-06-01', '2025-06-30', 'Juni 2025'],
    ['2025-06-16', '2025-06-26', '16. Juni - 26. Juni'],
];

foreach ($testRanges as $range) {
    $start = Carbon::parse($range[0]);
    $end = Carbon::parse($range[1]);
    
    echo "\n\n=== TESTING PERIOD: {$range[2]} ===\n";
    echo "From: {$start->format('Y-m-d')} To: {$end->format('Y-m-d')}\n";
    
    // Check pricing for this period
    $pricing = CompanyPricing::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->where('valid_from', '<=', $start)
        ->where(function ($q) use ($end) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>=', $end);
        })
        ->orderBy('valid_from', 'desc')
        ->first();
    
    if ($pricing) {
        echo "✅ Found active pricing: ID {$pricing->id} (valid from {$pricing->valid_from})\n";
    } else {
        echo "❌ NO active pricing found!\n";
        
        // Check why
        $anyActive = CompanyPricing::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->count();
        echo "   Total active pricings: {$anyActive}\n";
        
        $validInRange = CompanyPricing::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('valid_from', '<=', $start)
            ->count();
        echo "   Pricings starting before period: {$validInRange}\n";
    }
    
    // Check calls
    $calls = Call::where('company_id', $company->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
    
    $callsWithDuration = Call::where('company_id', $company->id)
        ->whereBetween('created_at', [$start, $end])
        ->where(function($q) {
            $q->where('duration_sec', '>', 0)
              ->orWhere('duration_minutes', '>', 0);
        })
        ->count();
    
    echo "Calls in period: {$calls} (with duration: {$callsWithDuration})\n";
}

// Check the exact query the service uses
echo "\n\n=== EXACT SERVICE QUERY TEST ===\n";
$periodStart = Carbon::parse('2025-03-01');
$periodEnd = Carbon::parse('2025-06-26');

$query = CompanyPricing::where('company_id', $company->id)
    ->where('is_active', true)
    ->where('valid_from', '<=', $periodStart)
    ->where(function ($q) use ($periodEnd) {
        $q->whereNull('valid_until')
          ->orWhere('valid_until', '>=', $periodEnd);
    });

echo "SQL Query: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";

$result = $query->first();
echo "Result: " . ($result ? "Found ID {$result->id}" : "NULL") . "\n";