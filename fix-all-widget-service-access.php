<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;34m=== FIXING ALL WIDGET SERVICE ACCESS ===\033[0m\n\n";

$widgets = [
    'AppointmentKpiWidget',
    'CallKpiWidget', 
    'CustomerKpiWidget'
];

foreach ($widgets as $widget) {
    $file = __DIR__ . "/app/Filament/Admin/Widgets/{$widget}.php";
    
    if (file_exists($file)) {
        echo "Fixing $widget...\n";
        
        $content = file_get_contents($file);
        
        // Replace direct service access with getter method
        $content = preg_replace(
            '/return\s+\$this->metricsService->/',
            'return $this->getMetricsService()->',
            $content
        );
        
        // Replace isset checks
        $content = preg_replace(
            '/if\s*\(\s*!isset\s*\(\s*\$this->metricsService\s*\)\s*\)\s*{[^}]+}/s',
            '',
            $content
        );
        
        // Replace direct assignments in methods
        $content = preg_replace(
            '/\$kpis\s*=\s*\$this->metricsService->/',
            '$kpis = $this->getMetricsService()->',
            $content
        );
        
        // Add getter method if it doesn't exist
        if (!preg_match('/protected\s+function\s+getMetricsService\(\)/', $content)) {
            // Find the mount method or constructor
            if (preg_match('/(public\s+function\s+mount\(\)[^}]*\})/s', $content, $matches)) {
                $insertPos = strpos($content, $matches[0]) + strlen($matches[0]);
                
                $getterMethod = "\n\n    protected function getMetricsService(): DashboardMetricsService\n" .
                               "    {\n" .
                               "        if (!isset(\$this->metricsService) || !\$this->metricsService) {\n" .
                               "            \$this->metricsService = app(DashboardMetricsService::class);\n" .
                               "        }\n" .
                               "        return \$this->metricsService;\n" .
                               "    }";
                
                $content = substr($content, 0, $insertPos) . $getterMethod . substr($content, $insertPos);
            }
        }
        
        // Ensure property is nullable
        $content = preg_replace(
            '/protected\s+DashboardMetricsService\s+\$metricsService;/',
            'protected ?DashboardMetricsService $metricsService = null;',
            $content
        );
        
        file_put_contents($file, $content);
        echo "✓ Fixed $widget\n";
    }
}

echo "\n\033[1;33mClearing caches...\033[0m\n";
exec('php artisan optimize:clear');

echo "\n\033[1;34m=== ALL WIDGET SERVICE ACCESS FIXED ===\033[0m\n";
echo "✅ All widgets should now handle service initialization correctly!\n";