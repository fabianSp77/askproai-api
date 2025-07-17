<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Sende E-Mail DIREKT vom Business Portal ===\n\n";

try {
    // Get Call 229
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if (!$call) {
        echo "❌ Call 229 nicht gefunden!\n";
        exit(1);
    }
    
    $timestamp = now()->format('d.m.Y H:i:s');
    $recipient = 'fabianspitzer@icloud.com';
    
    echo "Sende E-Mail an: $recipient\n";
    echo "Call ID: {$call->id}\n";
    echo "Zeitstempel: $timestamp\n\n";
    
    // Send directly (not queued)
    Mail::to($recipient)->send(new CallSummaryEmail(
        $call,
        true,  // include transcript
        false, // no CSV
        "Diese E-Mail wurde DIREKT vom Business Portal versendet.\n\nZeitpunkt: $timestamp\n\nBitte bestätigen Sie den Empfang dieser E-Mail.",
        'internal'
    ));
    
    echo "✅ E-Mail wurde DIREKT versendet (nicht über Queue)\n\n";
    
    // Log activity
    \App\Models\CallActivity::log($call, \App\Models\CallActivity::TYPE_EMAIL_SENT, 'Direkt-Test vom Business Portal', [
        'user_id' => 1,
        'is_system' => false,
        'description' => "Test-E-Mail direkt versendet an $recipient",
        'metadata' => [
            'recipients' => [$recipient],
            'subject' => 'Anrufzusammenfassung vom ' . $call->created_at->format('d.m.Y H:i'),
            'included_transcript' => true,
            'sent_at' => $timestamp,
            'send_method' => 'direct'
        ]
    ]);
    
    echo "=== WICHTIGE INFORMATIONEN ===\n";
    echo "1. Die E-Mail wurde DIREKT versendet (nicht über Queue)\n";
    echo "2. Betreff: 'Anrufzusammenfassung vom " . $call->created_at->format('d.m.Y H:i') . "'\n";
    echo "3. Absender: info@askproai.de\n";
    echo "4. Empfänger: $recipient\n\n";
    
    echo "Bitte prüfen Sie:\n";
    echo "- Hauptposteingang\n";
    echo "- Spam/Junk-Ordner\n";
    echo "- Werbung/Promotions Tab (bei Gmail)\n";
    echo "- Updates Tab (bei iCloud)\n\n";
    
    echo "Die E-Mail enthält:\n";
    echo "- Anrufinformationen\n";
    echo "- Transkript des Gesprächs\n";
    echo "- Test-Nachricht mit Zeitstempel\n";
    
} catch (\Exception $e) {
    echo "❌ FEHLER beim E-Mail-Versand:\n";
    echo $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}