<?php

// Simple webhook logger to capture all incoming requests to Retell endpoints

$logFile = '/var/www/api-gateway/storage/logs/retell-webhook-monitor.log';

// Endpoints to monitor
$endpoints = [
    '/api/retell/webhook',
    '/api/retell/function-call',
    '/api/retell/optimized-webhook',
    '/api/retell/enhanced-webhook'
];

echo "Monitoring Retell webhook endpoints...\n";
echo "Log file: $logFile\n";
echo "Endpoints: " . implode(', ', $endpoints) . "\n\n";

// Create a simple monitoring script
$monitorScript = <<<'SCRIPT'
#!/bin/bash

# Monitor nginx access logs for Retell endpoints
tail -f /var/log/nginx/access.log | while read line; do
    if echo "$line" | grep -E "(retell/webhook|retell/function-call|retell/optimized-webhook|retell/enhanced-webhook)" > /dev/null; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] $line"
    fi
done
SCRIPT;

file_put_contents('/tmp/monitor-retell.sh', $monitorScript);
chmod('/tmp/monitor-retell.sh', 0755);

echo "To monitor in real-time, run: /tmp/monitor-retell.sh\n";

// Check recent nginx logs
echo "\n=== Recent Retell Endpoint Hits (last 50) ===\n";
$recentHits = shell_exec("grep -E 'retell/(webhook|function-call|optimized-webhook|enhanced-webhook)' /var/log/nginx/access.log | tail -50");
echo $recentHits ?: "No recent hits found\n";

// Check if endpoints are registered
echo "\n=== Registered Routes ===\n";
foreach ($endpoints as $endpoint) {
    $routes = shell_exec("php artisan route:list | grep '$endpoint'");
    if ($routes) {
        echo "✓ $endpoint\n";
        echo $routes;
    } else {
        echo "✗ $endpoint - NOT FOUND\n";
    }
}