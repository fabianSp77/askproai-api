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

// CI/CD Health Endpoints sind weiter unten mit Bearer-Token abgesichert (Zeilen 327-344)

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
Route::prefix('docs/backup-system')->middleware('docs.nocache')->group(function () {
    // Login form (no auth required)
    Route::get('/login', [\App\Http\Controllers\DocsAuthController::class, 'showLogin'])
        ->name('docs.backup-system.login');

    // Handle login (no auth required)
    Route::post('/login', [\App\Http\Controllers\DocsAuthController::class, 'login'])
        ->name('docs.backup-system.login.submit');

    // Logout (no auth required)
    Route::post('/logout', [\App\Http\Controllers\DocsAuthController::class, 'logout'])
        ->name('docs.backup-system.logout');

    // Static assets (CSS, JS, images) - no auth required so browser can load them
    // Supports both root-level (/assets/...) and subdirectory assets (.../assets/...)
    Route::get('/{path}/assets/{asset}', function ($path, $asset) {
        // Only allow static assets
        $allowedExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'];
        $extension = strtolower(pathinfo($asset, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            abort(404);
        }

        // Security: Prevent path traversal
        if (str_contains($path, '..') || str_contains($asset, '..') || str_contains($path, '\\') || str_contains($asset, '\\')) {
            abort(403, 'Invalid file path');
        }

        $basePath = storage_path('docs/backup-system');
        $filePath = $basePath . '/' . $path . '/assets/' . $asset;

        // Ensure the resolved path is within the base directory
        $realBasePath = realpath($basePath);
        $realFilePath = realpath($filePath);

        if (!$realFilePath || !str_starts_with($realFilePath, $realBasePath . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'Asset not found');
        }

        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath),
            'Cache-Control' => 'public, max-age=31536000', // 1 year cache for static assets
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->where('path', '.+')->name('docs.backup-system.subdirectory-assets');

    // Root-level assets (backward compatibility)
    Route::get('/assets/{asset}', function ($asset) {
        // Only allow static assets
        $allowedExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'svg', 'gif', 'webp'];
        $extension = strtolower(pathinfo($asset, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            abort(404);
        }

        // Security: Prevent path traversal
        if (str_contains($asset, '..') || str_contains($asset, '/') || str_contains($asset, '\\')) {
            abort(403, 'Invalid file path');
        }

        $filePath = storage_path('docs/backup-system/' . $asset);

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'Asset not found');
        }

        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath),
            'Cache-Control' => 'public, max-age=31536000', // 1 year cache for static assets
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->name('docs.backup-system.assets');
});

// Backup System Documentation Hub - Protected Routes (Laravel Session Auth)
Route::prefix('docs/backup-system')->middleware(['docs.nocache', 'docs.auth'])->group(function () {
    // Main hub page
    Route::get('/', function () {
        $indexPath = storage_path('docs/backup-system/index.html');
        if (!file_exists($indexPath)) {
            abort(404, 'Documentation hub not found');
        }

        // Get HTML content
        $html = file_get_contents($indexPath);

        // Inject logout button if authenticated (NGINX Basic Auth)
        $username = request()->server('PHP_AUTH_USER');
        if ($username) {
            // Improved logout button integrated into accessibility bar
            $logoutButton = '
            <div class="user-info" style="margin-left: auto; display: flex; align-items: center; gap: 0.8rem; padding-left: 1rem; border-left: 1px solid rgba(255,255,255,0.2);">
                <span style="color: var(--text-sidebar, #ecf0f1); font-size: 0.9em; display: flex; align-items: center; gap: 0.4rem;">
                    <span class="mdi mdi-account-circle" style="font-size: 1.3em;"></span>
                    <span class="username-text">' . htmlspecialchars($username) . '</span>
                </span>
                <form method="POST" action="' . url('/docs/backup-system/logout') . '" style="margin: 0; display: inline;">
                    ' . csrf_field() . '
                    <button type="submit" title="Abmelden" aria-label="Abmelden" style="background: rgba(231, 76, 60, 0.9); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.85em; font-weight: 500; display: inline-flex; align-items: center; gap: 0.4rem; min-height: 44px; transition: all 0.2s ease;">
                        <span class="mdi mdi-logout"></span>
                        <span class="logout-text">Abmelden</span>
                    </button>
                </form>
            </div>

            <style>
                /* Responsive logout button */
                @media (max-width: 768px) {
                    .user-info .username-text { display: none; }
                    .user-info .logout-text { display: none; }
                    .user-info { padding-left: 0.5rem; gap: 0.5rem; }
                    .user-info button { padding: 0.5rem; min-width: 44px; justify-content: center; }
                }

                @media (max-width: 480px) {
                    .accessibility-bar {
                        flex-wrap: wrap;
                        padding: 0.4rem 0.5rem;
                        gap: 0.5rem;
                    }
                    .user-info {
                        border-left: none;
                        width: 100%;
                        justify-content: flex-end;
                        padding-left: 0;
                    }
                }

                .user-info button:hover {
                    background: rgba(231, 76, 60, 1);
                    transform: translateY(-1px);
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                }

                .user-info button:active {
                    transform: translateY(0);
                }

                .user-info button:focus {
                    outline: 2px solid var(--warning, #f39c12);
                    outline-offset: 2px;
                }
            </style>';

            // Inject into accessibility bar (before closing </div> of accessibility-bar)
            $html = str_replace('</div><!-- accessibility-bar end -->', $logoutButton . '</div><!-- accessibility-bar end -->', $html);

            // Fallback: If accessibility-bar marker not found, inject before first closing div after accessibility-bar class
            if (strpos($html, $logoutButton) === false) {
                // Try alternative: inject after the last button in accessibility-bar
                $html = preg_replace(
                    '/(<div class="accessibility-bar"[^>]*>.*?)(<\/div>)/s',
                    '$1' . $logoutButton . '$2',
                    $html,
                    1
                );
            }
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net;",
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
                } elseif (str_contains($filename, 'E2E') || str_contains($filename, 'WORKFLOW_HARDENING') || str_contains($filename, 'GATE_VALIDATION')) {
                    $category = 'E2E Validation Reports';
                } elseif (str_contains($filename, 'BACKUP') || str_contains($filename, 'PITR') || str_contains($filename, 'Zero-Loss') || str_contains($filename, 'NAS')) {
                    $category = 'Backup & PITR';
                } elseif (str_contains($filename, 'DEPLOY') || str_contains($filename, 'deployment-release') || str_contains($filename, 'STAGING') || str_contains($filename, 'BRANCH') || str_contains($filename, 'PROTECTION')) {
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

    // API: Get incident history
    Route::get('/api/incidents', function () {
        $incidentFile = '/var/backups/askproai/incidents.json';

        if (!file_exists($incidentFile)) {
            return response()->json([
                'status' => 'success',
                'incidents' => [],
                'stats' => [
                    'total' => 0,
                    'critical' => 0,
                    'high' => 0,
                    'medium' => 0,
                    'low' => 0,
                    'info' => 0,
                ]
            ]);
        }

        $data = json_decode(file_get_contents($incidentFile), true);

        return response()->json([
            'status' => 'success',
            'incidents' => $data['incidents'] ?? [],
            'stats' => $data['stats'] ?? [],
        ], 200, [
            'Content-Security-Policy' => "default-src 'none';",
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    })->name('docs.backup-system.api.incidents');

    // Serve incident markdown files
    Route::get('/incidents/{incidentId}', function ($incidentId) {
        // Security: Validate incident ID format (INC-YYYYMMDDHHMMSS-XXXXXX.md)
        if (!preg_match('/^INC-\d{14}-[A-Za-z0-9]{6}\.md$/', $incidentId)) {
            abort(403, 'Invalid incident ID format');
        }

        $filePath = storage_path('docs/backup-system/incidents/' . $incidentId);

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'Incident documentation not found');
        }

        return response()->file($filePath, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . $incidentId . '"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    })->name('docs.backup-system.incidents.show');

    // Serve individual documentation files (supports subdirectories)
    Route::get('/{file}', function ($file) {
        // Security: Prevent path traversal
        if (str_contains($file, '..') || str_contains($file, '\\')) {
            abort(403, 'Invalid file path');
        }

        $basePath = storage_path('docs/backup-system');
        $filePath = $basePath . '/' . $file;

        // Ensure the resolved path is within the base directory
        $realBasePath = realpath($basePath);
        $realFilePath = realpath($filePath);

        if (!$realFilePath || !str_starts_with($realFilePath, $realBasePath . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, 'File not found');
        }

        // Only allow specific file types
        $allowedExtensions = ['html', 'pdf', 'json', 'md', 'css', 'js', 'png', 'jpg', 'svg', 'yaml'];
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            abort(403, 'File type not allowed');
        }

        return response()->file($filePath, [
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self' https://cdn.jsdelivr.net;",
            'X-Frame-Options' => 'DENY',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })->name('docs.backup-system.file')->where('file', '.+');
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

// --- CI/CD Health Endpoints (Bearer Token) ---
Route::get('/health', function (Illuminate\Http\Request $request) {
    $token = $request->bearerToken();
    abort_unless($token && hash_equals(env('HEALTHCHECK_TOKEN', ''), $token), 401);
    return response()->json([
        'status' => 'healthy',
        'env'    => app()->environment(),
    ], 200);
});

Route::get('/api/health-check', function (Illuminate\Http\Request $request) {
    $token = $request->bearerToken();
    abort_unless($token && hash_equals(env('HEALTHCHECK_TOKEN', ''), $token), 401);
    return response()->json([
        'status'  => 'healthy',
        'service' => 'api',
    ], 200);
});

require __DIR__.'/auth.php';
require __DIR__.'/web-test.php';
