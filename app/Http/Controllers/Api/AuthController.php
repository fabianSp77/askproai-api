<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\CustomerAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    /**
     * Handle admin login and return token
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has 2FA enabled
        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id,
            ], 200);
        }

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Handle business portal login and return token
     */
    public function portalLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = PortalUser::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Check if 2FA is required
        if ($user->requires2FA() && $user->two_factor_secret) {
            return response()->json([
                'requires_2fa' => true,
                'user_id' => $user->id,
            ], 200);
        }

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        // Record login
        $user->recordLogin($request->ip());

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'role' => $user->role,
                'permissions' => $user->permissions,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Handle customer login and return token
     */
    public function customerLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $auth = CustomerAuth::where('email', $request->email)->first();

        if (! $auth || ! Hash::check($request->password, $auth->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token
        $token = $auth->createToken($request->device_name)->plainTextToken;

        // Record login
        $auth->recordLogin();

        return response()->json([
            'user' => [
                'id' => $auth->id,
                'name' => $auth->name,
                'email' => $auth->email,
                'customer_id' => $auth->customer_id,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Get current user for any guard
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Return appropriate user data based on guard
        if ($user instanceof User) {
            return response()->json([
                'guard' => 'web',
                'user' => $user,
            ]);
        } elseif ($user instanceof PortalUser) {
            return response()->json([
                'guard' => 'portal',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'role' => $user->role,
                    'permissions' => $user->permissions,
                ],
            ]);
        } elseif ($user instanceof CustomerAuth) {
            return response()->json([
                'guard' => 'customer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'customer_id' => $user->customer_id,
                ],
            ]);
        }

        return response()->json(['message' => 'Unknown user type'], 500);
    }

    /**
     * Logout and revoke token
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ], 200);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();
        
        // Create new token
        $token = $user->createToken($request->device_name ?? 'web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Verify 2FA code
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'code' => 'required',
            'guard' => 'required|in:web,portal,customer',
            'device_name' => 'required',
        ]);

        // Get user based on guard
        $user = null;
        switch ($request->guard) {
            case 'web':
                $user = User::find($request->user_id);
                break;
            case 'portal':
                $user = PortalUser::withoutGlobalScopes()->find($request->user_id);
                break;
            case 'customer':
                $user = CustomerAuth::find($request->user_id);
                break;
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['Invalid user.'],
            ]);
        }

        // Verify 2FA code
        $provider = app(TwoFactorAuthenticationProvider::class);
        
        if (!$provider->verify(decrypt($user->two_factor_secret), $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two factor authentication code was invalid.'],
            ]);
        }

        // Create token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
}