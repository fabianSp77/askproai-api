<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Mail\CustomCallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== COMPLETE EMAIL DATA TEST ===\n\n";

// Get a test call with complete data
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('custom_analysis_data')
    ->orderBy('created_at', 'desc')
    ->first();

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
echo "Call Date: " . $call->created_at->format('d.m.Y H:i') . "\n\n";

// Show available data
echo "VERFÜGBARE DATEN:\n";
echo str_repeat('-', 50) . "\n";

// Customer Request
if (isset($call->custom_analysis_data['customer_request'])) {
    echo "✅ Kundenanliegen: " . $call->custom_analysis_data['customer_request'] . "\n";
} elseif ($call->reason_for_visit) {
    echo "✅ Anrufsgrund: " . $call->reason_for_visit . "\n";
} else {
    echo "❌ Kein Kundenanliegen gefunden\n";
}

// Summary
if ($call->call_summary) {
    echo "✅ Zusammenfassung: " . substr($call->call_summary, 0, 80) . "...\n";
} else {
    echo "❌ Keine Zusammenfassung\n";
}

// Customer Info
if ($call->extracted_name || isset($call->custom_analysis_data['caller_full_name'])) {
    echo "✅ Kundenname: " . ($call->extracted_name ?? $call->custom_analysis_data['caller_full_name']) . "\n";
}

// Check for English content
$hasEnglish = false;
if (isset($call->custom_analysis_data['customer_request'])) {
    $text = $call->custom_analysis_data['customer_request'];
    if (preg_match('/\b(the|is|are|was|were|have|has|been|their|with|from|about|would|could|should)\b/i', $text)) {
        echo "⚠️  Kundenanliegen ist auf Englisch\n";
        $hasEnglish = true;
    }
}

if ($call->call_summary && preg_match('/\b(the|is|are|was|were|have|has|been|their|with|from|about|would|could|should)\b/i', $call->call_summary)) {
    echo "⚠️  Zusammenfassung ist auf Englisch\n";
    $hasEnglish = true;
}

echo "\n";

// Simulate English data for testing
if (!$hasEnglish) {
    echo "Simuliere englische Daten für Test...\n";
    $call->custom_analysis_data = array_merge($call->custom_analysis_data ?? [], [
        'customer_request' => 'The customer would like to schedule an appointment for next week. They need information about our services and pricing.',
        'urgency_level' => 'high'
    ]);
    $call->call_summary = 'Customer called to inquire about our services. They are interested in scheduling a consultation.';
}

// Send test email
echo "\nSending email with complete data...\n";
$email = new CustomCallSummaryEmail(
    $call,
    '📋 Vollständige Datenprüfung - Kundenanliegen & Übersetzung',
    '<p><strong>Diese Email zeigt alle verfügbaren Daten:</strong></p>
    <ul style="margin: 10px 0; padding-left: 20px;">
        <li>✅ <strong>Kundenanliegen</strong> - Jetzt prominent über der Zusammenfassung</li>
        <li>✅ <strong>Automatische Übersetzung</strong> - EN→DE für alle Texte</li>
        <li>✅ <strong>Vollständige Daten</strong> - Alle verfügbaren Informationen</li>
        <li>✅ <strong>Hierarchie</strong> - Wichtigste Infos zuerst</li>
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
    
    echo "NEUE FEATURES:\n";
    echo "1. KUNDENANLIEGEN als erste Information (über Zusammenfassung)\n";
    echo "2. Automatische Übersetzung EN→DE für:\n";
    echo "   - Kundenanliegen\n";
    echo "   - Zusammenfassung\n";
    echo "3. Fallback-Reihenfolge für Kundenanliegen:\n";
    echo "   - custom_analysis_data['customer_request']\n";
    echo "   - reason_for_visit\n";
    echo "   - metadata['customer_data']['customer_request']\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "\nDone!\n";