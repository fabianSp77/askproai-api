<?php

namespace App\Http\Controllers;

use App\Services\CalcomService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DirectCalcomController extends Controller
{
    private $calcomService;

    public function __construct(CalcomService $calcomService)
    {
        $this->calcomService = $calcomService;
    }

    public function checkAvailability(Request $request)
    {
        try {
            $request->validate([
                'eventTypeId' => 'required|integer',
                'dateFrom' => 'required|date',
                'dateTo' => 'required|date'
            ]);

            $result = $this->calcomService->checkAvailability(
                $request->eventTypeId,
                $request->dateFrom,
                $request->dateTo
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Keine Verfügbarkeit gefunden'
            ], 404);

        } catch (\Exception $e) {
            Log::error('DirectCalcom availability error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler bei der Verfügbarkeitsprüfung',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bookAppointment(Request $request)
    {
        try {
            $request->validate([
                'eventTypeId' => 'required|integer',
                'startTime' => 'required|date',
                'name' => 'required|string',
                'email' => 'required|email'
            ]);

            // KEINE userId mehr! Cal.com wählt automatisch einen verfügbaren User
            
            // Endzeit berechnen (30 Minuten Standard)
            $startTime = Carbon::parse($request->startTime);
            $endTime = $startTime->copy()->addMinutes(30);

            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null
            ];

            $notes = $request->notes ?? null;

            $result = $this->calcomService->bookAppointment(
                $request->eventTypeId,
                $startTime->toIso8601String(),
                $endTime->toIso8601String(),
                $customerData,
                $notes
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Termin erfolgreich gebucht'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terminbuchung fehlgeschlagen'
            ], 400);

        } catch (\Exception $e) {
            Log::error('DirectCalcom booking error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler bei der Terminbuchung',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getEventTypes()
    {
        try {
            $result = $this->calcomService->getEventTypes();

            if ($result) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Keine Event-Typen gefunden'
            ], 404);

        } catch (\Exception $e) {
            Log::error('DirectCalcom get event types error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Event-Typen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
