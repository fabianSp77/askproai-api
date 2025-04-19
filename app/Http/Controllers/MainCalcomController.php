<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MainCalcomController extends Controller
{
    protected $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eventTypeId' => 'required|integer',
            'dateFrom' => 'required|date',
            'dateTo' => 'required|date|after:dateFrom',
            'username' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $availability = $this->calcomService->checkAvailability($request->only([
                'eventTypeId', 'dateFrom', 'dateTo', 'username'
            ]));

            return response()->json(['status' => 'success', 'availability' => $availability], 200);
        } catch (\Exception $e) {
            Log::error('❌ Verfügbarkeitsprüfung fehlgeschlagen', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Verfügbarkeitsprüfung fehlgeschlagen',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eventTypeId' => 'required|integer',
            'start' => 'required|date',
            'name' => 'required|string',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $eventTypeDetails = $this->calcomService->getEventTypeDetails($request->input('eventTypeId'));
            $duration = $eventTypeDetails['length'] ?? 30;

            $startTime = new \DateTime($request->input('start'));
            $endTime = clone $startTime;
            $endTime->modify("+{$duration} minutes");

            // ACHTUNG: end explizit mitgeben, wie von API verlangt!
            $bookingData = [
                'eventTypeId' => $request->input('eventTypeId'),
                'start' => $startTime->format('c'),
                'end' => $endTime->format('c'),
                'name' => $request->input('name'),
                'email' => $request->input('email')
            ];

            $booking = $this->calcomService->createBooking($bookingData);

            return response()->json(['status' => 'success', 'booking' => $booking], 200);
        } catch (\Exception $e) {
            Log::error('❌ Buchung fehlgeschlagen', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Buchung fehlgeschlagen',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}

