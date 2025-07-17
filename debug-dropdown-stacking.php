<?php
/**
 * Debug script to analyze dropdown stacking issues
 * Run this to check CSS file loading and potential conflicts
 */

echo "=== Dropdown Stacking Debug Analysis ===\n\n";

// Check if CSS files exist
$cssFiles = [
    'resources/css/filament/admin/dropdown-stacking-consolidated.css',
    'resources/css/filament/admin/sidebar-content-fix.css',
    'resources/css/filament/admin/bulk-action-dropdown-fix.css',
    'resources/css/filament/admin/theme.css'
];

echo "1. CSS File Status:\n";
foreach ($cssFiles as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    echo "   - $file: " . ($exists ? "EXISTS ($size bytes)" : "MISSING") . "\n";
}

// Check theme.css imports
echo "\n2. Theme.css Import Analysis:\n";
$themeContent = file_get_contents('resources/css/filament/admin/theme.css');
preg_match_all('/@import\s+[\'"]([^\'"]+)[\'"];/', $themeContent, $imports);
foreach ($imports[1] as $import) {
    echo "   - $import\n";
}

// Check for conflicting z-index values
echo "\n3. Z-index Values in Theme.css:\n";
preg_match_all('/z-index:\s*([^;]+);/i', $themeContent, $zIndexes);
foreach ($zIndexes[0] as $zIndex) {
    echo "   - $zIndex\n";
}

// Check if we need to rebuild assets
echo "\n4. Asset Build Status:\n";
$publicCss = 'public/css/filament/admin/theme.css';
if (file_exists($publicCss)) {
    $sourceTime = filemtime('resources/css/filament/admin/theme.css');
    $buildTime = filemtime($publicCss);
    echo "   - Source modified: " . date('Y-m-d H:i:s', $sourceTime) . "\n";
    echo "   - Build modified: " . date('Y-m-d H:i:s', $buildTime) . "\n";
    echo "   - Needs rebuild: " . ($sourceTime > $buildTime ? "YES" : "NO") . "\n";
} else {
    echo "   - Built CSS not found!\n";
}

// Suggest commands
echo "\n5. Recommended Actions:\n";
echo "   1. Clear all caches:\n";
echo "      php artisan optimize:clear\n";
echo "   2. Rebuild assets:\n";
echo "      npm run build\n";
echo "   3. Clear browser cache (Ctrl+Shift+R)\n";
echo "   4. Test in browser console:\n";
echo "      debugStackingContexts()\n";

echo "\n=== End Debug Analysis ===\n";