<?php
/**
 * Fix Portal Authentication
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== FIXING PORTAL AUTHENTICATION ===\n\n";

// 1. Create a test user with known password
echo "1. Creating test portal user...\n";

// Delete existing test user
\App\Models\PortalUser::where('email', 'portal-test@askproai.de')->delete();

// Create new test user
$testUser = \App\Models\PortalUser::create([
    'name' => 'Portal Test User',
    'email' => 'portal-test@askproai.de', 
    'password' => bcrypt('test123'),
    'company_id' => 1,
    'is_active' => true,
    'role' => 'admin'
]);

echo "✅ Created user: {$testUser->email} (password: test123)\n";

// 2. Test authentication directly
echo "\n2. Testing direct authentication...\n";

$attempt = \Auth::guard('portal')->attempt([
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]);

if ($attempt) {
    echo "✅ Authentication successful!\n";
    
    $user = \Auth::guard('portal')->user();
    echo "   User ID: {$user->id}\n";
    echo "   User Name: {$user->name}\n";
    
    // Create token
    try {
        $token = $user->createToken('test-token')->plainTextToken;
        echo "✅ Token created: " . substr($token, 0, 20) . "...\n";
    } catch (\Exception $e) {
        echo "❌ Token creation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Authentication failed!\n";
    
    // Debug why
    $user = \App\Models\PortalUser::where('email', 'portal-test@askproai.de')->first();
    if (!$user) {
        echo "   User not found\n";
    } else {
        echo "   User found (ID: {$user->id})\n";
        echo "   Is active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        
        // Test password directly
        if (\Hash::check('test123', $user->password)) {
            echo "   Password hash is correct\n";
        } else {
            echo "   Password hash is WRONG\n";
        }
    }
}

// 3. Now test API endpoint
echo "\n3. Testing API endpoint...\n";

$request = \Illuminate\Http\Request::create('/api/v2/portal/auth/login', 'POST', [
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]);

$request->headers->set('Accept', 'application/json');

try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = json_decode($response->getContent(), true);
    
    echo "Response Status: {$statusCode}\n";
    
    if ($statusCode === 200) {
        echo "✅ API Login successful!\n";
        if (isset($content['token'])) {
            echo "   Token: " . substr($content['token'], 0, 20) . "...\n";
        }
    } else {
        echo "❌ API Login failed\n";
        echo "   Response: " . json_encode($content, JSON_PRETTY_PRINT) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// 4. Clear caches
echo "\n4. Clearing caches...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "✅ Caches cleared\n";

echo "\n=== TEST CREDENTIALS ===\n";
echo "Email: portal-test@askproai.de\n";
echo "Password: test123\n";
echo "\nYou can now test the API login with these credentials.\n";