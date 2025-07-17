<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestCallsController extends Controller
{
    public function test(Request $request)
    {
        return response()->json([
            'test' => 'success',
            'path' => $request->path(),
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => [
                'accept' => $request->header('Accept'),
                'x-requested-with' => $request->header('X-Requested-With'),
            ],
            'auth' => [
                'portal' => auth()->guard('portal')->check(),
                'portal_user' => auth()->guard('portal')->user() ? auth()->guard('portal')->user()->email : null,
                'web' => auth()->guard('web')->check(),
                'web_user' => auth()->guard('web')->user() ? auth()->guard('web')->user()->email : null,
            ],
            'session' => [
                'id' => session()->getId(),
                'portal_user_id' => session('portal_user_id'),
                'is_admin_viewing' => session('is_admin_viewing'),
            ]
        ]);
    }
}