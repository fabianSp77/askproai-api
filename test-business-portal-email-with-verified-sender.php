<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Business Portal E-Mail mit verifiziertem Sender ===\n\n";

// Verify config
echo "1. Aktuelle Mail-Konfiguration:\n";
echo "   From Address: " . config('mail.from.address') . "\n";
echo "   From Name: " . config('mail.from.name') . "\n\n";

// Test with Call 228
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $call->company_id);

// Clear previous activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 228)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

try {
    echo "2. Sende E-Mail über Business Portal Flow:\n";
    
    // Send via Mail facade (like Business Portal does)
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false, // No CSV for quick test
        'Business Portal Test mit verifiziertem Sender - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail erfolgreich versendet!\n\n";
    
    echo "=== ERWARTETES ERGEBNIS ===\n";
    echo "Diese E-Mail sollte DEFINITIV ankommen, da sie jetzt von 'onboarding@resend.dev' gesendet wird.\n";
    echo "Prüfen Sie Ihren Posteingang!\n\n";
    
    echo "=== NÄCHSTE SCHRITTE ===\n";
    echo "Wenn diese E-Mail ankommt:\n";
    echo "1. Das Problem war die fehlende Domain-Verifizierung\n";
    echo "2. Verifizieren Sie 'askproai.de' in Resend\n";
    echo "3. Dann können Sie MAIL_FROM_ADDRESS wieder auf 'info@askproai.de' setzen\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}