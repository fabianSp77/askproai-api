<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CalcomHybridService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CalComController extends Controller
{
    protected CalcomHybridService $calcomService;
    
    public function __construct(CalcomHybridService $calcomService)
    {
        $this->calcomService = $calcomService;
    }
    
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
            
            // Prepare booking data for hybrid service
            $bookingData = [
                'eventTypeId' => (int)$validatedData['eventTypeId'],
                'start' => $validatedData['start'],
                'attendee' => [
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'timeZone' => $validatedData['timeZone'],
                    'language' => 'de'
                ],
                'metadata' => [],
                'location' => ['type' => 'address', 'address' => 'Vor Ort']
            ];
            
            // Log what we're sending
            Log::info('[CalComController] Creating booking via hybrid service', [
                'event_type_id' => $bookingData['eventTypeId'],
                'attendee' => $bookingData['attendee']['email']
            ]);
            
            // Use the hybrid service to create booking (will use V2)
            $bookingResult = $this->calcomService->createBooking($bookingData);
            
            // Log the result
            Log::info('[CalComController] Booking created successfully', [
                'booking_uid' => $bookingResult['uid'] ?? 'unknown',
                'booking_id' => $bookingResult['id'] ?? 'unknown'
            ]);
            
            // Return successful response
            return response()->json([
                'success' => true,
                'message' => 'Terminbuchung erfolgreich',
                'data' => $bookingResult
            ], 201);
        } catch (\Exception $e) {
            Log::error('Cal.com Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Terminbuchung fehlgeschlagen',
                'details' => ['message' => $e->getMessage()]
            ], 500);
        }
    }
}

