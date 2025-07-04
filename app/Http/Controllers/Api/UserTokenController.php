<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTokenController extends Controller
{
    /**
     * Create a new API token
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array'
        ]);

        $user = Auth::user();
        
        // Delete existing token with same name
        $user->tokens()->where('name', $request->name)->delete();
        
        // Create new token
        $token = $user->createToken(
            $request->name,
            $request->abilities ?? ['*']
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities
        ]);
    }

    /**
     * List user's tokens
     */
    public function index()
    {
        $tokens = Auth::user()->tokens()->get();
        
        return response()->json([
            'tokens' => $tokens->map(fn($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at
            ])
        ]);
    }

    /**
     * Delete a token
     */
    public function destroy($tokenId)
    {
        Auth::user()->tokens()->where('id', $tokenId)->delete();
        
        return response()->json(['message' => 'Token deleted']);
    }
}