<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== WEBHOOK HEALTH CHECK ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Check for stuck in_progress calls
echo "1. STUCK IN_PROGRESS CALLS\n";
echo str_repeat("-", 40) . "\n";

$stuckCalls = DB::table('calls')
    ->where('call_status', 'in_progress')
    ->where('created_at', '<', now()->subMinutes(15))
    ->count();

if ($stuckCalls > 0) {
    echo "⚠️ Found $stuckCalls stuck calls (older than 15 minutes)\n";
    echo "   Run: php cleanup-stale-calls.php\n";
} else {
    echo "✅ No stuck calls\n";
}

// 2. Check for duplicate calls
echo "\n2. DUPLICATE CALLS CHECK\n";
echo str_repeat("-", 40) . "\n";

$duplicates = DB::table('calls')
    ->select('call_id', DB::raw('COUNT(*) as count'))
    ->where('created_at', '>', now()->subDay())
    ->groupBy('call_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isNotEmpty()) {
    echo "⚠️ Found duplicate calls:\n";
    foreach ($duplicates as $dup) {
        echo "   - " . $dup->call_id . ": " . $dup->count . " times\n";
    }
} else {
    echo "✅ No duplicate calls in last 24 hours\n";
}

// 3. Check for missing data
echo "\n3. CALLS WITH MISSING DATA\n";
echo str_repeat("-", 40) . "\n";

$missingData = DB::table('calls')
    ->where('created_at', '>', now()->subDay())
    ->where(function($query) {
        $query->where('from_number', 'unknown')
              ->orWhereNull('from_number')
              ->orWhere('from_number', '');
    })
    ->count();

if ($missingData > 0) {
    echo "⚠️ Found $missingData calls with missing phone numbers\n";
} else {
    echo "✅ All calls have phone numbers\n";
}

// 4. Check webhook endpoint
echo "\n4. WEBHOOK ENDPOINT TEST\n";
echo str_repeat("-", 40) . "\n";

$ch = curl_init('https://api.askproai.de/api/retell/webhook-simple');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'health_check']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Webhook endpoint responding (HTTP $httpCode)\n";
} else {
    echo "❌ Webhook endpoint error (HTTP $httpCode)\n";
}

// 5. Recent call activity
echo "\n5. RECENT CALL ACTIVITY\n";
echo str_repeat("-", 40) . "\n";

$recentCalls = DB::table('calls')
    ->where('created_at', '>', now()->subHour())
    ->count();

$last5Minutes = DB::table('calls')
    ->where('created_at', '>', now()->subMinutes(5))
    ->count();

echo "Calls in last hour: $recentCalls\n";
echo "Calls in last 5 minutes: $last5Minutes\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "HEALTH STATUS: ";

if ($stuckCalls == 0 && $duplicates->isEmpty() && $missingData == 0 && $httpCode === 200) {
    echo "✅ HEALTHY\n";
} else {
    echo "⚠️ ISSUES DETECTED\n";
}

echo str_repeat("=", 50) . "\n";