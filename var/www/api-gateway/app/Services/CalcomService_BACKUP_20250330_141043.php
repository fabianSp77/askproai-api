<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class CalcomService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeZone;
    protected $language;

    public function __construct()
    {
        $this->baseUrl = config('services.calcom.base_url');
        $this->apiKey = config('services.calcom.api_key');
        $this->timeZone = config('app.timezone', 'Europe/Berlin');
        $this->language = config('app.locale', 'de');
    }

    public function checkAvailability(array $data)
    {
        $maxRetries = 3;
        $retryDelay = 500;

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

                if ($response->successful()) {
                    Log::info('✅ Verfügbarkeitsprüfung erfolgreich', ['response' => $response->json()]);
                    return $response->json();
                }

                Log::warning('⚠️ Versuch fehlgeschlagen bei Verfügbarkeitsprüfung', ['response' => $response->json()]);
            } catch (RequestException $e) {
                Log::error('❌ Fehler bei Verfügbarkeitsprüfung', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt
                ]);
            }

            if ($attempt < $maxRetries) {
                usleep($retryDelay * 1000);
            }
        }

        throw new \Exception('Cal.com Verfügbarkeitsprüfung fehlgeschlagen.');
    }

    public function getEventTypeDetails($eventTypeId)
    {
        try {
            $url = "{$this->baseUrl}/event-types/{$eventTypeId}?apiKey={$this->apiKey}";

            $response = Http::get($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Event-Typ-Details Abruf fehlgeschlagen', ['response' => $response->json()]);
        } catch (\Exception $e) {
            Log::error('Fehler bei Event-Typ-Details', ['error' => $e->getMessage()]);
        }

        return ['length' => 30];
    }

    public function createBooking(array $data)
    {
        $maxRetries = 3;
        $retryDelay = 500;

        $eventTypeDetails = $this->getEventTypeDetails($data['eventTypeId']);
        $duration = $eventTypeDetails['length'] ?? 30;

        $startTime = new \DateTime($data['start']);
        $endTime = clone $startTime;
        $endTime->modify("+{$duration} minutes");

        $payload = [
            'eventTypeId' => $data['eventTypeId'],
            'start' => $startTime->format('c'),
            'end' => $endTime->format('c'),
            'timeZone' => $this->timeZone,
            'language' => $this->language,
            'attendee' => [
                'name' => $data['name'],
                'email' => $data['email'],
            ],
            'metadata' => new \stdClass(),
            'responses' => [
                'name' => $data['name'],
                'email' => $data['email']
            ]
        ];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $url = "{$this->baseUrl}/bookings?apiKey={$this->apiKey}";
                $response = Http::post($url, $payload);

                if ($response->successful()) {
                    Log::info('✅ Cal.com Buchung erfolgreich erstellt', ['response' => $response->json()]);
                    return $response->json();
                }

                Log::warning('⚠️ Buchungsversuch fehlgeschlagen', ['attempt' => $attempt, 'response' => $response->json()]);
            } catch (RequestException $e) {
                Log::error('❌ Fehler bei Buchung', ['error' => $e->getMessage()]);
            }

            if ($attempt < $maxRetries) {
                usleep($retryDelay * 1000);
            }
        }

        throw new \Exception('Cal.com Buchung nach mehreren Versuchen fehlgeschlagen.');
    }
}
