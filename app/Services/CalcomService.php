<?php

namespace App\Services;

use App\Models\Integration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CalcomService
{
    public function __construct(private Integration $integration) {}

    private function key(): string
    {
        return $this->integration->credentials['cal_api_key'];
    }

    private function endpoint(string $path): string
    {
        return "https://api.cal.com/v1/$path";
    }

    /** Verfügbarkeiten holen */
    public function availability(array $payload): Response
    {
        return Http::withToken($this->key())
                   ->post($this->endpoint('availability'), $payload);
    }

    /** Termin buchen */
    public function book(array $payload): Response
    {
        return Http::withToken($this->key())
                   ->post($this->endpoint('booking'), $payload);
    }
}
