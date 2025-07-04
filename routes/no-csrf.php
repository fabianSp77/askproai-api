<?php

use Illuminate\Support\Facades\Route;

// These routes have NO middleware at all - use with caution!
Route::withoutMiddleware('*')->group(function () {
    Route::post('/api/no-csrf/login', [App\Http\Controllers\Auth\NoCSRFLoginController::class, 'login']);
});