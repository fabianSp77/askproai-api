<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Add this to routes/business-portal.php in the auth routes section
Route::get("/auth/check-fixed", function () {
    try {
        $user = Auth::guard("portal")->user();
        
        // Try to restore from session if not authenticated
        if (!$user && session("portal_user_id")) {
            $userId = session("portal_user_id");
            $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
            if ($user) {
                app()->instance("current_company_id", $user->company_id);
                Auth::guard("portal")->login($user);
            }
        }
        
        return response()->json([
            "authenticated" => (bool) $user,
            "user" => $user ? [
                "id" => $user->id,
                "email" => $user->email,
                "name" => $user->name,
                "company_id" => $user->company_id
            ] : null,
            "session" => [
                "id" => session()->getId(),
                "portal_user_id" => session("portal_user_id"),
                "portal_company_id" => session("portal_company_id"),
                "all" => session()->all()
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ], 500);
    }
})->name("business.auth.check-fixed");
