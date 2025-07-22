<?php

use App\Http\Controllers\Portal\AnalyticsController;
use App\Http\Controllers\Portal\AppointmentController;
use App\Http\Controllers\Portal\Auth\AjaxLoginController;
use App\Http\Controllers\Portal\Auth\LoginController;
use App\Http\Controllers\Portal\Auth\TwoFactorController;
use App\Http\Controllers\Portal\BillingController;
use App\Http\Controllers\Portal\CallController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DemoController;
use App\Http\Controllers\Portal\FeedbackController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\Portal\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Portal Routes
|--------------------------------------------------------------------------
|
| Business portal routes for company users (B2B)
|
| Structure:
| 1. Test/Debug Routes (should be removed in production)
| 2. Authentication Routes
| 3. Authenticated Portal Routes
| 4. API Routes (authenticated)
| 5. API Routes (optional auth)
| 6. Catch-all Route for React SPA (must be last)
|
*/

// ========================================
// 1. TEST/DEBUG ROUTES (Remove in production)
// ========================================
Route::prefix('business')->name('business.')->group(function () {
    // Simple session test
    Route::get('/api/simple-session-test', [App\Http\Controllers\Portal\Api\SimpleSessionTestController::class, 'test'])
        ->name('api.simple-session-test');

    // Force auth test
    Route::get('/api/force-auth-test', [App\Http\Controllers\Portal\Api\ForceAuthController::class, 'forceAuth'])
        ->name('api.force-auth-test');

    // Session test routes
    Route::middleware(['web'])->prefix('session-test')->group(function () {
        Route::get('/set', function () {
            session(['test_value' => 'Session set at ' . now()]);
            session(['counter' => session('counter', 0) + 1]);

            return response()->json([
                'success' => true,
                'session_id' => session()->getId(),
                'session_name' => session()->getName(),
                'data_set' => [
                    'test_value' => session('test_value'),
                    'counter' => session('counter'),
                ],
            ]);
        })->name('session-test.set');

        Route::get('/get', function () {
            return response()->json([
                'session_id' => session()->getId(),
                'session_name' => session()->getName(),
                'test_value' => session('test_value'),
                'counter' => session('counter'),
                'all_data' => session()->all(),
            ]);
        })->name('session-test.get');
    });
});

// ========================================
// 2. AUTHENTICATION ROUTES
// ========================================
Route::prefix('business')->middleware(['web', 'portal.session'])->name('business.')->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // AJAX Authentication Routes
    Route::prefix('api/auth')->name('api.auth.')->group(function () {
        Route::post('/login', [AjaxLoginController::class, 'login'])->name('login');
        Route::post('/logout', [AjaxLoginController::class, 'logout'])->name('logout');
        Route::get('/check', [AjaxLoginController::class, 'check'])->name('check');
        Route::post('/refresh', [AjaxLoginController::class, 'refresh'])->name('refresh');
    });

    // 2FA routes
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');

    // Additional 2FA routes referenced in LoginController
    Route::get('/two-factor/setup', [TwoFactorController::class, 'showSetupForm'])->name('two-factor.setup');
    Route::post('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup.post');
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'showChallengeForm'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge.post');
});

// ========================================
// 3. AUTHENTICATED PORTAL ROUTES
// ========================================
Route::prefix('business')->middleware(['web', 'portal.session', 'portal.auth', 'portal.2fa'])->name('business.')->group(function () {
    // Main portal routes
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.main');

    // Calls
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [CallController::class, 'index'])->name('index');
        Route::get('/{call}', [CallController::class, 'show'])->name('show');
    });

    // Appointments
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
        Route::get('/{appointment}', [AppointmentController::class, 'show'])->name('show');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])->name('edit');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])->name('destroy');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('/topup', [BillingController::class, 'topup'])->name('topup');
        Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [BillingController::class, 'downloadInvoice'])->name('invoice.download');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/company', [SettingsController::class, 'updateCompany'])->name('company.update');
    });

    // Team
    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [TeamController::class, 'edit'])->name('edit');
        Route::put('/{user}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');
    });

    // Feedback
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
    });
});

// ========================================
// 4. AUTHENTICATED API ROUTES
// ========================================
Route::prefix('business/api')->middleware(['web', 'portal.session', 'portal.auth', 'portal.2fa'])->name('business.api.')->group(function () {
    // User info
    Route::get('/user', function () {
        $user = auth()->guard('portal')->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'role' => $user->role ?? 'user',
            'session_id' => session()->getId(),
        ]);
    })->name('user');

    // Dashboard data
    Route::get('/dashboard', [App\Http\Controllers\Portal\Api\DashboardApiControllerEnhanced::class, 'index'])->name('dashboard.data');

    // Calls API
    Route::prefix('calls')->name('calls.')->group(function () {
        Route::get('/', [CallController::class, 'apiIndex'])->name('index');
        Route::get('/{call}', [CallController::class, 'apiShow'])->name('show');
        Route::get('/{call}/transcript', [CallController::class, 'transcript'])->name('transcript');
        Route::get('/{call}/summary', [CallController::class, 'summary'])->name('summary');
    });

    // Appointments API
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'apiIndex'])->name('index');
        Route::post('/', [AppointmentController::class, 'apiStore'])->name('store');
        Route::get('/{appointment}', [AppointmentController::class, 'apiShow'])->name('show');
        Route::put('/{appointment}', [AppointmentController::class, 'apiUpdate'])->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'apiDestroy'])->name('destroy');
    });

    // Notifications API
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Portal\Api\NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [App\Http\Controllers\Portal\Api\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [App\Http\Controllers\Portal\Api\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/preferences', [App\Http\Controllers\Portal\Api\NotificationController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [App\Http\Controllers\Portal\Api\NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });

    // Stats API
    Route::get('/stats', [App\Http\Controllers\Portal\Api\StatsController::class, 'index'])->name('stats');

    // Settings API
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Portal\Api\SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [App\Http\Controllers\Portal\Api\SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [App\Http\Controllers\Portal\Api\SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/notifications', [App\Http\Controllers\Portal\Api\SettingsController::class, 'updateNotifications'])->name('notifications.update');
    });
});

// ========================================
// 5. OPTIONAL AUTH API ROUTES (für React Bridge)
// ========================================
Route::prefix('business-api')->middleware(['web', 'portal.session'])->name('business.api.optional.')->group(function () {
    // Check auth status
    Route::get('/auth/check', function () {
        $user = auth()->guard('portal')->user();

        return response()->json([
            'authenticated' => ! is_null($user),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
            ] : null,
        ]);
    })->name('auth.check');
});

// ========================================
// 6. DEMO ROUTES (nur für Demo-Umgebung)
// ========================================
if (config('app.demo_mode', false)) {
    Route::prefix('business')->name('business.demo.')->group(function () {
        Route::get('/demo', [DemoController::class, 'dashboard'])->name('dashboard');
        Route::get('/demo/bypass', [DemoController::class, 'bypass'])->name('bypass');
    });
}

// Integrated Portal Route
Route::get('/business/portal', function () {
    return view('portal.business-integrated');
})->name('business.portal');

// ========================================
// 7. CATCH-ALL ROUTE FOR REACT SPA (MUST BE LAST!)
// ========================================
Route::prefix('business')->middleware(['web', 'portal.session'])->group(function () {
    Route::get('/{any?}', function () {
        return view('portal.react-app');
    })->where('any', '.*')->name('business.spa.catchall');
});
