<?php
echo "=== Fixing Session Directories ===\n\n";

$sessionBase = __DIR__ . '/storage/framework/sessions';

// Create all needed directories
$dirs = ['admin', 'business', 'portal', 'api', 'web'];

foreach ($dirs as $dir) {
    $path = $sessionBase . '/' . $dir;
    
    if (!is_dir($path)) {
        echo "Creating $path...\n";
        mkdir($path, 0775, true);
    }
    
    // Fix permissions
    chown($path, 'www-data');
    chgrp($path, 'www-data');
    chmod($path, 0775);
    
    echo "✓ Fixed permissions for $path\n";
}

// Clear any root-owned session files
echo "\nCleaning up session files...\n";
$files = glob($sessionBase . '/*');
foreach ($files as $file) {
    if (is_file($file) && !strpos($file, '.gitignore')) {
        $owner = posix_getpwuid(fileowner($file));
        if ($owner['name'] === 'root') {
            unlink($file);
            echo "✓ Removed root-owned file: " . basename($file) . "\n";
        }
    }
}

echo "\n=== Done ===\n";
echo "Session directories are now properly configured.\n";