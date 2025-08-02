<?php

use Illuminate\Support\Facades\Route;

// Test route for session functionality
Route::get('/api/test-session', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user() ? [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'company_id' => auth()->user()->company_id
        ] : null,
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'csrf_token' => csrf_token(),
        'guards' => [
            'web' => auth()->guard('web')->check(),
            'portal' => auth()->guard('portal')->check(),
        ]
    ]);
});