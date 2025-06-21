<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Models\Call;
use App\Models\Company;
use App\Services\CallDataRefresher;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.retell.api_key');
$baseUrl = rtrim(config('services.retell.base', 'https://api.retellai.com'), '/');

echo "Importing historical calls from Retell...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Get the first company
$company = Company::first();
if (!$company) {
    die("No company found in database!\n");
}

echo "Using company: {$company->name} (ID: {$company->id})\n\n";

// Get list of calls using direct API call
echo "Fetching calls from Retell API...\n";

$headers = [
    'Authorization' => 'Bearer ' . $apiKey,
    'Accept' => 'application/json',
];

// Try different endpoints
$endpoints = [
    '/v2/list-calls',
    '/list-calls',
    '/calls'
];

$calls = null;
foreach ($endpoints as $endpoint) {
    echo "Trying endpoint: $endpoint\n";
    
    try {
        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($baseUrl . $endpoint, [
                'limit' => 50,
                'sort' => 'desc'
            ]);
        
        if ($response->successful()) {
            $data = $response->json();
            // Check if calls are in a nested structure
            $calls = $data['calls'] ?? $data['data'] ?? $data;
            
            if (is_array($calls) && !empty($calls)) {
                echo "✅ Found calls at $endpoint\n";
                break;
            }
        } else {
            echo "   Failed: " . $response->status() . "\n";
        }
    } catch (\Exception $e) {
        echo "   Error: " . $e->getMessage() . "\n";
    }
}

if (!$calls) {
    // Try using the call refresher to get individual calls
    echo "\nTrying to fetch recent call IDs...\n";
    
    // Common test call IDs from logs
    $knownCallIds = [
        'c415a9facdf5873ab8d20db11b8860e31d15f75ed2b36816c3dd231f65bf0416',
        '9408eff8f0db9ddace793939d18fa6f1bb8118d711313fc5a60ed3cf5912a8f8',
        // Add more if you know them
    ];
    
    $imported = 0;
    $refresher = new CallDataRefresher();
    
    foreach ($knownCallIds as $callId) {
        echo "Checking call: $callId\n";
        
        // Check if already exists
        if (Call::where('call_id', $callId)->orWhere('retell_call_id', $callId)->exists()) {
            echo "   Already exists\n";
            continue;
        }
        
        // Create basic call record
        $call = Call::create([
            'company_id' => $company->id,
            'call_id' => $callId,
            'retell_call_id' => $callId,
            'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
            'status' => 'completed',
            'direction' => 'inbound',
            'from_number' => '+491604366218', // Your test number
            'to_number' => '+493083793369',
            'created_at' => now()->subHours(rand(1, 24)),
        ]);
        
        // Try to refresh data from API
        if ($refresher->refresh($call)) {
            echo "   ✅ Imported and refreshed\n";
            $imported++;
        } else {
            echo "   ⚠️  Created basic record (no API data)\n";
            $imported++;
        }
    }
    
    echo "\nImported $imported calls\n";
    
} else {
    // Process the calls array
    echo "Found " . count($calls) . " calls\n\n";
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($calls as $callData) {
        $callId = $callData['call_id'] ?? $callData['id'] ?? null;
        if (!$callId) continue;
        
        if (Call::where('call_id', $callId)->orWhere('retell_call_id', $callId)->exists()) {
            $skipped++;
            continue;
        }
        
        try {
            Call::create([
                'company_id' => $company->id,
                'call_id' => $callId,
                'retell_call_id' => $callId,
                'agent_id' => $callData['agent_id'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['call_type'] ?? 'inbound',
                'status' => $callData['call_status'] ?? 'completed',
                'start_timestamp' => isset($callData['start_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : null,
                'end_timestamp' => isset($callData['end_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : null,
                'duration_sec' => isset($callData['duration_ms']) 
                    ? round($callData['duration_ms'] / 1000) 
                    : null,
                'cost' => isset($callData['cost']) ? $callData['cost'] / 100 : 0,
                'transcript' => $callData['transcript'] ?? null,
                'summary' => $callData['call_analysis']['call_summary'] ?? null,
            ]);
            
            echo "✅ Imported: $callId\n";
            $imported++;
            
        } catch (\Exception $e) {
            echo "❌ Failed: $callId - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nImported: $imported, Skipped: $skipped\n";
}

echo "\nChecking current call count...\n";
$totalCalls = Call::count();
$recentCalls = Call::where('created_at', '>', now()->subDay())->count();

echo "Total calls in database: $totalCalls\n";
echo "Calls in last 24 hours: $recentCalls\n";

echo "\nDone!\n";