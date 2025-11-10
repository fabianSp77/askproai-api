<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  SERVICES WITH CAL.COM EVENT TYPE IDs\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$services = Service::where('company_id', 1)
    ->whereNotNull('calcom_event_type_id')
    ->get();

echo "Found " . $services->count() . " services with Cal.com mappings:\n\n";

foreach ($services as $service) {
    $name = $service->name;
    $id = $service->id;
    $calcomId = $service->calcom_event_type_id;
    $active = $service->is_active ? 'YES' : 'NO';
    $branch = $service->branch ? $service->branch->name : 'ALL BRANCHES';

    echo "Service: $name\n";
    echo "  DB ID: $id\n";
    echo "  Cal.com ID: $calcomId\n";
    echo "  Active: $active\n";
    echo "  Branch: $branch\n";
    echo "\n";
}
