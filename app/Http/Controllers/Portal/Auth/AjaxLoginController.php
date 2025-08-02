<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Services\Portal\PortalAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AjaxLoginController extends Controller
{
    protected $authService;

    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        // Rate limiting
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'code' => 'RATE_LIMITED',
            ], 429);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'remember' => 'boolean',
        ]);

        if ($validator->fails()) {
            RateLimiter::hit($key);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        // Attempt authentication
        $result = $this->authService->authenticate(
            $request->email,
            $request->password,
            $request->boolean('remember', false)
        );

        if (! $result['success']) {
            RateLimiter::hit($key);

            return response()->json($result, 401);
        }

        // Clear rate limiter on success
        RateLimiter::clear($key);

        // Return success with session info
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'session_token' => $result['session_token'],
                'csrf_token' => csrf_token(),
            ],
            'redirect' => $result['redirect'],
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $this->authService->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => '/business/login',
        ]);
    }

    /**
     * Check authentication status.
     */
    public function check(Request $request)
    {
        if (! $this->authService->check()) {
            return response()->json([
                'authenticated' => false,
            ]);
        }

        $user = $this->authService->user();

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'role' => $user->role ?? 'user',
                'permissions' => session('portal_permissions', []),
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Refresh session.
     */
    public function refresh(Request $request)
    {
        $userData = $this->authService->refreshSession();

        if (! $userData) {
            return response()->json([
                'success' => false,
                'message' => 'Session refresh failed',
                'code' => 'SESSION_EXPIRED',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $userData,
            'csrf_token' => csrf_token(),
        ]);
    }
}
