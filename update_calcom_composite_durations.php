<?php

/**
 * Update Cal.com Event Types for Composite Services
 *
 * Updates the duration of Event Types to match new composite service durations
 *
 * Service 177: 150 minutes (was 120)
 * Service 178: 170 minutes (was 120)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$baseUrl = rtrim(config('services.calcom.base_url'), '/');
$apiKey = config('services.calcom.api_key');
$teamId = 34209;

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║     CAL.COM EVENT TYPES AKTUALISIEREN (COMPOSITE)           ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$services = [
    177 => [
        'name' => 'Ansatzfärbung, waschen, schneiden, föhnen',
        'event_type_id' => 3719865,
        'old_duration' => 120,
        'new_duration' => 150
    ],
    178 => [
        'name' => 'Ansatz, Längenausgleich, waschen, schneiden, föhnen',
        'event_type_id' => 3719866,
        'old_duration' => 120,
        'new_duration' => 170
    ]
];

$updated = 0;
$failed = 0;

foreach ($services as $serviceId => $config) {
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo "Service ID {$serviceId}: {$config['name']}" . PHP_EOL;
    echo "Cal.com Event Type ID: {$config['event_type_id']}" . PHP_EOL;
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo PHP_EOL;

    echo "⏱️  Dauer-Update:" . PHP_EOL;
    echo "  Alt: {$config['old_duration']} Minuten" . PHP_EOL;
    echo "  Neu: {$config['new_duration']} Minuten (Composite mit Pausen)" . PHP_EOL;
    echo PHP_EOL;

    try {
        echo "  🔧 Cal.com Event Type aktualisieren..." . PHP_EOL;

        $payload = [
            'lengthInMinutes' => $config['new_duration']
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'cal-api-version' => '2024-08-13',
            'Content-Type' => 'application/json'
        ])->patch(
            $baseUrl . '/teams/' . $teamId . '/event-types/' . $config['event_type_id'],
            $payload
        );

        if ($response->successful()) {
            echo "  ✅ Cal.com Event Type aktualisiert!" . PHP_EOL;

            $data = $response->json();
            $eventType = $data['data'] ?? $data;

            if (isset($eventType['lengthInMinutes'])) {
                echo "     Bestätigte Dauer: {$eventType['lengthInMinutes']} Minuten" . PHP_EOL;
            }

            $updated++;
        } else {
            echo "  ❌ Fehler bei Cal.com Update: " . $response->status() . PHP_EOL;
            echo "     Response: " . $response->body() . PHP_EOL;
            $failed++;
        }

    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }

    echo PHP_EOL;
}

echo "╔══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║                    ZUSAMMENFASSUNG                           ║" . PHP_EOL;
echo "╚══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo "✅ Erfolgreich: {$updated}" . PHP_EOL;
echo "❌ Fehler: {$failed}" . PHP_EOL;
echo PHP_EOL;

if ($updated > 0) {
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo "WICHTIG - WIE COMPOSITE SERVICES FUNKTIONIEREN" . PHP_EOL;
    echo "═══════════════════════════════════════════════════════════" . PHP_EOL;
    echo PHP_EOL;

    echo "📋 Cal.com vs. Datenbank:" . PHP_EOL;
    echo "  - Cal.com Event Type: Zeigt GESAMT-Dauer (z.B. 150 min)" . PHP_EOL;
    echo "  - Datenbank (segments): Detaillierte Segment-Struktur" . PHP_EOL;
    echo "  - Booking-Logik: Backend entscheidet, wie gebucht wird" . PHP_EOL;
    echo PHP_EOL;

    echo "⚙️  Wie es funktioniert:" . PHP_EOL;
    echo "  1. Cal.com zeigt Verfügbarkeit für 150/170 min Block" . PHP_EOL;
    echo "  2. CompositeBookingService erstellt MEHRERE Cal.com Bookings:" . PHP_EOL;
    echo "     - Segment A (30 min) um 10:00" . PHP_EOL;
    echo "     - Pause (30 min) → Staff verfügbar für andere Kunden!" . PHP_EOL;
    echo "     - Segment B (15 min) um 11:00" . PHP_EOL;
    echo "     - usw." . PHP_EOL;
    echo "  3. Kunde bekommt EIN Appointment mit composite_group_uid" . PHP_EOL;
    echo PHP_EOL;

    echo "✅ Was jetzt funktioniert:" . PHP_EOL;
    echo "  - Admin Portal: Zeigt Segment-Struktur an" . PHP_EOL;
    echo "  - Web-Buchungen: CompositeBookingService erstellt Multi-Segment Bookings" . PHP_EOL;
    echo "  - Staff-Verfügbarkeit: Während Pausen verfügbar (pause_bookable_policy: free)" . PHP_EOL;
    echo PHP_EOL;

    echo "⚠️  Was NOCH NICHT funktioniert:" . PHP_EOL;
    echo "  - Voice AI kann diese Services NICHT buchen" . PHP_EOL;
    echo "  - Grund: AppointmentCreationService hat keine Composite-Logik" . PHP_EOL;
    echo PHP_EOL;
}

echo "✅ Script abgeschlossen: " . now()->toDateTimeString() . PHP_EOL;
