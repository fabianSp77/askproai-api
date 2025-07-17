<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestAuthController extends BaseApiController
{
    public function test(Request $request)
    {
        // Get all session info
        $sessionId = session()->getId();
        $sessionData = session()->all();
        
        // Check all guards
        $portalCheck = Auth::guard('portal')->check();
        $webCheck = Auth::guard('web')->check();
        
        // Get users if authenticated
        $portalUser = Auth::guard('portal')->user();
        $webUser = Auth::guard('web')->user();
        
        // Check company context
        $companyId = app()->has('current_company_id') ? app('current_company_id') : null;
        
        // Check session in database
        $dbSession = DB::table('sessions')->where('id', $sessionId)->first();
        
        return response()->json([
            'success' => true,
            'session' => [
                'id' => $sessionId,
                'has_data' => !empty($sessionData),
                'keys' => array_keys($sessionData),
            ],
            'auth' => [
                'portal' => [
                    'authenticated' => $portalCheck,
                    'user_id' => $portalUser ? $portalUser->id : null,
                    'user_email' => $portalUser ? $portalUser->email : null,
                ],
                'web' => [
                    'authenticated' => $webCheck,
                    'user_id' => $webUser ? $webUser->id : null,
                    'user_email' => $webUser ? $webUser->email : null,
                    'is_admin' => $webUser && method_exists($webUser, 'hasRole') ? $webUser->hasRole('Super Admin') : false,
                ],
            ],
            'context' => [
                'company_id' => $companyId,
                'from_base_controller' => $this->getCompany() ? $this->getCompany()->id : null,
            ],
            'session_data' => [
                'is_admin_viewing' => $sessionData['is_admin_viewing'] ?? false,
                'admin_impersonation' => $sessionData['admin_impersonation'] ?? null,
            ],
            'db_session' => $dbSession ? [
                'exists' => true,
                'user_id' => $dbSession->user_id,
                'last_activity' => date('Y-m-d H:i:s', $dbSession->last_activity),
            ] : ['exists' => false],
            'request' => [
                'cookies' => $request->cookies->all(),
                'has_session_cookie' => $request->hasCookie('askproai_session'),
            ],
        ]);
    }
}