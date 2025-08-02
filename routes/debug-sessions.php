<?php

use Illuminate\Support\Facades\Route;

// Debug route to check session status (remove in production)
Route::get('/debug/sessions', function() {
    return response()->json([
        'timestamp' => now()->toIso8601String(),
        'admin' => [
            'logged_in' => auth()->guard('web')->check(),
            'user' => auth()->guard('web')->user()?->email,
            'session_id' => session()->getId(),
            'cookie_name' => 'askproai_admin_session',
            'cookie_value' => $_COOKIE['askproai_admin_session'] ?? null,
        ],
        'portal' => [
            'logged_in' => auth()->guard('portal')->check(),
            'user' => auth()->guard('portal')->user()?->email,
            'session_id' => session()->getId(),
            'cookie_name' => 'askproai_portal_session',
            'cookie_value' => $_COOKIE['askproai_portal_session'] ?? null,
        ],
        'all_cookies' => array_keys($_COOKIE),
        'request_info' => [
            'url' => request()->url(),
            'is_admin' => request()->is('admin/*'),
            'is_portal' => request()->is('business/*'),
        ],
        'session_config' => [
            'cookie' => config('session.cookie'),
            'lifetime' => config('session.lifetime'),
            'path' => config('session.path'),
            'domain' => config('session.domain'),
        ]
    ]);
})->middleware('web');