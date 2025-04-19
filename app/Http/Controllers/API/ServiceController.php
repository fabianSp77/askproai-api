<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * Alle Dienstleistungen anzeigen.
     */
    public function index(): JsonResponse
    {
        $services = Service::all();
        return response()->json(['data' => $services]);
    }

    /**
     * Neue Dienstleistung anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'beschreibung' => 'nullable|string',
            'dauer' => 'required|integer|min:1',
            'preis' => 'nullable|numeric|min:0',
            'cal_com_event_type_id' => 'nullable|string',
        ]);

        $service = Service::create($validatedData);
        return response()->json(['message' => 'Dienstleistung erfolgreich erstellt', 'data' => $service], 201);
    }

    /**
     * Bestimmte Dienstleistung anzeigen.
     */
    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service]);
    }

    /**
     * Dienstleistungsdaten aktualisieren.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'sometimes|exists:businesses,id',
            'name' => 'sometimes|string|max:255',
            'beschreibung' => 'nullable|string',
            'dauer' => 'sometimes|integer|min:1',
            'preis' => 'nullable|numeric|min:0',
            'cal_com_event_type_id' => 'nullable|string',
        ]);

        $service->update($validatedData);
        return response()->json(['message' => 'Dienstleistung erfolgreich aktualisiert', 'data' => $service]);
    }

    /**
     * Dienstleistung löschen.
     */
    public function destroy(Service $service): JsonResponse
    {
        $service->delete();
        return response()->json(['message' => 'Dienstleistung erfolgreich gelöscht'], 200);
    }
}
