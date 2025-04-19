<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kunde;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class KundenController extends Controller
{
    // Login Methode (wichtigste Änderung!)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // User prüfen
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Ungültige Zugangsdaten.'], 401);
        }

        // Token generieren
        $token = $user->createToken('API Token')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    // Kunden anzeigen
    public function index()
    {
        return response()->json(Kunde::all());
    }

    // Kunde speichern
    public function store(Request $request)
    {
        $kunde = Kunde::create($request->all());
        return response()->json($kunde, 201);
    }
}
