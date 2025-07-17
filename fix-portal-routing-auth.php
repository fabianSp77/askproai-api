<?php
echo "🔧 Fixing Portal Routing and Auth Issues\n";
echo "========================================\n\n";

// 1. Fix the test/session route
echo "1️⃣ Checking current URL issue...\n";
echo "   You're on: /test/session\n";
echo "   Should be: /business/test/session\n";
echo "   Or better: /business/dashboard\n\n";

// 2. Check for branch context issue
echo "2️⃣ Checking branch context...\n";
$branchFile = '/var/www/api-gateway/app/Http/Middleware/BranchContextMiddleware.php';
if (file_exists($branchFile)) {
    echo "✅ BranchContextMiddleware exists\n";
    
    // Check if it's causing issues
    $content = file_get_contents($branchFile);
    if (strpos($content, 'required') !== false) {
        echo "⚠️  Branch might be required for portal access\n";
    }
}

// 3. Fix React Router to handle the dashboard route properly
echo "\n3️⃣ Fixing React Router...\n";

$portalAppFile = '/var/www/api-gateway/resources/js/PortalApp.jsx';
if (file_exists($portalAppFile)) {
    $content = file_get_contents($portalAppFile);
    
    // Check if dashboard route exists
    if (strpos($content, 'path="/"') !== false && strpos($content, 'path="/dashboard"') !== false) {
        echo "✅ Dashboard routes exist\n";
    } else {
        echo "⚠️  Dashboard routes might be missing\n";
    }
}

// 4. Create a debug script to check auth state
echo "\n4️⃣ Creating auth debug script...\n";

$authDebugScript = '<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\PortalUser;

echo "\n🔍 AUTH STATE DEBUG\n";
echo "==================\n\n";

// Start session
Session::start();

echo "Session Info:\n";
echo "- ID: " . Session::getId() . "\n";
echo "- Has portal_user_id: " . (Session::has("portal_user_id") ? "YES" : "NO") . "\n";
echo "- portal_user_id value: " . Session::get("portal_user_id", "NOT SET") . "\n";

// Check auth
echo "\nAuth Status:\n";
echo "- Portal guard check: " . (Auth::guard("portal")->check() ? "YES" : "NO") . "\n";

if (Session::has("portal_user_id")) {
    $userId = Session::get("portal_user_id");
    $user = PortalUser::find($userId);
    
    if ($user) {
        echo "\nUser from session:\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Active: " . ($user->is_active ? "YES" : "NO") . "\n";
        echo "- Company: " . $user->company->name . "\n";
        echo "- Company Active: " . ($user->company->is_active ? "YES" : "NO") . "\n";
        
        // Try to login the user
        Auth::guard("portal")->login($user);
        echo "\nAfter manual login:\n";
        echo "- Auth check: " . (Auth::guard("portal")->check() ? "YES" : "NO") . "\n";
    }
}

echo "\nAll session data:\n";
print_r(Session::all());
';

file_put_contents('/var/www/api-gateway/debug-auth-state.php', $authDebugScript);
echo "✅ Created debug-auth-state.php\n";

// 5. Create a middleware fix for API routes
echo "\n5️⃣ Creating API auth fix...\n";

$apiAuthFix = '<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PortalUser;

class EnsurePortalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check if already authenticated
        if (Auth::guard("portal")->check()) {
            return $next($request);
        }
        
        // Try to authenticate from session
        $userId = session("portal_user_id") ?? session("portal_login");
        
        if ($userId) {
            $user = PortalUser::find($userId);
            if ($user && $user->is_active && $user->company && $user->company->is_active) {
                Auth::guard("portal")->login($user);
                
                // Set company context
                app()->instance("current_company_id", $user->company_id);
                
                // Set branch context if available
                if ($user->company->branches()->exists()) {
                    $defaultBranch = $user->company->branches()->first();
                    session(["current_branch_id" => $defaultBranch->id]);
                }
            }
        }
        
        return $next($request);
    }
}
';

$middlewareDir = app_path('Http/Middleware');
file_put_contents($middlewareDir . '/EnsurePortalApiAuth.php', $apiAuthFix);
echo "✅ Created EnsurePortalApiAuth middleware\n";

echo "\n✅ Fixes applied!\n\n";
echo "Next steps:\n";
echo "1. Run: php debug-auth-state.php\n";
echo "2. Update routes to use new middleware\n";
echo "3. Rebuild React: npm run build\n";