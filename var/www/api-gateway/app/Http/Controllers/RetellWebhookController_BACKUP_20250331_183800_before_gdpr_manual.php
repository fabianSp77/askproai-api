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
        // Wichtig: $request->all() verwenden, um sicherzustellen, dass alle Daten gelesen werden
        $data = $request->all();

        // Frühes Logging des kompletten Payloads für Debugging
        Log::debug('Eingehender Webhook Payload', ['payload' => $data]);

        // Struktur der eingehenden Daten validieren
        // Sicherstellen, dass 'call_id' als required geprüft wird
        $validator = Validator::make($data, [
            'call_id' => 'required|string|max:255', // 'required' stellt sicher, dass es vorhanden und nicht leer ist
            'status' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|nullable|max:255',
            'duration' => 'sometimes|integer|nullable',
            'call_successful' => 'sometimes|boolean',
             '_datum__termin' => 'sometimes|required_with:_uhrzeit__termin|date_format:Y-m-d', // Wenn Uhrzeit da, muss Datum da sein
             '_uhrzeit__termin' => 'sometimes|required_with:_datum__termin|date_format:H:i', // Wenn Datum da, muss Uhrzeit da sein
             '_name' => 'sometimes|string|nullable|max:255',
             '_email' => 'sometimes|email|nullable|max:255',
        ]);

        if ($validator->fails()) {
            Log::error('Webhook Validierungsfehler', [
                'errors' => $validator->errors()->toArray(),
                'payload_snippet' => array_slice($data, 0, 5) // Nur ein Teil für den Log
            ]);
            // 422 ist der korrekte Statuscode für Validierungsfehler
            return response()->json(['error' => 'Ungültige Eingabedaten', 'details' => $validator->errors()], 422);
        }

         // Validierte Daten verwenden (sicherer)
         $validatedData = $validator->validated();

         // Initiales Logging nach erfolgreicher Validierung
         Log::info('Retell Webhook empfangen (validiert)', ['call_id' => $validatedData['call_id'], 'status' => $validatedData['status'] ?? 'N/A']);

        try {
            // updateOrCreate verwendet die validierten Daten oder Fallbacks
            $call = Call::updateOrCreate(
                ['call_id' => $validatedData['call_id']],
                [
                    'call_status' => $validatedData['status'] ?? $data['status'] ?? 'unknown',
                    'phone_number' => $validatedData['phone_number'] ?? $data['phone_number'] ?? null,
                    'call_duration' => $validatedData['duration'] ?? $data['duration'] ?? 0,
                    'transcript' => $data['transcript'] ?? null, // Diese Felder könnten sehr lang sein
                    'summary' => $data['summary'] ?? null,
                    'user_sentiment' => $data['user_sentiment'] ?? null,
                    'successful' => $validatedData['call_successful'] ?? $data['call_successful'] ?? false,
                    'disconnect_reason' => $data['disconnect_reason'] ?? null,
                    'raw_data' => json_encode($data) // Originaldaten speichern
                ]
            );

            // Nur versuchen, einen Termin zu handhaben, wenn relevante und validierte Daten vorhanden sind
            if (isset($validatedData['_datum__termin'], $validatedData['_uhrzeit__termin'])) {
                 Log::info('Termindaten erkannt, versuche Terminbuchung.', ['call_id' => $call->id]);
                // handleAppointment erwartet $validatedData, da diese die Termindaten sicher enthalten
                $this->handleAppointment($call, $validatedData);
            } else {
                Log::info('Keine vollständigen Termindaten im Webhook gefunden oder Validierung fehlgeschlagen.', ['call_id' => $call->id]);
            }


            $processingTime = microtime(true) - $startTime;

            Log::info('Webhook erfolgreich verarbeitet', [
                'call_record_id' => $call->id,
                'retell_call_id' => $call->call_id,
                'processing_time_sec' => round($processingTime, 3)
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $call->id, // Datenbank-ID des Calls
                'processing_time_sec' => round($processingTime, 3)
            ]);

        } catch (Throwable $e) {
            Log::error('Webhook Verarbeitung fehlgeschlagen', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 10)),
                'retell_call_id' => $data['call_id'] ?? 'nicht vorhanden',
                'payload_snippet' => array_slice($data, 0, 5)
            ]);

            // E-Mail-Benachrichtigung
            if (env('MAIL_MAILER') && env('MAIL_HOST') && env('ADMIN_EMAIL')) {
                try {
                    Mail::raw(
                        "Kritischer Fehler im AskProAI RetellWebhookController:\n\n" .
                        "Fehlermeldung: " . $e->getMessage() . "\n" .
                        "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n" .
                        "Retell Call ID: " . ($data['call_id'] ?? 'N/A') . "\n\n" .
                        "Payload (Auszug):\n" . json_encode(array_slice($data, 0, 5), JSON_PRETTY_PRINT),
                        function ($message) use ($data) {
                            $adminEmail = env('ADMIN_EMAIL', 'fabian@askproai.de');
                            $message->to($adminEmail)
                                    ->subject('⚠️ Kritischer Fehler im AskProAI RetellWebhook');
                        }
                    );
                     Log::info('Fehlerbenachrichtigung per E-Mail gesendet an ' . env('ADMIN_EMAIL'));
                } catch (Throwable $mailError) {
                    Log::error('Fehler beim Senden der Fehlerbenachrichtigungs-E-Mail', [
                         'mail_error_message' => $mailError->getMessage(),
                         'original_error_message' => $e->getMessage()
                    ]);
                }
            } else {
                 Log::warning('Mail-Konfiguration unvollständig, keine Fehlerbenachrichtigung gesendet.');
            }

            // Standardisierte Fehlerantwort für interne Fehler
            return response()->json(['error' => 'Interner Serverfehler bei der Webhook-Verarbeitung.'], 500);
        }
    }

     /**
      * Versucht, einen Termin über den CalcomService zu buchen.
      * Leitet an den Service weiter, der die Retry-Logik enthält.
      *
      * @param Call $call Das Call-Model-Objekt.
      * @param array $validatedData Die validierten Daten aus dem Webhook (enthält _datum, _uhrzeit etc.).
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
                                : 'termin+' . ($call->call_id ?? uniqid()) . '@askproai.de', // Eindeutige Fallback-E-Mail
             'phone' => $validatedData['phone_number'] ?? $call->phone_number ?? null,
             'call_id' => $call->id // Interne Call ID für Metadaten
         ];

        try {
             Log::info('Starte Cal.com Terminbuchung...', ['call_id' => $call->id, 'booking_data_summary' => ['name' => $bookingData['customerName'], 'email' => $bookingData['customerEmail'], 'date' => $bookingData['date'], 'time' => $bookingData['time']]]);
             $appointmentResponse = $this->calcomService->bookAppointment($bookingData);

             if ($appointmentResponse['success']) {
                 Log::info('Terminbuchung über CalcomService erfolgreich gemeldet.', [
                     'call_id' => $call->id,
                     'calcom_appointment_id' => $appointmentResponse['appointment_id']
                 ]);
                 // Hier könnte man den Termin in der eigenen DB speichern/aktualisieren

             } else {
                 Log::warning('CalcomService meldete erfolglose Terminbuchung.', [
                    'call_id' => $call->id,
                    'message' => $appointmentResponse['message'] ?? 'Keine Detailmeldung.'
                 ]);
                 // Hier ggf. eine spezifische, nicht-kritische Exception werfen oder anders signalisieren?
             }

        } catch (Throwable $e) {
             Log::error('Fehler bei der Ausführung von CalcomService->bookAppointment (vom Controller gefangen)', [
                'error' => $e->getMessage(),
                'call_id' => $call->id,
                'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 5)),
             ]);
             // Exception weiterwerfen, damit der Haupt-Catch-Block den 500er Fehler zurückgibt
             throw $e;
        }
    }
}
