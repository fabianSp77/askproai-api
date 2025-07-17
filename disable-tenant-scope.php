<?php
// Disable TenantScope temporarily to fix the system

echo "=== DISABLING TENANT SCOPE ===\n\n";

// 1. Comment out TenantScope in all models
echo "1. Disabling TenantScope in all models...\n";

$modelsPath = __DIR__ . '/app/Models';
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modelsPath),
    RecursiveIteratorIterator::SELF_FIRST
);

$fixedCount = 0;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $modified = false;
        
        // Comment out the TenantScope line
        if (strpos($content, 'static::addGlobalScope(new TenantScope') !== false) {
            $content = str_replace(
                'static::addGlobalScope(new TenantScope',
                '// TEMPORARILY DISABLED: static::addGlobalScope(new TenantScope',
                $content
            );
            $modified = true;
        }
        
        // Also handle variant with parentheses
        if (strpos($content, 'static::addGlobalScope(new TenantScope())') !== false) {
            $content = str_replace(
                'static::addGlobalScope(new TenantScope())',
                '// TEMPORARILY DISABLED: static::addGlobalScope(new TenantScope())',
                $content
            );
            $modified = true;
        }
        
        if ($modified) {
            file_put_contents($file->getPathname(), $content);
            echo "   ✓ Disabled in: " . $file->getFilename() . "\n";
            $fixedCount++;
        }
    }
}

echo "   Disabled TenantScope in $fixedCount files\n\n";

// 2. Create a temporary config to disable tenant scope globally
echo "2. Creating global disable config...\n";
$configContent = '<?php return ["disable_tenant_scope" => true];';
file_put_contents(__DIR__ . '/config/tenant.php', $configContent);
echo "   ✓ Created config/tenant.php\n\n";

// 3. Clear all caches
echo "3. Clearing all caches...\n";
exec('php artisan optimize:clear 2>&1', $output);
foreach ($output as $line) {
    echo "   $line\n";
}

// 4. Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n   ✓ OPcache cleared\n";
}

echo "\n=== TENANT SCOPE DISABLED ===\n";
echo "\nThe system should now work without tenant filtering.\n";
echo "You can re-enable it later by running: php enable-tenant-scope.php\n";