<?php
/**
 * Quick Wins Health Check Endpoint
 * Temporary bypass while Laravel routing is being fixed
 */

header('Content-Type: application/json');
header('X-Powered-By: AskProAI-QuickWins');

// Initialize response
$response = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'service' => 'askproai',
    'version' => '1.0.0-quickwins',
    'checks' => []
];

// Check PHP
$response['checks']['php'] = [
    'status' => 'ok',
    'version' => PHP_VERSION
];

// Check Redis
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->ping();
    
    $info = $redis->info();
    $response['checks']['redis'] = [
        'status' => 'ok',
        'version' => $info['redis_version'] ?? 'unknown',
        'connected_clients' => $info['connected_clients'] ?? 0,
        'used_memory_human' => $info['used_memory_human'] ?? 'unknown'
    ];
} catch (Exception $e) {
    $response['status'] = 'degraded';
    $response['checks']['redis'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Check Database
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=askproai_db',
        'askproai_user',
        'lkZ57Dju9EDjrMxn'
    );
    
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM companies');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['checks']['database'] = [
        'status' => 'ok',
        'type' => 'mysql',
        'companies' => $result['count']
    ];
} catch (Exception $e) {
    $response['status'] = 'degraded';
    $response['checks']['database'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Check Quick Wins components
$response['checks']['quick_wins'] = [
    'webhook_controller' => file_exists(__DIR__ . '/../../app/Http/Controllers/OptimizedRetellWebhookController.php') ? 'ready' : 'missing',
    'rate_limiter' => file_exists(__DIR__ . '/../../app/Services/RateLimiter/EnhancedRateLimiter.php') ? 'ready' : 'missing',
    'cache_manager' => file_exists(__DIR__ . '/../../app/Services/Cache/CacheManager.php') ? 'ready' : 'missing',
    'repositories' => file_exists(__DIR__ . '/../../app/Repositories/OptimizedAppointmentRepository.php') ? 'ready' : 'missing'
];

// Set appropriate HTTP status code
http_response_code($response['status'] === 'ok' ? 200 : 503);

// Output response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);