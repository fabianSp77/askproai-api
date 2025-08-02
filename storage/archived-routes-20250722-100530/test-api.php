<?php

use Illuminate\Support\Facades\Route;

// Test API endpoints for the final test suite
Route::prefix('api/test')->group(function () {
    
    // Session configuration test
    Route::get('/session-config', function () {
        return response()->json([
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'domain' => config('session.domain'),
            'path' => config('session.path'),
            'secure' => config('session.secure'),
            'http_only' => config('session.http_only'),
            'same_site' => config('session.same_site'),
            'cookie_name' => config('session.cookie'),
        ]);
    });
    
    // CSRF token endpoint
    Route::get('/csrf-token', function () {
        return response()->json([
            'token' => csrf_token()
        ]);
    });
    
});