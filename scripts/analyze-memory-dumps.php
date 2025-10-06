#!/usr/bin/env php
<?php

/**
 * Analyze Memory Dump Files
 *
 * Usage: php scripts/analyze-memory-dumps.php [--recent N] [--summary] [--details]
 */

$options = getopt('', ['recent:', 'summary', 'details', 'pattern:']);

$dumpDir = __DIR__ . '/../storage/logs/memory-dumps';
$recent = $options['recent'] ?? 10;
$showSummary = isset($options['summary']) || !isset($options['details']);
$showDetails = isset($options['details']);
$pattern = $options['pattern'] ?? null;

if (!is_dir($dumpDir)) {
    echo "❌ Dump directory not found: $dumpDir\n";
    exit(1);
}

// Find all dump files
$files = glob("$dumpDir/memory-dump-*.json");

if (empty($files)) {
    echo "📭 No memory dumps found in $dumpDir\n";
    exit(0);
}

// Sort by timestamp (newest first)
usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));

// Limit to recent
$files = array_slice($files, 0, (int)$recent);

echo "📊 Analyzing " . count($files) . " memory dumps\n";
echo str_repeat('=', 80) . "\n\n";

$allDumps = [];

foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), true);

    if (!$data) {
        echo "⚠️  Failed to parse: " . basename($file) . "\n";
        continue;
    }

    $allDumps[] = $data;

    if ($showDetails) {
        displayDumpDetails($data, $file);
    }
}

if ($showSummary) {
    displaySummary($allDumps);
}

if ($pattern) {
    searchPattern($allDumps, $pattern);
}

// ============================================================================
// Display Functions
// ============================================================================

function displayDumpDetails(array $dump, string $file): void
{
    echo "📄 " . basename($file) . "\n";
    echo "   Context: {$dump['context']}\n";
    echo "   Time: {$dump['timestamp']}\n";
    echo "   Memory: {$dump['memory']['current_mb']} MB / {$dump['memory']['limit_mb']} MB ";
    echo "({$dump['memory']['usage_percent']}%)\n";
    echo "   Peak: {$dump['memory']['peak_mb']} MB\n";

    if (isset($dump['session']['size_mb'])) {
        echo "   Session: {$dump['session']['size_mb']} MB\n";
    }

    if (isset($dump['session']['largest_keys'])) {
        echo "   Largest session keys:\n";
        foreach ($dump['session']['largest_keys'] as $key => $size) {
            echo "     - $key: $size\n";
        }
    }

    echo "\n";
}

function displaySummary(array $dumps): void
{
    echo str_repeat('=', 80) . "\n";
    echo "📈 SUMMARY ANALYSIS\n";
    echo str_repeat('=', 80) . "\n\n";

    // Memory statistics
    $memoryUsages = array_column(array_column($dumps, 'memory'), 'current_mb');
    $peakUsages = array_column(array_column($dumps, 'memory'), 'peak_mb');

    echo "Memory Statistics:\n";
    echo "  Average: " . round(array_sum($memoryUsages) / count($memoryUsages), 2) . " MB\n";
    echo "  Min: " . min($memoryUsages) . " MB\n";
    echo "  Max: " . max($memoryUsages) . " MB\n";
    echo "  Peak Max: " . max($peakUsages) . " MB\n";
    echo "\n";

    // Context breakdown
    $contexts = array_count_values(array_column($dumps, 'context'));
    arsort($contexts);

    echo "Contexts:\n";
    foreach ($contexts as $context => $count) {
        echo "  $context: $count times\n";
    }
    echo "\n";

    // Session analysis
    $sessionSizes = array_filter(
        array_column(array_column($dumps, 'session'), 'size_mb')
    );

    if (!empty($sessionSizes)) {
        echo "Session Statistics:\n";
        echo "  Average: " . round(array_sum($sessionSizes) / count($sessionSizes), 2) . " MB\n";
        echo "  Max: " . max($sessionSizes) . " MB\n";
        echo "\n";

        // Most common large session keys
        $allSessionKeys = [];
        foreach ($dumps as $dump) {
            if (isset($dump['session']['largest_keys'])) {
                foreach ($dump['session']['largest_keys'] as $key => $size) {
                    $allSessionKeys[$key] = ($allSessionKeys[$key] ?? 0) + 1;
                }
            }
        }

        if (!empty($allSessionKeys)) {
            arsort($allSessionKeys);
            echo "Most common large session keys:\n";
            foreach (array_slice($allSessionKeys, 0, 10, true) as $key => $count) {
                echo "  $key: appears in $count dumps\n";
            }
            echo "\n";
        }
    }

    // Backtrace analysis - find common hot paths
    $allFunctions = [];
    foreach ($dumps as $dump) {
        if (isset($dump['backtrace'])) {
            foreach ($dump['backtrace'] as $frame) {
                $func = $frame['function'] ?? 'unknown';
                $allFunctions[$func] = ($allFunctions[$func] ?? 0) + 1;
            }
        }
    }

    if (!empty($allFunctions)) {
        arsort($allFunctions);
        echo "Most common stack frames:\n";
        foreach (array_slice($allFunctions, 0, 15, true) as $func => $count) {
            echo "  $func: $count occurrences\n";
        }
        echo "\n";
    }

    // Patterns and insights
    echo "🔍 INSIGHTS:\n";

    // Check for session bloat
    if (!empty($sessionSizes) && max($sessionSizes) > 50) {
        echo "  ⚠️  Large session detected (max: " . max($sessionSizes) . " MB)\n";
        echo "      → Consider session data optimization\n";
    }

    // Check for memory growth pattern
    if (count($memoryUsages) >= 3) {
        $trend = ($memoryUsages[0] > $memoryUsages[count($memoryUsages) - 1]) ? 'increasing' : 'stable';
        if ($trend === 'increasing') {
            echo "  ⚠️  Memory usage trending upward over time\n";
            echo "      → Possible memory leak or accumulation\n";
        }
    }

    // Check for consistency
    $variance = variance($memoryUsages);
    if ($variance < 100) {
        echo "  ✅ Memory usage is consistent (low variance)\n";
        echo "      → Issue is deterministic, not random\n";
    } else {
        echo "  ⚠️  High memory variance detected\n";
        echo "      → Issue may be state-dependent\n";
    }

    echo "\n";
}

function searchPattern(array $dumps, string $pattern): void
{
    echo str_repeat('=', 80) . "\n";
    echo "🔎 SEARCHING FOR PATTERN: $pattern\n";
    echo str_repeat('=', 80) . "\n\n";

    foreach ($dumps as $dump) {
        $json = json_encode($dump);
        if (stripos($json, $pattern) !== false) {
            echo "✅ Found in dump: {$dump['timestamp']} ({$dump['context']})\n";

            // Show context
            if (isset($dump['session']['largest_keys'])) {
                foreach ($dump['session']['largest_keys'] as $key => $size) {
                    if (stripos($key, $pattern) !== false) {
                        echo "   Session key match: $key = $size\n";
                    }
                }
            }

            if (isset($dump['backtrace'])) {
                foreach ($dump['backtrace'] as $frame) {
                    $frameStr = json_encode($frame);
                    if (stripos($frameStr, $pattern) !== false) {
                        echo "   Backtrace match: {$frame['function']}\n";
                    }
                }
            }

            echo "\n";
        }
    }
}

function variance(array $values): float
{
    $mean = array_sum($values) / count($values);
    $variance = array_reduce($values, fn($carry, $val) => $carry + pow($val - $mean, 2), 0);
    return $variance / count($values);
}
