<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginDebugController extends Controller
{
    public function debug(Request $request)
    {
        $results = [];
        
        // 1. Check if demo user exists
        $user = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('email', 'demo@askproai.de')
            ->first();
            
        $results['user_exists'] = $user ? 'YES' : 'NO';
        if ($user) {
            $results['user_id'] = $user->id;
            $results['user_active'] = $user->is_active ? 'YES' : 'NO';
            $results['company_id'] = $user->company_id;
            $results['password_check'] = Hash::check('password', $user->password) ? 'VALID' : 'INVALID';
        }
        
        // 2. Check current auth status
        $results['portal_auth_check'] = Auth::guard('portal')->check() ? 'YES' : 'NO';
        $results['portal_user_id'] = Auth::guard('portal')->id();
        
        // 3. Check session
        $results['session_id'] = session()->getId();
        $results['session_name'] = session()->getName();
        $results['session_driver'] = config('session.driver');
        $results['session_domain'] = config('session.domain');
        $results['session_cookie'] = config('session.cookie');
        
        // 4. Check middleware
        $results['middleware_groups'] = array_keys(app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups());
        
        // 5. Test login
        if ($request->get('test_login') === '1' && $user) {
            Auth::guard('portal')->login($user);
            $results['login_attempt'] = 'EXECUTED';
            $results['after_login_check'] = Auth::guard('portal')->check() ? 'YES' : 'NO';
            $results['after_login_id'] = Auth::guard('portal')->id();
        }
        
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
}