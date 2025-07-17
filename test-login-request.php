<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Http\Controllers\Portal\Auth\LoginController;

echo "🔍 Testing Login Request\n";
echo "=======================\n\n";

// Create a fake request
$request = Request::create('/business/login', 'POST', [
    'email' => 'fabianspitzer@icloud.com',
    'password' => 'demo123',
    '_token' => 'test-token'
]);

// Set up the request in the container
$app->instance('request', $request);

// Bootstrap the kernel
$response = $kernel->handle($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: \n";

$content = $response->getContent();
if (strlen($content) > 500) {
    echo substr($content, 0, 500) . "...\n";
} else {
    echo $content . "\n";
}

// Also test the controller directly
echo "\n\nDirect Controller Test:\n";
echo "======================\n";

try {
    $controller = new LoginController();
    
    // Create a proper request with validation
    $request = new Request([
        'email' => 'fabianspitzer@icloud.com',
        'password' => 'demo123'
    ]);
    
    $request->setMethod('POST');
    
    // Test the login method
    $response = $controller->login($request);
    
    echo "✅ Login successful!\n";
    echo "Response type: " . get_class($response) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        echo "Validation errors: " . json_encode($e->errors()) . "\n";
    }
}