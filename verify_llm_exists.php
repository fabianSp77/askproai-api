#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 VERIFY LLM EXISTS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "LLM ID: $llmId\n\n";

// Try to get the LLM
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-retell-llm/$llmId");

echo "Status: {$response->status()}\n";

if ($response->successful()) {
    $llm = $response->json();
    echo "✅ LLM EXISTS!\n\n";
    echo "Model: {$llm['model']}\n";
    echo "Tools: " . count($llm['general_tools']) . "\n";
    
    // List tools
    foreach ($llm['general_tools'] as $idx => $tool) {
        echo "  " . ($idx + 1) . ". {$tool['name']}\n";
    }
} else {
    echo "❌ LLM NOT FOUND\n";
    echo "Response: {$response->body()}\n";
}

echo "\n";
