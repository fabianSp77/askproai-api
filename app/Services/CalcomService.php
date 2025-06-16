<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CacheService;

class CalcomService
{
    private $apiKey;
    private $baseUrl;
    private $teamSlug;
    private $cacheService;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = 'https://api.cal.com/v1';
        $this->teamSlug = 'askproai'; // Team-Slug aus den Dokumenten
        $this->cacheService = app(CacheService::class);
    }
    
    /**
     * Set API key (for dynamic configuration)
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function checkAvailability($eventTypeId, $dateFrom, $dateTo)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/availability', [
                'apiKey' => $this->apiKey,
                'eventTypeId' => $eventTypeId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'teamSlug' => $this->teamSlug // Team-Slug hinzufügen
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cal.com availability check failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com availability error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

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

            if (!empty($customerData['phone'])) {
                $phoneNote = "Telefon: " . $customerData['phone'];
                if (isset($data['responses']['notes'])) {
                    $data['responses']['notes'] = $phoneNote . "\n" . $data['responses']['notes'];
                } else {
                    $data['responses']['notes'] = $phoneNote;
                }
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/bookings?apiKey=' . $this->apiKey, $data);

            if ($response->successful()) {
                Log::info('Cal.com booking successful', [
                    'response' => $response->json()
                ]);
                return $response->json();
            }

            Log::error('Cal.com booking failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com booking error', [
                'error' => $e->getMessage(),
                'eventTypeId' => $eventTypeId
            ]);
            throw $e;
        }
    }

    public function getEventTypes($companyId = null)
    {
        // Use company ID for cache key, default to config if not provided
        $cacheCompanyId = $companyId ?? 'default';
        
        return $this->cacheService->getEventTypes($cacheCompanyId, function () {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->get($this->baseUrl . '/event-types?apiKey=' . $this->apiKey);

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Cal.com get event types error', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
}
