<?php

echo "=== COMPREHENSIVE CSS STRUCTURE ANALYSIS ===\n\n";

// 1. Check all CSS files in the build
echo "1. CSS Files in Build Directory:\n";
$buildDir = 'public/build/css/';
if (is_dir($buildDir)) {
    $files = scandir($buildDir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.css')) {
            $size = filesize($buildDir . $file);
            echo "   - $file (" . number_format($size / 1024, 2) . " KB)\n";
        }
    }
}

// 2. Analyze theme.css for potential issues
echo "\n2. Theme CSS Analysis:\n";
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
if (isset($manifest['resources/css/filament/admin/theme.css'])) {
    $themeFile = 'public/build/' . $manifest['resources/css/filament/admin/theme.css']['file'];
    if (file_exists($themeFile)) {
        $content = file_get_contents($themeFile);
        
        // Check for problematic patterns
        $patterns = [
            'position: fixed' => substr_count($content, 'position:fixed'),
            'position: absolute' => substr_count($content, 'position:absolute'),
            'z-index' => substr_count($content, 'z-index'),
            'overflow: hidden' => substr_count($content, 'overflow:hidden'),
            'pointer-events' => substr_count($content, 'pointer-events'),
            'transform' => substr_count($content, 'transform'),
            'scale' => substr_count($content, 'scale'),
            'zoom' => substr_count($content, 'zoom'),
        ];
        
        foreach ($patterns as $pattern => $count) {
            echo "   - $pattern: $count occurrences\n";
        }
    }
}

// 3. Check for CSS loading in templates
echo "\n3. CSS Loading in Templates:\n";
$templates = [
    'resources/views/vendor/filament-panels/components/layout/base.blade.php',
    'resources/views/vendor/filament-panels/components/layout/index.blade.php'
];

foreach ($templates as $template) {
    if (file_exists($template)) {
        $content = file_get_contents($template);
        echo "   - " . basename($template) . ":\n";
        if (str_contains($content, '@vite')) {
            preg_match_all('/@vite\(\[(.*?)\]\)/', $content, $matches);
            foreach ($matches[1] as $match) {
                echo "     Loading: $match\n";
            }
        }
    }
}

// 4. Check AdminPanelProvider hooks
echo "\n4. AdminPanelProvider Hooks:\n";
$provider = file_get_contents('app/Providers/Filament/AdminPanelProvider.php');
if (str_contains($provider, 'renderHook')) {
    echo "   ✓ Uses renderHook\n";
    if (str_contains($provider, 'HEAD_END')) {
        echo "   ✓ HEAD_END hook found\n";
    }
}

// 5. Browser-specific CSS issues
echo "\n5. Potential Issues for Screenshot:\n";
echo "   - Sidebar overlapping content: Could be z-index or position issue\n";
echo "   - Navigation not clickable: Likely overlay or pointer-events issue\n";
echo "   - Content cut off on right: Could be overflow or width calculation issue\n";
echo "   - Header covered: Z-index stacking context problem\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Create a debug CSS to identify overlapping elements\n";
echo "2. Use browser DevTools to inspect z-index stack\n";
echo "3. Check for invisible overlays blocking clicks\n";
echo "4. Verify viewport and container widths\n";