<?php
/**
 * Complete Filament Navigation Analysis Script
 * Analyzes all resources and their visibility for super_admin
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   COMPLETE FILAMENT NAVIGATION ANALYSIS FOR SUPER ADMIN       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get all resource files
$resourcesPath = app_path('Filament/Resources');
$resourceFiles = glob($resourcesPath . '/*Resource.php');

$analysis = [
    'visible' => [],
    'hidden_by_navigation' => [],
    'hidden_by_policy' => [],
    'total' => count($resourceFiles),
];

echo "📊 Analyzing " . count($resourceFiles) . " Filament Resources...\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

foreach ($resourceFiles as $file) {
    $resourceName = basename($file, '.php');
    $className = "App\\Filament\\Resources\\{$resourceName}";

    if (!class_exists($className)) {
        continue;
    }

    $reflection = new ReflectionClass($className);
    $content = file_get_contents($file);

    // Check if navigation is disabled
    $navigationDisabled = false;
    $navigationReason = '';

    if (method_exists($className, 'shouldRegisterNavigation')) {
        $navigationDisabled = !$className::shouldRegisterNavigation();

        // Try to extract reason from comments
        if (preg_match('/\/\*\*.*?shouldRegisterNavigation.*?\*\//s', $content, $matches)) {
            $comment = $matches[0];
            if (preg_match('/TODO:\s*(.+?)(?:\n|\*\/)/s', $comment, $reasonMatch)) {
                $navigationReason = trim($reasonMatch[1]);
            } elseif (preg_match('/disabled[:\-\s]+(.+?)(?:\n|\*\/)/i', $comment, $reasonMatch)) {
                $navigationReason = trim($reasonMatch[1]);
            }
        }
    }

    // Check if canViewAny returns false
    $canViewAnyDisabled = false;
    if (preg_match('/public\s+static\s+function\s+canViewAny\s*\(\)\s*:\s*bool\s*\{[^}]*return\s+false/s', $content)) {
        $canViewAnyDisabled = true;
    }

    // Get navigation details
    $navigationGroup = '';
    $navigationLabel = '';
    $navigationIcon = '';
    $navigationSort = null;

    if (preg_match('/protected static \?string \$navigationGroup = [\'"]([^\'"]+)[\'"]/s', $content, $matches)) {
        $navigationGroup = $matches[1];
    }
    if (preg_match('/protected static \?string \$navigationLabel = [\'"]([^\'"]+)[\'"]/s', $content, $matches)) {
        $navigationLabel = $matches[1];
    }
    if (preg_match('/protected static \?string \$navigationIcon = [\'"]([^\'"]+)[\'"]/s', $content, $matches)) {
        $navigationIcon = $matches[1];
    }
    if (preg_match('/protected static \?int \$navigationSort = (\d+)/s', $content, $matches)) {
        $navigationSort = (int)$matches[1];
    }

    // Get model
    $model = '';
    if (preg_match('/protected static \?string \$model = ([^;]+);/s', $content, $matches)) {
        $model = trim($matches[1], ' \\');
        $model = str_replace('::class', '', $model);
        $model = str_replace('App\\Models\\', '', $model);
    }

    // Check if policy exists
    $policyClass = "App\\Policies\\{$model}Policy";
    $hasPolicy = class_exists($policyClass);

    $resourceInfo = [
        'name' => $resourceName,
        'model' => $model,
        'group' => $navigationGroup,
        'label' => $navigationLabel,
        'icon' => $navigationIcon,
        'sort' => $navigationSort,
        'has_policy' => $hasPolicy,
        'reason' => $navigationReason,
    ];

    if ($navigationDisabled || $canViewAnyDisabled) {
        if ($navigationDisabled) {
            $analysis['hidden_by_navigation'][] = $resourceInfo;
        }
        if ($canViewAnyDisabled) {
            $analysis['hidden_by_policy'][] = $resourceInfo;
        }
    } else {
        $analysis['visible'][] = $resourceInfo;
    }
}

// Sort by group and sort order
usort($analysis['visible'], function($a, $b) {
    if ($a['group'] !== $b['group']) {
        return strcmp($a['group'], $b['group']);
    }
    return ($a['sort'] ?? 999) - ($b['sort'] ?? 999);
});

// Display results
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║             ✅ VISIBLE RESOURCES (Should Appear)               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$currentGroup = '';
foreach ($analysis['visible'] as $resource) {
    if ($resource['group'] !== $currentGroup) {
        if ($currentGroup !== '') echo "\n";
        echo "📁 {$resource['group']}\n";
        echo str_repeat('─', 60) . "\n";
        $currentGroup = $resource['group'];
    }

    $icon = $resource['icon'] ? str_replace('heroicon-o-', '', $resource['icon']) : 'none';
    $policy = $resource['has_policy'] ? '✅' : '❌';

    echo sprintf(
        "  %2d. %-35s %s Policy:%s\n",
        $resource['sort'] ?? 99,
        $resource['label'] ?: $resource['name'],
        "({$resource['model']})",
        $policy
    );
}

echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         ⚠️ HIDDEN RESOURCES (Intentionally Disabled)           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($analysis['hidden_by_navigation'] as $resource) {
    $policy = $resource['has_policy'] ? '✅' : '❌';
    echo sprintf(
        "  ❌ %-35s Policy:%s\n",
        $resource['label'] ?: $resource['name'],
        $policy
    );
    if ($resource['reason']) {
        echo "     Reason: {$resource['reason']}\n";
    }
    echo "\n";
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  Total Resources:           " . $analysis['total'] . "\n";
echo "  ✅ Visible:                " . count($analysis['visible']) . " (" . round(count($analysis['visible']) / $analysis['total'] * 100, 1) . "%)\n";
echo "  ❌ Hidden (Navigation):    " . count($analysis['hidden_by_navigation']) . " (" . round(count($analysis['hidden_by_navigation']) / $analysis['total'] * 100, 1) . "%)\n";
echo "  ❌ Hidden (Policy):        " . count($analysis['hidden_by_policy']) . " (" . round(count($analysis['hidden_by_policy']) / $analysis['total'] * 100, 1) . "%)\n";
echo "\n";

// Check for critical missing resources
$criticalResources = [
    'CompanyResource' => 'Unternehmen',
    'BranchResource' => 'Filialen',
    'StaffResource' => 'Personal',
    'CustomerResource' => 'Kunden',
    'AppointmentResource' => 'Termine',
    'ServiceResource' => 'Dienstleistungen',
    'CallResource' => 'Anrufe',
    'UserResource' => 'Benutzer',
    'PhoneNumberResource' => 'Telefonnummern',
];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║               CRITICAL RESOURCES CHECK                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$allCriticalVisible = true;
foreach ($criticalResources as $resourceName => $germanName) {
    $isVisible = false;
    foreach ($analysis['visible'] as $visible) {
        if ($visible['name'] === $resourceName) {
            $isVisible = true;
            break;
        }
    }

    if ($isVisible) {
        echo "  ✅ {$germanName} ({$resourceName})\n";
    } else {
        echo "  ❌ {$germanName} ({$resourceName}) - FEHLT!\n";
        $allCriticalVisible = false;
    }
}

echo "\n";
if ($allCriticalVisible) {
    echo "🎉 RESULT: ✅ ALL CRITICAL RESOURCES VISIBLE!\n";
} else {
    echo "⚠️  RESULT: ❌ SOME CRITICAL RESOURCES MISSING!\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Analysis Complete: " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
