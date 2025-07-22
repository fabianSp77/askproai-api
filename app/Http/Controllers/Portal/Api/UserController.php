<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function user(Request $request)
    {
        // Quick response to prevent timeout
        $user = Auth::guard('portal')->user();
        
        if (\!$user) {
            // Try to get from session
            $userId = session('portal_user_id');
            if ($userId) {
                $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
                if ($user) {
                    Auth::guard('portal')->login($user);
                }
            }
        }
        
        if (\!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => $user->role ?? 'user'
        ]);
    }
}
