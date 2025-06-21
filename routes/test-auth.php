<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver(),
        'session' => session()->all(),
    ]);
});

Route::get('/test-dashboard', function () {
    return response()->json([
        'route_exists' => true,
        'route_url' => '/admin',
    ]);
});