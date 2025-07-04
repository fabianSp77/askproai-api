#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "📞 Importing Latest Call\n";
echo "========================\n\n";

$apiKey = config('services.retell.api_key');

// Get the latest call
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 1,
    'sort_order' => 'descending'
]);

if (!$response->successful()) {
    echo "❌ API Error\n";
    exit(1);
}

$data = $response->json();
$calls = $data['results'] ?? [];

if (empty($calls)) {
    echo "❌ No calls found\n";
    exit(1);
}

$call = $calls[0];
$callId = $call['call_id'];

echo "Latest Call:\n";
echo "  ID: $callId\n";
echo "  From: " . $call['from_number'] . "\n";
echo "  To: " . $call['to_number'] . "\n";
echo "  Time: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n";
echo "  Duration: " . ($call['call_duration'] ?? 0) . " seconds\n";
echo "\n";

// Show all data
echo "Full Call Data:\n";
echo json_encode($call, JSON_PRETTY_PRINT) . "\n\n";

// Check if already exists
$existing = \App\Models\Call::where('retell_call_id', $callId)->first();
if ($existing) {
    echo "⚠️  Call already imported (DB ID: {$existing->id})\n";
} else {
    echo "Importing call...\n";
    
    // Create webhook event
    $webhookEvent = \App\Models\WebhookEvent::create([
        'provider' => 'retell',
        'event_type' => 'call_ended',
        'event_id' => 'import_' . $callId,
        'payload' => [
            'event_type' => 'call_ended',
            'call' => $call
        ],
        'status' => 'pending'
    ]);
    
    // Process it
    $job = new \App\Jobs\ProcessRetellWebhookJob($webhookEvent, 'import_' . uniqid());
    $webhookProcessor = app(\App\Services\WebhookProcessor::class);
    $deduplicationService = app(\App\Services\Webhook\EnhancedWebhookDeduplicationService::class);
    
    try {
        $job->handle($webhookProcessor, $deduplicationService);
        echo "✓ Imported successfully!\n";
        
        $importedCall = \App\Models\Call::where('retell_call_id', $callId)->first();
        if ($importedCall) {
            echo "  DB ID: {$importedCall->id}\n";
            echo "  Company ID: {$importedCall->company_id}\n";
            echo "  Customer ID: {$importedCall->customer_id}\n";
            
            if ($importedCall->appointment_id) {
                echo "  ✓ Appointment created!\n";
            } else {
                echo "  ℹ️  No appointment created (no booking data in call)\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}