<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kunde;
use Illuminate\Support\Facades\Validator;

class KundeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kunden = Kunde::all();
        return response()->json($kunden);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:kunden',
            'telefonnummer' => 'required|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kunde = Kunde::create($request->all());
        return response()->json($kunde, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kunde = Kunde::find($id);
        
        if (!$kunde) {
            return response()->json(['message' => 'Kunde nicht gefunden'], 404);
        }
        
        return response()->json($kunde);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kunde = Kunde::find($id);
        
        if (!$kunde) {
            return response()->json(['message' => 'Kunde nicht gefunden'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:kunden,email,' . $id,
            'telefonnummer' => 'string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kunde->update($request->all());
        return response()->json($kunde);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kunde = Kunde::find($id);
        
        if (!$kunde) {
            return response()->json(['message' => 'Kunde nicht gefunden'], 404);
        }
        
        $kunde->delete();
        return response()->json(['message' => 'Kunde erfolgreich gelöscht']);
    }
}
