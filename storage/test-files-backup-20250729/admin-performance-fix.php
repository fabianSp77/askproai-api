<?php
// Temporary Admin Performance Fix
// This script disables problematic JavaScript monitoring

// 1. Backup the problematic files
$filesToFix = [
    '/var/www/api-gateway/resources/views/vendor/filament-panels/components/csrf-fix.blade.php',
    '/var/www/api-gateway/resources/views/vendor/filament-panels/components/livewire-fix.blade.php'
];

$backupDir = '/var/www/api-gateway/storage/performance-fix-backup-' . date('Y-m-d-H-i-s');
mkdir($backupDir, 0755, true);

echo "=== Admin Performance Fix ===\n\n";
echo "Creating backups in: $backupDir\n\n";

foreach ($filesToFix as $file) {
    if (file_exists($file)) {
        $backupFile = $backupDir . '/' . basename($file);
        copy($file, $backupFile);
        echo "Backed up: " . basename($file) . "\n";
        
        // Create a minimal version without aggressive monitoring
        $content = file_get_contents($file);
        
        if (basename($file) === 'csrf-fix.blade.php') {
            // Replace with minimal CSRF fix
            $newContent = '{{-- CSRF Fix - Minimal Version --}}
<script>
(function() {
    console.log("CSRF Fix - Minimal Version Active");
    
    // Only handle Livewire CSRF tokens
    if (window.Livewire) {
        Livewire.hook("request", ({ options }) => {
            options.headers = options.headers || {};
            options.headers["X-CSRF-TOKEN"] = document.querySelector(\'meta[name="csrf-token"]\')?.content || "";
            options.headers["X-Requested-With"] = "XMLHttpRequest";
        });
    }
})();
</script>';
            
            file_put_contents($file, $newContent);
            echo "Replaced csrf-fix.blade.php with minimal version\n";
        }
        
        if (basename($file) === 'livewire-fix.blade.php') {
            // Replace with minimal version
            $newContent = '{{-- Livewire Fix - Minimal Version --}}
<script>
console.log("Livewire fix - minimal version active");
</script>';
            
            file_put_contents($file, $newContent);
            echo "Replaced livewire-fix.blade.php with minimal version\n";
        }
    }
}

// Clear all caches
echo "\nClearing caches...\n";
system('php artisan optimize:clear');
system('php artisan filament:cache-components');

echo "\n✅ Performance fix applied!\n";
echo "\nTo restore original files:\n";
echo "cp $backupDir/*.blade.php /var/www/api-gateway/resources/views/vendor/filament-panels/components/\n";
echo "\nPlease test the admin panel now.\n";