<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ---------- Filament-Admin-Dashboard als Root des Panels ----------
// Route::redirect('/admin', '/admin/dashboard')
//     ->name('filament.admin.redirect-to-dashboard')
//     ->middleware('web');        // schützt weiterhin durch Sessions & CSRF
