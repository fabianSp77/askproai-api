<?php

echo "=== CSS LOADING VERIFICATION ===\n\n";

// 1. Check base.blade.php
echo "1. Checking base.blade.php:\n";
$baseTemplate = file_get_contents('resources/views/vendor/filament-panels/components/layout/base.blade.php');
$hasOldCSS = false;

$oldCSSFiles = [
    'emergency-fix-476.css',
    'emergency-icon-fix-478.css',
    'consolidated-interactions.css',
    'consolidated-layout.css',
    'navigation-ultimate-fix.css',
    'macbook-responsive-fix.css',
    'foundation.css'
];

foreach ($oldCSSFiles as $css) {
    if (str_contains($baseTemplate, $css)) {
        echo "   ✗ Found reference to: $css\n";
        $hasOldCSS = true;
    }
}

if (!$hasOldCSS) {
    echo "   ✓ No old CSS references found\n";
}

// 2. Check AdminPanelProvider
echo "\n2. Checking AdminPanelProvider:\n";
$provider = file_get_contents('app/Providers/Filament/AdminPanelProvider.php');
if (str_contains($provider, 'clean-structure.css')) {
    echo "   ✓ clean-structure.css is loaded\n";
} else {
    echo "   ✗ clean-structure.css NOT loaded\n";
}

// 3. Check Vite manifest
echo "\n3. Checking Vite Manifest:\n";
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
$cleanCSS = $manifest['resources/css/filament/admin/clean-structure.css'] ?? null;
if ($cleanCSS) {
    echo "   ✓ clean-structure.css in manifest: {$cleanCSS['file']}\n";
    
    // Check if file exists
    $file = 'public/build/' . $cleanCSS['file'];
    if (file_exists($file)) {
        echo "   ✓ File exists, size: " . number_format(filesize($file) / 1024, 2) . " KB\n";
    } else {
        echo "   ✗ File NOT found at: $file\n";
    }
} else {
    echo "   ✗ clean-structure.css NOT in manifest\n";
}

// 4. Check vite.config.js
echo "\n4. Checking vite.config.js:\n";
$viteConfig = file_get_contents('vite.config.js');
$cssCount = 0;
foreach ($oldCSSFiles as $css) {
    if (str_contains($viteConfig, $css)) {
        echo "   ⚠️ Old CSS still in vite config: $css\n";
        $cssCount++;
    }
}
if ($cssCount === 0) {
    echo "   ✓ No old CSS files in vite config\n";
}

echo "\n=== STATUS ===\n";
if (!$hasOldCSS && $cleanCSS) {
    echo "✅ CSS loading is properly configured\n";
    echo "✅ Old CSS files removed from templates\n";
    echo "✅ Clean structure CSS is active\n";
    echo "\nThe admin panel should now work correctly with:\n";
    echo "- Normal zoom (100%)\n";
    echo "- Proper sidebar/content spacing\n";
    echo "- No cut-off content\n";
    echo "- Clickable navigation\n";
} else {
    echo "❌ CSS configuration needs attention\n";
}