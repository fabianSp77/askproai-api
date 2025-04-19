<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use Carbon\Carbon; // Carbon für Datums-/Zeitmanipulation hinzugefügt
use Throwable; // Throwable importieren

class CalcomService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeZone;
    protected $language;
    public $lastError = null;

    public function __construct()
    {
        $this->baseUrl = config('services.calcom.base_url', 'https://api.cal.com/v1');
        $this->apiKey = config('services.calcom.api_key', env('CALCOM_API_KEY'));
        $this->timeZone = config('app.timezone', 'Europe/Berlin');
        $this->language = config('app.locale', 'de');

        if (empty($this->apiKey)) {
            Log::error('Cal.com API Key nicht konfiguriert!');
        }
         if (empty($this->baseUrl)) {
            Log::error('Cal.com Base URL nicht konfiguriert!');
        }
    }

    /**
     * Prüft die Verfügbarkeit für einen bestimmten Event-Typ und Zeitraum.
     * (Unverändert)
     */
    public function checkAvailability(array $data)
    {
        $this->lastError = null;
        $maxRetries = 3;
        $retryDelay = 500;

        if (empty($data['eventTypeId']) || empty($data['dateFrom']) || empty($data['dateTo']) || empty($data['username'])) {
             Log::error('Unvollständige Daten für checkAvailability', ['data' => $data]);
             $this->lastError = 'Unvollständige Daten für Verfügbarkeitsprüfung.';
             throw new \InvalidArgumentException($this->lastError);
        }

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::acceptJson()->get("{$this->baseUrl}/availability", [
                    'apiKey' => $this->apiKey,
                    'eventTypeId' => $data['eventTypeId'],
                    'dateFrom' => $data['dateFrom'],
                    'dateTo' => $data['dateTo'],
                    'username' => $data['username'],
                    'timeZone' => $this->timeZone
                ]);

                Log::debug('Cal.com checkAvailability Antwort', [
                    'attempt' => $attempt,
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);

                if ($response->successful()) {
                    Log::info('✅ Cal.com Verfügbarkeitsprüfung erfolgreich', ['response_body' => $response->json()]);
                    return $response->json();
                }

                 Log::warning('⚠️ Versuch fehlgeschlagen bei Cal.com Verfügbarkeitsprüfung', [
                     'attempt' => $attempt,
                     'status' => $response->status(),
                     'response_body' => $response->body()
                 ]);
                 $this->lastError = "Cal.com API Fehler (Status {$response->status()}): " . $response->body();

                 if ($response->status() == 404) {
                     $this->lastError = "Cal.com Event Type {$data['eventTypeId']} oder User {$data['username']} nicht gefunden.";
                     Log::error('❌ ' . $this->lastError, ['data' => $data]);
                     throw new \Exception($this->lastError);
                 }

            } catch (RequestException $e) {
                $this->lastError = 'RequestException bei Cal.com Verfügbarkeitsprüfung: ' . $e->getMessage();
                Log::error('❌ ' . $this->lastError, [
                    'attempt' => $attempt,
                    'url' => "{$this->baseUrl}/availability",
                    'params' => $data
                ]);
            } catch (Throwable $e) {
                $this->lastError = 'Unerwarteter Fehler bei Cal.com Verfügbarkeitsprüfung: ' . $e->getMessage();
                 Log::error('❌ ' . $this->lastError, [
                    'attempt' => $attempt,
                 ]);
                 throw $e;
            }

            if ($attempt < $maxRetries) {
                usleep($retryDelay * 1000 * pow(2, $attempt - 1));
            }
        }

        $finalErrorMsg = 'Cal.com Verfügbarkeitsprüfung endgültig fehlgeschlagen nach ' . $maxRetries . ' Versuchen. Letzter Fehler: ' . $this->lastError;
        Log::error('❌ ' . $finalErrorMsg);
        throw new \Exception($finalErrorMsg);
    }

    /**
     * Holt Details zu einem Event-Typ von Cal.com.
     * (Unverändert)
     */
    public function getEventTypeDetails($eventTypeId)
    {
        $this->lastError = null;
        if (empty($eventTypeId)) {
            Log::warning('Keine EventTypeId für getEventTypeDetails angegeben.');
            return ['length' => 30];
        }

        try {
            $url = "{$this->baseUrl}/event-types/{$eventTypeId}?apiKey={$this->apiKey}";
            $response = Http::get($url);

            Log::debug('Cal.com getEventTypeDetails Antwort', [
                'eventTypeId' => $eventTypeId,
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                Log::info('✅ Cal.com Event-Typ-Details erfolgreich abgerufen.', ['eventTypeId' => $eventTypeId]);
                return $response->json()['eventType'] ?? ['length' => 30];
            }

            $this->lastError = "Cal.com Event-Typ-Details Abruf fehlgeschlagen (Status {$response->status()}): " . $response->body();
            Log::warning('⚠️ ' . $this->lastError, ['eventTypeId' => $eventTypeId]);

        } catch (RequestException $e) {
            $this->lastError = 'RequestException bei Cal.com Event-Typ-Details: ' . $e->getMessage();
            Log::error('❌ ' . $this->lastError, ['eventTypeId' => $eventTypeId, 'url' => $url ?? null]);
        } catch (Throwable $e) {
            $this->lastError = 'Unerwarteter Fehler bei Cal.com Event-Typ-Details: ' . $e->getMessage();
             Log::error('❌ ' . $this->lastError, ['eventTypeId' => $eventTypeId]);
        }

        Log::warning('Fallback: Standardlänge 30 Minuten wird verwendet.', ['eventTypeId' => $eventTypeId]);
        return ['length' => 30];
    }


    /**
     * Bucht einen Termin über die Cal.com API.
     *
     * @param array $bookingData Erwartet ['date', 'time', 'customerName', 'customerEmail', 'phone']
     * @return array ['success' => bool, 'message' => string, 'appointment_id' => string|null]
     * @throws Throwable Bei endgültigem Fehlschlag
     */
    public function bookAppointment(array $bookingData)
    {
        $this->lastError = null;
        // TODO: EventTypeId sollte konfigurierbar gemacht werden
        $eventTypeId = 2026901; // Festgelegt auf "Herren"

        if (empty($bookingData['date']) || empty($bookingData['time']) || empty($bookingData['customerName']) || empty($bookingData['customerEmail'])) {
            $this->lastError = 'Unvollständige Buchungsdaten.';
            Log::error($this->lastError, ['bookingData' => $bookingData]);
            return ['success' => false, 'message' => $this->lastError, 'appointment_id' => null];
        }

        try {
            // $eventTypeDetails = $this->getEventTypeDetails($eventTypeId); // Original-Zeile
            // $duration = $eventTypeDetails['length'] ?? 30; // Original-Zeile

            // TESTWEISE ÄNDERUNG: Dauer fix auf 30 Minuten setzen
            $duration = 30;
            Log::warning('TEST: Dauer fest auf 30 Minuten gesetzt für Cal.com Buchung.'); // Hinweis im Log

            $startDateTimeString = $bookingData['date'] . ' ' . $bookingData['time'];
            try {
                $startTime = Carbon::createFromFormat('Y-m-d H:i', $startDateTimeString, $this->timeZone);
            } catch (\Exception $parseEx) {
                Log::warning('Parsing mit Timezone fehlgeschlagen, versuche UTC.', ['tz' => $this->timeZone, 'datetime' => $startDateTimeString]);
                $startTime = Carbon::createFromFormat('Y-m-d H:i', $startDateTimeString);
            }

            if (!$startTime) {
                 $this->lastError = 'Ungültiges Datums-/Zeitformat.';
                 Log::error($this->lastError, ['date' => $bookingData['date'], 'time' => $bookingData['time']]);
                 return ['success' => false, 'message' => $this->lastError, 'appointment_id' => null];
            }

            $startTimeUtc = $startTime->copy()->utc();
            $endTimeUtc = $startTimeUtc->copy()->addMinutes($duration); // Verwendet jetzt die feste Dauer von 30

            // Payload für Cal.com API vorbereiten
            $payload = [
                'eventTypeId' => $eventTypeId,
                'start' => $startTimeUtc->toISOString(true),
                'end' => $endTimeUtc->toISOString(true), // Endzeit basiert jetzt auf 30 Min.
                'timeZone' => 'UTC',
                'language' => $this->language,
                'responses' => [
                    'name' => $bookingData['customerName'],
                    'email' => $bookingData['customerEmail'],
                    'location' => [
                        'optionValue' => 'phone',
                        'value' => $bookingData['phone'] ?? 'N/A'
                    ]
                ],
                 'metadata' => [
                     'source' => 'AskProAI Webhook',
                     'call_id_internal' => isset($bookingData['call_id']) ? (string)$bookingData['call_id'] : null
                 ],
                 'attendees' => [
                     [
                         'name' => $bookingData['customerName'],
                         'email' => $bookingData['customerEmail'],
                         'timeZone' => $this->timeZone,
                     ]
                 ],
            ];

            $maxRetries = 3;
            $retryDelay = 1000;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $url = "{$this->baseUrl}/bookings?apiKey={$this->apiKey}";
                    $response = Http::acceptJson()->post($url, $payload);

                    Log::debug('Cal.com bookAppointment Antwort', [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'response_body' => $response->body(),
                        'payload_sent' => $payload
                    ]);

                    if ($response->successful() && isset($response->json()['booking']['id'])) {
                        $bookingId = $response->json()['booking']['id'];
                        Log::info('✅ Cal.com Buchung erfolgreich erstellt', ['bookingId' => $bookingId]);
                         return [
                             'success' => true,
                             'message' => 'Termin erfolgreich gebucht.',
                             'appointment_id' => $bookingId
                         ];
                    }

                    $this->lastError = "Cal.com Buchungsversuch fehlgeschlagen (Status {$response->status()}): " . $response->body();
                    Log::warning('⚠️ ' . $this->lastError, [
                        'attempt' => $attempt,
                        ]);

                     if ($response->status() === 401 || $response->status() === 403) {
                         $this->lastError = 'Cal.com: Authentifizierung fehlgeschlagen (API Key?).';
                         Log::error('❌ ' . $this->lastError, ['status' => $response->status()]);
                         throw new \Exception($this->lastError);
                     }
                      if ($response->status() >= 400 && $response->status() < 500) {
                           throw new \Exception($this->lastError);
                      }

                } catch (RequestException $e) {
                    $this->lastError = 'RequestException bei Cal.com Buchung: ' . $e->getMessage();
                    Log::error('❌ ' . $this->lastError, [
                        'attempt' => $attempt,
                        'url' => $url ?? null,
                    ]);
                } catch (Throwable $e) {
                    $this->lastError = 'Unerwarteter Fehler bei Cal.com Buchung Versuch ' . $attempt . ': ' . $e->getMessage();
                    Log::error('❌ ' . $this->lastError);
                    if ($response->status() >= 400 && $response->status() < 500) throw $e;
                }

                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000 * pow(2, $attempt - 1));
                }
            }

            $finalErrorMsg = 'Cal.com Buchung endgültig fehlgeschlagen nach ' . $maxRetries . ' Versuchen. Letzter Fehler: ' . $this->lastError;
            Log::error('❌ ' . $finalErrorMsg);
            throw new \Exception($finalErrorMsg);

        } catch (Throwable $e) {
             $this->lastError = 'Genereller Fehler in bookAppointment-Prozess: ' . $e->getMessage();
             Log::critical($this->lastError, [
                 'trace' => $e->getTraceAsString(),
                 'bookingData' => $bookingData
             ]);
             throw $e;
        }
    }
}
