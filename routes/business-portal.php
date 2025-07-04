<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\Auth\LoginController;
use App\Http\Controllers\Portal\Auth\TwoFactorController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\CallController;
use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\BillingController;
use App\Http\Controllers\Portal\AnalyticsController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\Portal\TeamController;
use App\Http\Controllers\Portal\FeedbackController;

/*
|--------------------------------------------------------------------------
| Business Portal Routes
|--------------------------------------------------------------------------
|
| Business portal routes for company users (B2B)
|
*/

Route::prefix('business')->name('business.')->group(function () {
    
    // Admin access route (before auth middleware)
    Route::get('/admin-access', [App\Http\Controllers\Portal\AdminAccessController::class, 'access'])
        ->name('admin.access');
    
    // Authentication routes
    Route::middleware('guest:portal')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.post');
        
        // 2FA setup/challenge (before full auth)
        Route::get('/two-factor/setup', [TwoFactorController::class, 'showSetupForm'])->name('two-factor.setup');
        Route::post('/two-factor/setup', [TwoFactorController::class, 'confirmSetup'])->name('two-factor.setup.post');
        Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallengeForm'])->name('two-factor.challenge');
        Route::post('/two-factor/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('two-factor.challenge.post');
    });
    
    // Authenticated routes
    Route::middleware(['portal.auth'])->group(function () {
        
        // Admin exit route
        Route::get('/admin-exit', [App\Http\Controllers\Portal\AdminAccessController::class, 'exitAdminAccess'])
            ->name('admin.exit');
        
        // Logout
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Calls module
        Route::prefix('calls')->name('calls.')->group(function () {
            Route::get('/', [CallController::class, 'index'])
                ->middleware('portal.permission:calls.view_own')
                ->name('index');
                
            Route::get('/{call}', [CallController::class, 'show'])
                ->middleware('portal.permission:calls.view_own')
                ->name('show');
                
            Route::post('/{call}/status', [CallController::class, 'updateStatus'])
                ->middleware('portal.permission:calls.edit_own')
                ->name('update-status');
                
            Route::post('/{call}/assign', [CallController::class, 'assign'])
                ->middleware('portal.permission:calls.edit_all')
                ->name('assign');
                
            Route::post('/{call}/notes', [CallController::class, 'addNote'])
                ->middleware('portal.permission:calls.edit_own')
                ->name('add-note');
                
            Route::post('/{call}/schedule-callback', [CallController::class, 'scheduleCallback'])
                ->middleware('portal.permission:calls.edit_own')
                ->name('schedule-callback');
                
            Route::get('/export/csv', [CallController::class, 'exportCsv'])
                ->middleware('portal.permission:calls.export')
                ->name('export');
                
            Route::post('/export/bulk', [CallController::class, 'exportBulk'])
                ->middleware('portal.permission:calls.export')
                ->name('export.bulk');
        });
        
        // Appointments module (if enabled)
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentController::class, 'index'])
                ->middleware('portal.permission:appointments.view_own')
                ->name('index');
                
            Route::get('/{appointment}', [AppointmentController::class, 'show'])
                ->middleware('portal.permission:appointments.view_own')
                ->name('show');
        });
        
        // Billing module
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/', [BillingController::class, 'index'])
                ->middleware('portal.permission:billing.view')
                ->name('index');
                
            // Prepaid Balance Routes
            Route::get('/topup', [BillingController::class, 'topup'])
                ->middleware('portal.permission:billing.pay')
                ->name('topup');
                
            Route::post('/topup', [BillingController::class, 'processTopup'])
                ->middleware('portal.permission:billing.pay')
                ->name('topup.process');
                
            Route::get('/topup/success', [BillingController::class, 'topupSuccess'])
                ->middleware('portal.permission:billing.pay')
                ->name('topup.success');
                
            Route::get('/topup/cancel', [BillingController::class, 'topupCancel'])
                ->middleware('portal.permission:billing.pay')
                ->name('topup.cancel');
                
            // Transaction History
            Route::get('/transactions', [BillingController::class, 'transactions'])
                ->middleware('portal.permission:billing.view')
                ->name('transactions');
                
            Route::get('/transactions/{transaction}/invoice', [BillingController::class, 'downloadInvoice'])
                ->middleware('portal.permission:billing.view')
                ->name('transaction.invoice');
                
            // Usage Statistics
            Route::get('/usage', [BillingController::class, 'usage'])
                ->middleware('portal.permission:billing.view')
                ->name('usage');
                
            // Legacy invoice routes (kept for compatibility)
            Route::get('/invoices/{invoice}', [BillingController::class, 'showInvoice'])
                ->middleware('portal.permission:billing.view')
                ->name('invoice.show');
                
            Route::get('/invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])
                ->middleware('portal.permission:billing.view')
                ->name('invoice.download');
                
            Route::post('/invoices/{invoice}/pay', [BillingController::class, 'payInvoice'])
                ->middleware('portal.permission:billing.pay')
                ->name('invoice.pay');
        });
        
        // Analytics module
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index'])
                ->middleware('portal.permission:analytics.view_team')
                ->name('index');
                
            Route::get('/export', [AnalyticsController::class, 'export'])
                ->middleware('portal.permission:analytics.export')
                ->name('export');
        });
        
        // Team management
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])
                ->middleware('portal.permission:team.view')
                ->name('index');
                
            Route::get('/invite', [TeamController::class, 'showInviteForm'])
                ->middleware('portal.permission:team.manage')
                ->name('invite');
                
            Route::post('/invite', [TeamController::class, 'sendInvite'])
                ->middleware('portal.permission:team.manage')
                ->name('invite.send');
                
            Route::post('/users/{user}/update', [TeamController::class, 'updateUser'])
                ->middleware('portal.permission:team.manage')
                ->name('user.update');
                
            Route::post('/users/{user}/deactivate', [TeamController::class, 'deactivateUser'])
                ->middleware('portal.permission:team.manage')
                ->name('user.deactivate');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
            Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
            Route::get('/password', [SettingsController::class, 'password'])->name('password');
            Route::post('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
            Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::post('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');
            Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
        });
        
        // Feedback
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::post('/', [FeedbackController::class, 'store'])
                ->middleware('portal.permission:feedback.create')
                ->name('store');
                
            Route::get('/', [FeedbackController::class, 'index'])
                ->middleware('portal.permission:feedback.view_team')
                ->name('index');
                
            Route::post('/{feedback}/respond', [FeedbackController::class, 'respond'])
                ->middleware('portal.permission:feedback.respond')
                ->name('respond');
        });
    });
});