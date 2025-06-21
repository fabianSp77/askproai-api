<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;34m=== FIXING WIDGET INITIALIZATION ERRORS ===\033[0m\n\n";

// Find all widgets with typed service properties
$widgetFiles = glob(__DIR__ . '/app/Filament/Admin/Widgets/*.php');
$fixedCount = 0;

foreach ($widgetFiles as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Check for typed service properties without initialization
    if (preg_match('/protected\s+(\w+Service)\s+\$(\w+Service);/', $content, $matches)) {
        echo "Found uninitialized service in $filename\n";
        
        // Replace with nullable type and default null
        $content = preg_replace(
            '/protected\s+(\w+Service)\s+\$(\w+Service);/',
            'protected ?$1 $$2 = null;',
            $content
        );
        
        // Check if there's a mount method that sets the service
        if (preg_match('/public\s+function\s+mount\(\)[^{]*\{[^}]*\$this->(\w+Service)\s*=\s*app\([^)]+\);/s', $content, $mountMatches)) {
            $serviceName = $mountMatches[1];
            $serviceClass = $matches[1];
            
            // Remove service initialization from mount
            $content = preg_replace(
                '/\$this->' . $serviceName . '\s*=\s*app\([^)]+\);\s*/',
                '',
                $content
            );
            
            // Add getter method if it doesn't exist
            if (!preg_match('/protected\s+function\s+get\w+Service\(\)/', $content)) {
                $getterMethod = "\n    protected function get" . ucfirst($serviceName) . "(): $serviceClass\n" .
                               "    {\n" .
                               "        if (!\$this->$serviceName) {\n" .
                               "            \$this->$serviceName = app($serviceClass::class);\n" .
                               "        }\n" .
                               "        return \$this->$serviceName;\n" .
                               "    }\n";
                
                // Insert getter after mount method
                $content = preg_replace(
                    '/(public\s+function\s+mount\(\)[^}]*\})/s',
                    "$1\n$getterMethod",
                    $content
                );
                
                // Replace all direct service usage with getter
                $content = preg_replace(
                    '/\$this->' . $serviceName . '->/',
                    '$this->get' . ucfirst($serviceName) . '()->',
                    $content
                );
            }
        }
        
        file_put_contents($file, $content);
        echo "✓ Fixed $filename\n";
        $fixedCount++;
    }
}

// Clear all caches
echo "\n\033[1;33mClearing caches...\033[0m\n";
exec('php artisan optimize:clear');

echo "\n\033[1;34m=== SUMMARY ===\033[0m\n";
echo "Fixed $fixedCount widget files\n";
echo "\n✅ All widget initialization errors should be resolved!\n";