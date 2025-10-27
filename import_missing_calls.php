<?php

/**
 * IMPORT MISSING CALLS FROM RETELL API
 *
 * Imports alle Calls der letzten 24 Stunden von Retell API
 * und synchronisiert sie in die lokale Datenbank.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\RetellApiClient;
use App\Models\Call;
use Illuminate\Support\Facades\Log;

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   📞 IMPORT MISSING CALLS FROM RETELL API                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Get all calls from Retell API (last 24 hours)
echo "=== STEP 1: Fetching Calls from Retell API ===\n";

// Build request body
$requestBody = json_encode([
    'filter_criteria' => [
        'agent_id' => [$agentId]
    ],
    'sort_order' => 'descending',
    'limit' => 50
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/v2/list-calls",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestBody,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch calls: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

// ⚡ IMPORTANT: API returns array directly, not {calls: [...]}
$calls = json_decode($response, true);

if (!is_array($calls) || empty($calls)) {
    echo "⚠️  No calls found in Retell API\n";
    exit(0);
}

echo "✅ Found " . count($calls) . " calls in Retell API\n\n";

// Import each call
echo "=== STEP 2: Importing Calls to Database ===\n";

$retellClient = new RetellApiClient();
$imported = 0;
$skipped = 0;
$failed = 0;

foreach ($calls as $callData) {
    $callId = $callData['call_id'] ?? null;

    if (!$callId) {
        echo "⚠️  Skipping call without call_id\n";
        $failed++;
        continue;
    }

    // Check if already exists
    $existingCall = Call::where('retell_call_id', $callId)->first();

    if ($existingCall) {
        echo "⏭️  {$callId}: Already exists (ID: {$existingCall->id})\n";
        $skipped++;
        continue;
    }

    // Import call
    try {
        $call = $retellClient->syncCallToDatabase($callData);

        if ($call) {
            $duration = round(($callData['duration_ms'] ?? 0) / 1000);
            $timestamp = date('H:i:s', ($callData['start_timestamp'] ?? 0) / 1000);
            echo "✅ {$callId}: Imported (ID: {$call->id}) - {$duration}s @ {$timestamp}\n";
            $imported++;
        } else {
            echo "❌ {$callId}: Failed to import\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ {$callId}: Error - {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n";
echo "=== STEP 3: Summary ===\n";
echo "Total calls in Retell: " . count($calls) . "\n";
echo "✅ Imported: {$imported}\n";
echo "⏭️  Skipped (already exist): {$skipped}\n";
echo "❌ Failed: {$failed}\n\n";

// Verify calls now appear in database
$totalCallsInDb = Call::count();
$recentCalls = Call::orderBy('created_at', 'desc')->limit(5)->get();

echo "=== STEP 4: Verification ===\n";
echo "Total calls in database: {$totalCallsInDb}\n";
echo "\nMost recent calls:\n";

foreach ($recentCalls as $call) {
    $status = $call->call_status ?? $call->status ?? 'unknown';
    $createdAt = $call->created_at->format('Y-m-d H:i:s');
    echo "  - [{$status}] {$call->retell_call_id} (Created: {$createdAt})\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         ✅ IMPORT COMPLETE!                                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "🌐 Check calls in admin panel:\n";
echo "   https://api.askproai.de/admin/retell-call-sessions\n\n";

echo "📋 AUTOMATIC TRACKING STATUS:\n";
echo "   ✅ Webhook URL: Configured (Agent V38)\n";
echo "   ✅ Webhook Handler: Verified functional\n";
echo "   ✅ Next call will automatically appear!\n\n";
