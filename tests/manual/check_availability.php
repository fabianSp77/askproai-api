<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

$calcomService = new CalcomService();

echo "=== Verfügbarkeitsprüfung ===\n\n";

$eventTypeId = env('CALCOM_EVENT_TYPE_ID');
$userId = env('CALCOM_USER_ID');

// Prüfe Verfügbarkeit für mehrere Tage
$dates = [
    '2025-06-09' => 'Sonntag, 9. Juni',
    '2025-06-10' => 'Montag, 10. Juni',
    '2025-06-11' => 'Dienstag, 11. Juni'
];

foreach ($dates as $date => $label) {
    echo "\n$label:\n";
    
    $availability = $calcomService->checkAvailability($eventTypeId, $date, $date, $userId);
    
    if ($availability && isset($availability['busy'])) {
        echo "Gebuchte Zeiten:\n";
        if (empty($availability['busy'])) {
            echo "  - Keine Buchungen\n";
        } else {
            foreach ($availability['busy'] as $busy) {
                $start = new DateTime($busy['start']);
                $end = new DateTime($busy['end']);
                echo "  - " . $start->format('H:i') . " bis " . $end->format('H:i') . "\n";
            }
        }
        
        if (isset($availability['workingHours'])) {
            echo "\nArbeitszeiten:\n";
            foreach ($availability['workingHours'] as $wh) {
                $startTime = floor($wh['startTime'] / 60) . ':' . str_pad($wh['startTime'] % 60, 2, '0', STR_PAD_LEFT);
                $endTime = floor($wh['endTime'] / 60) . ':' . str_pad($wh['endTime'] % 60, 2, '0', STR_PAD_LEFT);
                echo "  - $startTime bis $endTime\n";
            }
        }
    }
}
