<?php
// Health Check Endpoint for AskProAI
// Created: 2025-01-15

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$checks = [
    'database' => false,
    'redis' => false,
    'queue' => false,
    'disk_space' => false,
    'memory' => false,
    'php_version' => false,
    'laravel_cache' => false
];

$details = [];
$startTime = microtime(true);

// Database Check
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=askproai_db', 'askproai_user', 'lkZ57Dju9EDjrMxn');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $result = $pdo->query('SELECT COUNT(*) as count FROM companies WHERE is_active = 1')->fetch();
    $checks['database'] = true;
    $details['database'] = ['active_companies' => $result['count']];
} catch (Exception $e) {
    $details['database'] = ['error' => $e->getMessage()];
}

// Redis Check
try {
    $redis = new Redis();
    if ($redis->connect('127.0.0.1', 6379, 1)) {
        $redis->ping();
        $checks['redis'] = true;
        $info = $redis->info();
        $details['redis'] = [
            'connected_clients' => $info['connected_clients'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'unknown'
        ];
    }
} catch (Exception $e) {
    $details['redis'] = ['error' => 'Connection failed'];
}

// Queue Check (check if queue table has recent jobs)
try {
    if ($checks['database']) {
        $recentJobs = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetch();
        $failedJobs = $pdo->query("SELECT COUNT(*) as count FROM failed_jobs WHERE failed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch();
        $checks['queue'] = true;
        $details['queue'] = [
            'recent_jobs' => $recentJobs['count'],
            'failed_jobs_24h' => $failedJobs['count']
        ];
    }
} catch (Exception $e) {
    $details['queue'] = ['error' => $e->getMessage()];
}

// Disk Space Check
$free = disk_free_space('/');
$total = disk_total_space('/');
$usedPercent = round((($total - $free) / $total) * 100, 2);
$checks['disk_space'] = $usedPercent < 90; // Alert if over 90% used
$details['disk_space'] = [
    'used_percent' => $usedPercent,
    'free_gb' => round($free / 1024 / 1024 / 1024, 2),
    'total_gb' => round($total / 1024 / 1024 / 1024, 2)
];

// Memory Check
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');
$checks['memory'] = $memoryUsage < 500 * 1024 * 1024; // Alert if over 500MB
$details['memory'] = [
    'current_mb' => round($memoryUsage / 1024 / 1024, 2),
    'limit' => $memoryLimit
];

// PHP Version Check
$phpVersion = phpversion();
$checks['php_version'] = version_compare($phpVersion, '8.0', '>=');
$details['php_version'] = $phpVersion;

// Laravel Cache Check
try {
    $cacheDir = '/var/www/api-gateway/bootstrap/cache';
    $configCached = file_exists($cacheDir . '/config.php');
    $routesCached = file_exists($cacheDir . '/routes-v7.php');
    $checks['laravel_cache'] = $configCached;
    $details['laravel_cache'] = [
        'config_cached' => $configCached,
        'routes_cached' => $routesCached
    ];
} catch (Exception $e) {
    $details['laravel_cache'] = ['error' => $e->getMessage()];
}

// Calculate response time
$responseTime = round((microtime(true) - $startTime) * 1000, 2);

// Overall status
$healthy = !in_array(false, $checks);
$criticalChecks = ['database', 'redis'];
$criticalHealthy = true;
foreach ($criticalChecks as $check) {
    if (!$checks[$check]) {
        $criticalHealthy = false;
        break;
    }
}

// Set appropriate HTTP status code
if (!$criticalHealthy) {
    http_response_code(503); // Service Unavailable
} elseif (!$healthy) {
    http_response_code(200); // Degraded but operational
} else {
    http_response_code(200); // All good
}

// Output response
echo json_encode([
    'status' => $criticalHealthy ? ($healthy ? 'healthy' : 'degraded') : 'unhealthy',
    'timestamp' => date('c'),
    'response_time_ms' => $responseTime,
    'checks' => $checks,
    'details' => $details,
    'environment' => [
        'app_env' => getenv('APP_ENV') ?: 'production',
        'app_debug' => getenv('APP_DEBUG') === 'true'
    ]
], JSON_PRETTY_PRINT);