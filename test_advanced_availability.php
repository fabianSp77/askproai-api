<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test: Erweiterte Verfügbarkeitsprüfung mit Kundenpräferenzen ===\n\n";

$webhookUrl = 'http://localhost:8000/api/retell/webhook';
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Test 1: Spezifischer Termin verfügbar
echo "Test 1: Prüfe spezifischen Termin\n";
$payload1 = [
    'event' => 'call_inbound',
    'call_id' => 'test_pref_001',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ],
    'dynamic_variables' => [
        'check_availability' => true,
        'requested_date' => $tomorrow,
        'requested_time' => '14:00',
        'event_type_id' => 1
    ]
];

$response1 = Http::post($webhookUrl, $payload1);
echo "Response:\n";
print_r($response1->json()['response']['dynamic_variables'] ?? []);
echo "\n---\n\n";

// Test 2: Mit Wochentag-Präferenz
echo "Test 2: Nur donnerstags verfügbar\n";
$payload2 = [
    'event' => 'call_inbound',
    'call_id' => 'test_pref_002',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ],
    'dynamic_variables' => [
        'check_availability' => true,
        'requested_date' => $tomorrow,
        'requested_time' => '16:00',
        'event_type_id' => 1,
        'customer_preferences' => 'Ich kann immer nur donnerstags'
    ]
];

$response2 = Http::post($webhookUrl, $payload2);
echo "Response:\n";
print_r($response2->json()['response']['dynamic_variables'] ?? []);
echo "\n---\n\n";

// Test 3: Mit Zeitbereich-Präferenz
echo "Test 3: Donnerstags 16-19 Uhr\n";
$payload3 = [
    'event' => 'call_inbound',
    'call_id' => 'test_pref_003',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ],
    'dynamic_variables' => [
        'check_availability' => true,
        'requested_date' => $tomorrow,
        'requested_time' => '17:00',
        'event_type_id' => 1,
        'customer_preferences' => 'Ich kann donnerstags von 16:00 bis 19:00 Uhr'
    ]
];

$response3 = Http::post($webhookUrl, $payload3);
echo "Response:\n";
print_r($response3->json()['response']['dynamic_variables'] ?? []);
echo "\n---\n\n";

// Test 4: Nur vormittags
echo "Test 4: Nur vormittags verfügbar\n";
$payload4 = [
    'event' => 'call_inbound',
    'call_id' => 'test_pref_004',
    'call_inbound' => [
        'from_number' => '+491234567890',
        'to_number' => '+493041735870'
    ],
    'dynamic_variables' => [
        'check_availability' => true,
        'requested_date' => $tomorrow,
        'requested_time' => '15:00', // Nachmittags - sollte Alternativen vormittags finden
        'event_type_id' => 1,
        'customer_preferences' => 'Ich kann nur vormittags'
    ]
];

$response4 = Http::post($webhookUrl, $payload4);
echo "Response:\n";
$vars = $response4->json()['response']['dynamic_variables'] ?? [];
print_r($vars);

if (isset($vars['alternative_slots'])) {
    echo "\nAlternative Termine: " . $vars['alternative_slots'] . "\n";
}
echo "\n---\n\n";

echo "=== Hinweise für Retell.ai Agent-Konfiguration ===\n\n";

echo "1. Neue Dynamic Variables für Kundenpräferenzen:\n";
echo "   - customer_preferences: Freitext mit Zeitpräferenzen\n";
echo "   - requested_time: Spezifische Uhrzeit (z.B. '14:00')\n\n";

echo "2. Response Variables die der Agent nutzen kann:\n";
echo "   - requested_slot_available: Boolean ob der Wunschtermin frei ist\n";
echo "   - alternative_slots: Formatierte Alternative Termine\n";
echo "   - alternative_dates: Array mit alternativen Daten\n";
echo "   - preference_matched: Boolean ob Alternativen zu Präferenzen passen\n\n";

echo "3. Beispiel-Prompts für den Agent:\n";
echo "   Wenn requested_slot_available = false und alternative_slots vorhanden:\n";
echo "   'Der gewünschte Termin ist leider nicht verfügbar. Ich hätte aber folgende Alternativen für Sie: {alternative_slots}. Welcher passt Ihnen besser?'\n\n";

echo "4. Unterstützte Präferenz-Formate:\n";
echo "   - 'nur donnerstags'\n";
echo "   - 'montags und mittwochs'\n";
echo "   - 'vormittags' / 'nachmittags' / 'abends'\n";
echo "   - 'von 16:00 bis 19:00 Uhr'\n";
echo "   - 'ab 16 Uhr'\n";
echo "   - Kombinationen: 'donnerstags nachmittags' oder 'montags von 9 bis 12 Uhr'\n";