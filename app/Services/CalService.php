<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalService
{
    protected $apiKey;

    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = config('services.calcom.base_url');
    }

    public function getEventTypes()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->get($this->baseUrl.'/event-types');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Cal.com API Fehler: '.$e->getMessage());

            return null;
        }
    }

    public function createBooking($data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->post($this->baseUrl.'/bookings', $data);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Cal.com Buchungsfehler: '.$e->getMessage());

            return null;
        }
    }
}
