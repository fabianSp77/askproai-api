<?php
// Emergency Fix Script
echo "=== EMERGENCY FIX RUNNING ===\n\n";

// 1. Fix TenantScope imports
echo "1. Fixing TenantScope imports in models...\n";

$modelsPath = __DIR__ . '/app/Models';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath),
    RecursiveIteratorIterator::SELF_FIRST
);

$fixedCount = 0;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Check if file uses TenantScope
        if (strpos($content, 'use App\Scopes\TenantScope;') !== false || 
            strpos($content, 'new TenantScope') !== false) {
            
            // Make sure the import is correct
            if (strpos($content, 'use App\Scopes\TenantScope;') === false) {
                // Add the import after namespace
                $content = preg_replace(
                    '/(namespace App\\\\Models;)/',
                    "$1\n\nuse App\\Scopes\\TenantScope;",
                    $content
                );
            }
            
            // Save the file
            file_put_contents($file->getPathname(), $content);
            echo "   ✓ Fixed: " . $file->getFilename() . "\n";
            $fixedCount++;
        }
    }
}

echo "   Fixed $fixedCount model files\n\n";

// 2. Clear opcache
echo "2. Clearing PHP opcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "   ✓ Opcache cleared\n";
} else {
    echo "   ⚠ Opcache not enabled\n";
}

// 3. Clear compiled files
echo "\n3. Clearing Laravel compiled files...\n";
$compiledPath = __DIR__ . '/bootstrap/cache/compiled.php';
if (file_exists($compiledPath)) {
    unlink($compiledPath);
    echo "   ✓ Compiled files removed\n";
}

// 4. Clear services cache
$servicesPath = __DIR__ . '/bootstrap/cache/services.php';
if (file_exists($servicesPath)) {
    unlink($servicesPath);
    echo "   ✓ Services cache removed\n";
}

// 5. Clear packages cache
$packagesPath = __DIR__ . '/bootstrap/cache/packages.php';
if (file_exists($packagesPath)) {
    unlink($packagesPath);
    echo "   ✓ Packages cache removed\n";
}

echo "\n=== FIX COMPLETED ===\n";
echo "\nPlease run: php artisan optimize:clear\n";