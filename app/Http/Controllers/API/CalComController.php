<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalComController extends Controller
{
    public function bookAppointment(Request $request): JsonResponse
    {
        try {
            // Validate incoming request
            $validatedData = $request->validate([
                'eventTypeId' => 'required|numeric',
                'start' => 'required|date_format:Y-m-d\TH:i:s.u\Z',
                'name' => 'required|string',
                'email' => 'required|email',
                'timeZone' => 'required|string',
            ]);

            // Set duration based on event type (from screenshots)
            $duration = ($validatedData['eventTypeId'] == 2031153) ? 60 : 45; // Damen: 60min, Herren: 45min

            // Parse start date and add appropriate duration
            $startTime = new \DateTime($validatedData['start']);
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");
            $endTimeFormatted = $endTime->format('Y-m-d\TH:i:s.u\Z');

            // Cal.com API details
            $apiKey = config('services.calcom.api_key');
            $userId = '1346408';

            // Build the correct format for Cal.com API - matching their exact expected format
            $payload = [
                'eventTypeId' => (int) $validatedData['eventTypeId'],
                'start' => $validatedData['start'],
                'end' => $endTimeFormatted,
                'timeZone' => $validatedData['timeZone'],
                'language' => 'de',
                'metadata' => (object) [],
                'responses' => [
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'guests' => [],
                    'location' => ['optionValue' => '', 'value' => 'Vor Ort'],
                    'notes' => '',
                ],
                'hasHashedBookingLink' => false,
                'hashedLink' => null,
                'smsReminderNumber' => null,
            ];

            // Log what we're sending
            Log::info('Cal.com Request Payload', ['payload' => $payload]);

            // Make the actual API call using v2
            $ch = curl_init('https://api.cal.com/v2/bookings');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'cal-api-version: 2025-01-07',
                'Authorization: Bearer '.$apiKey,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log the raw response
            Log::info('Cal.com Raw Response', [
                'response' => $response,
                'httpCode' => $httpCode,
                'curlError' => $curlError,
            ]);

            $responseData = json_decode($response, true);

            if ($httpCode >= 400 || $curlError) {
                $errorDetails = $responseData ?? ['curlError' => $curlError];

                return response()->json([
                    'error' => 'Terminbuchung fehlgeschlagen',
                    'details' => $errorDetails,
                ], $httpCode ?: 500);
            }

            // Success response
            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Cal.com Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Terminbuchung fehlgeschlagen',
                'details' => ['message' => $e->getMessage()],
            ], 500);
        }
    }
}
