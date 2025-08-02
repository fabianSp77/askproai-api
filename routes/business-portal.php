<?php

use App\Http\Controllers\Portal\AnalyticsController;
use App\Http\Controllers\Portal\SimpleAnalyticsController;
use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\SimpleAppointmentController;
use App\Http\Controllers\Portal\Auth\AjaxLoginController;
use App\Http\Controllers\Portal\Auth\LoginController;
use App\Http\Controllers\Portal\Auth\TwoFactorController;
use App\Http\Controllers\Portal\BillingController;
use App\Http\Controllers\Portal\SimpleBillingController;
use App\Http\Controllers\Portal\WorkingBillingController;
use App\Http\Controllers\Portal\ReactBillingController;
use App\Http\Controllers\Portal\ReactCallController;
use App\Http\Controllers\Portal\ReactAppointmentController;
use App\Http\Controllers\Portal\ReactDashboardController;
use App\Http\Controllers\Portal\CallController;
use App\Http\Controllers\Portal\SimpleCallController;
use App\Http\Controllers\Portal\TestCallsController;
use App\Http\Controllers\Portal\PublicTestCallsController;
use App\Http\Controllers\Portal\SimpleCallShowController;
use App\Http\Controllers\Portal\CustomerController;
use App\Http\Controllers\Portal\SimpleCustomerController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\SimpleDashboardController;
use App\Http\Controllers\Portal\FeedbackController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\Portal\SimpleSettingsController;
use App\Http\Controllers\Portal\TeamController;
use App\Http\Controllers\Portal\SimpleTeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Portal Routes
|--------------------------------------------------------------------------
*/

// ========================================
// 1. PUBLIC ROUTES (No Auth Required)
// ========================================
Route::prefix('business')->name('business.')->group(function () {
    // Login routes - NO AUTH MIDDLEWARE, but WITH RATE LIMITING
    Route::middleware(['web'])->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('auth.rate.limit')
            ->name('login.post');
        
        // AJAX Authentication Routes
        Route::prefix('api/auth')->name('api.auth.')->group(function () {
            Route::post('/login', [AjaxLoginController::class, 'login'])
                ->middleware('auth.rate.limit')
                ->name('login');
            Route::get('/check', [AjaxLoginController::class, 'check'])->name('check');
        });
        
        // TEMPORARY: Public test endpoint for calls
        Route::get('/api/public/calls', [PublicTestCallsController::class, 'apiIndex'])->name('api.public.calls');
    });
});

// ========================================
// 2. AUTHENTICATED ROUTES
// ========================================
Route::prefix('business')->middleware(['web', 'portal.auth'])->name('business.')->group(function () {
    // Main portal routes - Using ReactDashboardController with React UI
    Route::get('/', [ReactDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [ReactDashboardController::class, 'index'])->name('dashboard.main');
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // AJAX auth routes (need to be accessible for authenticated users)
    Route::get('/api/auth/check', [AjaxLoginController::class, 'check'])->name('api.auth.check');
    Route::post('/api/auth/logout', [AjaxLoginController::class, 'logout'])->name('api.auth.logout');
    Route::post('/api/auth/refresh', [AjaxLoginController::class, 'refresh'])->name('api.auth.refresh');
    
    // 2FA routes
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
    
    // Calls - Using ReactCallController with React UI
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [ReactCallController::class, 'index'])->name('index');
        Route::get('/{call}', [ReactCallController::class, 'show'])->name('show');
    });
    
    // Appointments - Using ReactAppointmentController with React UI
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [ReactAppointmentController::class, 'index'])->name('index');
        Route::get('/create', [ReactAppointmentController::class, 'create'])->name('create');
        Route::post('/', [SimpleAppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}', [SimpleAppointmentController::class, 'show'])->name('show');
        Route::get('/{appointment}/edit', [SimpleAppointmentController::class, 'edit'])->name('edit');
        Route::put('/{appointment}', [SimpleAppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [SimpleAppointmentController::class, 'destroy'])->name('destroy');
    });
    
    // Billing - Using ReactBillingController with original React UI
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [ReactBillingController::class, 'index'])->name('index');
        Route::get('/topup', [WorkingBillingController::class, 'topup'])->name('topup');
        Route::post('/topup', [WorkingBillingController::class, 'processTopup'])->name('topup.process');
        Route::get('/invoices', [WorkingBillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [WorkingBillingController::class, 'downloadInvoice'])->name('invoice.download');
    });
    
    // Settings - Using SimpleSettingsController temporarily
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SimpleSettingsController::class, 'index'])->name('index');
        Route::post('/profile', [SimpleSettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [SimpleSettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/2fa/enable', [SimpleSettingsController::class, 'enable2FA'])->name('2fa.enable');
        Route::post('/2fa/disable', [SimpleSettingsController::class, 'disable2FA'])->name('2fa.disable');
    });
    
    // Team - Using SimpleTeamController temporarily
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [SimpleTeamController::class, 'index'])->name('index');
        Route::get('/create', [SimpleTeamController::class, 'create'])->name('create');
        Route::post('/', [SimpleTeamController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [SimpleTeamController::class, 'edit'])->name('edit');
        Route::put('/{user}', [SimpleTeamController::class, 'update'])->name('update');
        Route::delete('/{user}', [SimpleTeamController::class, 'destroy'])->name('destroy');
    });
    
    // Analytics - Using SimpleAnalyticsController temporarily
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [SimpleAnalyticsController::class, 'index'])->name('index');
    });
    
    // Customers - Using SimpleCustomerController temporarily
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [SimpleCustomerController::class, 'index'])->name('index');
        Route::get('/{customer}', [SimpleCustomerController::class, 'show'])->name('show');
    });
    
    // Feedback
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

// ========================================
// 3. API ROUTES (Authenticated)
// ========================================
Route::prefix('business/api')->middleware(['web', 'portal.auth'])->name('business.api.')->group(function () {
    // Dashboard data - Using ReactDashboardController for React app
    Route::get('/dashboard/stats', [ReactDashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/recent-calls', [ReactDashboardController::class, 'recentCalls'])->name('dashboard.recent-calls');
    Route::get('/dashboard/upcoming-appointments', [ReactDashboardController::class, 'upcomingAppointments'])->name('dashboard.upcoming-appointments');
    
    // Calls API - Using ReactCallController for React app
    Route::get('/calls', [ReactCallController::class, 'apiIndex'])->name('calls.index');
    Route::get('/calls/{call}', [ReactCallController::class, 'apiShow'])->name('calls.show');
    
    // Appointments API - Using ReactAppointmentController for React app
    Route::get('/appointments', [ReactAppointmentController::class, 'apiIndex'])->name('appointments.index');
    Route::get('/appointments/filters', [ReactAppointmentController::class, 'getFilters'])->name('appointments.filters');
    Route::get('/appointments/available-slots', [ReactAppointmentController::class, 'availableSlots'])->name('appointments.available-slots');
    Route::post('/appointments', [ReactAppointmentController::class, 'apiStore'])->name('appointments.store');
    Route::post('/appointments/{appointment}/status', [ReactAppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'apiUpdate'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'apiDestroy'])->name('appointments.destroy');
    
    // Billing API for React app
    Route::get('/billing', [ReactBillingController::class, 'getBillingData'])->name('billing.data');
    Route::get('/billing/data', [ReactBillingController::class, 'getBillingData'])->name('billing.data.alt'); // Keep for compatibility
    Route::get('/billing/transactions', [ReactBillingController::class, 'getTransactions'])->name('billing.transactions');
    Route::get('/billing/usage', [ReactBillingController::class, 'getUsageData'])->name('billing.usage');
    Route::post('/billing/auto-topup', [ReactBillingController::class, 'updateAutoTopup'])->name('billing.auto-topup');
    Route::post('/billing/topup', [ReactBillingController::class, 'initiateTopup'])->name('billing.topup');
    
    // Customers API - CRITICAL FIX FOR 500 ERROR
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Portal\Api\CustomersApiController::class, 'index'])->name('index');
        Route::get('/{customer}', [\App\Http\Controllers\Portal\Api\CustomersApiController::class, 'show'])->name('show');
        Route::put('/{customer}', [\App\Http\Controllers\Portal\Api\CustomersApiController::class, 'update'])->name('update');
        Route::get('/{customer}/appointments', [\App\Http\Controllers\Portal\Api\CustomersApiController::class, 'appointments'])->name('appointments');
        Route::get('/{customer}/invoices', [\App\Http\Controllers\Portal\Api\CustomersApiController::class, 'invoices'])->name('invoices');
    });
    
    // Dashboard Stats API - FIX FOR MISSING STATS ENDPOINT
    Route::get('/stats', [\App\Http\Controllers\Portal\Api\DashboardApiController::class, 'stats'])->name('stats');
    
    // User API - For current user info
    Route::get('/user', [\App\Http\Controllers\Portal\Api\UserController::class, 'current'])->name('user.current');
});

// ========================================
// 5. CATCH-ALL for React SPA (Must be last)
// ========================================
// DISABLED - Causing redirect loops
// Route::get('/business/{any}', function () {
//     return view('portal.react-app');
// })->where('any', '.*')->middleware(['web']);