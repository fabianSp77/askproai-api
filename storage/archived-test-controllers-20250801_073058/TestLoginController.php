<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TestLoginController extends Controller
{
    public function testLogin(Request $request)
    {
        try {
            // Get input
            $email = $request->input('email', 'fabianspitzer@icloud.com');
            $password = $request->input('password', 'demo123');
            
            // Debug info
            $debug = [
                'received_email' => $email,
                'received_password' => $password,
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
            ];
            
            // Find user without global scopes
            $user = PortalUser::withoutGlobalScopes()->where('email', $email)->first();
            $debug['user_found'] = $user ? true : false;
            $debug['used_without_scopes'] = true;
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'debug' => $debug
                ], 404);
            }
            
            $debug['user_id'] = $user->id;
            $debug['user_active'] = $user->is_active;
            $debug['company_id'] = $user->company_id;
            
            // Check password
            $passwordCorrect = Hash::check($password, $user->password);
            $debug['password_correct'] = $passwordCorrect;
            
            if (!$passwordCorrect) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password',
                    'debug' => $debug
                ], 401);
            }
            
            // Check if active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not active',
                    'debug' => $debug
                ], 403);
            }
            
            // Login
            Auth::guard('portal')->login($user);
            
            // Set session
            session(['portal_user_id' => $user->id]);
            session(['portal_login' => $user->id]);
            
            $debug['login_success'] = Auth::guard('portal')->check();
            $debug['session_id'] = session()->getId();
            $debug['portal_user_id_in_session'] = session('portal_user_id');
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('business.dashboard'),
                'debug' => $debug
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}