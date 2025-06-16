<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n=== TESTING RETELL V2 API ===\n\n";

$base = config('services.retell.base') ?? env('RETELL_BASE');
$token = config('services.retell.token') ?? env('RETELL_TOKEN');

echo "Base URL: $base\n";
echo "Token configured: " . (!empty($token) ? "Yes (length: " . strlen($token) . ")" : "No") . "\n\n";

// Test different endpoints
$endpoints = [
    'GET /agent' => ['method' => 'GET', 'url' => '/agent'],
    'GET /v2/agent' => ['method' => 'GET', 'url' => '/v2/agent'],
    'POST /v2/list-agents' => ['method' => 'POST', 'url' => '/v2/list-agents', 'body' => []],
    'GET /v2/get-agent' => ['method' => 'GET', 'url' => '/v2/get-agent'],
];

foreach ($endpoints as $name => $config) {
    echo "Testing $name...\n";
    
    try {
        $request = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ]);
        
        $url = $base . $config['url'];
        
        if ($config['method'] === 'POST') {
            $response = $request->post($url, $config['body'] ?? []);
        } else {
            $response = $request->get($url);
        }
        
        echo "  Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $body = $response->json();
            echo "  Response type: " . gettype($body) . "\n";
            if (is_array($body)) {
                echo "  Keys: " . implode(', ', array_keys($body)) . "\n";
            }
        } else {
            $body = $response->body();
            if (strlen($body) < 200) {
                echo "  Body: " . $body . "\n";
            } else {
                echo "  Body (truncated): " . substr($body, 0, 100) . "...\n";
            }
        }
    } catch (\Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== TEST COMPLETE ===\n";