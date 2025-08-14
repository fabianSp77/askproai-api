<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $eventTypeId;

    protected int $maxRetries;

    protected string $timezone;

    protected string $language;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.calcom.base_url'), '/');
        $this->apiKey = config('services.calcom.api_key');
        $this->eventTypeId = config('services.calcom.event_type_id');
        $this->maxRetries = config('services.calcom.max_retries', 3);
        $this->timezone = config('services.calcom.timezone', 'Europe/Berlin');
        $this->language = config('services.calcom.language', 'de');
    }

    /**
     * Get event type details from Cal.com
     */
    public function getEventType(int $eventTypeId): array
    {
        $url = $this->baseUrl."/event-types/{$eventTypeId}";

        $response = $this->makeApiCall('GET', $url);

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch event type: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Create booking with automatic event type lookup and end time calculation
     */
    public function createBookingFromCall(array $requestData): array
    {
        try {
            // Get event type details to determine duration
            $eventTypeInfo = $this->getEventType($requestData['eventTypeId']);
            $duration = $eventTypeInfo['length'] ?? 30;

            // Calculate end time
            $startTime = new \DateTime($requestData['start']);
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");

            // Prepare booking payload
            $bookingData = [
                'eventTypeId' => (int) $requestData['eventTypeId'],
                'start' => $requestData['start'],
                'end' => $endTime->format('Y-m-d\TH:i:s.000\Z'),
                'attendees' => [
                    [
                        'email' => $requestData['email'],
                        'name' => $requestData['name'],
                        'timeZone' => $this->timezone,
                        'language' => $this->language,
                    ],
                ],
                'timeZone' => $this->timezone,
                'language' => $this->language,
            ];

            Log::info('[CalcomService] Creating booking with payload:', $bookingData);

            // Make booking request
            $response = $this->makeApiCall('POST', $this->baseUrl.'/bookings', $bookingData);

            if (! $response->successful()) {
                Log::error('[CalcomService] Booking failed:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Failed to create booking: '.$response->body());
            }

            $bookingResult = $response->json();
            Log::info('[CalcomService] Booking created successfully:', $bookingResult);

            return $bookingResult;

        } catch (\Exception $e) {
            Log::error('[CalcomService] Exception during booking:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Legacy method for backward compatibility
     */
    public function createBooking(array $bookingDetails): Response
    {
        $payload = [
            'eventTypeId' => (int) ($bookingDetails['eventTypeId'] ?? $this->eventTypeId),
            'start' => $bookingDetails['startTime'],
            'end' => $bookingDetails['endTime'],
            'timeZone' => $bookingDetails['timeZone'] ?? $this->timezone,
            'language' => $bookingDetails['language'] ?? $this->language,
            'metadata' => (object) [],
            'responses' => [
                'name' => $bookingDetails['name'],
                'email' => $bookingDetails['email'],
            ],
        ];

        Log::channel('calcom')->debug('[Cal.com] Sende createBooking Payload:', $payload);

        return $this->makeApiCall('POST', $this->baseUrl.'/bookings', $payload);
    }

    /**
     * Make API call with retry logic
     */
    private function makeApiCall(string $method, string $url, ?array $data = null): Response
    {
        $attempt = 0;
        $lastResponse = null;

        // Add API key to URL
        $separator = str_contains($url, '?') ? '&' : '?';
        $url .= $separator.'apiKey='.$this->apiKey;

        // Log URL without API key for security
        $logUrl = preg_replace('/apiKey=([^&]+)/', 'apiKey=***', $url);

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                Log::debug("[CalcomService] API Call (attempt {$attempt}): {$method} {$logUrl}");

                $http = Http::acceptJson()->timeout(30);

                $response = match (strtoupper($method)) {
                    'GET' => $http->get($url),
                    'POST' => $http->post($url, $data ?? []),
                    'PUT' => $http->put($url, $data ?? []),
                    'DELETE' => $http->delete($url),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
                };

                // If successful, return immediately
                if ($response->successful()) {
                    Log::debug('[CalcomService] API Call successful', [
                        'status' => $response->status(),
                        'attempt' => $attempt,
                    ]);

                    return $response;
                }

                // Store last response for error handling
                $lastResponse = $response;

                Log::warning("[CalcomService] API Call failed (attempt {$attempt})", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                // Wait between retries (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1) * 100000; // 100ms, 200ms, 400ms...
                    usleep($waitTime);
                }

            } catch (\Exception $e) {
                Log::warning("[CalcomService] API Call exception (attempt {$attempt}): ".$e->getMessage());

                if ($attempt < $this->maxRetries) {
                    $waitTime = pow(2, $attempt - 1) * 100000;
                    usleep($waitTime);
                } else {
                    throw $e;
                }
            }
        }

        // All retries failed, return last response or throw exception
        if ($lastResponse) {
            return $lastResponse;
        }

        throw new \Exception("Cal.com API call failed after {$this->maxRetries} attempts");
    }
}
