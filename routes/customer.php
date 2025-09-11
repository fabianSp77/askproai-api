<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customer;

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
|
| These routes handle the customer portal functionality including
| dashboard, billing, transactions, and account management.
|
*/

Route::middleware(['auth', 'verified', 'customer.access'])->prefix('customer')->name('customer.')->group(function () {
    
    // Dashboard
    Route::get('/', [Customer\DashboardController::class, 'index'])->name('dashboard');
    
    // Billing & Balance
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [Customer\BillingController::class, 'index'])->name('index');
        Route::get('/topup', [Customer\BillingController::class, 'topup'])->name('topup');
        Route::post('/topup', [Customer\BillingController::class, 'processTopup'])->name('topup.process');
        Route::get('/success', [Customer\BillingController::class, 'success'])->name('success');
        Route::get('/cancel', [Customer\BillingController::class, 'cancel'])->name('cancel');
        
        // Auto-topup settings
        Route::get('/auto-topup', [Customer\BillingController::class, 'autoTopupSettings'])->name('auto-topup');
        Route::post('/auto-topup', [Customer\BillingController::class, 'updateAutoTopup'])->name('auto-topup.update');
        
        // Payment methods
        Route::get('/payment-methods', [Customer\PaymentMethodController::class, 'index'])->name('payment-methods');
        Route::post('/payment-methods', [Customer\PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::delete('/payment-methods/{method}', [Customer\PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');
        Route::post('/payment-methods/{method}/default', [Customer\PaymentMethodController::class, 'setDefault'])->name('payment-methods.default');
    });
    
    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [Customer\TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [Customer\TransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/invoice', [Customer\TransactionController::class, 'downloadInvoice'])->name('invoice');
        Route::post('/export', [Customer\TransactionController::class, 'export'])->name('export');
    });
    
    // Calls
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [Customer\CallController::class, 'index'])->name('index');
        Route::get('/{call}', [Customer\CallController::class, 'show'])->name('show');
        Route::get('/{call}/recording', [Customer\CallController::class, 'recording'])->name('recording');
        Route::get('/{call}/transcript', [Customer\CallController::class, 'transcript'])->name('transcript');
        Route::post('/export', [Customer\CallController::class, 'export'])->name('export');
    });
    
    // Invoices & Statements
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [Customer\InvoiceController::class, 'index'])->name('index');
        Route::get('/{invoice}/download', [Customer\InvoiceController::class, 'download'])->name('download');
        Route::get('/statements', [Customer\InvoiceController::class, 'statements'])->name('statements');
        Route::get('/statements/{month}', [Customer\InvoiceController::class, 'downloadStatement'])->name('statement.download');
    });
    
    // API Keys
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [Customer\ApiKeyController::class, 'index'])->name('index');
        Route::post('/', [Customer\ApiKeyController::class, 'store'])->name('store');
        Route::delete('/{key}', [Customer\ApiKeyController::class, 'destroy'])->name('destroy');
        Route::post('/{key}/regenerate', [Customer\ApiKeyController::class, 'regenerate'])->name('regenerate');
    });
    
    // Profile & Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [Customer\SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [Customer\SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [Customer\SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/notifications', [Customer\SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::post('/webhook', [Customer\SettingsController::class, 'updateWebhook'])->name('webhook.update');
        Route::post('/2fa', [Customer\SettingsController::class, 'toggle2FA'])->name('2fa.toggle');
    });
    
    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [Customer\SupportController::class, 'index'])->name('index');
        Route::post('/ticket', [Customer\SupportController::class, 'createTicket'])->name('ticket.create');
        Route::get('/tickets', [Customer\SupportController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/{ticket}', [Customer\SupportController::class, 'showTicket'])->name('ticket.show');
    });
    
    // Real-time Balance Stream (SSE)
    Route::get('/balance/stream', [Customer\BalanceStreamController::class, 'stream'])
        ->name('balance.stream')
        ->middleware('throttle:10,1'); // Rate limit SSE connections
    
    // Usage Statistics API (for charts)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/usage-stats', [Customer\ApiController::class, 'usageStats'])->name('usage-stats');
        Route::get('/balance-history', [Customer\ApiController::class, 'balanceHistory'])->name('balance-history');
        Route::get('/call-analytics', [Customer\ApiController::class, 'callAnalytics'])->name('call-analytics');
    });
});

// Webhook endpoints (no auth required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/stripe', [Customer\StripeWebhookController::class, 'handle'])
        ->name('stripe')
        ->middleware('stripe.webhook');
    
    Route::post('/push-subscription', [Customer\PushSubscriptionController::class, 'store'])
        ->name('push.subscribe')
        ->middleware('auth');
    
    Route::delete('/push-subscription', [Customer\PushSubscriptionController::class, 'destroy'])
        ->name('push.unsubscribe')
        ->middleware('auth');
});