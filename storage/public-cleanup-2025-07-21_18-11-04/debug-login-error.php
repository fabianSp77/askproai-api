<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;

header('Content-Type: application/json');

// Create test request
$request = Request::create('/api/auth/portal/login', 'POST', [
    'email' => 'demo@askproai.de',
    'password' => 'demo123',
    'device_name' => 'web'
], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'CONTENT_TYPE' => 'application/json',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
]);

try {
    // Get controller
    $controller = new AuthController();
    
    // Call method directly
    $response = $controller->portalLogin($request);
    
    echo json_encode([
        'success' => true,
        'status' => $response->getStatusCode(),
        'data' => json_decode($response->getContent(), true)
    ], JSON_PRETTY_PRINT);
    
} catch (\Illuminate\Validation\ValidationException $e) {
    echo json_encode([
        'success' => false,
        'type' => 'validation',
        'errors' => $e->errors(),
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'type' => 'exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice($e->getTrace(), 0, 5)
    ], JSON_PRETTY_PRINT);
}
?>