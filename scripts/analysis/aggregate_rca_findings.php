#!/usr/bin/env php
<?php

/**
 * PHASE 2.3: RCA Document Aggregation
 *
 * Aggregates all Root Cause Analysis documents to extract:
 * 1. Known bug patterns
 * 2. Already solved problems
 * 3. Open issues
 * 4. Common failure modes
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2.3: RCA Document Aggregation                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ========================================================================
// 1. Discover RCA Documents
// ========================================================================

echo "🔍 Step 1: Discovering RCA documents...\n\n";

$rcaDirectories = [
    '/var/www/api-gateway/claudedocs/03_API/Retell_AI',
    '/var/www/api-gateway',
];

$rcaFiles = [];
$patterns = ['*RCA*.md', '*ROOT_CAUSE*.md', '*ANALYSIS*.md', '*CRITICAL*.md', '*BUG*.md'];

foreach ($rcaDirectories as $dir) {
    foreach ($patterns as $pattern) {
        $files = glob("{$dir}/{$pattern}");
        foreach ($files as $file) {
            if (!in_array($file, $rcaFiles)) {
                $rcaFiles[] = $file;
            }
        }
    }
}

echo "Found " . count($rcaFiles) . " RCA documents:\n";
foreach ($rcaFiles as $file) {
    echo "  - " . basename($file) . "\n";
}
echo "\n";

// ========================================================================
// 2. Parse RCA Documents
// ========================================================================

echo "📖 Step 2: Parsing RCA documents...\n\n";

$rcaData = [];
$keywords = [
    'bug_patterns' => ['bug', 'error', 'failure', 'issue', 'problem'],
    'root_causes' => ['root cause', 'caused by', 'due to', 'because'],
    'solutions' => ['fix', 'solved', 'resolved', 'corrected', 'patch'],
    'open_issues' => ['todo', 'pending', 'open', 'unresolved'],
    'agent_versions' => ['V\d+', 'version \d+', 'agent.*v?\d+'],
    'functions' => ['check_availability', 'book_appointment', 'initialize_call'],
];

foreach ($rcaFiles as $file) {
    $basename = basename($file);
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    // Extract metadata
    preg_match('/(\d{4}-\d{2}-\d{2})/', $basename, $dateMatch);
    $date = isset($dateMatch[1]) ? $dateMatch[1] : 'unknown';

    $rcaData[$basename] = [
        'file' => $file,
        'date' => $date,
        'size' => strlen($content),
        'lines' => count($lines),
        'mentions' => [],
        'sections' => [],
    ];

    // Count keyword mentions
    foreach ($keywords as $category => $words) {
        $rcaData[$basename]['mentions'][$category] = 0;

        foreach ($words as $word) {
            $pattern = "/{$word}/i";
            $matches = preg_match_all($pattern, $content, $m);
            $rcaData[$basename]['mentions'][$category] += $matches;
        }
    }

    // Extract sections
    foreach ($lines as $line) {
        if (preg_match('/^#+\s+(.+)$/', $line, $match)) {
            $rcaData[$basename]['sections'][] = trim($match[1]);
        }
    }

    echo "  ✅ Analyzed: {$basename} ({$date}, {$rcaData[$basename]['lines']} lines)\n";
}

echo "\n";

// ========================================================================
// 3. Extract Agent Version Mentions
// ========================================================================

echo "🔢 Step 3: Tracking agent version mentions...\n\n";

$versionMentions = [];

foreach ($rcaData as $basename => $data) {
    $content = file_get_contents($data['file']);

    // Find all version mentions
    preg_match_all('/\b[Vv](\d+)\b/', $content, $matches);

    foreach ($matches[1] as $version) {
        if (!isset($versionMentions[$version])) {
            $versionMentions[$version] = [
                'documents' => [],
                'total_mentions' => 0,
            ];
        }

        if (!in_array($basename, $versionMentions[$version]['documents'])) {
            $versionMentions[$version]['documents'][] = $basename;
        }

        $versionMentions[$version]['total_mentions']++;
    }
}

ksort($versionMentions);

echo "Agent Version Mentions in RCA Documents:\n";
echo str_repeat('-', 80) . "\n";
printf("%-10s %15s %s\n", "Version", "Total Mentions", "Documents");
echo str_repeat('-', 80) . "\n";

foreach ($versionMentions as $version => $data) {
    printf("%-10s %15d %d documents\n",
        "V{$version}",
        $data['total_mentions'],
        count($data['documents'])
    );
}
echo str_repeat('-', 80) . "\n\n";

// ========================================================================
// 4. Extract Common Failure Patterns
// ========================================================================

echo "🚨 Step 4: Identifying common failure patterns...\n\n";

$failurePatterns = [
    'check_availability_missing' => [
        'pattern' => '/check.*availability.*not.*called|no.*check.*availability/i',
        'severity' => 'high',
        'matches' => [],
    ],
    'race_condition' => [
        'pattern' => '/race.*condition|timing.*issue|non.*blocking/i',
        'severity' => 'high',
        'matches' => [],
    ],
    'customer_routing_fail' => [
        'pattern' => '/customer.*routing|wrong.*path|bekannt.*neu/i',
        'severity' => 'medium',
        'matches' => [],
    ],
    'version_mismatch' => [
        'pattern' => '/version.*mismatch|phone.*agent.*version|outdated.*flow/i',
        'severity' => 'high',
        'matches' => [],
    ],
    'function_timeout' => [
        'pattern' => '/timeout|timed.*out|too.*slow/i',
        'severity' => 'medium',
        'matches' => [],
    ],
];

foreach ($rcaFiles as $file) {
    $basename = basename($file);
    $content = file_get_contents($file);

    foreach ($failurePatterns as $patternName => &$patternData) {
        if (preg_match($patternData['pattern'], $content)) {
            $patternData['matches'][] = $basename;
        }
    }
}

echo "Common Failure Patterns Found:\n";
echo str_repeat('-', 80) . "\n";
printf("%-30s %-10s %s\n", "Pattern", "Severity", "Documents");
echo str_repeat('-', 80) . "\n";

foreach ($failurePatterns as $patternName => $data) {
    if (count($data['matches']) > 0) {
        printf("%-30s %-10s %d docs\n",
            $patternName,
            strtoupper($data['severity']),
            count($data['matches'])
        );
    }
}
echo str_repeat('-', 80) . "\n\n";

// ========================================================================
// 5. Extract Solutions & Fixes
// ========================================================================

echo "✅ Step 5: Extracting documented solutions...\n\n";

$solutions = [];

foreach ($rcaFiles as $file) {
    $basename = basename($file);
    $content = file_get_contents($file);
    $lines = explode("\n", $content);

    $inFixSection = false;
    $currentFix = [];

    foreach ($lines as $line) {
        // Detect fix sections
        if (preg_match('/^#+\s+(fix|solution|resolved|correction)/i', $line)) {
            $inFixSection = true;
            continue;
        }

        if (preg_match('/^#+\s+/', $line) && !preg_match('/fix|solution|resolved/i', $line)) {
            $inFixSection = false;
        }

        if ($inFixSection && trim($line) !== '') {
            $currentFix[] = $line;
        }
    }

    if (!empty($currentFix)) {
        $solutions[$basename] = [
            'file' => $file,
            'fix_content' => implode("\n", $currentFix),
            'line_count' => count($currentFix),
        ];
    }
}

echo "Documented Solutions Found: " . count($solutions) . "\n";
foreach ($solutions as $basename => $data) {
    echo "  - {$basename}: {$data['line_count']} lines of fixes\n";
}
echo "\n";

// ========================================================================
// 6. Generate Master RCA Index
// ========================================================================

echo "💾 Step 6: Generating master RCA index...\n\n";

$outputDir = '/var/www/api-gateway/storage/analysis';
$reportFile = "{$outputDir}/rca_master_index_" . Carbon::now()->format('Y-m-d_His') . ".md";

ob_start();
?>
# Master RCA Index

**Generated**: <?= Carbon::now()->format('Y-m-d H:i:s') ?>
**Total Documents Analyzed**: <?= count($rcaFiles) ?>

## Document Inventory

| Document | Date | Size | Lines | Bug Mentions | Solution Mentions |
|----------|------|------|-------|--------------|-------------------|
<?php foreach ($rcaData as $basename => $data): ?>
| <?= $basename ?> | <?= $data['date'] ?> | <?= round($data['size'] / 1024, 1) ?>KB | <?= $data['lines'] ?> | <?= $data['mentions']['bug_patterns'] ?> | <?= $data['mentions']['solutions'] ?> |
<?php endforeach; ?>

## Agent Version Timeline

<?php foreach ($versionMentions as $version => $data): ?>
### V<?= $version ?>

- **Mentioned in**: <?= count($data['documents']) ?> documents
- **Total mentions**: <?= $data['total_mentions'] ?>
- **Documents**:
<?php foreach ($data['documents'] as $doc): ?>
  - <?= $doc ?>

<?php endforeach; ?>
<?php endforeach; ?>

## Common Failure Patterns

<?php foreach ($failurePatterns as $patternName => $data): ?>
<?php if (count($data['matches']) > 0): ?>
### <?= ucwords(str_replace('_', ' ', $patternName)) ?> (Severity: <?= strtoupper($data['severity']) ?>)

**Found in <?= count($data['matches']) ?> documents**:
<?php foreach ($data['matches'] as $doc): ?>
- <?= $doc ?>
<?php endforeach; ?>

<?php endif; ?>
<?php endforeach; ?>

## Documented Solutions

<?php foreach ($solutions as $basename => $data): ?>
### <?= $basename ?>

```
<?= substr($data['fix_content'], 0, 500) ?>

...
```

**Full solution**: See [<?= basename($data['file']) ?>](<?= $data['file'] ?>)

<?php endforeach; ?>

## Known Issues Summary

Based on RCA analysis, the following issues are documented:

<?php
$allPatterns = array_keys(array_filter($failurePatterns, fn($p) => count($p['matches']) > 0));
foreach ($allPatterns as $pattern):
?>
- **<?= ucwords(str_replace('_', ' ', $pattern)) ?>**: Found in <?= count($failurePatterns[$pattern]['matches']) ?> RCA documents
<?php endforeach; ?>

## Recommendations

1. **Version Management**: Versions <?= implode(', ', array_keys($versionMentions)) ?> have documented issues - review for deprecation
2. **Pattern Monitoring**: Implement automated detection for common failure patterns
3. **Solution Database**: Maintain searchable database of fixes for rapid response
4. **Regression Prevention**: Add tests to prevent re-occurrence of solved issues

---

*Generated by Phase 2.3: RCA Document Aggregation*
<?php
$report = ob_get_clean();
file_put_contents($reportFile, $report);

echo "✅ Report saved: {$reportFile}\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2.3 COMPLETE                                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Aggregation Summary:\n";
echo "   - RCA documents analyzed: " . count($rcaFiles) . "\n";
echo "   - Agent versions mentioned: " . count($versionMentions) . "\n";
echo "   - Failure patterns identified: " . count(array_filter($failurePatterns, fn($p) => count($p['matches']) > 0)) . "\n";
echo "   - Documented solutions: " . count($solutions) . "\n\n";

echo "✅ Phase 2.3 complete!\n";
