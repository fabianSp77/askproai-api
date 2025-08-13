<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AdminV2 Portal Routes - Minimal Working Solution
|--------------------------------------------------------------------------
*/

// Guest routes (accessible without authentication)
Route::middleware(['web'])
    ->prefix('admin-v2')
    ->name('adminv2.')
    ->group(function () {
        // Standalone portal (single-page app that bypasses 405 error)
        Route::get('portal', function() {
            return view('adminv2.portal-standalone');
        })->name('portal');
        
        // Alternative API-based login page (workaround for 405 error)
        Route::get('login-api', function() {
            return view('adminv2.auth.login-api');
        })->name('login.api');
    });

// API Auth routes (JSON responses) - With session but without CSRF
Route::middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ])
    ->prefix('admin-v2/api')
    ->name('adminv2.api.')
    ->group(function () {
        Route::post('login', [\App\Http\Controllers\AdminV2\Auth\ApiLoginController::class, 'login'])->name('login');
        Route::get('check', [\App\Http\Controllers\AdminV2\Auth\ApiLoginController::class, 'check'])->name('check');
        Route::post('logout', [\App\Http\Controllers\AdminV2\Auth\ApiLoginController::class, 'logout'])->name('logout');
    });