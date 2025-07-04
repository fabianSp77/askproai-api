<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CALL DATA COMPLETENESS ANALYSIS ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Gesamtübersicht
echo "1. GESAMTÜBERSICHT\n";
echo str_repeat("-", 50) . "\n";

$totalCalls = DB::table('calls')->count();
$callsLast24h = DB::table('calls')->where('created_at', '>=', now()->subDay())->count();
$callsLast7d = DB::table('calls')->where('created_at', '>=', now()->subDays(7))->count();

echo "Gesamt Calls: $totalCalls\n";
echo "Calls letzte 24h: $callsLast24h\n";
echo "Calls letzte 7 Tage: $callsLast7d\n\n";

// 2. Duration Analysis
echo "2. DURATION DATA ANALYSIS\n";
echo str_repeat("-", 50) . "\n";

$durationIssues = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN duration_sec IS NULL OR duration_sec = 0 THEN 1 ELSE 0 END) as missing_duration,
        SUM(CASE WHEN start_timestamp IS NOT NULL AND end_timestamp IS NOT NULL 
                 AND TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) != COALESCE(duration_sec, 0) 
                 THEN 1 ELSE 0 END) as duration_mismatch,
        SUM(CASE WHEN start_timestamp IS NULL OR end_timestamp IS NULL THEN 1 ELSE 0 END) as missing_timestamps
    FROM calls
")[0];

echo "Total Calls: " . $durationIssues->total_calls . "\n";
echo "Missing Duration (0 or NULL): " . $durationIssues->missing_duration . "\n";
echo "Duration Mismatch: " . $durationIssues->duration_mismatch . "\n";
echo "Missing Timestamps: " . $durationIssues->missing_timestamps . "\n\n";

// 3. Analysis Data Completeness
echo "3. ANALYSIS DATA COMPLETENESS\n";
echo str_repeat("-", 50) . "\n";

$analysisData = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN user_sentiment IS NULL THEN 1 ELSE 0 END) as missing_sentiment,
        SUM(CASE WHEN call_successful IS NULL THEN 1 ELSE 0 END) as missing_success,
        SUM(CASE WHEN agent_name IS NULL THEN 1 ELSE 0 END) as missing_agent,
        SUM(CASE WHEN extracted_name IS NULL THEN 1 ELSE 0 END) as missing_name,
        SUM(CASE WHEN cost IS NULL THEN 1 ELSE 0 END) as missing_cost,
        SUM(CASE WHEN analysis IS NULL OR analysis = '' THEN 1 ELSE 0 END) as missing_analysis_json
    FROM calls
")[0];

echo "Missing User Sentiment: " . $analysisData->missing_sentiment . " (" . round(($analysisData->missing_sentiment / $totalCalls) * 100, 1) . "%)\n";
echo "Missing Call Successful: " . $analysisData->missing_success . " (" . round(($analysisData->missing_success / $totalCalls) * 100, 1) . "%)\n";
echo "Missing Agent Name: " . $analysisData->missing_agent . " (" . round(($analysisData->missing_agent / $totalCalls) * 100, 1) . "%)\n";
echo "Missing Extracted Name: " . $analysisData->missing_name . " (" . round(($analysisData->missing_name / $totalCalls) * 100, 1) . "%)\n";
echo "Missing Cost: " . $analysisData->missing_cost . " (" . round(($analysisData->missing_cost / $totalCalls) * 100, 1) . "%)\n";
echo "Missing Analysis JSON: " . $analysisData->missing_analysis_json . " (" . round(($analysisData->missing_analysis_json / $totalCalls) * 100, 1) . "%)\n\n";

// 4. Recent vs Old Calls
echo "4. DATA QUALITY BY AGE\n";
echo str_repeat("-", 50) . "\n";

$dataQualityByAge = DB::select("
    SELECT 
        CASE 
            WHEN created_at >= NOW() - INTERVAL 24 HOUR THEN 'Last 24h'
            WHEN created_at >= NOW() - INTERVAL 3 DAY THEN 'Last 3 days'
            WHEN created_at >= NOW() - INTERVAL 7 DAY THEN 'Last 7 days'
            ELSE 'Older than 7 days'
        END as age_group,
        COUNT(*) as total_calls,
        AVG(CASE WHEN user_sentiment IS NOT NULL THEN 1 ELSE 0 END) * 100 as sentiment_completeness,
        AVG(CASE WHEN duration_sec > 0 THEN 1 ELSE 0 END) * 100 as duration_completeness,
        AVG(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) * 100 as cost_completeness
    FROM calls
    GROUP BY 
        CASE 
            WHEN created_at >= NOW() - INTERVAL 24 HOUR THEN 'Last 24h'
            WHEN created_at >= NOW() - INTERVAL 3 DAY THEN 'Last 3 days'
            WHEN created_at >= NOW() - INTERVAL 7 DAY THEN 'Last 7 days'
            ELSE 'Older than 7 days'
        END
    ORDER BY MIN(created_at) DESC
");

foreach ($dataQualityByAge as $row) {
    echo $row->age_group . ":\n";
    echo "  Calls: " . $row->total_calls . "\n";
    echo "  Sentiment: " . round($row->sentiment_completeness, 1) . "%\n";
    echo "  Duration: " . round($row->duration_completeness, 1) . "%\n";
    echo "  Cost: " . round($row->cost_completeness, 1) . "%\n\n";
}

// 5. Identify problematic calls
echo "5. PROBLEMATIC CALLS SAMPLE\n";
echo str_repeat("-", 50) . "\n";

$problematicCalls = DB::select("
    SELECT 
        id,
        call_id,
        created_at,
        CASE WHEN duration_sec IS NULL OR duration_sec = 0 THEN 'MISSING_DURATION' ELSE 'OK' END as duration_status,
        CASE WHEN user_sentiment IS NULL THEN 'MISSING_SENTIMENT' ELSE 'OK' END as sentiment_status,
        CASE WHEN cost IS NULL THEN 'MISSING_COST' ELSE 'OK' END as cost_status
    FROM calls 
    WHERE (duration_sec IS NULL OR duration_sec = 0 OR user_sentiment IS NULL OR cost IS NULL)
    ORDER BY created_at DESC 
    LIMIT 10
");

echo "Top 10 recent calls with missing data:\n";
foreach ($problematicCalls as $call) {
    echo "ID: " . $call->id . " | " . $call->call_id . " | " . $call->created_at . "\n";
    echo "  Issues: ";
    $issues = [];
    if ($call->duration_status != 'OK') $issues[] = 'Duration';
    if ($call->sentiment_status != 'OK') $issues[] = 'Sentiment';
    if ($call->cost_status != 'OK') $issues[] = 'Cost';
    echo implode(', ', $issues) . "\n\n";
}

// 6. Webhook Data Analysis
echo "6. WEBHOOK DATA ANALYSIS\n";
echo str_repeat("-", 50) . "\n";

$webhookData = DB::select("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN webhook_data IS NOT NULL AND webhook_data != '' THEN 1 ELSE 0 END) as has_webhook_data,
        SUM(CASE WHEN raw_data IS NOT NULL AND raw_data != '' THEN 1 ELSE 0 END) as has_raw_data,
        AVG(CASE WHEN webhook_data IS NOT NULL AND webhook_data != '' THEN 1 ELSE 0 END) * 100 as webhook_completeness
    FROM calls
")[0];

echo "Calls with Webhook Data: " . $webhookData->has_webhook_data . "/" . $webhookData->total_calls . " (" . round($webhookData->webhook_completeness, 1) . "%)\n";
echo "Calls with Raw Data: " . $webhookData->has_raw_data . "/" . $webhookData->total_calls . "\n\n";

// 7. Recovery Strategy Recommendations
echo "7. RECOVERY STRATEGY RECOMMENDATIONS\n";
echo str_repeat("-", 50) . "\n";

$recoverableCalls = DB::select("
    SELECT 
        COUNT(*) as calls_with_call_id,
        SUM(CASE WHEN start_timestamp IS NOT NULL AND end_timestamp IS NOT NULL THEN 1 ELSE 0 END) as fixable_duration,
        SUM(CASE WHEN (duration_sec IS NULL OR duration_sec = 0 OR user_sentiment IS NULL) AND call_id IS NOT NULL THEN 1 ELSE 0 END) as retell_api_recoverable
    FROM calls
    WHERE call_id IS NOT NULL AND call_id != ''
")[0];

echo "Calls mit Call-ID (API-verfügbar): " . $recoverableCalls->calls_with_call_id . "\n";
echo "Duration reparierbar (via timestamps): " . $recoverableCalls->fixable_duration . "\n";
echo "Retell API recoverable: " . $recoverableCalls->retell_api_recoverable . "\n\n";

echo "EMPFOHLENE AKTIONEN:\n";
echo "1. Duration Fix: Berechne fehlende duration_sec aus timestamps\n";
echo "2. Retell API Sync: Lade fehlende Analysedaten für Calls mit call_id nach\n";
echo "3. Webhook Replay: Simuliere Webhook-Events für unvollständige Calls\n";
echo "4. Data Validation: Implementiere regelmäßige Completeness-Checks\n\n";

echo "✅ Analysis completed successfully\n";