<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/test-manual-login', function () {
    // Try to login as user ID 1
    $user = \App\Models\User::find(1);
    
    if ($user) {
        Auth::login($user);
        return response()->json([
            'logged_in' => true,
            'user' => $user->email,
            'redirect' => '/admin'
        ]);
    }
    
    return response()->json(['error' => 'User not found']);
});

Route::get('/test-after-login', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user()?->email,
    ]);
})->middleware('auth');