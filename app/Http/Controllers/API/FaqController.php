<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Alle FAQs anzeigen.
     */
    public function index(): JsonResponse
    {
        $faqs = Faq::all();

        return response()->json(['data' => $faqs]);
    }

    /**
     * Neue FAQ anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'frage' => 'required|string',
            'antwort' => 'required|string',
            'kategorie' => 'nullable|string',
            'haeufigkeit' => 'nullable|integer',
        ]);

        $faq = Faq::create($validatedData);

        return response()->json(['message' => 'FAQ erfolgreich erstellt', 'data' => $faq], 201);
    }

    /**
     * Bestimmte FAQ anzeigen.
     */
    public function show(Faq $faq): JsonResponse
    {
        return response()->json(['data' => $faq]);
    }

    /**
     * FAQ-Daten aktualisieren.
     */
    public function update(Request $request, Faq $faq): JsonResponse
    {
        $validatedData = $request->validate([
            'business_id' => 'sometimes|exists:businesses,id',
            'frage' => 'sometimes|string',
            'antwort' => 'sometimes|string',
            'kategorie' => 'nullable|string',
            'haeufigkeit' => 'nullable|integer',
        ]);

        $faq->update($validatedData);

        return response()->json(['message' => 'FAQ erfolgreich aktualisiert', 'data' => $faq]);
    }

    /**
     * FAQ löschen.
     */
    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ erfolgreich gelöscht'], 200);
    }
}
