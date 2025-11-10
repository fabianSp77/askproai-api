<?php
/**
 * Check active services duration and price
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;

$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';

echo "=== ACTIVE SERVICES FOR FRISEUR 1 ZENTRALE ===\n\n";

$services = Service::where('branch_id', $branchId)
    ->where('is_active', true)
    ->select('id', 'name', 'duration', 'duration_minutes', 'price', 'calcom_event_type_id')
    ->get();

echo "Found " . $services->count() . " active services:\n\n";

foreach ($services as $service) {
    echo "Service: {$service->name}\n";
    echo "  ID: {$service->id}\n";
    echo "  Cal.com Event Type: " . ($service->calcom_event_type_id ?? 'NOT SET') . "\n";
    echo "  duration: " . ($service->duration ?? 'NULL') . "\n";
    echo "  duration_minutes: " . ($service->duration_minutes ?? 'NULL') . "\n";
    echo "  price: €" . number_format($service->price, 2) . "\n";
    echo "\n";
}

echo "=== END CHECK ===\n";
