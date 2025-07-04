<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING LATEST RETELL CALLS ===\n\n";

try {
    // Initialize Retell service
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // Get latest calls
    $response = $retellService->listCalls(20);
    
    if (empty($response['calls'])) {
        echo "No calls found in Retell.\n";
        exit;
    }
    
    echo "Found " . count($response['calls']) . " recent calls:\n\n";
    
    foreach ($response['calls'] as $index => $call) {
        $startTime = isset($call['start_timestamp']) ? 
            date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) : 
            'Unknown';
        
        echo "Call #" . ($index + 1) . ":\n";
        echo "  Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
        echo "  From: " . ($call['from_number'] ?? 'Unknown') . "\n";
        echo "  To: " . ($call['to_number'] ?? 'Unknown') . "\n";
        echo "  Agent ID: " . ($call['agent_id'] ?? 'Unknown') . "\n";
        echo "  Start: {$startTime}\n";
        echo "  Duration: " . ($call['call_length'] ?? 0) . " seconds\n";
        echo "  Status: " . ($call['call_status'] ?? 'Unknown') . "\n";
        echo "  Disconnection: " . ($call['disconnection_reason'] ?? 'Unknown') . "\n";
        
        if (!empty($call['transcript_summary'])) {
            echo "  Summary: " . substr($call['transcript_summary'], 0, 100) . "...\n";
        }
        
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}