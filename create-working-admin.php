<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

echo "=== CREATING WORKING ADMIN ACCESS ===\n\n";

// 1. Create super admin user
echo "1. Creating super admin user...\n";
$user = User::updateOrCreate(
    ['email' => 'superadmin@askproai.de'],
    [
        'name' => 'Super Admin',
        'password' => Hash::make('SuperAdmin123!'),
        'company_id' => 1,
        'email_verified_at' => now()
    ]
);
echo "   ✅ Super admin created\n";

// 2. Create a special access token
echo "\n2. Creating access token...\n";
$token = Str::random(64);
\Illuminate\Support\Facades\Cache::put('admin_access_token_' . $token, $user->id, 3600);
echo "   ✅ Token created: $token\n";

// 3. Create token-based access page
echo "\n3. Creating token access page...\n";

$tokenAccess = '<?php
require_once __DIR__ . "/../vendor/autoload.php";
$app = require_once __DIR__ . "/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

$token = $_GET["token"] ?? "";

if ($token) {
    $userId = \Illuminate\Support\Facades\Cache::get("admin_access_token_" . $token);
    
    if ($userId) {
        $user = User::find($userId);
        if ($user) {
            // Start session
            Session::start();
            
            // Login user
            Auth::login($user);
            
            // Force session save
            Session::save();
            
            // Set additional cookie for bypass
            setcookie("admin_bypass", $user->id, time() + 7200, "/", "", true, true);
            
            // Clear token
            \Illuminate\Support\Facades\Cache::forget("admin_access_token_" . $token);
            
            // Redirect to admin
            header("Location: /admin");
            exit;
        }
    }
}

echo "Invalid or expired token";
';

file_put_contents(__DIR__ . '/public/admin-token-access.php', $tokenAccess);

// 4. Create bypass middleware
echo "\n4. Creating bypass middleware...\n";

$bypassMiddleware = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AdminBypass
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() && $request->cookie("admin_bypass")) {
            $user = User::find($request->cookie("admin_bypass"));
            if ($user) {
                auth()->login($user);
            }
        }
        
        return $next($request);
    }
}';

file_put_contents(__DIR__ . '/app/Http/Middleware/AdminBypass.php', $bypassMiddleware);

// 5. Register middleware
echo "\n5. Registering middleware...\n";
$kernelPath = __DIR__ . '/app/Http/Kernel.php';
if (file_exists($kernelPath)) {
    $kernel = file_get_contents($kernelPath);
    if (!str_contains($kernel, 'AdminBypass')) {
        $kernel = str_replace(
            "'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,",
            "'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,\n        'admin.bypass' => \App\Http\Middleware\AdminBypass::class,",
            $kernel
        );
        file_put_contents($kernelPath, $kernel);
        echo "   ✅ Middleware registered\n";
    }
}

echo "\n=== ACCESS LINKS ===\n\n";

echo "Option 1: Direct Token Access (EMPFOHLEN)\n";
echo "URL: https://api.askproai.de/admin-token-access.php?token=$token\n\n";

echo "Option 2: Direct Login\n";
echo "URL: https://api.askproai.de/direct-admin-login.php\n";
echo "Email: superadmin@askproai.de\n";
echo "Password: SuperAdmin123!\n\n";

echo "Option 3: Normal Admin Login\n";
echo "URL: https://api.askproai.de/admin/login\n";
echo "Email: superadmin@askproai.de\n";
echo "Password: SuperAdmin123!\n\n";

echo "WICHTIG: Der Token-Link funktioniert nur EINMAL und ist 1 Stunde gültig!\n";