<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CalcomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalComController extends Controller
{
    private $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    public function checkAvailability(Request $request)
    {
        try {
            $eventTypeId = $request->eventTypeId ?? env('CALCOM_EVENT_TYPE_ID');
            $dateFrom = $request->dateFrom ?? Carbon::now()->toIso8601String();
            $dateTo = $request->dateTo ?? Carbon::now()->addHours(24)->toIso8601String();

            // KEINE userId mehr verwenden!

            $result = $this->calcomService->checkAvailability(
                $eventTypeId,
                $dateFrom,
                $dateTo
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'availability' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Keine Verfügbarkeit gefunden'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Cal.com API availability error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createBooking(Request $request)
    {
        try {
            $request->validate([
                'eventTypeId' => 'required|integer',
                'start' => 'required|date',
                'name' => 'required|string',
                'email' => 'required|email'
            ]);

            // KEINE userId mehr verwenden!

            $startTime = Carbon::parse($request->start);
            $endTime = $startTime->copy()->addMinutes(30); // Standard 30 Minuten

            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null
            ];

            $result = $this->calcomService->bookAppointment(
                $request->eventTypeId,
                $startTime->toIso8601String(),
                $endTime->toIso8601String(),
                $customerData,
                $request->notes ?? null
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'booking' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Buchung fehlgeschlagen'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Cal.com API booking error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
