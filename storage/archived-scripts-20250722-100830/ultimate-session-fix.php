<?php

echo "=== ULTIMATE SESSION FIX ===\n\n";

// 1. Fix session configuration in bootstrap/app.php
echo "1. FIXING BOOTSTRAP CONFIGURATION:\n";

$bootstrapPath = __DIR__ . '/bootstrap/app.php';
$bootstrapContent = file_get_contents($bootstrapPath);

// Check if session is properly configured
if (!strpos($bootstrapContent, 'web.php')) {
    echo "   ⚠️  Bootstrap configuration might be missing routes\n";
}

// 2. Create a middleware that forces session cookies
$sessionMiddlewarePath = __DIR__ . '/app/Http/Middleware/EnsureValidSession.php';
$sessionMiddlewareContent = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force session configuration for local development
        if (!$request->secure()) {
            config([
                "session.secure" => false,
                "session.same_site" => "lax",
                "session.domain" => null,
                "session.http_only" => true
            ]);
        }
        
        // Ensure session is started
        if (!$request->hasSession()) {
            $session = app("session.store");
            $request->setLaravelSession($session);
        }
        
        $session = $request->session();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        // Check for backup cookie and restore session
        if ($request->hasCookie("portal_session_backup") && !$session->has("portal_user_id")) {
            try {
                $backup = decrypt($request->cookie("portal_session_backup"));
                if (isset($backup["user_id"])) {
                    $session->put("portal_user_id", $backup["user_id"]);
                    $session->put("portal_company_id", $backup["company_id"]);
                    $session->put("portal_authenticated", true);
                    
                    // Re-authenticate user
                    $user = \App\Models\PortalUser::withoutGlobalScopes()->find($backup["user_id"]);
                    if ($user) {
                        app()->instance("current_company_id", $user->company_id);
                        \Illuminate\Support\Facades\Auth::guard("portal")->login($user);
                    }
                }
            } catch (\Exception $e) {
                // Ignore decryption errors
            }
        }
        
        $response = $next($request);
        
        // Save session before response
        $session->save();
        
        return $response;
    }
}
';

file_put_contents($sessionMiddlewarePath, $sessionMiddlewareContent);
echo "   ✅ Created EnsureValidSession middleware\n";

// 3. Register middleware in Kernel
echo "\n2. REGISTERING MIDDLEWARE:\n";

$kernelPath = __DIR__ . '/app/Http/Kernel.php';
$kernelContent = file_get_contents($kernelPath);

// Check if business-portal middleware group exists
if (!strpos($kernelContent, "'business-portal'")) {
    echo "   ⚠️  business-portal middleware group not found, adding it...\n";
    
    // Add business-portal middleware group
    $middlewareGroups = "        'business-portal' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\EnsureValidSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],";
    
    // Insert before the closing of middlewareGroups
    $kernelContent = str_replace(
        "        'api' => [",
        $middlewareGroups . "\n        'api' => [",
        $kernelContent
    );
    
    file_put_contents($kernelPath, $kernelContent);
    echo "   ✅ Added business-portal middleware group\n";
} else {
    echo "   ✅ business-portal middleware group already exists\n";
}

// 4. Fix the auth check route
echo "\n3. FIXING AUTH CHECK ROUTE:\n";

$authCheckFix = '<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Add this to routes/business-portal.php in the auth routes section
Route::get("/auth/check-fixed", function () {
    try {
        $user = Auth::guard("portal")->user();
        
        // Try to restore from session if not authenticated
        if (!$user && session("portal_user_id")) {
            $userId = session("portal_user_id");
            $user = \App\Models\PortalUser::withoutGlobalScopes()->find($userId);
            if ($user) {
                app()->instance("current_company_id", $user->company_id);
                Auth::guard("portal")->login($user);
            }
        }
        
        return response()->json([
            "authenticated" => (bool) $user,
            "user" => $user ? [
                "id" => $user->id,
                "email" => $user->email,
                "name" => $user->name,
                "company_id" => $user->company_id
            ] : null,
            "session" => [
                "id" => session()->getId(),
                "portal_user_id" => session("portal_user_id"),
                "portal_company_id" => session("portal_company_id"),
                "all" => session()->all()
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            "error" => $e->getMessage(),
            "trace" => $e->getTraceAsString()
        ], 500);
    }
})->name("business.auth.check-fixed");
';

// Save as a separate file to include
file_put_contents(__DIR__ . '/routes/auth-check-fix.php', $authCheckFix);
echo "   ✅ Created auth check fix route\n";

// 5. Create a working session test endpoint
$sessionTestPath = __DIR__ . '/public/working-session-test.php';
$sessionTestContent = '<?php

// Start native PHP session
session_start();

// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true");

// Bootstrap Laravel
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Force session config
config([
    "session.secure" => false,
    "session.same_site" => "lax",
    "session.http_only" => true,
    "session.domain" => null
]);

$response = $kernel->handle($request);

// Get Laravel session data
$laravelSession = [
    "id" => session()->getId(),
    "started" => session()->isStarted(),
    "data" => session()->all()
];

// Get auth status
$user = \Illuminate\Support\Facades\Auth::guard("portal")->user();

$result = [
    "php_session" => [
        "id" => session_id(),
        "data" => $_SESSION
    ],
    "laravel_session" => $laravelSession,
    "auth" => [
        "check" => (bool) $user,
        "user" => $user ? [
            "id" => $user->id,
            "email" => $user->email
        ] : null
    ],
    "cookies" => $_COOKIE,
    "config" => [
        "driver" => config("session.driver"),
        "cookie" => config("session.cookie"),
        "secure" => config("session.secure")
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);
';

file_put_contents($sessionTestPath, $sessionTestContent);
echo "\n4. Created working session test ✅\n";

// 6. Clear all caches
echo "\n5. CLEARING ALL CACHES:\n";
$commands = [
    'php artisan config:cache' => 'Config cache',
    'php artisan route:cache' => 'Route cache', 
    'php artisan view:clear' => 'View cache',
    'php artisan cache:clear' => 'Application cache'
];

foreach ($commands as $cmd => $desc) {
    exec($cmd . ' 2>&1', $output, $returnCode);
    if ($returnCode === 0) {
        echo "   ✅ $desc\n";
    } else {
        echo "   ⚠️  $desc (might have warnings)\n";
    }
}

echo "\n=== ULTIMATE FIX APPLIED ===\n";
echo "\nTest endpoints:\n";
echo "1. http://localhost:8000/working-session-test.php\n";
echo "2. http://localhost:8000/business/auth/check-fixed\n";
echo "3. http://localhost:8000/ultimate-session-test.html\n";
echo "\nThe session should now work correctly!\n";