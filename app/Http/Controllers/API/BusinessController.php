<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Alle Unternehmen anzeigen.
     */
    public function index(): JsonResponse
    {
        $businesses = Business::all();

        return response()->json(['data' => $businesses]);
    }

    /**
     * Neues Unternehmen anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'adresse' => 'nullable|string',
            'telefon' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'website' => 'nullable|string|max:255',
            'oeffnungszeiten' => 'nullable|array',
            'api_key' => 'nullable|string',
            'cal_com_user_id' => 'nullable|string',
        ]);

        $business = Business::create($validatedData);

        return response()->json(['message' => 'Unternehmen erfolgreich erstellt', 'data' => $business], 201);
    }

    /**
     * Bestimmtes Unternehmen anzeigen.
     */
    public function show(Business $business): JsonResponse
    {
        return response()->json(['data' => $business]);
    }

    /**
     * Unternehmensdaten aktualisieren.
     */
    public function update(Request $request, Business $business): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'adresse' => 'nullable|string',
            'telefon' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
            'website' => 'nullable|string|max:255',
            'oeffnungszeiten' => 'nullable|array',
            'api_key' => 'nullable|string',
            'cal_com_user_id' => 'nullable|string',
        ]);

        $business->update($validatedData);

        return response()->json(['message' => 'Unternehmen erfolgreich aktualisiert', 'data' => $business]);
    }

    /**
     * Unternehmen löschen.
     */
    public function destroy(Business $business): JsonResponse
    {
        $business->delete();

        return response()->json(['message' => 'Unternehmen erfolgreich gelöscht'], 200);
    }
}
