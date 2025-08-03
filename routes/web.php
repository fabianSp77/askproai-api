<?php

// ULTRATHINK Auth Solution
// require __DIR__ . '/ultrathink-auth.php'; // Temporarily disabled - file missing

// Override 2FA routes to prevent 500 errors
// TEMPORARILY DISABLED - causing redirect loops
// Route::get('/business/two-factor/setup', function () {
//     return redirect('/business');
// })->name('business.two-factor.setup.override');

// Route::post('/business/two-factor/setup', function () {
//     return redirect('/business');
// })->name('business.two-factor.setup.post.override');

// Route::get('/business/two-factor/challenge', function () {
//     return redirect('/business');
// })->name('business.two-factor.challenge.override');

use App\Http\Controllers\Admin\CallCustomerAssignmentController;
use Illuminate\Support\Facades\Route;

// EMERGENCY FIX: Fixed login routes
// REMOVED FOR SECURITY

// React Admin Portal (wenn aktiviert)
if (config('app.admin_portal_react', false)) {
    Route::prefix('admin')->group(base_path('routes/admin-react.php'));
}

// Emergency auth routes - bypass everything
// REMOVED FOR SECURITY

// Debug authentication
Route::get('/auth-debug', function () {
    return response()->json([
        'auth_check' => auth()->check(),
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver(),
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_data' => session()->all(),
    ]);
});

// Root route - redirect to login
Route::get('/', function () {
    return redirect('/login');
});

// Unified Login Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\UnifiedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\UnifiedLoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\UnifiedLoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [App\Http\Controllers\UnifiedLoginController::class, 'logout']);
});

// Admin route - let Filament handle it
// Route removed - Filament will handle /admin route automatically

// Branch switching route
Route::post('/admin/branch/switch', [App\Http\Controllers\BranchSwitchController::class, 'switch'])
    ->middleware(['auth'])
    ->name('admin.branch.switch');

// Call customer assignment route
Route::post('/admin/calls/{call}/assign-customer', [CallCustomerAssignmentController::class, 'assign'])
    ->middleware(['auth'])
    ->name('admin.calls.assign-customer');

// Test ML Dashboard outside Filament
Route::get('/test-ml-dashboard', [App\Http\Controllers\TestMLController::class, 'index']);

// React Admin Portal Routes
Route::get('/admin-react-login', function () {
    return view('admin.react-app-complete');
});

// React Admin Portal - catch all routes
Route::get('/admin-react/{any?}', function () {
    return view('admin.react-admin-portal');
})->where('any', '.*');

Route::get('/admin/react-admin-portal', function () {
    return view('admin.react-admin-portal');
});

Route::get('/admin-test-appointments', function () {
    return view('admin.test-appointments');
})->middleware(['web', 'auth']);

// Documentation redirects to consolidate multiple locations
Route::get('/documentation/{any?}', [App\Http\Controllers\DocumentationRedirectController::class, 'redirect'])
    ->where('any', '.*');
Route::get('/docs/{any?}', [App\Http\Controllers\DocumentationRedirectController::class, 'redirect'])
    ->where('any', '.*');

// Main documentation landing page
Route::get('/documentation', [App\Http\Controllers\DocumentationRedirectController::class, 'index']);

// Filament dashboard routes fix removed - pages now use default route generation

// API Login routes are now in routes/api.php

// Public Topup Routes (no authentication required)
Route::prefix('topup')->name('public.topup.')->group(function () {
    Route::get('/{company}', [App\Http\Controllers\PublicTopupController::class, 'showTopupForm'])
        ->name('form');
    Route::post('/{company}', [App\Http\Controllers\PublicTopupController::class, 'processTopup'])
        ->name('process');
    Route::get('/{company}/success', [App\Http\Controllers\PublicTopupController::class, 'success'])
        ->name('success');
    Route::get('/{company}/cancel', [App\Http\Controllers\PublicTopupController::class, 'cancel'])
        ->name('cancel');
});

// API endpoint to generate topup links
Route::post('/api/generate-topup-link', [App\Http\Controllers\PublicTopupController::class, 'generateLink'])
    ->name('api.generate-topup-link');

// Retell Test Hub & Monitor Routes (available in all environments)
Route::get('/retell-test', function () {
    return view('retell-test-hub');
})->name('retell.test.hub');

Route::prefix('retell-monitor')->middleware(['web'])->group(function () {
    Route::get('/', function () {
        return view('basic-retell-monitor');
    })->name('retell.monitor.index');
    Route::get('/stats', [App\Http\Controllers\SimpleRetellMonitorController::class, 'stats'])
        ->name('retell.monitor.stats');
    Route::get('/calcom-status', [App\Http\Controllers\RetellMonitorController::class, 'calcomStatus'])
        ->name('retell.monitor.calcom-status');
    Route::get('/activity', [App\Http\Controllers\RetellMonitorController::class, 'activity'])
        ->name('retell.monitor.activity');
});

// Include help center routes
require __DIR__ . '/help-center.php';

// Include no-CSRF routes
require __DIR__ . '/no-csrf.php';

// Legal pages routes
Route::get('/privacy', [App\Http\Controllers\PrivacyController::class, 'privacy'])->name('privacy');
Route::get('/cookie-policy', [App\Http\Controllers\PrivacyController::class, 'cookiePolicy'])->name('cookie-policy');
Route::get('/terms', [App\Http\Controllers\PrivacyController::class, 'terms'])->name('terms');
Route::get('/impressum', [App\Http\Controllers\PrivacyController::class, 'impressum'])->name('impressum');

// Dashboard route - redirects to Filament admin panel
Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');

// Admin dashboard redirect
Route::get('/admin/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth'])->name('admin.dashboard');

// Invoice download route
Route::get('/invoice/{invoice}/download', function (App\Models\Invoice $invoice) {
    // Check if user has access to this invoice
    if (auth()->user()->company_id !== $invoice->company_id) {
        abort(403);
    }

    // For now, redirect to a placeholder
    // TODO: Implement actual PDF generation
    return response()->json([
        'message' => 'Invoice download not yet implemented',
        'invoice_id' => $invoice->id,
        'invoice_number' => $invoice->number,
    ]);
})->middleware(['auth'])->name('invoice.download');

// Profile routes (requires auth)
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/profile/edit', function () {
    return redirect('/admin/my-profile');
})->middleware(['auth'])->name('profile.edit');

// Portal routes (alias for business)
Route::get('/portal', function () {
    return redirect('/business');
});

Route::get('/portal/login', function () {
    return redirect('/business/login');
});

Route::post('/portal/login', [\App\Http\Controllers\Portal\Auth\LoginController::class, 'login'])
    ->name('portal.login.post');

// Logout route
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->middleware(['auth'])->name('logout');

// Test route for dashboard
Route::get('/test-dashboard', function () {
    return view('test-dashboard');
})->middleware('auth');

// CSRF test routes (for debugging)
Route::get('/csrf-test', function () {
    return view('csrf-test');
});

Route::post('/test-csrf', function () {
    return response()->json([
        'message' => 'CSRF token is valid!',
        'token' => csrf_token(),
    ]);
})->middleware('web');

// Test POST route
Route::post('/test-post', function () {
    return response()->json(['status' => 'POST route works']);
});

// Debug route
Route::get('/test-debug', function () {
    return view('test-debug');
});

// Livewire debug routes
Route::get('/livewire-debug', function () {
    return view('livewire-debug');
});

Route::get('/livewire-test', function () {
    return view('livewire-test');
});

Route::get('/test-livewire-check', function () {
    return view('test-livewire-check');
});

// Livewire routes are now registered in LivewireRouteFix provider

// Let Filament handle its own routes - no custom login routes!

// Temporary alternative login (non-Livewire)
Route::get('/temp-login', [App\Http\Controllers\TempLoginController::class, 'showLoginForm'])->name('temp.login');
Route::post('/temp-login', [App\Http\Controllers\TempLoginController::class, 'login'])->name('temp.login.submit');
Route::post('/temp-logout', [App\Http\Controllers\TempLoginController::class, 'logout'])->name('temp.logout');

// Debug routes
Route::get('/debug/session', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'session_domain' => config('session.domain'),
        'session_path' => config('session.path'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user(),
        'guards' => array_keys(config('auth.guards')),
        'default_guard' => config('auth.defaults.guard'),
    ]);
});

Route::post('/debug/login', function () {
    $credentials = [
        'email' => 'fabian@askproai.de',
        'password' => 'Qwe421as1!1',
    ];

    $result = Illuminate\Support\Facades\Auth::attempt($credentials);

    return response()->json([
        'attempt_result' => $result,
        'auth_check_after' => auth()->check(),
        'user_after' => auth()->user(),
        'session_id_after' => session()->getId(),
    ]);
});

Route::get('/debug/check-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
    ]);
})->middleware('web');

Route::get('/auth-debug', function () {
    return view('auth-debug');
});

Route::post('/debug/clear-logs', function () {
    file_put_contents(storage_path('logs/laravel.log'), '');

    return response()->json(['cleared' => true]);
});

// Livewire test page
Route::get('/livewire-test-page', function () {
    return view('livewire-test-page');
})->middleware(['web', 'auth']);

// Call sharing routes (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/calls/{call}/send-email', [App\Http\Controllers\CallShareController::class, 'sendCallEmail'])
        ->name('admin.calls.send-email');
});

// require __DIR__.'/test-errors.php';

// Customer Portal Routes - REMOVED (See Issue #464)
// All customer portal functionality has been removed.
// Use Business Portal (/business) instead for all portal needs.
Route::prefix('portal')->group(function () {
    // All portal routes now redirect to business portal
    Route::get('/calls/{id}', function ($id) {
        return redirect("/business/calls/{$id}", 301);
    });
    Route::get('/appointments/{id}', function ($id) {
        return redirect("/business/appointments/{$id}", 301);
    });
    Route::get('/customers/{id}', function ($id) {
        return redirect("/business/customers/{$id}", 301);
    });
    Route::get('/dashboard', function () {
        return redirect('/business/dashboard', 301);
    });
    Route::get('/', function () {
        return redirect('/business/', 301);
    });

    // Customer portal routes removed - was: require __DIR__ . '/portal.php';
});

// Business Portal Routes
require __DIR__ . '/business-portal.php';

// Admin API routes (web authenticated)
Route::middleware(['auth:web'])->prefix('admin-api')->group(function () {
    Route::post('/calls/{call}/translate-summary', [App\Http\Controllers\Admin\AdminApiController::class, 'translateCallSummary'])
        ->name('admin.api.calls.translate-summary');

    // Transaction exports
    Route::get('/transactions/export/csv', [App\Http\Controllers\Admin\TransactionExportController::class, 'exportCsv'])
        ->name('admin.api.transactions.export.csv');
    Route::get('/transactions/export/pdf', [App\Http\Controllers\Admin\TransactionExportController::class, 'exportPdf'])
        ->name('admin.api.transactions.export.pdf');
});

// Remove temporary debug route - let Livewire handle its own routes

// Removed - let Livewire handle its own routes

// Simple Livewire test
Route::get('/test-livewire-simple', function () {
    return view('test-livewire-simple');
})->middleware(['web']);
Route::get('/test-ml-livewire-page', function () {
    return view('test-ml-livewire-page');
});

// Debug route for Filament v3 issues
Route::get('/filament-debug', function () {
    return view('filament-debug');
})->middleware('auth');

// GDPR Routes
Route::prefix('gdpr')->group(function () {
    Route::post('/request-export', [App\Http\Controllers\GDPRController::class, 'requestDataExport'])
        ->name('gdpr.request-export');
    Route::post('/request-deletion', [App\Http\Controllers\GDPRController::class, 'requestDataDeletion'])
        ->name('gdpr.request-deletion');
    Route::get('/download/{token}', [App\Http\Controllers\GDPRController::class, 'downloadData'])
        ->name('gdpr.download');
    Route::get('/confirm-deletion/{token}', [App\Http\Controllers\GDPRController::class, 'confirmDeletion'])
        ->name('gdpr.confirm-deletion');
});

Route::get('/privacy-tools', [App\Http\Controllers\GDPRController::class, 'privacyTools'])
    ->name('privacy-tools');

// Temporary token login for testing
Route::get('/business/login-with-token', function (Request $request) {
    $token = $request->get('token');
    $userId = Illuminate\Support\Facades\Cache::pull('portal_login_token_' . $token);

    if (! $userId) {
        return redirect()->route('business.login')->with('error', 'Invalid or expired token');
    }

    $user = App\Models\PortalUser::find($userId);
    if (! $user || ! $user->is_active) {
        return redirect()->route('business.login')->with('error', 'User not found or inactive');
    }

    Illuminate\Support\Facades\Auth::guard('portal')->login($user);
    $user->recordLogin($request->ip());

    return redirect()->route('business.dashboard');
})->name('business.login-with-token');

// Admin viewing portal route
Route::get('/admin-view-portal/{session}', function ($session) {
    $data = Illuminate\Support\Facades\Cache::get('admin_viewing_' . $session);
    if (! $data) {
        return redirect('/business/login')->with('error', 'Invalid session');
    }

    // Set admin viewing session
    session(['is_admin_viewing' => true]);
    session(['admin_impersonation' => [
        'user_id' => 0,
        'company_id' => $data['company_id'],
        'company_name' => App\Models\Company::find($data['company_id'])->name,
        'admin_id' => $data['admin_id'],
    ]]);

    return redirect('/business/dashboard');
})->name('admin.view-portal');

// Test login page with credentials visible
Route::get('/portal-test-login', function () {
    return view('portal.test-login');
})->name('portal.test-login');

// React Admin Portal Routes - commented out duplicates (already defined above)
// Route::get('/admin-react-login', function () {
//     return view('admin.react-login');
// })->name('admin.react.login');

// Route::get('/admin-react-login-fixed', function () {
//     return view('admin.react-login-fixed');
// })->name('admin.react.login.fixed');

// Route::get('/admin-react', function () {
//     return view('admin.react-app-complete');
// })->name('admin.react.app');

// Include Livewire 404 fix routes
require __DIR__ . '/livewire-fix.php';

// Demo login route
require __DIR__ . '/demo-login.php';

// Session test routes for debugging
Route::get('/test-session-set-route', function () {
    session(['test_route' => 'Value set via route at ' . now()]);
    session(['counter' => session('counter', 0) + 1]);

    return response()->json([
        'success' => true,
        'session_id' => session()->getId(),
        'session_name' => session()->getName(),
        'data_set' => [
            'test_route' => session('test_route'),
            'counter' => session('counter'),
        ],
        'all_keys' => array_keys(session()->all()),
    ]);
})->name('test.session.set');

Route::get('/test-session-get-route', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'session_name' => session()->getName(),
        'test_route' => session('test_route'),
        'counter' => session('counter'),
        'all_data' => session()->all(),
    ]);
})->name('test.session.get');

// Debug routes (temporary) - DISABLED DUE TO MEMORY ISSUES
// require __DIR__ . '/debug-routes.php';

// Test routes (temporary)
// require __DIR__ . '/test-routes.php'; // Temporarily disabled - file missing

// Portal session debug route
Route::get('/portal-session-debug', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'session_name' => session()->getName(),
        'session_cookie' => request()->cookie('askproai_portal_session'),
        'portal_auth' => auth()->guard('portal')->check(),
        'portal_user' => auth()->guard('portal')->user(),
        'session_key' => 'login_portal_' . sha1(\App\Models\PortalUser::class),
        'session_has_key' => session()->has('login_portal_' . sha1(\App\Models\PortalUser::class)),
        'session_user_id' => session('login_portal_' . sha1(\App\Models\PortalUser::class)),
        'all_session_keys' => array_keys(session()->all()),
        'cookies' => array_keys(request()->cookies->all()),
        'middleware' => app()->router->getMiddleware(),
    ]);
})->middleware(['web']);

// Temporary admin bypass
// require __DIR__ . '/admin-bypass.php'; // Temporarily disabled - file missing

// Test API routes
// require __DIR__ . '/test-api.php'; // Temporarily disabled - file missing

// Direct login routes (bypass session migration issue)
Route::get('/direct-login', [App\Http\Controllers\DirectLoginController::class, 'login'])->name('direct.login');
Route::post('/api/direct-login', [App\Http\Controllers\DirectLoginController::class, 'apiLogin'])->name('api.direct.login');

// Debug route for session testing (remove in production)
if (app()->environment(['local', 'staging', 'production'])) { // Temporarily enabled for all environments
    require __DIR__ . '/debug-sessions.php';
    require __DIR__ . '/session-test.php';
}

// EMERGENCY TEST ROUTES
Route::get('/test-admin-auth', [App\Http\Controllers\SimpleAuthTestController::class, 'testAdminLogin']);
Route::get('/test-portal-auth', [App\Http\Controllers\SimpleAuthTestController::class, 'testPortalLogin']);

// ADMIN EMERGENCY ROUTES (with rate limiting)
require __DIR__ . '/admin-emergency.php';
