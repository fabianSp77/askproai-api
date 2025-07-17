<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthCheckController extends Controller
{
    public function check()
    {
        $portalUser = Auth::guard('portal')->user();
        $webUser = Auth::guard('web')->user();
        
        return response()->json([
            'authenticated' => $portalUser !== null || $webUser !== null,
            'portal' => [
                'authenticated' => $portalUser !== null,
                'user' => $portalUser ? [
                    'id' => $portalUser->id,
                    'email' => $portalUser->email,
                    'name' => $portalUser->name,
                    'company_id' => $portalUser->company_id,
                ] : null
            ],
            'web' => [
                'authenticated' => $webUser !== null,
                'user' => $webUser ? [
                    'id' => $webUser->id,
                    'email' => $webUser->email,
                    'name' => $webUser->name,
                ] : null
            ],
            'csrf_token' => csrf_token(),
            'session_id' => session()->getId(),
        ]);
    }
}