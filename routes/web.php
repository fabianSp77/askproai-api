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

// Backup System Documentation Hub - Login Routes (NO AUTH)
Route::prefix('docs/backup-system')->group(function () {
    // Login form (no auth required)
    Route::get('/login', [\App\Http\Controllers\DocsAuthController::class, 'showLogin'])
        ->name('docs.backup-system.login');

    // Handle login (no auth required)
    Route::post('/login', [\App\Http\Controllers\DocsAuthController::class, 'login'])
        ->name('docs.backup-system.login.submit');

    // Logout (no auth required)
    Route::post('/logout', [\App\Http\Controllers\DocsAuthController::class, 'logout'])
        ->name('docs.backup-system.logout');
});

// Backup System Documentation Hub - Protected Routes (Basic Auth)
Route::middleware(['docs.auth'])->prefix('docs/backup-system')->group(function () {
    // Main hub page
    Route::get('/', function () {
        $indexPath = storage_path('docs/backup-system/index.html');
        if (!file_exists($indexPath)) {
            abort(404, 'Documentation hub not found');
        }

        // Get HTML content
        $html = file_get_contents($indexPath);

        // Inject logout button if authenticated
        $username = \App\Http\Controllers\DocsAuthController::getUsername();
        if ($username) {
            $logoutButton = '
            <div style="position: fixed; top: 10px; right: 10px; background: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;">
                <span style="color: #666; font-size: 14px;">👤 ' . htmlspecialchars($username) . '</span>
                <form method="POST" action="' . url('/docs/backup-system/logout') . '" style="margin: 0;">
                    ' . csrf_field() . '
                    <button type="submit" style="background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500;">
                        Abmelden
                    </button>
                </form>
            </div>';

            // Inject before closing </body> tag
            $html = str_replace('</body>', $logoutButton . '</body>', $html);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;",
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'no-referrer',
        ]);
    })->name('docs.backup-system.index');

    // API: List all documentation files
    Route::get('/api/files', function () {
        $docsPath = storage_path('docs/backup-system');

        if (!is_dir($docsPath)) {
            return response()->json(['status' => 'error', 'message' => 'Documentation directory not found'], 404);
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($docsPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($docsPath . '/', '', $file->getPathname());

                // Skip hidden files and .htpasswd
                if (str_starts_with($file->getFilename(), '.')) {
                    continue;
                }

                // Determine category based on filename
                $filename = $file->getFilename();
                $category = 'Other';

                if (str_contains($filename, 'index') || str_contains($filename, 'INDEX')) {
                    $category = 'Hub & Index';
                } elseif (str_contains($filename, 'EXECUTIVE') || str_contains($filename, 'SUMMARY')) {
                    $category = 'Executive / Management';
                } elseif (str_contains($filename, 'BACKUP') || str_contains($filename, 'PITR') || str_contains($filename, 'Zero-Loss') || str_contains($filename, 'NAS')) {
                    $category = 'Backup & PITR';
                } elseif (str_contains($filename, 'DEPLOY') || str_contains($filename, 'deployment-release') || str_contains($filename, 'STAGING')) {
                    $category = 'Deployment & Gates';
                } elseif (str_contains($filename, 'TEST') || str_contains($filename, 'VALIDATION') || str_contains($filename, 'VERIFICATION')) {
                    $category = 'Testing & Validation';
                } elseif (str_contains($filename, 'EMAIL') || str_contains($filename, 'NOTIFICATION')) {
                    $category = 'E-Mail & Notifications';
                } elseif (str_contains($filename, 'INCIDENT') || str_contains($filename, 'RCA') || str_contains($filename, 'FIX') || str_contains($filename, 'DEBUGGING')) {
                    $category = 'Incident Reports & Fixes';
                } elseif (str_contains($filename, 'SECURITY') || str_contains($filename, 'AUTH')) {
                    $category = 'Security & Access';
                } elseif (str_contains($filename, 'UX') || str_contains($filename, 'DATE_PARSING') || str_contains($filename, 'DOCUMENTATION_HUB')) {
                    $category = 'UX & Documentation';
                } elseif ($filename === 'status.json') {
                    $category = 'Hub & Index';
                }

                $mtime = $file->getMTime();
                $ctime = $file->getCTime();
                $ageDays = floor((time() - $mtime) / 86400);

                $files[] = [
                    'path' => $relativePath,
                    'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'category' => $category,
                    'size' => $file->getSize(),
                    'mtime' => $mtime,
                    'ctime' => $ctime,
                    'age_days' => $ageDays,
                    'sha256' => hash_file('sha256', $file->getPathname()),
                    'type' => $file->getExtension(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'count' => count($files),
            'files' => $files,
        ], 200, [
            'Content-Security-Policy' => "default-src 'none';",
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    })->name('docs.backup-system.api.files');

    // Serve individual documentation files
    Route::get('/{file}', function ($file) {
        // Security: Prevent path traversal
        if (str_contains($file, '..') || str_contains($file, '/') || str_contains($file, '\\')) {
            abort(403, 'Invalid file path');
        }

        $filePath = storage_path('docs/backup-system/' . $file);

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'File not found');
        }

        // Only allow specific file types
        $allowedExtensions = ['html', 'pdf', 'json', 'md', 'css', 'js', 'png', 'jpg', 'svg'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            abort(403, 'File type not allowed');
        }

        return response()->file($filePath, [
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;",
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->name('docs.backup-system.file')->where('file', '[^/]+');
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


require __DIR__.'/auth.php';
require __DIR__.'/web-test.php';
