<?php

use App\Filament\Admin\Resources;
use App\Filament\Admin\Pages;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get all resource classes
$resourcePath = app_path('Filament/Admin/Resources');
$resourceFiles = glob($resourcePath . '/*Resource.php');

echo "=== RESOURCES ===\n\n";

foreach ($resourceFiles as $file) {
    $className = 'App\\Filament\\Admin\\Resources\\' . basename($file, '.php');
    
    if (!class_exists($className)) {
        continue;
    }
    
    // Skip abstract classes
    $reflection = new ReflectionClass($className);
    if ($reflection->isAbstract()) {
        continue;
    }
    
    try {
        $navLabel = $className::getNavigationLabel();
        $navGroup = $className::getNavigationGroup();
        $navSort = $className::getNavigationSort();
        $shouldShow = method_exists($className, 'shouldRegisterNavigation') 
            ? 'Has condition' 
            : 'Always visible';
        
        echo sprintf(
            "%-30s | Label: %-25s | Group: %-20s | Sort: %-5s | %s\n",
            basename($file, '.php'),
            $navLabel ?: 'DEFAULT',
            $navGroup ?: 'NONE',
            $navSort ?: '-',
            $shouldShow
        );
    } catch (Exception $e) {
        echo sprintf("%-30s | ERROR: %s\n", basename($file, '.php'), $e->getMessage());
    }
}

echo "\n\n=== PAGES ===\n\n";

// Get all page classes
$pagesPath = app_path('Filament/Admin/Pages');
$pageFiles = glob($pagesPath . '/*.php');

foreach ($pageFiles as $file) {
    $className = 'App\\Filament\\Admin\\Pages\\' . basename($file, '.php');
    
    if (!class_exists($className)) {
        continue;
    }
    
    // Skip abstract classes
    $reflection = new ReflectionClass($className);
    if ($reflection->isAbstract()) {
        continue;
    }
    
    try {
        $navLabel = property_exists($className, 'navigationLabel') && $className::$navigationLabel 
            ? $className::$navigationLabel 
            : (property_exists($className, 'title') ? $className::$title : 'DEFAULT');
        $navGroup = property_exists($className, 'navigationGroup') ? $className::$navigationGroup : 'NONE';
        $navSort = property_exists($className, 'navigationSort') ? $className::$navigationSort : '-';
        $shouldShow = method_exists($className, 'shouldRegisterNavigation') 
            ? 'Has condition' 
            : 'Always visible';
        
        echo sprintf(
            "%-30s | Label: %-25s | Group: %-20s | Sort: %-5s | %s\n",
            basename($file, '.php'),
            $navLabel ?: 'DEFAULT',
            $navGroup ?: 'NONE',
            $navSort ?: '-',
            $shouldShow
        );
    } catch (Exception $e) {
        echo sprintf("%-30s | ERROR: %s\n", basename($file, '.php'), $e->getMessage());
    }
}