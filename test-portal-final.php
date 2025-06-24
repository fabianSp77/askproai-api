<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n✅ Portal Status Check - FINAL\n";
echo str_repeat('=', 50) . "\n\n";

// Test Ultimate Resources
$ultimateResources = [
    'UltimateAppointmentResource' => \App\Filament\Admin\Resources\UltimateAppointmentResource::class,
    'UltimateCallResource' => \App\Filament\Admin\Resources\UltimateCallResource::class,
    'UltimateCustomerResource' => \App\Filament\Admin\Resources\UltimateCustomerResource::class,
];

echo "Ultimate Resources:\n";
foreach ($ultimateResources as $name => $class) {
    echo "  - {$name}: ";
    if (class_exists($class)) {
        try {
            $resource = new $class();
            echo "✅ OK\n";
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Not found\n";
    }
}

echo "\nRetell Configuration:\n";
try {
    $retellConfig = new \App\Filament\Admin\Pages\RetellWebhookConfiguration();
    echo "  - RetellWebhookConfiguration: ✅ OK\n";
} catch (\Exception $e) {
    echo "  - RetellWebhookConfiguration: ❌ " . $e->getMessage() . "\n";
}

echo "\nMCP Gateway:\n";
try {
    $gateway = app(\App\Services\MCP\MCPGateway::class);
    $health = $gateway->health();
    echo "  - Status: ✅ " . $health['gateway'] . "\n";
    $healthyCount = count(array_filter($health['servers'], fn($s) => ($s['status'] ?? '') === 'healthy'));
    echo "  - Healthy Servers: {$healthyCount}/" . count($health['servers']) . "\n";
} catch (\Exception $e) {
    echo "  - Error: ❌ " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "🎉 Portal should be working now!\n\n";
echo "Access URLs:\n";
echo "  - Company Integration Portal: /admin/company-integration-portal\n";
echo "  - Retell Webhook Config: /admin/retell-webhook-configuration\n";
echo "  - Dashboard: /admin\n\n";