<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== OPTIMIZED BUTTON LAYOUT TEST ===\n\n";

// Get test call
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

echo "Button Layout Optimizations:\n";
echo "✅ Shorter button labels:\n";
echo "   - 'Anruf anzeigen' → '📞 Details'\n";
echo "   - 'Aufzeichnung' → '🎧 Audio'\n";
echo "   - 'CSV herunterladen' → '📊 CSV'\n\n";

echo "✅ Smaller padding and font size\n";
echo "✅ Inline table layout for better alignment\n";
echo "✅ No line breaks with white-space: nowrap\n\n";

// Send test email
$email = new CustomCallSummaryEmail(
    $call,
    'Optimierte Button-Größen',
    '<p>Die Buttons im Header sind jetzt kompakter und passen besser nebeneinander.</p>',
    [
        'summary' => true,
        'customerInfo' => true,
        'appointmentInfo' => false,
        'transcript' => false,
        'attachCSV' => true,
        'attachRecording' => false
    ]
);

echo "Sending test email...\n";
try {
    Mail::to('fabian@askproai.de')->send($email);
    echo "✅ Email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "Done!\n";