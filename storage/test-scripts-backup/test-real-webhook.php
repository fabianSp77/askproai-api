<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Test Real Webhook ===\n\n";

$webhookSecret = config('services.retell.webhook_secret') ?: config('services.retell.api_key');
if (strpos($webhookSecret, 'eyJ') === 0) {
    $webhookSecret = decrypt($webhookSecret);
}

// Simuliere einen echten Webhook wie von Retell
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'call_test_' . time() . '_real',
        'from_number' => '+491701234567',
        'to_number' => '+493083793369',
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'direction' => 'inbound',
        'call_type' => 'phone_call',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 180) * 1000,
        'end_timestamp' => time() * 1000,
        'duration' => 180,
        'duration_ms' => 180000,
        'disconnection_reason' => 'user_hangup',
        'transcript' => 'Hallo, ich möchte gerne einen Termin vereinbaren.',
        'summary' => 'Kunde möchte Termin vereinbaren',
        'recording_url' => null,
        'public_log_url' => 'https://app.retellai.com/calls/call_test_' . time(),
        'call_cost' => [
            'total_cost' => 0.42,
            'llm_cost' => 0.15,
            'transcription_cost' => 0.12,
            'voice_cost' => 0.15
        ]
    ]
];

$body = json_encode($payload);
$timestamp = round(microtime(true) * 1000);

// Create signature like Retell does (mit Punkt zwischen timestamp und body)
$signaturePayload = $timestamp . '.' . $body;
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

echo "Sende Webhook an: https://api.askproai.de/api/retell/webhook\n";
echo "Timestamp: $timestamp\n";
echo "Call ID: " . $payload['call']['call_id'] . "\n\n";

// Send webhook
$response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'X-Retell-Signature' => $signature,
    'X-Retell-Timestamp' => $timestamp,
])->post('https://api.askproai.de/api/retell/webhook', $payload);

echo "Response Status: " . $response->status() . "\n";
echo "Response Body: " . $response->body() . "\n\n";

if ($response->successful()) {
    echo "✅ Webhook erfolgreich gesendet!\n\n";
    
    // Warte kurz und prüfe ob der Call in der DB ist
    sleep(2);
    
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('retell_call_id', $payload['call']['call_id'])
        ->first();
    if ($call) {
        echo "✅ Call wurde in Datenbank gespeichert!\n";
        echo "   DB ID: " . $call->id . "\n";
        echo "   Company ID: " . $call->company_id . "\n";
        echo "   Branch ID: " . $call->branch_id . "\n";
    } else {
        echo "❌ Call wurde NICHT in Datenbank gefunden!\n";
    }
} else {
    echo "❌ Webhook fehlgeschlagen!\n";
}