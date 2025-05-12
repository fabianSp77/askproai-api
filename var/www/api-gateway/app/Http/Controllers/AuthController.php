<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return response(['message' => 'Falsche Login-Daten'], 401);
        }

        $token = $user->createToken('API Token')->accessToken;

        return response([
            'token' => $token,
            'kunde_id' => $user->kunde_id
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response(['message' => 'Erfolgreich ausgeloggt'], 200);
    }
}
