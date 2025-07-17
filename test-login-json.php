<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing JSON Login ===\n\n";

// Create proper JSON request
$content = json_encode([
    'email' => 'admin@askproai.de',
    'password' => 'admin123'
]);

$request = \Illuminate\Http\Request::create(
    '/api/admin/auth/login',
    'POST',
    [],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CSRF_TOKEN' => 'test-token'
    ],
    $content
);

// Set the JSON content properly
$request->setJson(new \Symfony\Component\HttpFoundation\ParameterBag(json_decode($content, true)));

try {
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "\nResponse Body:\n";
    $responseContent = $response->getContent();
    echo $responseContent . "\n";
    
    // Pretty print if JSON
    $data = json_decode($responseContent, true);
    if ($data) {
        echo "\nParsed Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
    
    if ($response->getStatusCode() === 200) {
        echo "\n✅ Login successful!\n";
        if (isset($data['token'])) {
            echo "Token: " . substr($data['token'], 0, 30) . "...\n";
            echo "\nYou can now use this token to access the API\n";
        }
    } else {
        echo "\n❌ Login failed with status " . $response->getStatusCode() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . substr($e->getTraceAsString(), 0, 1000) . "\n";
}

// Also test with curl command
echo "\n\n=== CURL Command to test ===\n";
echo "curl -X POST https://api.askproai.de/api/admin/auth/login \\
  -H 'Content-Type: application/json' \\
  -H 'Accept: application/json' \\
  -d '{\"email\":\"admin@askproai.de\",\"password\":\"admin123\"}'\n";

$kernel->terminate($request, $response);