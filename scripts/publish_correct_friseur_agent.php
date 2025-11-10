<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== PUBLISH RICHTIGER FRISEUR 1 AGENT ===\n\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->post("{$baseUrl}/publish-agent/{$agentId}");

    if ($response->successful()) {
        $result = $response->json();
        echo "✅ Agent published!\n";
        echo "   Version: " . ($result['version'] ?? 'unknown') . "\n";
        echo "   Agent ID: " . ($result['agent_id'] ?? 'unknown') . "\n";
    } else {
        echo "⚠️ Status: " . $response->status() . "\n";
        echo $response->body() . "\n";
    }

    // Verify
    echo "\n🔍 Verifying...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-agent/{$agentId}");

    if ($verifyResponse->successful()) {
        $agent = $verifyResponse->json();
        echo "   Version: " . ($agent['version'] ?? 'unknown') . "\n";
        echo "   Is Published: " . ($agent['is_published'] ? '✅ YES' : '❌ NO') . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
