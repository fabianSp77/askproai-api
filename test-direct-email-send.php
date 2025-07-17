<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "Direct Email Send Test\n";
echo "=====================\n\n";

// Get call 229 specifically
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find(229);

if (!$call) {
    echo "Call 229 not found, using first available call\n";
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
}

if (!$call) {
    echo "No calls found\n";
    exit;
}

// Set company context
if ($call->company_id) {
    app()->instance('current_company_id', $call->company_id);
}

// Load relationships
$call->load(['company', 'customer', 'branch', 'charge']);

echo "Using Call ID: {$call->id}\n";
echo "From: " . $call->from_number . "\n";
echo "Customer: " . ($call->extracted_name ?? 'Unknown') . "\n\n";

try {
    $email = new CustomCallSummaryEmail(
        $call,
        'Test: Anrufzusammenfassung mit allen Fixes',
        '<p>Diese E-Mail testet alle Verbesserungen:</p>
        <ul>
            <li>✅ Korrekter Portal-Link (api.askproai.de)</li>
            <li>✅ Modernes E-Mail-Design</li>
            <li>✅ Transcript korrekt formatiert</li>
            <li>✅ CSV ohne BOM</li>
        </ul>',
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
    
    echo "Sending email to: fabian@askproai.de\n";
    Mail::to('fabian@askproai.de')->send($email);
    
    echo "\n✅ EMAIL SENT SUCCESSFULLY!\n";
    echo "\nPlease check your inbox for:\n";
    echo "- Modern email design\n";
    echo "- Correct portal link: https://api.askproai.de/business/calls/{$call->id}/v2\n";
    echo "- Properly formatted transcript\n";
    echo "- CSV attachment without BOM\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}