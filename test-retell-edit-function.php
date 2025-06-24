<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\RetellV2Service;
use App\Filament\Admin\Pages\RetellUltimateDashboard;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Retell Edit Function ===\n\n";

// Check if the RetellUltimateDashboard class exists
if (!class_exists(RetellUltimateDashboard::class)) {
    echo "❌ RetellUltimateDashboard class not found\n";
    exit(1);
}
echo "✅ RetellUltimateDashboard class exists\n";

// Check if methods exist
$dashboard = new RetellUltimateDashboard();
$methods = [
    'editFunction',
    'startEditingFunction',
    'cancelEditingFunction',
    'saveFunction',
    'loadAgents',
    'selectAgent',
    'loadLLMData'
];

echo "\nChecking methods:\n";
foreach ($methods as $method) {
    if (method_exists($dashboard, $method)) {
        echo "✅ Method exists: {$method}\n";
    } else {
        echo "❌ Method missing: {$method}\n";
    }
}

// Check properties
echo "\nChecking properties:\n";
$properties = [
    'editingFunction',
    'functionEditor',
    'llmData',
    'service',
    'error',
    'successMessage'
];

foreach ($properties as $property) {
    if (property_exists($dashboard, $property)) {
        echo "✅ Property exists: {$property}\n";
    } else {
        echo "❌ Property missing: {$property}\n";
    }
}

echo "\n=== Test Complete ===\n";