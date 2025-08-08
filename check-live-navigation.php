<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;

// Clear all Filament caches
Cache::flush();

echo "=== LIVE NAVIGATION CHECK ===\n\n";

// Get admin panel
$panel = Filament::getPanel('admin');

// Check what's actually registered
echo "1. REGISTERED PAGES:\n";
$pages = $panel->getPages();
foreach ($pages as $pageClass) {
    if (!class_exists($pageClass)) {
        echo "   ❌ Class not found: $pageClass\n";
        continue;
    }
    
    $reflection = new ReflectionClass($pageClass);
    
    // Check if should be in navigation
    $shouldShow = true;
    if ($reflection->hasMethod('shouldRegisterNavigation')) {
        $method = $reflection->getMethod('shouldRegisterNavigation');
        $shouldShow = $method->invoke(null);
    }
    
    $group = 'No Group';
    $label = class_basename($pageClass);
    $sort = 999;
    
    if ($reflection->hasProperty('navigationGroup')) {
        $prop = $reflection->getProperty('navigationGroup');
        $prop->setAccessible(true);
        $group = $prop->getValue() ?? 'No Group';
    }
    
    if ($reflection->hasProperty('navigationLabel')) {
        $prop = $reflection->getProperty('navigationLabel');
        $prop->setAccessible(true);
        $label = $prop->getValue() ?? class_basename($pageClass);
    }
    
    if ($reflection->hasProperty('navigationSort')) {
        $prop = $reflection->getProperty('navigationSort');
        $prop->setAccessible(true);
        $sort = $prop->getValue() ?? 999;
    }
    
    $status = $shouldShow ? '✅' : '🚫';
    echo "   $status [$sort] $label ($group) - " . class_basename($pageClass) . "\n";
}

echo "\n2. REGISTERED RESOURCES:\n";
$resources = $panel->getResources();
$resourceGroups = [];
foreach ($resources as $resourceClass) {
    if (!class_exists($resourceClass)) continue;
    
    $group = $resourceClass::getNavigationGroup() ?? 'No Group';
    $label = $resourceClass::getNavigationLabel();
    
    if (!isset($resourceGroups[$group])) {
        $resourceGroups[$group] = [];
    }
    $resourceGroups[$group][] = $label;
}

foreach ($resourceGroups as $group => $items) {
    echo "   $group: " . count($items) . " items\n";
}

echo "\n3. CHECKING SPECIFIC FILES:\n";

// Check SimpleDashboard
$simpleDash = '/var/www/api-gateway/app/Filament/Admin/Pages/SimpleDashboard.php';
if (file_exists($simpleDash)) {
    $content = file_get_contents($simpleDash);
    
    // Get navigation properties
    preg_match('/navigationGroup\s*=\s*"([^"]+)"/', $content, $groupMatch);
    preg_match('/navigationLabel\s*=\s*"([^"]+)"/', $content, $labelMatch);
    preg_match('/navigationSort\s*=\s*(\d+)/', $content, $sortMatch);
    
    echo "SimpleDashboard.php:\n";
    echo "   Group: " . ($groupMatch[1] ?? 'not set') . "\n";
    echo "   Label: " . ($labelMatch[1] ?? 'not set') . "\n";
    echo "   Sort: " . ($sortMatch[1] ?? 'not set') . "\n";
    
    if (strpos($content, 'shouldRegisterNavigation') !== false) {
        echo "   ⚠️  Has shouldRegisterNavigation method\n";
    } else {
        echo "   ✅ No shouldRegisterNavigation (visible by default)\n";
    }
}

// Check if there's a bootstrap cache issue
echo "\n4. CACHE STATUS:\n";
$bootstrapCache = base_path('bootstrap/cache/filament');
if (is_dir($bootstrapCache)) {
    $files = glob($bootstrapCache . '/*');
    echo "   Filament cache files: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "   ⚠️  Cache files exist - try: rm -rf bootstrap/cache/filament\n";
    }
} else {
    echo "   ✅ No Filament cache directory\n";
}

// Check compiled views
$viewCache = storage_path('framework/views');
$viewCount = count(glob($viewCache . '/*.php'));
echo "   Compiled views: $viewCount\n";
if ($viewCount > 100) {
    echo "   ⚠️  Many compiled views - try: php artisan view:clear\n";
}

echo "\n5. LANGUAGE FILES:\n";
$langFiles = [
    '/var/www/api-gateway/lang/de/admin.php',
    '/var/www/api-gateway/resources/lang/de/admin.php'
];

foreach ($langFiles as $file) {
    if (file_exists($file)) {
        $trans = include $file;
        if (isset($trans['navigation']['dashboards'])) {
            echo "   ✅ $file: 'dashboards' = '" . $trans['navigation']['dashboards'] . "'\n";
        }
    }
}

echo "\n6. POTENTIAL ISSUES:\n";
// Check for duplicate class names
$declaredClasses = get_declared_classes();
$dashboardClasses = array_filter($declaredClasses, fn($c) => strpos($c, 'Dashboard') !== false);
if (count($dashboardClasses) > 10) {
    echo "   ⚠️  Many Dashboard classes loaded: " . count($dashboardClasses) . "\n";
}

// Check opcache
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status && $status['opcache_enabled']) {
        echo "   ⚠️  OPcache is enabled - try: php artisan optimize:clear\n";
    }
}