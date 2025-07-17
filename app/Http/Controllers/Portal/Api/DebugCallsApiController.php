<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Call;

class DebugCallsApiController extends Controller
{
    public function debug(Request $request)
    {
        $portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
        
        return response()->json([
            'auth_check' => [
                'portal_authenticated' => Auth::guard('portal')->check(),
                'portal_user' => Auth::guard('portal')->user() ? [
                    'id' => Auth::guard('portal')->user()->id,
                    'email' => Auth::guard('portal')->user()->email,
                    'company_id' => Auth::guard('portal')->user()->company_id,
                ] : null,
                'web_authenticated' => Auth::guard('web')->check(),
                'web_user' => Auth::guard('web')->user() ? Auth::guard('web')->user()->email : null,
            ],
            'session_data' => [
                'session_id' => session()->getId(),
                'portal_session_key' => session($portalSessionKey),
                'portal_user_id' => session('portal_user_id'),
                'is_admin_viewing' => session('is_admin_viewing'),
                'admin_impersonation' => session('admin_impersonation'),
            ],
            'app_context' => [
                'current_company_id' => app()->has('current_company_id') ? app('current_company_id') : null,
                'current_branch_id' => session('current_branch_id'),
            ],
            'calls_test' => $this->testCallsAccess(),
            'request_info' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => [
                    'x-csrf-token' => $request->header('X-CSRF-TOKEN'),
                    'x-requested-with' => $request->header('X-Requested-With'),
                ],
                'cookies' => $request->cookies->all() ? array_keys($request->cookies->all()) : [],
            ]
        ]);
    }
    
    private function testCallsAccess()
    {
        try {
            // Try to get calls directly
            $user = Auth::guard('portal')->user();
            if ($user) {
                $calls = Call::withoutGlobalScopes()
                    ->where('company_id', $user->company_id)
                    ->latest()
                    ->limit(3)
                    ->get(['id', 'from_number', 'status', 'created_at']);
                    
                return [
                    'success' => true,
                    'count' => $calls->count(),
                    'calls' => $calls->toArray(),
                ];
            }
            
            return [
                'success' => false,
                'message' => 'No authenticated user',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}