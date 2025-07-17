<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-widgets', function () {
    // Login as admin
    $admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
    if ($admin) {
        \Illuminate\Support\Facades\Auth::login($admin);
    }
    
    return view('test-widget-render');
})->name('test.widgets');