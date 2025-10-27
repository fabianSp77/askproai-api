#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📞 LATEST CALL ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get latest call from DB
$latestCall = RetellCallSession::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    echo "❌ No calls found in database\n";
    exit(1);
}

echo "CALL OVERVIEW:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Call ID: {$latestCall->call_id}\n";
echo "Started: {$latestCall->started_at}\n";
echo "Agent Version: " . ($latestCall->agent_version ?? 'N/A') . "\n";
echo "Status: {$latestCall->call_status}\n\n";

// Get function calls
$functions = RetellFunctionTrace::where('call_session_id', $latestCall->id)
    ->orderBy('started_at', 'asc')
    ->get();

echo "FUNCTION CALLS: " . $functions->count() . "\n";
echo "───────────────────────────────────────────────────────────\n\n";

if ($functions->count() === 0) {
    echo "❌ KEINE FUNCTION CALLS!\n\n";
    echo "Das bedeutet:\n";
    echo "  - Agent Version hat 0 Tools (corrupted)\n";
    echo "  - Oder Functions wurden nicht aufgerufen\n\n";

    echo "CHECK:\n";
    echo "  1. Welche Version ist published? (run verify_v71_published.php)\n";
    echo "  2. Hat die published Version Tools?\n\n";

    exit(1);
}

// Analyze function calls
$hasInitialize = false;
$hasCheckAvailability = false;
$hasBookAppointment = false;

foreach ($functions as $func) {
    echo "✅ {$func->function_name}\n";

    if ($func->function_name === 'initialize_call') {
        $hasInitialize = true;
    }

    if (str_contains($func->function_name, 'check_availability')) {
        $hasCheckAvailability = true;

        if ($func->output_result) {
            $output = json_decode($func->output_result, true);
            if (isset($output['available'])) {
                echo "   Available: " . ($output['available'] ? 'YES' : 'NO') . "\n";
            }
            if (isset($output['alternatives']) && !$output['available']) {
                echo "   Alternatives: " . count($output['alternatives']) . " options\n";
            }
        }
    }

    if (str_contains($func->function_name, 'book_appointment')) {
        $hasBookAppointment = true;
    }
}

echo "\n";

// Get call from Retell API
echo "FETCHING TRANSCRIPT FROM RETELL API...\n";
echo "───────────────────────────────────────────────────────────\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->post("https://api.retellai.com/v2/list-calls", [
    'limit' => 5,
    'sort_order' => 'descending'
]);

$calls = $response->json();
$call = null;

foreach ($calls as $c) {
    if ($c['call_id'] === $latestCall->call_id) {
        $call = $c;
        break;
    }
}

if ($call && isset($call['transcript'])) {
    echo "TRANSCRIPT:\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo $call['transcript'] . "\n\n";
}

// Analysis
echo "═══════════════════════════════════════════════════════════\n";
echo "ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo ($hasInitialize ? "✅" : "❌") . " initialize_call " . ($hasInitialize ? "called" : "NOT called") . "\n";
echo ($hasCheckAvailability ? "✅" : "❌") . " check_availability " . ($hasCheckAvailability ? "called" : "NOT called") . "\n";
echo ($hasBookAppointment ? "✅" : "❌") . " book_appointment " . ($hasBookAppointment ? "called" : "NOT called") . "\n\n";

if ($hasInitialize && $hasCheckAvailability) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🎉 SYSTEM WORKS!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "✅ Agent Version {$latestCall->agent_version} has working tools\n";
    echo "✅ Functions are being called correctly\n";
    echo "✅ Real availability checks happening\n\n";

    exit(0);
} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "⚠️  ISSUE DETECTED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    if (!$hasInitialize) {
        echo "❌ initialize_call not called\n";
        echo "   → Agent might not have initialize tool\n\n";
    }

    if (!$hasCheckAvailability) {
        echo "❌ check_availability not called\n";
        echo "   → AI is probably hallucinating availability\n";
        echo "   → Agent might not have check_availability tool\n\n";
    }

    echo "NEXT STEPS:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "1. Verify which version is published: php verify_v71_published.php\n";
    echo "2. If wrong version: Publish V71 in Dashboard\n";
    echo "3. Make another test call\n";
    echo "4. Run this script again\n\n";

    exit(1);
}
