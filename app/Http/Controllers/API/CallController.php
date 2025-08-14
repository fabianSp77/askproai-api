<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Call;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    /**
     * Alle Anrufe anzeigen.
     */
    public function index(): JsonResponse
    {
        $calls = Call::all();

        return response()->json(['data' => $calls]);
    }

    /**
     * Neuen Anruf anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'call_id' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'business_id' => 'required|exists:businesses,id',
            'zeitpunkt' => 'required|date',
            'dauer' => 'required|integer|min:0',
            'telefonnummer_anrufer' => 'required|string',
            'sentiment' => 'nullable|string',
            'status' => 'required|string',
            'zusammenfassung' => 'nullable|string',
            'transkript' => 'nullable|string',
        ]);

        $call = Call::create($validatedData);

        return response()->json(['message' => 'Anruf erfolgreich erstellt', 'data' => $call], 201);
    }

    /**
     * Bestimmten Anruf anzeigen.
     */
    public function show(Call $call): JsonResponse
    {
        return response()->json(['data' => $call]);
    }

    /**
     * Anrufdaten aktualisieren.
     */
    public function update(Request $request, Call $call): JsonResponse
    {
        $validatedData = $request->validate([
            'call_id' => 'sometimes|string',
            'customer_id' => 'nullable|exists:customers,id',
            'business_id' => 'sometimes|exists:businesses,id',
            'zeitpunkt' => 'sometimes|date',
            'dauer' => 'sometimes|integer|min:0',
            'telefonnummer_anrufer' => 'sometimes|string',
            'sentiment' => 'nullable|string',
            'status' => 'sometimes|string',
            'zusammenfassung' => 'nullable|string',
            'transkript' => 'nullable|string',
        ]);

        $call->update($validatedData);

        return response()->json(['message' => 'Anruf erfolgreich aktualisiert', 'data' => $call]);
    }

    /**
     * Anruf löschen.
     */
    public function destroy(Call $call): JsonResponse
    {
        $call->delete();

        return response()->json(['message' => 'Anruf erfolgreich gelöscht'], 200);
    }
}
