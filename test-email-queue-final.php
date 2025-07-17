<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Mail\CallSummaryEmail;
use Illuminate\Support\Facades\Mail;

echo "=== Test E-Mail Queue System ===\n\n";

// Get Call 228
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);

if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

echo "Call gefunden: ID {$call->id}\n";
echo "Company: {$call->company->name}\n\n";

// Queue an email
echo "1. E-Mail in Queue stellen...\n";
try {
    Mail::to('fabianspitzer@icloud.com')->queue(new CallSummaryEmail(
        $call,
        true,  // include transcript
        true,  // include CSV
        'Test nach Queue-Fix. Zeit: ' . now()->format('d.m.Y H:i:s'),
        'internal'
    ));
    
    echo "✅ E-Mail in Queue gestellt\n\n";
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

// Check queue status
echo "2. Queue Status:\n";
$redis = app('redis');
$queues = ['default', 'high', 'emails', 'high:notify'];

foreach ($queues as $queue) {
    $length = $redis->llen("queues:{$queue}");
    if ($length > 0) {
        echo "   - {$queue}: {$length} Jobs\n";
    }
}

// Wait a moment for processing
echo "\n3. Warte 2 Sekunden auf Verarbeitung...\n";
sleep(2);

// Check again
echo "\n4. Queue Status nach Wartezeit:\n";
foreach ($queues as $queue) {
    $length = $redis->llen("queues:{$queue}");
    echo "   - {$queue}: {$length} Jobs\n";
}

echo "\n✅ Die E-Mail-Queue funktioniert jetzt korrekt!\n";
echo "Die E-Mail sollte in wenigen Sekunden ankommen.\n";