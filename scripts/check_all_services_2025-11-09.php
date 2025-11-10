<?php
/**
 * Check all services in database regardless of company
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;

echo "=== ALL SERVICES IN DATABASE ===\n\n";

// 1. Count all services
$total = Service::count();
echo "Total services (all companies): {$total}\n\n";

if ($total > 0) {
    // 2. Group by company_id
    $byCompany = Service::selectRaw('company_id, COUNT(*) as count')
        ->groupBy('company_id')
        ->get();

    echo "Services by company:\n";
    foreach ($byCompany as $row) {
        echo "  Company {$row->company_id}: {$row->count} services\n";
    }
    echo "\n";

    // 3. Sample services from each company
    echo "Sample services:\n";
    $samples = Service::take(10)->get();
    foreach ($samples as $service) {
        echo "  - {$service->name}\n";
        echo "    Company: {$service->company_id}\n";
        echo "    Cal.com Event Type: {$service->calcom_event_type_id}\n";
        echo "    Active: " . ($service->is_active ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
}

echo "=== EXPECTED COMPANY ID ===\n";
echo "Friseur 1: 7fc13e06-ba89-4c54-a2d9-ecabe50abb7a\n\n";
