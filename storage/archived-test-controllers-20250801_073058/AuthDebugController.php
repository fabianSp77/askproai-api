<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthDebugController extends Controller
{
    public function debug(Request $request)
    {
        return response()->json([
            'guards' => [
                'web' => [
                    'check' => auth()->guard('web')->check(),
                    'user' => auth()->guard('web')->user() ? auth()->guard('web')->user()->email : null,
                ],
                'portal' => [
                    'check' => auth()->guard('portal')->check(),
                    'user' => auth()->guard('portal')->user() ? auth()->guard('portal')->user()->email : null,
                ],
            ],
            'session' => [
                'id' => session()->getId(),
                'has_portal_user' => session()->has('portal_user_id'),
                'portal_user_id' => session()->get('portal_user_id'),
            ],
            'request' => [
                'user_via_request' => $request->user('portal') ? $request->user('portal')->email : null,
                'headers' => [
                    'cookie' => $request->header('cookie') ? 'present' : 'missing',
                    'x-csrf-token' => $request->header('x-csrf-token') ? 'present' : 'missing',
                ],
            ],
        ]);
    }
}