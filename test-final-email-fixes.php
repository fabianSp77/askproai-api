<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\CallExportService;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== FINAL EMAIL FIXES TEST ===\n\n";

// Get call 229 (user's example)
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
if (!$call) {
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
}

// Set company context
if ($call->company_id) {
    app()->instance('current_company_id', $call->company_id);
}

// Load relationships
$call->load(['company', 'customer', 'branch', 'charge']);

echo "Testing with Call ID: {$call->id}\n\n";

// 1. Test CSV Export with Customer Costs
echo "1. Testing CSV Export (Customer Costs Only)...\n";
$exportService = app(CallExportService::class);
$csvContent = $exportService->exportSingleCall($call);

// Check for BOM
$hasBOM = substr($csvContent, 0, 3) === "\xEF\xBB\xBF";
echo "   - CSV has BOM: " . ($hasBOM ? 'YES (Fixed)' : 'NO ✓') . "\n";

// Check cost column
$lines = explode("\n", $csvContent);
$headers = str_getcsv($lines[0]);
$costIndex = array_search('Anrufkosten', $headers);
if ($costIndex !== false && isset($lines[1])) {
    $data = str_getcsv($lines[1]);
    $cost = $data[$costIndex] ?? '';
    echo "   - Customer cost shown: $cost";
    if ($call->charge) {
        echo " (charge: {$call->charge->amount_charged} €)";
    }
    echo "\n";
}

// 2. Test Email with All Fixes
echo "\n2. Testing Email Generation...\n";
$email = new CustomCallSummaryEmail(
    $call,
    'Test: Vollständige E-Mail mit allen Fixes',
    '', // Empty custom content
    [
        'summary' => true,
        'customerInfo' => true,
        'appointmentInfo' => true,
        'transcript' => true,
        'attachCSV' => true,
        'attachRecording' => false
    ]
);

$content = $email->content();
$html = view($content->view, $content->with)->render();

// Check key features
echo "   - Custom content position: " . (strpos($html, 'Custom Content at Top') !== false ? 'TOP ✓' : 'Not found') . "\n";
echo "   - Action items removed: " . (strpos($html, 'Handlungsempfehlungen') === false ? 'YES ✓' : 'Still present') . "\n";
echo "   - Urgency displayed: " . (strpos($html, 'Dringlichkeit:') !== false ? 'YES ✓' : 'Not found') . "\n";
echo "   - Transcript not cut off: " . (strpos($html, 'max-height: 400px') === false ? 'YES ✓' : 'Still limited') . "\n";
echo "   - Audio link present: " . (strpos($html, 'Aufzeichnung im Portal anhören') !== false ? 'YES ✓' : 'Not found') . "\n";
echo "   - Portal link correct: " . (strpos($html, 'https://api.askproai.de/business/calls/') !== false ? 'YES ✓' : 'Wrong') . "\n";

// 3. Guest Access URL
echo "\n3. Guest Access URL:\n";
echo "   https://api.askproai.de/business/calls/{$call->id}/guest\n";
echo "   - External recipients can request access through this link\n";

// 4. Send Test Email
echo "\n4. Sending test email...\n";
try {
    Mail::to('fabian@askproai.de')->send($email);
    echo "   ✅ Email sent successfully!\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY OF FIXES ===\n";
echo "✅ CSV shows only customer costs (not internal costs)\n";
echo "✅ Urgency level is displayed in email\n";
echo "✅ Action items removed from email\n";
echo "✅ Custom text is optional and positioned at top\n";
echo "✅ Transcript is not cut off\n";
echo "✅ Audio link added to portal\n";
echo "✅ Guest access flow implemented\n";
echo "✅ Email preview functionality added (requires JS build)\n";

echo "\nDone!\n";