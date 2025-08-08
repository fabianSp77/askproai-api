<?php

echo "=== DESIGN ANALYSIS - Issue #510 ===\n\n";

// 1. Check what CSS files are currently loaded
echo "1. Currently loaded CSS files:\n";
$manifest = json_decode(file_get_contents('/var/www/api-gateway/public/build/manifest.json'), true);
foreach ($manifest as $key => $asset) {
    if (isset($asset['file']) && strpos($asset['file'], '.css') !== false) {
        echo "   - $key -> " . $asset['file'] . "\n";
        $cssFile = '/var/www/api-gateway/public/build/' . $asset['file'];
        if (file_exists($cssFile)) {
            $size = round(filesize($cssFile) / 1024, 2);
            echo "     Size: {$size} KB\n";
        }
    }
}

// 2. Check theme configuration
echo "\n2. Filament Theme Configuration:\n";
$adminProvider = file_get_contents('/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php');

// Check for theme settings
if (preg_match('/->colors\((.*?)\)/s', $adminProvider, $matches)) {
    echo "   Colors configured: YES\n";
}

if (strpos($adminProvider, '->font(') !== false) {
    echo "   Custom font: YES\n";
}

if (strpos($adminProvider, '->darkMode(') !== false) {
    echo "   Dark mode: CONFIGURED\n";
}

if (strpos($adminProvider, '->maxContentWidth(') !== false) {
    preg_match('/->maxContentWidth\((.*?)\)/', $adminProvider, $matches);
    echo "   Max content width: " . ($matches[1] ?? 'unknown') . "\n";
}

// 3. Check base template for design issues
echo "\n3. Base Template Analysis:\n";
$baseTemplate = file_get_contents('/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/base.blade.php');

// Count inline styles
$inlineStyleCount = substr_count($baseTemplate, 'style=');
$styleTagCount = substr_count($baseTemplate, '<style');
echo "   Inline styles: $inlineStyleCount\n";
echo "   Style tags: $styleTagCount\n";

// Check for problematic CSS
if (strpos($baseTemplate, 'NUCLEAR FIX ACTIVE') !== false) {
    echo "   ⚠️  Nuclear fix banner is active\n";
}

if (strpos($baseTemplate, 'pointer-events: auto !important') !== false) {
    echo "   ⚠️  Aggressive pointer-events overrides found\n";
}

// 4. Check specific design CSS files
echo "\n4. Design-affecting CSS files:\n";
$cssFiles = [
    'nuclear-fix.css' => 'resources/css/filament/admin/nuclear-fix.css',
    'login-fix.css' => 'resources/css/filament/admin/login-fix.css',
    'theme.css' => 'resources/css/filament/admin/theme.css'
];

foreach ($cssFiles as $name => $path) {
    $fullPath = '/var/www/api-gateway/' . $path;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $size = round(strlen($content) / 1024, 2);
        echo "   - $name: {$size} KB\n";
        
        // Check for problematic patterns
        if (strpos($content, 'background: #ff0000') !== false || 
            strpos($content, 'background: red') !== false ||
            strpos($content, 'background: #dc2626') !== false) {
            echo "     ⚠️  Contains red/warning backgrounds\n";
        }
        
        if (strpos($content, 'content:') !== false && strpos($content, '::before') !== false) {
            echo "     ⚠️  Contains ::before pseudo-elements with content\n";
        }
        
        if (strpos($content, 'position: fixed') !== false && strpos($content, 'top: 0') !== false) {
            echo "     ⚠️  Contains fixed position banners\n";
        }
        
        // Count !important declarations
        $importantCount = substr_count($content, '!important');
        if ($importantCount > 20) {
            echo "     ⚠️  High number of !important: $importantCount\n";
        }
    }
}

// 5. Check for design-breaking JavaScript
echo "\n5. JavaScript affecting design:\n";
if (strpos($baseTemplate, 'nuclear-unblock-everything.js') !== false) {
    echo "   ⚠️  Nuclear unblock script is active (adds red banner)\n";
}

// 6. Current state summary
echo "\n=== DESIGN ISSUES SUMMARY ===\n";
echo "1. Nuclear mode active - shows red warning banner\n";
echo "2. Multiple aggressive CSS overrides with !important\n";
echo "3. Debug indicators visible (Login Fix Active, etc.)\n";
echo "4. Pointer-events forced on all elements\n";
echo "5. Multiple fix layers stacked on top of each other\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Remove nuclear mode and warning banners\n";
echo "2. Consolidate CSS fixes into one clean file\n";
echo "3. Remove debug indicators from production\n";
echo "4. Use Filament's native styling system\n";
echo "5. Clean up !important overrides\n";
?>