<?php

use App\Http\Controllers\Portal\CustomerAuthController;
use App\Http\Controllers\Portal\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
|
| These routes handle the customer self-service portal where customers
| can view their appointments, invoices, and manage their profile.
|
*/

// Guest routes
Route::middleware('guest:customer')->group(function () {
    // Login
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])
        ->name('portal.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->name('portal.login.submit');
    
    // Magic link login
    Route::post('/magic-link', [CustomerAuthController::class, 'magicLinkLogin'])
        ->name('portal.magic-link');
    Route::get('/magic-link/{token}', [CustomerAuthController::class, 'verifyMagicLink'])
        ->name('portal.magic-link.verify');
    
    // Password reset
    Route::get('/forgot-password', [CustomerAuthController::class, 'showResetForm'])
        ->name('portal.password.request');
    Route::post('/forgot-password', [CustomerAuthController::class, 'sendResetLink'])
        ->name('portal.password.email');
    Route::get('/reset-password/{token}', [CustomerAuthController::class, 'showNewPasswordForm'])
        ->name('portal.password.reset');
    Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword'])
        ->name('portal.password.update');
});

// Authenticated routes
Route::middleware(['auth:customer', 'verified:customer'])->group(function () {
    // Dashboard
    Route::get('/', [CustomerDashboardController::class, 'index'])
        ->name('portal.dashboard');
    
    // Appointments
    Route::get('/appointments', [CustomerDashboardController::class, 'appointments'])
        ->name('portal.appointments');
    Route::get('/appointments/{appointment}', [CustomerDashboardController::class, 'showAppointment'])
        ->name('portal.appointments.show');
    Route::post('/appointments/{appointment}/cancel', [CustomerDashboardController::class, 'cancelAppointment'])
        ->name('portal.appointments.cancel');
    
    // Invoices
    Route::get('/invoices', [CustomerDashboardController::class, 'invoices'])
        ->name('portal.invoices');
    Route::get('/invoices/{invoice}', [CustomerDashboardController::class, 'showInvoice'])
        ->name('portal.invoices.show');
    Route::get('/invoices/{invoice}/download', [CustomerDashboardController::class, 'downloadInvoice'])
        ->name('portal.invoices.download');
    
    // Profile
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])
        ->name('portal.profile');
    Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])
        ->name('portal.profile.update');
    Route::put('/profile/password', [CustomerDashboardController::class, 'updatePassword'])
        ->name('portal.profile.password');
    Route::put('/profile/newsletter', [CustomerDashboardController::class, 'updateNewsletter'])
        ->name('portal.profile.newsletter');
    Route::delete('/profile', [CustomerDashboardController::class, 'deleteAccount'])
        ->name('portal.profile.delete');
    
    // Security
    Route::get('/security/2fa', [CustomerDashboardController::class, 'show2FASettings'])
        ->name('portal.security.2fa');
    
    // Privacy & GDPR
    Route::prefix('privacy')->name('portal.privacy')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\PrivacyController::class, 'index']);
        Route::put('/cookie-consent', [\App\Http\Controllers\Portal\PrivacyController::class, 'updateCookieConsent'])
            ->name('.cookie-consent');
        Route::delete('/cookie-consent', [\App\Http\Controllers\Portal\PrivacyController::class, 'withdrawCookieConsent'])
            ->name('.cookie-consent.withdraw');
        Route::post('/request-export', [\App\Http\Controllers\Portal\PrivacyController::class, 'requestDataExport'])
            ->name('.request-export');
        Route::post('/request-deletion', [\App\Http\Controllers\Portal\PrivacyController::class, 'requestDataDeletion'])
            ->name('.request-deletion');
        Route::get('/download-export/{gdprRequest}', [\App\Http\Controllers\Portal\PrivacyController::class, 'downloadExport'])
            ->name('.download-export');
    });
    
    // Logout
    Route::post('/logout', [CustomerAuthController::class, 'logout'])
        ->name('portal.logout');
});

// Email verification
Route::middleware(['auth:customer'])->group(function () {
    Route::get('/email/verify', function () {
        return view('portal.auth.verify-email');
    })->name('portal.verification.notice');
    
    Route::get('/email/verify/{id}/{hash}', function () {
        // Handle verification
    })->middleware(['signed'])->name('portal.verification.verify');
    
    Route::post('/email/verification-notification', function () {
        // Resend verification
    })->middleware(['throttle:6,1'])->name('portal.verification.send');
});

// Public legal pages (no auth required)
Route::get('/cookie-policy', [\App\Http\Controllers\Portal\PrivacyController::class, 'cookiePolicy'])
    ->name('portal.cookie-policy');
Route::get('/privacy-policy', [\App\Http\Controllers\Portal\PrivacyController::class, 'privacyPolicy'])
    ->name('portal.privacy-policy');

// Include knowledge base routes (requires authentication)
require __DIR__.'/knowledge.php';