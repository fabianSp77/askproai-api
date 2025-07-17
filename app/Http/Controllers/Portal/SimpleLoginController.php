<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class SimpleLoginController extends Controller
{
    public function simpleLogin(Request $request)
    {
        try {
            // Start session if not started
            if (!Session::isStarted()) {
                Session::start();
            }
            
            $email = 'fabianspitzer@icloud.com';
            $password = 'demo123';
            
            // Find user without scopes
            $user = PortalUser::withoutGlobalScopes()->where('email', $email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'debug' => [
                        'email' => $email,
                        'query' => 'PortalUser::withoutGlobalScopes()->where(email, ' . $email . ')'
                    ]
                ]);
            }
            
            // Check password
            if (!Hash::check($password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ]);
            }
            
            // Check if active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not active'
                ]);
            }
            
            // Login the user
            Auth::guard('portal')->login($user, true);
            
            // Force session save with additional keys for compatibility
            Session::put('portal_user_id', $user->id);
            Session::put('portal_login', $user->id);
            Session::put('portal_user_email', $user->email);
            
            // Manually set the Laravel auth session key if needed
            $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
            if (!Session::has($portalSessionKey)) {
                Session::put($portalSessionKey, $user->id);
            }
            
            Session::save();
            
            // Set company context
            app()->instance('current_company_id', $user->company_id);
            
            // Get branch
            $branch = \App\Models\Branch::withoutGlobalScopes()
                ->where('company_id', $user->company_id)
                ->first();
                
            if ($branch) {
                Session::put('current_branch_id', $branch->id);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'company_id' => $user->company_id
                ],
                'session' => [
                    'id' => Session::getId(),
                    'portal_user_id' => Session::get('portal_user_id'),
                    'portal_login' => Session::get('portal_login'),
                    'current_branch_id' => Session::get('current_branch_id'),
                    'auth_check' => Auth::guard('portal')->check()
                ],
                'redirect' => route('business.dashboard')
            ])->withCookie(cookie('portal_logged_in', '1', 120));
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}