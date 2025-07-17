<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\CallExportService;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "Fixing Email Transcript and CSV Issues...\n\n";

// Get a test call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('transcript')
    ->first();

if (!$call) {
    echo "No calls with transcript found\n";
    exit;
}

// Set company context
if ($call->company_id) {
    app()->instance('current_company_id', $call->company_id);
}

// Load relationships
$call->load(['company', 'customer', 'branch', 'charge']);

echo "Testing with Call ID: {$call->id}\n";
echo "Call has transcript: " . (strlen($call->transcript) > 0 ? 'Yes' : 'No') . "\n";
echo "Transcript length: " . strlen($call->transcript) . " characters\n\n";

// Test CSV Export
echo "Testing CSV Export...\n";
$exportService = app(CallExportService::class);

try {
    $csvContent = $exportService->exportSingleCall($call);
    echo "✅ CSV Export successful\n";
    echo "CSV Size: " . strlen($csvContent) . " bytes\n";
    
    // Save CSV for inspection
    file_put_contents('/tmp/test-call-export.csv', $csvContent);
    echo "CSV saved to: /tmp/test-call-export.csv\n\n";
    
    // Check CSV content
    $lines = explode("\n", $csvContent);
    echo "CSV has " . count($lines) . " lines\n";
    echo "First line (headers): " . substr($lines[0], 0, 100) . "...\n\n";
    
} catch (\Exception $e) {
    echo "❌ CSV Export failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Test Email Generation
echo "Testing Email Generation...\n";
try {
    $email = new CustomCallSummaryEmail(
        $call,
        'Test Anrufzusammenfassung',
        '<p>Dies ist ein Test der E-Mail-Funktion.</p>',
        [
            'summary' => true,
            'customerInfo' => true,
            'appointmentInfo' => true,
            'actionItems' => true,
            'transcript' => true,
            'attachCSV' => true,
            'attachRecording' => false
        ]
    );
    
    // Build email content
    $content = $email->content();
    echo "✅ Email content built successfully\n";
    
    // Test attachments
    $attachments = $email->attachments();
    echo "✅ Email has " . count($attachments) . " attachments\n";
    
    // Render the email view to check transcript formatting
    $view = view($content->view, $content->with)->render();
    file_put_contents('/tmp/test-email.html', $view);
    echo "Email HTML saved to: /tmp/test-email.html\n\n";
    
    // Check if transcript is properly formatted
    if (strpos($view, 'Transkript') !== false) {
        echo "✅ Transcript section found in email\n";
    } else {
        echo "❌ Transcript section missing from email\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Email generation failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

// Check transcript format
echo "\nAnalyzing Transcript Format...\n";
if ($call->transcript) {
    echo "Raw transcript (first 200 chars): " . substr($call->transcript, 0, 200) . "...\n\n";
    
    // Check line endings
    $hasWindowsLineEndings = strpos($call->transcript, "\r\n") !== false;
    $hasUnixLineEndings = strpos($call->transcript, "\n") !== false;
    
    echo "Windows line endings (\\r\\n): " . ($hasWindowsLineEndings ? 'Yes' : 'No') . "\n";
    echo "Unix line endings (\\n): " . ($hasUnixLineEndings ? 'Yes' : 'No') . "\n";
    
    // Split by different line endings
    $lines = preg_split('/\r\n|\r|\n/', $call->transcript);
    echo "Transcript has " . count($lines) . " lines\n";
    echo "First 3 lines:\n";
    for ($i = 0; $i < min(3, count($lines)); $i++) {
        echo "  Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
    }
}

echo "\nDone!\n";