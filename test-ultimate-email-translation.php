<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;
use App\Services\TranslationService;

echo "=== ULTIMATE EMAIL WITH TRANSLATION TEST ===\n\n";

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

// Test translation detection
$englishText = "The customer called to schedule an appointment for next week. They requested information about our services and pricing.";
$germanText = "Der Kunde hat angerufen, um einen Termin für nächste Woche zu vereinbaren.";

$email = new CustomCallSummaryEmail($call, '', '', []);
$isEnglish1 = $email->isEnglish($englishText);
$isEnglish2 = $email->isEnglish($germanText);

echo "English detection test:\n";
echo "English text: " . ($isEnglish1 ? "✅ Correctly detected as English" : "❌ Not detected") . "\n";
echo "German text: " . ($isEnglish2 ? "❌ Incorrectly detected as English" : "✅ Correctly detected as German") . "\n\n";

// Test translation service
echo "Testing translation service:\n";
try {
    $translationService = app(TranslationService::class);
    $translated = $translationService->translate($englishText, 'de');
    echo "Original: $englishText\n";
    echo "Translated: $translated\n";
    echo "✅ Translation service working\n\n";
} catch (\Exception $e) {
    echo "❌ Translation error: " . $e->getMessage() . "\n\n";
}

// Set English summary to test automatic translation
$call->call_summary = $englishText;
$call->custom_analysis_data = array_merge($call->custom_analysis_data ?? [], [
    'urgency_level' => 'high'
]);

// Send test email with ultimate design
echo "Sending ultimate email with automatic translation...\n";
$email = new CustomCallSummaryEmail(
    $call,
    'Ultimate Design: Portal-Links & Automatische Übersetzung',
    '<p><strong>Neue Features im Ultimate Design:</strong></p>
    <ul>
        <li>✅ "IM PORTAL VERFÜGBAR" Überschrift für Portal-Links</li>
        <li>✅ "ANRUF-DETAILS" für strukturierte Metadaten</li>
        <li>✅ "Gesprächsinformationen" als Hauptinhalt</li>
        <li>✅ Automatische Übersetzung von englischen Zusammenfassungen</li>
        <li>✅ Flexibles Design für zusätzliche Daten</li>
    </ul>',
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

echo "=== ULTIMATE EMAIL FEATURES ===\n";
echo "1. Portal-Links mit 'IM PORTAL VERFÜGBAR' Überschrift\n";
echo "2. Anruf-Details mit 'ANRUF-DETAILS' Label\n";
echo "3. Hauptinhalt unter 'Gesprächsinformationen'\n";
echo "4. Automatische Übersetzung EN→DE für Zusammenfassungen\n";
echo "5. Flexibles Design für weitere Felder\n";
echo "6. Kompakte Buttons: Details, Audio, CSV\n";
echo "7. Strukturierte Sektionen mit klaren Trennungen\n\n";

echo "Done!\n";