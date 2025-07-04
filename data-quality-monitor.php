<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATA QUALITY MONITOR ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// 1. CURRENT STATUS
echo "📊 AKTUELLE DATENQUALITÄT\n";
echo str_repeat("-", 50) . "\n";

$currentStats = DB::selectOne("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN duration_sec > 0 THEN 1 ELSE 0 END) as has_duration,
        SUM(CASE WHEN user_sentiment IS NOT NULL THEN 1 ELSE 0 END) as has_sentiment,
        SUM(CASE WHEN agent_name IS NOT NULL THEN 1 ELSE 0 END) as has_agent,
        SUM(CASE WHEN call_successful IS NOT NULL THEN 1 ELSE 0 END) as has_success,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as has_cost,
        SUM(CASE WHEN extracted_name IS NOT NULL THEN 1 ELSE 0 END) as has_customer,
        SUM(CASE WHEN end_to_end_latency IS NOT NULL THEN 1 ELSE 0 END) as has_latency,
        SUM(CASE WHEN analysis IS NOT NULL AND analysis != '' THEN 1 ELSE 0 END) as has_analysis
    FROM calls
");

$metrics = [
    'Duration' => [$currentStats->has_duration, $currentStats->total_calls],
    'Sentiment' => [$currentStats->has_sentiment, $currentStats->total_calls],
    'Agent Info' => [$currentStats->has_agent, $currentStats->total_calls],
    'Call Success' => [$currentStats->has_success, $currentStats->total_calls],
    'Cost Data' => [$currentStats->has_cost, $currentStats->total_calls],
    'Customer Name' => [$currentStats->has_customer, $currentStats->total_calls],
    'Latency' => [$currentStats->has_latency, $currentStats->total_calls],
    'Analysis JSON' => [$currentStats->has_analysis, $currentStats->total_calls]
];

foreach ($metrics as $name => [$count, $total]) {
    $percentage = round(($count / $total) * 100, 1);
    $status = $percentage >= 90 ? "🟢" : ($percentage >= 70 ? "🟡" : "🔴");
    echo sprintf("%-15s: %s %3d/%3d (%5.1f%%)\n", $name, $status, $count, $total, $percentage);
}

// 2. RECENT CALLS ANALYSIS
echo "\n📅 RECENT CALLS (LETZTE 24H)\n";
echo str_repeat("-", 50) . "\n";

$recentStats = DB::selectOne("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN user_sentiment IS NOT NULL THEN 1 ELSE 0 END) as has_sentiment,
        SUM(CASE WHEN agent_name IS NOT NULL THEN 1 ELSE 0 END) as has_agent,
        SUM(CASE WHEN call_successful IS NOT NULL THEN 1 ELSE 0 END) as has_success,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as has_cost
    FROM calls
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
");

if ($recentStats->total_calls > 0) {
    $recentMetrics = [
        'Sentiment' => [$recentStats->has_sentiment, $recentStats->total_calls],
        'Agent Info' => [$recentStats->has_agent, $recentStats->total_calls],
        'Call Success' => [$recentStats->has_success, $recentStats->total_calls],
        'Cost Data' => [$recentStats->has_cost, $recentStats->total_calls]
    ];
    
    foreach ($recentMetrics as $name => [$count, $total]) {
        $percentage = round(($count / $total) * 100, 1);
        $status = $percentage >= 90 ? "🟢" : ($percentage >= 70 ? "🟡" : "🔴");
        echo sprintf("%-15s: %s %3d/%3d (%5.1f%%)\n", $name, $status, $count, $total, $percentage);
    }
} else {
    echo "Keine Calls in den letzten 24 Stunden\n";
}

// 3. INCOMPLETE CALLS
echo "\n⚠️ UNVOLLSTÄNDIGE CALLS\n";
echo str_repeat("-", 50) . "\n";

$incompleteCalls = DB::select("
    SELECT 
        id,
        call_id,
        created_at,
        CASE WHEN user_sentiment IS NULL THEN 'Missing Sentiment' ELSE '' END as missing_sentiment,
        CASE WHEN agent_name IS NULL THEN 'Missing Agent' ELSE '' END as missing_agent,
        CASE WHEN call_successful IS NULL THEN 'Missing Success' ELSE '' END as missing_success,
        CASE WHEN cost IS NULL THEN 'Missing Cost' ELSE '' END as missing_cost
    FROM calls 
    WHERE (user_sentiment IS NULL OR agent_name IS NULL OR call_successful IS NULL OR cost IS NULL)
      AND call_id LIKE 'call_%'
    ORDER BY created_at DESC 
    LIMIT 10
");

if (!empty($incompleteCalls)) {
    echo "Top 10 unvollständige Calls:\n";
    foreach ($incompleteCalls as $call) {
        $issues = array_filter([
            $call->missing_sentiment,
            $call->missing_agent,
            $call->missing_success,
            $call->missing_cost
        ]);
        
        echo sprintf("ID %3d: %s (%s)\n", 
            $call->id, 
            $call->call_id, 
            implode(', ', $issues)
        );
    }
} else {
    echo "✅ Alle Calls sind vollständig!\n";
}

// 4. WEBHOOK HEALTH
echo "\n🔗 WEBHOOK HEALTH\n";
echo str_repeat("-", 50) . "\n";

$webhookStats = DB::selectOne("
    SELECT 
        COUNT(*) as total_recent,
        SUM(CASE WHEN webhook_data IS NOT NULL AND webhook_data != '' THEN 1 ELSE 0 END) as has_webhook,
        SUM(CASE WHEN raw_data IS NOT NULL AND raw_data != '' THEN 1 ELSE 0 END) as has_raw
    FROM calls
    WHERE created_at >= NOW() - INTERVAL 7 DAY
");

if ($webhookStats->total_recent > 0) {
    $webhookPercentage = round(($webhookStats->has_webhook / $webhookStats->total_recent) * 100, 1);
    $rawPercentage = round(($webhookStats->has_raw / $webhookStats->total_recent) * 100, 1);
    
    echo "Webhook Data (7 Tage): {$webhookStats->has_webhook}/{$webhookStats->total_recent} ({$webhookPercentage}%)\n";
    echo "Raw Data (7 Tage): {$webhookStats->has_raw}/{$webhookStats->total_recent} ({$rawPercentage}%)\n";
    
    if ($webhookPercentage < 90) {
        echo "⚠️ WARNUNG: Webhook-Verarbeitung unter 90%\n";
    }
} else {
    echo "Keine Calls in den letzten 7 Tagen\n";
}

// 5. RECOMMENDATIONS
echo "\n💡 EMPFEHLUNGEN\n";
echo str_repeat("-", 50) . "\n";

$recommendations = [];

// Check sentiment coverage
$sentimentCoverage = round(($currentStats->has_sentiment / $currentStats->total_calls) * 100, 1);
if ($sentimentCoverage < 80) {
    $recommendations[] = "Sentiment Coverage niedrig ($sentimentCoverage%) - Retell API Sync empfohlen";
}

// Check recent calls quality
if ($recentStats->total_calls > 0) {
    $recentSentiment = round(($recentStats->has_sentiment / $recentStats->total_calls) * 100, 1);
    if ($recentSentiment < 90) {
        $recommendations[] = "Neue Calls haben niedrige Datenqualität ($recentSentiment%) - Webhook prüfen";
    }
}

// Check webhook health
if ($webhookStats->total_recent > 0) {
    $webhookHealth = round(($webhookStats->has_webhook / $webhookStats->total_recent) * 100, 1);
    if ($webhookHealth < 80) {
        $recommendations[] = "Webhook-Verarbeitung problematisch ($webhookHealth%) - RetellWebhookWorkingController prüfen";
    }
}

// Check for old calls
$oldCallsCount = DB::selectOne("SELECT COUNT(*) as count FROM calls WHERE user_sentiment IS NULL AND call_id LIKE 'call_%'")->count;
if ($oldCallsCount > 10) {
    $recommendations[] = "$oldCallsCount alte Calls ohne Sentiment-Daten - Batch-Reparatur empfohlen";
}

if (empty($recommendations)) {
    echo "✅ Alle Systeme funktionieren optimal!\n";
} else {
    foreach ($recommendations as $i => $rec) {
        echo ($i + 1) . ". $rec\n";
    }
}

// 6. SUGGESTED ACTIONS
echo "\n🔧 VORGESCHLAGENE AKTIONEN\n";
echo str_repeat("-", 50) . "\n";

if ($sentimentCoverage < 90) {
    echo "1. Datenreparatur ausführen:\n";
    echo "   php /var/www/api-gateway/execute-safe-data-repair.php\n\n";
}

if ($recentStats->total_calls > 0 && $recentSentiment < 90) {
    echo "2. Webhook-System prüfen:\n";
    echo "   tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell\n\n";
}

echo "3. Monitoring automatisieren:\n";
echo "   # In Crontab hinzufügen:\n";
echo "   0 */6 * * * php /var/www/api-gateway/data-quality-monitor.php >> /var/log/data-quality.log\n\n";

// 7. PERFORMANCE SCORE
echo "🎯 DATENQUALITÄTS-SCORE\n";
echo str_repeat("-", 50) . "\n";

$scores = [
    'Sentiment' => min(100, $sentimentCoverage),
    'Agent' => min(100, round(($currentStats->has_agent / $currentStats->total_calls) * 100, 1)),
    'Cost' => min(100, round(($currentStats->has_cost / $currentStats->total_calls) * 100, 1)),
    'Duration' => min(100, round(($currentStats->has_duration / $currentStats->total_calls) * 100, 1))
];

$overallScore = round(array_sum($scores) / count($scores), 1);
$scoreStatus = $overallScore >= 90 ? "🟢 EXCELLENT" : ($overallScore >= 70 ? "🟡 GOOD" : "🔴 NEEDS WORK");

echo "Overall Score: $scoreStatus ($overallScore/100)\n";
foreach ($scores as $metric => $score) {
    $status = $score >= 90 ? "🟢" : ($score >= 70 ? "🟡" : "🔴");
    echo sprintf("  %-10s: %s %5.1f/100\n", $metric, $status, $score);
}

echo "\n✅ Data Quality Monitor completed\n";