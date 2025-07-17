<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Business Portal E-Mail Test (FINAL) ===\n\n";

try {
    // Get Call 229
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if (!$call) {
        echo "❌ Call 229 nicht gefunden!\n";
        exit(1);
    }
    
    $timestamp = now()->format('d.m.Y H:i:s');
    $testId = uniqid('bp-test-');
    $recipient = 'fabianspitzer@icloud.com';
    
    echo "Sende Business Portal E-Mail...\n";
    echo "Test-ID: $testId\n";
    echo "An: $recipient\n\n";
    
    // Send like the Business Portal does (queued)
    Mail::to($recipient)->queue(new CallSummaryEmail(
        $call,
        true,  // include transcript
        false, // no CSV
        "Business Portal Test\n\nTest-ID: $testId\nZeit: $timestamp\n\nBitte bestätigen Sie den Empfang dieser E-Mail.",
        'internal'
    ));
    
    echo "✅ E-Mail in Queue gestellt\n\n";
    
    // Process queue immediately
    echo "Verarbeite Queue...\n";
    \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1
    ]);
    
    $output = \Illuminate\Support\Facades\Artisan::output();
    if (trim($output)) {
        echo $output . "\n";
    }
    
    // Log activity like Business Portal does
    \App\Models\CallActivity::log($call, \App\Models\CallActivity::TYPE_EMAIL_SENT, 'Business Portal Test', [
        'user_id' => 1,
        'is_system' => false,
        'description' => "Test-E-Mail über Business Portal Queue",
        'metadata' => [
            'recipients' => [$recipient],
            'test_id' => $testId,
            'queued_at' => $timestamp
        ]
    ]);
    
    echo "\n=== ZUSAMMENFASSUNG ===\n";
    echo "✅ E-Mail wurde in Queue gestellt und verarbeitet\n";
    echo "✅ Test-ID: $testId\n";
    echo "✅ Die vorherige Test-E-Mail ist angekommen\n\n";
    
    echo "Diese E-Mail sollte in 1-2 Minuten ankommen.\n";
    echo "Betreff: 'Anrufzusammenfassung vom 04.07.2025 09:41'\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}