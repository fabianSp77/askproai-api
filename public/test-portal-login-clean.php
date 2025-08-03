<?php
// Clean test for portal login
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Test portal user authentication
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "<h1>Portal Login Test</h1>";

// Check demo user
echo "<h2>1. Demo User Check</h2>";
$demoUser = PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if ($demoUser) {
    echo "<p style='color: green;'>✓ Demo user found (ID: {$demoUser->id})</p>";
    echo "<p>Email: {$demoUser->email}</p>";
    echo "<p>Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "</p>";
    
    // Test password
    $validPassword = Hash::check('password', $demoUser->password);
    echo "<p>Password 'password' valid: " . ($validPassword ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>') . "</p>";
} else {
    echo "<p style='color: red;'>✗ Demo user not found</p>";
}

// Check current session
echo "<h2>2. Session Status</h2>";
echo "<p>Session ID: " . session()->getId() . "</p>";
echo "<p>Session Cookie Name: " . session()->getName() . "</p>";
echo "<p>CSRF Token: " . substr(csrf_token(), 0, 20) . "...</p>";

// Check if already logged in
echo "<h2>3. Current Auth Status</h2>";
$portalAuth = Auth::guard('portal')->check();
echo "<p>Portal Auth: " . ($portalAuth ? '<span style="color: green;">✓ Logged In</span>' : '<span style="color: red;">✗ Not Logged In</span>') . "</p>";

if ($portalAuth) {
    $user = Auth::guard('portal')->user();
    echo "<p>Logged in as: {$user->email}</p>";
    echo "<p><a href='/business/logout' onclick='event.preventDefault(); document.getElementById(\"logout-form\").submit();'>Logout</a></p>";
    echo '<form id="logout-form" action="/business/logout" method="POST" style="display: none;">';
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    echo '</form>';
} else {
    // Show login form
    echo "<h2>4. Test Login</h2>";
    ?>
    <form action="/business/login" method="POST" style="max-width: 400px;">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">
        
        <div style="margin-bottom: 15px;">
            <label for="email" style="display: block; margin-bottom: 5px;">Email:</label>
            <input type="email" name="email" id="email" value="demo@askproai.de" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
            <input type="password" name="password" id="password" value="password" 
                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <button type="submit" style="background: #4F46E5; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            Login
        </button>
    </form>
    
    <div style="margin-top: 20px; padding: 15px; background: #f3f4f6; border-radius: 4px;">
        <p><strong>Test Credentials:</strong></p>
        <p>Email: demo@askproai.de</p>
        <p>Password: password</p>
    </div>
    <?php
}

// Show any errors
if (session('errors')) {
    echo "<h2 style='color: red;'>Errors:</h2>";
    echo "<ul style='color: red;'>";
    foreach (session('errors')->all() as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<h2>5. Debug Info</h2>";
echo "<pre>";
echo "Middleware groups:\n";
$router = app()->make('router');
if (method_exists($router, 'getMiddlewareGroups')) {
    $groups = $router->getMiddlewareGroups();
    if (isset($groups['business-portal'])) {
        echo "business-portal group:\n";
        foreach ($groups['business-portal'] as $middleware) {
            echo "  - $middleware\n";
        }
    }
}
echo "\nPortal Guard Config:\n";
print_r(config('auth.guards.portal'));
echo "</pre>";
?>