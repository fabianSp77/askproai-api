<?php

use App\Models\WebhookEvent;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Fix Webhook Event Types\n";
echo "=================================\n\n";

// Get webhooks with NULL event_type
$webhooksToFix = WebhookEvent::whereNull('event_type')
    ->where('provider', 'retell')
    ->get();

echo "Found " . count($webhooksToFix) . " webhooks to fix\n\n";

$fixed = 0;

foreach ($webhooksToFix as $webhook) {
    $eventType = $webhook->payload['event'] ?? null;
    
    if ($eventType) {
        $webhook->event_type = $eventType;
        $webhook->save();
        $fixed++;
        echo "Fixed webhook ID {$webhook->id}: event_type = {$eventType}\n";
    }
}

echo "\n✅ Fixed {$fixed} webhooks\n";