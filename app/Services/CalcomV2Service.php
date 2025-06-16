<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomV2Service
{
    private $apiKey;
    private $baseUrlV1 = 'https://api.cal.com/v1';
    private $baseUrlV2 = 'https://api.cal.com/v2';

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key') ?? env('DEFAULT_CALCOM_API_KEY');
    }

    /**
     * V1 API für Users nutzen
     */
    public function getUsers()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/users?apiKey=' . $this->apiKey);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cal.com V1 getUsers failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com getUsers error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * V1 API für Event-Types nutzen
     */
    public function getEventTypes()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/event-types?apiKey=' . $this->apiKey);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com getEventTypes error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * V2 API für Verfügbarkeiten (wie gehabt)
     */
    public function checkAvailability($eventTypeId, $date, $timezone = 'Europe/Berlin')
    {
        try {
            $url = $this->baseUrlV2 . '/slots/available';
            
            $response = Http::withHeaders([
                'cal-api-version' => '2024-08-13',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($url, [
                'eventTypeId' => $eventTypeId,
                'startTime' => $date . 'T00:00:00.000Z',
                'endTime' => $date . 'T23:59:59.999Z',
                'timeZone' => $timezone
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com availability check error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * V1 API für Buchungen (wie im CalcomService)
     */
    public function bookAppointment($eventTypeId, $startTime, $endTime, $customerData, $notes = null)
    {
        try {
            $data = [
                'eventTypeId' => (int)$eventTypeId,
                'start' => $startTime,
                'timeZone' => 'Europe/Berlin',
                'language' => 'de',
                'metadata' => [
                    'source' => 'askproai',
                    'via' => 'phone_ai'
                ],
                'responses' => [
                    'name' => $customerData['name'] ?? 'Unbekannt',
                    'email' => $customerData['email'] ?? 'kunde@example.com',
                    'location' => 'phone'
                ]
            ];

            if ($notes) {
                $data['responses']['notes'] = $notes;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV1 . '/bookings?apiKey=' . $this->apiKey, $data);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com booking error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
