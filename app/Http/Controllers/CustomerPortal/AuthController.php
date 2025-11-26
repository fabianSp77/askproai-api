<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Exceptions\UserManagementException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPortal\AcceptInvitationRequest;
use App\Http\Resources\CustomerPortal\InvitationResource;
use App\Http\Resources\CustomerPortal\UserResource;
use App\Models\UserInvitation;
use App\Services\CustomerPortal\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Customer Portal Authentication Controller
 *
 * AUTHENTICATION FLOW:
 * 1. User receives email with invitation link containing token
 * 2. Frontend calls validateToken() to check if token is valid
 * 3. If valid, user fills registration form
 * 4. Frontend calls acceptInvitation() to create user account
 * 5. User receives Sanctum token for subsequent API calls
 * 6. Token stored in localStorage on client-side
 *
 * SECURITY:
 * - Token-based invitation (no traditional login)
 * - Tokens expire after 72 hours (configurable)
 * - Single-use tokens (cannot reuse after acceptance)
 * - Multi-tenant isolation via company_id
 * - Email verification automatic via invitation
 */
class AuthController extends Controller
{
    public function __construct(
        private UserManagementService $userManagement
    ) {}

    /**
     * Validate invitation token
     *
     * @param string $token Invitation token from email link
     * @return JsonResponse
     */
    public function validateToken(string $token): JsonResponse
    {
        try {
            // Find invitation by token
            $invitation = UserInvitation::where('token', $token)->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diese Einladung wurde nicht gefunden.',
                    'error_code' => 'TOKEN_NOT_FOUND',
                ], 404);
            }

            // Check if already accepted
            if ($invitation->accepted_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diese Einladung wurde bereits verwendet.',
                    'error_code' => 'TOKEN_ALREADY_USED',
                ], 422);
            }

            // Check if expired
            if ($invitation->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diese Einladung ist abgelaufen. Bitte fordern Sie eine neue an.',
                    'error_code' => 'TOKEN_EXPIRED',
                    'expired_at' => $invitation->expires_at->toIso8601String(),
                ], 422);
            }

            // Token is valid - return in format expected by frontend
            return response()->json([
                'success' => true,
                'data' => new InvitationResource($invitation),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Die Einladung konnte nicht überprüft werden. Bitte versuchen Sie es später erneut.',
                'error_code' => 'VALIDATION_ERROR',
            ], 500);
        }
    }

    /**
     * Accept invitation and create user account
     *
     * @param AcceptInvitationRequest $request
     * @param string $token
     * @return JsonResponse
     */
    public function acceptInvitation(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        try {
            // Accept invitation and create user
            $user = $this->userManagement->acceptInvitation(
                token: $token,
                userData: $request->validated()
            );

            // Generate Sanctum token for authentication
            $sanctumToken = $user->createToken(
                name: 'customer-portal',
                abilities: ['customer-portal:*']
            )->plainTextToken;

            // Log successful registration
            Log::info('User registered via invitation', [
                'user_id' => $user->id,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'token' => substr($token, 0, 8) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully. Welcome!',
                'user' => new UserResource($user),
                'access_token' => $sanctumToken,
                'token_type' => 'Bearer',
            ], 201);

        } catch (UserManagementException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->getErrorCode($e),
            ], $e->getCode());

        } catch (\Exception $e) {
            Log::error('Invitation acceptance failed', [
                'token' => substr($token, 0, 8) . '...',
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to create account. Please try again.',
                'error_code' => 'REGISTRATION_ERROR',
            ], 500);
        }
    }

    /**
     * Get user-friendly error code from exception
     */
    private function getErrorCode(\Exception $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'expired')) {
            return 'TOKEN_EXPIRED';
        }

        if (str_contains($message, 'already been used')) {
            return 'TOKEN_ALREADY_USED';
        }

        if (str_contains($message, 'does not match')) {
            return 'EMAIL_MISMATCH';
        }

        return 'VALIDATION_ERROR';
    }
}
