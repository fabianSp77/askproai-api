<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'portal' => 'required|in:admin,business',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check which portal user is trying to access
        if ($request->portal === 'admin') {
            // Admin portal - use User model
            $user = User::where('email', $request->email)->first();
            
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Die angegebenen Anmeldedaten sind ungültig.'],
                ]);
            }

            // Create token
            $token = $user->createToken('admin-portal')->plainTextToken;
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'admin',
                    'companyId' => $user->company_id ?? null,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                'expiresIn' => config('sanctum.expiration', 43200) * 60, // in seconds
            ]);
        } else {
            // Business portal - use PortalUser model
            $user = PortalUser::where('email', $request->email)
                ->where('active', true)
                ->first();
            
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Die angegebenen Anmeldedaten sind ungültig.'],
                ]);
            }

            // Check if company is active
            if (!$user->company->active) {
                return response()->json([
                    'message' => 'Ihr Unternehmen ist derzeit inaktiv.'
                ], 403);
            }

            // Create token
            $token = $user->createToken('business-portal')->plainTextToken;
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'companyId' => $user->company_id,
                    'branchIds' => $user->branches->pluck('id'),
                    'permissions' => $user->permissions,
                ],
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                'expiresIn' => config('sanctum.expiration', 43200) * 60,
            ]);
        }
    }

    /**
     * Register new business portal user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:portal_users',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create company
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'active' => false, // Requires admin activation
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create portal user
        $user = PortalUser::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'owner',
            'active' => true,
        ]);

        // Send verification email
        $user->sendEmailVerificationNotification();

        // Create token
        $token = $user->createToken('business-portal')->plainTextToken;

        return response()->json([
            'message' => 'Registrierung erfolgreich. Bitte überprüfen Sie Ihre E-Mail.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'companyId' => $user->company_id,
                'emailVerified' => false,
            ],
            'accessToken' => $token,
            'tokenType' => 'Bearer',
        ], 201);
    }

    /**
     * Logout user (Revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Erfolgreich abgemeldet'
        ]);
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
        $token = $user->createToken($user instanceof User ? 'admin-portal' : 'business-portal')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => config('sanctum.expiration', 43200) * 60,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        if ($user instanceof User) {
            // Admin user
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'admin',
                'companyId' => $user->company_id ?? null,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'emailVerified' => !is_null($user->email_verified_at),
                'createdAt' => $user->created_at->toIso8601String(),
            ]);
        } else {
            // Portal user
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'companyId' => $user->company_id,
                'branchIds' => $user->branches->pluck('id'),
                'permissions' => $user->permissions,
                'emailVerified' => !is_null($user->email_verified_at),
                'createdAt' => $user->created_at->toIso8601String(),
            ]);
        }
    }

    /**
     * Update user profile
     */
    public function updateUser(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:' . ($user instanceof User ? 'users' : 'portal_users') . ',email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        
        if ($request->has('email') && $user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            // Send new verification email
            $user->sendEmailVerificationNotification();
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return response()->json([
            'message' => 'Profil erfolgreich aktualisiert',
            'user' => $this->user($request)->getData(),
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'portal' => 'required|in:admin,business',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement password reset logic
        // For now, just return success
        
        return response()->json([
            'message' => 'Wenn die E-Mail-Adresse existiert, wurde ein Link zum Zurücksetzen des Passworts gesendet.'
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement password reset logic
        
        return response()->json([
            'message' => 'Passwort erfolgreich zurückgesetzt'
        ]);
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement email verification logic
        
        return response()->json([
            'message' => 'E-Mail erfolgreich verifiziert'
        ]);
    }
}