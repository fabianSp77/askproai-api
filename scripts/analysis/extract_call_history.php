#!/usr/bin/env php
<?php

/**
 * PHASE 1.1: Historical Call Data Extraction
 *
 * Extracts ALL calls from the last 7 days from the database
 * Provides comprehensive analysis of call patterns, success rates, and agent versions
 *
 * Output:
 * - JSON: Full call data for programmatic analysis
 * - CSV: Excel-compatible format for manual analysis
 * - Summary Report: High-level statistics
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1.1: Historical Call Data Extraction (Last 7 Days)     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$startDate = Carbon::now()->subDays(7);
$endDate = Carbon::now();

echo "📅 Extraction Period: {$startDate->format('Y-m-d H:i')} → {$endDate->format('Y-m-d H:i')}\n\n";

// ========================================================================
// 1. Extract All Call Sessions
// ========================================================================

echo "🔍 Step 1: Extracting call sessions from database...\n";

$calls = RetellCallSession::where('started_at', '>=', $startDate)
    ->orderBy('started_at', 'desc')
    ->with(['functionTraces', 'errors'])
    ->get();

echo "✅ Found {$calls->count()} calls in the last 7 days\n\n";

// ========================================================================
// 2. Analyze Call Distribution
// ========================================================================

echo "📊 Step 2: Analyzing call distribution...\n\n";

// By Status
$byStatus = $calls->groupBy('call_status');
echo "By Status:\n";
foreach ($byStatus as $status => $group) {
    $count = $group->count();
    $percentage = round(($count / $calls->count()) * 100, 1);
    echo "  - {$status}: {$count} ({$percentage}%)\n";
}
echo "\n";

// By Agent Version
$byVersion = $calls->groupBy('agent_version');
echo "By Agent Version:\n";
foreach ($byVersion->sortKeys() as $version => $group) {
    $count = $group->count();
    $percentage = round(($count / $calls->count()) * 100, 1);
    echo "  - V{$version}: {$count} ({$percentage}%)\n";
}
echo "\n";

// By Disconnect Reason
$byDisconnect = $calls->groupBy('disconnection_reason')->filter(fn($g) => $g->first()->disconnection_reason !== null);
echo "By Disconnect Reason:\n";
foreach ($byDisconnect as $reason => $group) {
    $count = $group->count();
    $percentage = round(($count / $calls->count()) * 100, 1);
    echo "  - {$reason}: {$count} ({$percentage}%)\n";
}
echo "\n";

// ========================================================================
// 3. Function Call Analysis
// ========================================================================

echo "🔧 Step 3: Analyzing function calls across all calls...\n\n";

$functionStats = [];
$totalFunctionCalls = 0;

foreach ($calls as $call) {
    foreach ($call->functionTraces as $trace) {
        $funcName = $trace->function_name;

        if (!isset($functionStats[$funcName])) {
            $functionStats[$funcName] = [
                'total_calls' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'pending_count' => 0,
                'total_duration_ms' => 0,
                'agent_versions' => [],
            ];
        }

        $functionStats[$funcName]['total_calls']++;
        $totalFunctionCalls++;

        if ($trace->status === 'success') {
            $functionStats[$funcName]['success_count']++;
        } elseif ($trace->status === 'error') {
            $functionStats[$funcName]['error_count']++;
        } elseif ($trace->status === 'pending') {
            $functionStats[$funcName]['pending_count']++;
        }

        if ($trace->duration_ms) {
            $functionStats[$funcName]['total_duration_ms'] += $trace->duration_ms;
        }

        // Track which agent versions called this function
        $agentVersion = $call->agent_version ?? 'unknown';
        if (!in_array($agentVersion, $functionStats[$funcName]['agent_versions'])) {
            $functionStats[$funcName]['agent_versions'][] = $agentVersion;
        }
    }
}

echo "Function Call Summary:\n";
echo str_repeat('-', 80) . "\n";
printf("%-30s %8s %8s %8s %12s %15s\n",
    "Function", "Total", "Success", "Errors", "Avg Duration", "Agent Versions");
echo str_repeat('-', 80) . "\n";

foreach ($functionStats as $funcName => $stats) {
    $avgDuration = $stats['total_calls'] > 0
        ? round($stats['total_duration_ms'] / $stats['total_calls'])
        : 0;

    $successRate = $stats['total_calls'] > 0
        ? round(($stats['success_count'] / $stats['total_calls']) * 100)
        : 0;

    $versions = implode(', ', array_map(fn($v) => "V{$v}", $stats['agent_versions']));

    printf("%-30s %8d %7d%% %8d %9dms %15s\n",
        substr($funcName, 0, 30),
        $stats['total_calls'],
        $successRate,
        $stats['error_count'],
        $avgDuration,
        substr($versions, 0, 15)
    );
}

echo str_repeat('-', 80) . "\n";
echo "Total Function Calls: {$totalFunctionCalls}\n\n";

// ========================================================================
// 4. Critical Issue Detection
// ========================================================================

echo "🚨 Step 4: Detecting critical issues...\n\n";

$issues = [];

// Issue 1: Calls without check_availability
$callsWithoutCheckAvailability = $calls->filter(function($call) {
    return $call->functionTraces->where('function_name', 'check_availability')->isEmpty()
        && $call->functionTraces->where('function_name', 'check_availability_v2')->isEmpty();
});

if ($callsWithoutCheckAvailability->count() > 0) {
    $percentage = round(($callsWithoutCheckAvailability->count() / $calls->count()) * 100, 1);
    echo "❌ Issue 1: Calls WITHOUT check_availability\n";
    echo "   Count: {$callsWithoutCheckAvailability->count()} ({$percentage}%)\n";
    echo "   Agent Versions: " . implode(', ',
        $callsWithoutCheckAvailability->pluck('agent_version')->unique()->sort()->map(fn($v) => "V{$v}")->toArray()
    ) . "\n\n";

    $issues[] = [
        'type' => 'missing_check_availability',
        'count' => $callsWithoutCheckAvailability->count(),
        'percentage' => $percentage,
        'call_ids' => $callsWithoutCheckAvailability->pluck('call_id')->toArray(),
    ];
}

// Issue 2: Calls with errors
$callsWithErrors = $calls->where('error_count', '>', 0);
if ($callsWithErrors->count() > 0) {
    $percentage = round(($callsWithErrors->count() / $calls->count()) * 100, 1);
    echo "❌ Issue 2: Calls WITH Errors\n";
    echo "   Count: {$callsWithErrors->count()} ({$percentage}%)\n";
    echo "   Total Errors: {$callsWithErrors->sum('error_count')}\n\n";

    $issues[] = [
        'type' => 'calls_with_errors',
        'count' => $callsWithErrors->count(),
        'percentage' => $percentage,
        'total_errors' => $callsWithErrors->sum('error_count'),
    ];
}

// Issue 3: User hangups (potential UX issues)
$userHangups = $calls->where('disconnection_reason', 'user_hangup');
if ($userHangups->count() > 0) {
    $percentage = round(($userHangups->count() / $calls->count()) * 100, 1);
    echo "⚠️  Issue 3: User Hangups\n";
    echo "   Count: {$userHangups->count()} ({$percentage}%)\n";
    echo "   Avg Duration: " . round($userHangups->avg('duration_ms') / 1000, 1) . "s\n\n";

    $issues[] = [
        'type' => 'user_hangups',
        'count' => $userHangups->count(),
        'percentage' => $percentage,
        'avg_duration_ms' => $userHangups->avg('duration_ms'),
    ];
}

// ========================================================================
// 5. Generate Outputs
// ========================================================================

echo "💾 Step 5: Generating output files...\n\n";

$outputDir = '/var/www/api-gateway/storage/analysis';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// 5.1 JSON Output (Full Data)
$jsonData = [
    'metadata' => [
        'generated_at' => Carbon::now()->toIso8601String(),
        'period_start' => $startDate->toIso8601String(),
        'period_end' => $endDate->toIso8601String(),
        'total_calls' => $calls->count(),
    ],
    'calls' => $calls->map(function($call) {
        return [
            'call_id' => $call->call_id,
            'agent_version' => $call->agent_version,
            'started_at' => $call->started_at?->toIso8601String(),
            'duration_ms' => $call->duration_ms,
            'call_status' => $call->call_status,
            'disconnection_reason' => $call->disconnection_reason,
            'function_call_count' => $call->function_call_count,
            'error_count' => $call->error_count,
            'functions' => $call->functionTraces->map(function($trace) {
                return [
                    'name' => $trace->function_name,
                    'status' => $trace->status,
                    'duration_ms' => $trace->duration_ms,
                ];
            })->toArray(),
        ];
    })->toArray(),
    'summary' => [
        'by_status' => $byStatus->map->count()->toArray(),
        'by_agent_version' => $byVersion->map->count()->toArray(),
        'function_stats' => $functionStats,
        'issues' => $issues,
    ],
];

$jsonFile = "{$outputDir}/call_history_" . Carbon::now()->format('Y-m-d_His') . ".json";
file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));
echo "✅ JSON saved: {$jsonFile}\n";

// 5.2 CSV Output (Excel-compatible)
$csvFile = "{$outputDir}/call_history_" . Carbon::now()->format('Y-m-d_His') . ".csv";
$csv = fopen($csvFile, 'w');

// CSV Headers
fputcsv($csv, [
    'Call ID',
    'Agent Version',
    'Started At',
    'Duration (s)',
    'Status',
    'Disconnect Reason',
    'Function Calls',
    'Errors',
    'Has check_availability',
]);

// CSV Data
foreach ($calls as $call) {
    $hasCheckAvailability = $call->functionTraces
        ->whereIn('function_name', ['check_availability', 'check_availability_v2'])
        ->isNotEmpty() ? 'Yes' : 'No';

    fputcsv($csv, [
        $call->call_id,
        $call->agent_version ?? 'N/A',
        $call->started_at?->format('Y-m-d H:i:s'),
        $call->duration_ms ? round($call->duration_ms / 1000, 1) : 'N/A',
        $call->call_status,
        $call->disconnection_reason ?? 'N/A',
        $call->function_call_count,
        $call->error_count,
        $hasCheckAvailability,
    ]);
}

fclose($csv);
echo "✅ CSV saved: {$csvFile}\n";

// 5.3 Summary Report (Markdown)
$reportFile = "{$outputDir}/call_history_summary_" . Carbon::now()->format('Y-m-d_His') . ".md";
ob_start();
?>
# Call History Analysis Summary

**Generated**: <?= Carbon::now()->format('Y-m-d H:i:s') ?>
**Period**: <?= $startDate->format('Y-m-d') ?> → <?= $endDate->format('Y-m-d') ?>
**Total Calls**: <?= $calls->count() ?>

## Distribution Analysis

### By Status
<?php foreach ($byStatus as $status => $group): ?>
- **<?= $status ?>**: <?= $group->count() ?> (<?= round(($group->count() / $calls->count()) * 100, 1) ?>%)
<?php endforeach; ?>

### By Agent Version
<?php foreach ($byVersion->sortKeys() as $version => $group): ?>
- **V<?= $version ?>**: <?= $group->count() ?> (<?= round(($group->count() / $calls->count()) * 100, 1) ?>%)
<?php endforeach; ?>

## Function Call Statistics

| Function | Total Calls | Success Rate | Errors | Avg Duration | Agent Versions |
|----------|------------|--------------|--------|--------------|----------------|
<?php foreach ($functionStats as $funcName => $stats): ?>
<?php
    $successRate = $stats['total_calls'] > 0 ? round(($stats['success_count'] / $stats['total_calls']) * 100) : 0;
    $avgDuration = $stats['total_calls'] > 0 ? round($stats['total_duration_ms'] / $stats['total_calls']) : 0;
    $versions = implode(', ', array_map(fn($v) => "V{$v}", $stats['agent_versions']));
?>
| <?= $funcName ?> | <?= $stats['total_calls'] ?> | <?= $successRate ?>% | <?= $stats['error_count'] ?> | <?= $avgDuration ?>ms | <?= $versions ?> |
<?php endforeach; ?>

## Critical Issues

<?php if (count($issues) > 0): ?>
<?php foreach ($issues as $issue): ?>
### Issue: <?= $issue['type'] ?>
- **Count**: <?= $issue['count'] ?> (<?= $issue['percentage'] ?>%)
<?php if (isset($issue['call_ids']) && count($issue['call_ids']) <= 10): ?>
- **Affected Calls**: <?= implode(', ', array_slice($issue['call_ids'], 0, 10)) ?>
<?php endif; ?>

<?php endforeach; ?>
<?php else: ?>
No critical issues detected.
<?php endif; ?>

## Next Steps

1. Investigate calls without check_availability (Agent Versions: <?= implode(', ', $callsWithoutCheckAvailability->pluck('agent_version')->unique()->sort()->map(fn($v) => "V{$v}")->toArray()) ?>)
2. Analyze error patterns in calls with errors
3. Review user hangup patterns for UX improvements
4. Compare flow configurations across agent versions

---

*Generated by Phase 1.1: Historical Call Data Extraction*
<?php
$report = ob_get_clean();
file_put_contents($reportFile, $report);
echo "✅ Report saved: {$reportFile}\n\n";

// ========================================================================
// 6. Final Summary
// ========================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1.1 COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Analysis Results:\n";
echo "   - Total Calls Analyzed: {$calls->count()}\n";
echo "   - Agent Versions Found: " . $byVersion->count() . "\n";
echo "   - Unique Functions Called: " . count($functionStats) . "\n";
echo "   - Total Function Calls: {$totalFunctionCalls}\n";
echo "   - Critical Issues Detected: " . count($issues) . "\n\n";

echo "📁 Output Files:\n";
echo "   - JSON: {$jsonFile}\n";
echo "   - CSV: {$csvFile}\n";
echo "   - Report: {$reportFile}\n\n";

echo "✅ Phase 1.1 extraction complete! Proceed to Phase 1.2 for pattern analysis.\n";
