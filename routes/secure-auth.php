<?php

use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Middleware\AuthenticationRateLimiter;
use App\Http\Middleware\SecureAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Secure Unified Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle authentication for all portals (admin, business, customer)
| with complete security measures including rate limiting and 2FA
|
*/

// Unified Login Routes (can be used by any portal)
Route::middleware(['web', AuthenticationRateLimiter::class])->group(function () {
    
    // Two-Factor Authentication routes
    Route::prefix('auth/two-factor')->name('auth.two-factor.')->group(function () {
        Route::get('/setup', [UnifiedLoginController::class, 'showTwoFactorSetup'])->name('setup');
        Route::post('/setup/confirm', [UnifiedLoginController::class, 'confirmTwoFactorSetup'])->name('confirm-setup');
        Route::get('/challenge', [UnifiedLoginController::class, 'showTwoFactorChallenge'])->name('challenge');
        Route::post('/verify', [UnifiedLoginController::class, 'verifyTwoFactor'])->name('verify');
    });
});

/*
|--------------------------------------------------------------------------
| Portal-Specific Authentication Routes
|--------------------------------------------------------------------------
|
| These routes provide portal-specific login pages while using the
| unified authentication controller
|
*/

// Admin Portal Authentication
Route::prefix('admin')->name('admin.')->middleware(['web', AuthenticationRateLimiter::class])->group(function () {
    Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');
});

// Business Portal Authentication - Enhanced with 2FA support
Route::prefix('business')->name('business.')->middleware(['web', AuthenticationRateLimiter::class])->group(function () {
    Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');
});

// Customer Portal Authentication
Route::prefix('customer')->name('customer.')->middleware(['web', AuthenticationRateLimiter::class])->group(function () {
    Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| API Authentication Routes
|--------------------------------------------------------------------------
|
| API endpoints for authentication with JSON responses
|
*/

Route::prefix('api/v2/auth')->name('api.auth.')->middleware(['api', AuthenticationRateLimiter::class])->group(function () {
    
    // API Login/Logout
    Route::post('/login', [UnifiedLoginController::class, 'login'])->name('login');
    Route::post('/logout', [UnifiedLoginController::class, 'logout'])->name('logout')->middleware('auth:sanctum');
    
    // API 2FA endpoints
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::post('/setup', [UnifiedLoginController::class, 'setupTwoFactor'])->name('setup');
        Route::post('/setup/confirm', [UnifiedLoginController::class, 'confirmTwoFactorSetup'])->name('confirm-setup');
        Route::post('/verify', [UnifiedLoginController::class, 'verifyTwoFactor'])->name('verify');
    });
    
    // User information (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function () {
            $user = auth()->user();
            return response()->json([
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'portal_type' => $user->portal_type,
                'company_id' => $user->company_id,
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'two_factor_enforced' => $user->requires2FA(),
            ]);
        })->name('user');
        
        Route::get('/company', function () {
            $user = auth()->user();
            $company = $user->getSecureCompany();
            
            if (!$company) {
                return response()->json(['error' => 'No company assigned'], 404);
            }
            
            return response()->json([
                'id' => $company->id,
                'name' => $company->name,
                'is_active' => $company->is_active,
            ]);
        })->name('company');
    });
});

/*
|--------------------------------------------------------------------------
| Protected Dashboard Routes (Examples)
|--------------------------------------------------------------------------
|
| These routes demonstrate the secure middleware usage
|
*/

// Admin Dashboard
Route::prefix('admin')->name('admin.')->middleware(['web', SecureAuthMiddleware::class . ':web'])->group(function () {
    Route::get('/secure-dashboard', function () {
        return view('admin.dashboard', [
            'user' => auth()->user(),
            'company' => auth()->user()->getSecureCompany()
        ]);
    })->name('secure-dashboard');
});

// Business Dashboard  
Route::prefix('business')->name('business.')->middleware(['web', SecureAuthMiddleware::class . ':web'])->group(function () {
    Route::get('/secure-dashboard', function () {
        return view('business.dashboard', [
            'user' => auth()->user(),
            'company' => auth()->user()->getSecureCompany()
        ]);
    })->name('secure-dashboard');
});

/*
|--------------------------------------------------------------------------
| Development Routes (Only in non-production environments)
|--------------------------------------------------------------------------
|
| These routes are only available in development/testing environments
|
*/

if (!app()->environment('production')) {
    Route::prefix('dev-auth')->name('dev.auth.')->middleware(['web'])->group(function () {
        
        // Quick login for testing (bypasses 2FA)
        Route::post('/quick-login/{userId}', function ($userId) {
            $user = \App\Models\User::withoutGlobalScopes()->find($userId);
            if ($user && $user->hasValidCompany()) {
                auth()->login($user);
                \App\Scopes\SecureTenantScope::setCompanyContext($user->company_id);
                session(['company_id' => $user->company_id]);
                
                return redirect($user->getDefaultPortalRoute());
            }
            
            return redirect('/login')->withErrors(['email' => 'Invalid test user']);
        })->name('quick-login');
        
        // Reset user lockout for testing
        Route::post('/unlock/{userId}', function ($userId) {
            $user = \App\Models\User::withoutGlobalScopes()->find($userId);
            if ($user) {
                $user->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null
                ]);
                
                return response()->json(['message' => 'User unlocked']);
            }
            
            return response()->json(['error' => 'User not found'], 404);
        })->name('unlock');
        
        // Debug authentication state
        Route::get('/debug-auth', function () {
            return response()->json([
                'authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'session_company' => session('company_id'),
                'tenant_context' => \App\Scopes\SecureTenantScope::getCurrentCompanyId(),
                'guards' => [
                    'web' => auth()->guard('web')->check(),
                    'portal' => auth()->guard('portal')->check() ?? false,
                ]
            ]);
        })->name('debug');
    });
}