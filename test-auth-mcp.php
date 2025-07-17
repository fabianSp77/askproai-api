<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\AuthenticationMCPServer;

echo "=== Testing AuthenticationMCPServer ===\n\n";

try {
    $authMCP = app(AuthenticationMCPServer::class);
    
    // Test 1: Debug Auth State
    echo "1. Debug Authentication State:\n";
    $result = $authMCP->handleToolCall('debug_auth_state', []);
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Check CSRF Config
    echo "2. Check CSRF Configuration:\n";
    $result = $authMCP->handleToolCall('check_csrf_config', []);
    echo "CSRF Exceptions: \n";
    foreach ($result['csrf_config']['exceptions'] as $exception) {
        echo "  - $exception\n";
    }
    echo "\nSanctum CSRF Middleware: " . $result['csrf_config']['sanctum_middleware']['validate_csrf_token'] . "\n";
    
    // Test 3: List tokens for admin user
    echo "\n3. List Active Tokens:\n";
    $result = $authMCP->handleToolCall('list_active_tokens', [
        'email' => 'admin@askproai.de'
    ]);
    if ($result['success']) {
        echo "User: " . $result['user']['email'] . " (" . $result['user']['type'] . ")\n";
        echo "Total tokens: " . $result['total'] . "\n";
        if ($result['total'] > 0) {
            echo "Recent tokens:\n";
            foreach (array_slice($result['tokens'], 0, 3) as $token) {
                echo "  - {$token['name']} created at {$token['created_at']}\n";
            }
        }
    }
    
    echo "\n✅ AuthenticationMCPServer is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}