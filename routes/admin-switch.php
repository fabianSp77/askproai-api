<?php

use Illuminate\Support\Facades\Route;

// Admin Portal Auswahl
Route::get('/admin-select', function () {
    return view('admin.switch-portal');
});

// React Admin App direkt
Route::get('/admin-react-app', function () {
    return view('admin.react-app-complete');
});

// React Admin Login direkt  
Route::get('/admin-react-login', function () {
    return view('admin.login-react');
});