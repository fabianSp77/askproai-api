<?php
echo "=== RE-ENABLING MODELS ===\n\n";

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
        
        // Re-enable TenantScope
        if (strpos($content, '// TEMPORARILY DISABLED: static::addGlobalScope(new TenantScope') !== false) {
            $content = str_replace(
                '// TEMPORARILY DISABLED: static::addGlobalScope(new TenantScope',
                'static::addGlobalScope(new TenantScope',
                $content
            );
            $modified = true;
        }
        
        if ($modified) {
            file_put_contents($file->getPathname(), $content);
            echo "   ✓ Re-enabled in: " . $file->getFilename() . "\n";
            $fixedCount++;
        }
    }
}

echo "\nRe-enabled TenantScope in $fixedCount files\n";
echo "Note: TenantScope is now an empty class that does nothing\n";