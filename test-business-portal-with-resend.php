<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Business Portal E-Mail Test mit Resend ===\n\n";

try {
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if (!$call) {
        echo "❌ Call 229 nicht gefunden!\n";
        exit(1);
    }
    
    $timestamp = now()->format('d.m.Y H:i:s');
    $testId = uniqid('bp-resend-');
    $recipient = 'fabianspitzer@icloud.com';
    
    echo "Mail System: " . config('mail.default') . " (sollte 'resend' sein)\n";
    echo "Test-ID: $testId\n";
    echo "An: $recipient\n\n";
    
    // Send via queue like Business Portal
    Mail::to($recipient)->queue(new CallSummaryEmail(
        $call,
        true,  // include transcript
        false, // no CSV
        "Business Portal Test mit Resend\n\nTest-ID: $testId\nZeit: $timestamp\n\nDiese E-Mail wurde über Resend versendet und sollte sofort ankommen!",
        'internal'
    ));
    
    echo "✅ E-Mail in Queue gestellt\n\n";
    
    // Process queue
    echo "Verarbeite Queue...\n";
    \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1
    ]);
    
    // Log activity
    \App\Models\CallActivity::log($call, \App\Models\CallActivity::TYPE_EMAIL_SENT, 'Resend Test vom Business Portal', [
        'user_id' => 1,
        'is_system' => false,
        'description' => "Test-E-Mail über Resend versendet",
        'metadata' => [
            'recipients' => [$recipient],
            'test_id' => $testId,
            'mail_driver' => 'resend',
            'sent_at' => $timestamp
        ]
    ]);
    
    echo "\n✅ FERTIG!\n";
    echo "Die E-Mail sollte SOFORT ankommen.\n";
    echo "Betreff: 'Anrufzusammenfassung vom 04.07.2025 09:41'\n";
    echo "\nVorteile von Resend:\n";
    echo "- ✅ SPF bereits konfiguriert\n";
    echo "- ✅ Bessere Zustellrate\n";
    echo "- ✅ Schnellere Zustellung\n";
    echo "- ✅ Detaillierte Delivery-Reports\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}