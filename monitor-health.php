<?php
// Continuous monitoring script
// Run this periodically to check system health

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

while (true) {
    echo "\n🔍 Running health check at " . date('Y-m-d H:i:s') . "\n";
    
    $output = shell_exec('php artisan askproai:preflight --quick --json 2>&1');
    $data = json_decode($output, true);
    
    if ($data && $data['summary']['errors'] > 0) {
        // Send alert (email, Slack, etc.)
        echo "⚠️ ALERT: System has " . $data['summary']['errors'] . " errors!\n";
        
        // Log to file
        file_put_contents(
            'storage/logs/preflight-alerts.log',
            date('Y-m-d H:i:s') . " - Errors detected: " . json_encode($data['summary']) . "\n",
            FILE_APPEND
        );
    } else {
        echo "✅ System healthy\n";
    }
    
    // Wait 5 minutes
    sleep(300);
}