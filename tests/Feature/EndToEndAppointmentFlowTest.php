<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CalcomService;
use App\Services\AppointmentAlternativeFinder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * End-to-End Test für den kompletten Terminbuchungs-Flow
 *
 * Testet: Retell → Laravel → Cal.com → AlternativeFinder → Response
 * OHNE echten Telefonanruf!
 */
class EndToEndAppointmentFlowTest extends TestCase
{
    /**
     * Test: User fragt "heute um 17:00"
     * Erwartung: Entweder 17:00 wird gebucht ODER Cal.com-verifizierte Alternativen
     */
    public function test_appointment_request_today_at_1700()
    {
        // Simulate Retell webhook call
        $requestData = [
            'call' => [
                'call_id' => 'test_call_' . uniqid(),
                'call_status' => 'ongoing'
            ],
            'name' => 'collect_appointment_data',
            'args' => [
                'call_id' => 'test_call_' . uniqid(),
                'name' => 'Test User',
                'datum' => 'heute',
                'uhrzeit' => '17:00',
                'dienstleistung' => 'Beratung',
                'email' => 'test@example.com'
            ]
        ];

        // Call the endpoint
        $response = $this->postJson('/api/retell/collect-appointment', $requestData);

        // Assert response structure
        $response->assertStatus(200);
        $json = $response->json();

        // Log the response for debugging
        Log::info('🧪 Test Response:', $json);

        $this->assertArrayHasKey('success', $json);

        if ($json['success'] === false && $json['status'] === 'unavailable') {
            // If 17:00 not available, check alternatives
            $this->assertArrayHasKey('alternatives', $json);
            $alternatives = $json['alternatives'];

            // ⚠️ KRITISCHER CHECK: Alternativen müssen Cal.com-verifiziert sein!
            foreach ($alternatives as $alt) {
                $this->assertTrue(
                    $alt['verified'] === true,
                    "Alternative {$alt['time']} at {$alt['date']} is NOT Cal.com verified! This is a BUG."
                );
            }

            // Alternativen dürfen nicht leer sein
            $this->assertNotEmpty($alternatives, "No alternatives provided when 17:00 unavailable");
        } else {
            // 17:00 ist verfügbar - sollte gebucht werden
            $this->assertTrue($json['success'], "Appointment at 17:00 should be bookable");
        }
    }

    /**
     * Test: Direkter Cal.com API Check für heute 17:00
     */
    public function test_calcom_api_has_slot_at_1700()
    {
        $calcom = app(CalcomService::class);
        $today = Carbon::today()->format('Y-m-d');

        $response = $calcom->getAvailableSlots(2563193, $today, $today);
        $data = $response->json();

        $slots = $data['data']['slots'][$today] ?? [];

        Log::info('🧪 Cal.com Slots for today:', [
            'date' => $today,
            'total_slots' => count($slots),
            'slots' => $slots
        ]);

        $this->assertNotEmpty($slots, "Cal.com should have slots for today");

        // Check if 17:00 (or 17:30) is available
        $has_1700_or_1730 = false;
        foreach ($slots as $slot) {
            $time = $slot['time'];
            if (str_contains($time, 'T17:00:00') || str_contains($time, 'T17:30:00')) {
                $has_1700_or_1730 = true;
                break;
            }
        }

        $this->assertTrue(
            $has_1700_or_1730,
            "Cal.com should have slots around 17:00, but none found! Available slots: " .
            implode(', ', array_column($slots, 'time'))
        );
    }

    /**
     * Test: AlternativeFinder generiert VERIFIZIERTE Alternativen
     */
    public function test_alternative_finder_verifies_slots()
    {
        $finder = app(AppointmentAlternativeFinder::class);

        $desiredDateTime = Carbon::today()->setTime(17, 0);
        $serviceId = 47; // Test service
        $eventTypeId = 2563193;

        $alternatives = $finder->findAlternatives(
            $desiredDateTime,
            60, // duration
            $serviceId,
            $eventTypeId
        );

        Log::info('🧪 AlternativeFinder Result:', [
            'count' => $alternatives->count(),
            'alternatives' => $alternatives->toArray()
        ]);

        if ($alternatives->isNotEmpty()) {
            // KRITISCH: Alle Alternativen müssen verifiziert sein!
            foreach ($alternatives as $alt) {
                $this->assertTrue(
                    isset($alt['verified']) && $alt['verified'] === true,
                    "Alternative {$alt['time']} is NOT verified! Verified flag: " .
                    json_encode($alt['verified'] ?? 'missing')
                );
            }
        }
    }

    /**
     * Test: Kein Self-Loop (System bietet 15:00 an, dann sagt "15:00 nicht verfügbar")
     */
    public function test_no_alternative_self_loop()
    {
        $finder = app(AppointmentAlternativeFinder::class);

        // Request 15:00
        $desiredDateTime = Carbon::today()->setTime(15, 0);
        $serviceId = 47;
        $eventTypeId = 2563193;

        $alternatives = $finder->findAlternatives(
            $desiredDateTime,
            60,
            $serviceId,
            $eventTypeId
        );

        // Wenn 15:00 nicht verfügbar ist, dürfen die Alternativen NICHT 15:00 enthalten
        if ($alternatives->isNotEmpty()) {
            foreach ($alternatives as $alt) {
                $this->assertNotEquals(
                    '15:00',
                    $alt['time'],
                    "Alternative should NOT be 15:00 when 15:00 was requested and unavailable!"
                );
            }
        }
    }

    /**
     * Test: Cal.com ISO 8601 Format Fix funktioniert
     */
    public function test_calcom_uses_iso8601_format()
    {
        $calcom = app(CalcomService::class);

        // Test mit einfachem Datum-String (sollte zu ISO 8601 konvertiert werden)
        $response = $calcom->getAvailableSlots(
            2563193,
            '2025-10-01',  // Einfaches Format
            '2025-10-01'
        );

        $data = $response->json();

        // Wenn das Format falsch wäre, kämen leere Slots zurück
        $slots = $data['data']['slots']['2025-10-01'] ?? [];

        $this->assertNotEmpty(
            $slots,
            "Cal.com returned empty slots - ISO 8601 format conversion may have failed!"
        );
    }

    /**
     * Test: E-Mail mit Leerzeichen wird korrekt bereinigt (Speech-to-Text Fix)
     * KRITISCHER TEST: Verhindert, dass E-Mail-Validation die Funktion blockiert
     */
    public function test_email_with_spaces_is_sanitized()
    {
        // Simulate Retell webhook with problematic email (spaces from speech-to-text)
        $requestData = [
            'call' => [
                'call_id' => 'test_email_' . uniqid(),
                'call_status' => 'ongoing'
            ],
            'name' => 'collect_appointment_data',
            'args' => [
                'call_id' => 'test_email_' . uniqid(),
                'name' => 'Test User',
                'datum' => 'heute',
                'uhrzeit' => '17:00',
                'dienstleistung' => 'Beratung',
                'email' => 'Fub Handy@Gmail.com'  // ← Leerzeichen von Retell Speech-to-Text!
            ]
        ];

        // Call the endpoint
        $response = $this->postJson('/api/retell/collect-appointment', $requestData);

        // Assert response structure
        $response->assertStatus(200);
        $json = $response->json();

        // KRITISCHER CHECK: Keine E-Mail-Validierungsfehler!
        if (isset($json['status']) && $json['status'] === 'error') {
            // Wenn Error, dann NICHT wegen E-Mail-Validation
            $this->assertFalse(
                isset($json['errors']['args.email']),
                "E-Mail validation failed! Email with spaces should be sanitized BEFORE validation. " .
                "Error: " . ($json['message'] ?? 'unknown')
            );
        }

        // Funktion sollte ausgeführt worden sein (success oder unavailable, aber nicht validation error)
        $this->assertTrue(
            isset($json['success']) || isset($json['status']),
            "Function should have been executed (no validation blocking)"
        );

        Log::info('🧪 Email Sanitization Test Result:', $json);
    }
}
