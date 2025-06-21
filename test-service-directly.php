<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\Stripe\EnhancedStripeInvoiceService;
use App\Models\Company;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get AskProAI company
$company = Company::where('name', 'like', '%AskProAI%')->first();
echo "Company: {$company->name} (ID: {$company->id})\n\n";

// Set company context
app()->instance('current_company_id', $company->id);

// Test periods
$periodStart = Carbon::parse('2025-03-01');
$periodEnd = Carbon::parse('2025-06-26');

echo "Testing period: {$periodStart->format('d.m.Y')} - {$periodEnd->format('d.m.Y')}\n\n";

// Create service
$service = new EnhancedStripeInvoiceService();

// Get statistics
echo "=== GETTING USAGE STATISTICS ===\n";
try {
    $stats = $service->getUsageStatistics($company, $periodStart, $periodEnd);
    echo json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($stats['has_pricing']) {
        echo "✅ Pricing found!\n";
        echo "Base fee: €" . $stats['pricing']['monthly_base_fee'] . "/month\n";
        echo "Included minutes: " . $stats['pricing']['included_minutes'] . "\n";
        echo "Price per minute: €" . $stats['pricing']['price_per_minute'] . "\n";
        echo "\n";
        echo "Total calls: " . $stats['usage']['total_calls'] . "\n";
        echo "Total minutes: " . $stats['usage']['total_minutes'] . "\n";
        echo "Billable minutes: " . $stats['usage']['billable_minutes'] . "\n";
    } else {
        echo "❌ No pricing found!\n";
        if (isset($stats['error'])) {
            echo "Error: " . $stats['error'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Try different period
echo "\n\n=== TESTING JUNE ONLY ===\n";
$juneStart = Carbon::parse('2025-06-01');
$juneEnd = Carbon::parse('2025-06-30');

try {
    $juneStats = $service->getUsageStatistics($company, $juneStart, $juneEnd);
    echo "June stats:\n";
    echo "Has pricing: " . ($juneStats['has_pricing'] ? 'YES' : 'NO') . "\n";
    if ($juneStats['has_pricing']) {
        echo "Total minutes: " . $juneStats['usage']['total_minutes'] . "\n";
    }
} catch (\Exception $e) {
    echo "June ERROR: " . $e->getMessage() . "\n";
}