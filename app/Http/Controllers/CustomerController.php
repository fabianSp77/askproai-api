<?php

namespace App\Http\Controllers;

use App\Models\Kunde;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Login method for API authentication
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Check user
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Ungültige Zugangsdaten.'], 401);
        }

        // Generate token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    /**
     * Display all customers
     */
    public function index()
    {
        return response()->json(Kunde::all());
    }

    /**
     * Store a new customer
     */
    public function store(Request $request)
    {
        $kunde = Kunde::create($request->all());
        return response()->json($kunde, 201);
    }

    /**
     * Display a specific customer
     */
    public function show(Kunde $kunde)
    {
        return response()->json($kunde);
    }

    /**
     * Update a specific customer
     */
    public function update(Request $request, Kunde $kunde)
    {
        $kunde->update($request->all());
        return response()->json($kunde, 200);
    }

    /**
     * Delete a specific customer
     */
    public function destroy(Kunde $kunde)
    {
        $kunde->delete();
        return response()->json(null, 204);
    }
}
