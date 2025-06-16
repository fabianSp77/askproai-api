<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

$calcomService = new CalcomService();
$eventTypes = $calcomService->getEventTypes();

echo "\n=== Verfügbare Event-Types ===\n\n";
echo "Anzahl: " . count($eventTypes) . "\n\n";

foreach ($eventTypes as $et) {
    echo "ID: {$et['id']}\n";
    echo "Titel: {$et['title']}\n";
    echo "Slug: {$et['slug']}\n";
    echo "Dauer: {$et['length']} Minuten\n";
    echo "Versteckt: " . ($et['hidden'] ? 'Ja' : 'Nein') . "\n";
    echo "---\n\n";
}
