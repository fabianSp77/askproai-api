<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test-Code
$company = \App\Models\Company::find(85);
$service = new \App\Services\CalcomService($company->calcom_api_key);

$tomorrow = \Carbon\Carbon::tomorrow()->setHour(10);
$dateFrom = $tomorrow->toIso8601String();
$dateTo = $tomorrow->copy()->addHour()->toIso8601String();

echo "Teste Verfügbarkeit von: " . $dateFrom . "\n";
echo "Teste Verfügbarkeit bis: " . $dateTo . "\n";

$availability = $service->checkAvailability($company->calcom_event_type_id, $dateFrom, $dateTo);

if ($availability) {
    echo "✅ Verbindung erfolgreich!\n";
    echo "Anzahl verfügbarer Slots: " . count($availability['slots'] ?? []) . "\n";
    
    if (isset($availability['slots']) && count($availability['slots']) > 0) {
        echo "\nErste 3 verfügbare Slots:\n";
        $slots = array_slice($availability['slots'], 0, 3);
        foreach ($slots as $index => $slot) {
            echo ($index + 1) . ". " . $slot['time'] . "\n";
        }
    }
} else {
    echo "❌ Keine Verfügbarkeit gefunden\n";
}
