<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

class WorkingLoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user WITHOUT global scopes
        $user = PortalUser::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 403);
        }

        // Set company context BEFORE login
        app()->instance('current_company_id', $user->company_id);

        // Login user with "remember me" option
        Auth::guard('portal')->login($user, true);

        // Regenerate session
        $request->session()->regenerate();

        // Store user data in session
        $request->session()->put('portal_user_id', $user->id);
        $request->session()->put('portal_company_id', $user->company_id);
        $request->session()->put('portal_authenticated', true);

        // Also store in PHP session as backup
        $_SESSION['portal_user_id'] = $user->id;
        $_SESSION['portal_company_id'] = $user->company_id;

        // Force save the session
        $request->session()->save();

        // Create a manual session cookie as backup
        $cookie = cookie(
            'portal_session_backup',
            encrypt([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'authenticated' => true,
            ]),
            60 * 24 * 7, // 7 days
            '/',
            null,
            false, // not secure for local dev
            true  // httponly
        );

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'company_id' => $user->company_id,
            ],
            'redirect' => '/business/dashboard',
            'session_id' => $request->session()->getId(),
            'php_session_id' => session_id(),
        ])->cookie($cookie);
    }
}
