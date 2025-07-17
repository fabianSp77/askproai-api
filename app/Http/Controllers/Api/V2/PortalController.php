<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\PortalUser;

class PortalController extends Controller
{
    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                "email" => "required|email",
                "password" => "required"
            ]);

            // Log the attempt
            Log::info("Portal API login attempt", ["email" => $credentials["email"]]);

            // Direct authentication check
            $user = PortalUser::where("email", $credentials["email"])->first();
            
            if (!$user || !password_verify($credentials["password"], $user->password)) {
                Log::warning("Portal API login failed - invalid credentials", ["email" => $credentials["email"]]);
                return response()->json([
                    "success" => false,
                    "message" => "Invalid credentials"
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                Log::warning("Portal API login failed - user inactive", ["email" => $credentials["email"]]);
                return response()->json([
                    "success" => false,
                    "message" => "Account is inactive"
                ], 403);
            }

            // Create token
            $token = $user->createToken("portal-api-token")->plainTextToken;
            
            Log::info("Portal API login successful", ["user_id" => $user->id]);
            
            return response()->json([
                "success" => true,
                "token" => $token,
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "email" => $user->email,
                    "role" => $user->role
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Portal API login exception", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);
            
            return response()->json([
                "success" => false,
                "message" => "Login failed",
                "error" => config("app.debug") ? $e->getMessage() : "Internal server error"
            ], 500);
        }
    }

    public function dashboard(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => [
                "user" => $request->user(),
                "stats" => [
                    "appointments" => 0,
                    "calls" => 0
                ]
            ]
        ]);
    }

    public function appointments(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
    }

    public function calls(Request $request)
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
    }
}