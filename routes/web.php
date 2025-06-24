<?php

use Illuminate\Support\Facades\Route;

// Test route to verify application is working
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'time' => now()->toISOString(),
        'user' => auth()->check() ? auth()->user()->email : 'not logged in'
    ]);
});

// Debug authentication
Route::get('/auth-debug', function () {
    return response()->json([
        'auth_check' => auth()->check(),
        'user' => auth()->user(),
        'guard' => auth()->getDefaultDriver(),
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_data' => session()->all()
    ]);
});

// Root route - redirect to admin
Route::get('/', function () {
    return redirect('/admin');
});

// Documentation redirects to consolidate multiple locations
Route::get('/documentation/{any?}', [App\Http\Controllers\DocumentationRedirectController::class, 'redirect'])
    ->where('any', '.*');
Route::get('/docs/{any?}', [App\Http\Controllers\DocumentationRedirectController::class, 'redirect'])
    ->where('any', '.*');

// Main documentation landing page
Route::get('/documentation', [App\Http\Controllers\DocumentationRedirectController::class, 'index']);

// Filament dashboard routes fix removed - pages now use default route generation

// Include help center routes
require __DIR__.'/help-center.php';

// Legal pages routes
Route::get('/privacy', [App\Http\Controllers\PrivacyController::class, 'privacy'])->name('privacy');
Route::get('/cookie-policy', [App\Http\Controllers\PrivacyController::class, 'cookiePolicy'])->name('cookie-policy');
Route::get('/terms', [App\Http\Controllers\PrivacyController::class, 'terms'])->name('terms');
Route::get('/impressum', [App\Http\Controllers\PrivacyController::class, 'impressum'])->name('impressum');

// Dashboard route - redirects to Filament admin panel
Route::get('/dashboard', function () {
    return redirect('/admin');
})->middleware(['auth'])->name('dashboard');

// Profile routes (requires auth)
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/profile/edit', function () {
    return redirect('/admin/my-profile');
})->middleware(['auth'])->name('profile.edit');

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
        'token' => csrf_token()
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

// Fallback for GET requests to Livewire update endpoint
Route::get('/livewire/update', function () {
    \Log::warning('GET request to /livewire/update detected', [
        'user_agent' => request()->userAgent(),
        'referer' => request()->header('referer'),
        'ip' => request()->ip(),
    ]);
    
    return response()->json([
        'message' => 'Method not allowed. This endpoint only accepts POST requests.',
        'hint' => 'If you are seeing this error, there might be an issue with Livewire JavaScript loading.'
    ], 405);
})->name('livewire.update.get');

// Let Filament handle its own routes - no custom login routes!

// Temporary alternative login (non-Livewire)
Route::get('/temp-login', [\App\Http\Controllers\TempLoginController::class, 'showLoginForm'])->name('temp.login');
Route::post('/temp-login', [\App\Http\Controllers\TempLoginController::class, 'login'])->name('temp.login.submit');
Route::post('/temp-logout', [\App\Http\Controllers\TempLoginController::class, 'logout'])->name('temp.logout');

// Simple login test
Route::post('/simple-login', [\App\Http\Controllers\SimpleLoginController::class, 'login'])->name('simple.login');

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
        'password' => 'Qwe421as1!1'
    ];
    
    $result = \Illuminate\Support\Facades\Auth::attempt($credentials);
    
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

// Debug login routes
Route::get('/debug-login', [\App\Http\Controllers\DebugLoginController::class, 'showForm']);
Route::post('/debug-login/attempt', [\App\Http\Controllers\DebugLoginController::class, 'attemptLogin']);

// Livewire test page
Route::get('/livewire-test-page', function() {
    return view('livewire-test-page');
})->middleware(['web', 'auth']);

// Call sharing routes (requires auth)
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/calls/{call}/send-email', [\App\Http\Controllers\CallShareController::class, 'sendCallEmail'])
        ->name('admin.calls.send-email');
});

require __DIR__.'/test-errors.php';

// Customer Portal Routes
Route::prefix('portal')->group(function () {
    require __DIR__.'/portal.php';
});
require __DIR__.'/test-livewire.php';
require __DIR__.'/livewire-test.php';

// Remove temporary debug route - let Livewire handle its own routes

// Removed - let Livewire handle its own routes

// Simple Livewire test
Route::get('/test-livewire-simple', function() {
    return view('test-livewire-simple');
})->middleware(['web']);
