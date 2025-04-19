<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RetellWebhookController extends Controller
{
    protected $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    // Hilfsfunktion zur Anonymisierung
    private function anonymizeEmail(?string $email): ?string {
        if (empty($email) || !str_contains($email, '@')) {
            return $email; // Bleibt null oder unverändert, wenn kein @
        }
        $parts = explode('@', $email);
        if (strlen($parts[0]) <= 1) {
            return '***@' . $parts[1]; // Nur *** wenn der lokale Teil zu kurz ist
        }
        return substr($parts[0], 0, 1) . str_repeat('*', strlen($parts[0]) - 1) . '@' . $parts[1];
    }

    private function anonymizePhone(?string $phone): ?string {
         if (empty($phone)) {
            return $phone;
        }
        $length = strlen($phone);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        // Ersetzt alles bis auf die letzten 4 Zeichen durch Sternchen
        return str_repeat('*', $length - 4) . substr($phone, -4);
    }


    public function processWebhook(Request $request)
    {
        $startTime = microtime(true);
        $data = $request->all();

        // DSGVO: Logge sensible Daten nur anonymisiert oder gar nicht
        Log::debug('Eingehender Webhook Payload (Auszug)', [
            'call_id' => $data['call_id'] ?? 'N/A',
            'status' => $data['status'] ?? 'N/A',
            //'phone_number' => $this->anonymizePhone($data['phone_number'] ?? null), // Beispiel Anonymisierung
            //'email' => $this->anonymizeEmail($data['_email'] ?? null) // Beispiel Anonymisierung
            'has_appointment_data' => (isset($data['_datum__termin'], $data['_uhrzeit__termin']))
            ]);

        $validator = Validator::make($data, [
            'call_id' => 'required|string|max:255',
            'status' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|nullable|max:255', // Validierung bleibt, aber Speicherung überdenken
            'duration' => 'sometimes|integer|nullable',
            'call_successful' => 'sometimes|boolean',
             '_datum__termin' => 'sometimes|required_with:_uhrzeit__termin|date_format:Y-m-d',
             '_uhrzeit__termin' => 'sometimes|required_with:_datum__termin|date_format:H:i',
             '_name' => 'sometimes|string|nullable|max:255', // Validierung bleibt
             '_email' => 'sometimes|email|nullable|max:255', // Validierung bleibt
        ]);

        if ($validator->fails()) {
            Log::error('Webhook Validierungsfehler', [
                'errors' => $validator->errors()->toArray(),
                'retell_call_id' => $data['call_id'] ?? 'N/A', // Call ID für Zuordnung loggen
                // KEINE weiteren Payload-Daten im Fehlerfall loggen, um DSGVO sicherzustellen
            ]);
            return response()->json(['error' => 'Ungültige Eingabedaten', 'details' => $validator->errors()], 422);
        }

         $validatedData = $validator->validated();

         Log::info('Retell Webhook empfangen (validiert)', ['call_id' => $validatedData['call_id'], 'status' => $validatedData['status'] ?? 'N/A']);

        try {
            // Speichere nur die benötigten, validierten oder anonymisierten Daten
            // DSGVO-Entscheidung: Welche Daten MÜSSEN gespeichert werden?
            $call = Call::updateOrCreate(
                ['call_id' => $validatedData['call_id']],
                [
                    'call_status' => $validatedData['status'] ?? $data['status'] ?? 'unknown',
                    // Entscheide: Echte Nummer speichern oder anonymisiert? Hängt vom Zweck ab.
                    // 'phone_number' => $validatedData['phone_number'] ?? $data['phone_number'] ?? null, // Option 1: Echt
                    'phone_number' => $this->anonymizePhone($validatedData['phone_number'] ?? $data['phone_number'] ?? null), // Option 2: Anonymisiert

                    'call_duration' => $validatedData['duration'] ?? $data['duration'] ?? 0,
                    'user_sentiment' => $data['user_sentiment'] ?? null,
                    'successful' => $validatedData['call_successful'] ?? $data['call_successful'] ?? false,
                    'disconnect_reason' => $data['disconnect_reason'] ?? null,

                    // Explizit NICHT speichern wg. DSGVO (außer es gibt triftigen Grund & Einwilligung)
                    'transcript' => null,
                    'summary' => null,
                    'raw_data' => null,
                    // 'name' => $validatedData['_name'] ?? null,
                    // 'email' => $this->anonymizeEmail($validatedData['_email'] ?? $data['_email'] ?? null), // E-Mail anonymisieren, falls gespeichert

                    // 'kunde_id' muss ggf. später durch separate Logik zugewiesen werden
                ]
            );

            if (isset($validatedData['_datum__termin'], $validatedData['_uhrzeit__termin'])) {
                 Log::info('Termindaten erkannt, versuche Terminbuchung.', ['call_id' => $call->id]);
                // Übergib nur die Daten, die für die Buchung *wirklich* nötig sind + anonymisierte Daten
                $this->handleAppointment($call, $validatedData, $data);
            } else {
                Log::info('Keine vollständigen Termindaten im Webhook gefunden.', ['call_id' => $call->id]);
            }


            $processingTime = microtime(true) - $startTime;
            Log::info('Webhook erfolgreich verarbeitet', [
                'call_record_id' => $call->id,
                'retell_call_id' => $call->call_id,
                'processing_time_sec' => round($processingTime, 3)
            ]);

            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'processing_time_sec' => round($processingTime, 3)
            ]);

        } catch (Throwable $e) {
            // DSGVO: Im Fehlerfall nur anonymisierte oder irrelevante Daten loggen
            Log::error('Webhook Verarbeitung fehlgeschlagen', [
                'exception_type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 5)), // Nur kurzer Trace
                'retell_call_id' => $data['call_id'] ?? 'N/A',
                // KEIN Payload Snippet mehr im ERROR log
            ]);

            // E-Mail-Benachrichtigung (ohne Payload)
            if (env('MAIL_MAILER') && env('MAIL_HOST') && env('ADMIN_EMAIL')) {
                try {
                    Mail::raw(
                        "Kritischer Fehler im AskProAI RetellWebhookController:\n\n" .
                        "Fehlermeldung: " . $e->getMessage() . "\n" .
                        "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n" .
                        "Retell Call ID: " . ($data['call_id'] ?? 'N/A'), // Nur die Call ID senden
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
            return response()->json(['error' => 'Interner Serverfehler bei der Webhook-Verarbeitung.'], 500);
        }
    }

 /**
  * @param Call $call
  * @param array $validatedData Validierte Daten aus dem Request
  * @param array $originalData Original-Payload für Fallbacks bei Name/Email (optional, besser vermeiden)
  * @throws Throwable
  */
private function handleAppointment(Call $call, array $validatedData, array $originalData = [])
{
     // DSGVO-Hinweis: Stelle sicher, dass du die Einwilligung hast, diese Daten zu verwenden.
     $bookingData = [
         'date' => $validatedData['_datum__termin'],
         'time' => $validatedData['_uhrzeit__termin'],
         // Name wird oft für Kalendereintrag benötigt
         'customerName' => $validatedData['_name'] ?? 'Termin via AskProAI', // Sicherer Fallback
         // E-Mail an Cal.com: Muss es die echte sein oder reicht eine anonyme?
         'customerEmail' => $validatedData['_email'] ?? 'no-reply+' . ($call->call_id ?? uniqid()) . '@askproai.de', // Fallback mit Call-ID
         // Telefonnummer: Wird sie für Cal.com benötigt? Wenn ja, Einwilligung prüfen.
         'phone' => $validatedData['phone_number'] ?? null, // Ggf. echte Nummer senden, wenn für Cal.com nötig
         'call_id' => $call->id // Interne Call ID für Metadaten
     ];

    try {
         Log::info('Starte Cal.com Terminbuchung...', ['call_id' => $call->id, 'booking_data_summary' => ['date' => $bookingData['date'], 'time' => $bookingData['time']]]); // Nur unkritische Daten loggen
         $appointmentResponse = $this->calcomService->bookAppointment($bookingData);

         if ($appointmentResponse['success']) {
             Log::info('Terminbuchung über CalcomService erfolgreich gemeldet.', [
                 'call_id' => $call->id,
                 'calcom_appointment_id' => $appointmentResponse['appointment_id']
             ]);
             // TODO: Ggf. lokalen Appointment-Eintrag erstellen (nur mit notwendigen Daten)

         } else {
             Log::warning('CalcomService meldete erfolglose Terminbuchung.', [
                'call_id' => $call->id,
                'calcom_error' => $this->calcomService->lastError ?? ($appointmentResponse['message'] ?? 'Unbekannter Cal.com Fehler') // Detail von Service holen
             ]);
         }

    } catch (Throwable $e) {
         Log::error('Fehler bei der Ausführung von CalcomService->bookAppointment (vom Controller gefangen)', [
            'error' => $e->getMessage(),
            'call_id' => $call->id,
            'trace_snippet' => implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 5)),
         ]);
         throw $e; // Exception weiterwerfen, damit der Haupt-Catch-Block den 500er Fehler zurückgibt
    }
}
