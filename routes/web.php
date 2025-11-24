<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\TestChecklistController;

Route::get('/', function () {
    return redirect('/admin');
});

// Debug route to check current user and permissions
Route::get('/debug-user', function () {
    if (!auth()->check()) {
        return response()->json([
            'authenticated' => false,
            'message' => 'Not logged in. Please login at /admin/login first'
        ]);
    }

    $user = auth()->user();
    $roles = $user->roles->pluck('name')->toArray();

    return response()->json([
        'authenticated' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
        ],
        'roles' => $roles,
        'permissions' => [
            'can_view_services' => $user->can('viewAny', \App\Models\Service::class),
            'can_create_services' => $user->can('create', \App\Models\Service::class),
        ],
        'policy_checks' => [
            'hasAnyRole_admin' => $user->hasAnyRole(['admin', 'Admin']),
            'hasAnyRole_manager' => $user->hasAnyRole(['manager']),
            'hasAnyRole_company_owner' => $user->hasAnyRole(['company_owner']),
            'hasAnyRole_reseller_owner' => $user->hasAnyRole(['reseller_owner']),
            'hasRole_super_admin' => $user->hasRole('super_admin'),
            'hasRole_Super_Admin' => $user->hasRole('Super Admin'),
        ]
    ], 200, [], JSON_PRETTY_PRINT);
})->middleware('web');

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


require __DIR__.'/auth.php';
require __DIR__.'/web-test.php';
