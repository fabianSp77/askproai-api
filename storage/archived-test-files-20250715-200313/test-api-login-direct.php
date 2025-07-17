<?php
/**
 * Direct API Login Test
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== DIRECT API LOGIN TEST ===\n\n";

// Create request
$request = \Illuminate\Http\Request::create('/api/v2/portal/auth/login', 'POST', [
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

echo "1. Sending request to: /api/v2/portal/auth/login\n";
echo "   Email: portal-test@askproai.de\n";
echo "   Password: test123\n\n";

try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "2. Response:\n";
    echo "   Status: $statusCode\n";
    echo "   Content: $content\n\n";
    
    if ($statusCode === 200) {
        echo "✅ SUCCESS! API Login is working!\n";
        $json = json_decode($content, true);
        if (isset($json['token'])) {
            echo "✅ Token received: " . substr($json['token'], 0, 30) . "...\n";
        }
    } else {
        echo "❌ FAILED! Status code: $statusCode\n";
        
        // Check recent logs
        echo "\n3. Recent log entries:\n";
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recentLines = array_slice($lines, -10);
            
            foreach ($recentLines as $line) {
                if (strpos($line, 'Portal API') !== false || strpos($line, 'ERROR') !== false) {
                    echo "   " . substr($line, 0, 150) . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

// Check if user exists
echo "\n4. Checking test user:\n";
$user = \App\Models\PortalUser::where('email', 'portal-test@askproai.de')->first();
if ($user) {
    echo "   ✅ User exists (ID: {$user->id})\n";
    echo "   Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
    echo "   Role: {$user->role}\n";
    
    // Test password
    if (password_verify('test123', $user->password)) {
        echo "   ✅ Password is correct\n";
    } else {
        echo "   ❌ Password verification failed\n";
    }
    
    // Test token creation
    try {
        $token = $user->createToken('test')->plainTextToken;
        echo "   ✅ Can create tokens\n";
        $user->tokens()->delete();
    } catch (\Exception $e) {
        echo "   ❌ Cannot create tokens: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ User not found\n";
}