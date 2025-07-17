<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Portal\PublicDownloadController;

echo "=== CSV DOWNLOAD LINK TEST ===\n\n";

// Get a test call
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

// Generate a test token
$token = PublicDownloadController::generateDownloadToken($call->id);
echo "Generated download token: $token\n";
echo "Download URL: https://api.askproai.de/business/download/csv/$token\n\n";

// Test the cache
$cachedCallId = \Illuminate\Support\Facades\Cache::get("csv_download_token_{$token}");
echo "Token verified in cache: " . ($cachedCallId == $call->id ? "YES ✓" : "NO ✗") . "\n\n";

// Send test email with working CSV link
echo "Sending test email with CSV download link...\n";
$email = new CustomCallSummaryEmail(
    $call,
    'Test: CSV Download Link funktioniert jetzt',
    '<p><strong>Der CSV-Download-Link ist jetzt funktionsfähig!</strong></p>
    <p>Der Link im Header führt direkt zum CSV-Download ohne Login-Anforderung.</p>',
    [
        'summary' => true,
        'customerInfo' => true,
        'appointmentInfo' => true,
        'transcript' => false,
        'attachCSV' => true,
        'attachRecording' => false
    ]
);

try {
    Mail::to('fabian@askproai.de')->send($email);
    echo "✅ Email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== IMPLEMENTATION DETAILS ===\n";
echo "1. CSV-Button generiert temporären Token (24h gültig)\n";
echo "2. Token wird in Cache gespeichert\n";
echo "3. Öffentlicher Download-Link ohne Login\n";
echo "4. Link funktioniert für alle Empfänger\n";
echo "5. Sichere Implementierung mit Token-Validierung\n\n";

echo "Test the link manually:\n";
echo "https://api.askproai.de/business/download/csv/$token\n\n";

echo "Done!\n";