<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "\n🔍 API AUTHENTICATION CHECK\n";
echo "============================\n\n";

// 1. Check current auth state
echo "1️⃣ CURRENT AUTH STATE:\n";
echo "----------------------\n";

// Simulate web context
Auth::guard('portal')->loginUsingId(22); // fabianspitzer@icloud.com ID

$user = Auth::guard('portal')->user();
if ($user) {
    echo "✅ User authenticated:\n";
    echo "   ID: {$user->id}\n";
    echo "   Email: {$user->email}\n";
    echo "   Guard: portal\n";
} else {
    echo "❌ No user authenticated\n";
}

// 2. Check API routes
echo "\n2️⃣ API ROUTES CHECK:\n";
echo "--------------------\n";

$routes = [
    'business.api.dashboard',
    'business.api.notifications.index',
    'business.api.calls.index'
];

foreach ($routes as $routeName) {
    try {
        $route = Route::getRoutes()->getByName($routeName);
        if ($route) {
            echo "✅ Route exists: {$routeName}\n";
            echo "   URI: " . $route->uri() . "\n";
            echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
        } else {
            echo "❌ Route not found: {$routeName}\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error checking route {$routeName}: " . $e->getMessage() . "\n";
    }
}

// 3. Check session configuration
echo "\n3️⃣ SESSION CONFIGURATION:\n";
echo "-------------------------\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Cookie: " . config('session.cookie') . "\n";
echo "Domain: " . config('session.domain') . "\n";
echo "Same Site: " . config('session.same_site') . "\n";
echo "HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n";
echo "Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

// 4. Check CSRF configuration
echo "\n4️⃣ CSRF CONFIGURATION:\n";
echo "----------------------\n";
$csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken(app());
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$exceptions = $property->getValue($csrfMiddleware);
echo "CSRF Exceptions:\n";
foreach ($exceptions as $exception) {
    echo "   - {$exception}\n";
}

// 5. Check API authentication middleware
echo "\n5️⃣ PORTAL AUTH MIDDLEWARE:\n";
echo "--------------------------\n";
$middlewareClass = 'App\\Http\\Middleware\\PortalAuthenticate';
if (class_exists($middlewareClass)) {
    echo "✅ PortalAuthenticate middleware exists\n";
} else {
    echo "❌ PortalAuthenticate middleware not found!\n";
    
    // Check alternative
    $altMiddleware = 'App\\Http\\Middleware\\Authenticate';
    if (class_exists($altMiddleware)) {
        echo "✅ Default Authenticate middleware exists\n";
    }
}

echo "\n📝 RECOMMENDATIONS:\n";
echo "==================\n";
echo "1. Ensure API calls include credentials\n";
echo "2. Check if cookies are being sent with requests\n";
echo "3. Verify CORS settings if API is on different domain\n";
echo "4. Make sure session cookie is httpOnly=false for JavaScript access\n";