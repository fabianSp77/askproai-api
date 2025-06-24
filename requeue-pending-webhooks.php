<?php

use App\Models\WebhookEvent;
use App\Jobs\ProcessRetellCallEndedJob;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Requeuing pending Retell webhooks...\n\n";

$pending = WebhookEvent::withoutGlobalScopes()
    ->where('status', 'pending')
    ->where('provider', 'retell')
    ->where('created_at', '>', now()->subDays(2))
    ->get();

echo "Found {$pending->count()} pending webhooks\n";

foreach ($pending as $webhook) {
    try {
        $job = new ProcessRetellCallEndedJob(
            $webhook->payload,
            $webhook->id,
            $webhook->correlation_id
        );
        
        dispatch($job);
        echo "✅ Queued webhook #{$webhook->id}\n";
        
    } catch (\Exception $e) {
        echo "❌ Failed webhook #{$webhook->id}: {$e->getMessage()}\n";
    }
}

echo "\nDone! Run queue worker: php artisan queue:work --queue=webhooks\n";
