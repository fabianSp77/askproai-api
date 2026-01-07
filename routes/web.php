<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\TestChecklistController;

Route::get('/', function () {
    return redirect('/admin');
});

// Debug route REMOVED for security (was exposing user details and permissions)

// Redirect old business routes to admin
Route::redirect('/business', '/admin', 301);
Route::redirect('/business/login', '/admin/login', 301);
Route::redirect('/business/{any}', '/admin/{any}', 301)->where('any', '.*');

// Test Checklist Routes (Protected - requires authentication)
Route::middleware(['auth'])->prefix('test-checklist')->group(function () {
    Route::get('/', [TestChecklistController::class, 'index'])->name('test-checklist.index');
    Route::get('/status', [TestChecklistController::class, 'status'])->name('test-checklist.status');
    Route::post('/test-webhook', [TestChecklistController::class, 'testWebhook'])->name('test-checklist.test-webhook');
    Route::post('/check-availability', [TestChecklistController::class, 'checkAvailability'])->name('test-checklist.check-availability');
    Route::post('/clear-cache', [TestChecklistController::class, 'clearCache'])->name('test-checklist.clear-cache');
});

// Admin API Routes (Internal use only)
Route::middleware(['auth'])->prefix('admin/api')->name('admin.api.')->group(function () {
    Route::get('output-config/preview', [\App\Http\Controllers\Admin\EmailPreviewController::class, 'preview'])
        ->name('output-config.preview');
});

// Monitoring Routes
// Health endpoint stays public for load balancer health checks
Route::get('/monitor/health', [MonitoringController::class, 'health'])->name('monitor.health');

// Dashboard requires authentication
Route::middleware(['auth'])->prefix('monitor')->group(function () {
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

// Conversation Flow Routes
Route::prefix('conversation-flow')->group(function () {
    // Public download - no auth required
    Route::get('/download-json', [\App\Http\Controllers\ConversationFlowController::class, 'downloadJson'])
        ->name('conversation-flow.download-json');
    Route::get('/download-guide', [\App\Http\Controllers\ConversationFlowController::class, 'downloadGuide'])
        ->name('conversation-flow.download-guide');

    // Protected routes
    Route::middleware(['auth:web'])->group(function () {
        Route::get('/reports', [\App\Http\Controllers\ConversationFlowController::class, 'viewReports'])
            ->name('conversation-flow.reports');
    });
});

// Customer Portal Routes
Route::prefix('kundenportal')->name('customer-portal.')->group(function () {
    // Public routes (no auth required)
    Route::get('/einladung/{token}', function ($token) {
        return view('customer-portal.auth.invitation', ['token' => $token]);
    })->name('invitation');

    // Redirect login to invitation (customers use invitation links)
    Route::get('/login', function () {
        return redirect()->route('customer-portal.invitation', ['token' => 'expired'])
            ->with('message', 'Bitte verwenden Sie den Einladungslink aus Ihrer E-Mail.');
    })->name('login');
});

// Customer Portal - Protected Routes (requires Sanctum token via Alpine.js)
// Note: These routes render Blade views. Authentication is handled client-side via Alpine.js
// The actual API calls will validate the Sanctum token
Route::middleware(['web'])->group(function () {
    Route::get('/meine-termine', function () {
        return view('customer-portal.appointments.index');
    })->name('customer-portal.appointments.index');

    Route::get('/meine-termine/{id}', function ($id) {
        return view('customer-portal.appointments.show', ['appointmentId' => $id]);
    })->name('customer-portal.appointments.show');

    Route::get('/meine-termine/{id}/umbuchen', function ($id) {
        return view('customer-portal.appointments.reschedule', ['appointmentId' => $id]);
    })->name('customer-portal.appointments.reschedule');

    Route::get('/meine-termine/{id}/stornieren', function ($id) {
        return view('customer-portal.appointments.cancel', ['appointmentId' => $id]);
    })->name('customer-portal.appointments.cancel');
});


// ============================================================================
// Session Management Routes (Session-Timeout-Warning-System)
// ============================================================================
Route::middleware(['web', 'auth'])->prefix('api/session')->name('session.')->group(function () {
    Route::post('/ping', [\App\Http\Controllers\SessionController::class, 'ping'])
        ->name('ping');
    Route::get('/status', [\App\Http\Controllers\SessionController::class, 'status'])
        ->name('status');
});

require __DIR__.'/auth.php';
require __DIR__.'/web-test.php';


