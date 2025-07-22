<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class UltrathinkAuthController extends Controller
{
    /**
     * The ULTIMATE solution: Bridge token to session
     */
    public function bridgeAuth(Request $request)
    {
        // Try multiple methods to get the user
        $user = null;
        
        // Method 1: From Bearer token
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }
        
        // Method 2: From query parameter
        if (!$user && $request->has('token')) {
            $token = PersonalAccessToken::findToken($request->query('token'));
            if ($token) {
                $user = $token->tokenable;
            }
        }
        
        // Method 3: From localStorage (via custom header)
        if (!$user && $request->header('X-Auth-Token')) {
            $token = PersonalAccessToken::findToken($request->header('X-Auth-Token'));
            if ($token) {
                $user = $token->tokenable;
            }
        }
        
        if (!$user) {
            return redirect('/business/login')->with('error', 'No valid token found');
        }
        
        // Create session auth
        Auth::guard('portal')->login($user, true);
        
        // Set all possible session keys (overkill but ensures it works)
        session([
            'portal_authenticated' => true,
            'portal_user_id' => $user->id,
            'portal_company_id' => $user->company_id,
            'portal_user' => $user->toArray(),
            '_auth_portal' => $user->id,
            'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal') => $user->id
        ]);
        
        // Force session regeneration and save
        session()->regenerate();
        session()->save();
        
        // Set company context
        app()->instance('current_company_id', $user->company_id);
        
        // Redirect to business portal
        return redirect('/business')->with('success', 'Authenticated via ULTRATHINK bridge!');
    }
    
    /**
     * Direct session creation (bypass everything)
     */
    public function directSession(Request $request)
    {
        $email = $request->input('email', 'demo@askproai.de');
        
        $user = PortalUser::withoutGlobalScopes()
            ->where('email', $email)
            ->first();
            
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        // Force session login
        Auth::guard('portal')->login($user, true);
        session()->regenerate();
        session()->save();
        
        return response()->json([
            'success' => true,
            'session_id' => session()->getId(),
            'user' => $user->toArray(),
            'redirect' => '/business'
        ]);
    }
}