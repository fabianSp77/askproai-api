<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionDebugController extends Controller
{
    public function debug(Request $request)
    {
        $sessionId = session()->getId();
        $portalSessionCookie = $request->cookie('askproai_portal_session');
        
        // Try to read session file directly
        $sessionFileData = null;
        if ($portalSessionCookie) {
            $sessionFile = storage_path('framework/sessions/portal/' . $portalSessionCookie);
            if (file_exists($sessionFile)) {
                try {
                    $sessionFileData = unserialize(file_get_contents($sessionFile));
                } catch (\Exception $e) {
                    $sessionFileData = ['error' => $e->getMessage()];
                }
            }
        }
        
        return response()->json([
            'session' => [
                'id' => $sessionId,
                'cookie_id' => $portalSessionCookie,
                'match' => $sessionId === $portalSessionCookie,
                'all_data' => session()->all(),
                'is_started' => session()->isStarted(),
            ],
            'auth' => [
                'portal_check' => Auth::guard('portal')->check(),
                'portal_user' => Auth::guard('portal')->user() ? [
                    'id' => Auth::guard('portal')->user()->id,
                    'email' => Auth::guard('portal')->user()->email,
                ] : null,
                'web_check' => Auth::guard('web')->check(),
            ],
            'config' => [
                'session_cookie' => config('session.cookie'),
                'session_driver' => config('session.driver'),
                'session_path' => config('session.path'),
                'session_files' => config('session.files'),
            ],
            'session_file' => [
                'path' => $sessionFile ?? null,
                'exists' => isset($sessionFile) && file_exists($sessionFile),
                'data' => $sessionFileData,
            ],
            'request' => [
                'cookies' => array_keys($request->cookies->all()),
                'headers' => [
                    'X-Requested-With' => $request->header('X-Requested-With'),
                    'Accept' => $request->header('Accept'),
                ],
            ],
        ]);
    }
}