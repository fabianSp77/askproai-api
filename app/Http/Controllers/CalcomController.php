<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalcomController extends Controller
{
    private const BASE_URL = "https://api.cal.com/v1";
    private const API_KEY = "cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da";
    private const TIMEZONE = "Europe/Berlin";
    private const LANGUAGE = "de";
    private const MAX_RETRIES = 3;

    /**
     * Versucht einen API-Aufruf mit automatischen Wiederholungsversuchen bei Fehlern
     */
    private function callWithRetry($url, $method = 'GET', $data = null, $maxRetries = self::MAX_RETRIES)
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            $attempt++;

            try {
                $ch = curl_init($url);
                $options = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        "Accept: application/json",
                        "Content-Type: application/json"
                    ],
                    CURLOPT_SSL_VERIFYPEER => false
                ];

                if ($method === 'POST') {
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data);
                }

                curl_setopt_array($ch, $options);

                $response = curl_exec($ch);
                $error = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if (!$error) {
                    return [
                        'success' => true,
                        'data' => json_decode($response, true),
                        'httpCode' => $httpCode
                    ];
                }

                $lastError = $error;
                Log::warning("API call failed (attempt $attempt/$maxRetries)", ['error' => $error]);

                // Warte zwischen den Versuchen (exponentiell)
                if ($attempt < $maxRetries) {
                    usleep(100000 * pow(2, $attempt)); // 200ms, 400ms, 800ms...
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Exception during API call (attempt $attempt/$maxRetries)",
                    ['exception' => $e->getMessage()]);

                if ($attempt < $maxRetries) {
                    usleep(100000 * pow(2, $attempt));
                }
            }
        }

        return [
            'success' => false,
            'error' => $lastError
        ];
    }

    /**
     * Erstellt eine neue Terminbuchung bei Cal.com
     */
    public function createBooking(Request $request)
    {
        try {
            $request->validate([
                'eventTypeId' => 'required',
                'start' => 'required|string',
                'name' => 'required|string',
                'email' => 'required|email',
            ]);

            // Event-Typ-Details abrufen, um die Dauer zu bestimmen
            $url = self::BASE_URL . "/event-types/" . $request->eventTypeId . "?apiKey=" . self::API_KEY;
            $eventTypeResult = $this->callWithRetry($url);
            
            if (!$eventTypeResult['success']) {
                Log::error('Event Type Info Error', ['error' => $eventTypeResult['error']]);
                throw new \Exception("Fehler beim Abrufen der Eventdaten: " . $eventTypeResult['error']);
            }

            $eventTypeInfo = $eventTypeResult['data'];
            $duration = $eventTypeInfo['length'] ?? 30; // Standarddauer 30 Minuten falls nicht verfügbar

            // Startzeit parsen und Endzeit berechnen
            $startTime = new \DateTime($request->start);
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");

            // Format für Cal.com API
            $bookingData = [
                'eventTypeId' => (int)$request->eventTypeId,
                'start' => $request->start,
                'end' => $endTime->format('Y-m-d\TH:i:s.000\Z'), // WICHTIG: Explizite Endzeit
                'attendees' => [
                    [
                        'email' => $request->email,
                        'name' => $request->name,
                        'timeZone' => self::TIMEZONE,
                        'language' => self::LANGUAGE
                    ]
                ],
                'timeZone' => self::TIMEZONE,
                'language' => self::LANGUAGE
            ];

            Log::info('Cal.com Booking Payload', ['data' => $bookingData]);

            $url = self::BASE_URL . "/bookings?apiKey=" . self::API_KEY;
            $logUrl = preg_replace('/apiKey=([^&]+)/', 'apiKey=***', $url);
            Log::debug('Cal.com Booking URL', ['url' => $logUrl]);

            $bookingResult = $this->callWithRetry($url, 'POST', $bookingData);

            if (!$bookingResult['success']) {
                Log::error('Cal.com Booking Error', ['error' => $bookingResult['error']]);
                return response()->json(['error' => "Cal.com API Error: " . $bookingResult['error']], 500);
            }

            $httpCode = $bookingResult['httpCode'];
            $decodedResponse = $bookingResult['data'];
            
            Log::info('Cal.com Booking Response', ['code' => $httpCode, 'data' => $decodedResponse]);

            return $httpCode >= 400
                ? response()->json(['error' => 'Failed to create booking', 'details' => $decodedResponse, 'status_code' => $httpCode], $httpCode)
                : response()->json($decodedResponse);
                
        } catch (\Exception $e) {
            Log::error('Cal.com Exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to create booking', 'message' => $e->getMessage()], 500);
        }
    }
}
