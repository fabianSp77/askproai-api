<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\CallExportService;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

// Test the CallExportService
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->first();
if (!$call) {
    echo "No calls found\n";
    exit;
}

// Set company context
if ($call->company_id) {
    app()->instance('current_company_id', $call->company_id);
}

// Now load relationships with context set
$call->load(['company', 'customer', 'branch']);

echo "Testing CallExportService...\n";
$exportService = app(CallExportService::class);

try {
    // Test the correct method name
    $csvContent = $exportService->exportSingleCall($call);
    echo "✅ CSV export successful\n";
    echo "CSV length: " . strlen($csvContent) . " bytes\n";
} catch (\Exception $e) {
    echo "❌ CSV export failed: " . $e->getMessage() . "\n";
}

// Test the email
echo "\nTesting CustomCallSummaryEmail...\n";
try {
    $email = new CustomCallSummaryEmail(
        $call,
        'Test Subject',
        '<p>Test HTML content</p>',
        [
            'summary' => true,
            'customerInfo' => true,
            'appointmentInfo' => true,
            'actionItems' => true,
            'transcript' => false,
            'attachCSV' => true,
            'attachRecording' => false
        ]
    );
    
    echo "✅ Email object created successfully\n";
    
    // Test building the email
    $content = $email->content();
    echo "✅ Email content built successfully\n";
    
    // Test attachments
    $attachments = $email->attachments();
    echo "✅ Attachments: " . count($attachments) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Email test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}