<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalComController extends Controller
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = config('services.calcom.base_url');
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'dateFrom' => 'required',
                'dateTo' => 'required',
                'eventTypeId' => 'required',
                'userId' => 'required'
            ]);

            $response = Http::get($this->baseUrl . '/availability', [
                'apiKey' => $this->apiKey,
                'dateFrom' => $validated['dateFrom'],
                'dateTo' => $validated['dateTo'],
                'eventTypeId' => $validated['eventTypeId'],
                'userId' => $validated['userId']
            ]);

            Log::info('Cal.com API Response', [
                'statusCode' => $response->status(),
                'response' => $response->json()
            ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Cal.com API Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to check availability',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
