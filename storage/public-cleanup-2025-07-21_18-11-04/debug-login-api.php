<?php
/**
 * Debug API Login
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;

header('Content-Type: text/plain');

echo "=== API Login Debug ===\n\n";

// 1. Check if user exists
$email = 'demo@askproai.de';
$password = 'demo123';

$user = PortalUser::withoutGlobalScopes()->where('email', $email)->first();

if (!$user) {
    echo "❌ User not found: $email\n";
    exit;
}

echo "✅ User found:\n";
echo "   - ID: " . $user->id . "\n";
echo "   - Email: " . $user->email . "\n";
echo "   - Company ID: " . $user->company_id . "\n";
echo "   - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
echo "   - Password Hash: " . substr($user->password, 0, 30) . "...\n\n";

// 2. Test password
echo "Testing password '$password':\n";
$passwordValid = Hash::check($password, $user->password);
echo "   - Password valid: " . ($passwordValid ? '✅ YES' : '❌ NO') . "\n\n";

// 3. Test API endpoint directly
echo "Testing API endpoint /api/auth/portal/login:\n";

$request = Request::create('/api/auth/portal/login', 'POST', [
    'email' => $email,
    'password' => $password,
    'device_name' => 'test'
], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'CONTENT_TYPE' => 'application/json',
]);

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $statusCode = $response->getStatusCode();
    
    echo "   - Status Code: $statusCode\n";
    echo "   - Response: " . $content . "\n\n";
    
    if ($statusCode === 200) {
        $data = json_decode($content, true);
        if (isset($data['token'])) {
            echo "✅ LOGIN SUCCESSFUL!\n";
            echo "   - Token: " . substr($data['token'], 0, 20) . "...\n";
            echo "   - User: " . $data['user']['email'] . "\n";
        }
    } else {
        echo "❌ LOGIN FAILED\n";
        $data = json_decode($content, true);
        if (isset($data['errors'])) {
            echo "   - Errors: " . json_encode($data['errors']) . "\n";
        }
        if (isset($data['message'])) {
            echo "   - Message: " . $data['message'] . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "   - File: " . $e->getFile() . "\n";
    echo "   - Line: " . $e->getLine() . "\n";
}

// 4. Check route exists
echo "\nChecking if route exists:\n";
$router = app('router');
$routes = $router->getRoutes();
$found = false;
foreach ($routes as $route) {
    if ($route->uri() === 'api/auth/portal/login' && in_array('POST', $route->methods())) {
        $found = true;
        echo "✅ Route found: POST /api/auth/portal/login\n";
        echo "   - Action: " . $route->getActionName() . "\n";
        echo "   - Middleware: " . implode(', ', $route->middleware()) . "\n";
        break;
    }
}
if (!$found) {
    echo "❌ Route not found!\n";
}
?>