<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Boot the app
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test data
    $data = [
        'balance' => 150.00,
        'currency' => 'EUR',
        'auto_topup_enabled' => true,
        'auto_topup_threshold' => 50,
        'auto_topup_amount' => 100,
    ];
    
    $transactions = collect([
        (object)[
            'id' => 1,
            'type' => 'topup',
            'amount' => 100,
            'description' => 'Guthaben Aufladung',
            'created_at' => now()->subDays(5),
            'status' => 'completed',
            'invoice_url' => null
        ],
        (object)[
            'id' => 2,
            'type' => 'usage',
            'amount' => -15.50,
            'description' => 'Anrufgebühren',
            'created_at' => now()->subDays(3),
            'status' => 'completed',
            'invoice_url' => null
        ]
    ]);
    
    // Try to render the view
    $view = view('portal.billing.simple-index', compact('data', 'transactions'));
    $content = $view->render();
    
    echo "✅ View rendered successfully!\n";
    echo "Content length: " . strlen($content) . " bytes\n";
    
} catch (\Exception $e) {
    echo "❌ Error rendering view:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}