<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

echo "Listing all Retell LLMs...\n\n";

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-retell-llms');

if ($response->successful()) {
    $llms = $response->json();
    echo "Found " . count($llms) . " LLMs\n\n";
    
    foreach ($llms as $idx => $llm) {
        $id = $llm['llm_id'] ?? 'N/A';
        $model = $llm['model'] ?? 'N/A';
        $toolCount = isset($llm['general_tools']) ? count($llm['general_tools']) : 0;
        
        $marker = ($id === $llmId) ? ' ← OUR LLM' : '';
        echo ($idx + 1) . ". $id$marker\n";
        echo "   Model: $model\n";
        echo "   Tools: $toolCount\n\n";
    }
} else {
    echo "Failed: {$response->status()}\n";
    echo $response->body() . "\n";
}
