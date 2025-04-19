<?php

namespace App\Http\Controllers;

use App\Models\Kunde;
use Illuminate\Http\Request;

class KundeController extends Controller
{
    // Alle Kunden anzeigen
    public function index()
    {
        return response()->json(Kunde::all());
    }

    // Neuen Kunden erstellen
    public function store(Request $request)
    {
        $kunde = Kunde::create($request->all());
        return response()->json($kunde, 201);
    }

    // Einen Kunden anzeigen
    public function show(Kunde $kunden)
    {
        return response()->json($kunden);
    }

    // Einen Kunden aktualisieren
    public function update(Request $request, Kunde $kunden)
    {
        $kunden->update($request->all());
        return response()->json($kunden, 200);
    }

    // Einen Kunden löschen
    public function destroy(Kunde $kunden)
    {
        $kunden->delete();
        return response()->json(null, 204);
    }
}
