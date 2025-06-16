<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

$service = new CalcomService();

$result = $service->createBookingWithConfirmation(
    'cal_live_bd7aedbdf12085c5312c79ba73585920',
    '1414768',
    '2026302',
    '2025-06-05T11:00:00+02:00',
    'Mail Test Final',
    'fabianspitzer@icloud.com',
    89,
    true
);

if (isset($result['id'])) {
    echo "✓ Buchung erstellt: " . $result['id'] . "\n";
    echo "✓ Prüfen Sie Ihre E-Mail!\n";
} else {
    echo "✗ Fehler bei Buchung\n";
    print_r($result);
}
