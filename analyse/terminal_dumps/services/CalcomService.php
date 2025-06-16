<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CalcomService
{
    public function createBookingWithTimeout(array $bookingDetails, string $teamSlug, int $timeoutSeconds = 5)
    {
        $baseUrl = config('calcom.base_url', 'https://api.cal.com/v1');
        $apiKey = config('calcom.api_key');

        // API-Key als Query-Parameter anhängen!
        $url = "$baseUrl/bookings?apiKey=$apiKey";

        return Http::timeout($timeoutSeconds)
            ->post($url, array_merge($bookingDetails, [
                'teamSlug' => $teamSlug,
            ]));
    }
}
