<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Invoice;
use App\Models\Company;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get the most recent invoice
$invoice = Invoice::with(['company', 'flexibleItems'])->latest()->first();

if (!$invoice) {
    echo "No invoices found.\n";
    exit;
}

echo "Invoice: {$invoice->invoice_number}\n";
echo "Company: {$invoice->company->name}\n";
echo "Date: {$invoice->invoice_date->format('d.m.Y')}\n";
echo "Total: €" . number_format($invoice->total, 2, ',', '.') . "\n";
echo "\n";

echo "Flexible Items:\n";
echo str_repeat("-", 80) . "\n";

foreach ($invoice->flexibleItems as $item) {
    echo "Description: {$item->description}\n";
    echo "  Type: {$item->type}\n";
    echo "  Quantity: {$item->quantity} {$item->unit}\n";
    echo "  Unit Price: €" . number_format($item->unit_price, 2, ',', '.') . "\n";
    echo "  Amount: €" . number_format($item->amount, 2, ',', '.') . "\n";
    if ($item->period_start && $item->period_end) {
        echo "  Period: {$item->period_start->format('d.m.Y')} - {$item->period_end->format('d.m.Y')}\n";
    }
    echo "\n";
}

// Note: Regular items table doesn't exist, we only use flexible items