<?php
use Illuminate\Support\Facades\Route;

// TEMPORARY BYPASS - REMOVE IN PRODUCTION!
Route::get('/admin-bypass/{path?}', function ($path = '') {
    // Force login
    $user = \App\Models\User::where('email', 'demo@askproai.de')->first();
    if ($user) {
        Auth::login($user);
        session()->regenerate();
    }
    
    // Proxy to admin routes
    $adminPath = '/admin' . ($path ? '/' . $path : '');
    return redirect($adminPath);
})->where('path', '.*');