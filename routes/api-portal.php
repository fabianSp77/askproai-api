<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\Api\DashboardApiController;
use App\Http\Controllers\Portal\Api\CallsApiController;
use App\Http\Controllers\Portal\Api\AppointmentsApiController;
use App\Http\Controllers\Portal\Api\CustomersApiController;
use App\Http\Controllers\Portal\Api\TeamApiController;
use App\Http\Controllers\Portal\Api\AnalyticsApiController;
use App\Http\Controllers\Portal\Api\SettingsApiController;
use App\Http\Controllers\Portal\Api\BillingApiController;
use App\Http\Controllers\Portal\Api\EmailController;
use App\Http\Controllers\Portal\Api\CustomerJourneyApiController;
use App\Http\Controllers\Portal\Api\FeedbackApiController;
use App\Http\Controllers\Portal\Api\SessionDebugController;

/*
|--------------------------------------------------------------------------
| Portal API Routes
|--------------------------------------------------------------------------
|
| API routes for the Business Portal React frontend
|
*/

// Auth check route (without auth middleware)
Route::get('business/api/auth-check', [App\Http\Controllers\Portal\Api\AuthCheckController::class, 'check'])
    ->middleware(['web'])
    ->name('business.api.auth-check');

// Debug route for session testing
Route::get('business/api/debug-session', [SessionDebugController::class, 'debug'])
    ->middleware(['web'])
    ->name('business.api.debug-session');

// Test session route
Route::get('business/api/test-session', [\App\Http\Controllers\Portal\Api\TestSessionController::class, 'test'])
    ->middleware(['web'])
    ->name('business.api.test-session');

// Debug session route (no auth)
Route::get('business/api/debug-session-noauth', [\App\Http\Controllers\Portal\Api\DebugSessionController::class, 'debug'])
    ->middleware(['web'])
    ->name('business.api.debug-session-noauth');

// Test auth route (no auth required)
Route::get('business/api/test-auth', [\App\Http\Controllers\Portal\Api\TestAuthController::class, 'test'])
    ->middleware(['web'])
    ->name('business.api.test-auth');

// Business Portal API Routes
Route::prefix('business/api')
    ->middleware(['web', 'portal.session', \App\Http\Middleware\SharePortalSession::class, 'portal.auth.api'])
    ->name('business.api.')
    ->group(function () {
        // Test route
        Route::get('/test-calls', [\App\Http\Controllers\Portal\Api\TestCallsController::class, 'test'])->name('test-calls');
        
        // Debug route for calls authentication
        Route::get('/debug-calls-auth', [\App\Http\Controllers\Portal\Api\DebugCallsApiController::class, 'debug'])->name('debug-calls-auth');
        
        // Dashboard
        Route::get('/dashboard', [DashboardApiController::class, 'index'])->name('dashboard');
        
        // Calls
        Route::prefix('calls')->name('calls.')->group(function () {
            Route::get('/', [CallsApiController::class, 'index'])->name('index');
            Route::get('/{call}', [CallsApiController::class, 'show'])->name('show');
            Route::get('/{call}/v2', [CallsApiController::class, 'show'])->name('show.v2');
            Route::get('/{call}/navigation', [CallsApiController::class, 'navigation'])->name('navigation');
            Route::get('/{call}/timeline', [CallsApiController::class, 'timeline'])->name('timeline');
            Route::post('/export', [CallsApiController::class, 'export'])->name('export');
            Route::post('/export-batch', [CallsApiController::class, 'exportBatch'])->name('export-batch');
            Route::post('/{call}/send-summary', [CallsApiController::class, 'sendSummary'])->name('send-summary');
            Route::post('/{call}/translate', [CallsApiController::class, 'translate'])->name('translate');
            Route::post('/{call}/assign', [CallsApiController::class, 'assign'])->name('assign');
        });
        
        // Email
        Route::prefix('email')->name('email.')->group(function () {
            Route::post('/send-direct', [EmailController::class, 'sendDirect'])->name('send-direct');
            Route::post('/preview', [EmailController::class, 'preview'])->name('preview');
            Route::get('/csv/{call}', [EmailController::class, 'downloadCsv'])->name('download-csv');
        });
        
        // Appointments
        Route::prefix('appointments')->name('appointments.')->group(function () {
            Route::get('/', [AppointmentsApiController::class, 'index'])->name('index');
            Route::get('/filters', [AppointmentsApiController::class, 'filters'])->name('filters');
            Route::get('/calendar', [AppointmentsApiController::class, 'calendar'])->name('calendar');
            Route::get('/{appointment}', [AppointmentsApiController::class, 'show'])->name('show');
            Route::post('/', [AppointmentsApiController::class, 'store'])->name('store');
            Route::put('/{appointment}', [AppointmentsApiController::class, 'update'])->name('update');
            Route::delete('/{appointment}', [AppointmentsApiController::class, 'destroy'])->name('destroy');
            Route::post('/export', [AppointmentsApiController::class, 'export'])->name('export');
            Route::post('/{appointment}/status', [AppointmentsApiController::class, 'updateStatus'])->name('update-status');
        });
        
        // Customers
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [CustomersApiController::class, 'index'])->name('index');
            Route::get('/{customer}', [CustomersApiController::class, 'show'])->name('show');
            Route::get('/{customer}/timeline', [CustomersApiController::class, 'timeline'])->name('timeline');
            Route::get('/{customer}/appointments', [CustomersApiController::class, 'appointments'])->name('appointments');
            Route::get('/{customer}/calls', [CustomersApiController::class, 'calls'])->name('calls');
            Route::post('/export', [CustomersApiController::class, 'export'])->name('export');
        });
        
        // Customer Journey
        Route::prefix('customer-journey')->name('customer-journey.')->group(function () {
            Route::get('/call/{call}', [CustomerJourneyApiController::class, 'getCallJourney'])->name('call');
            Route::post('/customer/{customer}/status', [CustomerJourneyApiController::class, 'updateJourneyStatus'])->name('update-status');
            Route::post('/call/{call}/assign', [CustomerJourneyApiController::class, 'assignCustomer'])->name('assign');
            Route::post('/customer/{customer}/note', [CustomerJourneyApiController::class, 'addNote'])->name('add-note');
            Route::get('/stats', [CustomerJourneyApiController::class, 'getCustomerStats'])->name('stats');
        });
        
        // Team
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamApiController::class, 'index'])->name('index');
            Route::get('/roles', [TeamApiController::class, 'roles'])->name('roles');
            Route::get('/{member}', [TeamApiController::class, 'show'])->name('show');
            Route::get('/{member}/availability', [TeamApiController::class, 'availability'])->name('availability');
            Route::post('/invite', [TeamApiController::class, 'invite'])->name('invite');
            Route::put('/{member}', [TeamApiController::class, 'update'])->name('update');
            Route::delete('/{member}', [TeamApiController::class, 'destroy'])->name('destroy');
        });
        
        // Analytics
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/overview', [AnalyticsApiController::class, 'overview'])->name('overview');
            Route::get('/calls', [AnalyticsApiController::class, 'calls'])->name('calls');
            Route::get('/appointments', [AnalyticsApiController::class, 'appointments'])->name('appointments');
            Route::get('/customers', [AnalyticsApiController::class, 'customers'])->name('customers');
            Route::get('/revenue', [AnalyticsApiController::class, 'revenue'])->name('revenue');
            Route::get('/team-performance', [AnalyticsApiController::class, 'teamPerformance'])->name('team-performance');
            Route::post('/export', [AnalyticsApiController::class, 'export'])->name('export');
        });
        
        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsApiController::class, 'index'])->name('index');
            Route::get('/profile', [SettingsApiController::class, 'getProfile'])->name('profile');
            Route::put('/profile', [SettingsApiController::class, 'updateProfile'])->name('update-profile');
            Route::put('/password', [SettingsApiController::class, 'updatePassword'])->name('update-password');
            Route::post('/2fa/enable', [SettingsApiController::class, 'enable2FA'])->name('2fa.enable');
            Route::post('/2fa/confirm', [SettingsApiController::class, 'confirm2FA'])->name('2fa.confirm');
            Route::post('/2fa/disable', [SettingsApiController::class, 'disable2FA'])->name('2fa.disable');
            Route::get('/company', [SettingsApiController::class, 'getCompanySettings'])->name('company');
            Route::get('/services', [SettingsApiController::class, 'services'])->name('services');
            Route::get('/working-hours', [SettingsApiController::class, 'workingHours'])->name('working-hours');
            Route::get('/integrations', [SettingsApiController::class, 'integrations'])->name('integrations');
            Route::put('/company', [SettingsApiController::class, 'updateCompany'])->name('update-company');
            Route::put('/services', [SettingsApiController::class, 'updateServices'])->name('update-services');
            Route::put('/working-hours', [SettingsApiController::class, 'updateWorkingHours'])->name('update-working-hours');
            Route::post('/theme', [SettingsApiController::class, 'updateTheme'])->name('theme.update');
            
            // Call notification settings
            Route::get('/call-notifications', [SettingsApiController::class, 'getCallNotificationSettings'])->name('call-notifications');
            Route::put('/call-notifications', [SettingsApiController::class, 'updateCallNotificationSettings'])->name('call-notifications.update');
            Route::put('/call-notifications/user', [SettingsApiController::class, 'updateUserCallPreferences'])->name('call-notifications.user');
        });
        
        // Billing
        Route::prefix('billing')->name('billing.')->group(function () {
            Route::get('/', [BillingApiController::class, 'index'])->name('index');
            Route::get('/transactions', [BillingApiController::class, 'getTransactions'])->name('transactions');
            Route::get('/usage', [BillingApiController::class, 'getUsage'])->name('usage');
            Route::get('/invoices', [BillingApiController::class, 'invoices'])->name('invoices');
            Route::get('/invoices/{invoice}', [BillingApiController::class, 'downloadInvoice'])->name('download-invoice');
            Route::get('/payment-methods', [BillingApiController::class, 'paymentMethods'])->name('payment-methods');
            Route::post('/payment-methods', [BillingApiController::class, 'addPaymentMethod'])->name('add-payment-method');
            Route::delete('/payment-methods/{method}', [BillingApiController::class, 'removePaymentMethod'])->name('remove-payment-method');
            Route::post('/topup', [BillingApiController::class, 'topup'])->name('topup');
            Route::get('/auto-topup', [BillingApiController::class, 'getAutoTopupSettings'])->name('get-auto-topup');
            Route::put('/auto-topup', [BillingApiController::class, 'updateAutoTopupSettings'])->name('update-auto-topup');
        });
        
        // Feedback
        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/', [FeedbackApiController::class, 'index'])->name('index');
            Route::get('/filters', [FeedbackApiController::class, 'getFilters'])->name('filters');
            Route::post('/', [FeedbackApiController::class, 'store'])->name('store');
            Route::get('/{id}', [FeedbackApiController::class, 'show'])->name('show');
            Route::post('/{id}/respond', [FeedbackApiController::class, 'respond'])->name('respond');
            Route::put('/{id}/status', [FeedbackApiController::class, 'updateStatus'])->name('update-status');
        });
        
        // Notifications
        Route::get('/notifications', function() {
            return response()->json([
                'notifications' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 1
                ]
            ]);
        })->name('notifications.index');
    });