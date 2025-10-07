<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\TestChecklistController;

Route::get('/', function () {
    return redirect('/admin');
});

// Redirect old business routes to admin
Route::redirect('/business', '/admin', 301);
Route::redirect('/business/login', '/admin/login', 301);
Route::redirect('/business/{any}', '/admin/{any}', 301)->where('any', '.*');

// Test Checklist Routes (Public Access)
Route::prefix('test-checklist')->group(function () {
    Route::get('/', [TestChecklistController::class, 'index'])->name('test-checklist.index');
    Route::get('/status', [TestChecklistController::class, 'status'])->name('test-checklist.status');
    Route::post('/test-webhook', [TestChecklistController::class, 'testWebhook'])->name('test-checklist.test-webhook');
    Route::post('/check-availability', [TestChecklistController::class, 'checkAvailability'])->name('test-checklist.check-availability');
    Route::post('/clear-cache', [TestChecklistController::class, 'clearCache'])->name('test-checklist.clear-cache');
});

// Monitoring Routes
Route::prefix('monitor')->group(function () {
    Route::get('/health', [MonitoringController::class, 'health'])->name('monitor.health');
    Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('monitor.dashboard');
});

// Guides & Documentation Routes
Route::prefix('guides')->group(function () {
    Route::get('/retell-agent-update', function () {
        return view('guides.retell-agent-update');
    })->name('guides.retell-agent-update');

    Route::get('/retell-agent-query-function', function () {
        return view('guides.retell-agent-query-function');
    })->name('guides.retell-agent-query-function');
});

// Protected Documentation Routes (requires authentication)
Route::middleware(['auth'])->prefix('docs')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocsController::class, 'index'])->name('docs.index');
    Route::get('/claudedocs/{path}', [\App\Http\Controllers\DocsController::class, 'show'])
        ->name('docs.show')
        ->where('path', '.*');
});


require __DIR__.'/auth.php';
require __DIR__.'/web-test.php';
