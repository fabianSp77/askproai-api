<?php

use Illuminate\Support\Facades\Route;

// Admin session test
Route::get('/admin/session-test', function() {
    return response()->json([
        'guard' => 'web',
        'logged_in' => auth()->guard('web')->check(),
        'user' => auth()->guard('web')->user()?->email,
        'session_id' => session()->getId(),
        'session_config' => [
            'cookie' => config('session.cookie'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'lifetime' => config('session.lifetime'),
            'driver' => config('session.driver'),
            'files' => config('session.files'),
        ],
        'cookies' => $_COOKIE,
        'expected_cookie' => 'askproai_admin_session',
        'actual_cookie' => $_COOKIE['askproai_admin_session'] ?? 'NOT SET',
    ]);
})->middleware(['admin', 'auth']);

// Portal session test  
Route::get('/business/session-test', function() {
    return response()->json([
        'guard' => 'portal',
        'logged_in' => auth()->guard('portal')->check(),
        'user' => auth()->guard('portal')->user()?->email,
        'session_id' => session()->getId(),
        'session_config' => [
            'cookie' => config('session.cookie'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'lifetime' => config('session.lifetime'),
            'driver' => config('session.driver'),
            'files' => config('session.files'),
        ],
        'cookies' => $_COOKIE,
        'expected_cookie' => 'askproai_portal_session',
        'actual_cookie' => $_COOKIE['askproai_portal_session'] ?? 'NOT SET',
    ]);
})->middleware(['business-portal', 'portal.auth']);