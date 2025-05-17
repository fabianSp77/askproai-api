<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class CalcomService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $eventTypeId;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.calcom.base_url'), '/');
        $this->apiKey      = config('services.calcom.api_key');
        $this->eventTypeId = config('services.calcom.event_type_id');
    }

    public function createBooking(array $bookingDetails): Response
    {
        $payload = [
            'eventTypeId' => (int)($bookingDetails['eventTypeId'] ?? $this->eventTypeId),
            'start'       => $bookingDetails['startTime'],
            'end'         => $bookingDetails['endTime'],
            'timeZone'    => $bookingDetails['timeZone'] ?? 'Europe/Berlin',
            'language'    => $bookingDetails['language'] ?? 'de',
            'metadata'    => (object)[],
            'responses'   => [
                'name'  => $bookingDetails['name'],
                'email' => $bookingDetails['email'],
            ],
        ];

        Log::channel('calcom')->debug('[Cal.com] Sende createBooking Payload:', $payload);

        $query = ['apiKey' => $this->apiKey];
        $fullUrl = $this->baseUrl . '/bookings?' . http_build_query($query);
        $resp = Http::acceptJson()->post($fullUrl, $payload);

        Log::channel('calcom')->debug('[Cal.com] Booking Response:', [
            'status' => $resp->status(),
            'body'   => $resp->json() ?? $resp->body(),
        ]);

        return $resp;
    }
}
