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

if (!$company) {
    echo "AskProAI company not found.\n";
    exit(1);
}

echo "Testing usage-based invoice for: {$company->name}\n";
echo "Company ID: {$company->id}\n\n";

// Set company context for tenant scope
app()->instance('current_company_id', $company->id);

// Set date range as requested
$periodStart = Carbon::parse('2025-03-01');
$periodEnd = Carbon::parse('2025-06-26');

echo "Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n\n";

// Get usage statistics
$service = new EnhancedStripeInvoiceService();
$stats = $service->getUsageStatistics($company, $periodStart, $periodEnd);

echo "Usage Statistics:\n";
echo json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";

if ($stats['has_pricing']) {
    echo "Creating usage-based invoice...\n";
    
    try {
        $invoice = $service->createUsageBasedInvoice($company, $periodStart, $periodEnd, [
            'skip_setup_fee' => false, // Include setup fee
        ]);
        
        echo "\n✅ Invoice created successfully!\n";
        echo "=====================================\n";
        echo "Invoice ID: {$invoice->id}\n";
        echo "Invoice Number: {$invoice->invoice_number}\n";
        echo "Period: {$periodStart->format('d.m.Y')} - {$periodEnd->format('d.m.Y')}\n";
        echo "-------------------------------------\n";
        echo "Subtotal: €" . number_format($invoice->subtotal, 2, ',', '.') . "\n";
        echo "Tax (19%): €" . number_format($invoice->tax_amount, 2, ',', '.') . "\n";
        echo "TOTAL: €" . number_format($invoice->total, 2, ',', '.') . "\n";
        echo "=====================================\n\n";
        
        echo "Invoice Items:\n";
        foreach ($invoice->flexibleItems as $item) {
            echo sprintf(
                "- %s\n  %s %s × €%s = €%s\n\n",
                $item->description,
                number_format($item->quantity, 2, ',', '.'),
                $item->unit,
                number_format($item->unit_price, 2, ',', '.'),
                number_format($item->amount, 2, ',', '.')
            );
        }
        
    } catch (\Exception $e) {
        echo "❌ Error creating invoice: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ No pricing model found for this period.\n";
    if (isset($stats['error'])) {
        echo "Error: " . $stats['error'] . "\n";
    }
}