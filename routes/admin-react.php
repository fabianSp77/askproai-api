<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReactAdminController;

/*
|--------------------------------------------------------------------------
| React Admin Portal Routes
|--------------------------------------------------------------------------
|
| These routes serve the React-based admin portal
|
*/

// Login page
Route::get('/login', [ReactAdminController::class, 'login'])->name('admin.react.login');

// React Admin Portal
Route::get('/react-admin-portal', [ReactAdminController::class, 'portal'])->name('admin.react.portal');

// Admin React SPA - catch all routes (requires authentication)
Route::get('/{any?}', [ReactAdminController::class, 'index'])
    ->where('any', '.*')
    ->name('admin.react.app');