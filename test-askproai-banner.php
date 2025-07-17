<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== ASKPROAI SERVICE BANNER TEST ===\n\n";

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

echo "Testing with Call ID: {$call->id}\n";
echo "Company: " . ($call->company->name ?? 'N/A') . "\n\n";

// Send test email with new banner
echo "Sending email with elegant AskProAI service banner...\n";
$email = new CustomCallSummaryEmail(
    $call,
    '✨ Neues Design mit AskProAI Service-Banner',
    '<p><strong>Jetzt mit elegantem Service-Banner ganz oben!</strong></p>
    <p>Die Email zeigt jetzt deutlich, dass es sich um einen Service von AskProAI.de handelt - mit einem schönen, professionellen Design.</p>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li>✨ Elegantes Banner mit Link zu AskProAI.de</li>
        <li>🎨 Dunkler Hintergrund für Premium-Look</li>
        <li>🔗 Klickbarer Link zur Hauptseite</li>
        <li>📱 Mobile-optimiert</li>
    </ul>',
    [
        'summary' => true,
        'customerInfo' => true,
        'appointmentInfo' => false,
        'transcript' => false,
        'attachCSV' => true,
        'attachRecording' => false
    ]
);

try {
    Mail::to('fabian@askproai.de')->send($email);
    echo "✅ Email sent successfully!\n\n";
    
    echo "=== NEUE FEATURES ===\n\n";
    echo "🎯 SERVICE-BANNER:\n";
    echo "   - Ganz oben positioniert\n";
    echo "   - Dunkler, eleganter Hintergrund (#0f172a)\n";
    echo "   - Text: '✨ Ein Service von AskProAI.de - KI-gestützte Anrufverwaltung'\n";
    echo "   - Verlinkung zu https://askproai.de\n";
    echo "   - Dezent aber professionell\n\n";
    
    echo "📐 DESIGN-DETAILS:\n";
    echo "   - Kompakte Höhe (12px Padding)\n";
    echo "   - Zentrierte Ausrichtung\n";
    echo "   - Farbschema passend zum Rest der Email\n";
    echo "   - Klare Trennung vom Hauptinhalt\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "Done!\n";