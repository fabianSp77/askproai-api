#!/usr/bin/env php
<?php

/**
 * PHASE 2.1: Flow Version Comparison Tool
 *
 * Compares all flow JSON files to identify:
 * 1. When was check_availability added?
 * 2. What changed in node configuration?
 * 3. What are the breaking changes between versions?
 * 4. Feature matrix across versions
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2.1: Flow Version Comparison                           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ========================================================================
// 1. Discover All Flow Files
// ========================================================================

echo "🔍 Step 1: Discovering flow JSON files...\n\n";

$flowDir = '/var/www/api-gateway/public';
$flowFiles = glob("{$flowDir}/*flow*.json");

echo "Found " . count($flowFiles) . " flow files:\n";
foreach ($flowFiles as $file) {
    echo "  - " . basename($file) . "\n";
}
echo "\n";

// ========================================================================
// 2. Parse and Index Flows
// ========================================================================

echo "📖 Step 2: Parsing flow files...\n\n";

$flows = [];

foreach ($flowFiles as $file) {
    $basename = basename($file);

    // Extract version number if present
    preg_match('/[vV](\d+)/', $basename, $matches);
    $version = isset($matches[1]) ? (int)$matches[1] : null;

    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if (!$data) {
        echo "⚠️  Warning: Could not parse {$basename}\n";
        continue;
    }

    $flows[$basename] = [
        'version' => $version,
        'file' => $file,
        'data' => $data,
        'metadata' => [
            'node_count' => count($data['nodes'] ?? []),
            'edge_count' => count($data['edges'] ?? []),
        ],
    ];

    echo "  ✅ Loaded: {$basename} (Version: " . ($version ?? 'unknown') . ", Nodes: {$flows[$basename]['metadata']['node_count']})\n";
}

echo "\n";

// Sort flows by version
uasort($flows, function($a, $b) {
    if ($a['version'] === null && $b['version'] === null) return 0;
    if ($a['version'] === null) return 1;
    if ($b['version'] === null) return -1;
    return $a['version'] <=> $b['version'];
});

// ========================================================================
// 3. Extract Functions from Each Flow
// ========================================================================

echo "🔧 Step 3: Extracting function nodes...\n\n";

foreach ($flows as $name => &$flow) {
    $functions = [];

    if (isset($flow['data']['nodes'])) {
        foreach ($flow['data']['nodes'] as $node) {
            if (isset($node['type']) && $node['type'] === 'function_call') {
                $functions[] = [
                    'id' => $node['id'] ?? null,
                    'name' => $node['data']['name'] ?? $node['id'],
                    'description' => $node['data']['description'] ?? null,
                    'speak_during_execution' => $node['data']['speak_during_execution'] ?? false,
                    'wait_for_result' => $node['data']['wait_for_result'] ?? false,
                ];
            }
        }
    }

    $flow['functions'] = $functions;
    $flow['metadata']['function_count'] = count($functions);
}

echo "Function Node Summary:\n";
echo str_repeat('-', 80) . "\n";
printf("%-40s %10s %10s\n", "Flow File", "Version", "Functions");
echo str_repeat('-', 80) . "\n";

foreach ($flows as $name => $flow) {
    printf("%-40s %10s %10d\n",
        substr($name, 0, 40),
        $flow['version'] ?? 'N/A',
        $flow['metadata']['function_count']
    );
}
echo str_repeat('-', 80) . "\n\n";

// ========================================================================
// 4. check_availability Detection
// ========================================================================

echo "🚨 Step 4: check_availability Timeline Analysis...\n\n";

$checkAvailabilityTimeline = [];

foreach ($flows as $name => $flow) {
    $hasCheckAvailability = false;

    foreach ($flow['functions'] as $func) {
        if (stripos($func['name'], 'check_availability') !== false) {
            $hasCheckAvailability = true;
            $checkAvailabilityTimeline[$name] = [
                'version' => $flow['version'],
                'function' => $func,
            ];
            break;
        }
    }

    if (!$hasCheckAvailability) {
        $checkAvailabilityTimeline[$name] = [
            'version' => $flow['version'],
            'function' => null,
        ];
    }
}

echo "check_availability Availability:\n";
echo str_repeat('-', 80) . "\n";

foreach ($checkAvailabilityTimeline as $name => $data) {
    $status = $data['function'] ? '✅ Present' : '❌ Missing';
    $version = $data['version'] ?? 'N/A';

    echo "V{$version}: {$status}\n";

    if ($data['function']) {
        echo "  Config: speak_during=" . ($data['function']['speak_during_execution'] ? 'true' : 'false');
        echo ", wait_for_result=" . ($data['function']['wait_for_result'] ? 'true' : 'false') . "\n";
    }
}
echo "\n";

// Find first version with check_availability
$firstWithCheckAvailability = null;
foreach ($checkAvailabilityTimeline as $name => $data) {
    if ($data['function']) {
        $firstWithCheckAvailability = $data['version'];
        break;
    }
}

if ($firstWithCheckAvailability) {
    echo "📅 check_availability first appeared in: V{$firstWithCheckAvailability}\n\n";
} else {
    echo "⚠️  check_availability not found in any flow version\n\n";
}

// ========================================================================
// 5. Feature Matrix
// ========================================================================

echo "📊 Step 5: Building feature matrix...\n\n";

$allFunctions = [];
foreach ($flows as $flow) {
    foreach ($flow['functions'] as $func) {
        $funcName = $func['name'];
        if (!in_array($funcName, $allFunctions)) {
            $allFunctions[] = $funcName;
        }
    }
}
sort($allFunctions);

echo "Feature Matrix (Function Presence):\n";
echo str_repeat('=', 120) . "\n";

printf("%-30s", "Function");
foreach ($flows as $name => $flow) {
    $version = $flow['version'] ?? '??';
    printf(" | V%-3s", $version);
}
echo "\n";
echo str_repeat('=', 120) . "\n";

foreach ($allFunctions as $funcName) {
    printf("%-30s", substr($funcName, 0, 30));

    foreach ($flows as $flow) {
        $hasFunc = false;
        foreach ($flow['functions'] as $func) {
            if ($func['name'] === $funcName) {
                $hasFunc = true;
                break;
            }
        }
        printf(" | %3s", $hasFunc ? '✓' : '-');
    }
    echo "\n";
}
echo str_repeat('=', 120) . "\n\n";

// ========================================================================
// 6. Breaking Changes Detection
// ========================================================================

echo "🔴 Step 6: Detecting breaking changes...\n\n";

$breakingChanges = [];

// Compare each version with previous
$previousFlow = null;
foreach ($flows as $name => $flow) {
    if ($previousFlow === null) {
        $previousFlow = $flow;
        continue;
    }

    $changes = [];

    // Detect removed functions
    foreach ($previousFlow['functions'] as $prevFunc) {
        $found = false;
        foreach ($flow['functions'] as $currFunc) {
            if ($prevFunc['name'] === $currFunc['name']) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $changes[] = [
                'type' => 'function_removed',
                'function' => $prevFunc['name'],
            ];
        }
    }

    // Detect added functions
    foreach ($flow['functions'] as $currFunc) {
        $found = false;
        foreach ($previousFlow['functions'] as $prevFunc) {
            if ($currFunc['name'] === $prevFunc['name']) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $changes[] = [
                'type' => 'function_added',
                'function' => $currFunc['name'],
            ];
        }
    }

    // Detect node count changes
    if ($flow['metadata']['node_count'] !== $previousFlow['metadata']['node_count']) {
        $diff = $flow['metadata']['node_count'] - $previousFlow['metadata']['node_count'];
        $changes[] = [
            'type' => 'node_count_change',
            'diff' => $diff,
        ];
    }

    if (count($changes) > 0) {
        $breakingChanges[$name] = [
            'from_version' => $previousFlow['version'],
            'to_version' => $flow['version'],
            'changes' => $changes,
        ];
    }

    $previousFlow = $flow;
}

if (count($breakingChanges) > 0) {
    echo "Breaking Changes Detected:\n";
    echo str_repeat('-', 80) . "\n";

    foreach ($breakingChanges as $flowName => $data) {
        echo "V{$data['from_version']} → V{$data['to_version']}:\n";
        foreach ($data['changes'] as $change) {
            switch ($change['type']) {
                case 'function_removed':
                    echo "  ❌ Function removed: {$change['function']}\n";
                    break;
                case 'function_added':
                    echo "  ✅ Function added: {$change['function']}\n";
                    break;
                case 'node_count_change':
                    echo "  🔄 Node count changed: " . ($change['diff'] > 0 ? "+{$change['diff']}" : $change['diff']) . "\n";
                    break;
            }
        }
        echo "\n";
    }
} else {
    echo "No breaking changes detected.\n\n";
}

// ========================================================================
// 7. Generate Output Report
// ========================================================================

echo "💾 Step 7: Generating comparison report...\n\n";

$outputDir = '/var/www/api-gateway/storage/analysis';
$reportFile = "{$outputDir}/flow_comparison_" . Carbon::now()->format('Y-m-d_His') . ".md";

ob_start();
?>
# Flow Version Comparison Report

**Generated**: <?= Carbon::now()->format('Y-m-d H:i:s') ?>
**Total Flows Analyzed**: <?= count($flows) ?>

## Flow Inventory

| Flow File | Version | Nodes | Edges | Functions |
|-----------|---------|-------|-------|-----------|
<?php foreach ($flows as $name => $flow): ?>
| <?= $name ?> | <?= $flow['version'] ?? 'N/A' ?> | <?= $flow['metadata']['node_count'] ?> | <?= $flow['metadata']['edge_count'] ?> | <?= $flow['metadata']['function_count'] ?> |
<?php endforeach; ?>

## check_availability Timeline

<?php if ($firstWithCheckAvailability): ?>
**First Appearance**: V<?= $firstWithCheckAvailability ?>
<?php else: ?>
**Status**: Not found in any version ⚠️
<?php endif; ?>

<?php foreach ($checkAvailabilityTimeline as $name => $data): ?>
- **V<?= $data['version'] ?? 'N/A' ?>**: <?= $data['function'] ? '✅ Present' : '❌ Missing' ?>
<?php if ($data['function']): ?>
  - `speak_during_execution`: <?= $data['function']['speak_during_execution'] ? 'true' : 'false' ?>
  - `wait_for_result`: <?= $data['function']['wait_for_result'] ? 'true' : 'false' ?>
<?php endif; ?>
<?php endforeach; ?>

## Feature Matrix

| Function | <?= implode(' | ', array_map(function($flow) { return 'V' . ($flow['version'] ?? '?'); }, $flows)) ?> |
|----------|<?= str_repeat('------|', count($flows)) ?>
<?php foreach ($allFunctions as $funcName): ?>
| <?= $funcName ?> |<?php foreach ($flows as $flow): ?> <?= in_array($funcName, array_column($flow['functions'], 'name')) ? '✓' : '-' ?> |<?php endforeach; ?>

<?php endforeach; ?>

## Breaking Changes

<?php if (count($breakingChanges) > 0): ?>
<?php foreach ($breakingChanges as $flowName => $data): ?>
### V<?= $data['from_version'] ?> → V<?= $data['to_version'] ?>

<?php foreach ($data['changes'] as $change): ?>
<?php if ($change['type'] === 'function_removed'): ?>
- ❌ **Function Removed**: `<?= $change['function'] ?>`
<?php elseif ($change['type'] === 'function_added'): ?>
- ✅ **Function Added**: `<?= $change['function'] ?>`
<?php elseif ($change['type'] === 'node_count_change'): ?>
- 🔄 **Node Count**: <?= $change['diff'] > 0 ? "+{$change['diff']}" : $change['diff'] ?> nodes
<?php endif; ?>
<?php endforeach; ?>

<?php endforeach; ?>
<?php else: ?>
No breaking changes detected between versions.
<?php endif; ?>

## Recommendations

1. **Standardize on Latest**: Ensure all production agents use the latest flow version
2. **Function Consistency**: All essential functions (check_availability, book_appointment) must be present
3. **Deprecation Path**: Plan deprecation for versions without critical functions

---

*Generated by Phase 2.1: Flow Version Comparison*
<?php
$report = ob_get_clean();
file_put_contents($reportFile, $report);

echo "✅ Report saved: {$reportFile}\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2.1 COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Analysis Summary:\n";
echo "   - Flow files analyzed: " . count($flows) . "\n";
echo "   - Unique functions found: " . count($allFunctions) . "\n";
echo "   - check_availability first appeared: V" . ($firstWithCheckAvailability ?? 'NEVER') . "\n";
echo "   - Breaking changes detected: " . count($breakingChanges) . "\n\n";

echo "✅ Phase 2.1 complete! Proceed to Phase 2.3 for RCA aggregation.\n";
