<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\HealthMonitorController;
use App\Http\Controllers\CustomLoginController;
use App\Http\Controllers\DirectLoginController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', [App\Http\Controllers\HealthCheckController::class, '__invoke']);

// Filament login redirect (fixes "Route [login] not defined" error)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Custom login routes (temporary fix for missing form inputs)
Route::get('/admin/login-fix', [CustomLoginController::class, 'showLoginForm'])->name('custom.login');
Route::post('/admin/login-fix', [CustomLoginController::class, 'login'])->name('custom.login.submit');

// Direct login routes (bypass Livewire completely)
Route::get('/admin/direct-login', [DirectLoginController::class, 'showLoginForm'])->name('direct.login');
Route::post('/admin/direct-login', [DirectLoginController::class, 'login'])->name('direct.login.submit');

// Morphing Navigation Test
Route::get('/test-morphing-nav', function () {
    return view('test-morphing-navigation');
});

// Test route for transaction display
Route::get('/test-transaction/{id}', function ($id) {
    $transaction = \App\Models\Transaction::with(['tenant', 'call', 'appointment', 'topup'])->findOrFail($id);
    return view('test-transaction-simple', compact('transaction'));
});

// Health Monitor Dashboard
Route::prefix('health-monitor')->group(function () {
    Route::get('/', [HealthMonitorController::class, 'dashboard'])->name('health-monitor.dashboard');
    Route::post('/check', [HealthMonitorController::class, 'check'])->name('health-monitor.check');
    Route::post('/clear-cache', [HealthMonitorController::class, 'clearCache'])->name('health-monitor.clear-cache');
});

// Test RetellAgent
Route::get('/test-retell-agent/{id}', function ($id) {
    $agent = \App\Models\RetellAgent::with('company')->find($id);
    if (!$agent) {
        return 'Agent not found';
    }
    return view('test-retell-agent', compact('agent'));
});


// Billing Routes (protected)
Route::middleware(['auth'])->prefix('billing')->group(function () {
    Route::get('/', [App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::get('/transactions', [App\Http\Controllers\BillingController::class, 'transactions'])->name('billing.transactions');
    Route::get('/topup', [App\Http\Controllers\BillingController::class, 'topup'])->name('billing.topup');
    Route::get('/checkout', [App\Http\Controllers\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/success', function () {
        return view('billing.success', ['message' => 'Zahlung erfolgreich! Ihr Guthaben wurde aufgeladen.']);
    })->name('billing.success');
    Route::get('/cancel', function () {
        return view('billing.cancel', ['message' => 'Zahlung abgebrochen. Sie können es jederzeit erneut versuchen.']);
    })->name('billing.cancel');
});

// Stripe Webhook (no auth required)
Route::post('/billing/webhook', [App\Http\Controllers\BillingController::class, 'webhook'])
    ->name('billing.webhook')
    ->withoutMiddleware(['auth', 'csrf']);

// Include Customer Portal Routes
require __DIR__.'/customer.php';

// Backup Monitor Dashboard (protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/backup-monitor', [App\Http\Controllers\BackupMonitorController::class, 'index']);
    Route::get('/admin/backup-monitor/status', [App\Http\Controllers\BackupMonitorController::class, 'status']);
    
    // Navigation redirects for common admin paths
    Route::get('/admin/profile', function () {
        $user = auth()->user();
        if ($user) {
            return redirect("/admin/users/{$user->id}/edit");
        }
        return redirect('/admin/users');
    })->name('admin.profile');
    
    Route::get('/admin/settings', function () {
        return redirect('/admin/integrations');
    })->name('admin.settings');
    
    Route::get('/admin/help', function () {
        return view('admin.help');
    })->name('admin.help');
});

// Flowbite Component Test Page
Route::get('/test/flowbite-all', function () {
    return view('flowbite-test-all');
});

// Placeholder image route for missing Flowbite demo images
Route::get('/placeholder-images/{path}', function ($path) {
    $fullPath = public_path('images/' . $path);
    
    // If the actual image exists, serve it
    if (file_exists($fullPath)) {
        return response()->file($fullPath);
    }
    
    // Otherwise, serve a placeholder
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $type = 'placeholder';
    
    if (str_contains($path, 'user') || str_contains($path, 'avatar')) {
        $type = 'avatar';
    } elseif (str_contains($path, 'logo')) {
        $type = 'logo';
    } elseif (str_contains($path, 'music') || str_contains($path, 'image')) {
        $type = 'photo';
    }
    
    // Generate a simple SVG placeholder
    $width = $type === 'avatar' ? 150 : 400;
    $height = $type === 'avatar' ? 150 : 300;
    $color = match($type) {
        'avatar' => '#6366f1',
        'logo' => '#ec4899',
        'photo' => '#3b82f6',
        default => '#9ca3af'
    };
    $label = strtoupper($type);
    
    $svg = <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
        <rect width="{$width}" height="{$height}" fill="{$color}"/>
        <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="white" font-family="Arial, sans-serif" font-size="24" font-weight="bold">{$label}</text>
        <text x="50%" y="70%" text-anchor="middle" fill="white" font-family="Arial, sans-serif" font-size="14">{$width}×{$height}</text>
    </svg>
    SVG;
    
    return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
})->where('path', '.*');

// Flowbite Component Preview Route
Route::get('/admin/flowbite-preview', function () {
    try {
        $componentPath = request('path');
        
        // Security check - ensure path is within allowed directories
        if (!$componentPath || !str_contains($componentPath, '/resources/views/components/flowbite')) {
            return response('Invalid component path', 403);
        }
        
        // Convert file path to Blade component name
        $relativePath = str_replace('/var/www/api-gateway/resources/views/components/', '', $componentPath);
        $relativePath = str_replace('.blade.php', '', $relativePath);
        $componentName = str_replace('/', '.', $relativePath);
        
        // Check for special components that shouldn't be rendered directly
        $basename = basename($relativePath);
        if (str_starts_with($basename, '_')) {
            // Special component - show informative message instead
            return view('flowbite-preview-special', [
                'componentName' => $componentName,
                'reason' => 'dashboard'
            ]);
        }
        
        // Check file size
        $fileSize = filesize($componentPath);
        
        // Check for empty stub files
        if ($fileSize < 200) {
            return view('flowbite-preview-special', [
                'componentName' => $componentName,
                'reason' => 'stub',
                'size' => $fileSize . ' bytes'
            ]);
        }
        
        // Check for large components (likely full pages)
        if ($fileSize > 50000) { // 50KB threshold
            return view('flowbite-preview-special', [
                'componentName' => $componentName,
                'reason' => 'large',
                'size' => number_format($fileSize / 1024, 2) . ' KB'
            ]);
        }
        
        // Check if view exists
        if (!View::exists('components.' . $componentName)) {
            return response('Component not found: ' . $componentName, 404);
        }
        
        // Try to render the component with error handling
        try {
            return view('flowbite-preview', ['componentName' => $componentName]);
        } catch (\Throwable $renderError) {
            // Component exists but can't be rendered - show special error page
            \Log::error('Flowbite component render error', [
                'component' => $componentName,
                'error' => $renderError->getMessage()
            ]);
            
            return view('flowbite-preview-error', [
                'componentName' => $componentName,
                'error' => 'This component requires additional dependencies or context that are not available in preview mode.'
            ]);
        }
    } catch (\Exception $e) {
        return response('Error loading component: ' . $e->getMessage(), 500);
    }
})->middleware('web');
