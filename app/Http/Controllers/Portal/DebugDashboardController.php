<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebugDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Debug info
            $debugInfo = [
                "session_id" => session()->getId(),
                "session_started" => session()->isStarted(),
                "auth_check" => Auth::guard("portal")->check(),
                "portal_user_id" => session("portal_user_id"),
                "company_context" => app()->has("current_company_id") ? app("current_company_id") : null,
                "user" => null
            ];
            
            // Try to get user
            $user = Auth::guard("portal")->user();
            
            // If no user but we have session data, try to restore
            if (!$user && session("portal_user_id")) {
                $userId = session("portal_user_id");
                $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
                
                if ($user) {
                    // Set company context
                    app()->instance("current_company_id", $user->company_id);
                    
                    // Re-authenticate
                    Auth::guard("portal")->login($user);
                    
                    $debugInfo["restored_from_session"] = true;
                }
            }
            
            if ($user) {
                $debugInfo["user"] = [
                    "id" => $user->id,
                    "email" => $user->email,
                    "name" => $user->name,
                    "company_id" => $user->company_id
                ];
                
                // Ensure company context is set
                if (!app()->has("current_company_id")) {
                    app()->instance("current_company_id", $user->company_id);
                }
                
                // Return the actual dashboard view
                return view("portal.react-dashboard", [
                    "user" => $user,
                    "debugInfo" => $debugInfo
                ]);
            } else {
                // Not authenticated
                return response()->json([
                    "error" => "Not authenticated",
                    "debug" => $debugInfo,
                    "redirect" => "/business/login"
                ], 401);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                "error" => "Dashboard error",
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString()
            ], 500);
        }
    }
}
