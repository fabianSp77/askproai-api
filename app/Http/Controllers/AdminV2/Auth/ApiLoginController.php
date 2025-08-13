<?php

namespace App\Http\Controllers\AdminV2\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiLoginController extends Controller
{
    protected string $guard = 'adminv2';

    /**
     * Handle JSON login request
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        
        if (Auth::guard($this->guard)->attempt($credentials, true)) {
            $user = Auth::guard($this->guard)->user();
            
            // Generate API token for session
            $token = Str::random(60);
            
            // Store token in session
            $request->session()->regenerate();
            $request->session()->put('adminv2_api_token', $token);
            $request->session()->put('adminv2_user_id', $user->id);
            $request->session()->put('adminv2_logged_in', true);
            $request->session()->save();
            
            Log::info('AdminV2 API Login Success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'session_id' => session()->getId()
            ]);
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'redirect_url' => '/admin-v2/dashboard',
                'session_id' => session()->getId()
            ]);
        }
        
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }
    
    /**
     * Check authentication status
     */
    public function check(Request $request): JsonResponse
    {
        $user = Auth::guard($this->guard)->user();
        
        if ($user) {
            return response()->json([
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);
        }
        
        return response()->json([
            'authenticated' => false
        ], 401);
    }
    
    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard($this->guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}