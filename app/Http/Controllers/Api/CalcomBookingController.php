<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CalcomBookingController extends Controller
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.calcom.base_url'), '/');
        $this->apiKey  = config('services.calcom.api_key');
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

        /* ---------- 4. Call an Cal.com --------------------------------- */
        $url   = "{$this->baseUrl}/bookings?apiKey={$this->apiKey}";
        $resp  = Http::acceptJson()->post($url, $data);

        return $resp->successful()
            ? response()->json($resp->json(), 201)
            : response()->json(
                  ['error' => 'Cal.com-Fehler', 'details' => $resp->json()],
                  $resp->status() ?: 400
              );
    }
}
