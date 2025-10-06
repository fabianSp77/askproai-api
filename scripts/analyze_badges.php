#!/usr/bin/env php
<?php

/**
 * Badge Restoration Analysis Script
 * Analyzes all Filament Resources to identify disabled badges and assess restoration safety
 */

$resourceDir = '/var/www/api-gateway/app/Filament/Resources';
$backupSuffix = '.pre-caching-backup';

// Badge analysis results
$results = [];

// Scan all Resource files
$resourceFiles = glob("$resourceDir/*Resource.php");

foreach ($resourceFiles as $currentFile) {
    $resourceName = basename($currentFile, '.php');
    $backupFile = $currentFile . $backupSuffix;

    // Skip if no backup exists
    if (!file_exists($backupFile)) {
        continue;
    }

    $currentContent = file_get_contents($currentFile);
    $backupContent = file_get_contents($backupFile);

    // Extract current badge implementation
    preg_match('/public static function getNavigationBadge\(\).*?\{(.*?)\n    \}/s', $currentContent, $currentMatches);
    preg_match('/public static function getNavigationBadgeColor\(\).*?\{(.*?)\n    \}/s', $currentContent, $currentColorMatches);

    // Extract backup badge implementation
    preg_match('/public static function getNavigationBadge\(\).*?\{(.*?)\n    \}/s', $backupContent, $backupMatches);
    preg_match('/public static function getNavigationBadgeColor\(\).*?\{(.*?)\n    \}/s', $backupContent, $backupColorMatches);

    $currentBadge = $currentMatches[1] ?? null;
    $backupBadge = $backupMatches[1] ?? null;

    // Check if badge is disabled
    $isDisabled = $currentBadge && (
        str_contains($currentBadge, 'return null') &&
        str_contains($currentBadge, 'EMERGENCY')
    );

    if (!$isDisabled && !$backupBadge) {
        continue; // No badge or not disabled
    }

    // Analyze the backup badge
    $analysis = [
        'resource' => $resourceName,
        'is_disabled' => $isDisabled,
        'current_implementation' => trim($currentBadge ?? ''),
        'original_implementation' => trim($backupBadge ?? ''),
        'has_color' => !empty($backupColorMatches[1] ?? ''),
        'uses_cache' => str_contains($backupBadge ?? '', 'Cache::remember'),
        'query_complexity' => 'SIMPLE',
        'safety_rating' => 'SAFE',
        'priority' => 'HIGH',
        'notes' => []
    ];

    // Analyze query complexity
    if (str_contains($backupBadge, 'join') || str_contains($backupBadge, 'with(')) {
        $analysis['query_complexity'] = 'COMPLEX';
        $analysis['safety_rating'] = 'CAUTION';
    } elseif (str_contains($backupBadge, 'whereDate') || str_contains($backupBadge, 'whereMonth')) {
        $analysis['query_complexity'] = 'MODERATE';
    }

    // Check for multi-tenant safety
    if (str_contains($backupBadge, 'company_id') || str_contains($backupBadge, 'branch_id')) {
        $analysis['notes'][] = 'Uses tenant filtering (safe)';
    }

    // Determine user-facing priority
    if (str_contains($resourceName, 'Appointment') || str_contains($resourceName, 'Call') || str_contains($resourceName, 'Customer')) {
        $analysis['priority'] = 'HIGH';
        $analysis['notes'][] = 'User-facing resource';
    } elseif (str_contains($resourceName, 'Notification') || str_contains($resourceName, 'Callback')) {
        $analysis['priority'] = 'MEDIUM';
        $analysis['notes'][] = 'Admin resource';
    } else {
        $analysis['priority'] = 'LOW';
        $analysis['notes'][] = 'System resource';
    }

    // Safety assessment based on caching
    if ($analysis['uses_cache']) {
        $analysis['notes'][] = 'Already uses caching';
    } else {
        $analysis['notes'][] = 'Needs caching trait added';
        if ($analysis['query_complexity'] === 'COMPLEX') {
            $analysis['safety_rating'] = 'RISKY';
        }
    }

    $results[] = $analysis;
}

// Sort by priority
usort($results, function($a, $b) {
    $priorityOrder = ['HIGH' => 0, 'MEDIUM' => 1, 'LOW' => 2];
    return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
});

// Output report
echo "=" . str_repeat("=", 100) . "\n";
echo "NAVIGATION BADGE RESTORATION ANALYSIS REPORT\n";
echo "=" . str_repeat("=", 100) . "\n\n";

echo "SUMMARY:\n";
echo "--------\n";
echo "Total Resources Analyzed: " . count($results) . "\n";
echo "Disabled Badges Found: " . count(array_filter($results, fn($r) => $r['is_disabled'])) . "\n\n";

// Count by priority
$highPriority = count(array_filter($results, fn($r) => $r['priority'] === 'HIGH'));
$mediumPriority = count(array_filter($results, fn($r) => $r['priority'] === 'MEDIUM'));
$lowPriority = count(array_filter($results, fn($r) => $r['priority'] === 'LOW'));

echo "Priority Breakdown:\n";
echo "  HIGH:   $highPriority resources\n";
echo "  MEDIUM: $mediumPriority resources\n";
echo "  LOW:    $lowPriority resources\n\n";

// Count by safety rating
$safe = count(array_filter($results, fn($r) => $r['safety_rating'] === 'SAFE'));
$caution = count(array_filter($results, fn($r) => $r['safety_rating'] === 'CAUTION'));
$risky = count(array_filter($results, fn($r) => $r['safety_rating'] === 'RISKY'));

echo "Safety Ratings:\n";
echo "  SAFE:    $safe resources (can restore immediately)\n";
echo "  CAUTION: $caution resources (test first)\n";
echo "  RISKY:   $risky resources (keep disabled or refactor)\n\n";

echo "=" . str_repeat("=", 100) . "\n\n";

// Detailed breakdown
foreach (['HIGH', 'MEDIUM', 'LOW'] as $priority) {
    $priorityResources = array_filter($results, fn($r) => $r['priority'] === $priority);

    if (empty($priorityResources)) continue;

    echo "$priority PRIORITY RESOURCES\n";
    echo str_repeat("-", 100) . "\n\n";

    foreach ($priorityResources as $analysis) {
        echo "{$analysis['resource']}\n";
        echo "  Status: " . ($analysis['is_disabled'] ? 'DISABLED' : 'ENABLED') . "\n";
        echo "  Safety: {$analysis['safety_rating']}\n";
        echo "  Query Complexity: {$analysis['query_complexity']}\n";
        echo "  Uses Cache: " . ($analysis['uses_cache'] ? 'YES' : 'NO') . "\n";
        echo "  Has Color: " . ($analysis['has_color'] ? 'YES' : 'NO') . "\n";

        if (!empty($analysis['notes'])) {
            echo "  Notes:\n";
            foreach ($analysis['notes'] as $note) {
                echo "    - $note\n";
            }
        }

        if (!empty($analysis['original_implementation'])) {
            echo "\n  Original Implementation:\n";
            $lines = explode("\n", trim($analysis['original_implementation']));
            foreach ($lines as $line) {
                echo "    " . trim($line) . "\n";
            }
        }

        echo "\n";
    }

    echo "\n";
}

// Restoration recommendations
echo "=" . str_repeat("=", 100) . "\n";
echo "RESTORATION RECOMMENDATIONS\n";
echo "=" . str_repeat("=", 100) . "\n\n";

echo "PHASE 1: Immediate Restoration (SAFE badges)\n";
echo "---------------------------------------------\n";
$safeResources = array_filter($results, fn($r) => $r['safety_rating'] === 'SAFE');
foreach ($safeResources as $res) {
    echo "  - {$res['resource']}\n";
}

echo "\nPHASE 2: Test and Restore (CAUTION badges)\n";
echo "-------------------------------------------\n";
$cautionResources = array_filter($results, fn($r) => $r['safety_rating'] === 'CAUTION');
foreach ($cautionResources as $res) {
    echo "  - {$res['resource']} (requires testing)\n";
}

echo "\nPHASE 3: Refactor or Keep Disabled (RISKY badges)\n";
echo "--------------------------------------------------\n";
$riskyResources = array_filter($results, fn($r) => $r['safety_rating'] === 'RISKY');
foreach ($riskyResources as $res) {
    echo "  - {$res['resource']} (needs refactoring or keep disabled)\n";
}

echo "\n";
echo "=" . str_repeat("=", 100) . "\n";
echo "Analysis complete. Caching system is ready for badge restoration.\n";
echo "=" . str_repeat("=", 100) . "\n";
