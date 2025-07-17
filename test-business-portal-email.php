<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Test Business Portal E-Mail für Call 229 ===\n\n";

try {
    // Get Call 229
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);
    
    if (!$call) {
        echo "❌ Call 229 nicht gefunden!\n";
        exit(1);
    }
    
    echo "Call gefunden: ID {$call->id}\n";
    echo "Von: {$call->from_number}\n";
    echo "An: {$call->to_number}\n";
    echo "Erstellt: {$call->created_at->format('d.m.Y H:i:s')}\n\n";
    
    // Test direkt senden (ohne Queue)
    echo "1. Test DIREKTER Versand (ohne Queue):\n";
    $recipient = 'fabianspitzer@icloud.com';
    
    try {
        Mail::to($recipient)->send(new CallSummaryEmail(
            $call,
            true,  // include transcript
            false, // no CSV
            'Test-Nachricht vom Business Portal (DIREKT)',
            'internal'
        ));
        echo "   ✅ Direkt versendet an $recipient\n\n";
    } catch (\Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n\n";
    }
    
    // Test mit Queue (wie im Portal)
    echo "2. Test MIT Queue (wie im Portal):\n";
    
    try {
        Mail::to($recipient)->queue(new CallSummaryEmail(
            $call,
            true,  // include transcript
            false, // no CSV
            'Test-Nachricht vom Business Portal (QUEUE)',
            'internal'
        ));
        echo "   ✅ In Queue gestellt für $recipient\n";
        
        // Verarbeite Queue sofort
        echo "   Verarbeite Queue...\n";
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1
        ]);
        echo \Illuminate\Support\Facades\Artisan::output();
        
    } catch (\Exception $e) {
        echo "   ❌ Fehler: " . $e->getMessage() . "\n";
    }
    
    // Prüfe Queue Status
    echo "\n3. Queue Status:\n";
    $redis = app('redis');
    $queues = ['default', 'high', 'low', 'webhooks', 'emails'];
    
    foreach ($queues as $queue) {
        $length = $redis->llen("queues:{$queue}");
        if ($length > 0) {
            echo "   - {$queue}: {$length} Jobs\n";
        }
    }
    
    // Prüfe letzte Aktivitäten
    echo "\n4. Letzte E-Mail Aktivitäten für Call 229:\n";
    $activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 229)
        ->where('activity_type', 'email_sent')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    
    foreach ($activities as $activity) {
        echo "   - {$activity->created_at->format('d.m.Y H:i:s')}: {$activity->description}\n";
        if (isset($activity->metadata['recipients'])) {
            echo "     Empfänger: " . implode(', ', $activity->metadata['recipients']) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test abgeschlossen!\n";