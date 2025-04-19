<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CalcomBookingController extends Controller
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = 'https://api.cal.com/v2';
    }

    public function createBooking(Request $request)
    {
        $bookingData = $request->validate([
            'eventTypeId' => 'required|integer',
            'start' => 'required|date',
            'attendee.name' => 'required|string',
            'attendee.email' => 'required|email',
            'attendee.timeZone' => 'required|string',
            'metadata' => 'sometimes|array'
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'cal-api-version' => '2024-08-13',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/bookings', $bookingData);

            if ($response->successful()) {
                return response()->json($response->json(), 201);
            }

            return response()->json([
                'error' => 'Fehler bei API-Aufruf',
                'details' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Serverfehler API-Verbindung',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
