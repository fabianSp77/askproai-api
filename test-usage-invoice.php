<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\Stripe\EnhancedStripeInvoiceService;
use App\Models\Company;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set up company context for tenant scope
app()->instance('current_company_id', null);

try {
    // Get a company with pricing model
    $company = Company::find(85); // AskProAI has pricing model
    
    if (!$company) {
        echo "No company found in database.\n";
        exit(1);
    }
    
    echo "Testing usage-based invoice for: {$company->name}\n";
    echo "Company ID: {$company->id}\n\n";
    
    // Set company context for tenant scope
    app()->instance('current_company_id', $company->id);
    
    // Set date range (current month)
    $periodStart = Carbon::parse('2025-06-16');
    $periodEnd = Carbon::parse('2025-06-30');
    
    echo "Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n\n";
    
    // Get usage statistics
    $service = new EnhancedStripeInvoiceService();
    $stats = $service->getUsageStatistics($company, $periodStart, $periodEnd);
    
    echo "Usage Statistics:\n";
    echo json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($stats['has_pricing']) {
        echo "Creating usage-based invoice...\n";
        
        try {
            $invoice = $service->createUsageBasedInvoice($company, $periodStart, $periodEnd);
            
            echo "Invoice created successfully!\n";
            echo "Invoice ID: {$invoice->id}\n";
            echo "Invoice Number: {$invoice->invoice_number}\n";
            echo "Subtotal: €" . number_format($invoice->subtotal, 2) . "\n";
            echo "Tax: €" . number_format($invoice->tax_amount, 2) . "\n";
            echo "Total: €" . number_format($invoice->total, 2) . "\n\n";
            
            echo "Invoice Items:\n";
            foreach ($invoice->flexibleItems as $item) {
                echo "- {$item->description}: {$item->quantity} {$item->unit} × €{$item->unit_price} = €{$item->amount}\n";
            }
            
        } catch (\Exception $e) {
            echo "Error creating invoice: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
        }
    } else {
        echo "No pricing model found for this company.\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}