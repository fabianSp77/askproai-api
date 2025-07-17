<?php

namespace App\Http\Controllers\Portal\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionDebugController extends BaseApiController
{
    public function debug(Request $request)
    {
        $sessionId = session()->getId();
        $cookieSessionId = $request->cookie(config('session.cookie'));
        
        return response()->json([
            'session_id' => $sessionId,
            'cookie_session_id' => $cookieSessionId,
            'session_data' => session()->all(),
            'guards' => [
                'portal' => Auth::guard('portal')->check(),
                'web' => Auth::guard('web')->check(),
            ],
            'user' => [
                'portal' => Auth::guard('portal')->user() ? Auth::guard('portal')->user()->id : null,
                'web' => Auth::guard('web')->user() ? Auth::guard('web')->user()->id : null,
            ],
            'headers' => [
                'cookie' => $request->header('Cookie'),
                'csrf' => $request->header('X-CSRF-TOKEN'),
            ],
            'company' => $this->getCompany() ? $this->getCompany()->id : null,
        ]);
    }
}
