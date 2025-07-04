<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    /**
     * Create a new API token for the authenticated user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Nicht authentifiziert'
            ], 401);
        }

        // For now, create a simple token (in production, use Laravel Sanctum)
        $token = Str::random(80);
        
        // Store token in session for this demo
        session(['api_token' => $token]);
        session(['api_token_user_id' => $user->id]);

        return response()->json([
            'token' => $token,
            'type' => 'Bearer',
            'expires_in' => null // No expiration for demo
        ]);
    }
}