<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TokenAccessController extends Controller
{
    /**
     * Handle token-based access to the business portal.
     */
    public function adminAccess(Request $request)
    {
        $token = $request->get('token');
        
        if (!$token) {
            return response()->json(['error' => 'Token required'], 400);
        }
        
        // For now, we'll implement a simple token verification
        // In production, this should verify against a database table
        // or use a more secure token system
        
        // Example: Find user by token (you would need a tokens table)
        // For demonstration, we'll check if this is a valid admin user
        
        // You could implement this by:
        // 1. Creating a personal_access_tokens table
        // 2. Storing temporary access tokens with expiration
        // 3. Verifying the token and logging in the user
        
        // For now, let's create a simple implementation
        // that redirects to the business login with a message
        
        return redirect('/business/login')
            ->with('info', 'Please login with your credentials to access the business portal.');
    }
    
    /**
     * Generate a one-time access token for a user.
     * This would be called from the admin panel to generate access links.
     */
    public function generateAccessToken(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'expires_in' => 'integer|min:1|max:1440', // minutes, max 24 hours
        ]);
        
        $user = User::findOrFail($request->user_id);
        
        // Check if user has business role
        if (!$user->hasRole('business')) {
            return response()->json(['error' => 'User does not have business access'], 403);
        }
        
        // Generate a secure token
        $token = Str::random(64);
        $expiresAt = now()->addMinutes($request->get('expires_in', 60));
        
        // In a real implementation, you would store this in a database
        // For example:
        // DB::table('business_access_tokens')->insert([
        //     'user_id' => $user->id,
        //     'token' => hash('sha256', $token),
        //     'expires_at' => $expiresAt,
        //     'created_at' => now(),
        // ]);
        
        $url = url('/business/admin-access?token=' . $token);
        
        return response()->json([
            'success' => true,
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}