<?php
// Simple script to check if admin-fix.js is being loaded

echo "Checking admin panel JavaScript fix...\n\n";

// Check if the file exists in build
$buildFile = '/var/www/api-gateway/public/build/assets/filament.admin.fix-CrD3dor7.js';
if (file_exists($buildFile)) {
    echo "✅ Build file exists: " . basename($buildFile) . "\n";
    echo "   Size: " . filesize($buildFile) . " bytes\n\n";
    
    // Show first few lines
    echo "First few lines of built JS:\n";
    echo "----------------------------\n";
    $content = file_get_contents($buildFile);
    $lines = explode("\n", $content);
    foreach (array_slice($lines, 0, 5) as $line) {
        echo substr($line, 0, 100) . (strlen($line) > 100 ? '...' : '') . "\n";
    }
} else {
    echo "❌ Build file not found!\n";
}

echo "\n\nChecking base.blade.php includes the script...\n";
$bladeFile = '/var/www/api-gateway/resources/views/vendor/filament-panels/components/layout/base.blade.php';
$bladeContent = file_get_contents($bladeFile);

if (strpos($bladeContent, 'admin-fix.js') !== false) {
    echo "✅ admin-fix.js is referenced in base.blade.php\n";
    
    // Find the exact line
    $lines = explode("\n", $bladeContent);
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'admin-fix.js') !== false) {
            echo "   Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ admin-fix.js NOT found in base.blade.php\n";
}

echo "\n\nTo test in browser:\n";
echo "1. Open the admin panel: https://api.askproai.de/admin\n";
echo "2. Open browser console (F12)\n";
echo "3. Look for messages starting with [ADMIN-FIX]\n";
echo "4. Check if red debug box appears in bottom-right corner\n";
?>