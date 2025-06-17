<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Alle Kunden anzeigen.
     */
    public function index(): JsonResponse
    {
        $customers = Customer::all();
        return response()->json(['data' => $customers]);
    }

    /**
     * Neuen Kunden anlegen.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefonnummer' => 'required|string|max:20',
            'anrede' => 'nullable|string|max:10',
            'notizen' => 'nullable|string',
        ]);

        $customer = Customer::create($validatedData);
        return response()->json(['message' => 'Kunde erfolgreich erstellt', 'data' => $customer], 201);
    }

    /**
     * Bestimmten Kunden anzeigen.
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => $customer]);
    }

    /**
     * Kundendaten aktualisieren.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefonnummer' => 'sometimes|string|max:20',
            'anrede' => 'nullable|string|max:10',
            'notizen' => 'nullable|string',
        ]);

        $customer->update($validatedData);
        return response()->json(['message' => 'Kunde erfolgreich aktualisiert', 'data' => $customer]);
    }

    /**
     * Kunden löschen.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return response()->json(['message' => 'Kunde erfolgreich gelöscht'], 200);
    }
}
