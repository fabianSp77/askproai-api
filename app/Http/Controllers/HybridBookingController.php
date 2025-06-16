<?php

namespace App\Http\Controllers;

use App\Services\CalcomService;      // V1 für Verfügbarkeit
use App\Services\CalcomV2Service;    // V2 für Buchungen
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HybridBookingController extends Controller
{
    private $calcomV1;
    private $calcomV2;

    public function __construct()
    {
        $this->calcomV1 = new CalcomService();
        $this->calcomV2 = new CalcomV2Service();
    }

    /**
     * Verfügbare Slots abrufen (V1)
     */
    public function getAvailableSlots(Request $request)
    {
        $eventTypeId = $request->input('eventTypeId', 2026302);
        $date = $request->input('date', Carbon::tomorrow()->format('Y-m-d'));
        
        try {
            $availability = $this->calcomV1->checkAvailability(
                $eventTypeId,
                $date,
                $date,
                'Europe/Berlin'
            );

            return response()->json([
                'success' => true,
                'date' => $date,
                'slots' => $availability['slots'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen der Verfügbarkeit', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der verfügbaren Termine'
            ], 500);
        }
    }

    /**
     * Termin buchen (V2)
     */
    public function bookAppointment(Request $request)
    {
        $request->validate([
            'eventTypeId' => 'required|integer',
            'start' => 'required|date',
            'name' => 'required|string',
            'email' => 'required|email'
        ]);

        try {
            $booking = $this->calcomV2->bookAppointment(
                $request->input('eventTypeId'),
                $request->input('start'),
                [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'timeZone' => $request->input('timeZone', 'Europe/Berlin')
                ]
            );

            return response()->json([
                'success' => true,
                'booking' => $booking
            ]);

        } catch (\Exception $e) {
            Log::error('Fehler bei der Terminbuchung', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Terminbuchung'
            ], 500);
        }
    }

    /**
     * Automatisch nächsten verfügbaren Slot buchen
     */
    public function bookNextAvailable(Request $request)
    {
        $eventTypeId = $request->input('eventTypeId', 2026302);
        $date = $request->input('date', Carbon::tomorrow()->format('Y-m-d'));
        $name = $request->input('name', 'Test Kunde');
        $email = $request->input('email', 'test@example.com');

        try {
            // 1. Verfügbarkeit prüfen (V1)
            Log::info('Prüfe Verfügbarkeit via V1', ['date' => $date]);
            
            $availability = $this->calcomV1->checkAvailability(
                $eventTypeId,
                $date,
                $date,
                'Europe/Berlin'
            );

            if (empty($availability['slots'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Keine freien Termine verfügbar'
                ], 404);
            }

            // 2. Ersten verfügbaren Slot buchen (V2)
            $firstSlot = $availability['slots'][0];
            $startTime = Carbon::parse($firstSlot['time'])->toIso8601String();

            Log::info('Buche Termin via V2', ['start' => $startTime]);

            $booking = $this->calcomV2->bookAppointment(
                $eventTypeId,
                $startTime,
                [
                    'name' => $name,
                    'email' => $email,
                    'timeZone' => 'Europe/Berlin'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Termin erfolgreich gebucht',
                'booking' => $booking,
                'slot' => $firstSlot
            ]);

        } catch (\Exception $e) {
            Log::error('Fehler im Hybrid-Booking', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Terminverarbeitung'
            ], 500);
        }
    }
}
