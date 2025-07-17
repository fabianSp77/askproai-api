<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== ULTIMATE EMAIL DESIGN FINAL TEST ===\n\n";

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

// Simulate English summary to test translation
$originalSummary = $call->call_summary;
$call->call_summary = "The customer called to schedule an appointment for next week. They requested information about our services and would like a callback tomorrow morning.";

// Set urgency for testing
$call->custom_analysis_data = array_merge($call->custom_analysis_data ?? [], [
    'urgency_level' => 'high'
]);

// Send test email with ultimate design
echo "Sending ultimate email with all features...\n";
$email = new CustomCallSummaryEmail(
    $call,
    '🎯 Ultimate Email Design - Alle Features aktiv',
    '<p><strong>Das neue Ultimate Design ist jetzt aktiv mit:</strong></p>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li><strong>"IM PORTAL VERFÜGBAR"</strong> - Klare Kennzeichnung der Portal-Links</li>
        <li><strong>"ANRUF-DETAILS"</strong> - Strukturierte Metadaten-Anzeige</li>
        <li><strong>"Gesprächsinformationen"</strong> - Hauptinhalt-Überschrift</li>
        <li><strong>Automatische Übersetzung</strong> - EN→DE für Zusammenfassungen</li>
        <li><strong>Flexibles Layout</strong> - Erweiterbar für neue Datenfelder</li>
    </ul>
    <p style="margin-top: 15px; color: #059669;"><em>Diese Email zeigt das finale Design mit allen Verbesserungen!</em></p>',
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
    
    echo "=== IMPLEMENTIERTE FEATURES ===\n\n";
    echo "📌 PORTAL-LINKS SECTION:\n";
    echo "   - Überschrift: 'IM PORTAL VERFÜGBAR'\n";
    echo "   - Kompakte Buttons: Details, Audio, CSV\n";
    echo "   - Dunkler Hintergrund für bessere Abgrenzung\n\n";
    
    echo "📊 ANRUF-DETAILS SECTION:\n";
    echo "   - Überschrift: 'ANRUF-DETAILS'\n";
    echo "   - Datum, Uhrzeit, Dauer, Priorität\n";
    echo "   - Strukturierte Darstellung mit Labels\n\n";
    
    echo "📝 HAUPTINHALT:\n";
    echo "   - Überschrift: 'Gesprächsinformationen'\n";
    echo "   - Subsektionen: KONTAKTDATEN, ZUSAMMENFASSUNG, TERMINANFRAGE\n";
    echo "   - Klare visuelle Trennung der Bereiche\n\n";
    
    echo "🌐 ÜBERSETZUNG:\n";
    echo "   - Englische Zusammenfassungen werden automatisch übersetzt\n";
    echo "   - Fallback auf Original bei Übersetzungsfehler\n\n";
    
    echo "🎨 DESIGN:\n";
    echo "   - Konsistente Farbgebung und Abstände\n";
    echo "   - Mobile-optimiert\n";
    echo "   - Erweiterbar für zusätzliche Datenfelder\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Restore original summary
$call->call_summary = $originalSummary;

echo "Done!\n";