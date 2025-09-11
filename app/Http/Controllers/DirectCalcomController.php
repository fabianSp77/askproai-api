<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectCalcomController extends Controller
{
    const TIMEZONE = 'Europe/Berlin';
    const LANGUAGE = 'de';
    
    private string $baseUrl;
    private string $apiKey;
    private bool $useV2;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.calcom.base_url', 'https://api.cal.com/v2'), '/');
        $this->apiKey = config('services.calcom.api_key', '');
        $this->useV2 = str_contains($this->baseUrl, '/v2');
    }

    private function callWithRetry($url, $method = 'GET', $payload = [])
    {
        $attempts = 3;
        $response = null;

        while ($attempts-- > 0) {
            try {
                // Build HTTP client with proper authentication
                if ($this->useV2) {
                    // V2 API: Bearer authentication with headers
                    $http = Http::acceptJson()
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'cal-api-version' => '2024-08-13',
                            'Content-Type' => 'application/json'
                        ]);
                } else {
                    // V1 API: Query parameter authentication (fallback)
                    $http = Http::acceptJson();
                    // Add API key to URL for V1
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url = $url . $separator . 'apiKey=' . $this->apiKey;
                }

                if ($method === 'GET') {
                    $response = $http->get($url);
                } else {
                    $response = $http->post($url, $payload);
                }

                if ($response->successful()) {
                    return ['success' => true, 'data' => $response->json()];
                }

                Log::warning("[DirectCalcomController] API-Aufruf fehlgeschlagen", [
                    'httpCode' => $response->status(),
                    'response' => $response->body(),
                    'url' => preg_replace('/apiKey=[^&]+/', 'apiKey=***', $url)
                ]);
            } catch (\Throwable $e) {
                Log::error("[DirectCalcomController] API-Aufruf Exception", [
                    'exception' => $e->getMessage()
                ]);
            }

            if ($attempts > 0) {
                sleep(1);
            }
        }

        return ['success' => false, 'data' => $response ? $response->json() : null];
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date',
            'eventTypeId' => 'required|integer',
            'userId' => 'nullable|integer'
        ]);

        $userId = $request->userId ?? 1346408; // Verwende Default-User-ID wenn nicht angegeben

        // Build URL without API key (will be added in headers for V2)
        $queryParams = http_build_query([
            'eventTypeId' => $request->eventTypeId,
            'userId' => $userId,
            'dateFrom' => $request->dateFrom,
            'dateTo' => $request->dateTo
        ]);
        
        $url = $this->baseUrl . "/availability?" . $queryParams;

        $result = $this->callWithRetry($url);

        if (!$result['success']) {
            return response()->json([
                'error' => 'Verfügbarkeitsprüfung fehlgeschlagen',
                'details' => $result['data'] ?? 'Keine Daten verfügbar'
            ], 500);
        }
        return response()->json($result['data']);
    }
    
    public function createBooking(Request $request)
    {
        $request->validate([
            'eventTypeId' => 'required|integer',
            'start' => 'required|date',
            'name' => 'required|string',
            'email' => 'required|email',
        ]);
        
        // Get event type details
        $eventTypeUrl = $this->baseUrl . "/event-types/{$request->eventTypeId}";
        $eventTypeDetails = $this->callWithRetry($eventTypeUrl);
        
        if (!$eventTypeDetails['success'] || !isset($eventTypeDetails['data']['event_type']['length'])) {
            return response()->json([
                'error' => 'Event-Typ-Daten konnten nicht geladen werden',
                'details' => $eventTypeDetails['data'] ?? 'Keine Daten verfügbar'
            ], 500);
        }
        
        $duration = $eventTypeDetails['data']['event_type']['length'];
        $startTime = new \DateTime($request->start);
        $endTime = clone $startTime;
        $endTime->modify("+{$duration} minutes");
        
        $payload = [
            'eventTypeId' => $request->eventTypeId,
            'start' => $startTime->format('c'),
            'end' => $endTime->format('c'),
            'timeZone' => self::TIMEZONE,
            'language' => self::LANGUAGE,
            'attendees' => [
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'timeZone' => self::TIMEZONE,
                    'language' => self::LANGUAGE,
                ]
            ],
            'metadata' => (object)[],
            'responses' => (object)[
                'email' => $request->email,
                'name' => $request->name
            ]
        ];
        
        // Create booking
        $bookingUrl = $this->baseUrl . "/bookings";
        $bookingResult = $this->callWithRetry($bookingUrl, 'POST', $payload);
        
        if (!$bookingResult['success']) {
            return response()->json([
                'error' => 'Buchung fehlgeschlagen',
                'details' => $bookingResult['data'] ?? 'Keine Daten verfügbar'
            ], 500);
        }
        
        return response()->json(['status' => 'success', 'booking' => $bookingResult['data']]);
    }
}
