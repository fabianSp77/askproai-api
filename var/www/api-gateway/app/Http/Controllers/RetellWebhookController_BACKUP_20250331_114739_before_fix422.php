<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Services\CalcomService; // Sicherstellen, dass der Service importiert wird
use Illuminate\Support\Facades\Validator; // Validator für Eingabeprüfung hinzugefügt
use Throwable; // Throwable für Catch-Blöcke importieren

class RetellWebhookController extends Controller
{
    protected $calcomService;

    // Dependency Injection für den Service nutzen (Best Practice)
    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    public function processWebhook(Request $request)
    {
        $startTime = microtime(true);
        $data = $request->all();

        // Struktur der eingehenden Daten validieren (Beispiel)
        $validator = Validator::make($data, [
            'call_id' => 'required|string',
            'status' => 'sometimes|string', // 'status' ist das Feld von Retell
            'phone_number' => 'sometimes|string|nullable',
            'duration' => 'sometimes|integer|nullable',
            'call_successful' => 'sometimes|boolean', // Hinzugefügt für Validierung
             // Füge hier weitere erwartete Felder hinzu
             '_datum__termin' => 'sometimes|date_format:Y-m-d',
             '_uhrzeit__termin' => 'sometimes|date_format:H:i',
             '_name' => 'sometimes|string|nullable',
             '_email' => 'sometimes|email|nullable',
        ]);

        if ($validator->fails()) {
            Log::error('Webhook Validierungsfehler', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $data
            ]);
            return response()->json(['error' => 'Ungültige Eingabedaten', 'details' => $validator->errors()], 422);
        }

         // Validierte Daten verwenden (sicherer)
         $validatedData = $validator->validated();


         // Initiales Logging mit validierten Daten
         Log::info('Retell Webhook empfangen (validiert)', ['call_id' => $validatedData['call_id'], 'status' => $validatedData['status'] ?? 'N/A']);


        try {
            // updateOrCreate verwendet die validierten Daten, wo möglich
            $call = Call::updateOrCreate(
                ['call_id' => $validatedData['call_id']],
                [
                    // KORREKTUR 1: Spaltenname in der DB ist 'call_status'
                    'call_status' => $validatedData['status'] ?? $data['status'] ?? 'unknown',
                    'phone_number' => $validatedData['phone_number'] ?? $data['phone_number'] ?? null,
                    // KORREKTUR 2: Spaltenname in der DB ist 'call_duration'
                    'call_duration' => $validatedData['duration'] ?? $data['duration'] ?? 0,
                    'transcript' => $data['transcript'] ?? null,
                    'summary' => $data['summary'] ?? null,
                    'user_sentiment' => $data['user_sentiment'] ?? null,
                    // KORREKTUR 3: Spaltenname in der DB ist 'successful'
                    'successful' => $validatedData['call_successful'] ?? $data['call_successful'] ?? false, // Spaltenname korrigiert
                    'disconnect_reason' => $data['disconnect_reason'] ?? null,
                    'raw_data' => json_encode($data)
                    // 'kunde_id' wird hier nicht gesetzt, muss evtl. später erfolgen
                ]
            );

            // Nur versuchen, einen Termin zu handhaben, wenn relevante Daten vorhanden sind
            if (isset($validatedData['_datum__termin'], $validatedData['_uhrzeit__termin'])) {
                 Log::info('Termindaten erkannt, versuche Terminbuchung.', ['call_id' => $call->id]);
                $this->handleAppointment($call, $validatedData); // Übergibt validierte Daten
            } else {
                Log::info('Keine vollständigen Termindaten im Webhook gefunden.', ['call_id' => $call->id]);
            }


            $processingTime = microtime(true) - $startTime;

            Log::info('Webhook erfolgreich verarbeitet', [
                'call_record_id' => $call->id, // Datenbank-ID des Calls
                'retell_call_id' => $call->call_id, // Retell Call ID
                'processing_time_sec' => round($processingTime, 3)
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $call->id, // Gibt die Datenbank-ID zurück
                'processing_time_sec' => round($processingTime, 3)
            ]);

        } catch (Throwable $e) { // Throwable fängt auch Datenbank-Exceptions etc.
            // Fehler im Hauptprozess loggen
            Log::error('Webhook Verarbeitung fehlgeschlagen', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 10)),
                'retell_call_id' => $data['call_id'] ?? 'nicht vorhanden',
                'payload_snippet' => array_slice($data, 0, 5)
            ]);

            // E-Mail-Benachrichtigung senden (nur wenn Mail konfiguriert ist)
            if (env('MAIL_MAILER') && env('MAIL_HOST') && env('ADMIN_EMAIL')) {
                try {
                    Mail::raw(
                        "Kritischer Fehler im AskProAI RetellWebhookController:\n\n" .
                        "Fehlermeldung: " . $e->getMessage() . "\n" .
                        "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n" .
                        "Retell Call ID: " . ($data['call_id'] ?? 'N/A') . "\n\n" .
                        "Payload (Auszug):\n" . json_encode(array_slice($data, 0, 5), JSON_PRETTY_PRINT),
                        function ($message) use ($data) {
                            $adminEmail = env('ADMIN_EMAIL', 'fabian@askproai.de'); // Standard jetzt deine E-Mail
                            $message->to($adminEmail)
                                    ->subject('⚠️ Kritischer Fehler im AskProAI RetellWebhook');
                        }
                    );
                     Log::info('Fehlerbenachrichtigung per E-Mail gesendet an ' . env('ADMIN_EMAIL'));
                } catch (Throwable $mailError) { // Throwable fängt auch Mail-Exceptions
                    Log::error('Fehler beim Senden der Fehlerbenachrichtigungs-E-Mail', [
                         'mail_error_message' => $mailError->getMessage(),
                         'original_error_message' => $e->getMessage()
                    ]);
                }
            } else {
                 Log::warning('Mail-Konfiguration unvollständig, keine Fehlerbenachrichtigung gesendet.');
            }

            // Standardisierte Fehlerantwort
            return response()->json(['error' => 'Interner Serverfehler bei der Webhook-Verarbeitung.'], 500);
        }
    }

     /**
      * Versucht, einen Termin über den CalcomService zu buchen.
      * Leitet an den Service weiter, der die Retry-Logik enthält.
      *
      * @param Call $call Das Call-Model-Objekt.
      * @param array $validatedData Die validierten Daten aus dem Webhook.
      * @throws Throwable Wenn die Buchung endgültig fehlschlägt (wird vom Service geworfen).
      */
    private function handleAppointment(Call $call, array $validatedData)
    {
         // Daten für den CalcomService vorbereiten
         $bookingData = [
             'date' => $validatedData['_datum__termin'],
             'time' => $validatedData['_uhrzeit__termin'],
             'customerName' => $validatedData['_name'] ?? $call->name ?? 'Unbekannter Kunde',
             'customerEmail' => filter_var($validatedData['_email'] ?? $call->email ?? '', FILTER_VALIDATE_EMAIL)
                                ? ($validatedData['_email'] ?? $call->email)
                                : 'termin+' . ($call->call_id ?? uniqid()) . '@askproai.de',
             'phone' => $validatedData['phone_number'] ?? $call->phone_number ?? null,
             'call_id' => $call->id
         ];

        try {
             Log::info('Starte Cal.com Terminbuchung...', ['call_id' => $call->id, 'booking_data_summary' => ['name' => $bookingData['customerName'], 'email' => $bookingData['customerEmail'], 'date' => $bookingData['date'], 'time' => $bookingData['time']]]);
             $appointmentResponse = $this->calcomService->bookAppointment($bookingData);

             if ($appointmentResponse['success']) {
                 Log::info('Terminbuchung über CalcomService erfolgreich gemeldet.', [
                     'call_id' => $call->id,
                     'calcom_appointment_id' => $appointmentResponse['appointment_id']
                 ]);
                 // Zukünftig: Termin lokal speichern (siehe auskommentiertes Beispiel unten)

             } else {
                 Log::warning('CalcomService meldete erfolglose Terminbuchung.', [
                    'call_id' => $call->id,
                    'message' => $appointmentResponse['message'] ?? 'Keine Detailmeldung.'
                 ]);
             }

        } catch (Throwable $e) {
             Log::error('Fehler bei der Ausführung von CalcomService->bookAppointment (vom Controller gefangen)', [
                'error' => $e->getMessage(),
                'call_id' => $call->id,
                'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 5)),
             ]);
             throw $e; // Wichtig: Weiterwerfen für Haupt-Catch-Block
        }
         /*
         // Beispiel: Lokales Speichern des Termins (muss an deine Appointments-Tabelle angepasst werden!)
         try {
             $startTime = \Carbon\Carbon::parse($bookingData['date'] . ' ' . $bookingData['time'], env('APP_TIMEZONE', 'Europe/Berlin'));
             // Annahme: Dauer kommt aus CalcomService oder ist fix
             $durationMinutes = $appointmentResponse['duration'] ?? 30;
             $endTime = $startTime->copy()->addMinutes($durationMinutes);

             $call->appointments()->create([
                  'kunde_id' => $call->kunde_id, // Benötigt Kunde Beziehung im Call Model
                  'start_time' => $startTime->toDateTimeString(),
                  'end_time' => $endTime->toDateTimeString(),
                  'external_id' => $appointmentResponse['appointment_id'],
                  'external_system' => 'Cal.com',
                  'status' => 'confirmed',
                  'service' => 'Herren', // TODO: Dynamisch machen
                  // Weitere Felder nach Bedarf...
             ]);
             Log::info('Termin lokal in DB gespeichert.', ['call_id' => $call->id, 'appointment_id' => $appointmentResponse['appointment_id']]);
         } catch (\Exception $dbError) {
              Log::error('Fehler beim Speichern des Termins in lokaler DB', ['error' => $dbError->getMessage(), 'call_id' => $call->id]);
         }
         */
    }
}
