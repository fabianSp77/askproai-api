<?php

namespace Tests\Feature\Webhook;

use Illuminate\Foundation\Testing\RefreshDatabase; // Um die DB für jeden Test zurückzusetzen
// use Illuminate\Foundation\Testing\WithoutMiddleware; // Nur wenn absolut nötig aktivieren
use Tests\TestCase;
use App\Models\Call; // Call Model importieren
use App\Services\CalcomService; // CalcomService importieren für Mocking
use Mockery; // Mocking-Bibliothek importieren
use Mockery\MockInterface; // Typ-Hinting für Mockery

class RetellWebhookTest extends TestCase
{
    // Nutze RefreshDatabase, um sicherzustellen, dass jeder Test mit einer sauberen DB startet
    use RefreshDatabase;

    /**
     * Testet den Webhook-Endpunkt mit gültigen Daten, aber *ohne* Termindaten.
     * Erwartet eine erfolgreiche Antwort, ohne dass Cal.com aufgerufen wird.
     *
     * @return void
     */
    public function test_webhook_processes_call_without_appointment_data_successfully(): void
    {
        // 1. Vorbereitung: Testdaten ohne Termin-Infos
        $payload = [
            'call_id' => 'test_success_' . uniqid(),
            'status' => 'call_ended',
            'phone_number' => '+491609876543',
            'duration' => 45,
            'transcript' => 'Nur ein kurzer Anruf ohne Termin.',
            'summary' => 'Kein Termin.',
            'user_sentiment' => 'neutral',
            'call_successful' => false, // Sende 'false' im Payload
            'disconnect_reason' => 'user_hangup',
            // Keine _datum__termin oder _uhrzeit__termin Felder
        ];

        // 2. Aktion: Sende einen POST-Request an den Webhook-Endpunkt
        $response = $this->postJson('/api/webhooks/retell', $payload);

        // 3. Überprüfungen (Assertions):
        // Prüfe, ob der HTTP-Statuscode 200 (OK) ist
        $response->assertStatus(200);

        // Prüfe, ob die JSON-Antwort die erwartete Struktur und Werte hat
        $response->assertJson([
            'success' => true,
        ]);

        // Prüfe, ob ein entsprechender Call-Eintrag in der Datenbank erstellt wurde
        // mit den korrekten Spaltennamen aus dem Call-Model
        $this->assertDatabaseHas('calls', [
            'call_id' => $payload['call_id'],
            'phone_number' => $payload['phone_number'],
            'call_status' => $payload['status'], // Spalte: call_status
            'successful' => 0, // Spalte: successful, Wert 0 für false
        ]);

        // Hole die ID des erstellten Calls für die JSON-Antwort-Prüfung
        $call = Call::where('call_id', $payload['call_id'])->first();
        $this->assertNotNull($call, 'Call wurde nicht in der Datenbank gefunden.');
        $response->assertJson([
            'call_id' => $call->id,
        ]);
    }

     /**
      * Testet den Webhook-Endpunkt mit gültigen Daten *inklusive* Termindaten.
      * Simuliert eine erfolgreiche Cal.com-Buchung.
      *
      * @return void
      */
     public function test_webhook_processes_call_with_appointment_data_and_mocks_calcom_success(): void
     {
         // 1. Vorbereitung: Mocking des CalcomService
         $this->instance(
             CalcomService::class,
             Mockery::mock(CalcomService::class, function (MockInterface $mock) {
                 $mock->shouldReceive('bookAppointment')
                      ->once()
                      ->andReturn([
                          'success' => true,
                          'message' => 'Termin erfolgreich gebucht (Mock).',
                          'appointment_id' => 'mock_booking_id_123'
                      ]);
             })
         );

         // 2. Vorbereitung: Testdaten mit Termin-Infos
         $payload = [
             'call_id' => 'test_booking_' . uniqid(),
             'status' => 'call_ended',
             'phone_number' => '+491601122334',
             'duration' => 120,
             'transcript' => 'Termin für Montag 10 Uhr gebucht.',
             'summary' => 'Termin gebucht.',
             'user_sentiment' => 'positive',
             'call_successful' => true, // Sende 'true' im Payload
             'disconnect_reason' => null,
              '_datum__termin' => now()->addDays(5)->format('Y-m-d'),
              '_uhrzeit__termin' => '10:00',
              '_name' => 'Test Booker',
              '_email' => 'booker@example.com'
         ];

         // 3. Aktion: Sende den POST-Request
         $response = $this->postJson('/api/webhooks/retell', $payload);

         // 4. Überprüfungen (Assertions):
         $response->assertStatus(200);
         $response->assertJson(['success' => true]);

         // Prüfe, ob der Call in der DB ist
         // mit den korrekten Spaltennamen aus dem Call-Model
         $this->assertDatabaseHas('calls', [
             'call_id' => $payload['call_id'],
             'successful' => 1, // Spalte: successful, Wert 1 für true
         ]);

         // Hole die ID des erstellten Calls für die JSON-Antwort-Prüfung
         $call = Call::where('call_id', $payload['call_id'])->first();
         $this->assertNotNull($call, 'Call wurde nicht in der Datenbank gefunden.');
         $response->assertJson(['call_id' => $call->id]);
     }

     /**
      * Testet den Webhook-Endpunkt mit fehlender call_id.
      * Erwartet einen 422 Validierungsfehler.
      *
      * @return void
      */
     public function test_webhook_returns_validation_error_if_call_id_is_missing(): void
     {
         // 1. Vorbereitung: Payload ohne call_id
         $payload = [
             // 'call_id' fehlt absichtlich
             'status' => 'call_ended',
             'phone_number' => '+491601111111',
         ];

         // 2. Aktion: Sende den POST-Request
         $response = $this->postJson('/api/webhooks/retell', $payload);

         // 3. Überprüfungen (Assertions):
         $response->assertStatus(422); // Erwarte HTTP 422 Unprocessable Entity
         // KORRIGIERTE PRÜFUNG: Prüft, ob die Fehlermeldung im details-Array existiert
         $response->assertJsonFragment(['The call id field is required.']);
         $response->assertJsonStructure([ // Prüft die allgemeine Struktur der Fehlerantwort
             'error',
             'details' => [
                 'call_id'
             ]
         ]);
     }

}
