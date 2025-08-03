<?php
// Test script for business portal login
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test 1: Check middleware configuration
echo "<h2>1. Middleware Configuration</h2>";
$middleware = $app->make('router')->getMiddleware();
echo "<pre>";
echo "Registered middleware aliases:\n";
foreach ($middleware as $alias => $class) {
    if (str_contains($alias, 'portal') || str_contains($class, 'Portal')) {
        echo "  $alias => $class\n";
    }
}
echo "</pre>";

// Test 2: Check middleware groups
echo "<h2>2. Middleware Groups</h2>";
$middlewareGroups = config('app.middleware_groups', []);
echo "<pre>";
echo "Business-portal group:\n";
// Get from router instead of config
$router = app()->make('router');
if (method_exists($router, 'getMiddlewareGroups')) {
    $groups = $router->getMiddlewareGroups();
    if (isset($groups['business-portal'])) {
        print_r($groups['business-portal']);
    }
} else {
    echo "Unable to retrieve middleware groups from router\n";
}
echo "</pre>";

// Test 3: Check session configuration
echo "<h2>3. Session Configuration</h2>";
echo "<pre>";
echo "Session driver: " . config('session.driver') . "\n";
echo "Session cookie: " . config('session.cookie') . "\n";
echo "Portal session cookie: " . env('PORTAL_SESSION_COOKIE', 'askproai_portal_session') . "\n";
echo "Session domain: " . config('session.domain') . "\n";
echo "Session path: " . config('session.path') . "\n";
echo "</pre>";

// Test 4: Check auth configuration
echo "<h2>4. Auth Configuration</h2>";
echo "<pre>";
echo "Default guard: " . config('auth.defaults.guard') . "\n";
echo "Portal guard config:\n";
print_r(config('auth.guards.portal'));
echo "\nPortal provider config:\n";
print_r(config('auth.providers.portal_users'));
echo "</pre>";

// Test 5: Database test - find demo user
echo "<h2>5. Demo User Check</h2>";
echo "<pre>";
try {
    $demoUser = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
        ->where('email', 'demo@askproai.de')
        ->first();
    
    if ($demoUser) {
        echo "Demo user found:\n";
        echo "  ID: " . $demoUser->id . "\n";
        echo "  Email: " . $demoUser->email . "\n";
        echo "  Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
        echo "  Company ID: " . $demoUser->company_id . "\n";
        echo "  Password hash: " . substr($demoUser->password, 0, 20) . "...\n";
        
        // Test password
        $testPassword = 'password';
        $isValid = \Illuminate\Support\Facades\Hash::check($testPassword, $demoUser->password);
        echo "  Password 'password' valid: " . ($isValid ? 'Yes' : 'No') . "\n";
    } else {
        echo "Demo user NOT found\n";
    }
} catch (\Exception $e) {
    echo "Error checking demo user: " . $e->getMessage() . "\n";
}
echo "</pre>";

// Test 6: Route check
echo "<h2>6. Route Configuration</h2>";
echo "<pre>";
$loginRoute = route('business.login');
$loginPostRoute = route('business.login.post');
echo "Login GET route: $loginRoute\n";
echo "Login POST route: $loginPostRoute\n";
echo "</pre>";

// Test 7: Create a simple login test
echo "<h2>7. Login Test Form</h2>";
?>
<form action="<?= route('business.login.post') ?>" method="POST">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <p>
        <label>Email: <input type="email" name="email" value="demo@askproai.de" style="width: 300px;"></label>
    </p>
    <p>
        <label>Password: <input type="password" name="password" value="password" style="width: 300px;"></label>
    </p>
    <p>
        <button type="submit" style="padding: 10px 20px; background: #4F46E5; color: white; border: none; cursor: pointer;">
            Test Login
        </button>
    </p>
</form>

<h2>8. Session Test</h2>
<pre>
<?php
// Initialize session
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "Current session ID: " . session()->getId() . "\n";
echo "Session name: " . session()->getName() . "\n";
echo "Session driver: " . session()->getDefaultDriver() . "\n";
echo "Session started: " . (session()->isStarted() ? 'Yes' : 'No') . "\n";

// Check cookies
echo "\nCookies:\n";
foreach ($_COOKIE as $name => $value) {
    if (str_contains($name, 'session') || str_contains($name, 'XSRF')) {
        echo "  $name = " . substr($value, 0, 20) . "...\n";
    }
}
?>
</pre>