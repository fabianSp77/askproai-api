<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;
use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  IS_ACTIVE MYSTERY INVESTIGATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$serviceId = 438; // Herrenhaarschnitt

echo "Testing Service ID: $serviceId (Herrenhaarschnitt)\n\n";

// Test 1: Direct SQL
echo "Test 1: Direct SQL Query\n";
echo "─────────────────────────────────────────────────────────\n";
$sql = DB::select("SELECT id, name, is_active FROM services WHERE id = ?", [$serviceId]);
if (!empty($sql)) {
    $row = $sql[0];
    echo "Name: {$row->name}\n";
    echo "is_active (raw): {$row->is_active}\n";
    echo "is_active (bool): " . ($row->is_active ? 'YES' : 'NO') . "\n\n";
}

// Test 2: Eloquent without scope
echo "Test 2: Eloquent withoutGlobalScopes()\n";
echo "─────────────────────────────────────────────────────────\n";
$serviceNoScope = Service::withoutGlobalScopes()->find($serviceId);
if ($serviceNoScope) {
    echo "Name: {$serviceNoScope->name}\n";
    echo "is_active (attribute): {$serviceNoScope->is_active}\n";
    echo "is_active (bool): " . ($serviceNoScope->is_active ? 'YES' : 'NO') . "\n";
    echo "is_active (getRawOriginal): " . $serviceNoScope->getRawOriginal('is_active') . "\n\n";
}

// Test 3: Standard Eloquent
echo "Test 3: Standard Eloquent find()\n";
echo "─────────────────────────────────────────────────────────\n";
$service = Service::find($serviceId);
if ($service) {
    echo "Name: {$service->name}\n";
    echo "is_active (attribute): {$service->is_active}\n";
    echo "is_active (bool): " . ($service->is_active ? 'YES' : 'NO') . "\n";
    echo "is_active (getRawOriginal): " . $service->getRawOriginal('is_active') . "\n\n";
}

// Test 4: Check casts
echo "Test 4: Model Configuration\n";
echo "─────────────────────────────────────────────────────────\n";
$testService = new Service();
$casts = $testService->getCasts();
echo "is_active cast: " . ($casts['is_active'] ?? 'NOT SET') . "\n\n";

// Test 5: Check if there's a difference in attributes vs original
if ($service) {
    echo "Test 5: Attributes Analysis\n";
    echo "─────────────────────────────────────────────────────────\n";
    echo "Attributes:\n";
    print_r($service->getAttributes());
    echo "\nOriginal:\n";
    print_r($service->getOriginal());
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  INVESTIGATION COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n";
