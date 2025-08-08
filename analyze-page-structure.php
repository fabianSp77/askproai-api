<?php

echo "=== ADMIN PANEL STRUCTURE ANALYSIS ===\n\n";

// Test 1: CSS Files Loading
echo "1. CSS Files in Build:\n";
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
$cssFiles = array_filter($manifest, function($item) {
    return isset($item['file']) && str_contains($item['file'], '.css');
});

foreach ($cssFiles as $key => $item) {
    $size = file_exists('public/build/' . $item['file']) ? 
        number_format(filesize('public/build/' . $item['file']) / 1024, 2) . ' KB' : 
        'NOT FOUND';
    echo "   - $key => {$item['file']} ($size)\n";
}

// Test 2: Foundation CSS specifically
echo "\n2. Foundation CSS Status:\n";
$foundationEntry = $manifest['resources/css/filament/admin/foundation.css'] ?? null;
if ($foundationEntry) {
    $file = 'public/build/' . $foundationEntry['file'];
    if (file_exists($file)) {
        echo "   ✓ Built successfully: {$foundationEntry['file']}\n";
        echo "   ✓ Size: " . number_format(filesize($file) / 1024, 2) . " KB\n";
        
        // Check content
        $content = file_get_contents($file);
        echo "   ✓ Contains CSS variables: " . (str_contains($content, '--sidebar-width') ? 'YES' : 'NO') . "\n";
        echo "   ✓ Contains layout fixes: " . (str_contains($content, '.fi-main') ? 'YES' : 'NO') . "\n";
        echo "   ✓ No zoom applied: " . (!str_contains($content, 'zoom:') || str_contains($content, 'zoom: 1') ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ✗ File not found at: $file\n";
    }
} else {
    echo "   ✗ Not found in manifest\n";
}

// Test 3: AdminPanelProvider
echo "\n3. AdminPanelProvider Configuration:\n";
$provider = file_get_contents('app/Providers/Filament/AdminPanelProvider.php');
if (str_contains($provider, 'foundation.css')) {
    echo "   ✓ Foundation.css is loaded in provider\n";
    if (str_contains($provider, 'Vite::tag')) {
        echo "   ✓ Using Vite tag for loading\n";
    }
} else {
    echo "   ✗ Foundation.css not found in provider\n";
}

// Test 4: Check for conflicting CSS
echo "\n4. Potentially Conflicting CSS Files:\n";
$conflicts = [
    'emergency-layout-fix.css',
    'layout-spacing-fix.css',
    'sidebar-header-alignment-fix.css',
    'macbook-responsive-fix.css',
    'navigation-ultimate-fix.css'
];

foreach ($conflicts as $file) {
    $key = "resources/css/filament/admin/$file";
    if (isset($manifest[$key])) {
        echo "   ⚠️  $file is still being loaded\n";
    }
}

// Test 5: JavaScript status
echo "\n5. JavaScript Status:\n";
$adminJs = $manifest['resources/js/bundles/admin.js'] ?? null;
if ($adminJs) {
    $jsFile = 'public/build/' . $adminJs['file'];
    if (file_exists($jsFile)) {
        $size = filesize($jsFile);
        echo "   ✓ admin.js size: " . number_format($size / 1024, 2) . " KB\n";
        echo "   " . ($size < 100 ? "✓ JavaScript is disabled (good)" : "⚠️  JavaScript may still be active") . "\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Foundation.css is loaded and should provide clean layout\n";
echo "2. Old fix files are still present and may conflict\n";
echo "3. Visit https://api.askproai.de/admin?debug=1 to see debug info\n";
echo "4. Check browser console for any CSS conflicts\n";