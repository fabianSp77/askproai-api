<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DirectCalcomController extends Controller
{
    const BASE_URL = 'https://api.cal.com/v2';

    const API_KEY = null; // Use config('services.calcom.api_key') instead

    const TIMEZONE = 'Europe/Berlin';

    const LANGUAGE = 'de';

    private function callWithRetry($url, $method = 'GET', $payload = [])
    {
        $attempts = 3;
        $response = null;

        while ($attempts-- > 0) {
            try {
                if ($method === 'GET') {
                    $response = Http::get($url);
                } else {
                    $response = Http::post($url, $payload);
                }

                if ($response->successful()) {
                    return ['success' => true, 'data' => $response->json()];
                }

                \Log::warning('API-Aufruf fehlgeschlagen', [
                    'httpCode' => $response->status(),
                    'response' => $response->body(),
                ]);
            } catch (\Throwable $e) {
                \Log::error('API-Aufruf Exception', ['exception' => $e->getMessage()]);
            }

            sleep(1);
        }

        return ['success' => false, 'data' => $response ? $response->json() : null];
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date',
            'eventTypeId' => 'required|integer',
            'userId' => 'nullable|integer',
        ]);

        $userId = $request->userId ?? 1346408; // Verwende Default-User-ID wenn nicht angegeben

        $url = self::BASE_URL.'/availability?apiKey='.self::API_KEY;
        $url .= '&eventTypeId='.$request->eventTypeId;
        $url .= '&userId='.$userId;
        $url .= '&dateFrom='.urlencode($request->dateFrom);
        $url .= '&dateTo='.urlencode($request->dateTo);

        $result = $this->callWithRetry($url);

        if (! $result['success']) {
            return response()->json([
                'error' => 'Verfügbarkeitsprüfung fehlgeschlagen',
                'details' => $result['data'] ?? 'Keine Daten verfügbar',
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
        $eventTypeDetails = $this->callWithRetry(self::BASE_URL."/event-types/{$request->eventTypeId}?apiKey=".self::API_KEY);
        if (! $eventTypeDetails['success'] || ! isset($eventTypeDetails['data']['event_type']['length'])) {
            return response()->json([
                'error' => 'Event-Typ-Daten konnten nicht geladen werden',
                'details' => $eventTypeDetails['data'] ?? 'Keine Daten verfügbar',
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
                ],
            ],
            'metadata' => (object) [],
            'responses' => (object) [
                'email' => $request->email,
                'name' => $request->name,
            ],
        ];
        $bookingResult = $this->callWithRetry(self::BASE_URL.'/bookings?apiKey='.self::API_KEY, 'POST', $payload);
        if (! $bookingResult['success']) {
            return response()->json([
                'error' => 'Buchung fehlgeschlagen',
                'details' => $bookingResult['data'] ?? 'Keine Daten verfügbar',
            ], 500);
        }

        return response()->json(['status' => 'success', 'booking' => $bookingResult['data']]);
    }
}
