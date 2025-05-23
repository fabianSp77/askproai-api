<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    /**
     * Alle Termine anzeigen.
     */
    public function index(): JsonResponse
    {
        $appointments = Appointment::all();
        return response()->json(['data' => $appointments]);
    }

    /**
     * Neuen Termin anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'business_id' => 'required|exists:businesses,id',
            'staff_id' => 'required|exists:staff,id',
            'service_id' => 'required|exists:services,id',
            'datum' => 'required|date',
            'uhrzeit' => 'required|date_format:H:i',
            'dauer' => 'required|integer|min:1',
            'status' => 'required|string',
            'cal_com_buchungs_id' => 'nullable|string',
            'notizen' => 'nullable|string',
        ]);

        $appointment = Appointment::create($validatedData);
        return response()->json(['message' => 'Termin erfolgreich erstellt', 'data' => $appointment], 201);
    }

    /**
     * Bestimmten Termin anzeigen.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json(['data' => $appointment]);
    }

    /**
     * Termindaten aktualisieren.
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validatedData = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'business_id' => 'sometimes|exists:businesses,id',
            'staff_id' => 'sometimes|exists:staff,id',
            'service_id' => 'sometimes|exists:services,id',
            'datum' => 'sometimes|date',
            'uhrzeit' => 'sometimes|date_format:H:i',
            'dauer' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string',
            'cal_com_buchungs_id' => 'nullable|string',
            'notizen' => 'nullable|string',
        ]);

        $appointment->update($validatedData);
        return response()->json(['message' => 'Termin erfolgreich aktualisiert', 'data' => $appointment]);
    }

    /**
     * Termin löschen.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();
        return response()->json(['message' => 'Termin erfolgreich gelöscht'], 200);
    }
}
