<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UltrathinkAuthController;

// ULTRATHINK Auth Bridge Routes
Route::prefix('auth')->group(function () {
    // Bridge token to session
    Route::get('/bridge', [UltrathinkAuthController::class, 'bridgeAuth'])
        ->name('auth.bridge');
    
    // Direct session creation
    Route::post('/direct-session', [UltrathinkAuthController::class, 'directSession'])
        ->name('auth.direct-session');
    
    // Business portal with auto-bridge
    Route::get('/business-bridge', function() {
        return redirect('/auth/bridge?redirect=/business');
    })->name('business.bridge');
});