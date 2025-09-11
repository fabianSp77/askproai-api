<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomBookingController extends Controller
{
    private string $baseUrl;
    private string $apiKey;
    private bool $useV2;

    public function __construct()
    {
        // Use services.calcom config for consistency
        $this->baseUrl = rtrim(config('services.calcom.base_url', config('calcom.base_url', 'https://api.cal.com/v2')), '/');
        $this->apiKey  = config('services.calcom.api_key', config('calcom.api_key', ''));
        
        // Determine API version from base URL
        $this->useV2 = str_contains($this->baseUrl, '/v2');
    }

    /**  POST /api/calcom/bookings  */
    public function createBooking(Request $request)
    {
        /* ---------- 1. Validieren -------------------------------------- */
        $data = $request->validate([
            'eventTypeId'       => 'required|integer',
            'start'             => 'required|date',
            'timeZone'          => 'required|string',
            'language'          => 'required|string',

            'metadata'          => 'sometimes',   // kein Typ-Zwang!
            'responses'         => 'sometimes|array',

            'attendee.name'     => 'required|string',
            'attendee.email'    => 'required|email',
            'attendee.timeZone' => 'required|string',
        ]);

        /* ---------- 2. metadata immer als Object ----------------------- */
        if (! array_key_exists('metadata', $data) || $data['metadata'] === []){
            $data['metadata'] = (object) [];
        } else {
            // Array → Objekt casten
            $data['metadata'] = json_decode(json_encode($data['metadata']));
        }

        /* ---------- 3. responses nur senden, wenn notwendig ------------ */
        if (empty($data['responses'] ?? null)) {
            $data['responses'] = (object) [
                'name'  => $data['attendee']['name'],
                'email' => $data['attendee']['email'],
            ];
        }

        /* ---------- 4. Call an Cal.com with V2 API -------------------- */
        try {
            if ($this->useV2) {
                // V2 API: Bearer authentication with headers
                $url = "{$this->baseUrl}/bookings";
                
                $resp = Http::acceptJson()
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'cal-api-version' => '2024-08-13',
                        'Content-Type' => 'application/json'
                    ])
                    ->post($url, $data);
                    
                Log::info('[CalcomBookingController] V2 API call made', [
                    'status' => $resp->status(),
                    'url' => $url
                ]);
            } else {
                // V1 API: Query parameter authentication (fallback)
                $url = "{$this->baseUrl}/bookings?apiKey={$this->apiKey}";
                $resp = Http::acceptJson()->post($url, $data);
                
                Log::info('[CalcomBookingController] V1 API call made', [
                    'status' => $resp->status()
                ]);
            }

            return $resp->successful()
                ? response()->json($resp->json(), 201)
                : response()->json(
                      ['error' => 'Cal.com-Fehler', 'details' => $resp->json()],
                      $resp->status() ?: 400
                  );
                  
        } catch (\Exception $e) {
            Log::error('[CalcomBookingController] Exception during booking:', [
                'message' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Booking failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
