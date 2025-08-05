<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class NoCSRFLoginController extends Controller
{
    public function __construct()
    {
        // No middleware at all
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Die angegebenen Anmeldedaten sind nicht korrekt.'],
            ]);
        }

        // Create a Sanctum token
        $token = $user->createToken('command-intelligence')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Erfolgreich angemeldet',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
}