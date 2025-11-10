<?php
/**
 * Verify services after sync
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Service;

$companyId = '7fc13e06-ba89-4c54-a2d9-ecabe50abb7a'; // Friseur 1

echo "=== SERVICES VERIFICATION ===\n\n";

// 1. Count services
$servicesTotal = Service::where('company_id', $companyId)->count();
$servicesActive = Service::where('company_id', $companyId)->where('is_active', true)->count();

echo "Total services: {$servicesTotal}\n";
echo "Active services: {$servicesActive}\n\n";

// 2. Check for Herrenhaarschnitt specifically
$herrenhaarschnitt = Service::where('company_id', $companyId)
    ->where('name', 'LIKE', '%Herrenhaarschnitt%')
    ->first();

if ($herrenhaarschnitt) {
    echo "✅ Herrenhaarschnitt found:\n";
    echo "   ID: {$herrenhaarschnitt->id}\n";
    echo "   Name: {$herrenhaarschnitt->name}\n";
    echo "   Duration: {$herrenhaarschnitt->duration_minutes} min\n";
    echo "   Price: €{$herrenhaarschnitt->price}\n";
    echo "   Cal.com Event Type ID: {$herrenhaarschnitt->calcom_event_type_id}\n";
    echo "   Active: " . ($herrenhaarschnitt->is_active ? 'YES' : 'NO') . "\n";
    echo "   Sync Status: {$herrenhaarschnitt->sync_status}\n\n";
} else {
    echo "❌ Herrenhaarschnitt NOT found\n\n";
}

// 3. List all active services
echo "All active services for Friseur 1:\n";
$activeServices = Service::where('company_id', $companyId)
    ->where('is_active', true)
    ->orderBy('name')
    ->get();

foreach ($activeServices as $service) {
    echo "  - {$service->name} ({$service->duration_minutes} min, €{$service->price})\n";
}

echo "\n=== BACKEND READY FOR TESTING ===\n";
