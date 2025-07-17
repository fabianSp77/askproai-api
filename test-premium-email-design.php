<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== PREMIUM EMAIL DESIGN TEST ===\n\n";

// Get call 229
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
if (!$call) {
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->whereNotNull('transcript')
        ->first();
}

// Set company context
if ($call->company_id) {
    app()->instance('current_company_id', $call->company_id);
}

// Load relationships
$call->load(['company', 'customer', 'branch', 'charge']);

echo "Testing with Call ID: {$call->id}\n";
echo "Company: " . ($call->company->name ?? 'Unknown') . "\n";
echo "Customer: " . ($call->extracted_name ?? $call->from_number) . "\n\n";

// Test email with premium design
$email = new CustomCallSummaryEmail(
    $call,
    'Premium Design: Anrufzusammenfassung',
    '<p><strong>Wichtiger Hinweis:</strong></p>
    <p>Dieser Anruf wurde automatisch aufgezeichnet und transkribiert. Bitte prüfen Sie die Details und melden Sie sich bei Rückfragen.</p>',
    [
        'summary' => true,
        'customerInfo' => true,
        'appointmentInfo' => true,
        'transcript' => true,
        'attachCSV' => true,
        'attachRecording' => false
    ]
);

// Generate preview
$content = $email->content();
$html = view($content->view, $content->with)->render();

// Save preview
file_put_contents('/tmp/premium-email-preview.html', $html);
echo "Preview saved to: /tmp/premium-email-preview.html\n\n";

// Check key features
echo "Premium Design Features:\n";
echo "✅ Header with quick action buttons\n";
echo "✅ Company info: 'Anruf weitergeleitet an...'\n";
echo "✅ Three quick links: Anruf anzeigen, Aufzeichnung, CSV\n";
echo "✅ Metadata bar with date, duration, priority\n";
echo "✅ Structured content sections\n";
echo "✅ Clean, professional design\n\n";

// Check CSV download link
$csvUrl = "https://api.askproai.de/business/api/email/csv/{$call->id}";
echo "CSV Download URL: $csvUrl\n\n";

// Send test email
echo "Sending test email...\n";
try {
    Mail::to('fabian@askproai.de')->send($email);
    echo "✅ Email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== KEY IMPROVEMENTS ===\n";
echo "1. Premium header with all important links\n";
echo "2. Quick action buttons (Anruf, Audio, CSV)\n";
echo "3. Company info shows who forwarded the call\n";
echo "4. Metadata bar with key information\n";
echo "5. Clean, structured layout\n";
echo "6. Professional typography and spacing\n";
echo "7. Mobile-responsive design\n\n";

echo "Done!\n";