<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\CalcomService;

echo "Testing Cal.com Availability...\n\n";

$service = new CalcomService();

// Test für die nächsten 7 Tage
$dateFrom = (new DateTime('tomorrow'))->format('Y-m-d\TH:i:s') . '+02:00';
$dateTo = (new DateTime('+7 days'))->format('Y-m-d\TH:i:s') . '+02:00';

echo "Checking availability from $dateFrom to $dateTo\n";
echo "Event Type ID: 2026302\n\n";

$availability = $service->checkAvailability(2026302, $dateFrom, $dateTo);

if ($availability) {
    echo "✓ Availability check successful!\n";
    echo "Available slots: " . json_encode($availability, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "✗ Availability check failed!\n";
}
