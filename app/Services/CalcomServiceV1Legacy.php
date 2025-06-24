<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomServiceV1Legacy
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = 'https://api.cal.com/v1';
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
                'dateTo' => $dateTo
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

    public function getEventTypes()
    {
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
    }

    public function getBookings($params = [])
    {
        try {
            $queryParams = array_merge([
                'apiKey' => $this->apiKey
            ], $params);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/bookings', $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Cal.com get bookings failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Cal.com get bookings error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function cancelBooking($bookingId, $reason = null)
    {
        try {
            $data = [];
            if ($reason) {
                $data['reason'] = $reason;
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl . '/bookings/' . $bookingId . '?apiKey=' . $this->apiKey, $data);

            if ($response->successful()) {
                Log::info('Cal.com booking cancelled', [
                    'bookingId' => $bookingId
                ]);
                return true;
            }

            Log::error('Cal.com cancel booking failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'bookingId' => $bookingId
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Cal.com cancel booking error', [
                'error' => $e->getMessage(),
                'bookingId' => $bookingId
            ]);
            return false;
        }
    }
}
