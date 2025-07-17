<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

// Get a recent call with audio
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNotNull('recording_url')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "No calls with recordings found.\n";
    exit(1);
}

echo "Call ID: {$call->id}\n";
echo "Recording URL: " . ($call->recording_url ?? 'null') . "\n";
echo "Audio URL: " . ($call->audio_url ?? 'null') . "\n";
echo "RecordingUrl: " . ($call->recordingUrl ?? 'null') . "\n";

// Check webhook data
if ($call->webhook_data) {
    $webhookData = is_string($call->webhook_data) ? json_decode($call->webhook_data, true) : $call->webhook_data;
    if (isset($webhookData['recording_url'])) {
        echo "Webhook recording_url: " . $webhookData['recording_url'] . "\n";
    }
}

echo "\nOther relevant fields:\n";
echo "From Number: " . $call->from_number . "\n";
echo "Duration (sec): " . $call->duration_sec . "\n";
echo "Created At: " . $call->created_at . "\n";