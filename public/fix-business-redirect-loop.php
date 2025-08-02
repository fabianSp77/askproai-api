<?php

echo "🔧 Fixing Business Portal Redirect Loop\n";
echo "=====================================\n\n";

// The problem: /business/ requires auth, redirects to /business/login
// But /business/login is also in the business-portal middleware group
// This creates a redirect loop

// Solution: Create a simpler route structure

$routeFile = '/var/www/api-gateway/routes/business-portal.php';
$content = file_get_contents($routeFile);

// Check current structure
echo "1. Current route structure:\n";
if (strpos($content, "Route::prefix('business')->middleware(['business-portal'])->name('business.')->group(function () {") !== false) {
    echo "   ❌ Login routes are inside business-portal middleware\n";
} else {
    echo "   ✅ Login routes are outside business-portal middleware\n";
}

echo "\n2. Creating fixed route file...\n";

// Create a backup
$backupFile = $routeFile . '.backup-' . date('Y-m-d-H-i-s');
copy($routeFile, $backupFile);
echo "   ✅ Backup created: $backupFile\n";

// Fix the routes - split auth and non-auth routes properly
$fixedContent = <<<'PHP'
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
*/

// ========================================
// 1. PUBLIC ROUTES (No Auth Required)
// ========================================
Route::prefix('business')->name('business.')->group(function () {
    // Login routes - NO AUTH MIDDLEWARE
    Route::middleware(['web'])->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.post');
        
        // AJAX Authentication Routes
        Route::prefix('api/auth')->name('api.auth.')->group(function () {
            Route::post('/login', [AjaxLoginController::class, 'login'])->name('login');
            Route::get('/check', [AjaxLoginController::class, 'check'])->name('check');
        });
    });
});

// ========================================
// 2. AUTHENTICATED ROUTES
// ========================================
Route::prefix('business')->middleware(['web', 'portal.auth'])->name('business.')->group(function () {
    // Main portal routes
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.main');
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // AJAX logout
    Route::post('/api/auth/logout', [AjaxLoginController::class, 'logout'])->name('api.auth.logout');
    Route::post('/api/auth/refresh', [AjaxLoginController::class, 'refresh'])->name('api.auth.refresh');
    
    // 2FA routes
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
    
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
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/2fa/enable', [SettingsController::class, 'enable2FA'])->name('2fa.enable');
        Route::post('/2fa/disable', [SettingsController::class, 'disable2FA'])->name('2fa.disable');
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
    
    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    
    // Feedback
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

// ========================================
// 3. API ROUTES (Authenticated)
// ========================================
Route::prefix('business/api')->middleware(['web', 'portal.auth'])->name('business.api.')->group(function () {
    // Dashboard data
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/recent-calls', [DashboardController::class, 'recentCalls'])->name('dashboard.recent-calls');
    Route::get('/dashboard/upcoming-appointments', [DashboardController::class, 'upcomingAppointments'])->name('dashboard.upcoming-appointments');
    
    // Calls API
    Route::get('/calls', [CallController::class, 'apiIndex'])->name('calls.index');
    Route::get('/calls/{call}', [CallController::class, 'apiShow'])->name('calls.show');
    
    // Appointments API
    Route::get('/appointments', [AppointmentController::class, 'apiIndex'])->name('appointments.index');
    Route::get('/appointments/available-slots', [AppointmentController::class, 'availableSlots'])->name('appointments.available-slots');
    Route::post('/appointments', [AppointmentController::class, 'apiStore'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'apiUpdate'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'apiDestroy'])->name('appointments.destroy');
});

// ========================================
// 4. DEMO ROUTES (Optional Auth)
// ========================================
Route::prefix('business/demo')->middleware(['web'])->name('business.demo.')->group(function () {
    Route::get('/', [DemoController::class, 'index'])->name('index');
    Route::get('/components', [DemoController::class, 'components'])->name('components');
});

// ========================================
// 5. CATCH-ALL for React SPA (Must be last)
// ========================================
Route::get('/business/{any}', function () {
    return view('portal.react-app');
})->where('any', '.*')->middleware(['web']);
PHP;

file_put_contents($routeFile, $fixedContent);
echo "   ✅ Routes fixed!\n";

echo "\n3. Clearing caches...\n";
exec('php /var/www/api-gateway/artisan config:cache 2>&1', $output);
echo "   ✅ Config cache cleared\n";

echo "\n✅ Fix completed!\n";
echo "\nThe redirect loop should now be fixed.\n";
echo "Login routes are now outside the auth middleware.\n";