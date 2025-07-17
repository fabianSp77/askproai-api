<?php
/**
 * Fix JSON Request for API Login
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== API JSON REQUEST FIX ===\n\n";

// Method 1: Using server variables to simulate JSON
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/v2/portal/auth/login';
$_SERVER['CONTENT_TYPE'] = 'application/json';

$jsonData = json_encode([
    'email' => 'portal-test@askproai.de',
    'password' => 'test123'
]);

// Create request with JSON content
$request = \Illuminate\Http\Request::create(
    '/api/v2/portal/auth/login',
    'POST',
    [],    // GET parameters
    [],    // POST parameters  
    [],    // files
    $_SERVER,
    $jsonData
);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

echo "1. Testing with proper JSON request...\n";

try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "   Status: $statusCode\n";
    
    if ($statusCode === 200) {
        echo "   ✅ SUCCESS!\n";
        $json = json_decode($content, true);
        if (isset($json['token'])) {
            echo "   Token: " . substr($json['token'], 0, 30) . "...\n";
        }
    } else {
        echo "   ❌ Failed\n";
        echo "   Response: $content\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Update the test suite to use this approach
echo "\n2. Updating test suite...\n";

$testSuitePath = public_path('ultimate-portal-test-suite.php');
$content = file_get_contents($testSuitePath);

// Find and replace the API test section
$newApiTest = <<<'PHP'
                    // Test API Login with correct password
                    $jsonData = json_encode([
                        'email' => 'portal-test@askproai.de',
                        'password' => 'test123'
                    ]);
                    
                    $request = \Illuminate\Http\Request::create(
                        '/api/v2/portal/auth/login',
                        'POST',
                        [],
                        [],
                        [],
                        ['CONTENT_TYPE' => 'application/json'],
                        $jsonData
                    );
                    
                    $request->headers->set('Accept', 'application/json');
                    $request->headers->set('Content-Type', 'application/json');
PHP;

// Replace the problematic section
$pattern = '/\/\/ Test API Login with correct password.*?$request->headers->set\(\'Content-Type\', \'application\/json\'\);.*?\]\)\);/s';
$replacement = $newApiTest;

if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $replacement, $content);
    file_put_contents($testSuitePath, $content);
    echo "   ✅ Test suite updated\n";
} else {
    echo "   ⚠️  Could not update test suite automatically\n";
}

echo "\n=== DONE ===\n";
echo "The API login should now work correctly in the test suite!\n";