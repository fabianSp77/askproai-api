<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomV2Service
{
    private $apiKey;
    private $baseUrlV1 = 'https://api.cal.com/v1';
    private $baseUrlV2 = 'https://api.cal.com/v2';

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.calcom.api_key') ?? env('DEFAULT_CALCOM_API_KEY');
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
     * V2 API für Verfügbarkeiten - mit korrektem Slot-Flattening
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
                $data = $response->json();
                $slots = $data['data']['slots'] ?? [];
                
                // Flatten the nested slots structure
                // V2 API returns slots grouped by date with time objects
                $flatSlots = [];
                foreach ($slots as $dateKey => $daySlots) {
                    if (is_array($daySlots)) {
                        foreach ($daySlots as $slot) {
                            // Handle both object format {"time": "..."} and direct string format
                            if (is_array($slot) && isset($slot['time'])) {
                                $flatSlots[] = $slot['time'];
                            } elseif (is_string($slot)) {
                                $flatSlots[] = $slot;
                            }
                        }
                    }
                }
                
                return [
                    'success' => true,
                    'data' => [
                        'slots' => $flatSlots,
                        'raw_slots' => $slots // Keep original structure for debugging
                    ]
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to check availability: ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com availability check error', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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
    
    /**
     * Hole alle Bookings mit Paginierung
     */
    public function getBookings($params = [])
    {
        try {
            // Use v2 API for bookings since v1 is not authorized
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ];
            
            $queryParams = array_merge([
                'limit' => $params['limit'] ?? 100,
                'page' => $params['page'] ?? 1
            ], $params);
            
            // Remove apiKey from params for v2
            unset($queryParams['apiKey']);
            
            $response = Http::withHeaders($headers)
                ->get($this->baseUrlV2 . '/bookings', $queryParams);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // v2 returns data in 'data' field
                $bookings = $responseData['data'] ?? [];
                
                // Normalize the response structure to match v1 format
                return [
                    'success' => true,
                    'data' => [
                        'bookings' => $bookings,
                        'total' => count($bookings), // v2 doesn't provide total in response
                        'page' => $queryParams['page'],
                        'total_pages' => 1 // v2 pagination works differently
                    ]
                ];
            }

            Log::error('Cal.com v2 getBookings failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to fetch bookings: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com v2 getBookings error', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Hole ein einzelnes Booking
     */
    public function getBooking($bookingId)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/bookings/' . $bookingId . '?apiKey=' . $this->apiKey);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getBooking error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all schedules (v2)
     */
    public function getSchedules()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/schedules');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch schedules: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getSchedules error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get teams (v2)
     */
    public function getTeams()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/teams');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch teams: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getTeams error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get team event types (v2)
     */
    public function getTeamEventTypes($teamId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/teams/' . $teamId . '/event-types');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch team event types: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getTeamEventTypes error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get webhooks (v2)
     */
    public function getWebhooks()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/webhooks');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch webhooks: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getWebhooks error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create webhook (v2)
     */
    public function createWebhook($subscriberUrl, $triggers = ['BOOKING_CREATED', 'BOOKING_CANCELLED', 'BOOKING_RESCHEDULED'])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/webhooks', [
                'subscriberUrl' => $subscriberUrl,
                'triggers' => $triggers,
                'active' => true,
                'secret' => config('services.calcom.webhook_secret')
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create webhook: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com createWebhook error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get event type with full details including hosts (v2)
     */
    public function getEventTypeDetails($eventTypeId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV2 . '/event-types/' . $eventTypeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch event type details: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com getEventTypeDetails error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel booking (v2)
     */
    public function cancelBooking($bookingId, $reason = null)
    {
        try {
            $data = [];
            if ($reason) {
                $data['cancellationReason'] = $reason;
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/bookings/' . $bookingId . '/cancel', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to cancel booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com cancelBooking error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reschedule booking (v2)
     */
    public function rescheduleBooking($bookingId, $start, $reason = null)
    {
        try {
            $data = [
                'start' => $start
            ];
            
            if ($reason) {
                $data['rescheduleReason'] = $reason;
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrlV2 . '/bookings/' . $bookingId . '/reschedule', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to reschedule booking: ' . $response->body()
            ];
            
        } catch (\Exception $e) {
            Log::error('Cal.com rescheduleBooking error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
