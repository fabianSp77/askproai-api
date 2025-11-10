#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "🔍 Checking agent fields...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ Failed\n";
    exit(1);
}

$agent = $response->json();

echo "📋 All Agent Fields:\n";
$keys = array_keys($agent);
sort($keys);

foreach ($keys as $key) {
    $value = $agent[$key];
    if (is_array($value)) {
        echo "   {$key}: array(" . count($value) . " items)\n";
    } elseif (is_string($value) && strlen($value) > 100) {
        echo "   {$key}: string(" . strlen($value) . " chars)\n";
    } elseif (is_bool($value)) {
        echo "   {$key}: " . ($value ? 'true' : 'false') . "\n";
    } elseif (is_null($value)) {
        echo "   {$key}: null\n";
    } else {
        echo "   {$key}: {$value}\n";
    }
}

file_put_contents('/tmp/agent_all_fields.json', json_encode($agent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✅ Saved to /tmp/agent_all_fields.json\n";
