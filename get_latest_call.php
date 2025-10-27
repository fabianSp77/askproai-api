<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

// Try different approach - get from database first
$session = \App\Models\RetellCallSession::orderBy('created_at', 'desc')->first();

if ($session) {
    echo "Latest call from database:\n";
    echo "Call ID: {$session->call_id}\n";
    echo "Created: {$session->created_at}\n\n";

    $callId = $session->call_id;

    // Get from Retell API
    $callResp = Http::withHeaders(['Authorization' => "Bearer $token"])
        ->get("https://api.retellai.com/get-call/$callId");

    if (!$callResp->successful()) {
        echo "❌ Failed to get call from API\n";
        echo "Status: {$callResp->status()}\n";
        echo "Error: " . json_encode($callResp->json(), JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    $call = $callResp->json();

    // Save to file
    file_put_contents(__DIR__ . "/latest_test_call_v4_full.json", json_encode($call, JSON_PRETTY_PRINT));

    echo "✅ Call data saved to latest_test_call_v4_full.json\n";
    echo "Call ID: {$call['call_id']}\n";
    echo "Status: {$call['call_status']}\n";
    echo "Duration: " . ($call['end_timestamp'] - $call['start_timestamp']) / 1000 . "s\n";
} else {
    echo "❌ No calls found in database\n";
}
