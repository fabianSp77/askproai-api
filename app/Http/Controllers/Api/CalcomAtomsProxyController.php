<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CalcomAtomsProxyController extends Controller
{
    /**
     * Proxy all Cal.com API requests (atoms, slots, timezones, bookings, etc.)
     * Supports GET, POST, PUT, PATCH, DELETE with full body forwarding
     * Handles full path matching like: atoms/event-types/slug/public
     */
    public function proxyAll(Request $request, string $calcom_path)
    {
        $apiUrl = config('calcom.base_url');
        $url = "{$apiUrl}/{$calcom_path}";

        // Forward query parameters
        $queryParams = $request->query();

        // Get request method
        $method = strtolower($request->method());

        try {
            // Build HTTP request with headers (including Cal.com API key)
            $httpRequest = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('calcom.api_key'),
                'cal-api-version' => config('calcom.api_version', '2024-08-13'),
            ]);

            // Transform request body if needed (BookerEmbed v1 -> Cal.com API v2)
            $requestBody = $request->all();
            if ($method === 'post' && str_contains($calcom_path, 'bookings')) {
                $requestBody = $this->transformBookingRequest($requestBody);
            }

            // Forward request based on method
            if (in_array($method, ['post', 'put', 'patch'])) {
                // For POST/PUT/PATCH: forward body + query params
                $response = $httpRequest->$method($url, array_merge(
                    $requestBody,
                    $queryParams
                ));
            } else {
                // For GET/DELETE: only query params
                $response = $httpRequest->$method($url, $queryParams);
            }

            // Log errors for debugging
            if ($response->status() >= 400) {
                \Log::error('[Calcom API Proxy] Cal.com returned error', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                    'request_body' => $request->all(),
                    'response_body' => $response->json() ?? $response->body(),
                ]);
            } else if ($response->status() >= 200 && $response->status() < 300) {
                \Log::info('[Calcom API Proxy] Success', [
                    'method' => $method,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                // Log booking responses for debugging
                if ($method === 'post' && str_contains($calcom_path, 'bookings')) {
                    \Log::info('[Calcom API Proxy] Booking response', [
                        'original_response' => $response->json(),
                    ]);
                }
            }

            // Return response as-is, but log for debugging
            $responseBody = $response->body();

            if ($method === 'post' && str_contains($calcom_path, 'bookings') && $response->successful()) {
                \Log::info('[Calcom Booking] Full response being sent to frontend', [
                    'response' => json_decode($responseBody, true),
                ]);
            }

            return response($responseBody, $response->status())
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            \Log::error('[Calcom API Proxy] Error', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to proxy request to Cal.com'
            ], 500);
        }
    }

    /**
     * Transform BookerEmbed v1 request format to Cal.com API v2 format
     */
    private function transformBookingRequest(array $request): array
    {
        // Extract attendee info from "responses" (v1 format)
        $responses = $request['responses'] ?? [];

        // Build transformed request
        $transformed = [
            'attendee' => [
                'name' => $responses['name'] ?? '',
                'email' => $responses['email'] ?? '',
                'timeZone' => $request['timeZone'] ?? 'Europe/Berlin',
                'language' => $request['language'] ?? 'en',
            ],
            'start' => $request['start'],
            'eventTypeId' => $request['eventTypeId'],
            'metadata' => (object)[], // Empty object, not array
        ];

        // Add optional fields only if they exist and are valid
        if (!empty($responses['attendeePhoneNumber'])) {
            $transformed['attendee']['phoneNumber'] = $responses['attendeePhoneNumber'];
        }

        // Add notes if provided
        if (!empty($responses['notes'])) {
            $transformed['metadata'] = (object)['notes' => $responses['notes']];
        }

        return $transformed;
    }

    /**
     * Transform Cal.com API v2 response to BookerEmbed v1 expected format
     */
    private function transformBookingResponse(array $response): string
    {
        // Cal.com v2 returns: {status: "success", data: {...}}
        // BookerEmbed expects the booking data directly in an array-like structure

        if (isset($response['data'])) {
            // Wrap in array if BookerEmbed expects it
            return json_encode([
                'status' => $response['status'] ?? 'success',
                'data' => [$response['data']], // Wrap in array for BookerEmbed
            ]);
        }

        return json_encode($response);
    }
}
