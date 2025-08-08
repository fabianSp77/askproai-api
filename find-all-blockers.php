<?php

echo "=== FINDING ALL CLICK BLOCKERS - Issue #509 ===\n\n";

// Check what JavaScript files are actually loaded
echo "1. JavaScript files in public/js/:\n";
$jsFiles = glob('/var/www/api-gateway/public/js/*.js');
foreach ($jsFiles as $file) {
    $size = filesize($file);
    $modified = date('Y-m-d H:i:s', filemtime($file));
    echo "   - " . basename($file) . " ({$size} bytes, modified: {$modified})\n";
    
    // Check for blocking patterns
    $content = file_get_contents($file);
    if (strpos($content, 'preventDefault') !== false || 
        strpos($content, 'stopPropagation') !== false ||
        strpos($content, 'pointer-events') !== false ||
        strpos($content, 'return false') !== false) {
        echo "     ⚠️  Contains blocking code!\n";
    }
}

echo "\n2. CSS files that might block:\n";
$cssFiles = array_merge(
    glob('/var/www/api-gateway/public/css/*.css'),
    glob('/var/www/api-gateway/public/build/css/*.css')
);

foreach ($cssFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'pointer-events: none') !== false ||
        strpos($content, 'pointer-events:none') !== false) {
        echo "   ⚠️  " . basename($file) . " - Contains pointer-events: none\n";
        
        // Count occurrences
        $count = substr_count($content, 'pointer-events: none') + substr_count($content, 'pointer-events:none');
        echo "      Found $count occurrences\n";
    }
}

echo "\n3. Checking build manifest:\n";
$manifest = json_decode(file_get_contents('/var/www/api-gateway/public/build/manifest.json'), true);
$jsAssets = array_filter($manifest, function($item) {
    return isset($item['file']) && strpos($item['file'], '.js') !== false;
});

foreach ($jsAssets as $key => $asset) {
    echo "   - $key -> " . $asset['file'] . "\n";
}

echo "\n4. Checking loaded scripts in base.blade.php:\n";
$baseTemplate = file_get_contents('/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/base.blade.php');

// Find all script tags
preg_match_all('/<script[^>]*src="([^"]+)"/', $baseTemplate, $scriptMatches);
foreach ($scriptMatches[1] as $script) {
    echo "   - $script\n";
}

// Find all @vite directives
preg_match_all('/@vite\(\[(.*?)\]\)/', $baseTemplate, $viteMatches);
foreach ($viteMatches[1] as $vite) {
    echo "   - @vite: $vite\n";
}

echo "\n5. Checking for global event listeners:\n";
$publicJsFiles = glob('/var/www/api-gateway/public/js/*.js');
foreach ($publicJsFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for global event listeners that might block
    if (preg_match('/document\.addEventListener\s*\(\s*[\'"]click[\'"]/i', $content)) {
        echo "   ⚠️  " . basename($file) . " - Has global click listener\n";
    }
    
    if (preg_match('/window\.addEventListener\s*\(\s*[\'"]click[\'"]/i', $content)) {
        echo "   ⚠️  " . basename($file) . " - Has window click listener\n";
    }
}

echo "\n6. Checking Alpine/Livewire configuration:\n";
$configFiles = [
    '/var/www/api-gateway/config/filament.php',
    '/var/www/api-gateway/config/livewire.php',
    '/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php'
];

foreach ($configFiles as $config) {
    if (file_exists($config)) {
        echo "   - " . basename($config) . " exists\n";
        $content = file_get_contents($config);
        
        // Check for problematic settings
        if (strpos($content, 'spa(') !== false) {
            echo "     ⚠️  SPA mode might be enabled\n";
        }
        if (strpos($content, 'globalSearch(false)') !== false) {
            echo "     Note: Global search disabled\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "This analysis will help identify what's blocking all clicks.\n";
echo "Next step: Create a nuclear solution to remove ALL blockers.\n";
?>