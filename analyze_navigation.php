<?php

$results = [
    'resources' => [],
    'pages' => [],
    'groups' => [],
    'conflicts' => [],
    'missing_configs' => []
];

// Helper function to extract class content
function getClassContent($file) {
    if (!file_exists($file)) {
        return null;
    }
    return file_get_contents($file);
}

// Helper function to extract method or property value
function extractValue($content, $pattern, $default = null) {
    if (preg_match($pattern, $content, $matches)) {
        return trim($matches[1], '\'"');
    }
    return $default;
}

// Analyze Resources
$resourceFiles = glob('/var/www/api-gateway/app/Filament/Admin/Resources/*Resource.php');
foreach ($resourceFiles as $file) {
    if (strpos($file, '.disabled') !== false || strpos($file, '.backup') !== false || strpos($file, '.simple') !== false) {
        continue;
    }
    
    $content = getClassContent($file);
    if (!$content) continue;
    
    $className = basename($file, '.php');
    
    // Skip if class is commented out or doesn't extend Resource
    if (!preg_match('/class\s+' . $className . '\s+extends\s+.*Resource/', $content)) {
        continue;
    }
    
    $resourceData = [
        'file' => $file,
        'class' => $className,
        'navigation_group' => extractValue($content, '/getNavigationGroup\(\)[^{]*{[^}]*return\s+[\'"]([^\'"]+)[\'"]/', null),
        'navigation_label' => extractValue($content, '/getNavigationLabel\(\)[^{]*{[^}]*return\s+[\'"]([^\'"]+)[\'"]/', null),
        'navigation_sort' => extractValue($content, '/protected\s+static\s+\?int\s+\$navigationSort\s*=\s*(\d+)/', null),
        'navigation_icon' => extractValue($content, '/protected\s+static\s+\?string\s+\$navigationIcon\s*=\s*[\'"]([^\'"]+)[\'"]/', null),
        'should_register' => !preg_match('/shouldRegisterNavigation\(\)[^{]*{[^}]*return\s+false/', $content),
        'model' => extractValue($content, '/protected\s+static\s+\?string\s+\$model\s*=\s*([^;]+)/', null),
    ];
    
    // Extract label if no navigation label
    if (!$resourceData['navigation_label']) {
        $resourceData['navigation_label'] = extractValue($content, '/protected\s+static\s+\?string\s+\$label\s*=\s*[\'"]([^\'"]+)[\'"]/', null);
    }
    
    // Extract plural label for default navigation label
    if (!$resourceData['navigation_label']) {
        $resourceData['navigation_label'] = extractValue($content, '/protected\s+static\s+\?string\s+\$pluralLabel\s*=\s*[\'"]([^\'"]+)[\'"]/', null);
    }
    
    $results['resources'][] = $resourceData;
    
    // Track navigation groups
    if ($resourceData['navigation_group'] && $resourceData['should_register']) {
        if (!isset($results['groups'][$resourceData['navigation_group']])) {
            $results['groups'][$resourceData['navigation_group']] = [];
        }
        $results['groups'][$resourceData['navigation_group']][] = $resourceData;
    }
}

// Analyze Pages
$pageFiles = glob('/var/www/api-gateway/app/Filament/Admin/Pages/*.php');
foreach ($pageFiles as $file) {
    if (strpos($file, '.disabled') !== false || strpos($file, '.backup') !== false) {
        continue;
    }
    
    $content = getClassContent($file);
    if (!$content) continue;
    
    $className = basename($file, '.php');
    
    // Skip if class is commented out or doesn't extend Page
    if (!preg_match('/class\s+' . $className . '\s+extends\s+.*Page/', $content)) {
        continue;
    }
    
    $pageData = [
        'file' => $file,
        'class' => $className,
        'navigation_group' => extractValue($content, '/getNavigationGroup\(\)[^{]*{[^}]*return\s+[\'"]([^\'"]+)[\'"]/', null),
        'navigation_label' => extractValue($content, '/getNavigationLabel\(\)[^{]*{[^}]*return\s+[\'"]([^\'"]+)[\'"]/', null),
        'navigation_sort' => extractValue($content, '/protected\s+static\s+\?int\s+\$navigationSort\s*=\s*(\d+)/', null),
        'navigation_icon' => extractValue($content, '/protected\s+static\s+\?string\s+\$navigationIcon\s*=\s*[\'"]([^\'"]+)[\'"]/', null),
        'should_register' => !preg_match('/shouldRegisterNavigation\(\)[^{]*{[^}]*return\s+false/', $content),
        'title' => extractValue($content, '/protected\s+static\s+\?string\s+\$title\s*=\s*[\'"]([^\'"]+)[\'"]/', null),
        'slug' => extractValue($content, '/protected\s+static\s+string\s+\$slug\s*=\s*[\'"]([^\'"]+)[\'"]/', null),
    ];
    
    // Extract label if no navigation label
    if (!$pageData['navigation_label'] && $pageData['title']) {
        $pageData['navigation_label'] = $pageData['title'];
    }
    
    $results['pages'][] = $pageData;
    
    // Track navigation groups
    if ($pageData['navigation_group'] && $pageData['should_register']) {
        if (!isset($results['groups'][$pageData['navigation_group']])) {
            $results['groups'][$pageData['navigation_group']] = [];
        }
        $results['groups'][$pageData['navigation_group']][] = $pageData;
    }
}

// Check for conflicts and missing configs
foreach ($results['groups'] as $groupName => $items) {
    // Sort by navigation sort
    usort($items, function($a, $b) {
        $sortA = $a['navigation_sort'] ?? 999;
        $sortB = $b['navigation_sort'] ?? 999;
        return $sortA - $sortB;
    });
    
    // Check for duplicate sort positions
    $sortPositions = [];
    foreach ($items as $item) {
        if ($item['navigation_sort']) {
            if (isset($sortPositions[$item['navigation_sort']])) {
                $results['conflicts'][] = [
                    'type' => 'duplicate_sort',
                    'group' => $groupName,
                    'position' => $item['navigation_sort'],
                    'items' => [$sortPositions[$item['navigation_sort']], $item['class']]
                ];
            } else {
                $sortPositions[$item['navigation_sort']] = $item['class'];
            }
        }
    }
}

// Check for missing configurations
foreach (array_merge($results['resources'], $results['pages']) as $item) {
    if ($item['should_register']) {
        $missing = [];
        if (!$item['navigation_group']) $missing[] = 'navigation_group';
        if (!$item['navigation_icon']) $missing[] = 'navigation_icon';
        if (!$item['navigation_label']) $missing[] = 'navigation_label';
        
        if (!empty($missing)) {
            $results['missing_configs'][] = [
                'class' => $item['class'],
                'missing' => $missing
            ];
        }
    }
}

// Output results
echo "=== FILAMENT NAVIGATION STRUCTURE ANALYSIS ===\n\n";

echo "NAVIGATION GROUPS:\n";
echo "==================\n";
foreach ($results['groups'] as $groupName => $items) {
    echo "\n📁 $groupName\n";
    echo str_repeat('-', strlen($groupName) + 4) . "\n";
    
    usort($items, function($a, $b) {
        $sortA = $a['navigation_sort'] ?? 999;
        $sortB = $b['navigation_sort'] ?? 999;
        return $sortA - $sortB;
    });
    
    foreach ($items as $item) {
        $type = isset($item['model']) ? 'R' : 'P';
        $sort = $item['navigation_sort'] ?? '---';
        $icon = $item['navigation_icon'] ?? '❓';
        $label = $item['navigation_label'] ?? $item['class'];
        echo sprintf("  [%s] %3s | %s %s\n", $type, $sort, $icon, $label);
    }
}

echo "\n\nITEMS WITHOUT GROUPS:\n";
echo "=====================\n";
foreach (array_merge($results['resources'], $results['pages']) as $item) {
    if ($item['should_register'] && !$item['navigation_group']) {
        $type = isset($item['model']) ? 'Resource' : 'Page';
        echo sprintf("- %s: %s (Label: %s)\n", $type, $item['class'], $item['navigation_label'] ?? 'None');
    }
}

echo "\n\nNAVIGATION CONFLICTS:\n";
echo "===================\n";
if (empty($results['conflicts'])) {
    echo "✅ No conflicts found\n";
} else {
    foreach ($results['conflicts'] as $conflict) {
        if ($conflict['type'] === 'duplicate_sort') {
            echo sprintf("⚠️  Duplicate sort position %d in group '%s': %s\n", 
                $conflict['position'], 
                $conflict['group'], 
                implode(', ', $conflict['items'])
            );
        }
    }
}

echo "\n\nMISSING CONFIGURATIONS:\n";
echo "======================\n";
if (empty($results['missing_configs'])) {
    echo "✅ All items properly configured\n";
} else {
    foreach ($results['missing_configs'] as $missing) {
        echo sprintf("⚠️  %s is missing: %s\n", 
            $missing['class'], 
            implode(', ', $missing['missing'])
        );
    }
}

echo "\n\nDISABLED ITEMS:\n";
echo "===============\n";
foreach (array_merge($results['resources'], $results['pages']) as $item) {
    if (!$item['should_register']) {
        $type = isset($item['model']) ? 'Resource' : 'Page';
        echo sprintf("- %s: %s\n", $type, $item['class']);
    }
}

echo "\n\nSUMMARY:\n";
echo "========\n";
$activeResources = count(array_filter($results['resources'], fn($r) => $r['should_register']));
$activePages = count(array_filter($results['pages'], fn($p) => $p['should_register']));
echo sprintf("Total Resources: %d (Active: %d)\n", count($results['resources']), $activeResources);
echo sprintf("Total Pages: %d (Active: %d)\n", count($results['pages']), $activePages);
echo sprintf("Navigation Groups: %d\n", count($results['groups']));
echo sprintf("Conflicts: %d\n", count($results['conflicts']));
echo sprintf("Missing Configs: %d\n", count($results['missing_configs']));

// Save detailed JSON output
file_put_contents('/var/www/api-gateway/navigation_analysis.json', json_encode($results, JSON_PRETTY_PRINT));
echo "\n\n✅ Detailed analysis saved to navigation_analysis.json\n";