<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;

// Force admin login
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    auth()->login($admin);
}

echo "=== AKTUELLE MENÜ-ANZEIGE ===\n\n";

// Get admin panel
$panel = Filament::getPanel('admin');

// Get all navigation items (Resources + Pages)
$resources = $panel->getResources();
$pages = $panel->getPages();

echo "1. RESOURCES im Menü:\n";
$resourceGroups = [];
foreach ($resources as $resourceClass) {
    if (!class_exists($resourceClass)) continue;
    
    $group = $resourceClass::getNavigationGroup() ?? 'Ungrouped';
    $label = $resourceClass::getNavigationLabel();
    
    $sort = 999;
    try {
        $reflection = new ReflectionClass($resourceClass);
        if ($reflection->hasProperty('navigationSort')) {
            $property = $reflection->getProperty('navigationSort');
            $property->setAccessible(true);
            $sort = $property->getValue() ?? 999;
        }
    } catch (Exception $e) {}
    
    if (!isset($resourceGroups[$group])) {
        $resourceGroups[$group] = [];
    }
    
    $resourceGroups[$group][] = [
        'label' => $label,
        'sort' => $sort,
        'type' => 'resource',
        'class' => class_basename($resourceClass)
    ];
}

echo "\n2. PAGES im Menü:\n";
$pageGroups = [];
foreach ($pages as $pageClass) {
    if (!class_exists($pageClass)) continue;
    
    // Check if page should be in navigation
    if (method_exists($pageClass, 'shouldRegisterNavigation')) {
        if (!$pageClass::shouldRegisterNavigation()) {
            continue;
        }
    }
    
    $group = null;
    $label = null;
    $sort = 999;
    
    try {
        $reflection = new ReflectionClass($pageClass);
        
        // Get navigation group
        if ($reflection->hasProperty('navigationGroup')) {
            $property = $reflection->getProperty('navigationGroup');
            $property->setAccessible(true);
            $group = $property->getValue();
        }
        
        // Get navigation label
        if ($reflection->hasProperty('navigationLabel')) {
            $property = $reflection->getProperty('navigationLabel');
            $property->setAccessible(true);
            $label = $property->getValue();
        } elseif (method_exists($pageClass, 'getNavigationLabel')) {
            $label = $pageClass::getNavigationLabel();
        } else {
            $label = class_basename($pageClass);
        }
        
        // Get sort
        if ($reflection->hasProperty('navigationSort')) {
            $property = $reflection->getProperty('navigationSort');
            $property->setAccessible(true);
            $sort = $property->getValue() ?? 999;
        }
    } catch (Exception $e) {}
    
    if ($group) {
        if (!isset($pageGroups[$group])) {
            $pageGroups[$group] = [];
        }
        
        $pageGroups[$group][] = [
            'label' => $label,
            'sort' => $sort,
            'type' => 'page',
            'class' => class_basename($pageClass)
        ];
    }
}

// Combine and sort
$allGroups = [];

// Add resources
foreach ($resourceGroups as $group => $items) {
    if (!isset($allGroups[$group])) {
        $allGroups[$group] = [];
    }
    $allGroups[$group] = array_merge($allGroups[$group], $items);
}

// Add pages
foreach ($pageGroups as $group => $items) {
    if (!isset($allGroups[$group])) {
        $allGroups[$group] = [];
    }
    $allGroups[$group] = array_merge($allGroups[$group], $items);
}

// Sort groups by minimum sort value
$groupSorts = [];
foreach ($allGroups as $group => $items) {
    $minSort = 999;
    foreach ($items as $item) {
        $minSort = min($minSort, $item['sort']);
    }
    $groupSorts[$group] = $minSort;
}
asort($groupSorts);

echo "\n3. KOMPLETTES MENÜ (sortiert):\n\n";
$position = 1;
foreach ($groupSorts as $group => $groupSort) {
    echo "[$position] $group (Min-Sort: $groupSort)\n";
    
    // Sort items within group
    usort($allGroups[$group], function($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });
    
    foreach ($allGroups[$group] as $item) {
        $type = $item['type'] === 'page' ? '📄' : '📁';
        echo "   └─ $type [{$item['sort']}] {$item['label']} ({$item['class']})\n";
    }
    echo "\n";
    $position++;
}

// Check specific dashboards
echo "4. DASHBOARD STATUS CHECK:\n";
$dashboardsToCheck = [
    'Dashboard',
    'AICallCenter', 
    'SystemMonitoringDashboard',
    'SimpleDashboard'
];

foreach ($dashboardsToCheck as $dashboardName) {
    $class = "App\\Filament\\Admin\\Pages\\$dashboardName";
    if (class_exists($class)) {
        $reflection = new ReflectionClass($class);
        
        $hasNavGroup = $reflection->hasProperty('navigationGroup');
        $hasNavSort = $reflection->hasProperty('navigationSort');
        $hasNavLabel = $reflection->hasProperty('navigationLabel');
        
        echo "\n$dashboardName:\n";
        echo "  - Exists: ✅\n";
        echo "  - Has navigationGroup: " . ($hasNavGroup ? '✅' : '❌') . "\n";
        echo "  - Has navigationSort: " . ($hasNavSort ? '✅' : '❌') . "\n";
        echo "  - Has navigationLabel: " . ($hasNavLabel ? '✅' : '❌') . "\n";
        
        if ($hasNavGroup) {
            $prop = $reflection->getProperty('navigationGroup');
            $prop->setAccessible(true);
            echo "  - Group value: " . ($prop->getValue() ?? 'null') . "\n";
        }
        
        if (method_exists($class, 'shouldRegisterNavigation')) {
            echo "  - shouldRegisterNavigation: " . ($class::shouldRegisterNavigation() ? 'true' : 'false') . "\n";
        }
    } else {
        echo "\n$dashboardName: ❌ Class not found\n";
    }
}

// Check translations
echo "\n5. TRANSLATION CHECK:\n";
$translationFile = base_path('resources/lang/de/admin.php');
if (file_exists($translationFile)) {
    $translations = include $translationFile;
    if (isset($translations['navigation']['dashboards'])) {
        echo "✅ 'dashboards' translation exists: " . $translations['navigation']['dashboards'] . "\n";
    } else {
        echo "❌ 'dashboards' translation missing\n";
    }
}