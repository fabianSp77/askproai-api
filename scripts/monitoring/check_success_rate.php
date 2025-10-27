#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "CHECK_AVAILABILITY SUCCESS RATE MONITORING\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get timeframe from argument (default: last 24 hours)
$hours = isset($argv[1]) ? (int)$argv[1] : 24;
$since = now()->subHours($hours);

echo "Analyzing calls from last {$hours} hours (since {$since->format('Y-m-d H:i:s')})\n\n";

// Get all calls in timeframe
$calls = \App\Models\RetellCallSession::where('created_at', '>=', $since)
    ->orderBy('created_at', 'desc')
    ->get();

if ($calls->isEmpty()) {
    echo "❌ No calls found in the last {$hours} hours\n\n";
    exit(0);
}

echo "Total calls: {$calls->count()}\n\n";

// Analyze each call
$stats = [
    'total' => $calls->count(),
    'with_initialize' => 0,
    'with_check_availability' => 0,
    'with_book_appointment' => 0,
    'completed' => 0,
    'failed' => 0,
    'in_progress' => 0,
    'total_duration' => 0,
    'calls_with_functions' => 0,
];

$callDetails = [];

foreach ($calls as $call) {
    $functions = $call->functionTraces()->pluck('function_name')->toArray();

    $hasInitialize = false;
    $hasCheckAvail = false;
    $hasBookAppt = false;

    foreach ($functions as $funcName) {
        if (str_contains($funcName, 'initialize')) $hasInitialize = true;
        if (str_contains($funcName, 'check_availability')) $hasCheckAvail = true;
        if (str_contains($funcName, 'book_appointment')) $hasBookAppt = true;
    }

    if ($hasInitialize) $stats['with_initialize']++;
    if ($hasCheckAvail) $stats['with_check_availability']++;
    if ($hasBookAppt) $stats['with_book_appointment']++;

    if (!empty($functions)) $stats['calls_with_functions']++;

    if ($call->call_status === 'completed') $stats['completed']++;
    if ($call->call_status === 'failed') $stats['failed']++;
    if ($call->call_status === 'in_progress') $stats['in_progress']++;

    $stats['total_duration'] += $call->duration ?? 0;

    $callDetails[] = [
        'call_id' => $call->call_id,
        'created_at' => $call->created_at->format('H:i:s'),
        'status' => $call->call_status,
        'duration' => $call->duration ?? 0,
        'has_check_avail' => $hasCheckAvail,
        'has_booking' => $hasBookAppt,
        'function_count' => count($functions),
    ];
}

// Calculate percentages
$initializeRate = $stats['total'] > 0 ? round(($stats['with_initialize'] / $stats['total']) * 100, 1) : 0;
$checkAvailRate = $stats['total'] > 0 ? round(($stats['with_check_availability'] / $stats['total']) * 100, 1) : 0;
$bookApptRate = $stats['total'] > 0 ? round(($stats['with_book_appointment'] / $stats['total']) * 100, 1) : 0;
$completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
$avgDuration = $stats['total'] > 0 ? round($stats['total_duration'] / $stats['total'], 1) : 0;

echo "═══════════════════════════════════════════════════════════\n";
echo "FUNCTION CALL RATES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "initialize_call:      {$stats['with_initialize']}/{$stats['total']} ({$initializeRate}%)";
if ($initializeRate >= 90) {
    echo " ✅\n";
} elseif ($initializeRate >= 70) {
    echo " ⚠️\n";
} else {
    echo " ❌\n";
}

echo "check_availability:   {$stats['with_check_availability']}/{$stats['total']} ({$checkAvailRate}%)";
if ($checkAvailRate >= 90) {
    echo " ✅ EXCELLENT!\n";
} elseif ($checkAvailRate >= 70) {
    echo " ⚠️  GOOD\n";
} elseif ($checkAvailRate >= 50) {
    echo " ⚠️  NEEDS IMPROVEMENT\n";
} else {
    echo " ❌ CRITICAL ISSUE\n";
}

echo "book_appointment:     {$stats['with_book_appointment']}/{$stats['total']} ({$bookApptRate}%)";
if ($bookApptRate >= 50) {
    echo " ✅\n";
} elseif ($bookApptRate >= 30) {
    echo " ⚠️\n";
} else {
    echo " ℹ️  (normal if users don't confirm)\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "CALL STATUS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Completed:     {$stats['completed']}/{$stats['total']} ({$completionRate}%)\n";
echo "Failed:        {$stats['failed']}/{$stats['total']}\n";
echo "In Progress:   {$stats['in_progress']}/{$stats['total']}\n";
echo "Avg Duration:  {$avgDuration} seconds\n";

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "RECENT CALLS DETAIL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach (array_slice($callDetails, 0, 10) as $detail) {
    $statusIcon = $detail['status'] === 'completed' ? '✅' : ($detail['status'] === 'failed' ? '❌' : '⏳');
    $checkIcon = $detail['has_check_avail'] ? '✅' : '❌';
    $bookIcon = $detail['has_booking'] ? '✅' : '  ';

    echo "{$statusIcon} {$detail['created_at']} | Duration: {$detail['duration']}s | ";
    echo "check_avail: {$checkIcon} | book: {$bookIcon} | ";
    echo "Functions: {$detail['function_count']}\n";
}

if (count($callDetails) > 10) {
    echo "\n... and " . (count($callDetails) - 10) . " more calls\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "VERDICT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($checkAvailRate >= 90) {
    echo "🎉 EXCELLENT! Fix is working perfectly!\n\n";
    echo "check_availability call rate: {$checkAvailRate}%\n";
    echo "This is a massive improvement from the previous 0%!\n\n";
    echo "Next steps:\n";
    echo "  - Continue monitoring over next few days\n";
    echo "  - Track user feedback\n";
    echo "  - Monitor booking conversion rate\n\n";
    exit(0);
} elseif ($checkAvailRate >= 70) {
    echo "✅ GOOD! Fix is mostly working\n\n";
    echo "check_availability call rate: {$checkAvailRate}%\n";
    echo "This is good, but there's room for improvement.\n\n";
    echo "Investigate:\n";
    echo "  - Calls that didn't trigger check_availability\n";
    echo "  - Are users hanging up before reaching that point?\n";
    echo "  - Check logs for any errors\n\n";
    exit(0);
} elseif ($checkAvailRate >= 50) {
    echo "⚠️  NEEDS IMPROVEMENT\n\n";
    echo "check_availability call rate: {$checkAvailRate}%\n";
    echo "Better than 0%, but still not where it should be.\n\n";
    echo "Action required:\n";
    echo "  - Verify V54 is actually published in Dashboard\n";
    echo "  - Check if phone mapping is correct\n";
    echo "  - Analyze calls that didn't trigger the function\n\n";
    exit(1);
} else {
    echo "❌ CRITICAL ISSUE\n\n";
    echo "check_availability call rate: {$checkAvailRate}%\n";
    echo "This is too low. Something is wrong.\n\n";
    echo "Immediate actions:\n";
    echo "  1. Verify V54 is published: php scripts/testing/verify_v54_ready.php\n";
    echo "  2. Check phone mapping in Dashboard\n";
    echo "  3. Check logs: tail -f storage/logs/laravel.log\n";
    echo "  4. Analyze latest call: php scripts/testing/check_latest_call_success.php\n\n";
    exit(1);
}
