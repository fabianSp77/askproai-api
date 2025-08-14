<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Alle Mitarbeiter anzeigen.
     */
    public function index(): JsonResponse
    {
        $staff = Staff::all();

        return response()->json(['data' => $staff]);
    }

    /**
     * Neuen Mitarbeiter anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'cal_com_event_type_id' => 'nullable|string',
            'verfuegbarkeit' => 'nullable|array',
        ]);

        $staff = Staff::create($validatedData);

        return response()->json(['message' => 'Mitarbeiter erfolgreich erstellt', 'data' => $staff], 201);
    }

    /**
     * Bestimmten Mitarbeiter anzeigen.
     */
    public function show(Staff $staff): JsonResponse
    {
        return response()->json(['data' => $staff]);
    }

    /**
     * Mitarbeiterdaten aktualisieren.
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'sometimes|exists:businesses,id',
            'name' => 'sometimes|string|max:255',
            'position' => 'nullable|string|max:255',
            'cal_com_event_type_id' => 'nullable|string',
            'verfuegbarkeit' => 'nullable|array',
        ]);

        $staff->update($validatedData);

        return response()->json(['message' => 'Mitarbeiter erfolgreich aktualisiert', 'data' => $staff]);
    }

    /**
     * Mitarbeiter löschen.
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return response()->json(['message' => 'Mitarbeiter erfolgreich gelöscht'], 200);
    }
}
