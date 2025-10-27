#!/usr/bin/env php
<?php

/**
 * PHASE 1.2: Function Call Pattern Analysis
 *
 * Deep analysis of function call patterns across agent versions
 * Identifies which functions are called in which versions and why
 *
 * Key Questions:
 * 1. In which agent versions is check_availability NEVER called?
 * 2. What is the initialize_call response structure across versions?
 * 3. What is the timing/latency pattern for each function?
 * 4. Are there correlation patterns (e.g., initialize_call → customer_routing)?
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RetellCallSession;
use App\Models\RetellFunctionTrace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1.2: Function Call Pattern Analysis                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ========================================================================
// 1. Build Function × Agent Version Matrix
// ========================================================================

echo "🔍 Step 1: Building Function × Agent Version Matrix...\n\n";

$matrix = [];
$agentVersions = [];
$functionNames = [];

$calls = RetellCallSession::where('started_at', '>=', Carbon::now()->subDays(7))
    ->with('functionTraces')
    ->get();

foreach ($calls as $call) {
    $agentVersion = $call->agent_version ?? 'unknown';

    if (!isset($matrix[$agentVersion])) {
        $matrix[$agentVersion] = [];
        $agentVersions[] = $agentVersion;
    }

    foreach ($call->functionTraces as $trace) {
        $funcName = $trace->function_name;

        if (!in_array($funcName, $functionNames)) {
            $functionNames[] = $funcName;
        }

        if (!isset($matrix[$agentVersion][$funcName])) {
            $matrix[$agentVersion][$funcName] = [
                'call_count' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'total_duration_ms' => 0,
                'call_ids' => [],
            ];
        }

        $matrix[$agentVersion][$funcName]['call_count']++;
        if ($trace->status === 'success') {
            $matrix[$agentVersion][$funcName]['success_count']++;
        } elseif ($trace->status === 'error') {
            $matrix[$agentVersion][$funcName]['error_count']++;
        }
        $matrix[$agentVersion][$funcName]['total_duration_ms'] += $trace->duration_ms ?? 0;

        if (!in_array($call->call_id, $matrix[$agentVersion][$funcName]['call_ids'])) {
            $matrix[$agentVersion][$funcName]['call_ids'][] = $call->call_id;
        }
    }
}

sort($agentVersions);
sort($functionNames);

echo "Matrix Dimensions:\n";
echo "  - Agent Versions: " . count($agentVersions) . " (" . implode(', ', array_map(fn($v) => "V{$v}", $agentVersions)) . ")\n";
echo "  - Function Names: " . count($functionNames) . "\n\n";

// Display Matrix
echo "Function Call Matrix:\n";
echo str_repeat('=', 100) . "\n";

printf("%-35s", "Function Name");
foreach ($agentVersions as $version) {
    printf(" | V%-3s", $version);
}
echo "\n";
echo str_repeat('=', 100) . "\n";

foreach ($functionNames as $funcName) {
    printf("%-35s", substr($funcName, 0, 35));

    foreach ($agentVersions as $version) {
        $count = $matrix[$version][$funcName]['call_count'] ?? 0;
        if ($count > 0) {
            $successRate = round(($matrix[$version][$funcName]['success_count'] / $count) * 100);
            printf(" | %2d%%", $successRate);
        } else {
            printf(" | --");
        }
    }
    echo "\n";
}
echo str_repeat('=', 100) . "\n\n";

// ========================================================================
// 2. Critical Finding: check_availability Analysis
// ========================================================================

echo "🚨 Step 2: check_availability Deep Dive...\n\n";

$checkAvailabilityVersions = [];
$noCheckAvailabilityVersions = [];

foreach ($agentVersions as $version) {
    $hasCheckAvailability = isset($matrix[$version]['check_availability']) ||
                           isset($matrix[$version]['check_availability_v2']);

    if ($hasCheckAvailability) {
        $checkAvailabilityVersions[] = $version;
    } else {
        $noCheckAvailabilityVersions[] = $version;
    }
}

echo "check_availability Call Distribution:\n";
echo "  ✅ Versions WITH check_availability: " .
    (count($checkAvailabilityVersions) > 0 ? implode(', ', array_map(fn($v) => "V{$v}", $checkAvailabilityVersions)) : 'NONE') . "\n";
echo "  ❌ Versions WITHOUT check_availability: " .
    (count($noCheckAvailabilityVersions) > 0 ? implode(', ', array_map(fn($v) => "V{$v}", $noCheckAvailabilityVersions)) : 'NONE') . "\n\n";

if (count($noCheckAvailabilityVersions) > 0) {
    echo "🔴 CRITICAL FINDING: The following agent versions NEVER called check_availability:\n";
    foreach ($noCheckAvailabilityVersions as $version) {
        $callCount = $calls->where('agent_version', $version)->count();
        echo "  - V{$version}: {$callCount} calls\n";
    }
    echo "\n";
}

// ========================================================================
// 3. initialize_call Response Structure Analysis
// ========================================================================

echo "🔍 Step 3: initialize_call Response Analysis...\n\n";

$initializeTraces = RetellFunctionTrace::where('function_name', 'initialize_call')
    ->whereHas('callSession', function($query) {
        $query->where('started_at', '>=', Carbon::now()->subDays(7));
    })
    ->with('callSession')
    ->get();

echo "initialize_call Statistics:\n";
echo "  Total Calls: {$initializeTraces->count()}\n\n";

$responsePatterns = [];

foreach ($initializeTraces as $trace) {
    $agentVersion = $trace->callSession->agent_version ?? 'unknown';
    $output = $trace->output_result ?? [];

    // Analyze response structure
    $hasCustomer = isset($output['customer']) || isset($output['data']['customer']);
    $hasMessage = isset($output['message']) || isset($output['data']['message']);
    $hasError = isset($output['error']) || isset($output['data']['error']);
    $isSuccess = isset($output['success']) && $output['success'] === true;

    $pattern = sprintf(
        "customer:%s|message:%s|error:%s|success:%s",
        $hasCustomer ? 'yes' : 'no',
        $hasMessage ? 'yes' : 'no',
        $hasError ? 'yes' : 'no',
        $isSuccess ? 'yes' : 'no'
    );

    if (!isset($responsePatterns[$agentVersion])) {
        $responsePatterns[$agentVersion] = [];
    }

    if (!isset($responsePatterns[$agentVersion][$pattern])) {
        $responsePatterns[$agentVersion][$pattern] = [
            'count' => 0,
            'example' => $output,
        ];
    }

    $responsePatterns[$agentVersion][$pattern]['count']++;
}

echo "Response Patterns by Agent Version:\n";
echo str_repeat('-', 100) . "\n";
foreach ($responsePatterns as $version => $patterns) {
    echo "V{$version}:\n";
    foreach ($patterns as $pattern => $data) {
        echo "  - Pattern: {$pattern} (Count: {$data['count']})\n";
        echo "    Example: " . json_encode($data['example']) . "\n";
    }
    echo "\n";
}

// ========================================================================
// 4. Function Timing/Latency Analysis
// ========================================================================

echo "⏱️  Step 4: Function Latency Analysis...\n\n";

$latencyStats = [];

foreach ($functionNames as $funcName) {
    $traces = RetellFunctionTrace::where('function_name', $funcName)
        ->whereNotNull('duration_ms')
        ->whereHas('callSession', function($query) {
            $query->where('started_at', '>=', Carbon::now()->subDays(7));
        })
        ->pluck('duration_ms')
        ->toArray();

    if (count($traces) > 0) {
        sort($traces);
        $p50 = $traces[floor(count($traces) * 0.50)] ?? 0;
        $p90 = $traces[floor(count($traces) * 0.90)] ?? 0;
        $p95 = $traces[floor(count($traces) * 0.95)] ?? 0;
        $min = min($traces);
        $max = max($traces);
        $avg = round(array_sum($traces) / count($traces));

        $latencyStats[$funcName] = compact('p50', 'p90', 'p95', 'min', 'max', 'avg', 'traces');
    }
}

echo "Function Latency Distribution:\n";
echo str_repeat('-', 100) . "\n";
printf("%-35s %8s %8s %8s %8s %8s %8s\n",
    "Function", "Min", "P50", "P90", "P95", "Max", "Avg");
echo str_repeat('-', 100) . "\n";

foreach ($latencyStats as $funcName => $stats) {
    printf("%-35s %7dms %7dms %7dms %7dms %7dms %7dms\n",
        substr($funcName, 0, 35),
        $stats['min'],
        $stats['p50'],
        $stats['p90'],
        $stats['p95'],
        $stats['max'],
        $stats['avg']
    );
}
echo str_repeat('-', 100) . "\n\n";

// ========================================================================
// 5. Function Call Sequence Analysis
// ========================================================================

echo "🔗 Step 5: Function Call Sequence Patterns...\n\n";

$sequences = [];

foreach ($calls as $call) {
    if ($call->functionTraces->isEmpty()) {
        continue;
    }

    $sequence = $call->functionTraces
        ->sortBy('execution_sequence')
        ->pluck('function_name')
        ->join(' → ');

    if (!isset($sequences[$sequence])) {
        $sequences[$sequence] = [
            'count' => 0,
            'agent_versions' => [],
            'call_ids' => [],
        ];
    }

    $sequences[$sequence]['count']++;

    $agentVersion = $call->agent_version ?? 'unknown';
    if (!in_array($agentVersion, $sequences[$sequence]['agent_versions'])) {
        $sequences[$sequence]['agent_versions'][] = $agentVersion;
    }

    $sequences[$sequence]['call_ids'][] = $call->call_id;
}

// Sort by frequency
arsort($sequences);

echo "Top 10 Most Common Function Call Sequences:\n";
echo str_repeat('-', 100) . "\n";

$counter = 1;
foreach (array_slice($sequences, 0, 10, true) as $sequence => $data) {
    echo "{$counter}. Sequence: {$sequence}\n";
    echo "   Count: {$data['count']} | Versions: " . implode(', ', array_map(fn($v) => "V{$v}", $data['agent_versions'])) . "\n\n";
    $counter++;
}

// ========================================================================
// 6. Generate Output Report
// ========================================================================

echo "💾 Step 6: Generating pattern analysis report...\n\n";

$outputDir = '/var/www/api-gateway/storage/analysis';
$reportFile = "{$outputDir}/function_patterns_" . Carbon::now()->format('Y-m-d_His') . ".md";

ob_start();
?>
# Function Call Pattern Analysis

**Generated**: <?= Carbon::now()->format('Y-m-d H:i:s') ?>
**Analysis Period**: Last 7 days
**Total Calls Analyzed**: <?= $calls->count() ?>

## Key Findings

### 1. check_availability Analysis

<?php if (count($noCheckAvailabilityVersions) > 0): ?>
🔴 **CRITICAL**: The following agent versions NEVER call check_availability:
<?php foreach ($noCheckAvailabilityVersions as $version): ?>
- **V<?= $version ?>**: <?= $calls->where('agent_version', $version)->count() ?> calls
<?php endforeach; ?>

This explains why availability checking is failing in production.
<?php else: ?>
✅ All agent versions call check_availability.
<?php endif; ?>

### 2. initialize_call Response Patterns

<?php foreach ($responsePatterns as $version => $patterns): ?>
**V<?= $version ?>**:
<?php foreach ($patterns as $pattern => $data): ?>
- Pattern: `<?= $pattern ?>` (<?= $data['count'] ?> occurrences)
<?php endforeach; ?>

<?php endforeach; ?>

### 3. Function Latency Statistics

| Function | Min | P50 | P90 | P95 | Max | Avg |
|----------|-----|-----|-----|-----|-----|-----|
<?php foreach ($latencyStats as $funcName => $stats): ?>
| <?= $funcName ?> | <?= $stats['min'] ?>ms | <?= $stats['p50'] ?>ms | <?= $stats['p90'] ?>ms | <?= $stats['p95'] ?>ms | <?= $stats['max'] ?>ms | <?= $stats['avg'] ?>ms |
<?php endforeach; ?>

### 4. Common Function Call Sequences

<?php $counter = 1; foreach (array_slice($sequences, 0, 5, true) as $sequence => $data): ?>
<?= $counter ?>. `<?= $sequence ?>`
   - Occurrences: <?= $data['count'] ?>
   - Agent Versions: <?= implode(', ', array_map(fn($v) => "V{$v}", $data['agent_versions'])) ?>

<?php $counter++; endforeach; ?>

## Recommendations

1. **Agent Version Upgrade**: Versions <?= implode(', ', array_map(fn($v) => "V{$v}", $noCheckAvailabilityVersions)) ?> must be upgraded or deprecated
2. **Function Monitoring**: Add alerting for missing check_availability calls
3. **Latency Optimization**: Functions exceeding 1000ms P95 should be optimized

---

*Generated by Phase 1.2: Function Pattern Analysis*
<?php
$report = ob_get_clean();
file_put_contents($reportFile, $report);

echo "✅ Report saved: {$reportFile}\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 1.2 COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "🔍 Key Insights:\n";
echo "   - Agent versions analyzed: " . count($agentVersions) . "\n";
echo "   - Unique function names: " . count($functionNames) . "\n";
echo "   - Versions WITHOUT check_availability: " . count($noCheckAvailabilityVersions) . "\n";
echo "   - Common call sequences identified: " . count($sequences) . "\n\n";

echo "✅ Phase 1.2 complete! Proceed to Phase 2.1 for flow comparison.\n";
